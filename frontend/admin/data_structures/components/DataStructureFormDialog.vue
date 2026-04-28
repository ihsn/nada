<template>
  <v-dialog :model-value="modelValue" max-width="640" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>{{ isEdit ? 'Edit data structure' : 'Add data structure' }}</v-card-title>
      <v-card-text>
        <DataStructureStructureForm
          v-if="modelValue"
          :structure="structure"
          @cancel="$emit('update:modelValue', false)"
          @saved="onSaved"
        />
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';
import DataStructureStructureForm from './DataStructureStructureForm.vue';

defineOptions({ name: 'DataStructureFormDialog' });

const props = defineProps({
  modelValue: Boolean,
  structure: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const isEdit = computed(() => !!props.structure?.id);

function onSaved(evt) {
  emit('saved', evt);
  emit('update:modelValue', false);
}
</script>
