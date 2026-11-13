<template>
  <div class="cit-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="cit-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 flex-grow-1">
        {{ t('export_citations', 'Export citations') }}
      </h1>
      <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" :to="{ name: 'citations-list' }">
        {{ t('cit_all_citations', 'All citations') }}
      </v-btn>
    </div>

    <v-card class="pa-4" elevation="1">
      <h2 class="cit-section-title">{{ t('cit_export_what', 'What to export') }}</h2>
      <v-radio-group v-model="scope" hide-details>
        <v-radio v-if="ids.length" value="selected" :label="t('cit_export_selected_n', 'The %d selected citation(s)', ids.length)" />
        <v-radio v-if="filtersOn" value="filtered" :label="t('cit_export_filtered', 'All citations matching the filters of the list')" />
        <v-radio value="all" :label="t('cit_export_all', 'All citations')" />
      </v-radio-group>
      <div v-if="scope === 'filtered'" class="cit-muted mt-2">{{ filterSummary }}</div>
    </v-card>

    <v-card class="pa-4" elevation="1">
      <h2 class="cit-section-title">{{ t('cit_export_format', 'Format') }}</h2>
      <v-radio-group v-model="format" hide-details>
        <v-radio v-for="f in formats" :key="f.value" :value="f.value">
          <template #label>
            <div>
              <div>{{ f.title }}</div>
              <div class="cit-muted">{{ f.hint }}</div>
            </div>
          </template>
        </v-radio>
      </v-radio-group>
      <v-alert v-if="format === 'json'" type="info" variant="tonal" density="compact" class="mt-3">
        {{ t('cit_export_json_note', 'The JSON file includes the internal fields (notes, flag, owner) and can be imported again.') }}
      </v-alert>
    </v-card>

    <div>
      <v-btn color="primary" variant="flat" prepend-icon="mdi-download" :href="downloadUrl" :disabled="!canExport">
        {{ t('cit_download', 'Download') }}
      </v-btn>
    </div>
  </div>
</template>

<script setup>
/**
 * Scope comes from the list: ?ids=1,2,3 for the selected citations, or the list's filter parameters.
 * The download is a plain GET, so the browser sends the session cookie and saves the file.
 */
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCitationsApi } from '../composables/useCitationsApi';
import { filtersFromQuery, filtersToParams } from '../utils';

defineOptions({ name: 'ExportPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const api = useCitationsApi();
const route = useRoute();

const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('title_citations', 'Citations'), to: { name: 'citations-list' } },
  { title: t('export_citations', 'Export citations'), disabled: true },
]);

const ids = computed(() =>
  String(route.query.ids || '')
    .split(',')
    .map((v) => Number(v))
    .filter((v) => Number.isInteger(v) && v > 0)
);
const filters = computed(() => filtersToParams(filtersFromQuery(route.query)));
const filtersOn = computed(() => Object.keys(filters.value).length > 0);
const scope = ref(ids.value.length ? 'selected' : filtersOn.value ? 'filtered' : 'all');
const format = ref('csv');
const canExport = ref(true);

const formats = computed(() => [
  { value: 'csv', title: 'CSV', hint: t('cit_export_csv_hint', 'For spreadsheets. Opens in Excel with accents intact.') },
  { value: 'bibtex', title: 'BibTeX', hint: t('cit_export_bibtex_hint', 'For LaTeX and most reference managers.') },
  { value: 'ris', title: 'RIS', hint: t('cit_export_ris_hint', 'For EndNote, Zotero and Mendeley.') },
  { value: 'json', title: 'JSON', hint: t('cit_export_json_hint', 'Complete data, for backups and moving citations to another NADA site.') },
]);

const filterSummary = computed(() =>
  Object.entries(filters.value)
    .map(([k, v]) => `${k}: ${v}`)
    .join(' · ')
);

const downloadUrl = computed(() =>
  api.exportUrl({
    format: format.value,
    ids: scope.value === 'selected' ? ids.value : [],
    filters: scope.value === 'filtered' ? filters.value : {},
  })
);

onMounted(async () => {
  try {
    const options = await api.options();
    canExport.value = !!options?.can;
  } catch {
    canExport.value = false;
  }
});
</script>
