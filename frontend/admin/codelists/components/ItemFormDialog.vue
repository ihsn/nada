<template>
  <v-dialog :model-value="modelValue" max-width="500" persistent @update:modelValue="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>{{ isEdit ? 'Edit item' : 'Add item' }}</v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-text-field
            v-model="form.code"
            label="Code"
            hint="Unique within this codelist (e.g. doc/qst)"
            persistent-hint
            :disabled="isEdit"
            :rules="[v => !!v?.trim() || 'Required']"
            class="mb-2"
          />
          <v-text-field
            v-model="form.title"
            label="Default title"
            hint="Fallback when no translation exists"
            persistent-hint
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

defineOptions({ name: 'ItemFormDialog' });

const props = defineProps({
  modelValue: Boolean,
  item: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const formRef = ref(null);
const form = ref({ code: '', title: '', sort_order: 0 });
const saving = ref(false);

const isEdit = computed(() => !!props.item?.id);

watch(
  () => [props.modelValue, props.item],
  () => {
    if (props.modelValue) {
      form.value = {
        code: props.item?.code ?? '',
        title: props.item?.title ?? '',
        sort_order: props.item?.sort_order ?? 0,
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
      itemId: props.item?.id,
      code: form.value.code.trim(),
      title: form.value.title?.trim() || null,
      sort_order: Number(form.value.sort_order) || 0,
    });
    emit('update:modelValue', false);
  } finally {
    saving.value = false;
  }
}
</script>
