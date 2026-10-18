<template>
  <!-- One line, like the Variables one: citations are secondary to studies. -->
  <v-card elevation="1" rounded="lg">
    <v-card-text class="d-flex align-center flex-wrap ga-4 py-3">
      <span class="font-weight-medium">Citations</span>
      <v-progress-circular v-if="loading && !stats" indeterminate size="18" width="2" />
      <span v-else-if="error" class="text-error text-body-2">{{ errorMessage(error) }}</span>
      <template v-else-if="stats">
        <span class="text-body-2">
          Database <strong>{{ formatCount(stats.database.published) }}</strong> published
          <template v-if="indexed">
            · Indexed <strong>{{ formatCount(indexed.published) }}</strong>
          </template>
        </span>
        <v-chip v-if="verdict" size="small" variant="tonal" :color="verdict.color" :prepend-icon="verdict.icon">
          {{ verdict.text }}
        </v-chip>
        <v-spacer />
        <router-link v-if="manage" :to="{ name: 'indexing', hash: '#citations' }" class="text-body-2 text-decoration-none">
          Manage →
        </router-link>
        <v-btn
          v-else-if="!notApplicable"
          size="small"
          variant="tonal"
          color="primary"
          prepend-icon="mdi-database-sync-outline"
          :loading="starting"
          :disabled="!canEdit"
          @click="startSync"
        >
          Update
        </v-btn>
      </template>
    </v-card-text>
    <div v-if="started || startError" class="px-4 pb-4">
      <v-alert v-if="started" type="success" variant="tonal" density="compact">
        Citation indexing started as a background job.
        <router-link :to="{ name: 'activity', query: { tab: 'jobs' } }">Follow it in Activity →</router-link>
      </v-alert>
      <v-alert v-if="startError" type="error" variant="tonal" density="compact">{{ startError }}</v-alert>
    </div>
    <p v-if="detail" class="text-caption text-medium-emphasis px-4 pb-3 mb-0">{{ detail }}</p>
  </v-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSemanticApi } from '../composables/useSemanticApi.js';
import { formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticCitationsCoverageCard' });

defineProps({
  /** Overview: report only, with a link to the Indexing page instead of the action. */
  manage: { type: Boolean, default: false },
});

const { canEdit } = useAppConfig();
const { loading, error, errorMessage, getCitationsStats, syncCitations } = useSemanticApi();
const stats = ref(null);
const starting = ref(false);
const started = ref(false);
const startError = ref(null);

/** nada-ai answers 501 when it has no citation index. */
const notApplicable = computed(() => stats.value?.index && !stats.value.index.ok && stats.value.index.status === 501);

/** The index side, or null when it is missing, unavailable or not applicable. */
const indexed = computed(() => {
  const idx = stats.value?.index;
  if (!idx?.ok || !idx.data?.exists) return null;
  return idx.data;
});

/** Only published citations are searchable, so they are what the comparison is about. */
const difference = computed(() => (indexed.value ? stats.value.database.published - indexed.value.published : 0));

const verdict = computed(() => {
  if (!stats.value) return null;
  if (notApplicable.value) return { text: 'Not applicable', color: 'grey', icon: 'mdi-minus-circle-outline' };
  const idx = stats.value.index;
  if (!idx.ok) return { text: 'Index unavailable', color: 'error', icon: 'mdi-alert-circle-outline' };
  if (!idx.data.exists) return { text: 'Not indexed yet', color: 'warning', icon: 'mdi-alert-outline' };
  if (difference.value === 0 && indexed.value.citations === stats.value.database.citations) {
    return { text: 'In sync', color: 'success', icon: 'mdi-check-circle-outline' };
  }
  if (difference.value > 0) {
    return { text: `${formatCount(difference.value)} not indexed`, color: 'warning', icon: 'mdi-alert-outline' };
  }
  if (difference.value < 0) {
    return { text: `${formatCount(-difference.value)} extra in index`, color: 'warning', icon: 'mdi-alert-outline' };
  }
  return { text: 'Counts differ', color: 'warning', icon: 'mdi-alert-outline' };
});

const detail = computed(() => {
  if (!stats.value) return null;
  const idx = stats.value.index;
  if (notApplicable.value) return 'The citation search index is only available with the OpenSearch engine.';
  if (!idx.ok) return idx.error || null;
  if (!idx.data.exists) return 'The citation index has not been created yet. Use Update on the Indexing page.';
  if (difference.value > 0) return 'Some published citations are not indexed. Update indexes every citation again.';
  if (difference.value < 0) {
    return 'The index holds more published citations than the database — usually citations removed or unpublished since they were indexed.';
  }
  return null;
});

async function startSync() {
  starting.value = true;
  started.value = false;
  startError.value = null;
  try {
    await syncCitations();
    started.value = true;
  } catch (e) {
    startError.value = errorMessage(e);
  } finally {
    starting.value = false;
  }
}

async function load() {
  try {
    stats.value = await getCitationsStats();
  } catch {
    stats.value = null;
  }
}

defineExpose({ load });

onMounted(load);
</script>
