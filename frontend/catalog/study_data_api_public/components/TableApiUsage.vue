<template>
  <section>
    <div class="tda-section-head">
      <h2 class="tda-h2">{{ ui.apiHeading }}</h2>
    </div>

    <v-sheet border rounded="lg" class="pa-4">
      <div v-for="ep in endpoints" :key="ep.url" class="mb-3">
        <div class="text-subtitle-2">{{ ep.label }}</div>
        <a :href="ep.url" target="_blank" rel="noopener" class="tau-url">{{ ep.url }}</a>
      </div>
    </v-sheet>

    <h3 class="tda-h3">{{ ui.apiParamsHeading }}</h3>
    <v-sheet border rounded="lg" class="overflow-hidden">
      <v-table density="compact">
        <thead>
          <tr>
            <th>{{ ui.parameterCol }}</th>
            <th>{{ ui.descriptionCol }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in parameters" :key="p.name">
            <td class="text-no-wrap"><code>{{ p.name }}</code></td>
            <td>{{ p.desc }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-sheet>

    <h3 class="tda-h3">{{ ui.filterSyntaxHeading }}</h3>
    <v-sheet border rounded="lg" class="overflow-hidden">
      <v-table density="compact">
        <tbody>
          <tr v-for="s in filterSyntax" :key="s.example">
            <td class="text-no-wrap"><code>{{ s.example }}</code></td>
            <td>{{ s.desc }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-sheet>

    <h3 class="tda-h3">{{ ui.examplesHeading }}</h3>
    <v-sheet border rounded="lg" class="pa-4">
      <div v-for="ex in examples" :key="ex.url" class="mb-3">
        <div class="text-subtitle-2">{{ ex.label }}</div>
        <a :href="ex.url" target="_blank" rel="noopener" class="tau-url">{{ ex.display }}</a>
      </div>
    </v-sheet>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { fmt, isNumericType } from '../utils/tableQuery.js';

defineOptions({ name: 'TableApiUsage' });

const props = defineProps({
  urls: { type: Object, required: true },
  dictionary: { type: Array, default: () => [] },
  bulkDownloadsUrl: { type: String, default: '' },
  /** the table's uploaded CSV can be downloaded */
  csvDownload: { type: Boolean, default: false },
  ui: { type: Object, required: true },
});

const endpoints = computed(() => {
  const list = [
    { label: props.ui.apiInfoLabel, url: props.urls.infoWithDictionary },
    { label: props.ui.apiDataLabel, url: props.urls.data },
    { label: props.ui.apiDictionaryLabel, url: props.urls.dictionary },
  ];
  if (props.csvDownload) {
    list.push({ label: props.ui.apiDownloadLabel, url: props.urls.download });
  }
  if (props.bulkDownloadsUrl) {
    list.push({ label: props.ui.bulkDownloadsHeading, url: props.bulkDownloadsUrl });
  }
  return list;
});

const parameters = computed(() => [
  { name: 'fields', desc: props.ui.paramFields },
  { name: 'limit', desc: props.ui.paramLimit },
  { name: 'offset', desc: props.ui.paramOffset },
  { name: 'format', desc: props.ui.paramFormat },
  { name: 'disposition', desc: props.ui.paramDisposition },
  { name: '{column}=…', desc: props.ui.paramColumn },
  { name: 'c[{column}]=…', desc: props.ui.paramC },
  { name: 'ft_query', desc: props.ui.paramFtQuery },
]);

const filterSyntax = computed(() => [
  { example: 'col=a,b,c', desc: props.ui.filterAnyOf },
  { example: 'col=1-10', desc: props.ui.filterRange },
  { example: 'col=>=5  col=<=5', desc: props.ui.filterGte },
  { example: 'col=!a', desc: props.ui.filterExclude },
  { example: 'col="1-10"', desc: props.ui.filterQuoted },
  { example: 'a=1&b=2', desc: props.ui.filterCombine },
]);

/** Examples use the table's real column names where possible. */
const examples = computed(() => {
  const names = props.dictionary.map((f) => f?.name).filter(Boolean);
  const data = props.urls.data;
  const list = [
    { label: props.ui.exampleFirstRows, query: 'limit=15' },
    { label: props.ui.exampleNextPage, query: 'limit=15&offset=15' },
  ];
  if (names.length >= 2) {
    list.push({ label: props.ui.exampleColumns, query: `fields=${names.slice(0, 3).join(',')}&limit=15` });
  }
  list.push({ label: props.ui.exampleCsv, query: 'limit=15&format=csv' });

  const groupField =
    props.dictionary.find((f) => Array.isArray(f?.code_list) && f.code_list.length)?.name ||
    props.dictionary.find((f) => f?.name && !isNumericType(f.data_type))?.name;
  const out = list.map((ex) => ({ label: ex.label, url: `${data}?${encodeURI(ex.query)}`, display: `${data}?${ex.query}` }));
  if (groupField) {
    const q = `fields=${groupField}`;
    out.push({
      label: fmt(props.ui.exampleAggregate, { column: groupField }),
      url: `${props.urls.aggregate}?${encodeURI(q)}`,
      display: `${props.urls.aggregate}?${q}`,
    });
  }
  return out;
});
</script>

<style scoped>
.tau-url {
  font-family: monospace;
  font-size: 0.8125rem;
  word-break: break-all;
}
</style>
