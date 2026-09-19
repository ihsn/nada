<template>
  <div>
    <v-alert v-if="error" type="error" variant="tonal" density="compact">
      {{ error }}
    </v-alert>
    <v-progress-linear v-else-if="loading && !rows.length" indeterminate color="primary" class="mb-3" />
    <v-table v-else>
      <thead>
        <tr>
          <th style="width: 140px">Type</th>
          <th style="min-width: 160px">Coverage</th>
          <th class="text-right">DB</th>
          <th class="text-right">Indexed</th>
          <th class="text-right">Missing</th>
          <th class="text-right">Stale</th>
          <th class="text-right">Errors</th>
          <th v-if="showActions" />
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.data_type">
          <td>
            <div class="font-weight-medium">{{ typeLabel(row.data_type) }}</div>
            <div
              v-if="typeLabel(row.data_type) !== row.data_type"
              class="text-caption text-medium-emphasis"
            >{{ row.data_type }}</div>
          </td>
          <td>
            <div class="d-flex semantic-coverage-bar">
              <div :style="{ flexBasis: rowSegments(row).indexedPct + '%', background: 'rgb(var(--v-theme-success))' }" />
              <div :style="{ flexBasis: rowSegments(row).missingPct + '%', background: 'rgb(var(--v-theme-warning))' }" />
              <div :style="{ flexBasis: rowSegments(row).errorsPct + '%', background: 'rgb(var(--v-theme-error))' }" />
            </div>
          </td>
          <td class="text-right text-medium-emphasis">{{ formatCount(row.catalog_total) }}</td>
          <td class="text-right font-weight-medium">{{ formatCount(row.indexed) }}</td>
          <td class="text-right">
            <a
              v-if="clickableCounts && row.missing > 0"
              href="#"
              class="text-decoration-underline"
              @click.prevent="$emit('select-missing', row.data_type)"
            >{{ formatCount(row.missing) }}</a>
            <span v-else>{{ formatCount(row.missing) }}</span>
          </td>
          <td class="text-right" :class="row.stale > 0 ? 'text-deep-orange' : ''">
            <a
              v-if="clickableCounts && row.stale > 0"
              href="#"
              class="text-deep-orange text-decoration-underline"
              @click.prevent="$emit('select-stale', row.data_type)"
            >{{ formatCount(row.stale) }}</a>
            <span v-else>{{ formatCount(row.stale) }}</span>
          </td>
          <td class="text-right">
            <a
              v-if="row.errors > 0"
              href="#"
              class="text-error text-decoration-underline"
              @click.prevent="$emit('select-errors', row.data_type)"
            >{{ formatCount(row.errors) }}</a>
            <span v-else class="text-medium-emphasis">0</span>
          </td>
          <td v-if="showActions" class="text-right">
            <v-btn
              size="small"
              variant="text"
              :disabled="!canEdit || (row.missing === 0 && row.stale === 0)"
              :loading="isSubmitting(row.data_type)"
              @click="$emit('index-type', row.data_type)"
            >
              Index
            </v-btn>
          </td>
        </tr>
        <tr v-if="!loading && rows.length === 0">
          <td :colspan="showActions ? 8 : 7" class="text-center text-medium-emphasis py-6">
            No catalog types to show yet.
          </td>
        </tr>
      </tbody>
      <tfoot v-if="rows.length > 0">
        <tr class="font-weight-bold">
          <td>Total</td>
          <td></td>
          <td class="text-right">{{ formatCount(total.catalog_total) }}</td>
          <td class="text-right">{{ formatCount(total.indexed) }}</td>
          <td class="text-right">{{ formatCount(total.missing) }}</td>
          <td class="text-right">{{ formatCount(total.stale) }}</td>
          <td class="text-right">{{ formatCount(total.errors) }}</td>
          <td v-if="showActions" />
        </tr>
      </tfoot>
    </v-table>
    <div class="semantic-coverage-legend">
      <span class="semantic-coverage-legend__item">
        <span class="semantic-coverage-legend__swatch" style="background: rgb(var(--v-theme-success))" />
        Indexed
      </span>
      <span class="semantic-coverage-legend__item">
        <span class="semantic-coverage-legend__swatch" style="background: rgb(var(--v-theme-warning))" />
        Missing
      </span>
      <span class="semantic-coverage-legend__item">
        <span class="semantic-coverage-legend__swatch" style="background: rgb(var(--v-theme-error))" />
        Errors
      </span>
      <span class="text-caption text-medium-emphasis">Bar totals are catalog records per type.</span>
    </div>
  </div>
</template>

<script setup>
import { typeLabel, formatCount } from '../typeLabels.js';

defineOptions({ name: 'SemanticCoverageTable' });

defineProps({
  rows: { type: Array, default: () => [] },
  total: { type: Object, default: () => ({ catalog_total: 0, indexed: 0, missing: 0, stale: 0, errors: 0 }) },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  showActions: { type: Boolean, default: false },
  clickableCounts: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: false },
  isSubmitting: { type: Function, default: () => false },
  rowSegments: { type: Function, required: true },
});

defineEmits(['select-errors', 'select-missing', 'select-stale', 'index-type']);
</script>

<style scoped>
.semantic-coverage-bar {
  width: 100%;
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  background: #e8eaed;
}
</style>
