<template>
  <div class="tables-tab-panel pa-4">
    <div class="tables-tab-toolbar">
      <span class="text-subtitle-1 font-weight-medium">Data management</span>
      <v-spacer />
      <v-btn color="primary" size="small" prepend-icon="mdi-upload" @click="openUpload">
        Upload data
      </v-btn>
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <v-btn size="small" variant="outlined" v-bind="menuProps" append-icon="mdi-menu-down">More</v-btn>
        </template>
        <v-list density="compact" min-width="220">
          <v-list-item
            v-if="storedCsv?.available"
            prepend-icon="mdi-file-download"
            title="Download stored CSV"
            @click="downloadStoredCsv"
          />
          <v-list-item
            v-if="storedCsv?.available"
            prepend-icon="mdi-database-refresh"
            title="Re-import stored CSV"
            :disabled="reloading || importing"
            @click="confirmReloadFromStored"
          />
          <v-list-item prepend-icon="mdi-clipboard-check-outline" title="Validate table" @click="runValidation" />
          <v-divider v-if="tableStats && tableStats.count > 0" />
          <v-list-item
            v-if="tableStats && tableStats.count > 0"
            prepend-icon="mdi-delete"
            title="Delete data"
            base-color="error"
            @click="showDeleteDialog = true"
          />
        </v-list>
      </v-menu>
    </div>
    <div>
      <div v-if="tableStats && tableStats.count !== undefined" class="mb-3">
        <v-chip size="small" prepend-icon="mdi-database">
          Total rows: {{ tableStats.count.toLocaleString() }}
        </v-chip>
      </div>

      <div v-if="previewLoading" class="text-center py-4">
        <v-progress-circular indeterminate color="primary" />
        <div class="mt-2">Loading data…</div>
      </div>
      <v-alert v-else-if="previewError" type="error" variant="tonal" density="compact">{{ previewError }}</v-alert>
      <template v-else-if="previewData?.length">
        <div class="tables-data-preview-section">
        <div class="tables-data-preview-wrap">
        <v-data-table
          :headers="previewHeaders"
          :items="truncatedPreviewData"
          :items-per-page="previewLimit"
          hide-default-footer
          density="compact"
          class="elevation-1 tables-data-preview-table"
        >
          <template v-for="h in previewHeaders" :key="`hdr-${h.key}`" #[`header.${h.key}`]>
            <div class="tables-preview-header-cell">
              <div class="tables-preview-header-cell__name">{{ h.fieldName }}</div>
              <div class="text-caption text-medium-emphasis tables-preview-header-cell__meta">
                <template v-if="h.label && h.label !== h.fieldName">{{ h.label }} · </template>
                {{ h.dataType }}
              </div>
            </div>
          </template>
        </v-data-table>
        </div>
        <div class="d-flex justify-space-between align-center mt-3">
          <div class="text-caption">
            Showing {{ (previewPage - 1) * previewLimit + 1 }} to
            {{ Math.min(previewPage * previewLimit, previewTotal) }} of {{ previewTotal }} rows
          </div>
          <div class="d-flex align-center gap-2 tables-preview-pager">
            <v-btn
              size="small"
              variant="text"
              prepend-icon="mdi-chevron-left"
              :disabled="previewPage === 1"
              @click="previewPage = Math.max(1, previewPage - 1)"
            >
              Previous
            </v-btn>
            <span class="text-caption text-medium-emphasis">
              Page {{ previewPage }} of {{ previewTotalPages }}
            </span>
            <v-btn
              size="small"
              variant="text"
              append-icon="mdi-chevron-right"
              :disabled="previewPage >= previewTotalPages"
              @click="previewPage++"
            >
              Next
            </v-btn>
          </div>
        </div>
        </div>
      </template>
      <div v-else class="text-center py-8 text-medium-emphasis">
        <v-icon size="48" color="grey" class="mb-2">mdi-database-off</v-icon>
        <div>No data available. Click "Upload data" to upload and import a CSV or ZIP file.</div>
      </div>
    </div>

    <v-dialog v-model="showUploadDialog" max-width="600" :persistent="uploading || deleting || importing">
      <v-card>
        <v-card-title class="d-flex align-center">
          Upload CSV or ZIP file
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" :disabled="uploading || deleting || importing" @click="showUploadDialog = false" />
        </v-card-title>
        <v-card-text>
          <p class="text-body-2 text-medium-emphasis mt-2 mb-4">
            Uploading data will delete all existing data in this table and replace it. This cannot be undone.
          </p>
          <TablesFormField label="CSV or ZIP file" required>
            <v-file-input
              v-model="uploadFile"
              accept=".csv,.zip"
              variant="outlined"
              density="compact"
              :prepend-icon="false"
              prepend-inner-icon="mdi-file-upload"
              placeholder="Choose file…"
              show-size
              clearable
              hide-details
              :disabled="uploading || deleting || importing"
            />
          </TablesFormField>
          <TablesFormField label="Sync fields after import" hint="Remove dictionary fields that are not present in the uploaded data">
            <v-switch
              v-model="syncFieldsAfterImport"
              color="primary"
              density="compact"
              hide-details
              :disabled="uploading || deleting || importing"
            />
          </TablesFormField>
          <v-alert v-if="uploadStatus" :type="uploadAlertType" variant="tonal" density="compact" class="mt-4">
            <div class="font-weight-medium mb-1">{{ uploadStatus.message }}</div>
            <div v-if="uploadStatus.file_path" class="text-caption">File: {{ uploadStatus.file_path }}</div>
            <v-progress-linear
              v-if="uploadStatus.progress_percent !== undefined && uploading"
              :model-value="uploadStatus.progress_percent"
              height="20"
              rounded
              class="mt-2"
            />
          </v-alert>
          <v-alert v-if="deleting" type="info" variant="tonal" density="compact" class="mt-4">
            Deleting existing data…
          </v-alert>
          <v-alert v-if="importStatus" :type="importAlertType" variant="tonal" density="compact" class="mt-4">
            <div class="font-weight-medium mb-2">{{ importStatus.message }}</div>
            <div v-if="importStatus.summary" class="text-caption mb-2">{{ importStatus.summary }}</div>
            <v-progress-linear
              v-if="importStatus.progress_percent !== undefined && importing"
              :model-value="importStatus.progress_percent"
              height="20"
              rounded
            />
            <v-btn
              v-if="importStatus.errors_count > 0 && !importing"
              size="small"
              class="mt-2"
              variant="outlined"
              prepend-icon="mdi-download"
              :loading="downloadingErrors"
              @click="downloadErrors"
            >
              Download skipped-row log ({{ importStatus.errors_count.toLocaleString() }})
            </v-btn>
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
            :disabled="!selectedUploadFile || uploading || deleting || importing"
            prepend-icon="mdi-upload"
            @click="uploadData"
          >
            {{ resumeUploadId && uploadStatus?.status === 'error' ? 'Retry upload' : 'Upload & import' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showValidateDialog" max-width="720">
      <v-card>
        <v-card-title class="d-flex align-center">
          Table validation
          <v-spacer />
          <v-btn icon="mdi-close" variant="text" @click="showValidateDialog = false" />
        </v-card-title>
        <v-card-text>
          <v-alert
            v-if="validationReport"
            :type="validationReport.valid ? 'success' : 'warning'"
            variant="tonal"
            density="compact"
            class="mb-4"
          >
            <span v-if="validationReport.valid">No errors found.</span>
            <span v-else>Validation found {{ validationReport.summary?.errors || 0 }} error(s).</span>
            <span v-if="validationReport.summary?.warnings">
              {{ validationReport.summary.warnings }} warning(s).
            </span>
          </v-alert>
          <v-alert v-if="validationError" type="error" variant="tonal" density="compact">{{ validationError }}</v-alert>
          <v-list v-if="validationReport?.issues?.length" density="compact">
            <v-list-item v-for="(issue, idx) in validationReport.issues" :key="idx">
              <template #prepend>
                <v-icon :color="issue.severity === 'error' ? 'error' : 'warning'" size="small">
                  {{ issue.severity === 'error' ? 'mdi-alert-circle' : 'mdi-alert' }}
                </v-icon>
              </template>
              <v-list-item-title class="text-body-2">{{ issue.message }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ issue.section }} · {{ issue.code }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <div v-else-if="validationReport && !validationReport.issues?.length" class="text-medium-emphasis">
            All checks passed.
          </div>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showValidateDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showReloadDialog" max-width="520" :persistent="reloading || importing">
      <v-card>
        <v-card-title>Re-import stored CSV</v-card-title>
        <v-card-text>
          <v-alert type="warning" variant="tonal" density="compact" class="mb-3">
            This deletes all rows in the table and imports again from the CSV file saved at upload time.
          </v-alert>
          <v-alert v-if="reloadStatus" :type="importAlertType" variant="tonal" density="compact">
            <div class="font-weight-medium mb-1">{{ reloadStatus.message }}</div>
            <div v-if="reloadStatus.summary" class="text-caption mb-2">{{ reloadStatus.summary }}</div>
            <v-progress-linear
              v-if="reloadStatus.progress_percent !== undefined && (reloading || importing)"
              :model-value="reloadStatus.progress_percent"
              height="20"
              rounded
            />
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" :disabled="reloading || importing" @click="showReloadDialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="reloading || importing" prepend-icon="mdi-database-refresh" @click="reloadFromStoredCsv">
            Re-import
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
          <TablesFormField label="Also delete table definition">
            <v-checkbox v-model="deleteDefinition" density="compact" hide-details />
          </TablesFormField>
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

    <v-dialog v-model="showCancelImportDialog" max-width="440">
      <v-card>
        <v-card-title>Cancel import?</v-card-title>
        <v-card-text>Data imported so far will remain in the table.</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showCancelImportDialog = false">Keep importing</v-btn>
          <v-btn color="error" variant="flat" @click="confirmCancelImport">Cancel import</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, inject } from 'vue';
import axios from 'axios';
import { useTablesApi } from '../composables/useTablesApi';
import TablesFormField from './TablesFormField.vue';
import { normalizeFieldFromApi } from '../utils/fieldUtils';

const props = defineProps({
  dbId: { type: String, required: true },
  tableId: { type: String, required: true },
});

const emit = defineEmits(['fields-changed', 'stats-updated']);
const setMessage = inject('setMessage', () => {});

const api = useTablesApi();
const showCancelImportDialog = ref(false);
const base = () => api.base();

/** v-file-input model: File | File[] | null */
const uploadFile = ref(null);
const resumeUploadId = ref(null);
const uploading = ref(false);
const importing = ref(false);
const importCancelled = ref(false);
const uploadStatus = ref(null);
const importStatus = ref(null);
const downloadingErrors = ref(false);
const showUploadDialog = ref(false);
const showDeleteDialog = ref(false);
const syncFieldsAfterImport = ref(true);
const deleteDefinition = ref(false);
const deleting = ref(false);
const deleteStatus = ref(null);
const tableStats = ref(null);
const storedCsv = ref(null);
const validating = ref(false);
const showValidateDialog = ref(false);
const validationReport = ref(null);
const validationError = ref(null);
const showReloadDialog = ref(false);
const reloading = ref(false);
const reloadStatus = ref(null);
const previewData = ref([]);
const previewHeaders = ref([]);
const previewLoading = ref(false);
const previewError = ref(null);
const previewLimit = 15;
const fieldMetaByName = ref(new Map());
const previewPage = ref(1);
const previewTotal = ref(0);

const previewTotalPages = computed(() =>
  Math.max(1, Math.ceil(previewTotal.value / previewLimit) || 1)
);

const selectedUploadFile = computed(() => {
  const f = uploadFile.value;
  if (!f) return null;
  return Array.isArray(f) ? f[0] ?? null : f;
});
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

function formatImportCounts(progress) {
  const inserted = progress.rows_inserted ?? progress.total_rows_processed ?? 0;
  const read = progress.csv_records_read ?? 0;
  const blank = progress.rows_blank ?? 0;
  const failed = progress.rows_failed ?? 0;
  const warned = progress.rows_warned ?? 0;
  const parts = [`${inserted.toLocaleString()} inserted`];
  if (read) parts.unshift(`${read.toLocaleString()} CSV rows read`);
  if (blank) parts.push(`${blank.toLocaleString()} blank`);
  if (failed) parts.push(`${failed.toLocaleString()} failed`);
  if (warned) parts.push(`${warned.toLocaleString()} extra-column warnings`);
  return parts.join(' · ');
}

function importUiStatus(progress) {
  const status = progress.import_status || 'in_progress';
  if (status === 'completed') return 'success';
  if (status === 'completed_with_errors') return 'warning';
  if (status === 'failed') return 'error';
  return 'in_progress';
}

function importHeadline(progress, fallbackMessage) {
  const status = progress.import_status || 'in_progress';
  if (status === 'completed') return fallbackMessage || 'Import completed';
  if (status === 'completed_with_errors') return fallbackMessage || 'Import completed with skipped or failed rows';
  if (status === 'failed') return fallbackMessage || 'Import finished with an accounting mismatch';
  return `Importing… ${progress.progress_percent || 0}%`;
}

async function downloadErrors() {
  downloadingErrors.value = true;
  try {
    await api.downloadImportErrors(props.dbId, props.tableId);
  } catch (e) {
    importStatus.value = {
      ...importStatus.value,
      status: 'error',
      message: (importStatus.value?.message || 'Import finished') + ' (could not download error log: ' + (e.message || 'error') + ')',
    };
  } finally {
    downloadingErrors.value = false;
  }
}

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
    uploadFile.value = null;
    resumeUploadId.value = null;
    uploadStatus.value = null;
    importStatus.value = null;
    uploading.value = false;
    importing.value = false;
    importCancelled.value = false;
    deleting.value = false;
  }
});
watch(selectedUploadFile, (file, prev) => {
  if (file !== prev && !uploading.value) {
    resumeUploadId.value = null;
  }
});

