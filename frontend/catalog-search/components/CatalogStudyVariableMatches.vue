<template>
  <div v-if="visible" class="study-variable-matches" @click.stop>
    <button
      type="button"
      class="study-variable-matches__toggle"
      :aria-expanded="expanded"
      @click="onToggle"
    >
      <span class="study-variable-matches__heading">
        {{ summaryLabel }}
      </span>
      <v-icon
        :icon="expanded ? 'mdi-chevron-down' : 'mdi-chevron-right'"
        size="18"
        class="study-variable-matches__chevron"
      />
    </button>

    <div v-if="expanded" class="study-variable-matches__panel">
      <div v-if="loading" class="study-variable-matches__loading text-caption text-medium-emphasis">
        <v-progress-circular indeterminate size="16" width="2" class="me-2" />
        {{ t('js_loading') }}
      </div>

      <v-alert
        v-else-if="error"
        type="error"
        variant="tonal"
        density="compact"
        class="mb-0"
        :text="error"
      />

      <div
        v-else-if="variables.length"
        class="study-variable-matches__table-wrap"
        :class="{ 'study-variable-matches__table-wrap--scroll': variables.length > 10 }"
      >
        <table class="study-variable-matches__table">
          <thead>
            <tr>
              <th class="study-variable-matches__compare-col">
                <button
                  type="button"
                  class="study-variable-matches__compare-header"
                  :title="t('compare_selected_variables', 'Compare selected variables')"
                  @click.stop="onCompareClick"
                >
                  {{ t('compare') }}
                </button>
              </th>
              <th v-if="hasFileColumn">{{ t('file') }}</th>
              <th>{{ t('name') }}</th>
              <th>{{ t('label') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="variable in variables" :key="variable.uid || variable.vid">
              <td class="study-variable-matches__compare-col">
                <v-checkbox
                  :model-value="isSelected(row.id, variable.vid)"
                  density="compact"
                  hide-details
                  :title="t('mark_for_variable_comparison', 'Mark for variable comparison')"
                  @click.stop
                  @update:model-value="(v) => onCompareToggle(variable.vid, v)"
                />
              </td>
              <td v-if="hasFileColumn">
                <div class="study-variable-matches__file">{{ variable.fid }}</div>
              </td>
              <td>
                <button
                  type="button"
                  class="study-variable-matches__var-link"
                  :title="variable.labl"
                  @click.stop="openVariableDialog(variable)"
                >
                  {{ variable.name }}
                </button>
              </td>
              <td>
                <button
                  type="button"
                  class="study-variable-matches__var-link study-variable-matches__var-link--label"
                  @click.stop="openVariableDialog(variable)"
                >
                  {{ variable.labl }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="study-variable-matches__empty text-caption text-medium-emphasis">
        {{ t('no_records_found') }}
      </div>
    </div>

    <div class="study-variable-matches__footer">
      <v-btn
        size="small"
        variant="outlined"
        color="primary"
        class="study-variable-matches__compare-btn"
        @click.stop="onCompareClick"
      >
        {{ t('title_compare_variables') }}
      </v-btn>
      <span class="study-variable-matches__compare-summary text-caption text-medium-emphasis">
        {{ compareSummary }}
      </span>
    </div>

    <CatalogVariableDetailDialog
      v-model="detailOpen"
      :study-id="row.id"
      :variable="detailVariable"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useVariableCompareCart } from '../composables/useVariableCompareCart';
import CatalogVariableDetailDialog from './CatalogVariableDetailDialog.vue';

defineOptions({ name: 'CatalogStudyVariableMatches' });

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
  summaryText,
  count,
} = useVariableCompareCart();

const expanded = ref(false);
const loading = ref(false);
const error = ref(null);
const variables = ref(null);
const detailOpen = ref(false);
const detailVariable = ref(null);

let fetchController = null;

const keyword = computed(() => (props.searchKeyword || '').trim());

const visible = computed(() =>
  props.row?.var_found != null
  && props.row.var_found !== ''
  && Number(props.row.var_found) > 0
  && keyword.value !== ''
  && (props.row.idno || props.row.id)
);

const summaryLabel = computed(() =>
  t(
    'variables_keywords_found',
    'Keyword(s) found in %d variable(s) out of %d',
    props.row.var_found,
    props.row.varcount ?? 'N'
  )
);

const hasFileColumn = computed(() =>
  Array.isArray(variables.value)
  && variables.value.some((v) => v.fid != null && v.fid !== '')
);

const compareSummary = computed(() => {
  if (!count.value) {
    return t('To compare, select two or more variables');
  }
  return summaryText(t);
});

function onCompareToggle(vid, checked) {
  toggleSelection(props.row.id, vid, checked, t);
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
    ps: '50',
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
    variables.value = [];
  } finally {
    loading.value = false;
    fetchController = null;
  }
}

