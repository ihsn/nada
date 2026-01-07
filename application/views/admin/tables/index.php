<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    
    <!-- Vuetify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    
    <!-- Vue.js and Axios -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
    <script src="https://unpkg.com/vue-router@3/dist/vue-router.js"></script>
    
    <style>
        .v-application {
            background-color: #f5f5f5 !important;
        }
        .action-buttons {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        .modal-body .form-group {
            margin-bottom: 1rem;
        }
        .field-row {
            border-bottom: 1px solid #e0e0e0;
            padding: 0.5rem 0;
        }
        .field-row:last-child {
            border-bottom: none;
        }
        .field-list-item {
            cursor: pointer;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .field-list-item:hover {
            background-color: #f5f5f5;
        }
        .field-list-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid #1976D2;
        }
        .search-field-compact >>> .v-input__control {
            min-height: 32px !important;
        }
        .search-field-compact >>> .v-input__slot {
            min-height: 32px !important;
            padding: 0 8px !important;
        }
        .search-field-compact >>> input {
            font-size: 13px !important;
            padding: 4px 0 !important;
        }
    </style>
</head>
<body>
    <v-app id="app">
        <router-view></router-view>
    </v-app>

    <script>
        const apiBase = '<?php echo $api_base_url; ?>';
        const adminDashboardUrl = '<?php echo base_url('admin/dashboard'); ?>';
        const studyEditBaseUrl = '<?php echo site_url('catalog/'); ?>';
        const pageTitle = '<?php echo $title; ?>';

        // Tables List Component
        const TablesList = {
            template: `
                <div>
                    <v-main>
                        <v-container fluid>
                            <div class="mb-4 d-flex align-center justify-space-between">
                                <h1 class="mb-0">Tables</h1>
                                <div class="d-flex align-center">                                    
                                    <v-btn color="primary" @click="$router.push('/create')">
                                        <v-icon left>mdi-plus</v-icon>
                                        Create Table
                                    </v-btn>
                                </div>
                            </div>

                            <v-alert v-if="error" type="error" dismissible @input="error = ''" class="mb-4">
                                {{ error }}
                            </v-alert>

                            <v-alert v-if="success" type="success" dismissible @input="success = ''" class="mb-4">
                                {{ success }}
                            </v-alert>

                            <v-card v-if="loading && tables.length === 0">
                                <v-card-text class="text-center py-12">
                                    <v-progress-circular indeterminate color="primary" size="64" class="mb-4"></v-progress-circular>
                                    <p class="text--secondary mt-4">Loading tables...</p>
                                </v-card-text>
                            </v-card>

                            <v-card v-else-if="tables.length > 0">
                                <div class="text--secondary mb-4 mt-4 ml-4 pt-4" v-if="totalTables > 0">Total: {{ totalTables }} table(s)</div>
                                <v-card-text class="pa-0">
                                    <v-data-table
                                        :headers="tableHeaders"
                                        :items="tables"
                                        :items-per-page="itemsPerPage"
                                        :page="currentPage"
                                        :server-items-length="totalTables"
                                        :loading="loading"
                                        @update:page="handlePageChange"
                                        @update:items-per-page="handleItemsPerPageChange"
                                        class="elevation-0"
                                        item-key="table_key"
                                    >
                                        <template v-slot:item.table_id="{ item }">
                                            {{ item._id || item.table_id }}
                                        </template>
                                        <template v-slot:item.db_id="{ item }">
                                            {{ item.db_id || 'N/A' }}
                                        </template>
                                        <template v-slot:item.title="{ item }">
                                            <a @click="editTable(item)" style="color: #1976D2; text-decoration: none; cursor: pointer;">
                                                {{ item.title || item.metadata?.title || 'N/A' }}
                                            </a>
                                        </template>
                                        <template v-slot:item.rows_count="{ item }">
                                            {{ formatNumber(item.rows_count || 0) }}
                                        </template>
                                        <template v-slot:item.storage_size="{ item }">
                                            {{ item.storage_size || 'N/A' }}
                                        </template>
                                        <template v-slot:item.nindexes="{ item }">
                                            <v-chip small color="info" v-if="item.nindexes">
                                                {{ item.nindexes }}
                                            </v-chip>
                                            <span v-else class="text--secondary">0</span>
                                        </template>
                                        <template v-slot:item.created_at="{ item }">
                                            <span v-if="item.created_at" class="text--secondary" style="font-size: 12px;">
                                                {{ formatDate(item.created_at) }}
                                            </span>
                                            <span v-else class="text--secondary" style="font-size: 12px;">N/A</span>
                                        </template>
                                        <template v-slot:item.updated_at="{ item }">
                                            <span v-if="item.updated_at" class="text--secondary" style="font-size: 12px;">
                                                {{ formatDate(item.updated_at) }}
                                            </span>
                                            <span v-else class="text--secondary" style="font-size: 12px;">N/A</span>
                                        </template>
                                        <template v-slot:item.actions="{ item }">
                                            <v-menu offset-y left :min-width="180">
                                                <template v-slot:activator="{ on, attrs }">
                                                    <v-btn icon v-bind="attrs" v-on="on">
                                                        <v-icon>mdi-dots-vertical</v-icon>
                                                    </v-btn>
                                                </template>
                                                <v-list dense style="min-width: 180px;">
                                                    <v-list-item @click="editTable(item)" class="px-2">
                                                        <v-list-item-icon class="mr-2" style="min-width: 32px;">
                                                            <v-icon small>mdi-pencil</v-icon>
                                                        </v-list-item-icon>
                                                        <v-list-item-content class="py-0">
                                                            <v-list-item-title style="font-size: 14px;">Edit</v-list-item-title>
                                                        </v-list-item-content>
                                                    </v-list-item>
                                                    <v-divider></v-divider>
                                                    <v-list-item :href="getApiUrl(item, 'info')" target="_blank" class="px-2">
                                                        <v-list-item-icon class="mr-2" style="min-width: 32px;">
                                                            <v-icon small>mdi-information</v-icon>
                                                        </v-list-item-icon>
                                                        <v-list-item-content class="py-0">
                                                            <v-list-item-title style="font-size: 14px;">Info</v-list-item-title>
                                                        </v-list-item-content>
                                                    </v-list-item>
                                                    <v-list-item :href="getApiUrl(item, 'fields')" target="_blank" class="px-2">
                                                        <v-list-item-icon class="mr-2" style="min-width: 32px;">
                                                            <v-icon small>mdi-format-list-bulleted</v-icon>
                                                        </v-list-item-icon>
                                                        <v-list-item-content class="py-0">
                                                            <v-list-item-title style="font-size: 14px;">Fields</v-list-item-title>
                                                        </v-list-item-content>
                                                    </v-list-item>
                                                    <v-list-item :href="getApiUrl(item, 'data')" target="_blank" class="px-2">
                                                        <v-list-item-icon class="mr-2" style="min-width: 32px;">
                                                            <v-icon small>mdi-database</v-icon>
                                                        </v-list-item-icon>
                                                        <v-list-item-content class="py-0">
                                                            <v-list-item-title style="font-size: 14px;">Data</v-list-item-title>
                                                        </v-list-item-content>
                                                    </v-list-item>
                                                    <v-list-item @click="exportDefinition(item)" class="px-2">
                                                        <v-list-item-icon class="mr-2" style="min-width: 32px;">
                                                            <v-icon small>mdi-download</v-icon>
                                                        </v-list-item-icon>
                                                        <v-list-item-content class="py-0">
                                                            <v-list-item-title style="font-size: 14px;">Export Definition</v-list-item-title>
                                                        </v-list-item-content>
                                                    </v-list-item>
                                                    <v-divider></v-divider>
                                                    <v-list-item @click="deleteTable(item)" class="px-2">
                                                        <v-list-item-icon class="mr-2" style="min-width: 32px;">
                                                            <v-icon small color="error">mdi-delete</v-icon>
                                                        </v-list-item-icon>
                                                        <v-list-item-content class="py-0">
                                                            <v-list-item-title class="error--text" style="font-size: 14px;">Delete</v-list-item-title>
                                                        </v-list-item-content>
                                                    </v-list-item>
                                                </v-list>
                                            </v-menu>
                                        </template>
                                    </v-data-table>
                                </v-card-text>
                            </v-card>

                            <v-card v-else>
                                <v-card-text class="text-center py-12">
                                    <v-icon size="64" color="grey lighten-1" class="mb-4">mdi-database-off</v-icon>
                                    <h5 class="mb-2">No Tables Found</h5>
                                    <p class="text--secondary mb-4">No tables are currently available in the database.</p>
                                    <v-btn color="primary" @click="$router.push('/create')">
                                        <v-icon left>mdi-plus</v-icon>
                                        Create Your First Table
                                    </v-btn>
                                </v-card-text>
                            </v-card>
                        </v-container>
                    </v-main>

                </div>
            `,
            data() {
                return {
                    adminDashboardUrl: adminDashboardUrl,
                    tables: [],
                    loading: false,
                    error: '',
                    success: '',
                    currentPage: 1,
                    itemsPerPage: 15,
                    totalTables: 0,
                    tableHeaders: [
                        { text: 'Title', value: 'title', sortable: true },
                        { text: 'Table ID', value: 'table_id', sortable: true },
                        { text: 'Database ID', value: 'db_id', sortable: true },
                        { text: 'Rows', value: 'rows_count', sortable: true, align: 'end' },
                        { text: 'Size', value: 'storage_size', sortable: false },
                        { text: 'Indexes', value: 'nindexes', sortable: false, align: 'center' },
                        { text: 'Created', value: 'created_at', sortable: true },
                        { text: 'Updated', value: 'updated_at', sortable: true },
                        { text: 'Actions', value: 'actions', sortable: false, align: 'center', width: '80px' }
                    ]
                };
            },
            mounted() {
                this.loadTables();
            },
            methods: {
                async loadTables() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const offset = (this.currentPage - 1) * this.itemsPerPage;
                        const response = await axios.get(apiBase, {
                            params: {
                                limit: this.itemsPerPage,
                                offset: offset
                            }
                        });
                        if (response.data.status === 'success') {
                            const tablesObj = response.data.tables || {};
                            this.tables = Object.values(tablesObj).map(table => ({
                                ...table,
                                _id: table._id || table.table_id,
                                table_key: table._id || table.table_id || `table_${Math.random()}`
                            }));
                            this.totalTables = response.data.total || 0;
                        } else {
                            this.error = response.data.message || 'Failed to load tables';
                        }
                    } catch (error) {
                        this.error = 'Error loading tables: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.loading = false;
                    }
                },
                handlePageChange(page) {
                    this.currentPage = page;
                    this.loadTables();
                },
                handleItemsPerPageChange(itemsPerPage) {
                    this.itemsPerPage = itemsPerPage;
                    this.currentPage = 1; // Reset to first page when changing page size
                    this.loadTables();
                },
                editTable(table) {
                    const tableId = table.table_id || table._id;
                    const dbId = table.db_id;
                    this.$router.push({ name: 'edit', params: { db_id: dbId, table_id: tableId } });
                },
                getApiUrl(table, endpoint) {
                    const dbId = table.db_id || table.metadata?.db_id;
                    const tableId = table.table_id || table.metadata?.table_id || table._id?.replace(`table_${dbId}_`, '');
                    
                    const endpoints = {
                        'info': `${apiBase}/info/${dbId}/${tableId}`,
                        'fields': `${apiBase}/fields/${dbId}/${tableId}`,
                        'data': `${apiBase}/data/${dbId}/${tableId}`
                    };
                    
                    return endpoints[endpoint] || '';
                },
                exportDefinition(table) {
                    const dbId = table.db_id || table.metadata?.db_id;
                    const tableId = table.table_id || table.metadata?.table_id || table._id?.replace(`table_${dbId}_`, '');
                    const url = `${apiBase}/export_definition/${dbId}/${tableId}`;
                    window.open(url, '_blank');
                },
                viewTableInfo(table) {
                    // Placeholder - implement if needed
                },
                viewDataDictionary(table) {
                    // Placeholder - implement if needed
                },
                manageIndexes(table) {
                    // Placeholder - implement if needed
                },
                uploadTableData(table) {
                    // Placeholder - implement if needed
                },
                async deleteTable(table) {
                    const tableName = table._id || table.table_id;
                    if (!confirm(`Delete table "${tableName}"?\n\nThis will permanently delete:\n- Table data\n- Table fields (data dictionary)\n- Table definition\n\nThis action cannot be undone.`)) return;
                    
                    try {
                        const response = await axios.post(
                            `${apiBase}/delete/${table.db_id}/${table.table_id}`,
                            { delete_definition: true }
                        );
                        if (response.data.status === 'success') {
                            this.success = 'Table, fields, and data deleted successfully';
                            this.loadTables();
                        } else {
                            this.error = response.data.message || 'Failed to delete table';
                        }
                    } catch (error) {
                        this.error = 'Error deleting table: ' + (error.response?.data?.message || error.message);
                    }
                },
                formatNumber(num) {
                    return new Intl.NumberFormat().format(num);
                },
                formatDate(dateString) {
                    if (!dateString) return 'N/A';
                    try {
                        const date = new Date(dateString);
                        if (isNaN(date.getTime())) return dateString;
                        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } catch (e) {
                        return dateString;
                    }
                }
            }
        };

        // Edit Table Component
        const EditTable = {
            template: `
                <div>
                    <div class="mb-3">
                        <v-btn text @click="$router.push('/')" class="mb-2">
                            <v-icon left>mdi-arrow-left</v-icon>
                            Back to Tables
                        </v-btn>
                        <h2 class="pl-4">Edit Table</h2>
                    </div>
                    <v-main>
                        <v-container fluid>
                            <v-alert v-if="error" type="error" dismissible @input="error = ''" class="mb-4">
                                {{ error }}
                            </v-alert>

                            <v-alert v-if="success" type="success" dismissible @input="success = ''" class="mb-4">
                                {{ success }}
                            </v-alert>

                            <v-card v-if="loading" class="mb-4">
                                <v-card-text class="text-center py-12">
                                    <v-progress-circular indeterminate color="primary" size="64" class="mb-4"></v-progress-circular>
                                    <p class="text--secondary mt-4">Loading table information...</p>
                                </v-card-text>
                            </v-card>

                            <template v-else>
                                <v-card>
                                    <v-tabs v-model="activeTab">
                                        <v-tab>Table Information</v-tab>
                                        <v-tab>Data Management</v-tab>
                                        <v-tab>Data Dictionary</v-tab>
                                        <v-tab>Indexes</v-tab>
                                        <v-tab>Study Links</v-tab>
                                    </v-tabs>

                                    <v-tabs-items v-model="activeTab">
                                        <!-- Tab 1: Table Information -->
                                        <v-tab-item>
                                            <v-card flat>
                                                <v-card-title>
                                                    <span>Table Information</span>
                                                    <v-spacer></v-spacer>
                                                    <v-btn 
                                                        color="primary" 
                                                        small 
                                                        @click="saveTableInfo" 
                                                        :loading="savingTableInfo"
                                                    >
                                                        <v-icon left small>mdi-content-save</v-icon>
                                                        Save Changes
                                                    </v-btn>
                                                </v-card-title>
                                                <v-card-text>
                                                    <v-row>
                                                        <v-col cols="12" md="6">
                                                            <v-text-field
                                                                v-model="tableInfo.db_id"
                                                                label="Database ID"
                                                                readonly
                                                                outlined
                                                                dense
                                                            ></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" md="6">
                                                            <v-text-field
                                                                v-model="tableInfo.table_id"
                                                                label="Table ID"
                                                                readonly
                                                                outlined
                                                                dense
                                                            ></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12">
                                                            <v-text-field
                                                                v-model="tableInfo.title"
                                                                label="Title"
                                                                outlined
                                                                dense
                                                            ></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12">
                                                            <v-textarea
                                                                v-model="tableInfo.description"
                                                                label="Description"
                                                                outlined
                                                                rows="3"
                                                            ></v-textarea>
                                                        </v-col>
                                                    </v-row>
                                                </v-card-text>
                                            </v-card>
                                        </v-tab-item>

                                        <!-- Tab 2: Data Management -->
                                        <v-tab-item>
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
                                        </v-tab-item>

                                        <!-- Tab 3: Data Dictionary -->
                                        <v-tab-item>
                                            <v-card flat>
                                                <v-card-title>
                                                    <span>Data Dictionary Fields</span>
                                                    <v-spacer></v-spacer>
                                                    <v-btn color="warning" small @click="syncDataDictionary" :loading="syncingFields" class="mr-2">
                                                        <v-icon left small>mdi-sync</v-icon>
                                                        Sync Fields
                                                    </v-btn>
                                                    <v-btn color="primary" small @click="populateSchema" :loading="populatingSchema" class="ml-2">
                                                        <v-icon left small>mdi-database-refresh</v-icon>
                                                        Populate from Data
                                                    </v-btn>
                                                    <v-btn color="success" small @click="showAddFieldDialog = true" class="ml-2">
                                                        <v-icon left small>mdi-plus</v-icon>
                                                        Add Field
                                                    </v-btn>
                                                </v-card-title>
                                    <v-card-text class="pa-0" style="height: calc(100vh - 200px); min-height: 500px;">
                                        <v-row no-gutters style="height: 100%;">
                                            <!-- Left Column - Field List (40%) -->
                                            <v-col cols="12" md="5" style="border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; height: 100%;">
                                                <!-- Search and Controls -->
                                                <div style="padding: 12px; border-bottom: 1px solid #e0e0e0; flex-shrink: 0;">
                                                    <div class="d-flex align-center justify-space-between" style="gap: 8px;">
                                                        <div class="d-flex align-center" style="gap: 8px; flex: 1;">
                                                            <v-text-field
                                                                v-model="fieldSearch"
                                                                placeholder="Search fields..."
                                                                prepend-inner-icon="mdi-magnify"
                                                                outlined
                                                                dense
                                                                hide-details
                                                                clearable
                                                                style="max-width: 200px;"
                                                                class="search-field-compact"
                                                            ></v-text-field>
                                                            <span class="text-caption text--secondary">
                                                                {{ filteredFields.length }}/{{ fields.length }}
                                                            </span>
                                                        </div>
                                                        <v-menu offset-y>
                                                            <template v-slot:activator="{ on, attrs }">
                                                                <v-btn
                                                                    small
                                                                    outlined
                                                                    v-bind="attrs"
                                                                    v-on="on"
                                                                >
                                                                    <v-icon left small>mdi-sort</v-icon>
                                                                    {{ getSortLabel(fieldSortBy) }}
                                                                    <v-icon right small>mdi-menu-down</v-icon>
                                                                </v-btn>
                                                            </template>
                                                            <v-list dense>
                                                                <v-list-item
                                                                    v-for="option in sortOptions"
                                                                    :key="option.value"
                                                                    @click="fieldSortBy = option.value"
                                                                    :class="{ 'v-list-item--active': fieldSortBy === option.value }"
                                                                >
                                                                    <v-list-item-content>
                                                                        <v-list-item-title>{{ option.text }}</v-list-item-title>
                                                                    </v-list-item-content>
                                                                </v-list-item>
                                                            </v-list>
                                                        </v-menu>
                                                    </div>
                                                </div>
                                                
                                                <!-- Field List -->
                                                <div style="flex: 1; overflow-y: auto; overflow-x: hidden; min-height: 0;">
                                                    <div
                                                        v-for="field in filteredAndSortedFields"
                                                        :key="field.name"
                                                        class="field-list-item"
                                                        :class="{ active: selectedField && selectedField.name === field.name }"
                                                        @click="selectField(field)"
                                                    >
                                                        <div class="d-flex align-center">
                                                            <v-icon small class="mr-2">mdi-table-column</v-icon>                                                            
                                                            <div class="flex-grow-1">
                                                                <div class="font-weight-medium">{{ field.name }}</div>
                                                                <div class="text-caption text--secondary">
                                                                    {{ field.label || 'N/A' }} • {{ field.data_type }}{{ field.column_type ? ' • ' + field.column_type : '' }}
                                                                </div>
                                                            </div>
                                                            <v-btn
                                                                icon                                                                
                                                                color="error"
                                                                class="ml-2"
                                                                @click.stop="deleteField(field)"
                                                            >
                                                                <v-icon small>mdi-delete</v-icon>
                                                            </v-btn>
                                                        </div>
                                                    </div>
                                                    <div v-if="fields.length === 0" class="text-center py-8 text--secondary">
                                                        <v-icon size="48" color="grey lighten-1" class="mb-2">mdi-table-off</v-icon>
                                                        <div>No fields defined</div>
                                                        <div class="text-caption mt-2">Click "Populate from Data" to auto-generate</div>
                                                    </div>
                                                    <div v-else-if="filteredFields.length === 0" class="text-center py-8 text--secondary">
                                                        <v-icon size="48" color="grey lighten-1" class="mb-2">mdi-magnify</v-icon>
                                                        <div>No fields match your search</div>
                                                    </div>
                                                </div>
                                            </v-col>

                                            <!-- Right Column - Field Editor (60%) -->
                                            <v-col cols="12" md="7" style="display: flex; flex-direction: column; height: 100%;">
                                                <div v-if="selectedField" style="padding: 24px; overflow-y: auto; overflow-x: hidden; height: 100%;">
                                                    <h3 class="mb-4">{{ selectedField.name }}</h3>
                                                    
                                                    <v-text-field
                                                        v-model="selectedField.label"
                                                        label="Label"
                                                        outlined
                                                        dense
                                                        class="mb-3"
                                                        @blur="updateField"
                                                    ></v-text-field>

                                                    <v-select
                                                        v-model="selectedField.data_type"
                                                        :items="dataTypes"
                                                        label="Data Type"
                                                        outlined
                                                        dense
                                                        class="mb-3"
                                                        @change="updateField"
                                                    ></v-select>

                                                    <v-select
                                                        v-model="selectedField.column_type"
                                                        :items="columnTypes"
                                                        label="Column Type"
                                                        outlined
                                                        dense
                                                        class="mb-3"
                                                        @change="updateField"
                                                    ></v-select>

                                                    <v-textarea
                                                        v-model="selectedField.description"
                                                        label="Description"
                                                        outlined
                                                        rows="3"
                                                        class="mb-3"
                                                        @blur="updateField"
                                                    ></v-textarea>

                                                    <v-text-field
                                                        v-model="selectedField.unit_of_measurement"
                                                        label="Unit of Measurement"
                                                        outlined
                                                        dense
                                                        class="mb-3"
                                                        @blur="updateField"
                                                    ></v-text-field>

                                                    <v-text-field
                                                        v-model="selectedField.format"
                                                        label="Format"
                                                        outlined
                                                        dense
                                                        class="mb-3"
                                                        @blur="updateField"
                                                    ></v-text-field>

                                                    <v-text-field
                                                        v-model="selectedField.time_period_format"
                                                        label="Time Period Format"
                                                        outlined
                                                        dense
                                                        class="mb-3"
                                                        :disabled="selectedField.column_type !== 'time_period'"
                                                        @blur="updateField"
                                                    ></v-text-field>

                                                    <v-divider class="my-4"></v-divider>

                                                    <!-- Code List Section -->
                                                    <h4 class="mb-3">Code List</h4>
                                                    <v-card outlined class="mb-3">
                                                        <v-card-text>
                                                            <div class="mb-3">
                                                                <span class="text--secondary">
                                                                    {{ selectedField.code_list ? selectedField.code_list.length : 0 }} item(s)
                                                                </span>
                                                            </div>
                                                            <v-simple-table v-if="selectedField.code_list && selectedField.code_list.length > 0" dense>
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width: 200px; border: 1px solid #e0e0e0;">Code</th>
                                                                        <th style="border: 1px solid #e0e0e0;">Label</th>
                                                                        <th style="border: 1px solid #e0e0e0;">Description</th>
                                                                        <th style="width: 80px; border: 1px solid #e0e0e0;">Actions</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr v-for="(codeItem, index) in selectedField.code_list" :key="index">
                                                                        <td style="border: 1px solid #e0e0e0; padding: 4px;">
                                                                            <input
                                                                                type="text"
                                                                                v-model="codeItem.code"
                                                                                @blur="updateField"
                                                                                placeholder="Code value"
                                                                                style="width: 100%; padding: 4px 8px; font-size: 13px; border: none; outline: none; box-sizing: border-box;"
                                                                            />
                                                                        </td>
                                                                        <td style="border: 1px solid #e0e0e0; padding: 4px;">
                                                                            <input
                                                                                type="text"
                                                                                v-model="codeItem.label"
                                                                                @blur="updateField"
                                                                                placeholder="Label"
                                                                                style="width: 100%; padding: 4px 8px; font-size: 13px; border: none; outline: none; box-sizing: border-box;"
                                                                            />
                                                                        </td>
                                                                        <td style="border: 1px solid #e0e0e0; padding: 4px;">
                                                                            <input
                                                                                type="text"
                                                                                v-model="codeItem.description"
                                                                                @blur="updateField"
                                                                                placeholder="Description"
                                                                                style="width: 100%; padding: 4px 8px; font-size: 13px; border: none; outline: none; box-sizing: border-box;"
                                                                            />
                                                                        </td>
                                                                        <td style="border: 1px solid #e0e0e0; padding: 4px; text-align: center;">
                                                                            <v-btn
                                                                                icon
                                                                                small
                                                                                color="error"
                                                                                @click="removeCodeListItem(index)"
                                                                            >
                                                                                <v-icon small>mdi-delete</v-icon>
                                                                            </v-btn>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </v-simple-table>
                                                            <div v-else class="text-center text--secondary py-8">
                                                                <v-icon size="48" color="grey lighten-1" class="mb-2">mdi-code-braces</v-icon>
                                                                <div>No code list items</div>
                                                                <div class="text-caption mt-2">Click "Add Code" to add one</div>
                                                            </div>
                                                            <div class="text-center mt-3">
                                                                <v-btn
                                                                    color="primary"
                                                                    small
                                                                    @click="addCodeListItem"
                                                                >
                                                                    <v-icon left small>mdi-plus</v-icon>
                                                                    Add Code
                                                                </v-btn>
                                                            </div>
                                                        </v-card-text>
                                                    </v-card>

                                                    <!-- Code List Reference Section -->
                                                    <v-expansion-panels v-model="codeListRefExpanded" class="mb-3">
                                                        <v-expansion-panel>
                                                            <v-expansion-panel-header>
                                                                <span class="font-weight-medium">Code List Reference</span>
                                                            </v-expansion-panel-header>
                                                            <v-expansion-panel-content>
                                                                <v-text-field
                                                                    v-model="selectedField.code_list_reference.id"
                                                                    label="ID"
                                                                    outlined
                                                                    dense
                                                                    class="mb-3"
                                                                    @blur="updateField"
                                                                ></v-text-field>
                                                                <v-text-field
                                                                    v-model="selectedField.code_list_reference.name"
                                                                    label="Name"
                                                                    outlined
                                                                    dense
                                                                    class="mb-3"
                                                                    @blur="updateField"
                                                                ></v-text-field>
                                                                <v-text-field
                                                                    v-model="selectedField.code_list_reference.version"
                                                                    label="Version"
                                                                    outlined
                                                                    dense
                                                                    class="mb-3"
                                                                    @blur="updateField"
                                                                ></v-text-field>
                                                                <v-text-field
                                                                    v-model="selectedField.code_list_reference.uri"
                                                                    label="URI"
                                                                    outlined
                                                                    dense
                                                                    class="mb-3"
                                                                    @blur="updateField"
                                                                ></v-text-field>
                                                                <v-textarea
                                                                    v-model="selectedField.code_list_reference.note"
                                                                    label="Note"
                                                                    outlined
                                                                    rows="2"
                                                                    dense
                                                                    @blur="updateField"
                                                                ></v-textarea>
                                                            </v-expansion-panel-content>
                                                        </v-expansion-panel>
                                                    </v-expansion-panels>

                                                    <v-divider class="my-4"></v-divider>

                                                    <div class="d-flex justify-space-between">
                                                        <v-btn color="error" small @click="deleteField(selectedField)">
                                                            <v-icon left small>mdi-delete</v-icon>
                                                            Delete Field
                                                        </v-btn>
                                                        <v-btn color="primary" small @click="saveField" :loading="savingField">
                                                            <v-icon left small>mdi-content-save</v-icon>
                                                            Save Changes
                                                        </v-btn>
                                                    </div>
                                                </div>
                                                <div v-else class="text-center py-12 text--secondary" style="display: flex; align-items: center; justify-content: center; height: 100%;">
                                                    <div>
                                                        <v-icon size="64" color="grey lighten-1" class="mb-4">mdi-cursor-pointer</v-icon>
                                                        <div>Select a field from the list to edit</div>
                                                    </div>
                                                </div>
                                            </v-col>
                                        </v-row>
                                    </v-card-text>
                                            </v-card>
                                        </v-tab-item>

                                        <!-- Tab 4: Indexes -->
                                        <v-tab-item>
                                            <v-card flat>
                                                <v-card-title>
                                                    <span>Index Management</span>
                                                    <v-spacer></v-spacer>
                                                    <v-btn 
                                                        color="primary" 
                                                        small 
                                                        @click="showCreateIndexDialog = true"
                                                        class="mr-2"
                                                    >
                                                        <v-icon left small>mdi-plus</v-icon>
                                                        Create Index
                                                    </v-btn>
                                                    <v-btn 
                                                        color="primary" 
                                                        small 
                                                        @click="showCreateTextIndexDialog = true"
                                                        class="mr-2"
                                                    >
                                                        <v-icon left small>mdi-text-search</v-icon>
                                                        Create Text Index
                                                    </v-btn>
                                                    <v-btn 
                                                        color="error" 
                                                        small 
                                                        @click="confirmDeleteAllIndexes"
                                                        :disabled="indexes.length <= 1"
                                                        class="mr-2"
                                                    >
                                                        <v-icon left small>mdi-delete-sweep</v-icon>
                                                        Delete All Indexes
                                                    </v-btn>
                                                    <v-btn 
                                                        icon 
                                                        small 
                                                        @click="loadIndexes"
                                                        :loading="loadingIndexes"
                                                    >
                                                        <v-icon>mdi-refresh</v-icon>
                                                    </v-btn>
                                                </v-card-title>
                                                <v-card-text>
                                                    <v-alert v-if="indexError" type="error" dismissible @input="indexError = ''" class="mb-4">
                                                        {{ indexError }}
                                                    </v-alert>

                                                    <v-alert v-if="indexSuccess" type="success" dismissible @input="indexSuccess = ''" class="mb-4">
                                                        {{ indexSuccess }}
                                                    </v-alert>

                                                    <v-data-table
                                                        :headers="indexHeaders"
                                                        :items="indexes"
                                                        :loading="loadingIndexes"
                                                        item-key="name"
                                                        class="elevation-1"
                                                    >
                                                        <template v-slot:item.name="{ item }">
                                                            <strong>{{ item.name }}</strong>
                                                            <v-chip 
                                                                v-if="item.name === '_id_'"
                                                                x-small 
                                                                color="grey" 
                                                                class="ml-2"
                                                            >
                                                                System
                                                            </v-chip>
                                                        </template>
                                                        <template v-slot:item.fields="{ item }">
                                                            <div v-if="item.key">
                                                                <v-chip
                                                                    v-for="(value, field) in item.key"
                                                                    :key="field"
                                                                    x-small
                                                                    color="info"
                                                                    class="mr-1 mb-1"
                                                                >
                                                                    {{ field }} ({{ value === 1 ? 'asc' : value === -1 ? 'desc' : value === 'text' ? 'text' : value }})
                                                                </v-chip>
                                                            </div>
                                                            <span v-else class="text--secondary">N/A</span>
                                                        </template>
                                                        <template v-slot:item.type="{ item }">
                                                            <v-chip 
                                                                x-small 
                                                                :color="item.name.includes('text') || (item.key && Object.values(item.key).some(v => v === 'text')) ? 'purple' : 'primary'"
                                                            >
                                                                {{ item.name.includes('text') || (item.key && Object.values(item.key).some(v => v === 'text')) ? 'Text' : 'Compound' }}
                                                            </v-chip>
                                                        </template>
                                                        <template v-slot:item.actions="{ item }">
                                                            <v-btn
                                                                v-if="item.name !== '_id_'"
                                                                icon
                                                                small
                                                                color="error"
                                                                @click="confirmDeleteIndex(item.name)"
                                                                :loading="deletingIndex === item.name"
                                                            >
                                                                <v-icon small>mdi-delete</v-icon>
                                                            </v-btn>
                                                            <span v-else class="text--secondary">System index</span>
                                                        </template>
                                                        <template v-slot:no-data>
                                                            <div class="text-center py-8">
                                                                <v-icon large color="grey lighten-1" class="mb-2">mdi-database-off</v-icon>
                                                                <p class="text--secondary">No custom indexes found</p>
                                                                <p class="text--secondary text-caption">Create an index to improve query performance</p>
                                                            </div>
                                                        </template>
                                                    </v-data-table>
                                                </v-card-text>
                                            </v-card>
                                        </v-tab-item>

                                        <!-- Tab 5: Study Links -->
                                        <v-tab-item>
                                            <v-card flat>
                                                <v-card-title>
                                                    <span>Study Links</span>
                                                    <v-spacer></v-spacer>
                                                    <v-btn
                                                        color="primary"
                                                        @click="showAttachStudyDialog = true"
                                                    >
                                                        <v-icon left>mdi-link-plus</v-icon>
                                                        Attach Study
                                                    </v-btn>
                                                </v-card-title>
                                                <v-card-text>
                                                    <v-alert v-if="studyLinksError" type="error" dismissible @input="studyLinksError = ''" class="mb-4">
                                                        {{ studyLinksError }}
                                                    </v-alert>

                                                    <v-alert v-if="studyLinksSuccess" type="success" dismissible @input="studyLinksSuccess = ''" class="mb-4">
                                                        {{ studyLinksSuccess }}
                                                    </v-alert>

                                                    <div v-if="loadingStudyLinks" class="text-center py-8">
                                                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                                                        <div class="mt-2 text--secondary">Loading study links...</div>
                                                    </div>

                                                    <v-data-table
                                                        v-else
                                                        :headers="studyLinksHeaders"
                                                        :items="attachedStudies"
                                                        :items-per-page="10"
                                                        class="elevation-1"
                                                    >
                                                        <template v-slot:item.title="{ item }">
                                                            <div>
                                                                <div class="font-weight-medium">{{ item.title || 'N/A' }}</div>
                                                                <div class="text-caption text--secondary">
                                                                    IDNO: {{ item.idno }} | {{ item.nation || 'N/A' }}{{ item.year_start ? ', ' + item.year_start : '' }}
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template v-slot:item.actions="{ item }">
                                                            <div class="d-flex align-center">
                                                                <v-btn
                                                                    icon
                                                                    small
                                                                    color="primary"
                                                                    :href="studyDataApiUrl(item.sid)"
                                                                    target="_blank"
                                                                    title="View/Edit Study"
                                                                >
                                                                    <v-icon small>mdi-open-in-new</v-icon>
                                                                </v-btn>
                                                                <v-btn
                                                                    icon
                                                                    small
                                                                    color="error"
                                                                    @click="confirmDetachStudy(item)"
                                                                    title="Detach Study"
                                                                >
                                                                    <v-icon small>mdi-link-variant-off</v-icon>
                                                                </v-btn>
                                                            </div>
                                                        </template>
                                                        <template v-slot:no-data>
                                                            <div class="text-center py-8">
                                                                <v-icon large color="grey lighten-1" class="mb-2">mdi-link-off</v-icon>
                                                                <p class="text--secondary">No studies attached to this table</p>
                                                                <p class="text--secondary text-caption">Click "Attach Study" to link a study to this table</p>
                                                            </div>
                                                        </template>
                                                    </v-data-table>
                                                </v-card-text>
                                            </v-card>
                                        </v-tab-item>
                                    </v-tabs-items>
                                </v-card>
                            </template>
                        </v-container>
                    </v-main>

                    <!-- Create Index Dialog -->
                    <v-dialog v-model="showCreateIndexDialog" max-width="600">
                        <v-card>
                            <v-card-title>Create Index</v-card-title>
                            <v-card-text>
                                <v-text-field
                                    v-model="newIndexFields"
                                    label="Index Fields *"
                                    placeholder="e.g., ISO3, country, value"
                                    outlined
                                    dense
                                    hint="Enter field names separated by commas"
                                    persistent-hint
                                    class="mb-3"
                                ></v-text-field>
                                <v-alert v-if="indexCreateError" type="error" class="mb-3">
                                    {{ indexCreateError }}
                                </v-alert>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showCreateIndexDialog = false; newIndexFields = ''; indexCreateError = ''">Cancel</v-btn>
                                <v-btn 
                                    color="primary" 
                                    @click="createIndex" 
                                    :disabled="!newIndexFields || creatingIndex"
                                    :loading="creatingIndex"
                                >
                                    Create Index
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Create Text Index Dialog -->
                    <v-dialog v-model="showCreateTextIndexDialog" max-width="600">
                        <v-card>
                            <v-card-title>Create Text Index</v-card-title>
                            <v-card-text>
                                <v-alert type="warning" outlined class="mb-4">
                                    <div class="text-caption">
                                        <strong>Text Index:</strong> Enables full-text search across specified fields. 
                                        Only one text index can exist per table. If a text index already exists, you must delete it first.
                                    </div>
                                </v-alert>
                                <v-text-field
                                    v-model="newTextIndexFields"
                                    label="Index Fields *"
                                    placeholder="e.g., title, description, notes"
                                    outlined
                                    dense
                                    hint="Enter field names separated by commas"
                                    persistent-hint
                                    class="mb-3"
                                ></v-text-field>
                                <v-alert v-if="indexCreateError" type="error" class="mb-3">
                                    {{ indexCreateError }}
                                </v-alert>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showCreateTextIndexDialog = false; newTextIndexFields = ''; indexCreateError = ''">Cancel</v-btn>
                                <v-btn 
                                    color="primary" 
                                    @click="createTextIndex" 
                                    :disabled="!newTextIndexFields || creatingIndex"
                                    :loading="creatingIndex"
                                >
                                    Create Text Index
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Delete Index Confirmation Dialog -->
                    <v-dialog v-model="showDeleteIndexDialog" max-width="500">
                        <v-card>
                            <v-card-title>Delete Index</v-card-title>
                            <v-card-text>
                                <p>Are you sure you want to delete the index <strong>{{ indexToDelete }}</strong>?</p>
                                <v-alert type="warning" outlined class="mt-3">
                                    This action cannot be undone. Deleting an index may affect query performance.
                                </v-alert>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showDeleteIndexDialog = false; indexToDelete = ''">Cancel</v-btn>
                                <v-btn 
                                    color="error" 
                                    @click="deleteIndex" 
                                    :loading="deletingIndex === indexToDelete"
                                >
                                    Delete
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Delete All Indexes Confirmation Dialog -->
                    <v-dialog v-model="showDeleteAllIndexesDialog" max-width="500">
                        <v-card>
                            <v-card-title>Delete All Indexes</v-card-title>
                            <v-card-text>
                                <p>Are you sure you want to delete <strong>all custom indexes</strong> for this table?</p>
                                <v-alert type="warning" outlined class="mt-3">
                                    <div class="text-caption">
                                        <strong>Warning:</strong> This will delete all indexes except the system index (_id_). 
                                        This action cannot be undone and may significantly affect query performance.
                                    </div>
                                </v-alert>
                                <div v-if="indexes.length > 1" class="mt-3">
                                    <p class="text-caption">The following {{ indexes.length - 1 }} index(es) will be deleted:</p>
                                    <ul class="text-caption">
                                        <li v-for="index in indexes" :key="index.name" v-if="index.name !== '_id_'">
                                            {{ index.name }}
                                        </li>
                                    </ul>
                                </div>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showDeleteAllIndexesDialog = false">Cancel</v-btn>
                                <v-btn 
                                    color="error" 
                                    @click="deleteAllIndexes" 
                                    :loading="deletingAllIndexes"
                                >
                                    Delete All
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Attach Study Dialog -->
                    <v-dialog v-model="showAttachStudyDialog" max-width="800" persistent>
                        <v-card>
                            <v-card-title>Attach Study</v-card-title>
                            <v-card-text>
                                <v-alert v-if="studySearchError" type="error" dismissible @input="studySearchError = ''" class="mb-4">
                                    {{ studySearchError }}
                                </v-alert>

                                <v-text-field
                                    v-model="studySearchQuery"
                                    label="Search Studies"
                                    placeholder="Enter study IDNO, title, or keywords"
                                    outlined
                                    dense
                                    prepend-inner-icon="mdi-magnify"
                                    @input="searchStudies"
                                    :loading="searchingStudies"
                                    class="mb-4"
                                ></v-text-field>

                                <div v-if="searchingStudies" class="text-center py-4">
                                    <v-progress-circular indeterminate color="primary" size="24"></v-progress-circular>
                                    <div class="text-caption text--secondary mt-2">Searching studies...</div>
                                </div>

                                <div v-else-if="studySearchResults.length > 0" style="max-height: 400px; overflow-y: auto;">
                                    <v-list>
                                        <v-list-item
                                            v-for="study in studySearchResults"
                                            :key="study.id"
                                            @click="attachStudy(study)"
                                        >
                                            <v-list-item-content>
                                                <v-list-item-title>{{ study.title || 'N/A' }}</v-list-item-title>
                                                <v-list-item-subtitle>
                                                    IDNO: {{ study.idno }} | {{ study.nation || 'N/A' }}{{ study.year_start ? ', ' + study.year_start : '' }}
                                                </v-list-item-subtitle>
                                            </v-list-item-content>
                                            <v-list-item-action>
                                                <v-btn
                                                    icon
                                                    small
                                                    color="primary"
                                                    @click.stop="attachStudy(study)"
                                                    :loading="attachingStudy"
                                                >
                                                    <v-icon small>mdi-link-plus</v-icon>
                                                </v-btn>
                                            </v-list-item-action>
                                        </v-list-item>
                                    </v-list>
                                </div>

                                <div v-else-if="studySearchQuery && studySearchQuery.length >= 2 && !searchingStudies" class="text-center py-8 text--secondary">
                                    <v-icon size="48" color="grey lighten-1" class="mb-2">mdi-magnify</v-icon>
                                    <div>No studies found</div>
                                    <div class="text-caption mt-2">Try a different search term</div>
                                </div>

                                <div v-else class="text-center py-8 text--secondary">
                                    <v-icon size="48" color="grey lighten-1" class="mb-2">mdi-magnify</v-icon>
                                    <div>Enter at least 2 characters to search for studies</div>
                                </div>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showAttachStudyDialog = false; studySearchQuery = ''; studySearchResults = []; studySearchError = ''">
                                    Close
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Detach Study Confirmation Dialog -->
                    <v-dialog v-model="showDetachStudyDialog" max-width="500">
                        <v-card>
                            <v-card-title>Detach Study</v-card-title>
                            <v-card-text>
                                <p>Are you sure you want to detach the following study from this table?</p>
                                <v-card outlined class="mt-3 pa-3" v-if="studyToDetach">
                                    <div class="font-weight-medium">{{ studyToDetach.title || 'N/A' }}</div>
                                    <div class="text-caption text--secondary mt-1">
                                        IDNO: {{ studyToDetach.idno }} | {{ studyToDetach.nation || 'N/A' }}{{ studyToDetach.year_start ? ', ' + studyToDetach.year_start : '' }}
                                    </div>
                                </v-card>
                                <v-alert type="info" outlined class="mt-3">
                                    <div class="text-caption">
                                        This will remove the link between the table and the study. The table and study data will not be deleted.
                                    </div>
                                </v-alert>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showDetachStudyDialog = false; studyToDetach = null">Cancel</v-btn>
                                <v-btn 
                                    color="error" 
                                    @click="detachStudy" 
                                    :loading="detachingStudy"
                                >
                                    Detach
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <!-- Add Field Dialog -->
                    <v-dialog v-model="showAddFieldDialog" max-width="600">
                        <v-card>
                            <v-card-title>Add New Field</v-card-title>
                            <v-card-text>
                                <v-text-field
                                    v-model="newField.name"
                                    label="Field Name *"
                                    outlined
                                    dense
                                    class="mb-3"
                                ></v-text-field>
                                <v-text-field
                                    v-model="newField.label"
                                    label="Label"
                                    outlined
                                    dense
                                    class="mb-3"
                                ></v-text-field>
                                <v-select
                                    v-model="newField.data_type"
                                    :items="dataTypes"
                                    label="Data Type *"
                                    outlined
                                    dense
                                    class="mb-3"
                                ></v-select>
                                <v-select
                                    v-model="newField.column_type"
                                    :items="columnTypes"
                                    label="Column Type"
                                    outlined
                                    dense
                                ></v-select>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn text @click="showAddFieldDialog = false">Cancel</v-btn>
                                <v-btn color="primary" @click="addField" :disabled="!newField.name || !newField.data_type">Add</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </div>
            `,
            data() {
                return {
                    adminDashboardUrl: adminDashboardUrl,
                    studyEditBaseUrl: studyEditBaseUrl,
                    loading: true,
                    error: '',
                    success: '',
                    dbId: '',
                    tableId: '',
                    tableInfo: {
                        db_id: '',
                        table_id: '',
                        title: '',
                        description: ''
                    },
                    fields: [],
                    selectedField: null,
                    showAddFieldDialog: false,
                    savingField: false,
                    savingTableInfo: false,
                    populatingSchema: false,
                    syncingFields: false,
                    fieldSearch: '',
                    fieldSortBy: 'order',
                    codeListRefExpanded: [],
                    activeTab: 0,
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
                    previewTotal: 0,
                    indexes: [],
                    loadingIndexes: false,
                    indexError: '',
                    indexSuccess: '',
                    showCreateIndexDialog: false,
                    showCreateTextIndexDialog: false,
                    showDeleteIndexDialog: false,
                    newIndexFields: '',
                    newTextIndexFields: '',
                    creatingIndex: false,
                    indexCreateError: '',
                    indexToDelete: '',
                    deletingIndex: '',
                    showDeleteAllIndexesDialog: false,
                    deletingAllIndexes: false,
                    indexHeaders: [
                        { text: 'Index Name', value: 'name', sortable: true },
                        { text: 'Fields', value: 'fields', sortable: false },
                        { text: 'Type', value: 'type', sortable: true },
                        { text: 'Actions', value: 'actions', sortable: false, align: 'center', width: '120px' }
                    ],
                    newField: {
                        name: '',
                        label: '',
                        data_type: 'string',
                        column_type: ''
                    },
                    dataTypes: [
                        { text: 'String', value: 'string' },
                        { text: 'Integer', value: 'integer' },
                        { text: 'Float', value: 'float' },
                        { text: 'Double', value: 'double' },
                        { text: 'Boolean', value: 'boolean' },
                        { text: 'Date', value: 'date' },
                        { text: 'DateTime', value: 'datetime' },
                        { text: 'Array', value: 'array' },
                        { text: 'Object', value: 'object' },
                        { text: 'Null', value: 'null' }
                    ],
                    columnTypes: [
                        { text: '-', value: '' },
                        { text: 'Dimension', value: 'dimension' },
                        { text: 'Time Period', value: 'time_period' },
                        { text: 'Measure', value: 'measure' },
                        { text: 'Attribute', value: 'attribute' },
                        { text: 'Indicator ID', value: 'indicator_id' },
                        { text: 'Indicator Name', value: 'indicator_name' },
                        { text: 'Geography', value: 'geography' },
                        { text: 'Observation Value', value: 'observation_value' },
                        { text: 'Periodicity', value: 'periodicity' }
                    ],
                    sortOptions: [
                        { text: 'Order', value: 'order' },
                        { text: 'Name (A-Z)', value: 'name_asc' },
                        { text: 'Name (Z-A)', value: 'name_desc' },
                        { text: 'Label (A-Z)', value: 'label_asc' },
                        { text: 'Label (Z-A)', value: 'label_desc' },
                        { text: 'Data Type', value: 'data_type' }
                    ],
                    attachedStudies: [],
                    loadingStudyLinks: false,
                    studyLinksError: '',
                    studyLinksSuccess: '',
                    showAttachStudyDialog: false,
                    studySearchQuery: '',
                    studySearchResults: [],
                    searchingStudies: false,
                    studySearchError: '',
                    selectedStudyIdno: '',
                    attachingStudy: false,
                    studyToDetach: null,
                    showDetachStudyDialog: false,
                    detachingStudy: false,
                    studyLinksHeaders: [
                        { text: 'Title', value: 'title', sortable: true },
                        { text: 'IDNO', value: 'idno', sortable: true },
                        { text: 'Nation', value: 'nation', sortable: true },
                        { text: 'Year', value: 'year_start', sortable: true },
                        { text: 'Actions', value: 'actions', sortable: false, align: 'center', width: '150px' }
                    ]
                };
            },
            computed: {
                hasCodeListReference() {
                    if (!this.selectedField || !this.selectedField.code_list_reference) return false;
                    const ref = this.selectedField.code_list_reference;
                    return !!(ref.id || ref.name || ref.version || ref.uri || ref.note);
                },
                filteredFields() {
                    if (!this.fieldSearch) {
                        return this.fields;
                    }
                    const search = this.fieldSearch.toLowerCase();
                    return this.fields.filter(field => {
                        return field.name.toLowerCase().includes(search) ||
                               (field.label && field.label.toLowerCase().includes(search)) ||
                               (field.data_type && field.data_type.toLowerCase().includes(search)) ||
                               (field.column_type && field.column_type.toLowerCase().includes(search));
                    });
                },
                filteredAndSortedFields() {
                    let fields = [...this.filteredFields];
                    
                    switch (this.fieldSortBy) {
                        case 'name_asc':
                            fields.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                            break;
                        case 'name_desc':
                            fields.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
                            break;
                        case 'label_asc':
                            fields.sort((a, b) => {
                                const labelA = a.label || a.name || '';
                                const labelB = b.label || b.name || '';
                                return labelA.localeCompare(labelB);
                            });
                            break;
                        case 'label_desc':
                            fields.sort((a, b) => {
                                const labelA = a.label || a.name || '';
                                const labelB = b.label || b.name || '';
                                return labelB.localeCompare(labelA);
                            });
                            break;
                        case 'data_type':
                            fields.sort((a, b) => {
                                const typeA = a.data_type || '';
                                const typeB = b.data_type || '';
                                return typeA.localeCompare(typeB) || (a.name || '').localeCompare(b.name || '');
                            });
                            break;
                        case 'order':
                        default:
                            fields.sort((a, b) => {
                                const orderA = a.field_order || 0;
                                const orderB = b.field_order || 0;
                                return orderA - orderB;
                            });
                            break;
                    }
                    
                    return fields;
                },
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
            async mounted() {
                await this.loadTableData();
            },
            watch: {
                activeTab(newVal) {
                    if (newVal === 1) {
                        // Data Management tab
                        this.loadTableStats();
                        this.loadPreviewData();
                    } else if (newVal === 3) {
                        // Indexes tab
                        this.loadIndexes();
                    } else if (newVal === 4) {
                        // Study Links tab
                        this.loadStudyLinks();
                    }
                },
                previewPage() {
                    this.loadPreviewData();
                },
                showUploadDialog(newVal) {
                    if (newVal) {
                        // Reset upload state when dialog opens
                        this.uploadFile = null;
                        this.uploadStatus = null;
                        this.importStatus = null;
                        this.uploading = false;
                        this.importing = false;
                        this.importCancelled = false;
                        this.deleting = false;
                    }
                },
                selectedField: {
                    handler(newVal, oldVal) {
                        // Sync selectedField changes to fields array in real-time for sidebar updates
                        if (newVal && newVal.name) {
                            const fieldIndex = this.fields.findIndex(f => f.name === newVal.name);
                            if (fieldIndex >= 0) {
                                // Only update if values actually changed to avoid unnecessary updates
                                const currentField = this.fields[fieldIndex];
                                const hasChanges = 
                                    currentField.label !== newVal.label ||
                                    currentField.data_type !== newVal.data_type ||
                                    currentField.column_type !== newVal.column_type ||
                                    currentField.description !== newVal.description ||
                                    currentField.unit_of_measurement !== newVal.unit_of_measurement ||
                                    currentField.format !== newVal.format ||
                                    currentField.time_period_format !== newVal.time_period_format;
                                
                                if (hasChanges) {
                                    // Update the field in the array with current selectedField values
                                    // Preserve field_order and other properties
                                    this.$set(this.fields, fieldIndex, {
                                        ...currentField,
                                        label: newVal.label,
                                        data_type: newVal.data_type,
                                        column_type: newVal.column_type,
                                        description: newVal.description,
                                        unit_of_measurement: newVal.unit_of_measurement,
                                        format: newVal.format,
                                        time_period_format: newVal.time_period_format
                                    });
                                }
                            }
                        }
                    },
                    deep: true,
                    immediate: false
                },
                '$route'(to) {
                    if (to.name === 'edit') {
                        this.loadTableData();
                    }
                }
            },
            methods: {
                async loadTableData() {
                    this.loading = true;
                    this.error = '';
                    const routeParams = this.$route.params;
                    this.dbId = routeParams.db_id;
                    this.tableId = routeParams.table_id;

                    try {
                        // Load table info
                        const infoResponse = await axios.get(`${apiBase}/info/${this.dbId}/${this.tableId}`);
                        if (infoResponse.data.status === 'success') {
                            const result = infoResponse.data.result;
                            this.tableInfo = {
                                db_id: this.dbId,
                                table_id: this.tableId,
                                title: result.metadata?.title || '',
                                description: result.metadata?.description || ''
                            };
                        }

                        // Load schema
                        await this.loadSchema();
                    } catch (error) {
                        this.error = 'Error loading table: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.loading = false;
                    }
                },
                async loadSchema() {
                    try {
                        const response = await axios.get(`${apiBase}/fields/${this.dbId}/${this.tableId}`);
                        if (response.data.status === 'success') {
                            this.fields = (response.data.fields || []).map(field => {
                                // Ensure code_list_reference is always an object, not null
                                let codeListRef = field.code_list_reference;
                                if (!codeListRef || typeof codeListRef !== 'object') {
                                    codeListRef = {
                                        id: '',
                                        name: '',
                                        version: '',
                                        uri: '',
                                        note: ''
                                    };
                                } else {
                                    // Ensure all properties exist
                                    codeListRef = {
                                        id: codeListRef.id || '',
                                        name: codeListRef.name || '',
                                        version: codeListRef.version || '',
                                        uri: codeListRef.uri || '',
                                        note: codeListRef.note || ''
                                    };
                                }
                                
                                return {
                                    name: field.name,
                                    label: (field.label !== undefined && field.label !== null) ? field.label : (field.title || field.name),
                                    data_type: field.data_type || field.dataType || 'string',
                                    column_type: field.column_type || '',
                                    description: (field.description !== undefined && field.description !== null) ? field.description : '',
                                    time_period_format: field.time_period_format || '',
                                    unit_of_measurement: field.unit_of_measurement || '',
                                    format: field.format || '',
                                    field_order: field.field_order || 0,
                                    code_list: field.code_list || [],
                                    code_list_reference: codeListRef
                                };
                            });
                            if (this.fields.length > 0 && !this.selectedField) {
                                this.selectField(this.fields[0]);
                            }
                        } else {
                            this.fields = [];
                        }
                    } catch (error) {
                        this.error = 'Error loading schema: ' + (error.response?.data?.message || error.message);
                        this.fields = [];
                    }
                },
                selectField(field) {
                    // If we have unsaved changes in selectedField, save them to the fields array first
                    if (this.selectedField && this.selectedField.name) {
                        const fieldIndex = this.fields.findIndex(f => f.name === this.selectedField.name);
                        if (fieldIndex >= 0) {
                            // Update the field in the array with current selectedField values
                            this.fields[fieldIndex] = {
                                ...this.fields[fieldIndex],
                                ...this.selectedField,
                                code_list: this.selectedField.code_list ? [...this.selectedField.code_list.map(item => ({ ...item }))] : [],
                                code_list_reference: this.selectedField.code_list_reference ? {
                                    id: this.selectedField.code_list_reference.id || '',
                                    name: this.selectedField.code_list_reference.name || '',
                                    version: this.selectedField.code_list_reference.version || '',
                                    uri: this.selectedField.code_list_reference.uri || '',
                                    note: this.selectedField.code_list_reference.note || ''
                                } : null
                            };
                        }
                    }
                    
                    // Deep clone to avoid reference issues
                    const codeListRef = field.code_list_reference || null;
                    this.selectedField = {
                        ...field,
                        code_list: field.code_list ? [...field.code_list.map(item => ({ ...item }))] : [],
                        code_list_reference: codeListRef ? {
                            id: codeListRef.id || '',
                            name: codeListRef.name || '',
                            version: codeListRef.version || '',
                            uri: codeListRef.uri || '',
                            note: codeListRef.note || ''
                        } : {
                            id: '',
                            name: '',
                            version: '',
                            uri: '',
                            note: ''
                        }
                    };
                },
                async updateField() {
                    if (!this.selectedField || !this.selectedField.name) return;
                    
                    // The watcher will handle updating the fields array in real-time
                    // Here we just need to save to server
                    await this.saveField();
                },
                async saveField() {
                    if (!this.selectedField || !this.selectedField.name) return;
                    this.savingField = true;
                    try {
                        // Find the existing field to preserve field_order
                        const existingField = this.fields.find(f => f.name === this.selectedField.name);
                        const preserveFieldOrder = existingField ? existingField.field_order : null;
                        
                        const updateData = {
                            name: this.selectedField.name,
                            label: (this.selectedField.label !== undefined && this.selectedField.label !== null) ? this.selectedField.label : this.selectedField.name,
                            data_type: this.selectedField.data_type || 'string',
                            column_type: this.selectedField.column_type || null,
                            description: (this.selectedField.description !== undefined && this.selectedField.description !== null) ? this.selectedField.description : '',
                            time_period_format: this.selectedField.time_period_format || null,
                            unit_of_measurement: this.selectedField.unit_of_measurement || null,
                            format: this.selectedField.format || null,
                            code_list: this.selectedField.code_list || [],
                            code_list_reference: this.selectedField.code_list_reference && 
                                (this.selectedField.code_list_reference.id || 
                                 this.selectedField.code_list_reference.name || 
                                 this.selectedField.code_list_reference.uri) 
                                ? this.selectedField.code_list_reference : null
                        };
                        
                        // Preserve field_order for existing fields to prevent them from moving to bottom
                        if (preserveFieldOrder !== null && preserveFieldOrder !== undefined) {
                            updateData.field_order = preserveFieldOrder;
                        }
                        
                        // Use unified POST endpoint for upsert
                        const response = await axios.post(
                            `${apiBase}/fields/${this.dbId}/${this.tableId}`,
                            updateData
                        );
                        if (response.data.status === 'success') {
                            this.success = `Field ${response.data.action === 'created' ? 'created' : 'updated'} successfully`;
                            
                            // Fetch the updated field from server to get all current values
                            try {
                                const fieldResponse = await axios.get(
                                    `${apiBase}/field/${this.dbId}/${this.tableId}/${this.selectedField.name}`
                                );
                                if (fieldResponse.data.status === 'success' && fieldResponse.data.field) {
                                    const updatedFieldFromServer = fieldResponse.data.field;
                                    
                                    // Update the field in the local fields array with server data
                                    const fieldIndex = this.fields.findIndex(f => f.name === this.selectedField.name);
                                    if (fieldIndex >= 0) {
                                        // Ensure code_list_reference is always an object
                                        let codeListRef = updatedFieldFromServer.code_list_reference;
                                        if (!codeListRef || typeof codeListRef !== 'object') {
                                            codeListRef = {
                                                id: '',
                                                name: '',
                                                version: '',
                                                uri: '',
                                                note: ''
                                            };
                                        }
                                        
                                        this.fields[fieldIndex] = {
                                            ...updatedFieldFromServer,
                                            code_list: updatedFieldFromServer.code_list || [],
                                            code_list_reference: codeListRef
                                        };
                                        
                                        // Only re-select if this is still the currently selected field
                                        if (this.selectedField && this.selectedField.name === updatedFieldFromServer.name) {
                                            this.selectField(this.fields[fieldIndex]);
                                        }
                                    } else {
                                        // New field - add to array
                                        let codeListRef = updatedFieldFromServer.code_list_reference;
                                        if (!codeListRef || typeof codeListRef !== 'object') {
                                            codeListRef = {
                                                id: '',
                                                name: '',
                                                version: '',
                                                uri: '',
                                                note: ''
                                            };
                                        }
                                        this.fields.push({
                                            ...updatedFieldFromServer,
                                            code_list: updatedFieldFromServer.code_list || [],
                                            code_list_reference: codeListRef
                                        });
                                        
                                        // Only re-select if this is still the currently selected field
                                        if (this.selectedField && this.selectedField.name === updatedFieldFromServer.name) {
                                            this.selectField(this.fields[this.fields.length - 1]);
                                        }
                                    }
                                } else {
                                    // Fallback: update from selectedField if fetch fails
                                    const fieldIndex = this.fields.findIndex(f => f.name === this.selectedField.name);
                                    if (fieldIndex >= 0) {
                                        this.fields[fieldIndex] = {
                                            ...this.fields[fieldIndex],
                                            ...this.selectedField,
                                            field_order: preserveFieldOrder !== null ? preserveFieldOrder : this.fields[fieldIndex].field_order
                                        };
                                        // Only re-select if this is still the currently selected field
                                        if (this.selectedField && this.selectedField.name === this.fields[fieldIndex].name) {
                                            this.selectField(this.fields[fieldIndex]);
                                        }
                                    }
                                }
                            } catch (fetchError) {
                                // Fallback: update from selectedField if fetch fails
                                const fieldIndex = this.fields.findIndex(f => f.name === this.selectedField.name);
                                if (fieldIndex >= 0) {
                                    this.fields[fieldIndex] = {
                                        ...this.fields[fieldIndex],
                                        ...this.selectedField,
                                        field_order: preserveFieldOrder !== null ? preserveFieldOrder : this.fields[fieldIndex].field_order
                                    };
                                    // Only re-select if this is still the currently selected field
                                    if (this.selectedField && this.selectedField.name === this.fields[fieldIndex].name) {
                                        this.selectField(this.fields[fieldIndex]);
                                    }
                                }
                            }
                        } else {
                            this.error = response.data.message || 'Failed to update field';
                        }
                    } catch (error) {
                        this.error = 'Error updating field: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.savingField = false;
                    }
                },
                async saveTableInfo() {
                    if (!this.dbId || !this.tableId) {
                        this.error = 'Database ID and Table ID are required';
                        return;
                    }
                    this.savingTableInfo = true;
                    this.error = '';
                    try {
                        const updateData = {
                            title: this.tableInfo.title || '',
                            description: this.tableInfo.description || ''
                        };
                        const response = await axios.put(
                            `${apiBase}/update_table/${this.dbId}/${this.tableId}`,
                            updateData
                        );
                        if (response.data.status === 'success') {
                            this.success = 'Table information updated successfully';
                            // Reload table info to get latest data
                            await this.loadTableData();
                        } else {
                            this.error = response.data.message || 'Failed to update table information';
                        }
                    } catch (error) {
                        this.error = 'Error updating table information: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.savingTableInfo = false;
                    }
                },
                async populateSchema() {
                    this.populatingSchema = true;
                    this.error = '';
                    try {
                        const response = await axios.post(`${apiBase}/fields/${this.dbId}/${this.tableId}/populate`);
                        if (response.data.status === 'success') {
                            this.success = `Successfully populated ${response.data.total_fields || 0} fields`;
                            await this.loadSchema();
                        } else {
                            this.error = response.data.message || 'Failed to populate schema';
                        }
                    } catch (error) {
                        this.error = 'Error populating schema: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.populatingSchema = false;
                    }
                },
                async loadIndexes() {
                    this.loadingIndexes = true;
                    this.indexError = '';
                    try {
                        const response = await axios.get(`${apiBase}/indexes/${this.dbId}/${this.tableId}`);
                        if (response.data.status === 'success' && response.data.result) {
                            // Convert the result object to an array
                            this.indexes = Object.keys(response.data.result).map(name => ({
                                name: name,
                                key: response.data.result[name]
                            }));
                        } else {
                            this.indexes = [];
                        }
                    } catch (error) {
                        this.indexError = 'Error loading indexes: ' + (error.response?.data?.message || error.message);
                        this.indexes = [];
                    } finally {
                        this.loadingIndexes = false;
                    }
                },
                async createIndex() {
                    if (!this.newIndexFields || !this.newIndexFields.trim()) {
                        this.indexCreateError = 'Please enter at least one field name';
                        return;
                    }

                    this.creatingIndex = true;
                    this.indexCreateError = '';
                    try {
                        const response = await axios.post(`${apiBase}/indexes/${this.dbId}/${this.tableId}`, {
                            index_fields: this.newIndexFields.trim()
                        });
                        if (response.data.status === 'success') {
                            this.indexSuccess = 'Index created successfully';
                            this.showCreateIndexDialog = false;
                            this.newIndexFields = '';
                            await this.loadIndexes();
                            setTimeout(() => {
                                this.indexSuccess = '';
                            }, 3000);
                        } else {
                            this.indexCreateError = response.data.message || 'Failed to create index';
                        }
                    } catch (error) {
                        this.indexCreateError = error.response?.data?.message || error.message || 'Error creating index';
                    } finally {
                        this.creatingIndex = false;
                    }
                },
                async createTextIndex() {
                    if (!this.newTextIndexFields || !this.newTextIndexFields.trim()) {
                        this.indexCreateError = 'Please enter at least one field name';
                        return;
                    }

                    this.creatingIndex = true;
                    this.indexCreateError = '';
                    try {
                        const response = await axios.post(`${apiBase}/text_index/${this.dbId}/${this.tableId}`, {
                            index_fields: this.newTextIndexFields.trim()
                        });
                        if (response.data.status === 'success') {
                            this.indexSuccess = 'Text index created successfully';
                            this.showCreateTextIndexDialog = false;
                            this.newTextIndexFields = '';
                            await this.loadIndexes();
                            setTimeout(() => {
                                this.indexSuccess = '';
                            }, 3000);
                        } else {
                            this.indexCreateError = response.data.message || 'Failed to create text index';
                        }
                    } catch (error) {
                        this.indexCreateError = error.response?.data?.message || error.message || 'Error creating text index';
                    } finally {
                        this.creatingIndex = false;
                    }
                },
                confirmDeleteIndex(indexName) {
                    this.indexToDelete = indexName;
                    this.showDeleteIndexDialog = true;
                },
                async deleteIndex() {
                    if (!this.indexToDelete) return;

                    this.deletingIndex = this.indexToDelete;
                    try {
                        const response = await axios.delete(`${apiBase}/indexes/${this.dbId}/${this.tableId}/${this.indexToDelete}`);
                        if (response.data.status === 'success') {
                            this.indexSuccess = 'Index deleted successfully';
                            this.showDeleteIndexDialog = false;
                            this.indexToDelete = '';
                            await this.loadIndexes();
                            setTimeout(() => {
                                this.indexSuccess = '';
                            }, 3000);
                        } else {
                            this.indexError = response.data.message || 'Failed to delete index';
                        }
                    } catch (error) {
                        this.indexError = error.response?.data?.message || error.message || 'Error deleting index';
                    } finally {
                        this.deletingIndex = '';
                    }
                },
                confirmDeleteAllIndexes() {
                    // Only show dialog if there are custom indexes (more than just _id_)
                    if (this.indexes.length <= 1) {
                        this.indexError = 'No custom indexes to delete';
                        return;
                    }
                    this.showDeleteAllIndexesDialog = true;
                },
                async deleteAllIndexes() {
                    this.deletingAllIndexes = true;
                    this.indexError = '';
                    try {
                        const response = await axios.post(`${apiBase}/indexes/${this.dbId}/${this.tableId}/all`);
                        if (response.data.status === 'success') {
                            this.indexSuccess = response.data.message || 'All indexes deleted successfully';
                            this.showDeleteAllIndexesDialog = false;
                            await this.loadIndexes();
                            setTimeout(() => {
                                this.indexSuccess = '';
                            }, 5000);
                        } else {
                            this.indexError = response.data.message || 'Failed to delete all indexes';
                        }
                    } catch (error) {
                        this.indexError = error.response?.data?.message || error.message || 'Error deleting all indexes';
                    } finally {
                        this.deletingAllIndexes = false;
                    }
                },
                async syncDataDictionary() {
                    if (!confirm('This will remove any fields from the data dictionary that do not exist in the actual data. Continue?')) {
                        return;
                    }
                    
                    this.syncingFields = true;
                    this.error = '';
                    this.success = '';
                    
                    try {
                        const response = await axios.post(`${apiBase}/fields/${this.dbId}/${this.tableId}/sync`);
                        if (response.data.status === 'success') {
                            const removed = response.data.fields_removed || 0;
                            const added = response.data.fields_added || 0;
                            const total = response.data.total_fields || 0;
                            
                            let message = `Data dictionary synced successfully. `;
                            if (removed > 0 || added > 0) {
                                message += `${removed} field(s) removed, ${added} field(s) added. `;
                            }
                            message += `Total fields: ${total}`;
                            
                            this.success = message;
                            await this.loadSchema();
                        } else {
                            this.error = response.data.message || 'Failed to sync data dictionary';
                        }
                    } catch (error) {
                        this.error = 'Error syncing data dictionary: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.syncingFields = false;
                    }
                },
                async addField() {
                    if (!this.newField.name || !this.newField.data_type) {
                        this.error = 'Field name and data type are required';
                        return;
                    }
                    try {
                        const fieldData = {
                            name: this.newField.name,
                            label: this.newField.label || this.newField.name,
                            data_type: this.newField.data_type,
                            column_type: this.newField.column_type || null,
                            description: ''
                        };
                        const response = await axios.post(
                            `${apiBase}/fields/${this.dbId}/${this.tableId}`,
                            fieldData
                        );
                        if (response.data.status === 'success') {
                            this.success = `Field ${this.newField.name} added successfully`;
                            this.showAddFieldDialog = false;
                            this.newField = { name: '', label: '', data_type: 'string', column_type: '' };
                            await this.loadSchema();
                        } else {
                            this.error = response.data.message || 'Failed to add field';
                        }
                    } catch (error) {
                        this.error = 'Error adding field: ' + (error.response?.data?.message || error.message);
                    }
                },
                async deleteField(field) {
                    if (!confirm(`Delete field "${field.name}"? This will remove the field metadata from the data dictionary.`)) return;
                    try {
                        const response = await axios.post(
                            `${apiBase}/fields/${this.dbId}/${this.tableId}/${field.name}/delete`
                        );
                        if (response.data.status === 'success') {
                            this.success = `Field ${field.name} deleted successfully`;
                            this.selectedField = null;
                            await this.loadSchema();
                        } else {
                            this.error = response.data.message || 'Failed to delete field';
                        }
                    } catch (error) {
                        this.error = 'Error deleting field: ' + (error.response?.data?.message || error.message);
                    }
                },
                getColumnTypeColor(columnType) {
                    const colors = {
                        dimension: 'primary',
                        time_period: 'info',
                        measure: 'success',
                        attribute: 'warning',
                        indicator_id: 'purple',
                        indicator_name: 'purple',
                        geography: 'teal',
                        observation_value: 'orange',
                        periodicity: 'cyan'
                    };
                    return colors[columnType] || 'grey';
                },
                getSortLabel(value) {
                    const option = this.sortOptions.find(opt => opt.value === value);
                    return option ? option.text : 'Order';
                },
                addCodeListItem() {
                    if (!this.selectedField.code_list) {
                        this.selectedField.code_list = [];
                    }
                    this.selectedField.code_list.push({
                        code: null,
                        label: null,
                        description: ''
                    });
                    this.updateField();
                },
                removeCodeListItem(index) {
                    if (this.selectedField.code_list && this.selectedField.code_list.length > index) {
                        this.selectedField.code_list.splice(index, 1);
                        this.updateField();
                    }
                },
                clearCodeListReference() {
                    if (this.selectedField && this.selectedField.code_list_reference) {
                        this.selectedField.code_list_reference = {
                            id: '',
                            name: '',
                            version: '',
                            uri: '',
                            note: ''
                        };
                        this.updateField();
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
                        // Step 1: Upload file
                        const formData = new FormData();
                        formData.append('file', this.uploadFile);

                        const uploadResponse = await fetch(`${apiBase}/upload/${this.dbId}/${this.tableId}`, {
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

                            // Step 2: Delete existing data
                            this.deleting = true;
                            try {
                                const deleteResponse = await fetch(`${apiBase}/delete/${this.dbId}/${this.tableId}`, {
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
                                    // Step 3: Start import
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

                        // Loop until import is complete
                        while (hasMore && !this.importCancelled) {
                            const response = await fetch(`${apiBase}/import/${this.dbId}/${this.tableId}`, {
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
                                // Update progress display
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

                                // Check if more data needs to be imported
                                hasMore = progress.has_more === true && 
                                          progress.import_status !== 'completed';

                                // Small delay between chunks to prevent overwhelming the server
                                if (hasMore && !this.importCancelled) {
                                    await new Promise(resolve => setTimeout(resolve, 500));
                                }
                            } else {
                                // Import failed
                                this.importStatus = {
                                    status: 'error',
                                    message: importResult.message || 'Import failed'
                                };
                                hasMore = false;
                            }
                        }

                        // Handle cancellation
                        if (this.importCancelled) {
                            this.importStatus = {
                                status: 'warning',
                                message: 'Import cancelled by user'
                            };
                            await this.loadTableStats();
                            await this.loadPreviewData();
                            return;
                        }

                        // Import completed successfully
                        if (importResult && importResult.status === 'success') {
                            // Sync fields if enabled
                            if (this.syncFields) {
                                await this.syncFieldsAfterImport();
                            }
                            
                            this.showUploadDialog = false;
                            await this.loadTableStats();
                            await this.loadPreviewData();
                            await this.loadSchema();
                        } else {
                            // Final error state
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
                async loadTableStats() {
                    try {
                        const response = await axios.get(`${apiBase}/info/${this.dbId}/${this.tableId}`);
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
                        const response = await axios.get(`${apiBase}/data/${this.dbId}/${this.tableId}`, {
                            params: {
                                limit: this.previewLimit,
                                offset: offset
                            }
                        });

                        // API returns data directly: { rows_count, limit, offset, found, total, data }
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
                async deleteTableData() {
                    this.deleting = true;
                    this.deleteStatus = null;

                    try {
                        const response = await fetch(`${apiBase}/delete/${this.dbId}/${this.tableId}`, {
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
                    const url = `${apiBase}/data/${this.dbId}/${this.tableId}?format=csv&limit=${this.previewLimit}&offset=${offset}`;
                    window.open(url, '_blank');
                },
                async syncFieldsAfterImport() {
                    try {
                        const response = await axios.post(`${apiBase}/fields/${this.dbId}/${this.tableId}/sync`);
                        if (response.data.status === 'success') {
                            // Show success message in import status
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
                        // Don't fail the import if sync fails, just log it
                    }
                },
                async loadStudyLinks() {
                    this.loadingStudyLinks = true;
                    this.studyLinksError = '';
                    this.studyLinksSuccess = '';

                    try {
                        const response = await axios.get(`${apiBase}/${this.dbId}/${this.tableId}/studies`);
                        if (response.data.status === 'success') {
                            this.attachedStudies = response.data.studies || [];
                        } else {
                            this.studyLinksError = response.data.error || 'Failed to load study links';
                        }
                    } catch (error) {
                        this.studyLinksError = 'Error loading study links: ' + (error.response?.data?.error || error.message);
                        this.attachedStudies = [];
                    } finally {
                        this.loadingStudyLinks = false;
                    }
                },
                async searchStudies() {
                    if (!this.studySearchQuery || this.studySearchQuery.length < 2) {
                        this.studySearchResults = [];
                        return;
                    }

                    this.searchingStudies = true;
                    this.studySearchError = '';

                    try {
                        // Use catalog API for study search
                        const catalogApiBase = apiBase.replace('/api/tables', '/api/catalog');
                        const response = await axios.get(`${catalogApiBase}/search`, {
                            params: {
                                sk: this.studySearchQuery,
                                ps: 20,
                                page: 1
                            }
                        });

                        // Catalog API returns: { result: { rows: [...], found: N, total: N }, params: {...} }
                        if (response.data.result && response.data.result.rows) {
                            // Filter out already attached studies
                            const attachedSids = this.attachedStudies.map(s => s.sid);
                            this.studySearchResults = response.data.result.rows
                                .filter(study => !attachedSids.includes(study.id))
                                .map(study => ({
                                    id: study.id,
                                    sid: study.id,
                                    idno: study.idno,
                                    title: study.title,
                                    nation: study.nation,
                                    year_start: study.year_start
                                }));
                        } else {
                            this.studySearchResults = [];
                        }
                    } catch (error) {
                        this.studySearchError = 'Error searching studies: ' + (error.response?.data?.errors || error.response?.data?.error || error.message);
                        this.studySearchResults = [];
                    } finally {
                        this.searchingStudies = false;
                    }
                },
                async attachStudy(study) {
                    this.attachingStudy = true;
                    this.studyLinksError = '';

                    try {
                        const response = await axios.post(`${apiBase}/attach_to_study`, {
                            db_id: this.dbId,
                            table_id: this.tableId,
                            idno: study.idno
                        });

                        if (response.data.status === 'success') {
                            this.studyLinksSuccess = `Study "${study.title}" attached successfully`;
                            this.showAttachStudyDialog = false;
                            this.studySearchQuery = '';
                            this.studySearchResults = [];
                            await this.loadStudyLinks();
                            setTimeout(() => {
                                this.studyLinksSuccess = '';
                            }, 3000);
                        } else {
                            this.studyLinksError = response.data.error || 'Failed to attach study';
                        }
                    } catch (error) {
                        this.studyLinksError = 'Error attaching study: ' + (error.response?.data?.error || error.message);
                    } finally {
                        this.attachingStudy = false;
                    }
                },
                studyDataApiUrl(sid) {
                    return studyEditBaseUrl + sid + '/data-api';
                },
                confirmDetachStudy(study) {
                    this.studyToDetach = study;
                    this.showDetachStudyDialog = true;
                },
                async detachStudy() {
                    if (!this.studyToDetach) return;

                    this.detachingStudy = true;
                    this.studyLinksError = '';

                    try {
                        const response = await axios.post(`${apiBase}/detach_from_study`, {
                            db_id: this.dbId,
                            table_id: this.tableId,
                            sid: this.studyToDetach.sid
                        });

                        if (response.data.status === 'success') {
                            this.studyLinksSuccess = `Study "${this.studyToDetach.title}" detached successfully`;
                            this.showDetachStudyDialog = false;
                            this.studyToDetach = null;
                            await this.loadStudyLinks();
                            setTimeout(() => {
                                this.studyLinksSuccess = '';
                            }, 3000);
                        } else {
                            this.studyLinksError = response.data.error || 'Failed to detach study';
                        }
                    } catch (error) {
                        this.studyLinksError = 'Error detaching study: ' + (error.response?.data?.error || error.message);
                    } finally {
                        this.detachingStudy = false;
                    }
                }
            }
        };

        // Create Table Component
        const CreateTable = {
            template: `
                <div>
                    <div class="mb-3">
                        <v-btn text @click="$router.push('/')" class="mb-2">
                            <v-icon left>mdi-arrow-left</v-icon>
                            Back to Tables
                        </v-btn>
                        <h2>Create New Table</h2>
                    </div>
                    <v-main>
                        <v-container>
                            <v-alert v-if="error" type="error" dismissible @input="error = ''" class="mb-4">
                                {{ error }}
                            </v-alert>

                            <v-alert v-if="success" type="success" dismissible @input="success = ''" class="mb-4">
                                {{ success }}
                            </v-alert>

                            <v-card>
                                <v-card-title>Table Information</v-card-title>
                                <v-card-text>
                                    <v-form ref="form">
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="formData.db_id"
                                                    label="Database ID *"
                                                    outlined
                                                    dense
                                                    required
                                                    :rules="[
                                                        v => !!v || 'Database ID is required',
                                                        v => !v || /^[a-zA-Z0-9_]+$/.test(v) || 'Only alphanumeric characters and underscores are allowed'
                                                    ]"
                                                    @input="formData.db_id = formData.db_id ? formData.db_id.toLowerCase().replace(/[^a-z0-9_]/g, '') : ''"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="formData.table_id"
                                                    label="Table ID *"
                                                    outlined
                                                    dense
                                                    required
                                                    :rules="[
                                                        v => !!v || 'Table ID is required',
                                                        v => !v || /^[a-zA-Z0-9_]+$/.test(v) || 'Only alphanumeric characters and underscores are allowed'
                                                    ]"
                                                    @input="formData.table_id = formData.table_id ? formData.table_id.toLowerCase().replace(/[^a-z0-9_]/g, '') : ''"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12">
                                                <v-text-field
                                                    v-model="formData.title"
                                                    label="Title"
                                                    outlined
                                                    dense
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12">
                                                <v-textarea
                                                    v-model="formData.description"
                                                    label="Description"
                                                    outlined
                                                    rows="3"
                                                ></v-textarea>
                                            </v-col>
                                        </v-row>
                                    </v-form>
                                </v-card-text>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn text @click="$router.push('/')">
                                        Cancel
                                    </v-btn>
                                    <v-btn 
                                        color="primary" 
                                        @click="createTable" 
                                        :loading="creating"
                                        :disabled="!formData.db_id || !formData.table_id"
                                    >
                                        <v-icon left>mdi-content-save</v-icon>
                                        Create Table
                                    </v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-container>
                    </v-main>
                </div>
            `,
            data() {
                return {
                    adminDashboardUrl: adminDashboardUrl,
                    error: '',
                    success: '',
                    creating: false,
                    formData: {
                        db_id: '',
                        table_id: '',
                        title: '',
                        description: '',
                        data_dictionary: []
                    }
                };
            },
            methods: {
                async createTable() {
                    if (!this.$refs.form.validate()) {
                        return;
                    }
                    
                    if (!this.formData.db_id || !this.formData.table_id) {
                        this.error = 'Database ID and Table ID are required';
                        return;
                    }
                    
                    this.creating = true;
                    this.error = '';
                    try {
                        const payload = {
                            title: this.formData.title || '',
                            description: this.formData.description || '',
                            data_dictionary: this.formData.data_dictionary || []
                        };
                        const response = await axios.post(
                            `${apiBase}/create_table/${this.formData.db_id}/${this.formData.table_id}`,
                            payload
                        );
                        if (response.data.status === 'success') {
                            this.success = 'Table created successfully';
                            // Navigate to edit page after a short delay
                            setTimeout(() => {
                                this.$router.push({ 
                                    name: 'edit', 
                                    params: { 
                                        db_id: this.formData.db_id, 
                                        table_id: this.formData.table_id 
                                    } 
                                });
                            }, 1000);
                        } else {
                            this.error = response.data.message || 'Failed to create table';
                        }
                    } catch (error) {
                        this.error = 'Error creating table: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.creating = false;
                    }
                }
            }
        };

        // Router Configuration
        const routes = [
            { path: '/', component: TablesList },
            { path: '/create', component: CreateTable },
            { path: '/edit/:db_id/:table_id', name: 'edit', component: EditTable }
        ];

        const router = new VueRouter({
            mode: 'hash',
            base: '/admin/tables',
            routes
        });

        // Vue App
        new Vue({
            el: '#app',
            router,
            vuetify: new Vuetify({
                theme: {
                    themes: {
                        light: {
                            primary: '#1976D2',
                            secondary: '#424242',
                            accent: '#82B1FF',
                            error: '#FF5252',
                            info: '#2196F3',
                            success: '#4CAF50',
                            warning: '#FB8C00'
                        }
                    }
                }
            })
        });
    </script>
</body>
</html>
