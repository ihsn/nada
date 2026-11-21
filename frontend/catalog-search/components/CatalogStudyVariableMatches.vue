<template>
  <div
    v-if="visible"
    class="study-variable-matches"
    :class="{ 'study-variable-matches--expanded': expanded }"
    @click.stop
  >
    <button
      type="button"
      class="study-variable-matches__toggle"
      :aria-expanded="expanded"
      :aria-controls="panelId"
      @click="onToggle"
    >
      <v-icon icon="$mdi-format-list-bulleted" size="18" class="study-variable-matches__icon" />
      <span class="study-variable-matches__heading">
        <strong class="study-variable-matches__count">{{ matchLabel }}</strong>
        <span v-if="totalLabel" class="study-variable-matches__total">{{ totalLabel }}</span>
      </span>
      <span class="study-variable-matches__action">
        {{ expanded ? t('hide_variables', 'Hide variables') : t('show_variables', 'Show variables') }}
        <v-icon
          icon="$mdi-chevron-down"
          size="18"
          class="study-variable-matches__chevron"
        />
      </span>
    </button>

    <div v-if="expanded" :id="panelId" class="study-variable-matches__panel">
      <div v-if="loading" class="study-variable-matches__skeleton" aria-busy="true" :aria-label="t('js_loading')">
        <div v-for="n in 3" :key="n" class="study-variable-matches__skeleton-row">
          <span class="sk sk--box" />
          <span class="sk sk--name" />
          <span class="sk sk--label" />
        </div>
      </div>

      <v-alert
        v-else-if="error"
        type="error"
        variant="tonal"
        density="compact"
        class="ma-3"
        :text="error"
      />

      <template v-else-if="variables && variables.length">
        <div class="study-variable-matches__table-wrap">
          <table class="study-variable-matches__table">
            <thead>
              <tr>
                <th class="study-variable-matches__check-col">
                  <input
                    ref="selectAllEl"
                    type="checkbox"
                    class="study-variable-matches__checkbox"
                    :checked="allSelected"
                    :title="t('select_all', 'Select all')"
                    :aria-label="t('select_all', 'Select all')"
                    @change="onSelectAll"
                  >
                </th>
                <th class="study-variable-matches__name-col">{{ t('name') }}</th>
                <th>{{ t('label') }}</th>
                <th v-if="hasFileColumn" class="study-variable-matches__file-col">{{ t('file') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="variable in variables"
                :key="variable.uid || variable.vid"
                class="study-variable-matches__row"
                :class="{ 'is-selected': isSelected(row.id, variable.vid) }"
                @click="onRowClick(variable)"
              >
                <td class="study-variable-matches__check-col">
                  <input
                    type="checkbox"
                    class="study-variable-matches__checkbox"
                    :checked="isSelected(row.id, variable.vid)"
                    :title="t('mark_for_variable_comparison', 'Mark for variable comparison')"
                    :aria-label="t('mark_for_variable_comparison', 'Mark for variable comparison') + ': ' + variable.name"
                    @click.stop
                    @change="onCompareToggle(variable.vid, $event)"
                  >
                </td>
                <td class="study-variable-matches__name-col">
                  <button
                    type="button"
                    class="study-variable-matches__var-name"
                    :title="t('variable_info', 'Variable information')"
                    @click.stop="openVariableDialog(variable)"
                    v-html="highlight(variable.name)"
                  />
                </td>
                <td class="study-variable-matches__label" :title="variable.labl" v-html="highlight(variable.labl)" />
                <td v-if="hasFileColumn" class="study-variable-matches__file-col">
                  <span class="study-variable-matches__file" :title="variable.file_name || variable.fid">
                    {{ variable.file_name || variable.fid }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="isTruncated" class="study-variable-matches__more">
          {{ t('showing_first_n_of_m', 'Showing the first %s of %s matches', formatNumber(variables.length), formatNumber(row.var_found)) }}
        </div>
      </template>

      <div v-else class="study-variable-matches__empty">
        {{ t('no_records_found') }}
      </div>

      <div class="study-variable-matches__footer">
        <span class="study-variable-matches__compare-summary" aria-live="polite">
          {{ compareSummary }}
        </span>
        <button
          v-if="count > 0"
          type="button"
          class="study-variable-matches__clear"
          @click="clear"
        >
          {{ t('clear_selection', 'Clear selection') }}
        </button>
        <v-btn
          size="small"
          variant="flat"
          color="primary"
          class="study-variable-matches__compare-btn"
          :disabled="count < 2"
          @click="onCompareClick"
        >
          {{ count > 0 ? t('compare_n', 'Compare (%d)', count) : t('compare') }}
        </v-btn>
      </div>
    </div>

    <CatalogVariableDetailDialog
      v-if="detailOpen"
      v-model="detailOpen"
      :study-id="row.id"
      :variable="detailVariable"
    />
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, ref, watch, watchEffect } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useVariableCompareCart } from '../composables/useVariableCompareCart';

const CatalogVariableDetailDialog = defineAsyncComponent(
  () => import('./CatalogVariableDetailDialog.vue')
);

defineOptions({ name: 'CatalogStudyVariableMatches' });

const PAGE_SIZE = 50;

const props = defineProps({
  row:           { type: Object, required: true },
  searchKeyword: { type: String, default: '' },
});

const { t } = useI18n();
const { apiBaseUrl, siteUrl } = useAppConfig();
const {
  isSelected,
  toggleSelection,
  tryOpenCompare,
  clear,
  count,
  studyCount,
  MAX_ITEMS,
} = useVariableCompareCart();

const expanded = ref(false);
const loading = ref(false);
const error = ref(null);
const variables = ref(null);
const detailOpen = ref(false);
const detailVariable = ref(null);
const selectAllEl = ref(null);

let fetchController = null;

const keyword = computed(() => (props.searchKeyword || '').trim());
const panelId = computed(() => `study-vars-${props.row.id}`);

const visible = computed(() =>
  props.row?.var_found != null
  && props.row.var_found !== ''
  && Number(props.row.var_found) > 0
  && keyword.value !== ''
  && (props.row.idno || props.row.id)
);

function formatNumber(n) {
  const num = Number(n);
  return Number.isFinite(num) ? num.toLocaleString() : String(n ?? '');
}

const matchLabel = computed(() => {
  const n = Number(props.row.var_found);
  return n === 1
    ? t('variable_matching_n', '%s matching variable', formatNumber(n))
    : t('variables_matching_n', '%s matching variables', formatNumber(n));
});

const totalLabel = computed(() => {
  const total = Number(props.row.varcount);
  if (!Number.isFinite(total) || total <= 0) return '';
  return t('of_n_in_study', 'of %s in this study', formatNumber(total));
});

const isTruncated = computed(() =>
  Array.isArray(variables.value)
  && variables.value.length >= PAGE_SIZE
  && Number(props.row.var_found) > variables.value.length
);

const hasFileColumn = computed(() =>
  Array.isArray(variables.value)
  && variables.value.some((v) => (v.file_name || v.fid) != null && (v.file_name || v.fid) !== '')
);

const selectedInList = computed(() =>
  (variables.value || []).filter((v) => isSelected(props.row.id, v.vid)).length
);

const allSelected = computed(() =>
  !!variables.value?.length && selectedInList.value === variables.value.length
);

watchEffect(() => {
  if (selectAllEl.value) {
    selectAllEl.value.indeterminate = selectedInList.value > 0 && !allSelected.value;
  }
});

const compareSummary = computed(() => {
  if (count.value === 0) {
    return t('compare_select_hint', 'Select 2 or more variables to compare');
  }
  if (count.value === 1) {
    return t('compare_select_one_more', '1 selected, select 1 more to compare');
  }
  return t('compare_variables_summary', '%d variables from %d studies', count.value, studyCount.value);
});

/** Search terms for highlighting: drop quotes/operators and very short tokens. */
const highlightRegex = computed(() => {
  const terms = keyword.value
    .replace(/["'()+\-~*<>]/g, ' ')
    .split(/\s+/)
    .filter((w) => w.length >= 2 && !/^(and|or|not)$/i.test(w))
    .map((w) => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
  if (!terms.length) return null;
  return new RegExp(`(${terms.join('|')})`, 'gi');
});

function escapeHtml(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/** Split on search terms (capture group puts matches at odd indexes), escape each part, wrap matches. */
function highlight(text) {
  if (!highlightRegex.value) return escapeHtml(text);
  return String(text ?? '')
    .split(highlightRegex.value)
    .map((part, i) => (i % 2 === 1 ? `<mark>${escapeHtml(part)}</mark>` : escapeHtml(part)))
    .join('');
}

function onCompareToggle(vid, event) {
  const result = toggleSelection(props.row.id, vid, event.target.checked, t);
  if (!result.ok) {
    event.target.checked = false;
  }
}

function onRowClick(variable) {
  const checked = !isSelected(props.row.id, variable.vid);
  toggleSelection(props.row.id, variable.vid, checked, t);
}

/**
 * Header checkbox: if anything in this list is selected (all or partial), clear this list;
 * otherwise select as many as the compare limit allows. Driven by state, not the native
 * checked value, because the max limit means "all selected" may never be reached.
 */
function onSelectAll() {
  const list = variables.value || [];
  if (selectedInList.value > 0) {
    list.forEach((v) => toggleSelection(props.row.id, v.vid, false));
    nextTick(syncSelectAllEl);
    return;
  }
  for (const v of list) {
    if (isSelected(props.row.id, v.vid)) continue;
    if (count.value >= MAX_ITEMS) {
      window.alert(t('You have selected the maximum variables to compare'));
      break;
    }
    toggleSelection(props.row.id, v.vid, true);
  }
  nextTick(syncSelectAllEl);
}

/** Keep the native header checkbox in sync with the cart (checked / indeterminate). */
function syncSelectAllEl() {
  if (!selectAllEl.value) return;
  selectAllEl.value.checked = allSelected.value;
  selectAllEl.value.indeterminate = selectedInList.value > 0 && !allSelected.value;
}

function onCompareClick() {
  tryOpenCompare(siteUrl.value, t);
}

function resetState() {
  expanded.value = false;
  loading.value = false;
  error.value = null;
  variables.value = null;
  detailOpen.value = false;
  detailVariable.value = null;
  if (fetchController) {
    fetchController.abort();
    fetchController = null;
  }
}

watch([() => props.row.id, keyword], resetState);

onBeforeUnmount(() => {
  if (fetchController) {
    fetchController.abort();
  }
});

function openVariableDialog(variable) {
  detailVariable.value = variable;
  detailOpen.value = true;
}

async function loadVariables() {
  const idno = props.row.idno;
  if (!idno || !keyword.value) {
    error.value = t('error_invalid_parameters', 'Invalid parameters');
    return;
  }

  if (fetchController) {
    fetchController.abort();
  }
  fetchController = new AbortController();
  const { signal } = fetchController;

  loading.value = true;
  error.value = null;

  const params = new URLSearchParams({
    idno,
    sk: keyword.value,
    ps: String(PAGE_SIZE),
  });
  const url = apiBaseUrl.value + 'variables?' + params.toString();

  try {
    const res = await fetch(url, { credentials: 'same-origin', signal });
    if (!res.ok) throw new Error('HTTP ' + res.status);

    const json = await res.json();
    if (json.status === 'failed') {
      throw new Error(json.message || 'Request failed');
    }

    variables.value = Array.isArray(json.variables) ? json.variables : [];
  } catch (err) {
    if (err.name === 'AbortError') return;
    error.value = err.message || t('error_invalid_parameters');
    variables.value = null;
  } finally {
    loading.value = false;
    fetchController = null;
  }
}

function onToggle() {
  expanded.value = !expanded.value;
  // Load once; collapsing keeps the list so re-opening is instant. Retry after an error.
  if (expanded.value && variables.value === null && !loading.value) {
    loadVariables();
  }
}
</script>

<style scoped>
.study-variable-matches {
  background: var(--study-variable-bg, #f4f6f8);
  border-top: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.1));
  border-radius: 0 0 9px 9px;
  font-size: var(--catalog-font-ui, 0.875rem);
}

/* ---- Collapsed row: the primary affordance ---- */
.study-variable-matches__toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 20px;
  border: 0;
  border-radius: 0 0 9px 9px;
  background: transparent;
  text-align: left;
  cursor: pointer;
  font: inherit;
  line-height: 1.4;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  transition: background-color 0.15s ease;
}

.study-variable-matches--expanded .study-variable-matches__toggle {
  border-radius: 0;
}

.study-variable-matches__toggle:hover {
  background: rgba(21, 101, 192, 0.06);
}

.study-variable-matches__toggle:focus-visible {
  outline: 2px solid var(--catalog-link, #1565c0);
  outline-offset: -2px;
}

.study-variable-matches__icon {
  flex-shrink: 0;
  color: var(--catalog-link, #1565c0);
}

.study-variable-matches__heading {
  flex: 1;
  min-width: 0;
}

.study-variable-matches__count {
  font-weight: 600;
  color: var(--catalog-link, #1565c0);
}

.study-variable-matches__total {
  margin-left: 4px;
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}

.study-variable-matches__action {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
  font-size: var(--catalog-font-small, 0.8125rem);
  font-weight: 500;
  color: var(--catalog-link, #1565c0);
}

.study-variable-matches__chevron {
  transition: transform 0.2s ease;
}

.study-variable-matches--expanded .study-variable-matches__chevron {
  transform: rotate(180deg);
}

/* ---- Expanded panel ---- */
.study-variable-matches__panel {
  margin: 0 16px 14px;
  background: #fff;
  border: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.1));
  border-radius: 8px;
  overflow: hidden;
}

.study-variable-matches__table-wrap {
  max-height: 320px;
  overflow-y: auto;
}

.study-variable-matches__table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.study-variable-matches__table th,
.study-variable-matches__table td {
  padding: 8px 12px;
  text-align: left;
  vertical-align: middle;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}

.study-variable-matches__table th {
  position: sticky;
  top: 0;
  z-index: 1;
  background: #fafbfc;
  font-size: var(--catalog-font-small, 0.75rem);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}

.study-variable-matches__table tbody tr:last-child td {
  border-bottom: 0;
}

.study-variable-matches__row {
  cursor: pointer;
  transition: background-color 0.1s ease;
}

.study-variable-matches__row:hover {
  background: rgba(21, 101, 192, 0.04);
}

.study-variable-matches__row.is-selected {
  background: rgba(21, 101, 192, 0.08);
}

.study-variable-matches__check-col {
  width: 40px;
  padding-right: 0 !important;
}

.study-variable-matches__checkbox {
  width: 16px;
  height: 16px;
  margin: 0;
  vertical-align: middle;
  cursor: pointer;
  accent-color: var(--catalog-link, #1565c0);
}

.study-variable-matches__name-col {
  width: 28%;
}

.study-variable-matches__var-name {
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  border: 0;
  padding: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--catalog-link, #1565c0);
}

.study-variable-matches__var-name:hover {
  text-decoration: underline;
}

.study-variable-matches__label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--catalog-text-primary, rgba(26, 35, 50, 0.92));
}

.study-variable-matches__file-col {
  width: 18%;
}

.study-variable-matches__file {
  display: inline-block;
  max-width: 100%;
  padding: 1px 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: middle;
  border-radius: 10px;
  background: rgba(15, 23, 42, 0.05);
  font-size: var(--catalog-font-small, 0.75rem);
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}

.study-variable-matches :deep(mark) {
  padding: 0 1px;
  border-radius: 2px;
  background: rgba(255, 213, 79, 0.45);
  color: inherit;
}

.study-variable-matches__more,
.study-variable-matches__empty {
  padding: 8px 12px;
  border-top: 1px solid rgba(15, 23, 42, 0.06);
  font-size: var(--catalog-font-small, 0.75rem);
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}

.study-variable-matches__empty {
  border-top: 0;
}

/* ---- Footer: compare action lives with the list ---- */
.study-variable-matches__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: #fafbfc;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
}

.study-variable-matches__compare-summary {
  flex: 1;
  min-width: 0;
  font-size: var(--catalog-font-small, 0.8125rem);
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}

.study-variable-matches__clear {
  border: 0;
  padding: 0;
  background: transparent;
  cursor: pointer;
  font: inherit;
  font-size: var(--catalog-font-small, 0.8125rem);
  color: var(--catalog-link, #1565c0);
}

.study-variable-matches__clear:hover {
  text-decoration: underline;
}

.study-variable-matches__compare-btn {
  text-transform: none;
  letter-spacing: 0;
}

.study-variable-matches__compare-btn:not(.v-btn--disabled),
.study-variable-matches__compare-btn:not(.v-btn--disabled) :deep(.v-btn__content) {
  color: #fff !important;
}

/* ---- Loading skeleton ---- */
.study-variable-matches__skeleton {
  padding: 4px 0;
}

.study-variable-matches__skeleton-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 10px 12px;
}

.sk {
  display: inline-block;
  height: 12px;
  border-radius: 4px;
  background: linear-gradient(90deg, #eef1f4 25%, #f6f8fa 50%, #eef1f4 75%);
  background-size: 200% 100%;
  animation: sk-shimmer 1.2s ease-in-out infinite;
}

.sk--box { width: 16px; height: 16px; }
.sk--name { width: 18%; }
.sk--label { width: 50%; }

@keyframes sk-shimmer {
  from { background-position: 200% 0; }
  to { background-position: -200% 0; }
}

@media (max-width: 600px) {
  .study-variable-matches__toggle {
    padding: 10px 14px;
    flex-wrap: wrap;
  }

  .study-variable-matches__panel {
    margin: 0 8px 10px;
  }

  .study-variable-matches__file-col {
    display: none;
  }

  .study-variable-matches__name-col {
    width: 36%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .sk { animation: none; }
  .study-variable-matches__chevron { transition: none; }
}
</style>
