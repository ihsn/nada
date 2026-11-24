<template>
  <section>
    <div class="tda-section-head">
      <h2 class="tda-h2">{{ ui.dictionaryHeading }}</h2>
      <v-chip size="small" variant="tonal">{{ fields.length.toLocaleString() }}</v-chip>
      <v-spacer />
      <v-btn size="small" variant="text" class="text-none" prepend-icon="mdi-code-json" :href="jsonUrl" target="_blank" rel="noopener">
        JSON
      </v-btn>
    </div>

    <v-text-field
      v-if="fields.length > 10"
      v-model="search"
      :placeholder="ui.dictionarySearch"
      density="compact"
      variant="outlined"
      hide-details
      clearable
      prepend-inner-icon="mdi-magnify"
      class="mb-3 tdd-search"
    />

    <v-sheet border rounded="lg" class="overflow-hidden">
      <v-table density="compact" :height="filtered.length > 15 ? 520 : undefined" fixed-header>
        <thead>
          <tr>
            <th class="tdd-num">#</th>
            <th>{{ ui.colName }}</th>
            <th>{{ ui.colLabel }}</th>
            <th>{{ ui.colType }}</th>
            <th>{{ ui.colCodes }}</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="field in filtered" :key="field.name">
            <tr>
              <td class="tdd-num text-medium-emphasis">{{ field._n }}</td>
              <td class="font-weight-medium">{{ field.name }}</td>
              <td>{{ field.label && field.label !== field.name ? field.label : '' }}</td>
              <td class="text-medium-emphasis">{{ field.data_type || '' }}</td>
              <td>
                <v-btn
                  v-if="field.code_list?.length"
                  size="x-small"
                  variant="tonal"
                  class="text-none"
                  :append-icon="open[field.name] ? 'mdi-chevron-up' : 'mdi-chevron-down'"
                  @click="open[field.name] = !open[field.name]"
                >
                  {{ fmt(ui.codesCount, { count: field.code_list.length }) }}
                </v-btn>
              </td>
            </tr>
            <tr v-if="open[field.name]" class="tdd-codes-row">
              <td></td>
              <td colspan="4" class="py-2">
                <v-table density="compact" class="tdd-codes">
                  <thead>
                    <tr>
                      <th>{{ ui.codeCol }}</th>
                      <th>{{ ui.colLabel }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(code, i) in field.code_list" :key="i">
                      <td>{{ code.code }}</td>
                      <td>{{ code.label }}</td>
                    </tr>
                  </tbody>
                </v-table>
              </td>
            </tr>
          </template>
          <tr v-if="!filtered.length">
            <td colspan="5" class="text-center text-medium-emphasis py-6">{{ ui.dictionaryEmpty }}</td>
          </tr>
        </tbody>
      </v-table>
    </v-sheet>
  </section>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { fmt } from '../utils/tableQuery.js';

defineOptions({ name: 'TableDataDictionary' });

const props = defineProps({
  dictionary: { type: Array, default: () => [] },
  jsonUrl: { type: String, default: '' },
  ui: { type: Object, required: true },
});

const search = ref('');
const open = reactive({});

const fields = computed(() => props.dictionary.filter((f) => f?.name).map((f, i) => ({ ...f, _n: i + 1 })));
const filtered = computed(() => {
  const q = String(search.value || '').toLowerCase().trim();
  if (!q) return fields.value;
  return fields.value.filter(
    (f) => f.name.toLowerCase().includes(q) || String(f.label || '').toLowerCase().includes(q)
  );
});
</script>

<style scoped>
.tdd-search {
  max-width: 360px;
}
.tdd-num {
  width: 56px;
}
.tdd-codes-row > td {
  background: rgba(var(--v-theme-on-surface), 0.03);
}
.tdd-codes {
  max-width: 640px;
  background: transparent;
}
</style>
