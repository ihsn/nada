<template>
  <v-dialog :model-value="modelValue" max-width="560" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>{{ isEdit ? 'Edit codelist' : 'Add codelist' }}</v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-row dense>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.agency"
                label="Agency"
                hint="Maintaining agency (default: NADA)"
                persistent-hint
                :disabled="isEdit"
                :rules="[v => !!v?.trim() || 'Required']"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.version"
                label="Version"
                hint="Semantic version (default: 1.0.0), e.g. 1.2.3"
                persistent-hint
                :disabled="isEdit"
                :rules="[v => !!v?.trim() || 'Required']"
              />
            </v-col>
          </v-row>

          <v-text-field
            v-model="form.name"
            label="Name"
            hint="Short identifier (e.g. dctypes). Unique within agency + version."
            persistent-hint
            :disabled="isEdit"
            :rules="[v => !!v?.trim() || 'Required']"
            class="mt-3"
          />

          <v-text-field
            v-model="form.idno"
            label="IDNO"
            :hint="idnoHint"
            persistent-hint
            :placeholder="autoIdnoPreview"
            class="mt-3"
          >
            <template v-if="isEdit" #append-inner>
              <v-btn
                size="x-small"
                variant="text"
                icon="mdi-refresh"
                title="Regenerate from agency/name/version"
                @click="form.idno = ''"
              />
            </template>
          </v-text-field>

          <v-text-field
            v-model="form.description"
            label="Description"
            hint="Optional short description"
            persistent-hint
            class="mt-3"
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

const DEFAULT_AGENCY = 'NADA';
const DEFAULT_VERSION = '1.0.0';

const props = defineProps({
  modelValue: Boolean,
  codelist: { type: Object, default: null },
});

const emit = defineEmits(['update:modelValue', 'saved']);

const formRef = ref(null);
const form = ref({
  name: '',
  agency: DEFAULT_AGENCY,
  version: DEFAULT_VERSION,
  idno: '',
  description: '',
});
const saving = ref(false);

const isEdit = computed(() => !!props.codelist?.id);

// Deterministic preview used as placeholder and as blank-save value.
const autoIdnoPreview = computed(() => {
  const a = (form.value.agency || '').trim();
  const n = (form.value.name || '').trim();
  const v = (form.value.version || '').trim();
  if (!a || !n || !v) return '';
  return `${a}_${n}_${v}`;
});

const idnoHint = computed(() =>
  isEdit.value
    ? 'Leave blank and save to regenerate from agency/name/version.'
    : 'Optional. Auto-generated from agency/name/version if left blank.'
);

watch(
  () => [props.modelValue, props.codelist],
  () => {
    if (props.modelValue) {
      form.value = {
        name:        props.codelist?.name        ?? '',
        agency:      props.codelist?.agency      ?? DEFAULT_AGENCY,
        version:     props.codelist?.version     ?? DEFAULT_VERSION,
        idno:        props.codelist?.idno        ?? '',
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
    const payload = {
      isEdit: isEdit.value,
      name:        form.value.name.trim(),
      agency:      (form.value.agency || DEFAULT_AGENCY).trim(),
      version:     (form.value.version || DEFAULT_VERSION).trim(),
      // Empty string triggers server-side auto (re)generation.
      idno:        form.value.idno?.trim() ?? '',
      description: form.value.description?.trim() || null,
    };
    emit('saved', payload);
    emit('update:modelValue', false);
  } finally {
    saving.value = false;
  }
}
</script>
