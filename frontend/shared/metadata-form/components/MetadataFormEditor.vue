<script setup>
import { computed, toRaw, toRef, watch } from 'vue';
import { isEqual } from 'lodash';
import { provideMetadataFormStore } from '../composables/useMetadataFormStore';
import { createMetadataFormStore } from '../composables/createMetadataFormStore';
import { createMetadataNav, provideMetadataNav } from '../composables/useMetadataNav';
import { provideMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { createMetadataFormUi, provideMetadataFormUi } from '../composables/useMetadataFormUi';
import MetadataFormTree from './MetadataFormTree.vue';
import MetadataActiveSection from './MetadataActiveSection.vue';
import MetadataStackedForm from './MetadataStackedForm.vue';

const props = defineProps({
  /** Root template object: { type, title, items } or { template: { items } } */
  formTemplate: { type: Object, required: true },
  /** Initial metadata object */
  modelValue: { type: Object, default: () => ({}) },
  showTree: { type: Boolean, default: true },
  /** tree = catalog (default); stacked = full-width sections, no tree */
  layout: { type: String, default: 'tree' },
  labels: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const isStacked = computed(() => props.layout === 'stacked' || props.layout === 'panels');

const store = createMetadataFormStore(props.modelValue || {});
provideMetadataFormStore(store);
const labels = provideMetadataFormLabels(toRef(props, 'labels'));
provideMetadataFormUi(createMetadataFormUi());

const rootItems = computed(() => {
  const t = props.formTemplate || {};
  if (Array.isArray(t.items)) return t.items;
  if (t.template && Array.isArray(t.template.items)) return t.template.items;
  return [];
});

const nav = createMetadataNav(rootItems);
provideMetadataNav(nav);

watch(
  rootItems,
  () => {
    if (!nav.activeNode.value) {
      nav.selectInitial();
    }
  },
  { immediate: true }
);

let syncing = false;

watch(
  () => props.modelValue,
  (v) => {
    if (syncing) return;
    const incoming = v && typeof v === 'object' ? v : {};
    if (!isEqual(toRaw(incoming), toRaw(store.state.data))) {
      syncing = true;
      store.replaceData(incoming);
      syncing = false;
    }
  }
);

watch(
  () => store.state.data,
  () => {
    if (syncing) return;
    const payload = store.getPayload();
    if (!isEqual(payload, toRaw(props.modelValue || {}))) {
      syncing = true;
      emit('update:modelValue', payload);
      syncing = false;
    }
  },
  { deep: true }
);

const activeNode = computed(() => nav.activeNode.value);

defineExpose({
  getPayload: () => store.getPayload(),
  replaceData: (obj) => store.replaceData(obj),
  store,
  nav,
});
</script>

<template>
  <div class="mf-editor" :class="{ 'mf-editor--stacked': isStacked }">
    <MetadataStackedForm v-if="isStacked" :items="rootItems" />
    <div v-else class="mf-editor-layout" :class="{ 'mf-editor-layout--no-tree': !showTree }">
      <aside v-if="showTree" class="mf-editor-tree">
        <MetadataFormTree :items="rootItems" />
      </aside>
      <main id="mf-main-content" class="mf-editor-main">
        <MetadataActiveSection v-if="activeNode" :node="activeNode" />
        <div v-else class="text-medium-emphasis pa-4">
          {{ labels.selectSection }}
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.mf-editor {
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
}
.mf-editor--stacked {
  height: auto;
}
.mf-editor-layout {
  display: grid;
  grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
  gap: 0;
  align-items: stretch;
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  border-radius: 8px;
  overflow: hidden;
  background: rgba(var(--v-theme-on-surface), 0.02);
}
.mf-editor-layout--no-tree {
  grid-template-columns: 1fr;
  background: rgb(var(--v-theme-surface));
}
.mf-editor-tree {
  border-right: 1px solid rgba(var(--v-theme-on-surface), 0.1);
  background: transparent;
  padding: 12px 8px;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.mf-editor-tree :deep(.mf-tree) {
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
}
.mf-editor-main {
  min-width: 0;
  min-height: 0;
  max-height: 100%;
  overflow: auto;
  overscroll-behavior: contain;
  padding: 16px 18px 48px;
  background: rgb(var(--v-theme-surface));
}
@media (max-width: 960px) {
  .mf-editor-layout {
    grid-template-columns: 1fr;
    grid-template-rows: minmax(160px, 220px) minmax(0, 1fr);
  }
  .mf-editor-tree {
    border-right: 0;
    border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.1);
    max-height: 220px;
  }
}
</style>
