<template>
  <div class="semantic-scheduler-details">
    <section v-for="group in groups" :key="group.title">
      <div class="semantic-scheduler-heading">
        <v-icon :icon="group.icon" size="16" />
        {{ group.title }}
      </div>
      <dl>
        <template v-for="row in group.rows" :key="row.label">
          <dt>{{ row.label }}</dt>
          <dd :class="row.tone ? `text-${row.tone}` : ''" :title="row.title || undefined">{{ row.value }}</dd>
        </template>
      </dl>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticSchedulerDetails' });

/** Everything NADA-AI's GET /admin/scheduler/status reports, grouped: timing, passes, requests to NADA, acks, errors. */
const props = defineProps({
  scheduler: { type: Object, required: true },
});

function time(ts) {
  return ts ? new Date(Number(ts) * 1000).toLocaleString() : '—';
}

function ago(ts) {
  if (!ts) return '';
  const seconds = Math.round(Date.now() / 1000 - Number(ts));
  if (seconds < 0) return `in ${duration(-seconds)}`;
  return `${duration(seconds)} ago`;
}

function duration(seconds) {
  if (seconds < 60) return `${seconds} s`;
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return `${minutes} min`;
  return `${Math.floor(minutes / 60)} h ${minutes % 60} min`;
}

/** A short time ("5 min ago", "in 3 min", or a short date past a day) with the full date for the tooltip. */
function stamp(ts) {
  if (!ts) return { value: '—' };
  const seconds = Math.abs(Date.now() / 1000 - Number(ts));
  const value =
    seconds < 24 * 3600
      ? ago(ts)
      : new Date(Number(ts) * 1000).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
  return { value, title: time(ts) };
}

function passRows(pass) {
  if (!pass?.started_at) return [{ label: 'Started', value: '—' }];
  const finished = pass.finished_at ? Number(pass.finished_at) : null;
  return [
    { label: 'Started', ...stamp(pass.started_at) },
    ...(finished ? [{ label: 'Took', value: duration(Math.max(0, finished - Number(pass.started_at))) }] : []),
    { label: 'Processed', value: formatCount(pass.processed || 0) },
    { label: 'Taken off the queue', value: formatCount(pass.acked ?? pass.processed ?? 0) },
    { label: 'Indexed', value: formatCount(pass.indexed || 0) },
    { label: 'Removed', value: formatCount(pass.deleted || 0) },
    { label: 'Failed', value: formatCount(pass.failed || 0), tone: pass.failed ? 'error' : null },
  ];
}

const groups = computed(() => {
  const s = props.scheduler;
  const requests = s.requests || {};
  const trouble = requests.last_trouble;
  const acks = s.acks || {};
  const owner = s.owner || {};
  const out = [
    {
      title: 'Timing',
      icon: 'mdi-timer-outline',
      rows: [
        { label: 'Heartbeat', ...stamp(s.heartbeat_at), tone: s.state === 'stuck' ? 'error' : null },
        { label: 'Last read the queue', ...stamp(s.last_poll_at) },
        { label: 'Next check', ...stamp(s.next_poll_at) },
        { label: 'Checks when idle', value: s.interval_seconds ? `every ${duration(Number(s.interval_seconds))}` : '—' },
      ],
    },
  ];
  if (s.pass?.started_at && !(s.last_pass && s.last_pass.started_at === s.pass.started_at)) {
    out.push({ title: 'This pass', icon: 'mdi-play-circle-outline', rows: passRows(s.pass) });
  }
  out.push({ title: 'Last finished pass', icon: 'mdi-history', rows: passRows(s.last_pass) });
  out.push({
    title: 'Requests to NADA',
    icon: 'mdi-swap-horizontal',
    rows: [
      { label: 'In the last hour', value: formatCount(requests.last_hour || 0) },
      { label: 'Minimum gap', value: requests.min_interval_ms != null ? `${requests.min_interval_ms} ms` : '—' },
      {
        label: 'Last trouble',
        value: trouble ? [trouble.kind, trouble.message, trouble.at ? `(${ago(trouble.at)})` : ''].filter(Boolean).join(' ') : 'None',
        tone: trouble ? 'warning' : null,
        title: trouble?.message,
      },
    ],
  });
  out.push({
    title: 'Results sent to NADA',
    icon: 'mdi-check-all',
    rows: [
      { label: 'Not yet sent', value: formatCount(acks.unsent || 0), tone: acks.unsent ? 'warning' : null },
      {
        label: 'Failures in a row',
        value: `${acks.consecutive_failures || 0} (pauses at 10)`,
        tone: acks.consecutive_failures ? 'warning' : null,
      },
    ],
  });
  out.push({
    title: 'Errors',
    icon: 'mdi-alert-circle-outline',
    rows: [
      { label: 'Last error', value: s.last_error || 'None', tone: s.last_error ? 'error' : null, title: s.last_error },
      {
        label: 'Last crash',
        value: s.crash ? `${ago(s.crash.at)}: ${s.crash.message || ''}` : 'None',
        tone: s.crash ? 'error' : null,
        title: s.crash ? `${time(s.crash.at)}: ${s.crash.message || ''}` : undefined,
      },
      { label: 'Restarts', value: String(s.restarts || 0) },
    ],
  });
  out.push({
    title: 'Process',
    icon: 'mdi-server-outline',
    rows: [
      { label: 'Scheduler', value: s.enabled === false ? 'Turned off' : 'Turned on' },
      { label: 'Runs in', value: owner.host ? `${owner.host}, pid ${owner.pid}` : s.running_here === false ? 'Another process' : '—' },
      { label: 'Started', ...stamp(owner.started_at) },
    ],
  });
  return out;
});
</script>

<style scoped>
.semantic-scheduler-details {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px 32px;
}

.semantic-scheduler-heading {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 6px;
}

dl {
  display: grid;
  grid-template-columns: max-content 1fr;
  gap: 4px 16px;
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.4;
}

dt {
  color: rgba(var(--v-theme-on-surface), 0.6);
}

dd {
  margin: 0;
  min-width: 0;
  overflow-wrap: anywhere;
}
</style>
