<template>
  <v-card v-if="tracksNadaAi" elevation="1" rounded="lg" class="mb-4">
    <v-card-text class="py-3">
      <div class="d-flex align-center flex-wrap ga-3">
        <span class="font-weight-medium">Indexing activity</span>
        <v-chip size="small" variant="tonal" :color="stateChip.color" :prepend-icon="stateChip.icon">
          {{ stateChip.text }}
        </v-chip>
        <span v-if="activity.left > 0" class="text-body-2">
          <strong>{{ formatCount(activity.left) }}</strong> waiting in the queue
        </span>
        <span v-if="activity.failed > 0" class="text-body-2 text-error">
          {{ formatCount(activity.failed) }} failed
        </span>
        <v-spacer />
        <v-btn
          v-if="canEdit && canPauseOrResume"
          size="small"
          variant="tonal"
          :color="pausedByHand ? 'primary' : undefined"
          :prepend-icon="pausedByHand ? 'mdi-play' : 'mdi-pause'"
          :loading="switching"
          @click="togglePause"
        >
          {{ pausedByHand ? 'Resume' : 'Pause' }}
        </v-btn>
        <router-link :to="{ name: 'activity', query: { tab: 'queue' } }" class="text-body-2 text-decoration-none">
          Change queue →
        </router-link>
      </div>

      <template v-if="activity.state === 'processing'">
        <div v-if="activity.current" class="text-body-2 text-medium-emphasis mt-2">
          Now: <code>{{ activity.current.object_key }}</code> ({{ changeClassLabel(activity.current.change_class) }})
        </div>
        <template v-if="activity.total">
          <v-progress-linear :model-value="activity.percent" color="primary" height="8" rounded class="mt-3" />
          <div class="d-flex flex-wrap ga-4 text-caption text-medium-emphasis mt-1">
            <span>{{ formatCount(activity.done) }} of {{ formatCount(activity.total) }} done ({{ activity.percent }}%)</span>
            <span v-if="activity.rate">{{ rateText }}</span>
            <span v-if="activity.etaMinutes != null">about {{ durationText(activity.etaMinutes) }} left</span>
            <span v-if="activity.passFailed" class="text-error">{{ activity.passFailed }} failed in this pass</span>
          </div>
        </template>
      </template>

      <div v-else-if="activity.state === 'idle'" class="text-body-2 text-medium-emphasis mt-2">
        <template v-if="activity.left === 0">Up to date.</template>
        <template v-if="activity.nextCheckAt"> Next check at {{ timeText(activity.nextCheckAt) }}.</template>
      </div>

      <div v-else-if="activity.pause" class="text-body-2 text-medium-emphasis mt-2">
        {{ activity.pause.message }}<template v-if="activity.pause.resume_at"> — next try at {{ timeText(activity.pause.resume_at) }}</template>
      </div>

      <div v-if="activity.lastResultAt" class="text-caption text-medium-emphasis mt-2">
        Last result recorded {{ timeText(activity.lastResultAt) }}
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useSyncHealth } from '../composables/useSyncHealth.js';
import { changeClassLabel, formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticIndexingActivityCard' });

/**
 * What NADA-AI is doing with the change queue right now: shown only while NADA tracks changes for NADA-AI, and read
 * again every few seconds while there is work (useSyncHealth).
 */
const { activity, tracksNadaAi, refresh } = useSyncHealth();
const { canEdit } = useAppConfig();
const { pauseScheduler, resumeScheduler } = useSemanticApi();
const switching = ref(false);

/** Paused by hand (not a pause NADA-AI chose itself, which it lifts on its own). */
const pausedByHand = computed(() => activity.value.pause?.reason === 'manual');
/** Pause/Resume is offered whenever NADA-AI answers and its scheduler is set up. */
const canPauseOrResume = computed(() => !['unreachable', 'unknown', 'not_configured'].includes(activity.value.state));

async function togglePause() {
  if (!pausedByHand.value && !window.confirm('Pause indexing? NADA-AI finishes the item in hand, then waits until you resume it. Catalog changes keep queuing meanwhile.')) {
    return;
  }
  switching.value = true;
  try {
    if (pausedByHand.value) await resumeScheduler();
    else await pauseScheduler();
  } catch (e) {
    window.alert(`Could not ${pausedByHand.value ? 'resume' : 'pause'} indexing: ${e?.response?.data?.detail || e?.response?.data?.message || e?.message}`);
  } finally {
    switching.value = false;
    refresh();
  }
}

const STATES = {
  processing: { text: 'Indexing', color: 'primary', icon: 'mdi-progress-clock' },
  idle: { text: 'Idle', color: 'success', icon: 'mdi-check-circle-outline' },
  paused: { text: 'Paused', color: 'warning', icon: 'mdi-pause-circle-outline' },
  not_tracking: { text: 'Not tracked', color: 'warning', icon: 'mdi-link-off' },
  disabled: { text: 'Scheduler off', color: 'warning', icon: 'mdi-power-off' },
  not_configured: { text: 'Not configured', color: 'error', icon: 'mdi-alert-circle-outline' },
  crashed: { text: 'Stopped', color: 'error', icon: 'mdi-alert-circle-outline' },
  stuck: { text: 'Stuck', color: 'error', icon: 'mdi-alert-circle-outline' },
  running_elsewhere: { text: 'Runs in another process', color: 'info', icon: 'mdi-server-network' },
  unreachable: { text: 'NADA-AI not answering', color: 'error', icon: 'mdi-lan-disconnect' },
};
const stateChip = computed(
  () => STATES[activity.value.state] || { text: activity.value.state, color: 'grey', icon: 'mdi-help-circle-outline' }
);

const rateText = computed(() => {
  const perMinute = activity.value.rate || 0;
  return perMinute >= 1 ? `${perMinute.toFixed(1)} per minute` : `${(perMinute * 60).toFixed(1)} per hour`;
});

function durationText(minutes) {
  if (minutes < 60) return `${minutes} min`;
  const hours = Math.floor(minutes / 60);
  return `${hours} h ${minutes % 60} min`;
}

function timeText(ts) {
  return ts ? new Date(Number(ts) * 1000).toLocaleString() : '';
}
</script>