function onToggle() {
  if (expanded.value && variables.value !== null) {
    expanded.value = false;
    variables.value = null;
    error.value = null;
    return;
  }

  expanded.value = true;
  if (variables.value === null) {
    loadVariables();
  }
}
</script>

<style scoped>
.study-variable-matches {
  margin-top: 0;
  padding: 10px 16px 12px;
  background: var(--study-variable-bg, #f4f6f8);
  border-top: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.1));
  border-radius: 0 0 9px 9px;
}

.study-variable-matches__toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  padding: 6px 8px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  text-align: left;
  cursor: pointer;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  font-size: 0.875rem;
  line-height: 1.4;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.study-variable-matches__toggle:hover {
  color: #1565c0;
  background: rgba(255, 255, 255, 0.55);
}

.study-variable-matches__heading {
  flex: 1;
  min-width: 0;
}

.study-variable-matches__chevron {
  flex-shrink: 0;
  opacity: 0.6;
}

.study-variable-matches__panel {
  margin-top: 8px;
  padding: 0 8px 4px;
}

.study-variable-matches__loading {
  display: flex;
  align-items: center;
  padding: 4px 0 8px;
}

.study-variable-matches__table-wrap {
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 6px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.72);
}

.study-variable-matches__table-wrap--scroll {
  max-height: 280px;
  overflow-y: auto;
}

.study-variable-matches__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.study-variable-matches__table th,
.study-variable-matches__table td {
  padding: 6px 10px;
  text-align: left;
  vertical-align: top;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}

.study-variable-matches__table th {
  font-weight: 600;
  color: rgba(26, 35, 50, 0.55);
  background: #fff;
  position: sticky;
  top: 0;
  z-index: 1;
}

.study-variable-matches__table tbody tr:last-child td {
  border-bottom: 0;
}

.study-variable-matches__table tbody tr:hover {
  background: rgba(25, 118, 210, 0.05);
}

.study-variable-matches__var-link {
  border: 0;
  padding: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
  color: #1565c0;
  font-weight: 500;
  font-size: inherit;
  font-family: inherit;
  line-height: inherit;
}

.study-variable-matches__var-link:hover {
  text-decoration: underline;
}

.study-variable-matches__var-link--label {
  color: inherit;
  font-weight: 400;
}

.study-variable-matches__var-link--label:hover {
  color: #1565c0;
}

.study-variable-matches__file {
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.study-variable-matches__empty {
  padding: 4px 0 8px;
}

.study-variable-matches__compare-col {
  width: 36px;
  padding-left: 8px !important;
  padding-right: 4px !important;
  vertical-align: middle;
}

.study-variable-matches__compare-header {
  border: 0;
  padding: 0;
  background: transparent;
  font-size: 0.75rem;
  font-weight: 600;
  color: #1565c0;
  cursor: pointer;
  text-transform: capitalize;
}

.study-variable-matches__compare-header:hover {
  text-decoration: underline;
}

.study-variable-matches__compare-col :deep(.v-selection-control) {
  min-height: 0;
}

.study-variable-matches__compare-col :deep(.v-checkbox .v-selection-control__input) {
  width: 18px;
  height: 18px;
}

.study-variable-matches__footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
  padding: 0 8px 2px;
}

.study-variable-matches__compare-btn {
  text-transform: none;
  letter-spacing: 0;
}

.study-variable-matches__compare-summary {
  line-height: 1.35;
}
</style>
