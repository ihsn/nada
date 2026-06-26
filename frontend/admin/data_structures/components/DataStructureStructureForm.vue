<template>
  <div>
    <v-form ref="formRef">
      <v-row v-if="!isEdit" dense>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.agency"
            label="Agency"
            hint="Default: NADA"
            persistent-hint
            :rules="[v => !!v?.trim() || 'Required']"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="form.version"
            label="Version"
            hint="Semantic version (default: 1.0.0)"
            persistent-hint
            :rules="[
              v => !!v?.trim() || 'Required',
              v => VERSION_REGEX.test((v || '').trim()) || 'Use semantic version, e.g. 1.2.3'
            ]"
          />
        </v-col>
      </v-row>

      <v-text-field
        v-if="!isEdit"
        v-model="form.name"
        label="Name"
        hint="Short identifier; unique with agency + version."
        persistent-hint
        :rules="[v => !!v?.trim() || 'Required']"
        class="mt-1"
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

      <v-text-field v-model="form.title" label="Title" hint="Human-readable title" persistent-hint class="mt-3" />

      <v-select
        v-model="form.status"
        :items="statusOptions"
        item-title="title"
        item-value="value"
        label="Status"
        hint="Workflow state"
        persistent-hint
        class="mt-3"
      />

      <v-textarea v-model="form.description" label="Description" rows="2" auto-grow class="mt-3" />
      <v-textarea v-model="form.notes" label="Notes" rows="2" auto-grow class="mt-3" />
    </v-form>

    <div class="d-flex flex-wrap justify-end gap-2 mt-4">
      <v-btn variant="text" @click="$emit('cancel')">Cancel</v-btn>
      <v-btn color="primary" :loading="saving" @click="submit">
        {{ isEdit ? 'Update' : 'Create' }}
      </v-btn>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

defineOptions({ name: 'DataStructureStructureForm' });

const DEFAULT_AGENCY = 'NADA';
const DEFAULT_VERSION = '1.0.0';
const VERSION_REGEX = /^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|[0-9A-Za-z-]*[A-Za-z-][0-9A-Za-z-]*)(?:\.(?:0|[1-9]\d*|[0-9A-Za-z-]*[A-Za-z-][0-9A-Za-z-]*))*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/;
const statusOptions = [
  { title: 'Draft', value: 0 },
  { title: 'Review', value: 10 },
  { title: 'Published', value: 20 },
  { title: 'Deprecated', value: 30 },
  { title: 'Archived', value: 40 },
];

const props = defineProps({
  /** Null or object without `id` = create mode; object with `id` = edit. */
  structure: { type: Object, default: null },
});

const emit = defineEmits(['cancel', 'saved']);

const formRef = ref(null);
const form = ref({
  name: '',
  agency: DEFAULT_AGENCY,
  version: DEFAULT_VERSION,
  idno: '',
  title: '',
  status: 0,
  description: '',
  notes: '',
});
const saving = ref(false);

const isEdit = computed(() => !!props.structure?.id);

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

function resetFromProps() {
  const s = props.structure;
  if (s?.id) {
    form.value = {
      name: s.name || '',
      agency: s.agency || DEFAULT_AGENCY,
      version: s.version || DEFAULT_VERSION,
      idno: s.idno || '',
      title: s.title || '',
      status: Number.isFinite(Number(s.status)) ? Number(s.status) : 0,
      description: s.description || '',
      notes: s.notes || '',
    };
  } else {
    form.value = {
      name: '',
      agency: DEFAULT_AGENCY,
      version: DEFAULT_VERSION,
      idno: '',
      title: '',
      status: 0,
      description: '',
      notes: '',
    };
  }
}

watch(
  () => props.structure,
  () => {
    resetFromProps();
  },
  { deep: true, immediate: true }
);

async function submit() {
  const valid = await formRef.value?.validate();
  if (!valid?.valid) return;

  saving.value = true;
  try {
    if (isEdit.value) {
      emit('saved', {
        isEdit: true,
        structureId: props.structure.id,
        payload: {
          idno: form.value.idno,
          title: form.value.title,
          status: Number(form.value.status),
          description: form.value.description,
          notes: form.value.notes,
        },
      });
    } else {
      emit('saved', {
        isEdit: false,
        payload: {
          name: form.value.name.trim(),
          agency: (form.value.agency || DEFAULT_AGENCY).trim(),
          version: (form.value.version || DEFAULT_VERSION).trim(),
          idno: form.value.idno?.trim() ?? '',
          title: form.value.title?.trim() || undefined,
          status: Number(form.value.status),
          description: form.value.description?.trim() || undefined,
          notes: form.value.notes?.trim() || undefined,
        },
      });
    }
  } finally {
    saving.value = false;
  }
}

defineExpose({ resetFromProps });
</script>
