<template>
  <v-dialog :model-value="modelValue" max-width="500" persistent @update:modelValue="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>{{ isEdit ? 'Edit group' : 'Add group' }}</v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-text-field
            v-model="form.name"
            label="Name"
            hint="Letters, numbers, underscores and dashes only (e.g. questionnaires, my_group)"
            persistent-hint
            :rules="[
              v => !!v?.trim() || 'Required',
              v => !v?.trim() || /^[a-zA-Z0-9_-]+$/.test(String(v).trim()) || 'Only letters, numbers, underscores and dashes'
            ]"
            class="mb-2"
          />
          <v-text-field
            v-model.number="form.sort_order"
            label="Sort order"
            type="number"
            density="compact"
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

defineOptions({ name: 'GroupFormDialog' });

const props = defineProps({
  modelValue: Boolean,
  group: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const formRef = ref(null);
const form = ref({ name: '', sort_order: 0 });
const saving = ref(false);

const isEdit = computed(() => !!props.group?.id);

watch(
  () => [props.modelValue, props.group],
  () => {
    if (props.modelValue) {
      form.value = {
        name: props.group?.name ?? '',
        sort_order: props.group?.sort_order ?? 0,
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
      groupId: props.group?.id,
      name: form.value.name.trim(),
      sort_order: Number(form.value.sort_order) || 0,
    });
    emit('update:modelValue', false);
  } finally {
    saving.value = false;
  }
}
</script>
