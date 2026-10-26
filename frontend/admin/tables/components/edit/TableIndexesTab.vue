<template>
  <div class="tables-tab-panel pa-4">
    <div class="tables-tab-toolbar">
      <span class="text-subtitle-1 font-weight-medium">Index management</span>
      <v-spacer />
      <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="openCreateIndex">
        Create index
      </v-btn>
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <v-btn size="small" variant="outlined" v-bind="menuProps" append-icon="mdi-menu-down">More</v-btn>
        </template>
        <v-list density="compact" min-width="240">
          <v-list-item prepend-icon="mdi-text-search" title="Create text index" @click="openCreateTextIndex" />
          <v-list-item
            prepend-icon="mdi-delete-sweep"
            title="Delete all indexes"
            :disabled="indexes.length <= 1"
            @click="showDeleteAllDialog = true"
          />
          <v-divider />
          <v-list-item prepend-icon="mdi-playlist-check" title="Apply saved indexes" :disabled="applying" @click="applySaved" />
          <v-list-item
            prepend-icon="mdi-content-save"
            title="Save to table metadata"
            :disabled="syncingDef"
            @click="syncDefinition"
          />
          <v-list-item prepend-icon="mdi-refresh" title="Refresh list" :disabled="loading" @click="loadIndexes" />
        </v-list>
      </v-menu>
    </div>

    <v-data-table :headers="headers" :items="indexes" :loading="loading" item-value="name" density="comfortable">
      <template #item.name="{ item }">
        <strong>{{ item.name }}</strong>
        <v-chip v-if="item.name === '_id_'" size="x-small" color="grey" class="ml-2">System</v-chip>
      </template>
      <template #item.fields="{ item }">
        <template v-if="item.key">
          <v-chip
            v-for="(value, field) in item.key"
            :key="field"
            size="x-small"
            color="info"
            class="mr-1 mb-1"
          >
            {{ field }} ({{
              value === 1 ? 'asc' : value === -1 ? 'desc' : value === 'text' ? 'text' : value
            }})
          </v-chip>
        </template>
        <span v-else class="text-medium-emphasis">N/A</span>
      </template>
      <template #item.type="{ item }">
        <v-chip size="x-small" :color="isTextIndex(item) ? 'purple' : 'primary'">
          {{ isTextIndex(item) ? 'Text' : 'Compound' }}
        </v-chip>
      </template>
      <template #item.actions="{ item }">
        <v-btn
          v-if="item.name !== '_id_'"
          icon
          size="small"
          color="error"
          :loading="deletingName === item.name"
          @click="confirmDelete(item.name)"
        >
          <v-icon size="small">mdi-delete</v-icon>
        </v-btn>
        <span v-else class="text-caption text-medium-emphasis">System index</span>
      </template>
      <template #no-data>
        <div class="text-center py-8 text-medium-emphasis">
          <v-icon size="48" color="grey" class="mb-2">mdi-database-off</v-icon>
          <p>No custom indexes found</p>
        </div>
      </template>
    </v-data-table>

    <v-dialog v-model="showCreateIndexDialog" max-width="600">
      <v-card>
        <v-card-title>Create index</v-card-title>
        <v-card-text>
          <v-alert v-if="!loadingFields && !dictionaryFields.length" type="info" variant="tonal" density="compact" class="mb-3">
            No dictionary fields found. Add fields under Data dictionary first.
          </v-alert>
          <TablesFormField
            label="Index fields"
            required
            hint="Select fields in the order they should appear on the compound index"
          >
            <v-autocomplete
              v-model="selectedIndexFields"
              :items="fieldSelectItems"
              item-title="title"
              item-value="value"
              multiple
              chips
              closable-chips
              clearable
              variant="outlined"
              density="compact"
              hide-details
              :loading="loadingFields"
              :disabled="loadingFields || !dictionaryFields.length"
              placeholder="Type to filter fields…"
            />
          </TablesFormField>
          <v-alert v-if="createError" type="error" density="compact" class="mt-2">{{ createError }}</v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showCreateIndexDialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="creating"
            :disabled="loadingFields || !selectedIndexFields.length"
            @click="createIndex"
          >
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showCreateTextIndexDialog" max-width="600">
      <v-card>
        <v-card-title>Create text index</v-card-title>
        <v-card-text>
          <v-alert v-if="!loadingFields && !dictionaryFields.length" type="info" variant="tonal" density="compact" class="mb-3">
            No dictionary fields found. Add fields under Data dictionary first.
          </v-alert>
          <TablesFormField label="Index fields" required hint="Select one or more text-searchable fields">
            <v-autocomplete
              v-model="selectedTextIndexFields"
              :items="fieldSelectItems"
              item-title="title"
              item-value="value"
              multiple
              chips
              closable-chips
              clearable
              variant="outlined"
              density="compact"
              hide-details
              :loading="loadingFields"
              :disabled="loadingFields || !dictionaryFields.length"
              placeholder="Type to filter fields…"
            />
          </TablesFormField>
          <v-alert v-if="createError" type="error" density="compact" class="mt-2">{{ createError }}</v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showCreateTextIndexDialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="creating"
            :disabled="loadingFields || !selectedTextIndexFields.length"
            @click="createTextIndex"
          >
            Create
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDeleteDialog" max-width="440">
      <v-card>
        <v-card-title>Delete index?</v-card-title>
        <v-card-text>Delete index <strong>{{ indexToDelete }}</strong>?</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="!!deletingName" @click="deleteIndex">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDeleteAllDialog" max-width="440">
      <v-card>
        <v-card-title>Delete all indexes?</v-card-title>
        <v-card-text>This removes all custom indexes (except system _id_).</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showDeleteAllDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="deletingAll" @click="deleteAllIndexes">Delete all</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useTablesApi } from '../../composables/useTablesApi';
