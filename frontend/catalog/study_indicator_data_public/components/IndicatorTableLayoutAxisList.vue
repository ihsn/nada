<template>
  <div class="layout-axis-list">
    <div class="d-flex align-center gap-2 mb-2">
      <div>
        <div class="text-subtitle-2 font-weight-semibold">
          {{ title }}
        </div>
        <div v-if="subtitle" class="text-caption text-medium-emphasis">{{ subtitle }}</div>
      </div>
    </div>

    <v-select
      v-if="available.length"
      :items="availableSelectItems"
      item-title="title"
      item-value="value"
      density="compact"
      variant="outlined"
      hide-details
      placeholder="Add dimension…"
      class="mb-2"
      :model-value="null"
      @update:model-value="onAdd"
    />

    <div v-if="showTimeOrder" class="mb-2">
      <div class="text-caption text-medium-emphasis mb-1">Time period order</div>
      <v-btn-toggle
        :model-value="timeOrder"
        mandatory
        density="compact"
        variant="outlined"
        divided
        @update:model-value="$emit('time-order', $event)"
      >
        <v-btn value="asc" size="small">Ascending</v-btn>
        <v-btn value="desc" size="small">Descending</v-btn>
      </v-btn-toggle>
    </div>

    <v-list v-if="items.length" density="compact" class="layout-axis-list__items py-0 bg-transparent">
      <v-list-item
        v-for="(key, idx) in items"
        :key="key"
        class="layout-axis-list__item px-2"
        rounded="lg"
        border
      >
        <v-list-item-title class="text-body-2">{{ dimensionLabel(key) }}</v-list-item-title>
        <template #append>
          <v-btn
            icon
            variant="text"
            size="x-small"
            :disabled="idx === 0"
            aria-label="Move up"
            @click="$emit('move-up', key)"
          >
            <v-icon size="16">mdi-chevron-up</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            size="x-small"
            :disabled="idx === items.length - 1"
            aria-label="Move down"
            @click="$emit('move-down', key)"
          >
            <v-icon size="16">mdi-chevron-down</v-icon>
          </v-btn>
          <v-btn
            icon
            variant="text"
            size="x-small"
            :disabled="!canRemove(key)"
            aria-label="Remove"
            @click="$emit('remove', key)"
          >
            <v-icon size="16">mdi-close</v-icon>
          </v-btn>
        </template>
      </v-list-item>
    </v-list>
    <p v-else class="text-caption text-medium-emphasis mb-0">No dimensions assigned.</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'IndicatorTableLayoutAxisList' });

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  items: { type: Array, default: () => [] },
  available: { type: Array, default: () => [] },
  dimensionLabel: { type: Function, required: true },
  showTimeOrder: { type: Boolean, default: false },
  timeOrder: { type: String, default: 'asc' },
  canRemove: { type: Function, default: () => () => true },
});

const emit = defineEmits(['add', 'remove', 'move-up', 'move-down', 'time-order']);

const availableSelectItems = computed(() =>
  props.available.map((key) => ({
    value: key,
    title: props.dimensionLabel(key),
  }))
);

function onAdd(val) {
  if (val) emit('add', val);
}
</script>

<style scoped>
.layout-axis-list__item {
  margin-bottom: 0.35rem;
  background: rgba(var(--v-theme-on-surface), 0.03);
}
</style>
