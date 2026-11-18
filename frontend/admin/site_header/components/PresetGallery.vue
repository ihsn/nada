<template>
  <div>
    <div class="sh-presets">
      <button
        v-for="p in server.presets"
        :key="p.id"
        type="button"
        class="sh-preset"
        :class="{ 'sh-preset--active': form.preset === p.id }"
        :aria-pressed="form.preset === p.id"
        :title="p.description"
        @click="choose(p)"
      >
        <span class="sh-preset__thumb">
          <sh-preset-thumb :rows="p.settings.rows" :scope="p.settings.background_scope" :header-style="p.settings.header_style || {}" />
        </span>
        <span class="sh-preset__title">
          {{ p.title }}
          <v-icon v-if="form.preset === p.id" icon="mdi-check-circle" size="14" color="primary" />
        </span>
      </button>
    </div>

    <v-dialog v-model="confirm.show" max-width="440">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sh_layout_replace_title', 'Use this layout?') }}</v-card-title>
        <v-card-text>{{ t('sh_layout_replace_text', 'Your changes to the rows (items, row colours and sizes) are replaced by this layout. Discard draft undoes it until you publish.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="confirm.show = false">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" variant="flat" @click="apply(confirm.preset)">{{ t('sh_use_layout', 'Use layout') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { useSiteHeaderStore } from '../composables/useSiteHeaderStore';
import ShPresetThumb from './PresetThumb.vue';

defineOptions({ name: 'ShPresetGallery' });

const { server, form, applyPreset, rowsChangedFromPreset, t } = useSiteHeaderStore();
const confirm = reactive({ show: false, preset: null });

function choose(p) {
  if (p.id === form.value.preset && !rowsChangedFromPreset()) return;
  if (rowsChangedFromPreset()) {
    confirm.preset = p;
    confirm.show = true;
    return;
  }
  applyPreset(p);
}

function apply(p) {
  confirm.show = false;
  applyPreset(p);
}
</script>

<style scoped>
.sh-presets {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  align-content: start;
}
.sh-preset {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 6px;
  text-align: left;
  border: 1px solid rgba(var(--v-border-color), 0.25);
  border-radius: 8px;
  background: rgb(var(--v-theme-surface));
  color: inherit;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.sh-preset:hover {
  border-color: rgba(var(--v-theme-primary), 0.6);
}
.sh-preset:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}
.sh-preset--active {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
}
.sh-preset__thumb {
  display: block;
  height: 56px;
  border-radius: 4px;
  overflow: hidden;
  background: rgba(var(--v-theme-on-surface), 0.04);
  color: rgb(var(--v-theme-primary));
}
.sh-preset__title {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8125rem;
  font-weight: 500;
}
</style>
