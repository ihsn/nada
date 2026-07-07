<!-- Fonts -->
<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
<link href="<?php echo base_url('javascript/mdi/css/materialdesignicons.min.css'); ?>" rel="stylesheet">

<!-- Vuetify 2 (load after admin header Vuetify 3 bundle) -->
<link href="<?php echo base_url('javascript/vuetify.min.css'); ?>" rel="stylesheet">

<!-- Vue.js, Axios, Vuetify, Vue Router -->
<script src="<?php echo base_url('javascript/vue.min.js'); ?>"></script>
<script src="<?php echo base_url('javascript/axios.min.js'); ?>"></script>
<script src="<?php echo base_url('javascript/vuetify.min.js'); ?>"></script>
<script src="<?php echo base_url('javascript/vue-router.min.js'); ?>"></script>

<style>
        /* Contain Vuetify inside admin5 template without full-page takeover */
        #filestore-app .v-application--wrap { min-height: unset; }
        #filestore-app { margin-top: 8px; }

        /*
         * Admin header loads Vuetify 3 CSS globally; this page uses Vuetify 2.
         * V3 rules can set on-primary text color without matching V2 backgrounds.
         */
        #filestore-app .v-btn.primary:not(.v-btn--outlined):not(.v-btn--text):not(.v-btn--flat) {
            background-color: #1976D2 !important;
            border-color: #1976D2 !important;
            color: #fff !important;
        }
        #filestore-app .v-btn.primary.v-btn--outlined {
            background-color: transparent !important;
            color: #1976D2 !important;
            border-color: currentColor !important;
        }
        #filestore-app .v-btn.secondary:not(.v-btn--outlined):not(.v-btn--text):not(.v-btn--flat) {
            background-color: #424242 !important;
            border-color: #424242 !important;
            color: #fff !important;
        }
        #filestore-app .v-btn:not(.primary):not(.secondary):not(.error):not(.success):not(.warning):not(.info):not(.v-btn--outlined):not(.v-btn--text):not(.v-btn--flat) {
            background-color: #f5f5f5 !important;
            color: rgba(0, 0, 0, 0.87) !important;
        }
        #filestore-app .v-btn.v-btn--outlined:not(.primary):not(.secondary) {
            background-color: transparent !important;
            color: rgba(0, 0, 0, 0.87) !important;
            border-color: rgba(0, 0, 0, 0.38) !important;
        }
        #filestore-app .v-application {
            background-color: transparent !important;
        }
        .file-preview-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin: 10px 0px;
            border: 1px solid gainsboro;
            background-color: #f5f5f5;
        }
        .file-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e0e0e0;
            border-radius: 4px;
            margin: 10px 0px;
            border: 1px solid gainsboro;
            background-color: #f5f5f5;
        }
    </style>

<div id="filestore-app">
    <v-app id="app">
        <router-view></router-view>
    </v-app>
