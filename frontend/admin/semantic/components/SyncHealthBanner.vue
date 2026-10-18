<template>
  <div v-if="problems.length" class="mb-4">
    <v-alert
      v-for="problem in problems"
      :key="problem.key"
      :type="problem.type"
      variant="tonal"
      density="compact"
      class="mb-2"
    >
      {{ problem.text }}
    </v-alert>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useSyncHealth } from '../composables/useSyncHealth.js';

defineOptions({ name: 'SemanticSyncHealthBanner' });

/**
 * Shown on every page of the dashboard, and only when keeping the index in sync needs attention: NADA-AI's scheduler
 * is off, stopped, stuck or paused, nobody reads the change queue, changes failed, or a sync is due.
 */
const route = useRoute();
const { problems, refresh } = useSyncHealth();

onMounted(() => refresh());
watch(() => route.fullPath, () => refresh());
</script>
