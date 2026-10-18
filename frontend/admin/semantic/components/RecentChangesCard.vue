<template>
  <v-card v-if="tracksNadaAi" elevation="1" rounded="lg">
    <v-card-title class="semantic-card-title d-flex align-center flex-wrap ga-2">
      Recent changes
      <v-spacer />
      <router-link :to="{ name: 'activity', query: { tab: 'queue' } }" class="text-caption text-decoration-none">
        Change queue →
      </router-link>
    </v-card-title>
    <v-divider />
    <v-card-text class="pt-3">
      <v-alert v-if="loadError" type="error" variant="tonal" density="compact" class="mb-3">{{ loadError }}</v-alert>

      <div class="semantic-recent-grid">
        <section>
          <div class="semantic-recent-heading">
            <v-icon icon="mdi-clock-outline" size="16" />
            Waiting to be indexed
            <v-chip v-if="recent.waiting.length" size="x-small" variant="tonal" color="primary">{{ recent.waiting.length }}</v-chip>
          </div>
          <div v-if="recent.sync_waiting" class="text-caption text-medium-emphasis mb-1">
            Plus {{ formatCount(recent.sync_waiting) }} from a sync
          </div>
          <div v-if="!recent.waiting.length" class="text-body-2 text-medium-emphasis">Nothing waiting.</div>
          <div v-else class="semantic-recent-list">
            <RecentItem
              v-for="item in recent.waiting"
              :key="`w-${item.object_type}-${item.object_id}`"
              :item="item"
              :edit-url="editUrl(item)"
            />
          </div>
        </section>

        <section>
          <div class="semantic-recent-heading">
            <v-icon icon="mdi-history" size="16" />
            Recently indexed
          </div>
          <div v-if="!recent.results.length" class="text-body-2 text-medium-emphasis">Nothing yet.</div>
          <div v-else class="semantic-recent-list">
            <RecentItem
              v-for="item in recent.results"
              :key="`r-${item.object_type}-${item.object_id}`"
              :item="item"
              :edit-url="editUrl(item)"
            />
          </div>
        </section>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { useSyncHealth } from '../composables/useSyncHealth.js';
import { formatCount } from '../typeLabels.js';
import RecentItem from './RecentChangeItem.vue';

defineOptions({ name: 'SemanticRecentChangesCard' });

/**
 * The catalog edits waiting for NADA-AI (e.g. a study published or unpublished) and the latest results, so an edit
 * can be followed until it is searchable. Read again whenever the change queue moves (useSyncHealth).
 */
const { getRecentChanges } = useSemanticApi();
const { status, tracksNadaAi } = useSyncHealth();
const { studyEditBaseUrl } = useAppConfig();
const recent = ref({ waiting: [], sync_waiting: 0, results: [] });
const loadError = ref(null);

async function load() {
  try {
    const data = await getRecentChanges(8);
    recent.value = { waiting: data?.waiting || [], sync_waiting: data?.sync_waiting || 0, results: data?.results || [] };
    loadError.value = null;
  } catch (e) {
    loadError.value = e?.response?.data?.message || e?.message || 'Could not load the recent changes';
  }
}

function editUrl(item) {
  if (item.object_type !== 'survey') return null;
  return `${(studyEditBaseUrl.value || '').replace(/\/?$/, '/')}${item.object_id}`;
}

// the queue moved (a change queued or applied): read the lists again
watch(
  () => [status.value?.queue?.pending, status.value?.queue?.failed, status.value?.queue?.last_result_at],
  (now, before) => {
    if (before && JSON.stringify(now) !== JSON.stringify(before)) load();
  }
);
onMounted(load);

</script>

<style scoped>
.semantic-recent-heading {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  margin-bottom: 2px;
}

/* one line between entries, none above the first or below the last */
.semantic-recent-list > :deep(* + *) {
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.semantic-recent-grid {
  display: grid;
  gap: 24px;
  grid-template-columns: 1fr;
}

@media (min-width: 900px) {
  .semantic-recent-grid {
    grid-template-columns: 1fr 1fr;
  }
}

</style>
