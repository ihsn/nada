<template>
  <div class="import-failure-details">
    <template v-if="columns.length">
      <div class="font-weight-medium mb-1">
        Import stopped: {{ rowsFailed.toLocaleString() }} rows had invalid values
        <span v-if="maxInvalidRows">(limit {{ maxInvalidRows.toLocaleString() }})</span>.
      </div>
      <div class="text-body-2 mb-2">
        Rows imported before the failure remain in the table. Fix the data type in the data dictionary
        or the CSV file, then re-import.
      </div>
      <table class="import-failure-details__table text-body-2">
        <thead>
          <tr>
            <th>Column</th>
            <th>Type</th>
            <th class="text-right">Rows</th>
            <th>Examples</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="col in columns" :key="col.column">
            <td class="font-weight-medium">{{ col.column }}</td>
            <td>{{ col.data_type }}</td>
            <td class="text-right">{{ (col.count || 0).toLocaleString() }}</td>
            <td>
              <code v-for="(ex, i) in col.examples || []" :key="i" class="import-failure-details__example">{{
                ex === '' ? '(empty)' : ex
              }}</code>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="otherFailed > 0" class="text-caption mt-1">
        {{ otherFailed.toLocaleString() }} rows were malformed or rejected by the database.
      </div>
    </template>
    <div v-else class="font-weight-medium">{{ progress.failure_message || fallbackMessage }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  progress: { type: Object, required: true },
  fallbackMessage: { type: String, default: 'Import failed' },
});

const columns = computed(() =>
  [...(props.progress.invalid_columns || [])].sort((a, b) => (b.count || 0) - (a.count || 0))
);
const rowsFailed = computed(() => props.progress.rows_failed || 0);
const maxInvalidRows = computed(() => props.progress.max_invalid_rows || 0);
const otherFailed = computed(
  () => rowsFailed.value - columns.value.reduce((sum, c) => sum + (c.count || 0), 0)
);
</script>

<style scoped>
.import-failure-details__table {
  width: 100%;
  border-collapse: collapse;
}

.import-failure-details__table th,
.import-failure-details__table td {
  text-align: left;
  padding: 3px 8px 3px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  vertical-align: top;
}

.import-failure-details__table th.text-right,
.import-failure-details__table td.text-right {
  text-align: right;
}

.import-failure-details__table th {
  font-weight: 600;
}

.import-failure-details__example {
  display: inline-block;
  margin: 0 4px 2px 0;
  padding: 0 4px;
  border-radius: 3px;
  background: rgba(0, 0, 0, 0.06);
  font-size: 0.75rem;
  word-break: break-all;
}
</style>
