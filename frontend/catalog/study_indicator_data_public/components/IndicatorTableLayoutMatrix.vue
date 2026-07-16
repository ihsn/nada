<template>
  <div class="layout-matrix">
    <table class="layout-matrix__table">
      <thead>
        <tr>
          <th class="layout-matrix__th layout-matrix__th--drag" scope="col" />
          <th class="layout-matrix__th layout-matrix__th--order" scope="col" />
          <th class="layout-matrix__th layout-matrix__th--dim" scope="col">Dimension</th>
          <th class="layout-matrix__th layout-matrix__th--axis text-center" scope="col">Rows</th>
          <th class="layout-matrix__th layout-matrix__th--axis text-center" scope="col">Columns</th>
        </tr>
      </thead>
      <tbody ref="sortableBodyRef">
        <tr
          v-for="(key, idx) in dimensionOrder"
          :key="key"
          class="layout-matrix__row"
          :data-key="key"
        >
          <td class="layout-matrix__td layout-matrix__td--drag">
            <v-icon
              class="layout-matrix__drag-handle text-medium-emphasis"
              size="20"
              aria-hidden="true"
            >
              mdi-drag-vertical
            </v-icon>
          </td>
          <td class="layout-matrix__td layout-matrix__td--order">
            <div class="layout-matrix__order-controls">
              <v-btn
                icon
                variant="text"
                size="small"
                density="comfortable"
                :disabled="idx === 0"
                aria-label="Move up"
                @click="$emit('move', { key, delta: -1 })"
              >
                <v-icon size="20">mdi-chevron-up</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="small"
                density="comfortable"
                :disabled="idx === dimensionOrder.length - 1"
                aria-label="Move down"
                @click="$emit('move', { key, delta: 1 })"
              >
                <v-icon size="20">mdi-chevron-down</v-icon>
              </v-btn>
            </div>
          </td>
          <td class="layout-matrix__td layout-matrix__td--dim">
            <div class="layout-matrix__dim-label">
              <span>{{ dimensionLabel(key) }}</span>
            </div>
            <div
              v-if="isTimePeriodKey(key) && axisOf(key)"
              class="layout-matrix__time-order mt-1"
            >
              <v-btn-toggle
                :model-value="timeOrder"
                mandatory
                density="compact"
                variant="outlined"
                divided
                @update:model-value="$emit('time-order', $event)"
              >
                <v-btn value="asc" size="x-small">Asc</v-btn>
                <v-btn value="desc" size="x-small">Desc</v-btn>
              </v-btn-toggle>
            </div>
          </td>
          <td colspan="2" class="layout-matrix__td layout-matrix__td--axis">
            <v-radio-group
              :model-value="axisOf(key)"
              mandatory
              density="compact"
              hide-details
              class="layout-matrix__axis-group"
              @update:model-value="(v) => onAxisChange(key, v)"
            >
              <div class="layout-matrix__axis-grid">
                <div class="layout-matrix__axis-cell">
                  <v-radio value="row" />
                </div>
                <div class="layout-matrix__axis-cell">
                  <v-radio value="column" />
                </div>
              </div>
            </v-radio-group>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import Sortable from 'sortablejs';
import { isTimePeriodKey } from '@/shared/timeseries/tableLayoutMatrix.js';

defineOptions({ name: 'IndicatorTableLayoutMatrix' });

const props = defineProps({
  dimensionOrder: {
    type: Array,
    default: () => [],
  },
  rows: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    default: () => [],
  },
  dimensionLabel: {
    type: Function,
    required: true,
  },
  timeOrder: {
    type: String,
    default: 'asc',
  },
});

const emit = defineEmits(['set-axis', 'move', 'time-order', 'reorder']);

const sortableBodyRef = ref(null);
let sortableInstance = null;

function axisOf(key) {
  if ((props.rows || []).includes(key)) return 'row';
  if ((props.columns || []).includes(key)) return 'column';
  return null;
}

function onAxisChange(key, value) {
  if (value !== 'row' && value !== 'column') return;
  emit('set-axis', { key, axis: value });
}

function destroySortable() {
  if (sortableInstance) {
    sortableInstance.destroy();
    sortableInstance = null;
  }
}

function initSortable() {
  destroySortable();
  const el = sortableBodyRef.value;
  if (!el) return;

  sortableInstance = Sortable.create(el, {
    handle: '.layout-matrix__drag-handle',
    animation: 150,
    ghostClass: 'layout-matrix__row--ghost',
    chosenClass: 'layout-matrix__row--chosen',
    draggable: '.layout-matrix__row',
    onEnd(evt) {
      if (evt.oldIndex == null || evt.newIndex == null || evt.oldIndex === evt.newIndex) return;
      const visible = [...props.dimensionOrder];
      const moved = visible.splice(evt.oldIndex, 1)[0];
      visible.splice(evt.newIndex, 0, moved);
      emit('reorder', visible);
    },
  });
}

onMounted(() => {
  nextTick(initSortable);
});

onBeforeUnmount(destroySortable);

watch(
  () => props.dimensionOrder.length,
  () => {
    nextTick(initSortable);
  }
);
</script>

<style scoped>
.layout-matrix__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.layout-matrix__th {
  text-align: left;
  font-weight: 600;
  font-size: 0.6875rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
  padding: 0.35rem 0.5rem;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.12);
}

.layout-matrix__th--drag {
  width: 2rem;
}

.layout-matrix__th--order {
  width: 4.5rem;
}

.layout-matrix__th--axis {
  width: 4rem;
}

.layout-matrix__td {
  padding: 0.5rem;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  vertical-align: middle;
}

.layout-matrix__axis-group {
  margin: 0;
}

.layout-matrix__axis-group :deep(.v-selection-control-group) {
  width: 100%;
}

.layout-matrix__axis-grid {
  display: grid;
  grid-template-columns: 4rem 4rem;
  width: 100%;
  max-width: 8rem;
  margin-inline: auto;
}

.layout-matrix__axis-cell {
  display: flex;
  align-items: center;
  justify-content: center;
}

.layout-matrix__td--axis {
  padding: 0.25rem 0.5rem;
  vertical-align: middle;
}

.layout-matrix__td--dim {
  vertical-align: top;
}

.layout-matrix__td--drag {
  width: 2rem;
  text-align: center;
}

.layout-matrix__drag-handle {
  cursor: grab;
  opacity: 0.45;
}

.layout-matrix__drag-handle:active {
  cursor: grabbing;
}

.layout-matrix__order-controls {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 0.15rem;
}

.layout-matrix__dim-label {
  font-weight: 500;
}

.layout-matrix__time-order :deep(.v-btn) {
  min-width: 2.5rem;
  font-size: 0.6875rem;
}

.layout-matrix__row--ghost {
  opacity: 0.45;
  background: rgba(var(--v-theme-primary), 0.08);
}

.layout-matrix__row--chosen {
  background: rgba(var(--v-theme-on-surface), 0.04);
}
</style>
