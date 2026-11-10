<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>{{ t('analytics_quick_actions', 'Quick actions') }}</v-card-title>
    <v-card-text>
      <v-row>
        <v-col cols="12" md="6">
          <v-btn
            color="primary"
            size="large"
            block
            class="mb-2"
            prepend-icon="mdi-play-circle"
            :loading="running"
            :disabled="running || !canRun"
            @click="emit('run')"
          >
            {{ t('analytics_run_full', 'Run full aggregation') }}
          </v-btn>
          <div class="text-caption text-medium-emphasis text-center">
            {{ t('analytics_run_description', 'Auto-detects missing dates and processes all steps') }}
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <v-btn
            color="secondary"
            size="large"
            block
            class="mb-2"
            prepend-icon="mdi-stop-circle"
            :disabled="!running || !canRun"
            @click="emit('stop')"
          >
            {{ t('analytics_stop_aggregation', 'Stop aggregation') }}
          </v-btn>
          <div class="text-caption text-medium-emphasis text-center">{{ t('analytics_cancel_run', 'Cancel the current run') }}</div>
        </v-col>
      </v-row>
      <div v-if="!canRun" class="text-caption text-medium-emphasis mt-2">
        {{ t('analytics_run_requires_edit', 'Running aggregation requires the Reports edit permission.') }}
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineProps({
  running: { type: Boolean, default: false },
  canRun: { type: Boolean, default: false },
});

const emit = defineEmits(['run', 'stop']);

defineOptions({ name: 'AggregationControlsCard' });

const { t } = useI18n();
</script>
