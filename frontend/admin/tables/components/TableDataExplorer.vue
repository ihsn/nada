<template>
  <v-card flat rounded="0">
    <v-card-title class="d-flex flex-wrap align-center gap-2">
      <span>Data management</span>
      <v-spacer />
      <v-btn color="primary" size="small" prepend-icon="mdi-upload" class="mr-2" @click="openUpload">
        Upload data
      </v-btn>
      <v-btn
        v-if="tableStats && tableStats.count > 0"
        color="error"
        size="small"
        prepend-icon="mdi-delete"
        @click="showDeleteDialog = true"
      >
        Delete data
      </v-btn>
    </v-card-title>
    <v-card-text>
      <div v-if="tableStats && tableStats.count !== undefined" class="mb-3 d-flex flex-wrap align-center gap-2">
        <v-chip size="small" prepend-icon="mdi-database">
          Total rows: {{ tableStats.count.toLocaleString() }}
        </v-chip>
        <v-btn size="small" color="primary" prepend-icon="mdi-refresh" :loading="previewLoading" @click="loadPreviewData">
          Refresh
        </v-btn>
        <v-btn
          size="small"
          color="success"
          prepend-icon="mdi-download"
          :disabled="!previewData?.length"
          @click="exportToCSV"
        >
          Export CSV
        </v-btn>
      </div>

      <div v-if="previewLoading" class="text-center py-4">
        <v-progress-circular indeterminate color="primary" />
        <div class="mt-2">Loading data…</div>
      </div>
      <v-alert v-else-if="previewError" type="error" variant="tonal" density="compact">{{ previewError }}</v-alert>
      <template v-else-if="previewData?.length">
        <v-data-table
          :headers="previewHeaders"
          :items="truncatedPreviewData"
          :items-per-page="previewLimit"
          hide-default-footer
          density="compact"
          class="elevation-1"
        />
        <div class="d-flex justify-space-between align-center mt-3">
          <div class="text-caption">
            Showing {{ (previewPage - 1) * previewLimit + 1 }} to
            {{ Math.min(previewPage * previewLimit, previewTotal) }} of {{ previewTotal }} rows
          </div>
          <div class="d-flex align-center gap-2">
            <v-btn
              size="small"
              icon="mdi-chevron-left"
              :disabled="previewPage === 1"
              @click="previewPage = Math.max(1, previewPage - 1)"
            />
            <span class="text-caption">Page {{ previewPage }}</span>
            <v-btn
              size="small"
              icon="mdi-chevron-right"
              :disabled="previewPage * previewLimit >= previewTotal"
              @click="previewPage++"
            />
          </div>
        </div>
      </template>
      <div v-else class="text-center py-8 text-medium-emphasis">
        <v-icon size="48" color="grey" class="mb-2">mdi-database-off</v-icon>
        <div>No data available. Click "Upload data" to upload and import a CSV or ZIP file.</div>
      </div>
    </v-card-text>

    <v-dialog v-model="showUploadDialog" max-width="600" :persistent="uploading || deleting || importing">
      <v-card>
        <v-card-title class="d-flex align-center">
          Upload CSV or ZIP file
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" @click="showUploadDialog = false" />
        </v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" density="compact" class="mb-4">
            Uploading data will delete all existing data in this table and replace it. This cannot be undone.
          </v-alert>
          <v-file-input
            v-model="uploadFile"
            label="Select CSV or ZIP file"
            accept=".csv,.zip"
            variant="outlined"
            density="compact"
            prepend-icon="mdi-file-upload"
            show-size
            :disabled="uploading || deleting || importing"
          />
          <v-switch
            v-model="syncFieldsAfterImport"
            label="Sync fields after import (remove fields not in data)"
            density="compact"
            hide-details
            class="mt-3"
            :disabled="uploading || deleting || importing"
          />
          <v-alert v-if="uploadStatus" :type="uploadAlertType" variant="tonal" density="compact" class="mt-4">
            <div class="font-weight-medium mb-1">{{ uploadStatus.message }}</div>
            <div v-if="uploadStatus.file_path" class="text-caption">File: {{ uploadStatus.file_path }}</div>
          </v-alert>
          <v-alert v-if="deleting" type="info" variant="tonal" density="compact" class="mt-4">
            Deleting existing data…
          </v-alert>
          <v-alert v-if="importStatus" :type="importAlertType" variant="tonal" density="compact" class="mt-4">
            <div class="font-weight-medium mb-2">{{ importStatus.message }}</div>
            <v-progress-linear
              v-if="importStatus.progress_percent !== undefined && importing"
              :model-value="importStatus.progress_percent"
              height="20"
              rounded
            />
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn v-if="importing" variant="text" color="error" @click="cancelImport">Cancel import</v-btn>
          <v-btn variant="text" :disabled="uploading || deleting || importing" @click="showUploadDialog = false">
            Close
          </v-btn>
          <v-btn
            color="primary"
            :loading="uploading || deleting || importing"
            :disabled="!uploadFile?.length || uploading || deleting || importing"
            prepend-icon="mdi-upload"
            @click="uploadData"
          >
            Upload &amp; import
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showDeleteDialog" max-width="500" persistent>
      <v-card>
        <v-card-title class="bg-error text-white">Delete data</v-card-title>
        <v-card-text class="pt-4">
          <v-alert type="warning" variant="tonal" density="compact" class="mb-4">
            This will permanently delete all data in this table.
          </v-alert>
          <div v-if="tableStats?.count !== undefined" class="mb-3">
            Current row count: <strong>{{ tableStats.count.toLocaleString() }}</strong>
          </div>
          <v-checkbox v-model="deleteDefinition" label="Also delete table definition" density="compact" hide-details />
          <v-alert v-if="deleteStatus" :type="deleteStatus.status === 'success' ? 'success' : 'error'" class="mt-4">
            {{ deleteStatus.message }}
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="deleting" @click="showDeleteDialog = false">Cancel</v-btn>
          <v-btn color="error" :loading="deleting" prepend-icon="mdi-delete" @click="deleteTableData">
            Delete all data
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useTablesApi } from '../composables/useTablesApi';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
});

