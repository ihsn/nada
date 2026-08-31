<template>
  <v-card class="admin-catalog-surface pa-4" rounded="lg" elevation="1">
    <h2 class="text-subtitle-1 font-weight-semibold mb-3">
      {{ t('dd_log_history', 'Log history') }}
    </h2>

    <v-alert v-if="loadError" type="error" variant="tonal" density="compact" class="mb-3">
      {{ loadError }}
    </v-alert>
    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-3" />

    <v-table v-else-if="rows.length" density="comfortable" class="text-body-2">
      <thead>
        <tr>
          <th class="text-left">{{ t('dd_identity', 'Identity') }}</th>
          <th class="text-left">{{ t('dd_date', 'Date') }}</th>
          <th class="text-left">{{ t('dd_status', 'Status') }}</th>
          <th class="text-left">{{ t('description', 'Description') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, idx) in rows" :key="row.id || idx">
          <td class="text-no-wrap">{{ row.identity }}</td>
          <td class="text-no-wrap">{{ row.created_on }}</td>
          <td>{{ row.status }}</td>
          <td class="dd-history-desc" v-html="row.description" />
        </tr>
      </tbody>
    </v-table>
    <p v-else class="text-body-2 text-medium-emphasis mb-0">
      {{ t('no_records_found', 'No history') }}
    </p>
  </v-card>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useAdminDepositApi } from '../composables/useAdminDepositApi';
import { useI18n } from '@/shared/composables/useI18n';

const props = defineProps({
  projectId: { type: [String, Number], default: '' },
  active: { type: Boolean, default: false },
});

defineOptions({ name: 'AdminDepositHistoryTab' });

const { t } = useI18n();
const { fetchProjectHistory } = useAdminDepositApi();

const rows = ref([]);
const loading = ref(false);
const loadError = ref('');
let loadSeq = 0;

async function load() {
  if (!props.projectId) {
    rows.value = [];
    return;
  }
  const seq = ++loadSeq;
  loading.value = true;
  loadError.value = '';
  try {
    const result = await fetchProjectHistory(props.projectId);
    if (seq !== loadSeq) return;
    rows.value = Array.isArray(result?.items) ? result.items : [];
  } catch (e) {
    if (seq !== loadSeq) return;
    rows.value = [];
    loadError.value = e?.response?.data?.message || e?.message || t('dd_request_failed', 'Request failed');
  } finally {
    if (seq === loadSeq) {
      loading.value = false;
    }
  }
}

watch(
  () => [props.projectId, props.active],
  ([id, active]) => {
    if (id && active) {
      load();
    }
  },
  { immediate: true }
);
</script>

<style scoped>
.dd-history-desc {
  word-break: break-word;
}
.dd-history-desc :deep(i) {
  font-style: italic;
}
</style>
