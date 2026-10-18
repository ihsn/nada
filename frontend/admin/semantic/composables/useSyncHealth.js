import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useSemanticApi } from './useSemanticApi.js';

/** Minutes since a unix time, or null. */
function minutesSince(ts) {
  return ts ? Math.floor((Date.now() / 1000 - Number(ts)) / 60) : null;
}

function when(ts) {
  return ts ? new Date(Number(ts) * 1000).toLocaleString() : '';
}

/** How long the queue may sit unread before it counts as not being read (when NADA-AI does not say its interval). */
const DEFAULT_POLL_GRACE_MINUTES = 15;
/** How long items may wait with no result recorded, while NADA-AI reads the queue, before it counts as stuck. */
const STALLED_MINUTES = 30;
/** How often the status is read again while there is work, and while there is none (to notice new changes). */
const ACTIVE_REFRESH_MS = 12000;
const IDLE_REFRESH_MS = 60000;

// One copy for the whole dashboard: the banner, the activity card and the menu badge read the same status, and only
// one refresh runs at a time.
const status = ref(null);
const scheduler = ref(null);
const schedulerError = ref(false);
const loadedAt = ref(null);
let timer = null;
let users = 0;
let running = null;

function hasWork() {
  const queue = status.value?.queue || {};
  return scheduler.value?.state === 'processing' || (queue.pending || 0) > 0 || (queue.sync_pending || 0) > 0;
}

function schedule() {
  clearTimeout(timer);
  timer = null;
  if (users > 0) {
    timer = setTimeout(refresh, hasWork() ? ACTIVE_REFRESH_MS : IDLE_REFRESH_MS);
  }
}

async function refresh() {
  if (running) return running;
  const api = useSemanticApi();
  running = (async () => {
    const [s, sch] = await Promise.allSettled([api.getSyncStatus(), api.getSchedulerStatus()]);
    status.value = s.status === 'fulfilled' ? s.value : null;
    scheduler.value = sch.status === 'fulfilled' ? sch.value : null;
    schedulerError.value = sch.status === 'rejected';
    loadedAt.value = Date.now();
  })();
  try {
    await running;
  } finally {
    running = null;
    schedule();
  }
}

/**
 * Whether NADA-AI keeps the index in sync: NADA's own tracking status (always available) and NADA-AI's scheduler
 * status (when NADA-AI answers). Read again every few seconds while there is work, and every minute while idle.
 *
 * problems: what needs attention, {key, type, text}, most serious first (the banner).
 * activity: what is being done now (the activity card), see below.
 */
