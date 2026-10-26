import { onMounted, onUnmounted, ref } from 'vue';
import { useAnalyticsApi } from './useAnalyticsApi';
import { useAnalyticsMessages } from './useAnalyticsMessages';

function aggregationHasMore(res) {
  const v = res?.has_more;
  return v === true || v === 1 || v === '1';
}

function applyRunResponseToStatus(res) {
  return {
    type: 'info',
    title: 'Processing',
    message: res.message || 'Processing…',
    progress: res.progress ?? res.progress_percent ?? 0,
    details: `Step: ${res.current_step || 'N/A'}, Item: ${res.current_item || 'N/A'}`,
  };
}

export function useAggregationRunner() {
  const api = useAnalyticsApi();
  const { notifyError, notifySuccess } = useAnalyticsMessages();

  const running = ref(false);
  const stopRequested = ref(false);
  const lastCompletedAt = ref(null);
  const status = ref(null);

  let pollTimer = null;
  let pollInFlight = false;

  function clearPollTimer() {
    if (pollTimer) {
      clearTimeout(pollTimer);
      pollTimer = null;
    }
  }

  function schedulePoll(delayMs = 1000) {
    clearPollTimer();
    pollTimer = setTimeout(() => {
      pollTimer = null;
      pollStep();
    }, delayMs);
  }

  async function refreshStatus(options = {}) {
    const { resumeIfRunning = false } = options;
    try {
      const res = await api.fetchAggregationStatus();
      const s = res.data || {};
      lastCompletedAt.value = s.last_completed_at || s.completed_at || lastCompletedAt.value;

      if (s.status === 'running') {
        running.value = true;
        status.value = {
          type: 'info',
          title: 'Running',
          message: s.message || 'Processing…',
          progress: s.progress_percent ?? 0,
          details: `Step: ${s.current_step || 'N/A'}, Item: ${s.current_item || 'N/A'}`,
        };
        if (resumeIfRunning) {
          schedulePoll(500);
        }
        return;
      }

      running.value = false;

      if (s.status === 'completed') {
        status.value = {
          type: 'success',
          title: 'Completed',
          message: s.message || 'Completed',
          progress: 100,
        };
      } else if (s.status === 'failed') {
        status.value = {
          type: 'error',
          title: 'Failed',
          message: s.error_message || s.message || 'Failed',
        };
      }
    } catch (e) {
      notifyError(e.message || 'Failed to load aggregation status');
    }
  }

  async function pollStep() {
    if (pollInFlight) {
      return;
    }
    pollInFlight = true;
    if (stopRequested.value) {
      try {
        await api.stopAggregation();
      } catch {
        /* ignore */
      }
      status.value = {
        type: 'warning',
        title: 'Stopped',
        message: 'Aggregation was stopped',
      };
      running.value = false;
      stopRequested.value = false;
      clearPollTimer();
      pollInFlight = false;
      return;
    }

    try {
      const res = await api.runAggregationStep();
      if (res.status === 'error') {
        status.value = {
          type: 'error',
          title: 'Error',
          message: res.message || 'Aggregation failed',
        };
        running.value = false;
        clearPollTimer();
        pollInFlight = false;
        return;
      }

      status.value = applyRunResponseToStatus(res);

      if (!aggregationHasMore(res)) {
        status.value = {
          type: 'success',
          title: 'Completed',
          message: res.message || 'Aggregation completed successfully',
          progress: 100,
        };
        running.value = false;
        clearPollTimer();
        notifySuccess('Aggregation completed');
        await refreshStatus();
        pollInFlight = false;
        return;
      }

      running.value = true;
      schedulePoll(1000);
    } catch (e) {
      status.value = {
        type: 'error',
        title: 'Error',
        message: e.message || 'Aggregation failed',
      };
      running.value = false;
      clearPollTimer();
    } finally {
      pollInFlight = false;
    }
  }

  function runFull() {
    if (!window.confirm('This will run the full aggregation process. It may take several minutes. Continue?')) {
      return;
    }
    clearPollTimer();
    running.value = true;
    stopRequested.value = false;
    status.value = {
      type: 'info',
      title: 'Running aggregation',
      message: 'Starting…',
      progress: 0,
    };
    pollStep();
  }

  async function stop() {
    stopRequested.value = true;
    try {
      await api.stopAggregation();
    } catch (e) {
      notifyError(e.message || 'Failed to stop aggregation');
    }
  }

  onMounted(() => refreshStatus({ resumeIfRunning: true }));
  onUnmounted(clearPollTimer);

  return {
    running,
    status,
    lastCompletedAt,
    runFull,
    stop,
    refreshStatus,
  };
}
