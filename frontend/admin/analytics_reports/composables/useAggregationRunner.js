import { onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAnalyticsApi } from './useAnalyticsApi';
import { useAnalyticsMessages } from './useAnalyticsMessages';

const { t } = useI18n();

const serverMessageKeys = {
  'Starting aggregation...': 'analytics_status_starting',
  'Daily aggregation complete. Rolling up monthly next.': 'analytics_status_daily_complete',
  'Raw events cleanup completed': 'analytics_status_cleanup_complete',
  'Counters synced. Aggregation completed.': 'analytics_status_counters_synced',
  'Aggregation completed successfully': 'analytics_aggregation_completed_successfully',
  'Stopped by user': 'analytics_status_stopped_by_user',
  'Another aggregation is already running': 'analytics_status_already_running',
  'Processing...': 'analytics_status_processing',
};

export function localizeAggregationMessage(message, fallback) {
  if (!message) return fallback;
  if (Object.prototype.hasOwnProperty.call(serverMessageKeys, message)) {
    return t(serverMessageKeys[message], message);
  }

  let match = message.match(/^Processed (.+?)\. (\d+) date\(s\) remaining\.$/);
  if (match) return t('analytics_status_processed_dates', 'Processed %s. %s date(s) remaining.', match[1], match[2]);

  match = message.match(/^Monthly aggregates updated for (.+?)\.(?: (\d+) month\(s\) remaining to roll up\.)?$/);
  if (match) {
    const updated = t('analytics_status_monthly_updated', 'Monthly aggregates updated for %s.', match[1]);
    return match[2] ? `${updated} ${t('analytics_status_monthly_remaining', '%s month(s) remaining to roll up.', match[2])}` : updated;
  }

  match = message.match(/^Month-end processing completed for (.+)$/);
  if (match) return t('analytics_status_month_end', 'Month-end processing completed for %s', match[1]);

  match = message.match(/^Legacy migration complete\.(?: Backed up (\d+) survey\(s\))?(?:, seeded (\d+) legacy total\(s\) into analytics\.| \((\d+) already migrated\)\.)?$/);
  if (match) {
    let text = t('analytics_status_legacy_complete', 'Legacy migration complete.');
    if (match[1]) text += ` ${t('analytics_status_legacy_backup', 'Backed up %s survey(s)', match[1])}`;
    if (match[2]) text += `, ${t('analytics_status_legacy_seeded', 'seeded %s legacy total(s) into analytics.', match[2])}`;
    if (match[3]) text += ` ${t('analytics_status_legacy_skipped', '(%s already migrated).', match[3])}`;
    return text;
  }

  if (message.startsWith('Error: ')) {
    return t('analytics_status_error_detail', 'Error: %s', message.slice(7));
  }
  return message;
}

function statusDetails(step, item) {
  const unavailable = t('analytics_not_available', 'N/A');
  const stepText = step ? t(`analytics_step_${step}`, step) : unavailable;
  const itemText = item ? t(`analytics_item_${item}`, item) : unavailable;
  return t('analytics_step_item', 'Step: %s, Item: %s', stepText, itemText);
}

function aggregationHasMore(res) {
  const v = res?.has_more;
  return v === true || v === 1 || v === '1';
}

function applyRunResponseToStatus(res) {
  return {
    type: 'info',
    title: t('analytics_processing', 'Processing'),
    message: localizeAggregationMessage(res.message, t('analytics_processing_ellipsis', 'Processing…')),
    progress: res.progress ?? res.progress_percent ?? 0,
    details: statusDetails(res.current_step, res.current_item),
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
          title: t('analytics_running', 'Running'),
          message: localizeAggregationMessage(s.message, t('analytics_processing_ellipsis', 'Processing…')),
          progress: s.progress_percent ?? 0,
          details: statusDetails(s.current_step, s.current_item),
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
          title: t('analytics_completed', 'Completed'),
          message: localizeAggregationMessage(s.message, t('analytics_completed', 'Completed')),
          progress: 100,
        };
      } else if (s.status === 'failed') {
        status.value = {
          type: 'error',
          title: t('analytics_failed', 'Failed'),
          message: localizeAggregationMessage(s.error_message || s.message, t('analytics_failed', 'Failed')),
        };
      }
    } catch (e) {
      notifyError(localizeAggregationMessage(e.message, t('analytics_failed_status', 'Failed to load aggregation status')));
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
        title: t('analytics_stopped', 'Stopped'),
        message: t('analytics_was_stopped', 'Aggregation was stopped'),
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
          title: t('analytics_error', 'Error'),
          message: localizeAggregationMessage(res.message, t('analytics_aggregation_failed', 'Aggregation failed')),
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
          title: t('analytics_completed', 'Completed'),
          message: localizeAggregationMessage(res.message, t('analytics_aggregation_completed_successfully', 'Aggregation completed successfully')),
          progress: 100,
        };
        running.value = false;
        clearPollTimer();
        notifySuccess(t('analytics_aggregation_completed', 'Aggregation completed'));
        await refreshStatus();
        pollInFlight = false;
        return;
      }

      running.value = true;
      schedulePoll(1000);
    } catch (e) {
      status.value = {
        type: 'error',
        title: t('analytics_error', 'Error'),
        message: localizeAggregationMessage(e.message, t('analytics_aggregation_failed', 'Aggregation failed')),
      };
      running.value = false;
      clearPollTimer();
    } finally {
      pollInFlight = false;
    }
  }

  function runFull() {
    if (!window.confirm(t('analytics_confirm_run', 'This will run the full aggregation process. It may take several minutes. Continue?'))) {
      return;
    }
    clearPollTimer();
    running.value = true;
    stopRequested.value = false;
    status.value = {
      type: 'info',
      title: t('analytics_running_aggregation', 'Running aggregation'),
      message: t('analytics_starting', 'Starting…'),
      progress: 0,
    };
    pollStep();
  }

  async function stop() {
    stopRequested.value = true;
    try {
      await api.stopAggregation();
    } catch (e) {
      notifyError(localizeAggregationMessage(e.message, t('analytics_failed_stop', 'Failed to stop aggregation')));
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
