<template>
  <v-sheet border rounded="lg" class="dt-main-tabs-sheet">
    <v-tabs v-model="tabModel" color="primary" density="comfortable" class="dt-main-tabs px-2">
      <v-tab value="properties" prepend-icon="mdi-tune-variant">Properties</v-tab>
      <v-tab value="add-fields" prepend-icon="mdi-playlist-plus">
        Add fields
        <v-chip
          v-if="unusedCount > 0"
          size="x-small"
          color="primary"
          variant="flat"
          class="ml-2"
        >
          {{ unusedCount }}
        </v-chip>
      </v-tab>
      <v-tab value="json" prepend-icon="mdi-code-json">JSON</v-tab>
    </v-tabs>
    <v-divider />
    <div class="dt-main-tabs-body">
      <div v-show="tabModel === 'properties'" class="dt-main-tab-panel">
        <div class="dt-main-tab-scroll pa-4 pa-md-6">
          <slot name="properties" />
        </div>
      </div>
      <div v-show="tabModel === 'add-fields'" class="dt-main-tab-panel">
        <div class="dt-main-tab-scroll pa-4 pa-md-6">
          <slot name="add-fields" />
        </div>
      </div>
      <div v-show="tabModel === 'json'" class="dt-main-tab-panel">
        <div class="dt-main-tab-scroll pa-4 pa-md-6">
          <slot name="json" />
        </div>
      </div>
    </div>
  </v-sheet>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'DisplayTemplateMainTabs' });

const props = defineProps({
  modelValue: { type: String, default: 'properties' },
  unusedCount: { type: Number, default: 0 },
});

const emit = defineEmits(['update:modelValue']);

const tabModel = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
});
</script>

<style scoped>
.dt-main-tabs-sheet {
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
  overflow: hidden;
  background: var(--dt-panel-bg, #fff) !important;
}
.dt-main-tabs-body {
  flex: 1 1 0;
  min-height: 0;
  overflow: hidden;
}
.dt-main-tab-panel {
  height: 100%;
  min-height: 0;
}
.dt-main-tab-scroll {
  height: 100%;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
</style>
