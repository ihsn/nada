const VueDataExplorer = {
    props: {
        dbId: {
            type: String,
            required: true
        },
        tableId: {
            type: String,
            required: true
        },
        apiBase: {
            type: String,
            required: true
        }
    },
    template: `
        <div>
            <v-card flat>
                <v-card-title>
                    <span>Data Management</span>
                    <v-spacer></v-spacer>
                    <v-btn
                        color="primary"
                        @click="showUploadDialog = true"
                        class="mr-2"
                    >
                        <v-icon left>mdi-upload</v-icon>
                        Upload Data
                    </v-btn>
                    <v-btn
                        v-if="tableStats && tableStats.count > 0"
                        color="error"
                        @click="showDeleteDialog = true"
                    >
                        <v-icon left>mdi-delete</v-icon>
                        Delete Data
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <div v-if="tableStats && tableStats.count !== undefined" class="mb-3">
                        <v-chip small class="mr-2">
                            <v-icon left small>mdi-database</v-icon>
                            Total Rows: {{ tableStats.count.toLocaleString() }}
                        </v-chip>
                        <v-btn
                            small
                            color="primary"
                            @click="loadPreviewData"
                            :loading="previewLoading"
                            class="mr-2"
                        >
                            <v-icon left small>mdi-refresh</v-icon>
                            Refresh
                        </v-btn>
                        <v-btn
                            small
                            color="success"
                            @click="exportToCSV"
                            :disabled="!previewData || previewData.length === 0"
                        >
                            <v-icon left small>mdi-download</v-icon>
                            Export CSV
                        </v-btn>
                    </div>
                    <div v-if="previewLoading" class="text-center py-4">
                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                        <div class="mt-2">Loading data...</div>
                    </div>
                    <div v-else-if="previewError" class="text-center py-4">
                        <v-alert type="error" dense outlined>
                            {{ previewError }}
                        </v-alert>
                    </div>
                    <div v-else-if="previewData && previewData.length > 0">
                        <v-data-table
                            :headers="previewHeaders"
                            :items="truncatedPreviewData"
                            :items-per-page="previewLimit"
                            :page="previewPage"
                            hide-default-footer
                            dense
                            class="elevation-1"
                        ></v-data-table>
                        <div class="d-flex justify-space-between align-center mt-3">
                            <div class="text-caption">
                                Showing {{ ((previewPage - 1) * previewLimit) + 1 }} to {{ Math.min(previewPage * previewLimit, previewTotal) }} of {{ previewTotal }} rows
                            </div>
                            <div>
                                <v-btn
                                    small
                                    @click="previewPage = Math.max(1, previewPage - 1)"
                                    :disabled="previewPage === 1"
                                    class="mr-2"
                                >
                                    <v-icon small>mdi-chevron-left</v-icon>
                                </v-btn>
                                <span class="mx-2">Page {{ previewPage }}</span>
                                <v-btn
                                    small
                                    @click="previewPage = previewPage + 1"
                                    :disabled="previewPage * previewLimit >= previewTotal"
                                >
                                    <v-icon small>mdi-chevron-right</v-icon>
                                </v-btn>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text--secondary">
                        <v-icon large color="grey lighten-1" class="mb-2">mdi-database-off</v-icon>
                        <div>No data available. Click "Upload Data" to upload and import a CSV or ZIP file.</div>
                    </div>
                </v-card-text>
            </v-card>

            <!-- Upload Dialog -->
            <v-dialog v-model="showUploadDialog" max-width="600" :persistent="uploading || deleting || importing">
                <v-card>
                    <v-card-title>
                        <span>Upload CSV or ZIP File</span>
                        <v-spacer></v-spacer>
                        <v-btn icon @click="showUploadDialog = false">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-card-text>
                        <v-alert type="warning" dense outlined class="mb-4">
                            <strong>Warning:</strong> Uploading data will delete all existing data in this table and replace it with the new data. This action cannot be undone.
                        </v-alert>
                        <v-file-input
                            v-model="uploadFile"
                            label="Select CSV or ZIP file"
                            accept=".csv,.zip"
                            outlined
                            dense
                            prepend-icon="mdi-file-upload"
                            show-size
                            :disabled="uploading || deleting || importing"
                        ></v-file-input>
                        <v-switch
                            v-model="syncFields"
                            label="Sync fields after import (remove fields not in data)"
                            dense
                            class="mt-3"
                            :disabled="uploading || deleting || importing"
                        ></v-switch>
                        <div v-if="uploadStatus" class="mt-4">
                            <v-alert
                                :type="uploadStatus.status === 'success' ? 'success' : uploadStatus.status === 'error' ? 'error' : 'info'"
                                dense
                                outlined
                            >
                                <div class="font-weight-medium mb-1">{{ uploadStatus.message }}</div>
                                <div v-if="uploadStatus.file_path" class="text-caption">
                                    File: {{ uploadStatus.file_path }}
                                </div>
                                <div v-if="uploadStatus.csv_uploaded_at" class="text-caption">
                                    Uploaded: {{ uploadStatus.csv_uploaded_at }}
                                </div>
                                <div v-if="uploadStatus.import_status" class="text-caption mt-1">
                                    Status: {{ uploadStatus.import_status }}
                                </div>
                            </v-alert>
                        </div>
                        <div v-if="deleting" class="mt-4">
                            <v-alert type="info" dense outlined>
                                <div class="font-weight-medium mb-1">Deleting existing data...</div>
                            </v-alert>
                        </div>
                        <div v-if="importStatus" class="mt-4">
                            <v-alert
                                :type="importStatus.status === 'success' ? 'success' : importStatus.status === 'error' ? 'error' : importStatus.status === 'warning' ? 'warning' : 'info'"
                                dense
                                outlined
                            >
                                <div class="font-weight-medium mb-2">{{ importStatus.message }}</div>
                                <div v-if="importStatus.progress_percent !== undefined && importing" class="mb-2">
                                    <v-progress-linear
                                        :value="importStatus.progress_percent"
                                        :color="importStatus.status === 'error' ? 'error' : 'primary'"
                                        height="20"
                                        rounded
                                    >
                                        <template v-slot:default="{ value }">
                                            <strong class="white--text">{{ Math.ceil(value) }}%</strong>
                                        </template>
                                    </v-progress-linear>
                                </div>
                                <div v-if="importStatus.total_rows_processed !== undefined" class="text-caption">
                                    Total rows processed: {{ importStatus.total_rows_processed.toLocaleString() }}
                                </div>
                                <div v-if="importStatus.rows_processed" class="text-caption">
                                    This batch: {{ importStatus.rows_processed.toLocaleString() }} rows
                                </div>
                                <div v-if="importStatus.import_status === 'in_progress'" class="text-caption mt-1">
                                    <v-icon small>mdi-loading mdi-spin</v-icon>
                                    Import in progress...
                                </div>
                            </v-alert>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            v-if="importing"
                            text
                            color="error"
                            @click="cancelImport"
                            :disabled="!importing"
                        >
                            <v-icon left>mdi-cancel</v-icon>
                            Cancel Import
                        </v-btn>
                        <v-btn
                            text
                            @click="showUploadDialog = false"
                            :disabled="uploading || deleting || importing"
                        >
                            Close
                        </v-btn>
                        <v-btn
                            color="primary"
                            @click="uploadData"
                            :loading="uploading || deleting || importing"
                            :disabled="!uploadFile || uploading || deleting || importing"
                        >
                            <v-icon left>mdi-upload</v-icon>
                            Upload & Import
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Delete Dialog -->
            <v-dialog v-model="showDeleteDialog" max-width="500" persistent>
                <v-card>
                    <v-card-title class="error white--text">
                        <v-icon left color="white">mdi-alert</v-icon>
                        Delete Data
                    </v-card-title>
                    <v-card-text class="pt-4">
                        <v-alert type="warning" dense outlined class="mb-4">
                            <strong>Warning:</strong> This action will permanently delete all data in this table. This cannot be undone.
                        </v-alert>
                        <div v-if="tableStats && tableStats.count !== undefined" class="mb-3">
                            <div class="text-body-1">
                                Current row count: <strong>{{ tableStats.count.toLocaleString() }}</strong>
                            </div>
                        </div>
                        <v-checkbox
                            v-model="deleteDefinition"
                            label="Also delete table definition"
                            dense
                            class="mt-0"
                        ></v-checkbox>
                        <div v-if="deleteStatus" class="mt-4">
                            <v-alert
                                :type="deleteStatus.status === 'success' ? 'success' : 'error'"
                                dense
                                outlined
                            >
                                <div class="font-weight-medium mb-1">{{ deleteStatus.message }}</div>
                                <div v-if="deleteStatus.data_deleted !== undefined" class="text-caption">
                                    Rows deleted: {{ deleteStatus.data_deleted }}
                                </div>
                            </v-alert>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            text
                            @click="showDeleteDialog = false"
                            :disabled="deleting"
                        >
                            Cancel
                        </v-btn>
                        <v-btn
                            color="error"
                            @click="deleteTableData"
                            :loading="deleting"
                            :disabled="deleting"
                        >
                            <v-icon left>mdi-delete</v-icon>
                            Delete All Data
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </div>
    `,
    data() {
        return {
            uploadFile: null,
            uploading: false,
            importing: false,
            importCancelled: false,
            uploadStatus: null,
            importStatus: null,
            showUploadDialog: false,
            showDeleteDialog: false,
            syncFields: true,
            deleteDefinition: false,
            deleting: false,
            deleteStatus: null,
            tableStats: null,
            previewData: [],
            previewHeaders: [],
            previewLoading: false,
            previewError: null,
            previewLimit: 50,
            previewPage: 1,
            previewTotal: 0
        };
    },
    computed: {
        truncatedPreviewData() {
            if (!this.previewData || this.previewData.length === 0) {
                return [];
            }
            return this.previewData.map(row => {
                const truncatedRow = {};
                for (const key in row) {
                    const value = row[key];
                    if (typeof value === 'string' && value.length > 50) {
                        truncatedRow[key] = value.substring(0, 50) + '...';
                    } else {
                        truncatedRow[key] = value;
                    }
                }
                return truncatedRow;
            });
        }
    },
    watch: {
        previewPage() {
            this.loadPreviewData();
        },
        showUploadDialog(newVal) {
            if (newVal) {
                this.uploadFile = null;
                this.uploadStatus = null;
                this.importStatus = null;
                this.uploading = false;
                this.importing = false;
                this.importCancelled = false;
                this.deleting = false;
            }
        }
    },
    mounted() {
        this.loadTableStats();
        this.loadPreviewData();
    },
    methods: {
        async loadTableStats() {
            try {
                const response = await axios.get(`${this.apiBase}/info/${this.dbId}/${this.tableId}`);
                if (response.data.status === 'success' && response.data.result) {
                    this.tableStats = {
                        count: response.data.result.count || 0
                    };
                }
            } catch (error) {
                console.error('Error loading table stats:', error);
            }
        },
        async loadPreviewData() {
            this.previewLoading = true;
            this.previewError = null;

            try {
                const offset = (this.previewPage - 1) * this.previewLimit;
                const response = await axios.get(`${this.apiBase}/data/${this.dbId}/${this.tableId}`, {
                    params: {
                        limit: this.previewLimit,
                        offset: offset
                    }
                });

                const data = response.data.data || [];
                this.previewData = data;
                this.previewTotal = response.data.total || response.data.found || data.length;

                if (data.length > 0) {
                    this.previewHeaders = Object.keys(data[0]).map(key => ({
                        text: key,
                        value: key,
                        sortable: true
                    }));
                } else {
                    this.previewHeaders = [];
                }
            } catch (error) {
                this.previewError = 'Error loading preview data: ' + (error.response?.data?.message || error.message);
                this.previewData = [];
                this.previewHeaders = [];
            } finally {
                this.previewLoading = false;
            }
        },
        async uploadData() {
            if (!this.uploadFile) {
                alert('Please select a file to upload');
                return;
            }

            this.uploading = true;
            this.uploadStatus = null;
            this.importStatus = null;

            try {
                const formData = new FormData();
                formData.append('file', this.uploadFile);

                const uploadResponse = await fetch(`${this.apiBase}/upload/${this.dbId}/${this.tableId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const uploadResult = await uploadResponse.json();

                if (uploadResult.status === 'success') {
                    this.uploadStatus = {
                        status: 'success',
                        message: uploadResult.message,
                        file_path: uploadResult.file_path,
                        csv_uploaded_at: uploadResult.csv_uploaded_at,
                        import_status: uploadResult.import_status
                    };
                    this.uploadFile = null;

                    this.deleting = true;
                    try {
                        const deleteResponse = await fetch(`${this.apiBase}/delete/${this.dbId}/${this.tableId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                delete_definition: false
                            })
                        });

                        const deleteResult = await deleteResponse.json();
                        
                        if (deleteResult.status === 'success') {
                            this.deleting = false;
                            await this.importData();
                        } else {
                            this.deleting = false;
                            this.uploadStatus = {
                                status: 'error',
                                message: 'Failed to delete existing data: ' + (deleteResult.message || 'Unknown error')
                            };
                            await this.loadTableStats();
                            await this.loadPreviewData();
                        }
                    } catch (deleteError) {
                        this.deleting = false;
                        this.uploadStatus = {
                            status: 'error',
                            message: 'Error deleting existing data: ' + deleteError.message
                        };
                        await this.loadTableStats();
                        await this.loadPreviewData();
                    }
                } else {
                    this.uploadStatus = {
                        status: 'error',
                        message: uploadResult.message || 'Upload failed'
                    };
                    await this.loadTableStats();
                    await this.loadPreviewData();
                }
            } catch (error) {
                this.uploadStatus = {
                    status: 'error',
                    message: 'Upload failed: ' + error.message
                };
                await this.loadTableStats();
                await this.loadPreviewData();
            } finally {
                this.uploading = false;
            }
        },
        async importData() {
            this.importing = true;
            this.importStatus = null;
            this.importCancelled = false;

            try {
                let hasMore = true;
                let importResult = null;

                while (hasMore && !this.importCancelled) {
                    const response = await fetch(`${this.apiBase}/import/${this.dbId}/${this.tableId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            db_id: this.dbId,
                            table_id: this.tableId
                        })
                    });

                    importResult = await response.json();

                    if (importResult.status === 'success') {
                        const progress = importResult.progress || {};
                        const batch = importResult.batch || {};
                        
                        this.importStatus = {
                            status: progress.import_status === 'completed' ? 'success' : 'in_progress',
                            message: progress.import_status === 'completed' 
                                ? 'Import completed successfully' 
                                : `Importing... ${progress.progress_percent || 0}%`,
                            rows_processed: batch.rows_processed || 0,
                            total_rows_processed: progress.total_rows_processed || 0,
                            progress_percent: progress.progress_percent || 0,
                            import_status: progress.import_status || 'in_progress'
                        };

                        hasMore = progress.has_more === true && 
                                  progress.import_status !== 'completed';

                        if (hasMore && !this.importCancelled) {
                            await new Promise(resolve => setTimeout(resolve, 500));
                        }
                    } else {
                        this.importStatus = {
                            status: 'error',
                            message: importResult.message || 'Import failed'
                        };
                        hasMore = false;
                    }
                }

                if (this.importCancelled) {
                    this.importStatus = {
                        status: 'warning',
                        message: 'Import cancelled by user'
                    };
                    await this.loadTableStats();
                    await this.loadPreviewData();
                    return;
                }

                if (importResult && importResult.status === 'success') {
                    if (this.syncFields) {
                        await this.syncFieldsAfterImport();
                    }
                    
                    this.showUploadDialog = false;
                    await this.loadTableStats();
                    await this.loadPreviewData();
                    this.$emit('fields-changed');
                } else {
                    await this.loadTableStats();
                    await this.loadPreviewData();
                }
            } catch (error) {
                this.importStatus = {
                    status: 'error',
                    message: 'Import failed: ' + error.message
                };
                await this.loadTableStats();
                await this.loadPreviewData();
            } finally {
                this.importing = false;
                this.importCancelled = false;
            }
        },
        cancelImport() {
            if (confirm('Are you sure you want to cancel the import? The data imported so far will remain.')) {
                this.importCancelled = true;
            }
        },
        async deleteTableData() {
            this.deleting = true;
            this.deleteStatus = null;

            try {
                const response = await fetch(`${this.apiBase}/delete/${this.dbId}/${this.tableId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        delete_definition: this.deleteDefinition
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    this.deleteStatus = {
                        status: 'success',
                        message: result.message,
                        data_deleted: result.data_deleted
                    };
                    this.deleteDefinition = false;
                    await this.loadTableStats();
                    await this.loadPreviewData();
                    setTimeout(() => {
                        this.showDeleteDialog = false;
                        this.deleteStatus = null;
                    }, 2000);
                } else {
                    this.deleteStatus = {
                        status: 'error',
                        message: result.message || 'Delete failed'
                    };
                }
            } catch (error) {
                this.deleteStatus = {
                    status: 'error',
                    message: 'Delete failed: ' + error.message
                };
            } finally {
                this.deleting = false;
            }
        },
        exportToCSV() {
            const offset = (this.previewPage - 1) * this.previewLimit;
            const url = `${this.apiBase}/data/${this.dbId}/${this.tableId}?format=csv&limit=${this.previewLimit}&offset=${offset}`;
            window.open(url, '_blank');
        },
        async syncFieldsAfterImport() {
            try {
                const response = await axios.post(`${this.apiBase}/fields/${this.dbId}/${this.tableId}/sync`);
                if (response.data.status === 'success') {
                    if (this.importStatus) {
                        const removed = response.data.fields_removed || 0;
                        const added = response.data.fields_added || 0;
                        if (removed > 0 || added > 0) {
                            this.importStatus.message += ` Fields synced: ${removed} removed, ${added} added.`;
                        }
                    }
                }
            } catch (error) {
                console.error('Error syncing fields:', error);
            }
        }
    }
};