function openUpload() {
  showUploadDialog.value = true;
}

async function loadTableStats() {
  try {
    const result = await api.fetchTableInfo(props.dbId, props.tableId);
    const count = result.count || 0;
    tableStats.value = { count };
    storedCsv.value = result.stored_csv || null;
    emit('stats-updated', count);
  } catch (e) {
    console.error('Error loading table stats:', e);
  }
}

function downloadStoredCsv() {
  window.open(api.storedCsvDownloadUrl(props.dbId, props.tableId), '_blank');
}

function confirmReloadFromStored() {
  reloadStatus.value = null;
  showReloadDialog.value = true;
}

async function runValidation() {
  validating.value = true;
  validationError.value = null;
  validationReport.value = null;
  showValidateDialog.value = true;
  try {
    const data = await api.validateTable(props.dbId, props.tableId);
    validationReport.value = {
      valid: data.valid,
      summary: data.summary,
      issues: data.issues || [],
      context: data.context,
    };
  } catch (e) {
    validationError.value = e.response?.data?.message || e.message || 'Validation failed';
  } finally {
    validating.value = false;
  }
}

async function reloadFromStoredCsv() {
  reloading.value = true;
  importing.value = true;
  reloadStatus.value = { status: 'in_progress', message: 'Starting re-import…', progress_percent: 0 };
  try {
    await api.runImportChunks(props.dbId, props.tableId, 'import/reload', {
      onProgress: (importResult, progress) => {
        const terminal = ['completed', 'completed_with_errors', 'failed'].includes(progress.import_status);
        reloadStatus.value = {
          status: importUiStatus(progress),
          message: terminal
            ? importResult.message || importHeadline(progress)
            : importHeadline(progress),
          summary: formatImportCounts(progress),
          progress_percent: progress.progress_percent || 0,
          import_status: progress.import_status,
          errors_count: progress.errors_count || 0,
        };
      },
    });
    showReloadDialog.value = false;
    await refreshFieldMetaMap();
    await loadTableStats();
    await loadPreviewData();
    emit('fields-changed');
  } catch (e) {
    setMessage(e.response?.data?.message || e.message || 'Re-import failed', 'error');
  } finally {
    reloading.value = false;
    importing.value = false;
  }
}

