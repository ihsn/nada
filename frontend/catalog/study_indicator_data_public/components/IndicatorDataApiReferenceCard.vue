<template>
  <v-expansion-panels v-if="dataBaseUrl" variant="accordion" class="api-usage-expansion" :model-value="[]">
    <v-expansion-panel rounded="lg" elevation="0">
      <v-expansion-panel-title class="text-h6 py-2 px-3">
        {{ ui.apiUsageHeading }}
      </v-expansion-panel-title>
      <v-expansion-panel-text class="pt-1 px-3 pb-2">
        <div class="text-body-2 font-weight-medium mb-1">{{ ui.metadataLabel }}</div>
        <div class="api-line mb-4">
          <code class="api-ref-url">{{ schemaUrl }}</code>
          <v-btn icon size="x-small" variant="text" :aria-label="ui.copyUrl" @click="copyUrl(schemaUrl)">
            <v-icon size="18">mdi-content-copy</v-icon>
          </v-btn>
          <v-btn
            icon
            size="x-small"
            variant="text"
            :aria-label="ui.openUrl"
            :href="schemaUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            <v-icon size="18">mdi-open-in-new</v-icon>
          </v-btn>
        </div>

        <div class="text-body-2 font-weight-medium mb-1">{{ ui.notesLabel || 'Data notes' }}</div>
        <div class="text-caption text-medium-emphasis mb-1">
          {{ ui.notesDesc || 'Footnote columns hold note IDs (several separated by ;). This returns the footnote columns, the text of each note, and notes for the whole table.' }}
        </div>
        <div class="api-line mb-4">
          <code class="api-ref-url">{{ notesUrl }}</code>
          <v-btn icon size="x-small" variant="text" :aria-label="ui.copyUrl" @click="copyUrl(notesUrl)">
            <v-icon size="18">mdi-content-copy</v-icon>
          </v-btn>
          <v-btn
            icon
            size="x-small"
            variant="text"
            :aria-label="ui.openUrl"
            :href="notesUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            <v-icon size="18">mdi-open-in-new</v-icon>
          </v-btn>
        </div>

        <div class="text-body-2 font-weight-medium mb-1">{{ ui.dataLabel }}</div>
        <div class="api-line mb-4">
          <code class="api-ref-url">{{ dataUrl }}</code>
          <v-btn icon size="x-small" variant="text" :aria-label="ui.copyUrl" @click="copyUrl(dataUrl)">
            <v-icon size="18">mdi-content-copy</v-icon>
          </v-btn>
          <v-btn
            icon
            size="x-small"
            variant="text"
            :aria-label="ui.openUrl"
            :href="dataUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            <v-icon size="18">mdi-open-in-new</v-icon>
          </v-btn>
        </div>

        <div v-if="showBulkDownloadsApiLink && bulkDownloadsCatalogUrl" class="mb-4">
          <div class="text-body-2 font-weight-medium mb-1">{{ ui.bulkDownloadsHeading }}</div>
          <div class="api-line">
            <code class="api-ref-url">{{ bulkDownloadsCatalogUrl }}</code>
            <v-btn icon size="x-small" variant="text" :aria-label="ui.copyUrl" @click="copyUrl(bulkDownloadsCatalogUrl)">
              <v-icon size="18">mdi-content-copy</v-icon>
            </v-btn>
            <v-btn
              icon
              size="x-small"
              variant="text"
              :aria-label="ui.openUrl"
              :href="bulkDownloadsCatalogUrl"
              target="_blank"
              rel="noopener noreferrer"
            >
              <v-icon size="18">mdi-open-in-new</v-icon>
            </v-btn>
          </div>
        </div>

        <div v-if="openapiUrl" class="mb-4">
          <div class="text-body-2 font-weight-medium mb-1">{{ ui.openapiLabel || 'OpenAPI specification' }}</div>
          <div class="api-line">
            <code class="api-ref-url">{{ openapiUrl }}</code>
            <v-btn icon size="x-small" variant="text" :aria-label="ui.copyUrl" @click="copyUrl(openapiUrl)">
              <v-icon size="18">mdi-content-copy</v-icon>
            </v-btn>
            <v-btn
              icon
              size="x-small"
              variant="text"
              :aria-label="ui.openUrl"
              :href="openapiUrl"
              target="_blank"
              rel="noopener noreferrer"
            >
              <v-icon size="18">mdi-open-in-new</v-icon>
            </v-btn>
          </div>
        </div>

        <div v-if="playgroundUrl" class="mb-4">
          <v-btn
            size="small"
            variant="tonal"
            color="primary"
            prepend-icon="mdi-play-circle-outline"
            :href="playgroundUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            {{ ui.tryApiLabel || 'Try the API' }}
          </v-btn>
          <div class="text-caption text-medium-emphasis mt-1">
            {{ ui.tryApiDesc || 'Opens an interactive page where you can run live queries for this indicator.' }}
          </div>
        </div>

        <div class="text-caption text-medium-emphasis mt-4 mb-2">{{ ui.queryParamsHeading }}</div>
        <v-table density="compact" class="api-ref-table bg-transparent">
          <tbody>
            <tr v-for="row in paramRows" :key="row.name">
              <td class="py-1 pe-4 align-top text-no-wrap"><code class="text-primary">{{ row.name }}</code></td>
              <td class="py-1 text-body-2 text-medium-emphasis">
                {{ row.desc }}
                <div v-if="row.example" class="mt-1">
                  {{ ui.exampleLabel || 'Example:' }} <code class="api-ref-example">{{ row.example }}</code>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>
        <div v-if="hasFilterRows && structureUrl" class="text-body-2 text-medium-emphasis mt-2">
          {{ structureNoteBefore }}<a :href="structureUrl" class="text-primary">{{ ui.structureLinkText || 'Data structure' }}</a>{{ structureNoteAfter }}
        </div>

        <div class="text-subtitle-2 mt-5 mb-2">{{ ui.examplesHeading }}</div>
        <div class="mb-3">
          <div class="text-body-2 font-weight-medium mb-1">{{ exampleFirstTitle }}</div>
          <a class="text-body-2 text-primary text-break" :href="exampleFirstUrl" target="_blank" rel="noopener noreferrer">{{
            exampleFirstUrl
          }}</a>
        </div>
        <div :class="{ 'mb-3': exampleFilter }">
          <div class="text-body-2 font-weight-medium mb-1">{{ ui.exampleOffset }}</div>
          <a class="text-body-2 text-primary text-break" :href="exampleOffsetUrl" target="_blank" rel="noopener noreferrer">{{
            exampleOffsetUrl
          }}</a>
        </div>
        <div v-if="exampleFilter">
          <div class="text-body-2 font-weight-medium mb-1">{{ exampleFilter.title }}</div>
          <a class="text-body-2 text-primary text-break" :href="exampleFilter.url" target="_blank" rel="noopener noreferrer">{{
            exampleFilter.url
          }}</a>
        </div>
      </v-expansion-panel-text>
    </v-expansion-panel>
  </v-expansion-panels>
