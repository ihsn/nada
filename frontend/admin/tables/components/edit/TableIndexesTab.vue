<template>
  <v-card flat rounded="0">
    <v-card-title class="d-flex flex-wrap align-center gap-2">
      <span>Index management</span>
      <v-spacer />
      <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="showCreateIndexDialog = true">
        Create index
      </v-btn>
      <v-btn color="primary" size="small" prepend-icon="mdi-text-search" @click="showCreateTextIndexDialog = true">
        Text index
      </v-btn>
      <v-btn
        color="error"
        size="small"
        prepend-icon="mdi-delete-sweep"
        :disabled="indexes.length <= 1"
        @click="showDeleteAllDialog = true"
      >
        Delete all
      </v-btn>
      <v-btn icon size="small" :loading="loading" @click="loadIndexes">
        <v-icon>mdi-refresh</v-icon>
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-alert v-if="errorMsg" type="error" closable class="mb-4" @click:close="errorMsg = ''">{{ errorMsg }}</v-alert>
      <v-alert v-if="successMsg" type="success" closable class="mb-4" @click:close="successMsg = ''">
        {{ successMsg }}
      </v-alert>
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
    </v-card-text>

    <v-dialog v-model="showCreateIndexDialog" max-width="600">
      <v-card>
        <v-card-title>Create index</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="newIndexFields"
            label="Index fields *"
            hint="Comma-separated field names"
            variant="outlined"
            density="compact"
          />
          <v-alert v-if="createError" type="error" density="compact" class="mt-2">{{ createError }}</v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showCreateIndexDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="creating" @click="createIndex">Create</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showCreateTextIndexDialog" max-width="600">
      <v-card>
        <v-card-title>Create text index</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="newTextIndexFields"
            label="Index fields *"
            variant="outlined"
            density="compact"
          />
          <v-alert v-if="createError" type="error" density="compact" class="mt-2">{{ createError }}</v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showCreateTextIndexDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="creating" @click="createTextIndex">Create</v-btn>
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
  </v-card>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useTablesApi } from '../../composables/useTablesApi';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
});

const api = useTablesApi();
const indexes = ref([]);
const loading = ref(false);
const creating = ref(false);
const deletingName = ref('');
const deletingAll = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const createError = ref('');
const showCreateIndexDialog = ref(false);
const showCreateTextIndexDialog = ref(false);
const showDeleteDialog = ref(false);
const showDeleteAllDialog = ref(false);
const newIndexFields = ref('');
const newTextIndexFields = ref('');
const indexToDelete = ref('');

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

async function loadIndexes() {
  loading.value = true;
  errorMsg.value = '';
  try {
    indexes.value = await api.fetchIndexes(props.dbId, props.tableId);
  } catch (e) {
    errorMsg.value = e.message;
    indexes.value = [];
  } finally {
    loading.value = false;
  }
}

async function createIndex() {
  if (!newIndexFields.value?.trim()) {
    createError.value = 'Enter at least one field name';
    return;
  }
  creating.value = true;
  createError.value = '';
  try {
    await api.createIndex(props.dbId, props.tableId, newIndexFields.value.trim());
    successMsg.value = 'Index created successfully';
    showCreateIndexDialog.value = false;
    newIndexFields.value = '';
    await loadIndexes();
  } catch (e) {
    createError.value = e.message;
  } finally {
    creating.value = false;
  }
}

async function createTextIndex() {
  if (!newTextIndexFields.value?.trim()) {
    createError.value = 'Enter at least one field name';
    return;
  }
  creating.value = true;
  createError.value = '';
  try {
    await api.createTextIndex(props.dbId, props.tableId, newTextIndexFields.value.trim());
    successMsg.value = 'Text index created successfully';
    showCreateTextIndexDialog.value = false;
    newTextIndexFields.value = '';
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
    successMsg.value = 'Index deleted successfully';
    showDeleteDialog.value = false;
    indexToDelete.value = '';
    await loadIndexes();
  } catch (e) {
    errorMsg.value = e.message;
  } finally {
    deletingName.value = '';
  }
}

async function deleteAllIndexes() {
  deletingAll.value = true;
  try {
    const data = await api.deleteAllIndexes(props.dbId, props.tableId);
    successMsg.value = data.message || 'All indexes deleted successfully';
    showDeleteAllDialog.value = false;
    await loadIndexes();
  } catch (e) {
    errorMsg.value = e.message;
  } finally {
    deletingAll.value = false;
  }
}

onMounted(() => loadIndexes());

defineExpose({ loadIndexes });
</script>