async function refreshFieldMetaMap() {
  try {
    const raw = await api.fetchFields(props.dbId, props.tableId);
    const map = new Map();
    raw.map(normalizeFieldFromApi).forEach((field) => {
      map.set(field.name, field);
    });
    fieldMetaByName.value = map;
  } catch {
    fieldMetaByName.value = new Map();
  }
}

function buildPreviewHeaders(rowKeys) {
  const map = fieldMetaByName.value;
  return rowKeys.map((key) => {
    const meta = map.get(key);
    const dataType = meta?.data_type || 'string';
    const label = (meta?.label || '').trim();
    return {
      title: key,
      key,
      sortable: true,
      fieldName: key,
      label,
      dataType,
    };
  });
}

async function loadPreviewData() {
  previewLoading.value = true;
  previewError.value = null;
  try {
    if (!fieldMetaByName.value.size) {
      await refreshFieldMetaMap();
    }
    const offset = (previewPage.value - 1) * previewLimit;
    const { data } = await axios.get(`${base()}/data/${props.dbId}/${props.tableId}`, {
      params: { limit: previewLimit, offset },
    });
    const rows = data.data || [];
    previewData.value = rows;
    previewTotal.value = data.total || data.found || rows.length;
    previewHeaders.value = rows.length > 0 ? buildPreviewHeaders(Object.keys(rows[0])) : [];
  } catch (e) {
    previewError.value = 'Error loading preview: ' + (e.response?.data?.message || e.message);
    previewData.value = [];
    previewHeaders.value = [];
  } finally {
    previewLoading.value = false;
  }
}

