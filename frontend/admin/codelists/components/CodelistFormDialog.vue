<template>
  <v-dialog :model-value="modelValue" max-width="500" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>{{ isEdit ? 'Edit codelist' : 'Add codelist' }}</v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-text-field
            v-model="form.name"
            label="Name"
            hint="Unique identifier (e.g. dctypes)"
            persistent-hint
            :disabled="isEdit"
            :rules="[v => !!v?.trim() || 'Required']"
            class="mb-2"
          />
          <v-text-field
            v-model="form.description"
            label="Description"
            hint="Optional short description"
            persistent-hint
          />
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">Cancel</v-btn>
        <v-btn color="primary" :loading="saving" @click="submit">
          {{ isEdit ? 'Update' : 'Create' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

defineOptions({ name: 'CodelistFormDialog' });

const props = defineProps({
  modelValue: Boolean,
  codelist: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const formRef = ref(null);
const form = ref({ name: '', description: '' });
const saving = ref(false);

const isEdit = computed(() => !!props.codelist?.id);

watch(
  () => [props.modelValue, props.codelist],
  () => {
    if (props.modelValue) {
      form.value = {
        name: props.codelist?.name ?? '',
        description: props.codelist?.description ?? '',
      };
    }
  },
  { immediate: true }
);

async function submit() {
  const valid = await formRef.value?.validate();
  if (!valid?.valid) return;
  saving.value = true;
  try {
    emit('saved', {
      isEdit: isEdit.value,
      name: form.value.name.trim(),
      description: form.value.description?.trim() || null,
    });
    emit('update:modelValue', false);
  } finally {
    saving.value = false;
  }
}
</script>
