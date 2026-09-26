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
import { useEngineStatus } from '../composables/useEngineStatus.js';

defineOptions({ name: 'SemanticEngineStatusBanner' });

/**
 * Shown on every page of the dashboard, and only when something is wrong: nada-ai runs another engine than the
 * setting says, or it is not answering (the outage breaker is open). The full picture is the Overview card.
 */
const route = useRoute();
const { problems, refresh } = useEngineStatus();

onMounted(() => refresh());
watch(() => route.fullPath, () => refresh());
</script>
