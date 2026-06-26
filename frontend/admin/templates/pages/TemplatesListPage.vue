<template>
  <div class="d-flex flex-column gap-4">
    <div class="d-flex flex-column flex-sm-row gap-3 align-sm-center justify-space-between flex-wrap">
      <h1 class="text-h5 font-weight-semibold">Display templates</h1>
      <div class="d-flex flex-wrap gap-2 align-center">
        <v-btn color="secondary" variant="tonal" prepend-icon="mdi-code-json" @click="openRenderers">Renderers</v-btn>
        <v-btn color="secondary" variant="tonal" prepend-icon="mdi-file-import-outline" @click="importDlg = true">
          Import
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" @click="createDlg = true">New template</v-btn>
        <v-btn icon="mdi-reload" variant="text" :loading="loading" @click="load" />
      </div>
    </div>

    <v-sheet class="pa-3" rounded="lg" elevation="0" border>
      <div class="d-flex flex-column flex-md-row gap-3 flex-wrap align-md-center">
        <v-text-field
          v-model="search"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          label="Search"
          prepend-inner-icon="mdi-magnify"
          class="flex-grow-1"
          style="max-width: 320px"
        />
        <v-select
          v-model="dataTypeFilter"
          :items="dataTypeItems"
          label="Data type"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          style="max-width: 220px"
        />
        <v-select
          v-model="statusFilter"
          :items="STATUS_ITEMS"
          item-title="title"
          item-value="value"
          label="Status"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          style="max-width: 180px"
        />
      </div>
    </v-sheet>

    <v-data-table
      :headers="headers"
      :items="filtered"
      :loading="loading"
      item-value="uid"
      class="elevation-0 border rounded-lg"
      density="comfortable"
    >
      <template #item.name="{ item }">
        <router-link class="text-primary text-decoration-none" :to="{ name: 'template-detail', params: { uid: item.uid } }">
          {{ item.name }}
        </router-link>
      </template>
      <template #item.status="{ item }">
        <v-chip size="small" variant="tonal" :color="statusColor(item.status)">{{ item.status }}</v-chip>
      </template>
      <template #item.template_type="{ item }">
        <span class="text-caption">{{ item.template_type }}</span>
      </template>
      <template #item.actions="{ item }">
        <v-btn-group variant="plain" density="compact">
          <v-btn icon="mdi-pencil" @click="$router.push({ name: 'template-detail', params: { uid: item.uid } })" />
          <v-btn icon="mdi-content-copy" title="Duplicate" @click="onDuplicate(item)" />
          <v-btn icon="mdi-star-outline" title="Set default" @click="onSetDefault(item)" />
          <v-btn icon="mdi-delete-outline" color="error" @click="onDeletePrompt(item)" />
        </v-btn-group>
      </template>
      <template #bottom />
    </v-data-table>

    <v-dialog v-model="renderersDlg" max-width="900" scrollable>
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Configured renderers</span>
          <v-btn icon="mdi-close" variant="text" @click="renderersDlg = false" />
        </v-card-title>
        <v-divider />
        <v-card-text class="pa-0">
          <v-data-table
            :loading="renderersLoading"
            :headers="rendererHeaders"
            :items="renderers"
            density="compact"
          />
          <pre v-if="renderersErr" class="text-error ma-4 text-body-2">{{ renderersErr }}</pre>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-dialog v-model="createDlg" max-width="560" persistent>
      <v-card rounded="xl">
        <v-card-title class="pt-6 px-6">Create display template</v-card-title>
        <v-card-text class="px-6 pb-2">
          <v-text-field v-model="createForm.name" label="Name" variant="outlined" density="comfortable" class="mb-3" />
          <v-combobox
            v-model="createForm.data_type"
            :items="COMMON_DATA_TYPES"
            label="Data type"
            variant="outlined"
            density="comfortable"
            hide-details="auto"
          />
          <div class="text-caption text-medium-emphasis mt-2">Starts with an empty sections array.</div>
        </v-card-text>
        <v-card-actions class="px-6 pb-6 pt-2">
          <v-spacer />
          <v-btn variant="text" @click="createDlg = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="savingCreate" @click="doCreate">Create</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="importDlg" max-width="640" persistent scrollable>
      <v-card rounded="xl">
        <v-card-title class="pt-6 px-6">Import from metadata-editor template JSON</v-card-title>
        <v-card-text class="px-6 pb-2">
          <v-select v-model="importForm.data_type" :items="COMMON_DATA_TYPES" label="Target data type" variant="outlined" class="mb-3" />
          <v-text-field v-model="importForm.name" label="Template name (optional)" variant="outlined" density="comfortable" class="mb-3" />
          <v-file-input
            v-model="importFile"
            label="Pick JSON file"
            accept=".json,application/json"
            variant="outlined"
            prepend-icon="mdi-paperclip"
            show-size
            @update:model-value="readImportFile"
          />
          <v-textarea
            v-model="importForm.rawJson"
            label="Or paste JSON"
            variant="outlined"
            rows="10"
            class="font-mono text-body-2"
          />
        </v-card-text>
        <v-card-actions class="px-6 pb-6 pt-2">
          <v-spacer />
          <v-btn variant="text" @click="importDlg = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="savingImport" @click="doImport">Import</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDlg.show" max-width="440" persistent>
      <v-card rounded="xl">
        <v-card-title class="text-h6 font-weight-semibold pt-6 px-6">Delete template?</v-card-title>
        <v-card-text class="px-6">{{ deleteDlg.item?.name }} ({{ deleteDlg.item?.uid }})</v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="deleteDlg.show = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDlg.busy" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useTemplatesApi } from '../composables/useTemplatesApi';

