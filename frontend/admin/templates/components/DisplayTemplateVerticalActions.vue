<template>
  <div class="dt-v-actions d-flex flex-column align-center py-2 ga-1 border-s flex-shrink-0 align-self-stretch">
    <v-tooltip v-for="a in visibleActions" :key="a.id" :text="a.title" location="left">
      <template #activator="{ props: tip }">
        <v-btn
          v-bind="tip"
          :icon="a.icon"
          size="small"
          variant="text"
          :disabled="a.disabled"
          :color="a.danger ? 'error' : 'primary'"
          class="dt-v-actions__btn"
          @click="$emit('action', a.id)"
        />
      </template>
    </v-tooltip>
  </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'DisplayTemplateVerticalActions' });

const props = defineProps({
  canAddGroup: { type: Boolean, default: false },
  canAddSection: { type: Boolean, default: false },
  canAddField: { type: Boolean, default: false },
  canClone: { type: Boolean, default: false },
  canRemove: { type: Boolean, default: false },
  canMoveUp: { type: Boolean, default: false },
  canMoveDown: { type: Boolean, default: false },
});

defineEmits(['action']);

const visibleActions = computed(() => {
  const btn = (id, icon, title, disabled, danger = false) => ({
    id,
    icon,
    title,
    disabled: !!disabled,
    danger,
  });

  return [
    btn('add-group', 'mdi-folder-plus', 'Add section group', !props.canAddGroup),
    btn('add-section', 'mdi-view-list-outline', 'Add section', !props.canAddSection),
    btn('add-field', 'mdi-form-textbox', 'Add field', !props.canAddField),
    btn('clone', 'mdi-content-duplicate', 'Clone subtree', !props.canClone),
    btn('remove', 'mdi-delete-outline', 'Remove from tree', !props.canRemove, true),
    btn('move-up', 'mdi-arrow-up-bold-box', 'Move up', !props.canMoveUp),
    btn('move-down', 'mdi-arrow-down-bold-box', 'Move down', !props.canMoveDown),
  ];
});
</script>

<style scoped>
.dt-v-actions {
  width: 44px;
  flex-shrink: 0;
}
.dt-v-actions__btn {
  opacity: 0.92;
}
</style>