export function useSyncHealth() {
  onMounted(() => {
    users += 1;
    if (!loadedAt.value) refresh();
    else schedule();
  });
  onUnmounted(() => {
    users -= 1;
    if (users <= 0) {
      users = 0;
      clearTimeout(timer);
      timer = null;
    }
  });

  const tracksNadaAi = computed(() => status.value?.index_engine === 'nada_ai');

  /**
   * state: processing | idle | paused | not_tracking | disabled | not_configured | crashed | stuck | running_elsewhere
   *        | unreachable (NADA-AI does not answer) | unknown
   * done/left/total/percent: this pass of the scheduler (it runs until the queue is empty) against what NADA still
   * has pending; rate in items per minute; etaMinutes from it.
   */
  const activity = computed(() => {
    const queue = status.value?.queue || {};
    const sch = scheduler.value;
    const left = Number(queue.pending) || 0;
    const base = {
      state: sch ? (sch.state || 'unknown') : schedulerError.value ? 'unreachable' : 'unknown',
      left,
      failed: Number(queue.failed) || 0,
      syncLeft: Number(queue.sync_pending) || 0,
      current: sch?.current || null,
      pause: sch?.pause || null,
      nextCheckAt: sch?.next_poll_at || null,
      lastResultAt: queue.last_result_at || null,
      done: null,
      total: null,
      percent: null,
      rate: null,
      etaMinutes: null,
      passFailed: 0,
    };
    const pass = sch?.pass;
    if (sch?.state === 'processing' && pass?.started_at) {
      // what NADA has taken off its queue: processed items wait for their ack (sent in small batches), and until then
      // NADA still counts them as pending
      const done = Number(pass.acked ?? pass.processed) || 0;
      const total = done + left;
      const minutes = Math.max((Date.now() / 1000 - Number(pass.started_at)) / 60, 1 / 60);
      const rate = done / minutes;
      base.done = done;
      base.total = total;
      base.percent = total ? Math.round((100 * done) / total) : 100;
      base.rate = rate;
      base.etaMinutes = rate > 0 ? Math.ceil(left / rate) : null;
      base.passFailed = Number(pass.failed) || 0;
    }
    return base;
  });

  const problems = computed(() => {
    const out = [];
    const st = status.value;
    const sch = scheduler.value;
    if (!st || !tracksNadaAi.value) {
      // tracking for another engine (or none): nothing below applies to NADA-AI
      return out;
    }

    const tracked = st.tracked || {};
    const queue = st.queue || {};

    if (sch) {
      const pause = sch.pause || {};
      const reasons = {
        manual: `Indexing is paused${pause.since ? ` since ${when(pause.since)}` : ''}: catalog changes are queued but not applied until it is resumed (Indexing activity › Resume).`,
        acks_failing: `NADA-AI processes changes but NADA does not record the results: ${pause.message || ''}`,
        access_denied: `NADA refuses NADA-AI's key: ${pause.message || 'check the key NADA-AI uses for NADA'}`,
        error: `NADA-AI could not read the queue or the records from NADA (see NADA-AI's log): ${pause.message || ''}`,
        firewall: `A firewall blocks NADA-AI's requests to NADA${pause.message ? `: ${pause.message}` : ''}. Allow NADA-AI's address, or point it at NADA's internal URL.`,
        nada_unavailable: 'NADA-AI cannot reach NADA.',
        not_tracking: 'NADA does not track changes for this NADA-AI.',
        other_nada_ai: 'NADA tracks changes for another NADA-AI instance; this one is refused.',
      };
      if (sch.state === 'crashed') {
        out.push({ key: 'crashed', type: 'error', text: `NADA-AI's scheduler stopped with an error at ${when(sch.crash?.at)}${sch.restarts ? ` (restarted ${sch.restarts} times)` : ''}: ${sch.crash?.message || ''}` });
      } else if (sch.state === 'stuck') {
        out.push({ key: 'stuck', type: 'error', text: `NADA-AI's scheduler has not moved since ${when(sch.heartbeat_at)}${sch.current?.object_key ? ` (on ${sch.current.object_key})` : ''}. Restart NADA-AI.` });
      } else if (sch.state === 'disabled') {
        out.push({ key: 'disabled', type: 'warning', text: "NADA-AI's scheduler is turned off: catalog changes stay queued until someone uses Process now (NADA_RECONCILE_SEARCH_INDEX_ENABLED)." });
      } else if (sch.state === 'not_configured') {
        out.push({ key: 'not_configured', type: 'error', text: "NADA-AI does not have NADA's URL configured." });
      } else if (pause.reason === 'manual') {
        out.push({ key: 'paused-manual', type: 'warning', text: reasons.manual });
      } else if (sch.state === 'paused' || sch.state === 'not_tracking') {
        const text = reasons[pause.reason] || pause.message || 'NADA-AI has paused the queue.';
        const until = pause.resume_at ? ` Next try at ${when(pause.resume_at)}.` : '';
        out.push({ key: `paused-${pause.reason}`, type: pause.reason === 'nada_unavailable' ? 'warning' : 'error', text: text + until });
      }
    } else if (schedulerError.value) {
      out.push({ key: 'unreachable', type: 'warning', text: 'NADA-AI is not answering; what NADA sees of the queue is below.' });
    }

    // NADA's own signals: they tell that something is wrong even when NADA-AI cannot say why
    const pending = Number(queue.pending) || 0;
    const graceMinutes = sch?.interval_seconds ? Math.ceil((3 * sch.interval_seconds) / 60) : DEFAULT_POLL_GRACE_MINUTES;
    const sincePoll = minutesSince(tracked.last_poll_at);
    const sinceResult = minutesSince(queue.last_result_at);
    const sinceOldest = minutesSince(queue.oldest_pending_at);
    if (pending > 0 && (!sch || sch.state === 'idle' || sch.state === 'processing')) {
      if (sincePoll === null || sincePoll > graceMinutes) {
        out.push({
          key: 'not-polled',
          type: 'warning',
          text: tracked.last_poll_at
            ? `NADA-AI has not read the change queue since ${when(tracked.last_poll_at)}; ${pending} changes are waiting.`
            : `NADA-AI has not read the change queue yet; ${pending} changes are waiting.`,
        });
      } else if (sinceOldest !== null && sinceOldest > STALLED_MINUTES && (sinceResult === null || sinceResult > STALLED_MINUTES)) {
        // a long queue is not a problem as long as results keep being recorded
        out.push({
          key: 'stalled',
          type: 'warning',
          text: queue.last_result_at
            ? `NADA-AI reads the queue, but no result has been recorded since ${when(queue.last_result_at)}.`
            : 'NADA-AI reads the queue, but no result has been recorded yet.',
        });
      }
    }

    if ((queue.failed || 0) > 0) {
      out.push({ key: 'failed', type: 'warning', text: `${queue.failed} queued changes failed. See Activity › Change queue to retry them.` });
    }

    if ((!tracked.last_sync_at || Number(tracked.last_sync_at) < Number(tracked.tracked_since)) && !(queue.sync_pending > 0)) {
      out.push({
        key: 'sync-needed',
        type: 'info',
        text: 'NADA started tracking this index afresh. If NADA-AI already has an index, use Indexing › Maintenance › Use the existing index; otherwise Update all on the Indexing page.',
      });
    }

    return out;
  });

  /**
   * Call ``callback`` when a new result has been recorded (the queue moved), at most every ``minIntervalMs``: for
   * coverage numbers that should follow the indexing without reloading the page.
   */
  function onProgress(callback, minIntervalMs = 30000) {
    let last = 0;
    watch(
      () => status.value?.queue?.last_result_at,
      (now, before) => {
        if (!before || now === before || Date.now() - last < minIntervalMs) return;
        last = Date.now();
        callback();
      }
    );
  }

  return { status, scheduler, schedulerError, tracksNadaAi, activity, problems, refresh, onProgress };
}