const emit = defineEmits(['fields-changed']);

const api = useTablesApi();
const base = () => api.base();

const uploadFile = ref([]);
const uploading = ref(false);
const importing = ref(false);
const importCancelled = ref(false);
const uploadStatus = ref(null);
const importStatus = ref(null);
const showUploadDialog = ref(false);
const showDeleteDialog = ref(false);
const syncFieldsAfterImport = ref(true);
const deleteDefinition = ref(false);
const deleting = ref(false);
const deleteStatus = ref(null);
const tableStats = ref(null);
const previewData = ref([]);
const previewHeaders = ref([]);
const previewLoading = ref(false);
const previewError = ref(null);
const previewLimit = 50;
const previewPage = ref(1);
const previewTotal = ref(0);

const uploadAlertType = computed(() => {
  if (!uploadStatus.value) return 'info';
  return uploadStatus.value.status === 'success' ? 'success' : uploadStatus.value.status === 'error' ? 'error' : 'info';
});
const importAlertType = computed(() => {
  if (!importStatus.value) return 'info';
  const s = importStatus.value.status;
  if (s === 'success') return 'success';
  if (s === 'error') return 'error';
  if (s === 'warning') return 'warning';
  return 'info';
});

const truncatedPreviewData = computed(() => {
  if (!previewData.value?.length) return [];
  return previewData.value.map((row) => {
    const truncatedRow = {};
    for (const key in row) {
      const value = row[key];
      truncatedRow[key] =
        typeof value === 'string' && value.length > 50 ? value.substring(0, 50) + '...' : value;
    }
    return truncatedRow;
  });
});

watch(previewPage, () => loadPreviewData());
watch(showUploadDialog, (open) => {
  if (open) {
    uploadFile.value = [];
    uploadStatus.value = null;
    importStatus.value = null;
    uploading.value = false;
    importing.value = false;
    importCancelled.value = false;
    deleting.value = false;
  }
});

function openUpload() {
  showUploadDialog.value = true;
}

async function loadTableStats() {
  try {
    const result = await api.fetchTableInfo(props.dbId, props.tableId);
    tableStats.value = { count: result.count || 0 };
  } catch (e) {
    console.error('Error loading table stats:', e);
  }
}

async function loadPreviewData() {
  previewLoading.value = true;
  previewError.value = null;
  try {
    const offset = (previewPage.value - 1) * previewLimit;
    const { data } = await axios.get(`${base()}/data/${props.dbId}/${props.tableId}`, {
      params: { limit: previewLimit, offset },
    });
    const rows = data.data || [];
    previewData.value = rows;
    previewTotal.value = data.total || data.found || rows.length;
    previewHeaders.value =
      rows.length > 0
        ? Object.keys(rows[0]).map((key) => ({ title: key, key, sortable: true }))
        : [];
  } catch (e) {
    previewError.value = 'Error loading preview: ' + (e.response?.data?.message || e.message);
    previewData.value = [];
    previewHeaders.value = [];
  } finally {
    previewLoading.value = false;
  }
}