defineOptions({ name: 'TemplatesListPage' });

const STATUS_ITEMS = [
  { title: 'Draft', value: 'draft' },
  { title: 'Published', value: 'published' },
  { title: 'Archived', value: 'archived' },
];

const COMMON_DATA_TYPES = [
  'geographic',
  'survey',
  'document',
  'timeseries-db',
  'video',
];

const router = useRouter();
const setMessage = inject('setMessage', () => {});

const { loading, fetchTemplates, createTemplate, deleteTemplate, duplicateTemplate, setDefaultTemplate, importTemplate, fetchRenderers } =
  useTemplatesApi();

const templates = ref([]);
const search = ref('');
const dataTypeFilter = ref(null);
const statusFilter = ref(null);
const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'UID', key: 'uid', sortable: true },
  { title: 'Data type', key: 'data_type', sortable: true },
  { title: 'Type', key: 'template_type', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: '', key: 'actions', sortable: false, width: '180px' },
];

/** Pick distinct types from templates + commons */
const dataTypeItems = computed(() => {
  const seen = new Set(COMMON_DATA_TYPES);
  templates.value.forEach((t) => {
    if (t.data_type) seen.add(t.data_type);
  });
  return [...seen].sort();
});

const filtered = computed(() => {
  let rows = [...templates.value];
  const q = (search.value || '').trim().toLowerCase();
  if (q) {
    rows = rows.filter((r) => `${r.name} ${r.uid} ${r.data_type}`.toLowerCase().includes(q));
  }
  if (dataTypeFilter.value) rows = rows.filter((r) => r.data_type === dataTypeFilter.value);
  if (statusFilter.value) rows = rows.filter((r) => r.status === statusFilter.value);
  return rows.sort((a, b) => (a.name || '').localeCompare(b.name || '', undefined, { sensitivity: 'base' }));
});

watch([dataTypeFilter, statusFilter], () => load());