async function uploadData() {
  const file = selectedUploadFile.value;
  if (!file) {
    setMessage('Please select a file to upload', 'warning');
    return;
  }
  uploading.value = true;
  uploadStatus.value = { status: 'in_progress', message: 'Uploading file…', progress_percent: 0 };
  importStatus.value = null;
  try {
    const uploadResult = await api.uploadTableFile(
      props.dbId,
      props.tableId,
      file,
      ({ loaded, total, chunkIndex, totalChunks, attempt, maxAttempts }) => {
        const pct = total ? Math.round((loaded / total) * 100) : 0;
        let message = `Uploading file… ${pct}%`;
        if (Number.isInteger(chunkIndex) && totalChunks) {
          message += ` (chunk ${chunkIndex + 1} of ${totalChunks}`;
          if (attempt > 1) {
            message += `, retry ${attempt} of ${maxAttempts}`;
          }
          message += ')';
        }
        uploadStatus.value = { status: 'in_progress', message, progress_percent: pct };
      },
      {
        uploadId: resumeUploadId.value,
        onSession: ({ upload_id }) => {
          resumeUploadId.value = upload_id;
        },
      }
    );
    if (uploadResult.status === 'success') {
      uploadStatus.value = {
        status: 'success',
        message: uploadResult.message,
        file_path: uploadResult.file_path,
      };
      uploadFile.value = null;
      resumeUploadId.value = null;
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
    if (e.uploadId) {
      resumeUploadId.value = e.uploadId;
    }
    uploadStatus.value = {
      status: 'error',
      message: 'Upload failed: ' + (e.response?.data?.message || e.message),
    };
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
    let importResult = null;
    importResult = await api.runImportChunks(props.dbId, props.tableId, 'import', {
      shouldCancel: () => importCancelled.value,
      onProgress: (result, progress) => {
        if (importCancelled.value) {
          return;
        }
        const terminal = ['completed', 'completed_with_errors', 'failed'].includes(progress.import_status);
        importStatus.value = {
          status: importUiStatus(progress),
          message: terminal ? result.message || importHeadline(progress) : importHeadline(progress),
          summary: formatImportCounts(progress),
          progress_percent: progress.progress_percent || 0,
          import_status: progress.import_status || 'in_progress',
          errors_count: progress.errors_count || 0,
        };
      },
    });
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
      const hadRowIssues = (importStatus.value?.errors_count || 0) > 0
        || importStatus.value?.import_status === 'completed_with_errors'
        || importStatus.value?.import_status === 'failed';
      if (!hadRowIssues) {
        showUploadDialog.value = false;
        setMessage(importStatus.value?.message || 'Import completed', 'success');
      }
      await refreshFieldMetaMap();
      await loadTableStats();
      await loadPreviewData();
      emit('fields-changed');
    } else {
      await loadTableStats();
      await loadPreviewData();
    }
  } catch (e) {
    const msg = e?.response?.data?.message || e.message || 'Import failed';
    importStatus.value = { status: 'error', message: msg };
    setMessage(msg, 'error');
    await loadTableStats();
    await loadPreviewData();
  } finally {
    importing.value = false;
    importCancelled.value = false;
  }
}

