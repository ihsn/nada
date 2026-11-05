<template>
  <v-dialog
    :model-value="modelValue"
    max-width="640"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6 d-flex align-center justify-space-between">
        <span>{{ lbl.link_data_file_resources || 'Link external resources' }}</span>
        <v-btn icon variant="text" size="small" :disabled="saving" @click="close">
          <v-icon icon="mdi-close" />
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="px-6 py-5">
        <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
          {{ error }}
        </v-alert>
        <div v-if="fileName" class="text-body-2 mb-6 text-medium-emphasis">{{ fileName }}</div>
        <div v-if="loading" class="text-center py-6">
          <v-progress-circular indeterminate size="36" />
        </div>
        <template v-else>
          <p class="text-body-2 mb-4">
            {{ lbl.select_resources_for_data_file || 'Select external resources used by this data file.' }}
          </p>
          <div v-if="!rows.length" class="text-body-2 text-medium-emphasis">
            {{ lbl.no_microdata_resources || 'No microdata resources found for this study.' }}
          </div>
          <v-table v-else density="compact" class="attach-resources-table">
            <thead>
              <tr>
                <th style="width: 72px">{{ lbl.select || 'Select' }}</th>
                <th>{{ lbl.external_resource || 'External resource' }}</th>
                <th style="width: 160px">{{ lbl.file_format || 'File format' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.resource_id">
                <td>
                  <v-checkbox
                    v-model="row.selected"
                    density="compact"
                    hide-details
                    color="primary"
                    :disabled="saving"
                  />
                </td>
                <td class="text-body-2">{{ row.filename || row.title || row.resource_id }}</td>
                <td>
                  <v-select
                    v-model="row.file_format"
                    :items="formatItems"
                    density="compact"
                    variant="outlined"
                    hide-details
                    :disabled="saving || !row.selected"
                  />
                </td>
              </tr>
            </tbody>
          </v-table>
        </template>
      </v-card-text>
      <v-divider />
      <v-card-actions class="px-6 py-4">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">{{ lbl.cancel || 'Cancel' }}</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="loading" @click="submit">
          {{ lbl.update || 'Update' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyOverviewApi } from '../composables/useStudyOverviewApi';

defineOptions({ name: 'AttachDataFileResourcesDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  fileId: { type: [Number, String], default: null },
});
const emit = defineEmits(['update:modelValue', 'saved']);

const { config } = useAppConfig();
const { fetchDataFileResources, saveDataFileResources } = useStudyOverviewApi();
const lbl = computed(() => config.value?.labels || {});

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const fileName = ref('');
const rows = ref([]);

const formatItems = [
  { title: '—', value: '' },
  { title: 'SPSS', value: 'spss' },
  { title: 'STATA', value: 'stata' },
  { title: 'R', value: 'r' },
  { title: 'CSV', value: 'csv' },
  { title: 'Text', value: 'txt' },
];

function close() {
  emit('update:modelValue', false);
}

async function load() {
  if (props.fileId == null || props.fileId === '') return;
  loading.value = true;
  error.value = '';
  try {
    const data = await fetchDataFileResources(props.fileId);
    fileName.value = data.file?.file_name || data.file?.filename || String(props.fileId);
    const attachedMap = {};
    for (const a of data.attached_resources || []) {
      attachedMap[String(a.resource_id)] = a.file_format || '';
    }
    rows.value = (data.microdata_resources || []).map((r) => {
      const id = r.resource_id;
      return {
        resource_id: id,
        filename: r.filename || r.title || String(id),
        title: r.title || '',
        selected: Object.prototype.hasOwnProperty.call(attachedMap, String(id)),
        file_format: attachedMap[String(id)] || '',
      };
    });
  } catch (e) {
    error.value = e?.message || 'Failed to load resources';
    rows.value = [];
  } finally {
    loading.value = false;
  }
}

async function submit() {
  saving.value = true;
  error.value = '';
  try {
    const resources = rows.value
      .filter((r) => r.selected)
      .map((r) => ({
        resource_id: r.resource_id,
        file_format: r.file_format || '',
      }));
    await saveDataFileResources(props.fileId, resources);
    emit('saved');
    close();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Save failed';
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) load();
    else {
      error.value = '';
      rows.value = [];
      fileName.value = '';
    }
  }
);
</script>