</template>

<script setup>
import { computed, inject } from 'vue';

defineOptions({ name: 'IndicatorDataApiReferenceCard' });

const props = defineProps({
  dataBaseUrl: { type: String, default: '' },
  ui: { type: Object, default: () => ({}) },
  showBulkDownloadsApiLink: { type: Boolean, default: false },
  bulkDownloadsCatalogUrl: { type: String, default: '' },
  exampleLimit: { type: Number, default: 25 },
  /** { role: { role, label, codes[] } | null, column: { name, label, codes[] } | null } */
  filterExamples: { type: Object, default: () => ({}) },
  structureUrl: { type: String, default: '' },
  openapiUrl: { type: String, default: '' },
  playgroundUrl: { type: String, default: '' },
});

const setMessage = inject('setMessage', () => {});

const base = computed(() => String(props.dataBaseUrl || '').replace(/\/$/, ''));

const schemaUrl = computed(() => (base.value ? `${base.value}/schema` : ''));
const dataUrl = computed(() => (base.value ? `${base.value}` : ''));
const notesUrl = computed(() => (base.value ? `${base.value}/notes` : ''));

/** Filter query fragments built from this dataset's own codes, e.g. d[geography]=AFG,AFE. */
const filterParams = computed(() => {
  const ex = props.filterExamples || {};
  return {
    role: ex.role?.codes?.length ? `d[${ex.role.role}]=${ex.role.codes.join(',')}` : '',
    column: ex.column?.codes?.length ? `c[${ex.column.name}]=${ex.column.codes.join(',')}` : '',
  };
});