function cancelImport() {
  showCancelImportDialog.value = true;
}

function confirmCancelImport() {
  importCancelled.value = true;
  showCancelImportDialog.value = false;
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
      setMessage(result.message || 'Data deleted', 'success');
      await loadTableStats();
      await loadPreviewData();
      setTimeout(() => {
        showDeleteDialog.value = false;
        deleteStatus.value = null;
      }, 1500);
    } else {
      deleteStatus.value = { status: 'error', message: result.message || 'Delete failed' };
    }
  } catch (e) {
    deleteStatus.value = { status: 'error', message: 'Delete failed: ' + e.message };
  } finally {
    deleting.value = false;
  }
}

onMounted(() => {
  loadTableStats();
  loadPreviewData();
});

defineExpose({ loadPreviewData, loadTableStats, refreshFieldMetaMap });
</script>

<style scoped>
.tables-data-preview-section {
  margin-top: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 0.25rem;
}

.tables-data-preview-wrap {
  margin-top: 0;
}

.tables-data-preview-table :deep(thead th) {
  background-color: #f3f5f8 !important;
  vertical-align: bottom;
}

.tables-data-preview-table :deep(thead) {
  box-shadow: inset 0 -1px 0 rgba(0, 0, 0, 0.08);
}

.tables-preview-header-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  line-height: 1.25;
  padding: 4px 0;
  min-width: 5rem;
}

.tables-preview-header-cell__name {
  font-weight: 600;
  font-size: 0.8125rem;
}

.tables-preview-header-cell__meta {
  font-size: 0.7rem;
  white-space: normal;
}

.tables-preview-pager .v-btn {
  min-width: auto;
  text-transform: none;
  letter-spacing: normal;
}
</style>