</div>

    <script>
        const apiBase = '<?php echo $api_base_url; ?>';
        const baseUrl = '<?php echo base_url(); ?>';
        const filesPublicUrl = baseUrl + 'files/public';
        const pageTitle = '<?php echo $title; ?>';

        // Files List Component
        const FilesList = {
            template: `
                <div>
                    <v-main>
                        <v-container fluid>
                            <div class="mb-4 d-flex align-center justify-space-between">
                                <h1 class="mb-0"><?php echo t('file_manager');?></h1>
                                <div class="d-flex align-center">
                                    <v-btn color="primary" @click="$router.push('/upload')" class="mr-2">
                                        <v-icon left>mdi-upload</v-icon>
                                        Upload Files
                                    </v-btn>
                                    <v-btn outlined :loading="loading" :disabled="loading" @click="loadFiles">
                                        <v-icon v-if="!loading" left>mdi-refresh</v-icon>
                                        {{ loading ? 'Loading...' : 'Refresh' }}
                                    </v-btn>
                                </div>
                            </div>

                            <v-alert v-if="error" type="error" dismissible @input="error = ''" class="mb-4">
                                {{ error }}
                            </v-alert>

                            <v-alert v-if="success" type="success" dismissible @input="success = ''" class="mb-4">
                                {{ success }}
                            </v-alert>

                            <!-- Search and Filter -->
                            <v-card class="mb-4">
                                <v-card-text>
                                    <v-row>
                                        <v-col cols="12" md="8">
                                            <v-text-field
                                                v-model="searchKeywords"
                                                label="Search Files"
                                                prepend-inner-icon="mdi-magnify"
                                                outlined
                                                dense
                                                clearable
                                                @keyup.enter="loadFiles"
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" md="2">
                                            <v-select
                                                v-model="sortBy"
                                                :items="sortOptions"
                                                label="Sort By"
                                                outlined
                                                dense
                                                @change="loadFiles"
                                            ></v-select>
                                        </v-col>
                                        <v-col cols="12" md="2">
                                            <v-select
                                                v-model="sortOrder"
                                                :items="[{text: 'Ascending', value: 'asc'}, {text: 'Descending', value: 'desc'}]"
                                                label="Order"
                                                outlined
                                                dense
                                                @change="loadFiles"
                                            ></v-select>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Batch Actions -->
                            <v-card v-if="selectedFiles.length > 0" class="mb-4" color="primary" dark>
                                <v-card-text>
                                    <div class="d-flex align-center justify-space-between">
                                        <span>{{ selectedFiles.length }} file(s) selected</span>
                                        <v-btn color="error" @click="batchDelete" :loading="deleting">
                                            <v-icon left>mdi-delete</v-icon>
                                            Delete Selected
                                        </v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Files Table -->
                            <v-card v-if="loading && files.length === 0">
                                <v-card-text class="text-center py-12">
                                    <v-progress-circular indeterminate color="primary" size="64" class="mb-4"></v-progress-circular>
                                    <p class="text--secondary mt-4">Loading files...</p>
                                </v-card-text>
                            </v-card>

                            <v-card v-else-if="files.length > 0">
                                <v-card-title class="text-body-2 text-medium-emphasis">
                                    Found {{ totalFiles }} file(s)
                                </v-card-title>
                                
                                <v-data-table
                                    :headers="tableHeaders"
                                    :items="files"
                                    :loading="loading"
                                    item-key="file_name"
                                    :items-per-page="itemsPerPage"
                                    :page="currentPage"
                                    show-select
                                    v-model="selectedFiles"
                                    class="elevation-1"
                                >
                                    <template v-slot:item.preview="{ item }">
                                        <a :href="getFileUrl(item)" target="_blank" style="text-decoration: none;">
                                            <v-img
                                                v-if="item.is_image == 1"
                                                :src="getImageUrl(item.file_name)"
                                                class="file-preview-thumb"
                                                @error="handleImageError"
                                            ></v-img>
                                            <div v-else class="file-icon">
                                                <v-icon :color="getFileIconColor(item.file_ext)">{{ getFileIcon(item.file_ext) }}</v-icon>
                                            </div>
                                        </a>
                                    </template>

                                    <template v-slot:item.file_name="{ item }">
                                        <div class="d-flex flex-column">
                                            <a :href="getFileUrl(item)" target="_blank" style="text-decoration: none; color: inherit;">
                                                <span class="font-weight-medium">{{ item.file_name }}</span>
                                            </a>
                                            <span class="text-caption text-medium-emphasis">{{ item.file_path }}</span>
                                        </div>
                                    </template>

                                    <template v-slot:item.file_ext="{ item }">
                                        <v-chip small :color="getFileTypeColor(item.file_ext)" dark>
                                            {{ item.file_ext ? item.file_ext.toUpperCase() : 'N/A' }}
                                        </v-chip>
                                    </template>

                                    <template v-slot:item.file_size="{ item }">
                                        <span>{{ formatFileSize(item.file_size || 0) }}</span>
                                    </template>

                                    <template v-slot:item.changed="{ item }">
                                        <span>{{ formatDate(item.changed) }}</span>
                                    </template>

                                    <template v-slot:item.actions="{ item }">
                                        <div class="action-buttons">
                                            <v-btn icon small :href="getFileUrl(item)" target="_blank" color="primary">
                                                <v-icon small>mdi-eye</v-icon>
                                            </v-btn>
                                            <v-btn icon small @click="deleteFile(item.file_name)" color="error">
                                                <v-icon small>mdi-delete</v-icon>
                                            </v-btn>
                                        </div>
                                    </template>
                                </v-data-table>

                                <!-- Pagination -->
                                <div class="text-center pt-4" v-if="totalFiles > itemsPerPage">
                                    <v-pagination
                                        v-model="currentPage"
                                        :length="Math.ceil(totalFiles / itemsPerPage)"
                                        @input="loadFiles"
                                    ></v-pagination>
                                </div>
                            </v-card>

                            <v-card v-else>
                                <v-card-text class="text-center py-12">
                                    <v-icon size="64" color="grey lighten-1" class="mb-4">mdi-folder-off</v-icon>
                                    <h5 class="mb-2">No Files Found</h5>
                                    <p class="text--secondary mb-4">No files match your search criteria.</p>
                                    <v-btn color="primary" @click="$router.push('/upload')">
                                        <v-icon left>mdi-upload</v-icon>
                                        Upload Your First File
                                    </v-btn>
                                </v-card-text>
                            </v-card>
                        </v-container>
                    </v-main>
                </div>
            `,
            data() {
                return {
                    files: [],
                    loading: false,
                    error: '',
                    success: '',
                    searchKeywords: '',
                    filterType: '',
                    filterImages: false,
                    sortBy: 'changed',
                    sortOrder: 'desc',
                    currentPage: 1,
                    itemsPerPage: 15,
                    totalFiles: 0,
                    selectedFiles: [],
                    deleting: false,
                    tableHeaders: [
                        { text: 'Preview', value: 'preview', sortable: false, width: '80px' },
                        { text: 'File Name', value: 'file_name', sortable: true },
                        { text: 'Type', value: 'file_ext', sortable: true },
                        { text: 'Size', value: 'file_size', sortable: false },
                        { text: 'Upload Date', value: 'changed', sortable: true },
                        { text: 'Actions', value: 'actions', sortable: false, width: '120px' }
                    ],
                    sortOptions: [
                        { text: 'File Name', value: 'file_name' },
                        { text: 'Upload Date', value: 'changed' },
                        { text: 'File Type', value: 'file_ext' }
                    ]
                };
            },
            mounted() {
                this.loadFiles();
            },
            methods: {
                async loadFiles() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const offset = (this.currentPage - 1) * this.itemsPerPage;
                        const params = {
                            limit: this.itemsPerPage,
                            offset: offset,
                            sort_by: this.sortBy,
                            sort_order: this.sortOrder
                        };
                        
                        if (this.searchKeywords) {
                            params.search = this.searchKeywords;
                            params.field = 'file_name';
                        }
                        if (this.filterType) {
                            params.filter_type = this.filterType;
                        }
                        if (this.filterImages) {
                            params.filter_images = 'true';
                        }
                        
                        const response = await axios.get(apiBase, { params });
                        
                        if (response.data.status === 'success') {
                            this.files = response.data.files || [];
                            this.totalFiles = response.data.total || 0;
                        } else {
                            this.error = response.data.message || 'Failed to load files';
                        }
                    } catch (error) {
                        this.error = 'Error loading files: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.loading = false;
                    }
                },
                getFileUrl(item) {
                    // All filestore files are public - link directly to file path
                    // Construct URL: base_url() + 'files/public' + file_path + '/' + file_name
                    const filePath = item.file_path || '';
                    const fileName = encodeURIComponent(item.file_name);
                    // Remove leading slash from file_path if present, add trailing slash
                    const cleanPath = filePath.replace(/^\/+|\/+$/g, '');
                    const path = cleanPath ? cleanPath + '/' : '';
                    return `${filesPublicUrl}/${path}${fileName}`;
                },
                async deleteFile(filename) {
                    if (!confirm(`Delete file "${filename}"?\n\nThis action cannot be undone.`)) return;
                    
                    try {
                        const response = await axios.post(`${apiBase}/delete/${encodeURIComponent(filename)}`);
                        if (response.data.status === 'success') {
                            this.success = 'File deleted successfully';
                            this.loadFiles();
                            setTimeout(() => { this.success = ''; }, 3000);
                        } else {
                            this.error = response.data.message || 'Failed to delete file';
                        }
                    } catch (error) {
                        this.error = 'Error deleting file: ' + (error.response?.data?.message || error.message);
                    }
                },
                async batchDelete() {
                    if (!confirm(`Delete ${this.selectedFiles.length} file(s)?\n\nThis action cannot be undone.`)) return;
                    
                    this.deleting = true;
                    this.error = '';
                    
                    try {
                        const deletePromises = this.selectedFiles.map(file => 
                            axios.post(`${apiBase}/delete/${encodeURIComponent(file.file_name)}`)
                        );
                        
                        await Promise.all(deletePromises);
                        this.success = `${this.selectedFiles.length} file(s) deleted successfully`;
                        this.selectedFiles = [];
                        this.loadFiles();
                        setTimeout(() => { this.success = ''; }, 3000);
                    } catch (error) {
                        this.error = 'Error deleting files: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.deleting = false;
                    }
                },
                getImageUrl(filename) {
                    // Find file in list to get file_path
                    const file = this.files.find(f => f.file_name === filename);
                    if (file) {
                        return this.getFileUrl(file);
                    }
                    // Fallback if file not found
                    return `${filesPublicUrl}/${encodeURIComponent(filename)}`;
                },
                handleImageError(event) {
                    event.target.style.display = 'none';
                },
                getFileIcon(ext) {
                    const icons = {
                        'pdf': 'mdi-file-pdf',
                        'doc': 'mdi-file-word',
                        'docx': 'mdi-file-word',
                        'xls': 'mdi-file-excel',
                        'xlsx': 'mdi-file-excel',
                        'zip': 'mdi-folder-zip',
                        'txt': 'mdi-file-document',
                        'csv': 'mdi-file-delimited'
                    };
                    return icons[ext?.toLowerCase()] || 'mdi-file';
                },
                getFileIconColor(ext) {
                    const colors = {
                        'pdf': 'red',
                        'doc': 'blue',
                        'docx': 'blue',
                        'xls': 'green',
                        'xlsx': 'green',
                        'zip': 'orange',
                        'txt': 'grey',
                        'csv': 'teal'
                    };
                    return colors[ext?.toLowerCase()] || 'grey';
                },
                getFileTypeColor(ext) {
                    const colors = {
                        'pdf': 'red',
                        'doc': 'blue',
                        'docx': 'blue',
                        'xls': 'green',
                        'xlsx': 'green',
                        'zip': 'orange',
                        'txt': 'grey',
                        'csv': 'teal',
                        'jpg': 'purple',
                        'jpeg': 'purple',
                        'png': 'purple',
                        'gif': 'purple'
                    };
                    return colors[ext?.toLowerCase()] || 'grey';
                },
                formatFileSize(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                },
                formatDate(timestamp) {
                    if (!timestamp) return 'N/A';
                    try {
                        const date = new Date(timestamp * 1000);
                        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } catch (e) {
                        return 'N/A';
                    }
                }
            }
        };

        // File Upload Component
        const FileUpload = {
            template: `
                <div>
                    <div class="mb-3">
                        <v-btn text @click="$router.push('/')" class="mb-2">
                            <v-icon left>mdi-arrow-left</v-icon>
                            Back to Files
                        </v-btn>
                        <h2>Upload Files</h2>
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
                                <v-card-title>Upload Files</v-card-title>
                                <v-card-text>
                                    <v-file-input
                                        v-model="selectedFiles"
                                        label="Select Files"
                                        multiple
                                        prepend-icon="mdi-paperclip"
                                        outlined
                                        @change="handleFileSelect"
                                    ></v-file-input>

                                    <v-list v-if="filesToUpload.length > 0" class="mt-4">
                                        <v-list-item v-for="(file, index) in filesToUpload" :key="index">
                                            <v-list-item-content>
                                                <v-list-item-title>{{ file.name }}</v-list-item-title>
                                                <v-list-item-subtitle>
                                                    <span v-if="!file.error">{{ formatFileSize(file.size) }}</span>
                                                    <span v-else class="error--text">{{ file.errorMessage || 'Upload failed' }}</span>
                                                </v-list-item-subtitle>
                                            </v-list-item-content>
                                            <v-list-item-action>
                                                <v-progress-linear
                                                    v-if="file.uploading"
                                                    :value="file.progress"
                                                    color="primary"
                                                    height="6"
                                                ></v-progress-linear>
                                                <v-icon v-else-if="file.uploaded" color="success">mdi-check-circle</v-icon>
                                                <v-icon v-else-if="file.error" color="error">mdi-alert-circle</v-icon>
                                                <v-btn v-else icon small @click="removeFile(index)">
                                                    <v-icon small>mdi-close</v-icon>
                                                </v-btn>
                                            </v-list-item-action>
                                        </v-list-item>
                                    </v-list>

                                    <v-card-actions>
                                        <v-spacer></v-spacer>
                                        <v-btn text @click="$router.push('/')">Cancel</v-btn>
                                        <v-btn 
                                            color="primary" 
                                            @click="uploadFiles" 
                                            :loading="uploading"
                                            :disabled="filesToUpload.length === 0"
                                        >
                                            <v-icon left>mdi-upload</v-icon>
                                            Upload {{ filesToUpload.length }} File(s)
                                        </v-btn>
                                    </v-card-actions>
                                </v-card-text>
                            </v-card>
                        </v-container>
                    </v-main>
                </div>
            `,
            data() {
                return {
                    selectedFiles: [],
                    filesToUpload: [],
                    uploading: false,
                    error: '',
                    success: ''
                };
            },
            methods: {
                handleFileSelect(files) {
                    this.filesToUpload = [];
                    if (files) {
                        Array.from(files).forEach(file => {
                            if (this.validateFile(file)) {
                                this.filesToUpload.push({
                                    file: file,
                                    name: file.name,
                                    size: file.size,
                                    uploading: false,
                                    uploaded: false,
                                    error: false,
                                    errorMessage: '',
                                    progress: 0
                                });
                            }
                        });
                    }
                },
                validateFile(file) {
                    const maxSize = 100 * 1024 * 1024; // 100MB
                    if (file.size > maxSize) {
                        this.error = `File "${file.name}" exceeds maximum size of 100MB`;
                        return false;
                    }
                    return true;
                },
                removeFile(index) {
                    this.filesToUpload.splice(index, 1);
                },
                async uploadFiles() {
                    if (this.filesToUpload.length === 0) return;
                    
                    this.uploading = true;
                    this.error = '';
                    this.success = '';
                    
                    let successCount = 0;
                    let errorCount = 0;
                    
                    for (let i = 0; i < this.filesToUpload.length; i++) {
                        const fileItem = this.filesToUpload[i];
                        fileItem.uploading = true;
                        fileItem.progress = 0;
                        
                        try {
                            const formData = new FormData();
                            formData.append('file', fileItem.file);
                            
                            const response = await axios.post(apiBase, formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                },
                                onUploadProgress: (progressEvent) => {
                                    fileItem.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                                }
                            });
                            
                            if (response.data.status === 'success') {
                                fileItem.uploaded = true;
                                successCount++;
                            } else {
                                fileItem.error = true;
                                fileItem.errorMessage = this.extractErrorMessage(response.data.message || 'Upload failed');
                                errorCount++;
                            }
                        } catch (error) {
                            fileItem.error = true;
                            fileItem.errorMessage = this.extractErrorMessage(
                                error.response?.data?.message || error.message || 'Upload failed'
                            );
                            errorCount++;
                        } finally {
                            fileItem.uploading = false;
                        }
                    }
                    
                    this.uploading = false;
                    
                    if (successCount > 0) {
                        this.success = `${successCount} file(s) uploaded successfully`;
                        if (errorCount === 0) {
                            setTimeout(() => {
                                this.$router.push('/');
                            }, 2000);
                        }
                    }
                    if (errorCount > 0) {
                        const errorFiles = this.filesToUpload.filter(f => f.error).map(f => f.name).join(', ');
                        this.error = `${errorCount} file(s) failed to upload: ${errorFiles}`;
                    }
                },
                formatFileSize(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                },
                extractErrorMessage(message) {
                    if (!message) return 'Upload failed';
                    
                    // Remove HTML tags if present
                    let cleanMessage = message.replace(/<[^>]*>/g, '');
                    
                    // Extract meaningful error from API response
                    // Format: "UPLOAD_FAILED::files\/public\/... - error:: <p>message</p>"
                    const errorMatch = cleanMessage.match(/error::\s*(.+)/i);
                    if (errorMatch) {
                        cleanMessage = errorMatch[1].trim();
                    }
                    
                    // Remove "UPLOAD_FAILED::" prefix if present
                    cleanMessage = cleanMessage.replace(/^UPLOAD_FAILED::\s*/i, '');
                    
                    // Clean up any remaining path info
                    cleanMessage = cleanMessage.replace(/files[^:]*:\s*/i, '');
                    
                    return cleanMessage || 'Upload failed';
                }
            }
        };

        // File View Component
        const FileView = {
            props: ['filename'],
            template: `
                <div>
                    <div class="mb-3">
                        <v-btn text @click="$router.push('/')" class="mb-2">
                            <v-icon left>mdi-arrow-left</v-icon>
                            Back to Files
                        </v-btn>
                        <h2>File Details</h2>
                    </div>
                    <v-main>
                        <v-container>
                            <v-alert v-if="error" type="error" dismissible @input="error = ''" class="mb-4">
                                {{ error }}
                            </v-alert>

                            <v-card v-if="loading">
                                <v-card-text class="text-center py-12">
                                    <v-progress-circular indeterminate color="primary" size="64" class="mb-4"></v-progress-circular>
                                    <p class="text--secondary mt-4">Loading file information...</p>
                                </v-card-text>
                            </v-card>

                            <v-card v-else-if="file">
                                <v-card-title>File Information</v-card-title>
                                <v-card-text>
                                    <v-row>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                :value="file.file_name"
                                                label="File Name"
                                                readonly
                                                outlined
                                                dense
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                :value="file.file_ext ? file.file_ext.toUpperCase() : 'N/A'"
                                                label="File Type"
                                                readonly
                                                outlined
                                                dense
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                :value="formatFileSize(file.file_size || 0)"
                                                label="File Size"
                                                readonly
                                                outlined
                                                dense
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                :value="formatDate(file.changed)"
                                                label="Upload Date"
                                                readonly
                                                outlined
                                                dense
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field
                                                :value="file.file_path"
                                                label="File Path"
                                                readonly
                                                outlined
                                                dense
                                            ></v-text-field>
                                        </v-col>
                                        <v-col cols="12" v-if="file.is_image == 1">
                                            <v-img
                                                :src="getImageUrl(file.file_name)"
                                                max-height="500"
                                                contain
                                                class="mb-4"
                                            ></v-img>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                                <v-card-actions>
                                    <v-spacer></v-spacer>
                                    <v-btn color="primary" :href="getFileUrl(file)" target="_blank">
                                        <v-icon left>mdi-open-in-new</v-icon>
                                        Open File
                                    </v-btn>
                                    <v-btn color="error" @click="deleteFile">
                                        <v-icon left>mdi-delete</v-icon>
                                        Delete
                                    </v-btn>
                                </v-card-actions>
                            </v-card>
                        </v-container>
                    </v-main>
                </div>
            `,
            data() {
                return {
                    file: null,
                    loading: false,
                    error: ''
                };
            },
            mounted() {
                this.loadFileInfo();
            },
            watch: {
                '$route'(to, from) {
                    this.loadFileInfo();
                }
            },
            methods: {
                async loadFileInfo() {
                    this.loading = true;
                    this.error = '';
                    try {
                        const filename = decodeURIComponent(this.$route.params.filename || this.filename);
                        const response = await axios.get(`${apiBase}/${encodeURIComponent(filename)}`);
                        
                        if (response.data.status === 'success') {
                            this.file = response.data.file;
                        } else {
                            this.error = response.data.message || 'File not found';
                        }
                    } catch (error) {
                        this.error = 'Error loading file: ' + (error.response?.data?.message || error.message);
                    } finally {
                        this.loading = false;
                    }
                },
                async deleteFile() {
                    const filename = decodeURIComponent(this.$route.params.filename || this.filename);
                    if (!confirm(`Delete file "${filename}"?\n\nThis action cannot be undone.`)) return;
                    
                    try {
                        const response = await axios.post(`${apiBase}/delete/${encodeURIComponent(filename)}`);
                        if (response.data.status === 'success') {
                            this.$router.push('/');
                        } else {
                            this.error = response.data.message || 'Failed to delete file';
                        }
                    } catch (error) {
                        this.error = 'Error deleting file: ' + (error.response?.data?.message || error.message);
                    }
                },
                getImageUrl(filename) {
                    // Use public file path
                    if (this.file) {
                        return this.getFileUrl(this.file);
                    }
                    // Fallback if file not loaded
                    return `${filesPublicUrl}/${encodeURIComponent(filename)}`;
                },
                getFileUrl(item) {
                    // All filestore files are public - link directly to file path
                    // Construct URL: base_url() + 'files/public' + file_path + '/' + file_name
                    const filePath = item.file_path || '';
                    const fileName = encodeURIComponent(item.file_name);
                    const cleanPath = filePath.replace(/^\/+|\/+$/g, '');
                    const path = cleanPath ? cleanPath + '/' : '';
                    return `${filesPublicUrl}/${path}${fileName}`;
                },
                formatFileSize(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                },
                formatDate(timestamp) {
                    if (!timestamp) return 'N/A';
                    try {
                        const date = new Date(timestamp * 1000);
                        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } catch (e) {
                        return 'N/A';
                    }
                }
            }
        };

        // Router Configuration
        const routes = [
            { path: '/', component: FilesList },
            { path: '/upload', component: FileUpload },
            { path: '/view/:filename', name: 'view', component: FileView, props: true }
        ];

        const router = new VueRouter({
            mode: 'hash',
            base: '/admin/filestore',
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

