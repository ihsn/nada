<template>
  <v-expansion-panels
    v-model="panelOpen"
    variant="accordion"
    class="table-layout-expansion"
  >
    <v-expansion-panel value="layout" rounded="lg" elevation="0">
      <v-expansion-panel-title density="compact" class="table-layout-expansion__title">
        <span class="table-layout-expansion__title-text">Table layout</span>
      </v-expansion-panel-title>

      <v-expansion-panel-text class="table-layout-expansion__body">
        <LayoutMatrix
          :dimension-order="matrixDimensionOrder"
          :rows="draft.rows"
          :columns="draft.columns"
          :dimension-label="resolveDimensionLabel"
          :time-order="draft.time_order"
          @set-axis="onSetAxis"
          @move="onMoveKey"
          @reorder="onReorderVisible"
          @time-order="setTimeOrder"
        />

        <div class="d-flex flex-wrap align-center gap-2 mt-3">
          <v-checkbox
            v-model="draft.flatten_labels"
            density="compact"
            hide-details
            class="flex-grow-1"
            label="Use flat labels (no grouped headers)"
            @update:model-value="onFlattenChange"
          />
          <v-btn
            variant="text"
            size="small"
            class="text-none table-layout-panel__secondary-btn"
            :disabled="!hasAxisAssignment"
            @click="onSwapAxes"
          >
            Swap rows ↔ columns
          </v-btn>
          <v-btn
            variant="text"
            size="small"
            class="text-none table-layout-panel__secondary-btn"
            @click="onResetToDefault"
          >
            Reset to default
          </v-btn>
        </div>

        <v-alert
          v-if="validationMessage"
          type="warning"
          variant="outlined"
          density="compact"
          class="mt-3 mb-0 table-layout-panel__validation-alert text-body-2"
        >
          {{ validationMessage }}
        </v-alert>

        <v-divider class="mt-4 mb-3" />

        <div class="d-flex flex-wrap align-center justify-end gap-2 table-layout-panel__actions">
          <v-btn
            variant="outlined"
            size="small"
            rounded="lg"
            class="text-none"
            :disabled="!isDirty"
            @click="onCancel"
          >
            Cancel
          </v-btn>
          <v-btn
            color="white"
            variant="flat"
            size="small"
            rounded="lg"
            class="text-none table-layout-panel__apply-btn"
            prepend-icon="mdi-check"
            :disabled="!canApply"
            @click="onApply"
          >
            Apply layout
          </v-btn>
        </div>
      </v-expansion-panel-text>
    </v-expansion-panel>
  </v-expansion-panels>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import {
  TIME_PERIOD_KEY,
  createEmptyTableLayout,
  normalizeTableLayout,
  validateTableLayout,
} from '@/shared/timeseries/indicatorSchemaUtils.js';
import {
  axesOrderedByDimensionOrder,
  applyVisibleOrderToDimensionOrder,
  defaultDimensionOrder,
  defaultHiddenForStudySingletons,
  matrixVisibleDimensionOrder,
  moveKeyInOrder,
  swapLayoutAxes,
} from '@/shared/timeseries/tableLayoutMatrix.js';
import LayoutMatrix from './IndicatorTableLayoutMatrix.vue';

defineOptions({ name: 'IndicatorTableLayoutPanel' });

const props = defineProps({
  eligibleDimensions: {
    type: Array,
    default: () => [],
  },
  studySingletonKeys: {
    type: Array,
    default: () => [],
  },
  buildDefaultLayout: {
    type: Function,
    default: null,
  },
  appliedLayout: {
    type: Object,
    default: null,
  },
  dimensionLabelFn: {
    type: Function,
    default: null,
  },
});

const emit = defineEmits(['apply', 'cancel', 'dirty']);

const draft = reactive(createEmptyTableLayout());
const panelOpen = ref([]);

const dimensionByKey = computed(() => {
  const map = new Map();
  for (const d of props.eligibleDimensions) {
    map.set(d.key, d);
  }
  return map;
});

const eligibleKeys = computed(() => (props.eligibleDimensions || []).map((d) => d.key).filter(Boolean));

