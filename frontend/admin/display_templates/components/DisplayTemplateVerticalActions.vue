<template>
  <div class="dt-v-actions d-flex flex-column align-center py-2 ga-1 border-s flex-shrink-0 align-self-stretch">
    <template v-for="a in visibleActions" :key="a.id">
      <v-divider v-if="a.divider" class="dt-v-actions__divider my-1" />
      <v-tooltip v-else :text="a.title" location="left">
        <template #activator="{ props: tip }">
          <span v-bind="tip" class="d-inline-flex">
            <v-btn
              :icon="a.icon"
              size="small"
              variant="text"
              rounded="sm"
              :color="a.disabled ? undefined : a.danger ? 'error' : a.custom || a.widget ? undefined : a.highlight ? 'secondary' : 'primary'"
              :disabled="a.disabled"
              :class="[
                'dt-v-actions__btn',
                a.disabled ? 'dt-v-actions__btn--disabled' : 'dt-v-actions__btn--enabled',
                a.highlight && !a.disabled ? 'dt-v-actions__btn--highlight' : '',
                a.custom && !a.disabled ? 'dt-v-actions__btn--custom' : '',
                a.widget && !a.disabled ? 'dt-v-actions__btn--widget' : '',
              ]"
              @click="$emit('action', a.id)"
            />
          </span>
        </template>
      </v-tooltip>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'DisplayTemplateVerticalActions' });

const props = defineProps({
  canAddSection: { type: Boolean, default: false },
  canAddWidget: { type: Boolean, default: false },
  canAddField: { type: Boolean, default: false },
  canAddCustom: { type: Boolean, default: false },
  addCustomTitle: { type: String, default: 'Add custom field' },
  canCut: { type: Boolean, default: false },
  canPaste: { type: Boolean, default: false },
  hasClipboard: { type: Boolean, default: false },
  canRemove: { type: Boolean, default: false },
  canMoveUp: { type: Boolean, default: false },
  canMoveDown: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
});

defineEmits(['action']);

const visibleActions = computed(() => {
  const btn = (id, icon, title, disabled, opts = {}) => ({
    id,
    icon,
    title,
    disabled: !!disabled,
    danger: !!opts.danger,
    highlight: !!opts.highlight,
    custom: !!opts.custom,
    widget: !!opts.widget,
    divider: false,
  });

  return [
    btn(
      'add-section',
      'mdi-view-list-outline',
      props.canAddSection
        ? 'Add section'
        : 'Add section — select a section or section container',
      !props.canAddSection || props.readonly
    ),
    btn(
      'add-widget',
      'mdi-puzzle-outline',
      props.canAddWidget ? 'Add widget to selected section' : 'Add widget — select a section',
      !props.canAddWidget || props.readonly,
      { widget: true }
    ),
    btn(
      'add-custom',
      'mdi-text-box-plus-outline',
      props.canAddCustom
        ? props.addCustomTitle
        : 'Add custom field — select a section or a custom array',
      !props.canAddCustom || props.readonly,
      { custom: true }
    ),
    btn('add-field', 'mdi-playlist-plus', 'Browse unused core fields and containers', !props.canAddField || props.readonly),
    { id: 'divider-1', divider: true },
    btn(
      'cut',
      'mdi-content-cut',
      props.canCut ? 'Mark selected for move' : 'Mark selected nodes for move',
      !props.canCut || props.readonly
    ),
    btn(
      'paste',
      'mdi-content-paste',
      props.hasClipboard ? 'Move marked nodes here' : 'Paste',
      !props.canPaste || props.readonly,
      { highlight: props.hasClipboard && props.canPaste }
    ),
    btn('remove', 'mdi-delete-outline', 'Remove from tree', !props.canRemove || props.readonly, { danger: true }),
    { id: 'divider-2', divider: true },
    btn('move-up', 'mdi-arrow-up-bold-box', 'Move up', !props.canMoveUp || props.readonly),
    btn('move-down', 'mdi-arrow-down-bold-box', 'Move down', !props.canMoveDown || props.readonly),
  ];
});
</script>

<style scoped>
.dt-v-actions {
  width: 40px;
  flex-shrink: 0;
  background: transparent;
}
.dt-v-actions__divider {
  width: 20px;
  opacity: 0.45;
}
.dt-v-actions__btn {
  width: 32px;
  height: 32px;
  border-radius: 6px !important;
}
.dt-v-actions__btn--enabled {
  opacity: 1;
}
.dt-v-actions__btn--enabled:hover {
  background: rgba(var(--v-theme-on-surface), 0.08);
}
.dt-v-actions__btn--highlight {
  background: rgba(var(--v-theme-secondary), 0.16);
}
.dt-v-actions__btn--custom {
  color: #7b1fa2 !important;
}
.dt-v-actions__btn--custom:hover {
  background: rgba(123, 31, 162, 0.12);
}
.dt-v-actions__btn--widget {
  color: #e64a19 !important;
}
.dt-v-actions__btn--widget:hover {
  background: rgba(230, 74, 25, 0.12);
}
.dt-v-actions__btn--disabled {
  opacity: 0.32;
  color: rgba(var(--v-theme-on-surface), 0.34) !important;
}
.dt-v-actions__btn--disabled :deep(.v-icon) {
  opacity: 0.85;
}
</style>
