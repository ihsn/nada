<script setup>
/**
 * Field label with ME-style collapsible help (question icon, collapsed by default).
 */
import { computed, ref } from 'vue';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { useMetadataFormUi } from '../composables/useMetadataFormUi';

defineProps({
  label: { type: String, default: '' },
  helpText: { type: String, default: '' },
  required: { type: Boolean, default: false },
  /** Optional denser label style for compact array headers */
  dense: { type: Boolean, default: false },
});

const localOpen = ref(false);
const labels = useMetadataFormLabels();
const ui = useMetadataFormUi();
const open = computed(() => !!(ui?.showAllHelp?.value || localOpen.value));

function toggle() {
  if (ui?.showAllHelp?.value) return;
  localOpen.value = !localOpen.value;
}
</script>

<template>
  <div class="mf-field-help">
    <div class="mf-field-help-row" :class="{ 'mf-field-help-row--dense': dense }">
      <span class="mf-field-help-label" :class="dense ? 'text-body-2 font-weight-medium' : 'text-body-2 font-weight-medium'">
        {{ label }}
        <span v-if="required" class="text-error">*</span>
      </span>
      <button
        v-if="helpText"
        type="button"
        class="mf-help-btn"
        :class="{ 'mf-help-btn--open': open }"
        :aria-expanded="open"
        :aria-label="open ? labels.hideHelp : labels.showHelp"
        :title="open ? labels.hideHelp : labels.showHelp"
        @click="toggle"
      >
        <v-icon size="16">{{ open ? 'mdi-help-circle' : 'mdi-help-circle-outline' }}</v-icon>
      </button>
    </div>
    <div
      v-if="helpText && open"
      class="mf-help-text text-caption text-medium-emphasis"
      v-html="helpText"
    />
  </div>
</template>

<style scoped>
.mf-field-help {
  margin-bottom: 6px;
}
.mf-field-help-row {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 4px;
  min-width: 0;
}
.mf-field-help-label {
  color: rgba(var(--v-theme-on-surface), 0.87);
  min-width: 0;
}
.mf-help-btn {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  padding: 2px;
  margin: 0;
  cursor: pointer;
  color: rgba(var(--v-theme-on-surface), 0.45);
  border-radius: 50%;
  line-height: 1;
}
.mf-help-btn:hover {
  color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.08);
}
.mf-help-btn--open {
  color: rgb(var(--v-theme-primary));
}
.mf-help-text {
  margin-top: 4px;
  margin-bottom: 6px;
  line-height: 1.45;
}
.mf-help-text :deep(p) {
  margin: 0 0 0.5em;
}
.mf-help-text :deep(p:last-child) {
  margin-bottom: 0;
}
.mf-help-text :deep(ul),
.mf-help-text :deep(ol) {
  margin: 0.25em 0 0.5em;
  padding-left: 1.25em;
}
</style>