import TablesFormField from '../TablesFormField.vue';
import { normalizeFieldFromApi, sortFields } from '../../utils/fieldUtils';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
});

const setMessage = inject('setMessage', () => {});

const api = useTablesApi();
const indexes = ref([]);
const loading = ref(false);
const creating = ref(false);
const deletingName = ref('');
const deletingAll = ref(false);
const applying = ref(false);
const syncingDef = ref(false);
const createError = ref('');
const showCreateIndexDialog = ref(false);
const showCreateTextIndexDialog = ref(false);
const showDeleteDialog = ref(false);
const showDeleteAllDialog = ref(false);
const dictionaryFields = ref([]);
const loadingFields = ref(false);
const selectedIndexFields = ref([]);
const selectedTextIndexFields = ref([]);
const indexToDelete = ref('');

const fieldSelectItems = computed(() =>
  dictionaryFields.value.map((field) => {
    const label = (field.label || '').trim();
    const type = field.data_type || 'string';
    const title =
      label && label !== field.name
        ? `${field.name} — ${label} (${type})`
        : `${field.name} (${type})`;
    return { title, value: field.name };
  })
);

const headers = [
  { title: 'Index name', key: 'name' },
  { title: 'Fields', key: 'fields', sortable: false },
  { title: 'Type', key: 'type', sortable: false },
  { title: 'Actions', key: 'actions', align: 'center', width: 120, sortable: false },
];

function isTextIndex(item) {
  return (
    item.name.includes('text') ||
    (item.key && Object.values(item.key).some((v) => v === 'text'))
  );
}

async function loadDictionaryFields() {
  loadingFields.value = true;
  try {
    const raw = await api.fetchFields(props.dbId, props.tableId);
    dictionaryFields.value = sortFields(raw.map(normalizeFieldFromApi), 'order');
  } catch (e) {
    dictionaryFields.value = [];
    setMessage(e.message, 'error');
  } finally {
    loadingFields.value = false;
  }
}

function openCreateIndex() {
  createError.value = '';
  selectedIndexFields.value = [];
  showCreateIndexDialog.value = true;
  loadDictionaryFields();
}

function openCreateTextIndex() {
  createError.value = '';
  selectedTextIndexFields.value = [];
  showCreateTextIndexDialog.value = true;
  loadDictionaryFields();
}

async function loadIndexes() {
  loading.value = true;
  try {
    indexes.value = await api.fetchIndexes(props.dbId, props.tableId);
  } catch (e) {
    setMessage(e.message, 'error');
    indexes.value = [];
  } finally {
    loading.value = false;
  }
}

async function createIndex() {
  if (!selectedIndexFields.value.length) {
    createError.value = 'Select at least one field';
    return;
  }
  creating.value = true;
  createError.value = '';
  try {
    await api.createIndex(props.dbId, props.tableId, selectedIndexFields.value.join(','));
    setMessage('Index created successfully', 'success');
    showCreateIndexDialog.value = false;
    selectedIndexFields.value = [];
    await loadIndexes();
  } catch (e) {
    createError.value = e.message;
  } finally {
    creating.value = false;
  }
}

async function createTextIndex() {
  if (!selectedTextIndexFields.value.length) {
    createError.value = 'Select at least one field';
    return;
  }
  creating.value = true;
  createError.value = '';
  try {
    await api.createTextIndex(props.dbId, props.tableId, selectedTextIndexFields.value.join(','));
    setMessage('Text index created successfully', 'success');
    showCreateTextIndexDialog.value = false;
    selectedTextIndexFields.value = [];
    await loadIndexes();
  } catch (e) {
    createError.value = e.message;
  } finally {
    creating.value = false;
  }
}

function confirmDelete(name) {
  indexToDelete.value = name;
  showDeleteDialog.value = true;
}

async function deleteIndex() {
  deletingName.value = indexToDelete.value;
  try {
    await api.deleteIndex(props.dbId, props.tableId, indexToDelete.value);
    setMessage('Index deleted successfully', 'success');
    showDeleteDialog.value = false;
    indexToDelete.value = '';
    await loadIndexes();
  } catch (e) {
    setMessage(e.message, 'error');
  } finally {
    deletingName.value = '';
  }
}

async function deleteAllIndexes() {
  deletingAll.value = true;
  try {
    const data = await api.deleteAllIndexes(props.dbId, props.tableId);
    setMessage(data.message || 'All indexes deleted successfully', 'success');
    showDeleteAllDialog.value = false;
    await loadIndexes();
  } catch (e) {
    setMessage(e.message, 'error');
  } finally {
    deletingAll.value = false;
  }
}

async function applySaved() {
  applying.value = true;
  try {
    const data = await api.applySavedIndexes(props.dbId, props.tableId, true);
    const r = data.result || {};
    const errCount = (r.errors || []).length;
    const msg = data.message + (errCount ? ` (${errCount} error(s))` : '');
    setMessage(msg, errCount ? 'warning' : 'success');
    await loadIndexes();
  } catch (e) {
    setMessage(e.message, 'error');
  } finally {
    applying.value = false;
  }
}

async function syncDefinition() {
  syncingDef.value = true;
  try {
    const data = await api.syncIndexDefinitions(props.dbId, props.tableId);
    setMessage(data.message || 'Index definitions saved on table metadata', 'success');
  } catch (e) {
    setMessage(e.message, 'error');
  } finally {
    syncingDef.value = false;
  }
}

onMounted(() => loadIndexes());

defineExpose({ loadIndexes });
</script>
