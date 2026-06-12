<template>
  <div class="catalog-variable-results-list">
    <div class="results-toolbar d-flex align-center flex-wrap mb-4" style="gap: 12px;">
      <div class="text-body-2 text-medium-emphasis flex-shrink-0">
        {{ resultsCountLabel }}
      </div>

      <v-spacer />

      <v-btn
        size="small"
        variant="outlined"
        color="primary"
        rounded
        class="variable-results-compare-btn text-none"
        @click="onCompareClick"
      >
        <span class="variable-results-compare-btn__inner">
          <span>{{ t('compare') }}</span>
          <v-chip
            v-if="count > 0"
            size="x-small"
            variant="flat"
            color="primary"
            rounded
            class="variable-results-compare-btn__chip"
          >
            {{ count }}
          </v-chip>
        </span>
      </v-btn>

      <CatalogSortSelect :query="query" @sort="(by, order) => emit('sort', by, order)" />
    </div>

    <div
      v-for="row in results.rows"
      :key="`${row.sid}-${row.vid}`"
      class="variable-row"
      @click="onCardClick(row, $event)"
    >
      <div class="variable-row__compare" @click.stop>
        <v-checkbox
          :model-value="isSelected(row.sid, row.vid)"
          density="compact"
          hide-details
          :title="t('mark_for_variable_comparison', 'Mark for variable comparison')"
          @update:model-value="(v) => onCompareToggle(row, v)"
        />
      </div>

      <div
        class="variable-row__icon"
        :title="t('variable_info')"
      >
        <v-icon size="18" class="variable-row__icon-glyph">{{ VARIABLE_ROW_ICON }}</v-icon>
      </div>

      <div class="variable-row__body">
        <div class="variable-row__title">
          <button
            type="button"
            class="variable-row__title-btn"
            @click.stop="openVariableDialog(row)"
          >
            {{ variableTitle(row) }}
          </button>
          <a
            :href="variablePageUrl(row)"
            target="_blank"
            rel="noopener noreferrer"
            class="variable-row__open"
            :title="t('open_in_new_window', 'Open in new window')"
            @click.stop
          >
            <v-icon size="14">mdi-open-in-new</v-icon>
          </a>
        </div>

        <div v-if="row.qstn" class="variable-row__qstn">
          {{ row.qstn }}
        </div>

        <div v-if="row.fid" class="variable-row__file">
          <span class="variable-row__file-label">{{ t('file') }}:</span>
          {{ row.fid }}
        </div>

        <div class="variable-row__study">
          <a
            :href="studyUrl(row)"
            target="_blank"
            rel="noopener noreferrer"
            class="variable-row__study-link"
            @click.stop
          >
            {{ row.title }}
            <v-icon size="12" class="ms-1">mdi-open-in-new</v-icon>
          </a>
          <div v-if="studyMeta(row)" class="variable-row__study-meta">
            {{ studyMeta(row) }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="totalPages > 1" class="d-flex justify-space-between align-center mt-6 flex-wrap" style="gap: 12px;">
      <div class="text-caption text-medium-emphasis">
        {{ t('showing_pages', 'Page %s of %s', query.page, totalPages.toLocaleString()) }}
      </div>
      <v-pagination
        :model-value="query.page"
        :length="totalPages"
        :total-visible="7"
        density="comfortable"
        rounded
        @update:model-value="onPage"
      />
    </div>

    <CatalogPageSizeSelect
      :model-value="query.ps"
      @update:model-value="(size) => emit('page-size', size)"
    />

    <CatalogVariableDetailDialog
      v-model="detailOpen"
      :study-id="detailStudyId"
      :variable="detailVariable"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import CatalogSortSelect from './CatalogSortSelect.vue';
import CatalogPageSizeSelect from './CatalogPageSizeSelect.vue';
import CatalogVariableDetailDialog from './CatalogVariableDetailDialog.vue';
import { useVariableCompareCart } from '../composables/useVariableCompareCart';
import { joinSiteUrl } from '../catalogUrls';
import { catalogResultsRange } from '../catalogResultsRange';
import { formatStudyYearRange } from '../catalogDate';

defineOptions({ name: 'CatalogVariableResultsList' });

/** Swap to try: mdi-variable, mdi-code-brackets, mdi-table-column, mdi-numeric */
const VARIABLE_ROW_ICON = 'mdi-text-box-outline';

const props = defineProps({
  results: { type: Object, required: true },
  query:   { type: Object, required: true },
});
const emit = defineEmits(['sort', 'page', 'page-size']);

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { isSelected, toggleSelection, tryOpenCompare, count } = useVariableCompareCart();

const detailOpen = ref(false);
const detailStudyId = ref(null);
const detailVariable = ref(null);

const totalPages = computed(() =>
  props.results.found > 0 ? Math.ceil(props.results.found / props.query.ps) : 1
);

const range = computed(() => catalogResultsRange(props.results, props.query));
const fromNum = computed(() => range.value.from);
const toNum = computed(() => range.value.to);
const displayTotal = computed(() => range.value.total);

const resultsCountLabel = computed(() =>
  t(
    'results_count_variables',
    '%s–%s of %s variables',
    fromNum.value.toLocaleString(),
    toNum.value.toLocaleString(),
    displayTotal.value.toLocaleString()
  )
);

function variableTitle(row) {
  const parts = [row.name, row.labl].filter(Boolean);
  const unique = [...new Set(parts)];
  return unique.join(' - ') || row.labl || row.name || '';
}

function yearRange(row) {
  return formatStudyYearRange(row.year_start, row.year_end);
}

function studyMeta(row) {
  return [row.nation, yearRange(row), row.idno, row.authoring_entity].filter(Boolean).join(' · ');
}

function variablePageUrl(row) {
  return joinSiteUrl(siteUrl.value, `catalog/${row.sid}/variable/${row.vid}`);
}

function studyUrl(row) {
  return joinSiteUrl(siteUrl.value, `catalog/${row.sid}`);
}

function openVariableDialog(row) {
  detailStudyId.value = row.sid;
  detailVariable.value = row;
  detailOpen.value = true;
}

function onCompareToggle(row, checked) {
  toggleSelection(row.sid, row.vid, checked, t);
}

function onCompareClick() {
  tryOpenCompare(siteUrl.value, t);
}

function onCardClick(row, event) {
  if (event.target.closest('a, button, [role="button"], .variable-row__compare')) return;
  openVariableDialog(row);
}

function onPage(p) {
  emit('page', p);
}
</script>

<style scoped>
.variable-row__compare {
  flex-shrink: 0;
  padding-top: 6px;
}

.variable-row__compare :deep(.v-selection-control) {
  min-height: 0;
}

.variable-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding-bottom: 10px;
  margin-bottom: 10px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  cursor: pointer;
}