const paramRows = computed(() => {
  const u = props.ui || {};
  const fp = filterParams.value;
  return [
    { name: u.paramLimit, desc: u.paramLimitDesc },
    { name: u.paramOffset, desc: u.paramOffsetDesc },
    { name: u.paramSort, desc: u.paramSortDesc },
    { name: u.paramFrom, desc: u.paramFromDesc },
    { name: 'd[…]', desc: u.paramDDesc, example: fp.role, filter: true },
    { name: 'c[…]', desc: u.paramCDesc, example: fp.column, filter: true },
  ].filter((r) => r.name && r.desc);
});

const hasFilterRows = computed(() => paramRows.value.some((r) => r.filter));

const structureNoteParts = computed(() => {
  const s = String(props.ui?.structureNote || 'Column names and codes are listed on the {link} tab.');
  const i = s.indexOf('{link}');
  return i < 0 ? [s + ' ', ''] : [s.slice(0, i), s.slice(i + 6)];
});
const structureNoteBefore = computed(() => structureNoteParts.value[0]);
const structureNoteAfter = computed(() => structureNoteParts.value[1]);

/** First available filter, shown as a ready-to-open example URL. */
const exampleFilter = computed(() => {
  const u = dataUrl.value;
  if (!u) return null;
  const ex = props.filterExamples || {};
  const fp = filterParams.value;
  const key = ['role', 'column'].find((k) => fp[k]);
  if (!key) return null;
  const tpl = String(props.ui?.exampleFilter || 'Filter by {label}');
  return {
    title: tpl.replace(/\{label\}/g, ex[key]?.label || key),
    url: `${u}?${fp[key]}&limit=${lim.value}`,
  };
});

const lim = computed(() => (Number(props.exampleLimit) > 0 ? Number(props.exampleLimit) : 25));

const exampleFirstTitle = computed(() => {
  const s = String(props.ui?.exampleFirst || 'Get first {limit} results');
  return s.replace(/\{limit\}/g, String(lim.value));
});

const exampleFirstUrl = computed(() => {
  const u = dataUrl.value;
  if (!u) return '';
  return `${u}?limit=${lim.value}`;
});

const exampleOffsetUrl = computed(() => {
  const u = dataUrl.value;
  if (!u) return '';
  return `${u}?limit=${lim.value}&offset=${lim.value}`;
});

async function copyUrl(url) {
  const s = String(url ?? '');
  if (!s) return;
  try {
    if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(s);
    else throw new Error('no clipboard');
    setMessage(props.ui?.copied || 'Copied.', 'info');
  } catch {
    setMessage(props.ui?.copyFailed || 'Could not copy.', 'error');
  }
}
</script>

<style scoped>
.api-usage-expansion :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.api-usage-expansion :deep(.v-expansion-panel-title) {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  min-height: 2.5rem !important;
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

.api-line {
  display: flex;
  align-items: flex-start;
  gap: 0.25rem;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.35);
  padding-bottom: 0.5rem;
}

.api-ref-url {
  font-family: ui-monospace, Menlo, monospace;
  font-size: 0.75rem;
  min-width: 0;
  flex: 1 1 auto;
  word-break: break-all;
}

.api-ref-table :deep(td) {
  border: none !important;
}

.api-ref-table code {
  font-size: 0.75rem;
}

.api-ref-example {
  word-break: break-all;
}
</style>
