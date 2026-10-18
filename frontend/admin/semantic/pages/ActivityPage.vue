<template>
  <div>
    <IndexingActivityCard />
    <v-tabs v-model="tab" color="primary" class="mb-4" density="comfortable">
      <v-tab value="jobs">Jobs</v-tab>
      <v-tab value="queue">
        Change queue
        <v-chip v-if="queueFailed" size="x-small" color="error" variant="tonal" class="ml-2">{{ queueFailed }}</v-chip>
      </v-tab>
    </v-tabs>
    <JobsPage v-if="tab === 'jobs'" />
    <SyncStatusPage v-else />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import JobsPage from './JobsPage.vue';
import SyncStatusPage from './SyncStatusPage.vue';
import IndexingActivityCard from '../components/IndexingActivityCard.vue';

defineOptions({ name: 'SemanticActivityPage' });

/** Jobs (what nada-ai is running or ran) and the change queue (catalog edits waiting for it), one tab each. */
const route = useRoute();
const router = useRouter();
const { getSyncStatus } = useSemanticApi();
const queueFailed = ref(0);

const tab = computed({
  get: () => (route.query.tab === 'queue' ? 'queue' : 'jobs'),
  set: (value) => router.replace({ query: { ...route.query, tab: value } }),
});

onMounted(async () => {
  try {
    queueFailed.value = Number((await getSyncStatus())?.queue?.failed) || 0;
  } catch {
    // the badge is best-effort; the tab shows the real state
  }
});
</script>