const studySingletonKeys = computed(() => new Set(props.studySingletonKeys || []));

const matrixDimensionOrder = computed(() =>
  matrixVisibleDimensionOrder(draft.dimension_order, studySingletonKeys.value)
);

const hasAxisAssignment = computed(() => draft.rows.length > 0 || draft.columns.length > 0);

function resolveDimensionLabel(key) {
  const d = dimensionByKey.value.get(key);
  if (d?.component && props.dimensionLabelFn) {
    return props.dimensionLabelFn(d.component);
  }
  if (d?.component) return d.component.name || key;
  if (key === TIME_PERIOD_KEY) return 'Time period';
  return key;
}

function syncAxesFromOrder() {
  const ordered = axesOrderedByDimensionOrder(draft.dimension_order, draft.rows, draft.columns);
  draft.rows.splice(0, draft.rows.length, ...ordered.rows);
  draft.columns.splice(0, draft.columns.length, ...ordered.columns);
}

function ensureHiddenSingletons() {
  const hidden = defaultHiddenForStudySingletons(studySingletonKeys.value, draft.hidden_dimensions);
  draft.hidden_dimensions.splice(0, draft.hidden_dimensions.length, ...hidden);
  draft.rows.splice(0, draft.rows.length, ...draft.rows.filter((k) => !studySingletonKeys.value.has(k)));
  draft.columns.splice(
    0,
    draft.columns.length,
    ...draft.columns.filter((k) => !studySingletonKeys.value.has(k))
  );
}

function ensureDimensionOrder() {
  const keys = eligibleKeys.value;
  const current = [...draft.dimension_order];
  const merged = current.filter((k) => keys.includes(k));
  for (const k of keys) {
    if (!merged.includes(k)) merged.push(k);
  }
  draft.dimension_order.splice(0, draft.dimension_order.length, ...merged);
}

function onSetAxis({ key, axis }) {
  if (!key || studySingletonKeys.value.has(key)) return;
  if (axis !== 'row' && axis !== 'column') return;
  const nextRows = draft.rows.filter((k) => k !== key);
  const nextCols = draft.columns.filter((k) => k !== key);
  if (axis === 'row') nextRows.push(key);
  if (axis === 'column') nextCols.push(key);
  draft.rows.splice(0, draft.rows.length, ...nextRows);
  draft.columns.splice(0, draft.columns.length, ...nextCols);
  syncAxesFromOrder();
  emitDirty();
}

function onReorderVisible(nextVisibleOrder) {
  const merged = applyVisibleOrderToDimensionOrder(
    draft.dimension_order,
    studySingletonKeys.value,
    nextVisibleOrder
  );
  draft.dimension_order.splice(0, draft.dimension_order.length, ...merged);
  syncAxesFromOrder();
  emitDirty();
}

function onMoveKey({ key, delta }) {
  const next = moveKeyInOrder(draft.dimension_order, key, delta);
  draft.dimension_order.splice(0, draft.dimension_order.length, ...next);
  syncAxesFromOrder();
  emitDirty();
}

function onSwapAxes() {
  const swapped = swapLayoutAxes(draft.rows, draft.columns);
  draft.rows.splice(0, draft.rows.length, ...swapped.rows);
  draft.columns.splice(0, draft.columns.length, ...swapped.columns);
  syncAxesFromOrder();
  emitDirty();
}

function setTimeOrder(order) {
  draft.time_order = order === 'desc' ? 'desc' : 'asc';
  emitDirty();
}

function onFlattenChange(val) {
  draft.flatten_labels = !!val;
  draft.group_headers = !draft.flatten_labels;
  emitDirty();
}

const validation = computed(() => validateTableLayout(draft));
const validationMessage = computed(() => (validation.value.valid ? '' : validation.value.message || ''));
const canApply = computed(() => validation.value.valid && isDirty.value);

function layoutSnapshot(layout) {
  return JSON.stringify(normalizeTableLayout(layout));
}

const serializedApplied = computed(() => layoutSnapshot(props.appliedLayout));
const isDirty = computed(() => layoutSnapshot(draft) !== serializedApplied.value);