.variable-row__icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #eef2f7;
  border: 1px solid rgba(15, 23, 42, 0.08);
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

.variable-row:hover .variable-row__icon {
  background: #e3eaf3;
  border-color: rgba(21, 101, 192, 0.22);
}

.variable-row__icon-glyph {
  color: #1565c0 !important;
}

.variable-row__body {
  flex: 1;
  min-width: 0;
}

.variable-row:last-of-type {
  margin-bottom: 0;
}

.variable-row__title {
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.variable-row__title-btn {
  flex: 1;
  min-width: 0;
  border: 0;
  padding: 0;
  background: transparent;
  text-align: left;
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  color: #1565c0;
  cursor: pointer;
}

.variable-row__title-btn:hover {
  text-decoration: underline;
}

.variable-row__open {
  flex-shrink: 0;
  color: rgba(26, 35, 50, 0.4);
  line-height: 1;
  padding-top: 1px;
}

.variable-row__open:hover {
  color: #1565c0;
}

.variable-row__qstn {
  margin-top: 4px;
  font-size: 0.875rem;
  line-height: 1.4;
  color: rgba(26, 35, 50, 0.72);
}

.variable-row__file {
  margin-top: 4px;
  font-size: 0.8125rem;
  line-height: 1.35;
  color: rgba(26, 35, 50, 0.55);
}

.variable-row__file-label {
  font-weight: 500;
}

.variable-row__study {
  margin-top: 6px;
  font-size: 0.8125rem;
  line-height: 1.4;
  color: rgba(26, 35, 50, 0.6);
}

.variable-row__study-link {
  color: inherit;
  text-decoration: none;
}

.variable-row__study-link:hover {
  color: #1565c0;
  text-decoration: underline;
}

.variable-row__study-meta {
  margin-top: 2px;
}

.results-toolbar {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding-bottom: 12px;
}

.variable-results-compare-btn__inner {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.variable-results-compare-btn__chip {
  flex-shrink: 0;
}

.variable-results-compare-btn__chip :deep(.v-chip__content) {
  padding-inline: 6px;
}
</style>
