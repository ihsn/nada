<template>
  <div
    class="indicator-pivot-table"
    :class="{ 'indicator-pivot-table--sticky': sticky }"
    :style="sticky ? stickyStyle : undefined"
  >
    <table class="indicator-pivot-table__grid">
      <thead v-if="model.headerRows?.length">
        <tr v-for="(headerRow, rowIdx) in model.headerRows" :key="'hr-' + rowIdx">
          <th
            v-if="rowIdx === 0 && model.showRowLabels"
            class="indicator-pivot-table__corner"
            :class="{ 'indicator-pivot-table__corner--sticky': sticky }"
            :rowspan="model.cornerRowspan || 1"
            scope="col"
            :aria-hidden="cornerIsEmpty ? 'true' : undefined"
          >
            {{ cornerHeaderTitle }}
          </th>
          <th
            v-for="(cell, cellIdx) in headerRow"
            :key="'hc-' + rowIdx + '-' + cellIdx"
            class="indicator-pivot-table__col-header"
            :class="{
              'indicator-pivot-table__col-header--sticky': sticky,
              'indicator-pivot-table__col-header--group-start': cell.groupStart,
            }"
            :data-header-row="rowIdx"
            :colspan="cell.colspan || 1"
            :rowspan="cell.rowspan || 1"
            scope="col"
          >
            {{ cell.title }}
          </th>
        </tr>
      </thead>
      <tbody>
        <template v-for="(section, sIdx) in model.bodySections" :key="'sec-' + (section.sectionKey || sIdx)">
          <tr
            v-if="section.title && section.isGroupHeader"
            class="indicator-pivot-table__section-row"
          >
            <td :colspan="totalColSpan" class="indicator-pivot-table__section-cell" :style="sectionIndent(section.depth)">
              {{ section.title }}
            </td>
          </tr>
          <tr v-for="row in section.rows || []" :key="section.sectionKey + '-' + row.rowKey">
            <th
              v-if="model.showRowLabels"
              class="indicator-pivot-table__row-header"
              :class="{ 'indicator-pivot-table__row-header--sticky': sticky }"
              scope="row"
              :style="sectionIndent(section.depth)"
            >
              {{ row.label }}
            </th>
            <td
              v-for="col in model.leafColumns"
              :key="col.key"
              class="indicator-pivot-table__cell"
              :class="{ 'indicator-pivot-table__cell--group-start': col.groupStart }"
            >
              {{ row.cells[col.key] }}
            </td>
          </tr>
        </template>
        <tr v-if="!hasDataRows">
          <td :colspan="totalColSpan" class="indicator-pivot-table__empty text-medium-emphasis">
            No data for this layout and filter selection.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'IndicatorPivotTable' });

const HEADER_ROW_HEIGHT = '2.5rem';

const props = defineProps({
  model: {
    type: Object,
    required: true,
  },
  cornerHeaderTitle: {
    type: String,
    default: '',
  },
  sticky: {
    type: Boolean,
    default: false,
  },
});

const cornerIsEmpty = computed(() => !String(props.cornerHeaderTitle ?? '').trim());

const headerRowCount = computed(() => props.model?.headerRows?.length ?? 0);

const stickyStyle = computed(() => ({
  '--pivot-header-row-height': HEADER_ROW_HEIGHT,
  '--pivot-header-row-count': String(headerRowCount.value || 1),
}));

const hasDataRows = computed(() => {
  const sections = props.model?.bodySections;
  if (!Array.isArray(sections)) return false;
  return sections.some((s) => Array.isArray(s.rows) && s.rows.length > 0);
});

const totalColSpan = computed(() => {
  const cols = props.model?.leafColumns?.length ?? 1;
  return (props.model?.showRowLabels ? 1 : 0) + cols;
});

function sectionIndent(depth) {
  const d = Number(depth) || 0;
  if (d <= 0) return undefined;
  return { paddingLeft: `${0.75 + d * 0.75}rem` };
}
</script>

<style scoped>
.indicator-pivot-table__grid {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.875rem;
  --pivot-header-bg: color-mix(in srgb, rgb(var(--v-theme-surface)) 96%, rgb(var(--v-theme-on-surface)));
}

.indicator-pivot-table__corner,
.indicator-pivot-table__col-header,
.indicator-pivot-table__row-header {
  font-weight: 600;
  text-align: left;
  padding: 0.625rem 0.75rem;
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  background: var(--pivot-header-bg);
  white-space: nowrap;
}

.indicator-pivot-table__corner--sticky {
  position: sticky;
  top: 0;
  left: 0;
  z-index: 5;
  background: var(--pivot-header-bg);
  box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.06);
}

.indicator-pivot-table__col-header--sticky {
  position: sticky;
  top: calc(var(--pivot-header-row-height, 2.5rem) * var(--header-row, 0));
  z-index: 3;
  background: var(--pivot-header-bg);
}

.indicator-pivot-table__row-header--sticky {
  position: sticky;
  left: 0;
  z-index: 2;
  background: var(--pivot-header-bg);
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
}

.indicator-pivot-table__col-header--sticky[data-header-row='0'] {
  --header-row: 0;
}

.indicator-pivot-table__col-header--sticky[data-header-row='1'] {
  --header-row: 1;
}

.indicator-pivot-table__col-header--sticky[data-header-row='2'] {
  --header-row: 2;
}

.indicator-pivot-table__col-header {
  text-align: center;
  border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.indicator-pivot-table__cell {
  text-align: right;
}

.indicator-pivot-table__row-header {
  text-align: left;
  min-width: 8rem;
}

.indicator-pivot-table__cell {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid rgba(var(--v-border-color), calc(var(--v-border-opacity) * 0.6));
  font-variant-numeric: tabular-nums;
  background: rgb(var(--v-theme-surface));
}

.indicator-pivot-table__section-row {
  background: rgba(var(--v-theme-primary), 0.06);
}

.indicator-pivot-table__section-cell {
  padding: 0.5rem 0.75rem;
  font-weight: 600;
  font-size: 0.8125rem;
  text-align: left;
  background: rgba(var(--v-theme-primary), 0.06);
}

.indicator-pivot-table__col-header--group-start,
.indicator-pivot-table__cell--group-start {
  border-left: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.indicator-pivot-table__empty {
  padding: 1.5rem 0.75rem;
  text-align: center;
}
</style>
