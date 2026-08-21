<template>
  <div class="dt-add-fields-tab">
    <div class="dt-add-fields-banner mb-4">
      <v-alert
        :type="addTarget.ready ? 'info' : 'warning'"
        variant="tonal"
        density="compact"
        class="text-body-2"
        :icon="addTarget.ready ? 'mdi-target' : 'mdi-cursor-default-click-outline'"
      >
      <template v-if="addTarget.ready && mode === 'array-props' && addTarget.type === 'nested_array'">
        Unused fields for <strong>{{ addTarget.label }}</strong>
        <span class="text-medium-emphasis">(sections in the core template are ignored)</span>
      </template>
      <template v-else-if="addTarget.ready && mode === 'array-props' && addTarget.type === 'array'">
        Adding columns to <strong>{{ addTarget.label }}</strong>
        <span class="text-medium-emphasis">(array)</span>
      </template>
      <template v-else-if="addTarget.ready && mode === 'array-props'">
        Adding columns to <strong>{{ addTarget.label }}</strong>
        <span class="text-medium-emphasis">({{ addTarget.type }})</span>
      </template>
        <template v-else-if="addTarget.ready && addTarget.type === 'template'">
          Unused core section containers for
          <strong>{{ addTarget.label }}</strong>
        </template>
        <template v-else-if="addTarget.ready && addTarget.type === 'section_container'">
          Select a section in <strong>{{ addTarget.label }}</strong> to add unused core fields.
        </template>
        <template v-else-if="addTarget.ready">
          Adding to <strong>{{ addTarget.label }}</strong>
          <span v-if="addTarget.type" class="text-medium-emphasis">({{ addTarget.type }})</span>
        </template>
        <template v-else>
          {{ addTarget.hint }}
        </template>
      </v-alert>
    </div>

    <div v-if="!hasCore" class="text-body-2 text-medium-emphasis py-8 text-center">
      No core template baseline loaded for this type.
    </div>

    <!-- Array column mode -->
    <template v-else-if="mode === 'array-props'">
      <div v-if="!arrayProps.length" class="text-body-2 text-medium-emphasis py-8 text-center">
        All core columns for this array are already in the layout.
      </div>
      <div v-else class="dt-add-fields-scroll">
        <v-list density="compact" class="py-0 bg-transparent">
          <v-list-item
            v-for="col in arrayProps"
            :key="col.key"
            rounded="lg"
            class="dt-add-field-row"
            :disabled="readonly"
            @click="onRowClick(col)"
          >
            <template #prepend>
              <v-icon size="20" :icon="partIcon(col.type)" />
            </template>
            <v-list-item-title class="text-body-2">{{ col.title }}</v-list-item-title>
            <v-list-item-subtitle class="text-caption">{{ col.key }}</v-list-item-subtitle>
            <template #append>
              <v-btn
                size="small"
                variant="tonal"
                color="primary"
                :disabled="readonly || !canAdd"
                prepend-icon="mdi-plus"
                @click.stop="$emit('add-part', col)"
              >
                Add
              </v-btn>
            </template>
          </v-list-item>
        </v-list>
      </div>
    </template>

    <!-- Section field mode -->
    <template v-else>
      <div v-if="!groups.length" class="text-body-2 text-medium-emphasis py-6 text-center">
        <template v-if="!canAdd">{{ addTarget.hint }}</template>
        <template v-else-if="addTarget.type === 'template'">
          All core section containers are already in the layout.
        </template>
        <template v-else-if="addTarget.type === 'section_container'">
          Select a section in this container to add unused core fields.
        </template>
        <template v-else>All core fields for this container are already in the layout.</template>
      </div>
      <div v-else class="dt-add-fields-scroll">
        <v-expansion-panels v-model="expandedPanels" multiple variant="accordion" class="dt-add-fields-panels">
          <v-expansion-panel v-for="group in groups" :key="group.id" :value="group.id" rounded="lg" elevation="0">
            <v-expansion-panel-title class="text-body-2 font-weight-medium">
              <span class="text-truncate">{{ groupLabel(group) }}</span>
              <v-chip size="x-small" variant="tonal" class="ml-2">{{ group.fields.length }}</v-chip>
            </v-expansion-panel-title>
            <v-expansion-panel-text>
              <v-list density="compact" class="py-0 bg-transparent">
                <v-list-item
                  v-for="field in group.fields"
                  :key="field.key"
                  rounded="lg"
                  class="dt-add-field-row"
                  :disabled="readonly"
                  @click="onRowClick(field)"
                >
                  <template #prepend>
                    <v-icon size="20" :icon="partIcon(field.type)" />
                  </template>
                  <v-list-item-title class="text-body-2">{{ field.title }}</v-list-item-title>
                  <v-list-item-subtitle class="text-caption">{{ field.key }}</v-list-item-subtitle>
                  <template #append>
                    <v-btn
                      size="small"
                      variant="tonal"
                      color="primary"
                      :disabled="readonly || !canAdd"
                      prepend-icon="mdi-plus"
                      @click.stop="$emit('add-part', field)"
                    >
                      Add
                    </v-btn>
                  </template>
                </v-list-item>
              </v-list>
            </v-expansion-panel-text>
          </v-expansion-panel>
        </v-expansion-panels>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { formatGroupBreadcrumb } from '../utils/availableFieldGroups';

defineOptions({ name: 'DisplayTemplateAddFieldsTab' });

const props = defineProps({
  mode: { type: String, default: 'fields' },
  fieldGroups: { type: Array, default: () => [] },
  arrayProps: { type: Array, default: () => [] },
  hasCore: { type: Boolean, default: false },
  canAdd: { type: Boolean, default: false },
  addTarget: {
    type: Object,
    default: () => ({ ready: false, label: '', type: '', hint: '' }),
  },
  readonly: { type: Boolean, default: false },
});

const emit = defineEmits(['add-part']);

const expandedPanels = ref([]);

const groups = computed(() => props.fieldGroups);

function groupLabel(group) {
  return formatGroupBreadcrumb(group.breadcrumb) || 'Fields';
}

function partIcon(type) {
  switch (type) {
    case 'section':
    case 'section_container':
      return 'mdi-folder-outline';
    case 'nested_array':
      return 'mdi-file-tree-outline';
    case 'array':
    case 'simple_array':
      return 'mdi-table-large';
    case 'object':
      return 'mdi-code-json';
    case 'boolean':
      return 'mdi-toggle-switch-outline';
    case 'number':
    case 'integer':
      return 'mdi-numeric';
    default:
      return 'mdi-form-textbox';
  }
}

function onRowClick(part) {
  if (props.readonly || !props.canAdd) return;
  emit('add-part', part);
}

watch(
  groups,
  (next) => {
    expandedPanels.value = next.map((g) => g.id);
  },
  { immediate: true }
);
</script>

<style scoped>
.dt-add-fields-tab {
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
}
.dt-add-fields-banner {
  flex: 0 0 auto;
}
.dt-add-fields-banner :deep(.v-alert) {
  flex: 0 0 auto;
}
.dt-add-fields-scroll {
  flex: 1 1 0;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.dt-add-fields-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  margin-bottom: 8px;
}
.dt-add-field-row {
  min-height: 52px;
  cursor: pointer;
}
.dt-add-field-row:not(.v-list-item--disabled):hover {
  background: rgba(var(--v-theme-primary), 0.06);
}
</style>