async function load() {
  try {
    templates.value = await fetchTemplates({
      data_type: dataTypeFilter.value || undefined,
      status: statusFilter.value || undefined,
    });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

onMounted(() => load());

function statusColor(s) {
  if (s === 'published') return 'success';
  if (s === 'archived') return 'secondary';
  return 'default';
}

const createDlg = ref(false);
const createForm = ref({ name: '', data_type: 'document' });
const savingCreate = ref(false);

async function doCreate() {
  const name = (createForm.value.name || '').trim();
  const data_type = (createForm.value.data_type || '').trim();
  if (!name || !data_type) {
    setMessage('Name and data type are required.', 'warning');
    return;
  }
  savingCreate.value = true;
  try {
    const t = await createTemplate({
      name,
      data_type,
      template_json: { sections: [] },
    });
    createDlg.value = false;
    setMessage('Template created.', 'success');
    await load();
    if (t?.uid) router.push({ name: 'template-detail', params: { uid: t.uid } });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    savingCreate.value = false;
  }
}

const importDlg = ref(false);
const importForm = ref({ data_type: 'document', name: '', rawJson: '' });
const importFile = ref(null);
const savingImport = ref(false);

function readImportFile(files) {
  const f = Array.isArray(files) ? files[0] : files;
  if (!f) return;
  const reader = new FileReader();
  reader.onload = () => {
    importForm.value.rawJson = String(reader.result || '');
  };
  reader.readAsText(f);
}

async function doImport() {
  const data_type = (importForm.value.data_type || '').trim();
  let template;
  try {
    template = JSON.parse(importForm.value.rawJson || '{}');
  } catch {
    setMessage('Invalid JSON.', 'error');
    return;
  }
  if (!data_type) {
    setMessage('Data type is required.', 'warning');
    return;
  }
  savingImport.value = true;
  try {
    const body = { data_type, template };
    const name = (importForm.value.name || '').trim();
    if (name) body.name = name;
    const res = await importTemplate(body);
    importDlg.value = false;
    const msg = res?.import_summary
      ? `Imported. ${JSON.stringify(res.import_summary)}`
      : 'Import completed.';
    setMessage(msg, 'success');
    await load();
    const uid = res?.template?.uid;
    if (uid) router.push({ name: 'template-detail', params: { uid } });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    savingImport.value = false;
  }
}

async function onDuplicate(item) {
  try {
    const t = await duplicateTemplate(item.uid);
    setMessage('Duplicated.', 'success');
    await load();
    if (t?.uid) router.push({ name: 'template-detail', params: { uid: t.uid } });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

async function onSetDefault(item) {
  try {
    await setDefaultTemplate(item.data_type, item.uid);
    setMessage(`Default for ${item.data_type} set.`, 'success');
    await load();
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

const deleteDlg = ref({ show: false, item: null, busy: false });
function onDeletePrompt(item) {
  deleteDlg.value = { show: true, item, busy: false };
}
async function doDelete() {
  const item = deleteDlg.value.item;
  if (!item) return;
  deleteDlg.value.busy = true;
  try {
    await deleteTemplate(item.uid);
    deleteDlg.value.show = false;
    setMessage('Deleted.', 'success');
    await load();
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    deleteDlg.value.busy = false;
  }
}

const renderersDlg = ref(false);
const renderers = ref([]);
const renderersLoading = ref(false);
const renderersErr = ref('');
const rendererHeaders = [
  { title: 'Key', key: 'key' },
  { title: 'Label', key: 'label' },
  { title: 'Source types', key: 'supported_source_types' },
];

async function openRenderers() {
  renderersDlg.value = true;
  renderersLoading.value = true;
  renderersErr.value = '';
  try {
    const list = await fetchRenderers();
    renderers.value = list.map((r) => ({
      ...r,
      supported_source_types: Array.isArray(r.supported_source_types)
        ? r.supported_source_types.join(', ')
        : '',
    }));
  } catch (e) {
    renderersErr.value = e?.message || String(e);
  } finally {
    renderersLoading.value = false;
  }
}
</script>