function emitDirty() {
  emit('dirty', isDirty.value);
}

function cloneLayout(layout) {
  return normalizeTableLayout(layout);
}

function applyDraftFromLayout(layout) {
  const next = cloneLayout(layout);
  draft.rows.splice(0, draft.rows.length, ...next.rows);
  draft.columns.splice(0, draft.columns.length, ...next.columns);
  draft.group_headers = next.group_headers;
  draft.flatten_labels = next.flatten_labels;
  draft.time_order = next.time_order;
  draft.hidden_dimensions.splice(0, draft.hidden_dimensions.length, ...next.hidden_dimensions);
  const order =
    next.dimension_order?.length > 0
      ? [...next.dimension_order]
      : defaultDimensionOrder(props.eligibleDimensions);
  draft.dimension_order.splice(0, draft.dimension_order.length, ...order);
  ensureDimensionOrder();
  ensureHiddenSingletons();
  syncAxesFromOrder();
}

function resetDraftFromApplied() {
  applyDraftFromLayout(props.appliedLayout);
}

function onResetToDefault() {
  if (typeof props.buildDefaultLayout !== 'function') return;
  applyDraftFromLayout(props.buildDefaultLayout());
  emitDirty();
}

watch(
  () => props.eligibleDimensions,
  () => {
    ensureDimensionOrder();
    ensureHiddenSingletons();
    syncAxesFromOrder();
    emitDirty();
  },
  { deep: true }
);

function onApply() {
  if (!canApply.value) return;
  emit('apply', cloneLayout(draft));
}

function onCancel() {
  resetDraftFromApplied();
  emit('cancel');
  emitDirty();
}

watch(
  () => props.appliedLayout,
  () => {
    resetDraftFromApplied();
    emitDirty();
  },
  { immediate: true, deep: true }
);

const isPanelOpen = computed(() => Array.isArray(panelOpen.value) && panelOpen.value.includes('layout'));

function openPanel() {
  panelOpen.value = ['layout'];
}

function closePanel() {
  panelOpen.value = [];
}

function togglePanel() {
  if (isPanelOpen.value) closePanel();
  else openPanel();
}

defineExpose({
  isDirty,
  canApply,
  isPanelOpen,
  apply: onApply,
  cancel: onCancel,
  openPanel,
  closePanel,
  togglePanel,
});
</script>

<style scoped>
.table-layout-expansion {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 8px;
  overflow: hidden;
}

.table-layout-expansion :deep(.v-expansion-panel) {
  background: rgb(var(--v-theme-surface));
}

.table-layout-expansion :deep(.v-expansion-panel-title) {
  min-height: 36px;
  padding-block: 0.35rem;
  padding-inline: 0.75rem;
}

.table-layout-expansion__title-text {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.table-layout-expansion__title {
  font-size: 0.8125rem;
}

.table-layout-expansion__body :deep(.v-expansion-panel-text__wrapper) {
  padding: 0.75rem 1rem 1rem;
}

.table-layout-panel__apply-btn.v-btn--variant-flat {
  color: rgba(var(--v-theme-on-surface), 0.87) !important;
}

.table-layout-panel__apply-btn.v-btn--variant-flat :deep(.v-icon) {
  color: rgba(var(--v-theme-on-surface), 0.87) !important;
}

.table-layout-panel__apply-btn.v-btn--disabled {
  opacity: 0.55;
}

.table-layout-panel__secondary-btn {
  color: rgba(var(--v-theme-on-surface), 0.75);
}

.table-layout-panel__actions {
  padding-top: 0.15rem;
}

.table-layout-panel__validation-alert {
  color: rgba(var(--v-theme-on-surface), 0.9);
  background: rgba(var(--v-theme-warning), 0.1);
  border-color: rgba(var(--v-theme-warning), 0.45) !important;
}

.table-layout-panel__validation-alert :deep(.v-alert__content) {
  color: rgba(var(--v-theme-on-surface), 0.9);
}

.table-layout-panel__validation-alert :deep(.v-alert__prepend .v-icon) {
  color: rgb(var(--v-theme-warning));
}
</style>
