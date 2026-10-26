<template>
  <v-card class="admin-analytics-card" rounded="lg" elevation="1">
    <v-card-title>Quick actions</v-card-title>
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
            :disabled="running"
            @click="emit('run')"
          >
            Run full aggregation
          </v-btn>
          <div class="text-caption text-medium-emphasis text-center">
            Auto-detects missing dates and processes all steps
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <v-btn
            color="secondary"
            size="large"
            block
            class="mb-2"
            prepend-icon="mdi-stop-circle"
            :disabled="!running"
            @click="emit('stop')"
          >
            Stop aggregation
          </v-btn>
          <div class="text-caption text-medium-emphasis text-center">Cancel the current run</div>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
defineProps({
  running: { type: Boolean, default: false },
});

const emit = defineEmits(['run', 'stop']);

defineOptions({ name: 'AggregationControlsCard' });
</script>
