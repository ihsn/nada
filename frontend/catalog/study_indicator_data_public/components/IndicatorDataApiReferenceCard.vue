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

        <div class="text-caption text-medium-emphasis mt-4 mb-2">{{ ui.queryParamsHeading }}</div>
        <v-table density="compact" class="api-ref-table bg-transparent">
          <tbody>
            <tr v-for="row in paramRows" :key="row.name">
              <td class="py-1 pe-4 align-top"><code class="text-primary">{{ row.name }}</code></td>
              <td class="py-1 text-body-2 text-medium-emphasis">{{ row.desc }}</td>
            </tr>
          </tbody>
        </v-table>

        <div class="text-subtitle-2 mt-5 mb-2">{{ ui.examplesHeading }}</div>
        <div class="mb-3">
          <div class="text-body-2 font-weight-medium mb-1">{{ exampleFirstTitle }}</div>
          <a class="text-body-2 text-primary text-break" :href="exampleFirstUrl" target="_blank" rel="noopener noreferrer">{{
            exampleFirstUrl
          }}</a>
        </div>
        <div>
          <div class="text-body-2 font-weight-medium mb-1">{{ ui.exampleOffset }}</div>
          <a class="text-body-2 text-primary text-break" :href="exampleOffsetUrl" target="_blank" rel="noopener noreferrer">{{
            exampleOffsetUrl
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
});

const setMessage = inject('setMessage', () => {});

const base = computed(() => String(props.dataBaseUrl || '').replace(/\/$/, ''));

const schemaUrl = computed(() => (base.value ? `${base.value}/schema` : ''));
const dataUrl = computed(() => (base.value ? `${base.value}` : ''));

const paramRows = computed(() => {
  const u = props.ui || {};
  return [
    { name: u.paramLimit, desc: u.paramLimitDesc },
    { name: u.paramOffset, desc: u.paramOffsetDesc },
    { name: u.paramSort, desc: u.paramSortDesc },
    { name: u.paramFrom, desc: u.paramFromDesc },
    { name: u.paramDc, desc: u.paramDcDesc },
  ].filter((r) => r.name && r.desc);
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
</style>
