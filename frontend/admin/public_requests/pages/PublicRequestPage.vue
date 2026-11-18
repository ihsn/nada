<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="pr-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">
      {{ t('public_request', 'Public request') }}
    </h1>

    <v-alert v-if="error" type="error" class="mb-4" density="compact">{{ error }}</v-alert>

    <v-card v-if="request" elevation="1">
      <v-table density="comfortable" class="text-body-2">
        <tbody>
          <tr v-for="row in rows" :key="row.label">
            <th class="text-medium-emphasis" style="width: 220px; vertical-align: top">
              {{ row.label }}
            </th>
            <td style="white-space: pre-wrap">{{ row.value }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <div v-else-if="!error" class="py-8 text-medium-emphasis">
      <v-progress-circular indeterminate color="primary" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { usePublicRequestsApi } from '../composables/usePublicRequestsApi';

defineOptions({ name: 'PublicRequestPage' });

const props = defineProps({
  id: { type: [String, Number], default: null },
});

const route = useRoute();
const { siteUrl, config } = useAppConfig();
const { fetchDetail } = usePublicRequestsApi();

const loading = ref(false);
const error = ref('');
const request = ref(null);

const t = (key, fallback) => config.value?.translations?.[key] || fallback || key;
const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const requestId = computed(() => {
  const fromProp = Number(props.id);
  if (fromProp > 0) return fromProp;
  const fromRoute = Number(route.params.id);
  if (fromRoute > 0) return fromRoute;
  return Number(config.value?.requestId) || 0;
});

const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: `${siteBase.value}/admin` },
  {
    title: t('public_requests', 'Public requests'),
    href: `${siteBase.value}/admin/public_requests`,
  },
  { title: t('public_request', 'Public request'), disabled: true },
]);

function formatDate(ts) {
  if (!ts) return '—';
  try {
    return new Date(Number(ts) * 1000).toLocaleString();
  } catch {
    return String(ts);
  }
}

const rows = computed(() => {
  const r = request.value;
  if (!r) return [];
  const study = [r.title, r.nation, r.year_start].filter(Boolean).join(' — ');
  return [
    { label: t('request_id', 'Request ID'), value: r.id },
    { label: t('request_date', 'Request date'), value: formatDate(r.posted) },
    { label: t('study_title', 'Study title'), value: study || '—' },
    { label: t('study_id', 'Study ID'), value: r.idno || '—' },
    {
      label: t('full_name', 'Full name'),
      value: `${r.first_name || ''} ${r.last_name || ''}`.trim() || '—',
    },
    { label: t('username', 'Username'), value: r.username || '—' },
    { label: t('email', 'Email'), value: r.email || '—' },
    { label: t('organization', 'Organization'), value: r.company || '—' },
    { label: t('phone', 'Phone'), value: r.phone || '—' },
    { label: t('country', 'Country'), value: r.country || '—' },
    { label: t('intended_data_use', 'Intended data use'), value: r.abstract || '—' },
  ];
});

async function load() {
  error.value = '';
  request.value = null;
  if (!requestId.value) {
    error.value = 'Invalid request id';
    return;
  }
  loading.value = true;
  try {
    request.value = await fetchDetail(requestId.value);
  } catch (e) {
    error.value = e?.response?.data?.message || e.message || 'Failed to load request';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
watch(requestId, load);
</script>

<style>
.pr-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.pr-breadcrumbs .v-breadcrumbs-item,
.pr-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
</style>