async function uploadData() {
  const file = uploadFile.value?.[0];
  if (!file) {
    alert('Please select a file to upload');
    return;
  }
  uploading.value = true;
  uploadStatus.value = null;
  importStatus.value = null;
  try {
    const formData = new FormData();
    formData.append('file', file);
    const uploadResponse = await fetch(`${base()}/upload/${props.dbId}/${props.tableId}`, {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const uploadResult = await uploadResponse.json();
    if (uploadResult.status === 'success') {
      uploadStatus.value = {
        status: 'success',
        message: uploadResult.message,
        file_path: uploadResult.file_path,
      };
      uploadFile.value = [];
      deleting.value = true;
      try {
        const deleteResponse = await fetch(`${base()}/delete/${props.dbId}/${props.tableId}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ delete_definition: false }),
        });
        const deleteResult = await deleteResponse.json();
        deleting.value = false;
        if (deleteResult.status === 'success') {
          await importData();
        } else {
          uploadStatus.value = {
            status: 'error',
            message: 'Failed to delete existing data: ' + (deleteResult.message || 'Unknown error'),
          };
          await loadTableStats();
          await loadPreviewData();
        }
      } catch (deleteError) {
        deleting.value = false;
        uploadStatus.value = { status: 'error', message: 'Error deleting existing data: ' + deleteError.message };
        await loadTableStats();
        await loadPreviewData();
      }
    } else {
      uploadStatus.value = { status: 'error', message: uploadResult.message || 'Upload failed' };
      await loadTableStats();
      await loadPreviewData();
    }
  } catch (e) {
    uploadStatus.value = { status: 'error', message: 'Upload failed: ' + e.message };
    await loadTableStats();
    await loadPreviewData();
  } finally {
    uploading.value = false;
  }
}

async function importData() {
  importing.value = true;
  importStatus.value = null;
  importCancelled.value = false;
  try {
    let hasMore = true;
    let importResult = null;
    while (hasMore && !importCancelled.value) {
      const response = await fetch(`${base()}/import/${props.dbId}/${props.tableId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ db_id: props.dbId, table_id: props.tableId }),
      });
      importResult = await response.json();
      if (importResult.status === 'success') {
        const progress = importResult.progress || {};
        importStatus.value = {
          status: progress.import_status === 'completed' ? 'success' : 'in_progress',
          message:
            progress.import_status === 'completed'
              ? 'Import completed successfully'
              : `Importing… ${progress.progress_percent || 0}%`,
          progress_percent: progress.progress_percent || 0,
          import_status: progress.import_status || 'in_progress',
        };
        hasMore = progress.has_more === true && progress.import_status !== 'completed';
        if (hasMore && !importCancelled.value) {
          await new Promise((r) => setTimeout(r, 500));
        }
      } else {
        importStatus.value = { status: 'error', message: importResult.message || 'Import failed' };
        hasMore = false;
      }
    }
    if (importCancelled.value) {
      importStatus.value = { status: 'warning', message: 'Import cancelled by user' };
      await loadTableStats();
      await loadPreviewData();
      return;
    }
    if (importResult?.status === 'success') {
      if (syncFieldsAfterImport.value) {
        try {
          const syncData = await api.syncFields(props.dbId, props.tableId);
          const removed = syncData.fields_removed || 0;
          const added = syncData.fields_added || 0;
          if (removed > 0 || added > 0) {
            importStatus.value.message += ` Fields synced: ${removed} removed, ${added} added.`;
          }
        } catch (e) {
          console.error('Error syncing fields:', e);
        }
      }
      showUploadDialog.value = false;
      await loadTableStats();
      await loadPreviewData();
      emit('fields-changed');
    } else {
      await loadTableStats();
      await loadPreviewData();
    }
  } catch (e) {
    importStatus.value = { status: 'error', message: 'Import failed: ' + e.message };
    await loadTableStats();
    await loadPreviewData();
  } finally {
    importing.value = false;
    importCancelled.value = false;
  }
}

function cancelImport() {
  if (confirm('Cancel import? Data imported so far will remain.')) {
    importCancelled.value = true;
  }
}

async function deleteTableData() {
  deleting.value = true;
  deleteStatus.value = null;
  try {
    const response = await fetch(`${base()}/delete/${props.dbId}/${props.tableId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ delete_definition: deleteDefinition.value }),
    });
    const result = await response.json();
    if (result.status === 'success') {
      deleteStatus.value = { status: 'success', message: result.message };
      deleteDefinition.value = false;
      await loadTableStats();
      await loadPreviewData();
      setTimeout(() => {
        showDeleteDialog.value = false;
        deleteStatus.value = null;
      }, 2000);
    } else {
      deleteStatus.value = { status: 'error', message: result.message || 'Delete failed' };
    }
  } catch (e) {
    deleteStatus.value = { status: 'error', message: 'Delete failed: ' + e.message };
  } finally {
    deleting.value = false;
  }
}

function exportToCSV() {
  const offset = (previewPage.value - 1) * previewLimit;
  const url = `${base()}/data/${props.dbId}/${props.tableId}?format=csv&limit=${previewLimit}&offset=${offset}`;
  window.open(url, '_blank');
}

onMounted(() => {
  loadTableStats();
  loadPreviewData();
});

defineExpose({ loadPreviewData, loadTableStats });
</script>
