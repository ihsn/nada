<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Vue.js and Axios -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        a{
            text-decoration: none;
        },
        .card {
            margin-bottom: 30px;
        }
        .stat-card {
            border-left: 4px solid #0d6efd;
        }
        .stat-label {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 1.5em;
            font-weight: 600;
            color: #212529;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(13, 110, 253, 0.3);
            border-radius: 50%;
            border-top-color: #0d6efd;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .processing-indicator {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            margin: 15px 0;
            color: #212529;
            font-weight: 500;
        }
        .settings-panel {
            display: none;
        }
        .settings-panel.show {
            display: block;
        }
    </style>
</head>
<body>
    <div id="app" class="main-container">
        <div class="container px-4">
            <h1 class="mb-4">Database Logs Cleanup</h1>
            
            <!-- Database Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Database Statistics</h2>
                </div>
                <div class="card-body">
                    <div v-if="loadingStats" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading statistics...</p>
                    </div>
                    <div v-else>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-label">Total Rows</div>
                                        <div class="stat-value">{{ formatNumber(stats.total_rows) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" v-if="stats.table_size_mb">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-label">Table Size</div>
                                        <div class="stat-value">{{ stats.table_size_mb }} MB</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-label">Oldest Log</div>
                                        <div class="stat-value" style="font-size: 1em;">{{ stats.oldest_log_date || 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" v-if="stats.retention_days">
                            <strong>Retention Policy:</strong> Logs older than {{ stats.retention_days }} days (since {{ stats.cutoff_date }}) will be exported to CSV files and permanently removed from the database.
                            <span v-if="stats.csv_dir && !stats.directory_in_webroot">
                                <br><strong>Archive path:</strong> <code>{{ stats.csv_dir }}</code>
                                <span v-if="stats.csv_dir_writable" class="badge bg-success ms-2">Writable</span>
                                <span v-else-if="stats.csv_dir_exists" class="badge bg-danger ms-2">Not writable</span>
                                <span v-else class="badge bg-warning text-dark ms-2">Directory not found &mdash; will be created on first cleanup</span>
                            </span>
                        </div>
                        <div class="alert alert-warning" v-if="stats.csv_dir_exists && !stats.csv_dir_writable && !stats.directory_in_webroot">
                            <strong>&#9888; Archive directory is not writable.</strong>
                            The web server process does not have write permission to <code>{{ stats.csv_dir }}</code>. Cleanup is disabled until this is fixed.
                        </div>
                        <div class="alert alert-danger" v-if="stats.directory_in_webroot">
                            <strong>&#9888; Security Warning: Log archive directory is inside the web root.</strong><br>
                            The configured archive path is publicly accessible over HTTP. Backups are disabled until this is resolved.<br>
                            Please update <code>db_logs_csv_dir</code> in <code>application/config/db_logs.php</code> to a directory <strong>outside</strong> the web root (e.g. one level above <code>FCPATH</code>).
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cleanup Process -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Cleanup Process</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        The cleanup will move old log entries to CSV to reduce size of the logs table for better performance.
                    </div>
                    
                    <div class="mb-3">
                        <button 
                            @click="showSettings = !showSettings"
                            class="btn btn-link p-0"
                            type="button">
                            {{ showSettings ? 'Hide' : 'Show' }} Settings
                        </button>
                    </div>
                    
                    <div class="settings-panel card bg-light mb-3" :class="{show: showSettings}">
                        <div class="card-body">
                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-auto">
                                    <label class="form-label fw-bold mb-0">Chunk Size:</label>
                                </div>
                                <div class="col-auto">
                                    <input 
                                        type="number" 
                                        v-model.number="chunkSize" 
                                        min="1" 
                                        max="20000"
                                        class="form-control"
                                        style="width: 150px;"
                                        :disabled="processing">
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">(1 - 20000 rows per chunk)</small>
                                </div>
                            </div>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label class="form-label fw-bold mb-0">Time Limit:</label>
                                </div>
                                <div class="col-auto">
                                    <input 
                                        type="number" 
                                        v-model.number="timeLimit" 
                                        min="1" 
                                        max="30"
                                        class="form-control"
                                        style="width: 150px;"
                                        :disabled="processing">
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">(1 - 30 seconds per API call)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="cleanupStatus">
                        <div v-if="cleanupStatus.status === 'completed'" class="alert alert-success">
                            Cleanup completed successfully!                            
                        </div>
                        
                        <div v-else-if="cleanupStatus.status === 'stopped'" class="alert alert-warning">
                            Cleanup stopped. Click Start to continue processing.
                        </div>
                        
                        <div v-else-if="cleanupStatus.status === 'failed'" class="alert alert-danger">
                            Cleanup failed: {{ cleanupStatus.message || 'Unknown error' }}
                        </div>
                        
                        <div v-else>
                            <div class="processing-indicator" v-if="processing || (cleanupStatus && !cleanupStatus.status)">
                                <span class="spinner"></span>
                                <span>Processing cleanup...</span>
                            </div>
                            <div class="mb-2">
                                <strong>Rows Processed:</strong> {{ formatNumber(cleanupStatus.rows_processed || 0) }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="processing-indicator" v-if="processing && !cleanupStatus">
                        <span class="spinner"></span>
                        <span>Running cleanup...</span>
                    </div>
                    
                    <div class="mt-3">
                        <button 
                            @click="startCleanup" 
                            :disabled="processing || stats.directory_in_webroot || (stats.csv_dir_exists && !stats.csv_dir_writable) || (cleanupStatus && cleanupStatus.rows_processed > 0 && cleanupStatus.status !== 'completed' && cleanupStatus.status !== 'stopped' && cleanupStatus.status !== 'failed')"
                            class="btn btn-primary me-2">
                            Start Cleanup
                        </button>
                        
                        <button 
                            v-if="cleanupStatus && cleanupStatus.rows_processed > 0 && !cleanupStatus.status"
                            @click="stopCleanup"
                            :disabled="processing"
                            class="btn btn-danger">
                            Stop
                        </button>
                    </div>
                    
                    <div v-if="statusMessage" :class="['alert', 'mt-3', statusType === 'success' ? 'alert-success' : statusType === 'error' ? 'alert-danger' : 'alert-info']" v-html="statusMessage"></div>
                </div>
            </div>
            
            <!-- Exported CSV Files -->
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Exported CSV Files</h2>
                </div>
                <div class="card-body">
                    <div v-if="loadingFiles" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading files...</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="file in files" :key="file.filename">
                                    <td>{{ file.filename }}</td>
                                    <td>{{ file.date }}</td>
                                    <td>{{ file.size_formatted }}</td>
                                </tr>
                                <tr v-if="files.length === 0">
                                    <td colspan="3" class="text-center text-muted">No exported files</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Logs Cleanup -->
    <div id="api-logs-app" class="main-container">
        <div class="container px-4">
            <h1 class="mb-4 mt-5">API Logs Cleanup</h1>

            <!-- API Logs Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Database Statistics</h2>
                </div>
                <div class="card-body">
                    <div v-if="loadingStats" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading statistics...</p>
                    </div>
                    <div v-else>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-label">Total Rows</div>
                                        <div class="stat-value">{{ formatNumber(stats.total_rows) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" v-if="stats.table_size_mb">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-label">Table Size</div>
                                        <div class="stat-value">{{ stats.table_size_mb }} MB</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="stat-label">Oldest Log</div>
                                        <div class="stat-value" style="font-size: 1em;">{{ stats.oldest_log_date || 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info" v-if="stats.retention_days">
                            <strong>Retention Policy:</strong> Logs older than {{ stats.retention_days }} days (since {{ stats.cutoff_date }}) will be exported to CSV files and permanently removed from the database.
                            <span v-if="stats.csv_dir && !stats.directory_in_webroot">
                                <br><strong>Archive path:</strong> <code>{{ stats.csv_dir }}</code>
                                <span v-if="stats.csv_dir_writable" class="badge bg-success ms-2">Writable</span>
                                <span v-else-if="stats.csv_dir_exists" class="badge bg-danger ms-2">Not writable</span>
                                <span v-else class="badge bg-warning text-dark ms-2">Directory not found &mdash; will be created on first cleanup</span>
                            </span>
                        </div>
                        <div class="alert alert-warning" v-if="stats.csv_dir_exists && !stats.csv_dir_writable && !stats.directory_in_webroot">
                            <strong>&#9888; Archive directory is not writable.</strong>
                            The web server process does not have write permission to <code>{{ stats.csv_dir }}</code>. Cleanup is disabled until this is fixed.
                        </div>
                        <div class="alert alert-danger" v-if="stats.directory_in_webroot">
                            <strong>&#9888; Security Warning: Log archive directory is inside the web root.</strong><br>
                            The configured archive path is publicly accessible over HTTP. Backups are disabled until this is resolved.<br>
                            Please update <code>db_logs_csv_dir</code> in <code>application/config/db_logs.php</code> to a directory <strong>outside</strong> the web root (e.g. one level above <code>FCPATH</code>).
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Logs Cleanup Process -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Cleanup Process</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-3">
                        The cleanup will move old API log entries to CSV files (named <code>api_log-YYYY-MM.csv</code>) and remove them from the database.
                    </div>

                    <div class="mb-3">
                        <button @click="showSettings = !showSettings" class="btn btn-link p-0" type="button">
                            {{ showSettings ? 'Hide' : 'Show' }} Settings
                        </button>
                    </div>

                    <div class="settings-panel card bg-light mb-3" :class="{show: showSettings}">
                        <div class="card-body">
                            <div class="row g-3 align-items-center mb-3">
                                <div class="col-auto">
                                    <label class="form-label fw-bold mb-0">Chunk Size:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="number" v-model.number="chunkSize" min="1" max="20000" class="form-control" style="width: 150px;" :disabled="processing">
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">(1 - 20000 rows per chunk)</small>
                                </div>
                            </div>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label class="form-label fw-bold mb-0">Time Limit:</label>
                                </div>
                                <div class="col-auto">
                                    <input type="number" v-model.number="timeLimit" min="1" max="30" class="form-control" style="width: 150px;" :disabled="processing">
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted">(1 - 30 seconds per API call)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="cleanupStatus">
                        <div v-if="cleanupStatus.status === 'completed'" class="alert alert-success">
                            Cleanup completed successfully!
                        </div>
                        <div v-else-if="cleanupStatus.status === 'stopped'" class="alert alert-warning">
                            Cleanup stopped. Click Start to continue processing.
                        </div>
                        <div v-else-if="cleanupStatus.status === 'failed'" class="alert alert-danger">
                            Cleanup failed: {{ cleanupStatus.message || 'Unknown error' }}
                        </div>
                        <div v-else>
                            <div class="processing-indicator" v-if="processing || (cleanupStatus && !cleanupStatus.status)">
                                <span class="spinner"></span>
                                <span>Processing cleanup...</span>
                            </div>
                            <div class="mb-2">
                                <strong>Rows Processed:</strong> {{ formatNumber(cleanupStatus.rows_processed || 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="processing-indicator" v-if="processing && !cleanupStatus">
                        <span class="spinner"></span>
                        <span>Running cleanup...</span>
                    </div>

                    <div class="mt-3">
                        <button
                            @click="startCleanup"
                            :disabled="processing || stats.directory_in_webroot || (stats.csv_dir_exists && !stats.csv_dir_writable) || (cleanupStatus && cleanupStatus.rows_processed > 0 && cleanupStatus.status !== 'completed' && cleanupStatus.status !== 'stopped' && cleanupStatus.status !== 'failed')"
                            class="btn btn-primary me-2">
                            Start Cleanup
                        </button>
                        <button
                            v-if="cleanupStatus && cleanupStatus.rows_processed > 0 && !cleanupStatus.status"
                            @click="stopCleanup"
                            :disabled="processing"
                            class="btn btn-danger">
                            Stop
                        </button>
                    </div>

                    <div v-if="statusMessage" :class="['alert', 'mt-3', statusType === 'success' ? 'alert-success' : statusType === 'error' ? 'alert-danger' : 'alert-info']" v-html="statusMessage"></div>
                </div>
            </div>

            <!-- API Logs Exported CSV Files -->
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Exported CSV Files</h2>
                </div>
                <div class="card-body">
                    <div v-if="loadingFiles" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading files...</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Date</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="file in files" :key="file.filename">
                                    <td>{{ file.filename }}</td>
                                    <td>{{ file.date }}</td>
                                    <td>{{ file.size_formatted }}</td>
                                </tr>
                                <tr v-if="files.length === 0">
                                    <td colspan="3" class="text-center text-muted">No exported files</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const apiBase = '<?php echo site_url("api/db_logs"); ?>';
        
        new Vue({
            el: '#app',
            data: {
                stats: {},
                files: [],
                cleanupStatus: null,
                loadingStats: false,
                loadingFiles: false,
                processing: false,
                statusMessage: '',
                statusType: 'info',
                showSettings: false,
                chunkSize: 10000,
                timeLimit: 20
            },
            mounted() {
                this.loadStats();
                this.loadFiles();
            },
            methods: {
                async loadStats() {
                    this.loadingStats = true;
                    try {
                        const response = await axios.get(apiBase + '/stats');
                        if (response.data.status === 'success') {
                            this.stats = response.data.data;
                        } else {
                            this.showStatus('Failed to load statistics: ' + response.data.message, 'error');
                        }
                    } catch (error) {
                        this.showStatus('Error loading statistics: ' + (error.response?.data?.message || error.message), 'error');
                    } finally {
                        this.loadingStats = false;
                    }
                },
                async loadFiles() {
                    this.loadingFiles = true;
                    try {
                        const response = await axios.get(apiBase + '/files');
                        if (response.data.status === 'success') {
                            this.files = response.data.data;
                        } else {
                            this.showStatus('Failed to load files: ' + response.data.message, 'error');
                        }
                    } catch (error) {
                        this.showStatus('Error loading files: ' + (error.response?.data?.message || error.message), 'error');
                    } finally {
                        this.loadingFiles = false;
                    }
                },
                async processChunk() {
                    try {
                        const response = await axios.post(apiBase + '/cleanup/chunk', {
                            chunk_size: this.chunkSize,
                            time_limit: this.timeLimit
                        });
                        
                        // Handle files_created - convert object to array if needed
                        let filesCreated = response.data.files_created || [];
                        if (!Array.isArray(filesCreated)) {
                            filesCreated = Object.values(filesCreated);
                        }
                        
                        let existingFiles = this.cleanupStatus?.files_created || [];
                        if (!Array.isArray(existingFiles)) {
                            existingFiles = Object.values(existingFiles);
                        }
                        
                        this.cleanupStatus = {
                            rows_processed: (this.cleanupStatus?.rows_processed || 0) + (response.data.rows_processed || 0),
                            rows_deleted: (this.cleanupStatus?.rows_deleted || 0) + (response.data.rows_deleted || 0),
                            files_created: Array.from(new Set([...existingFiles, ...filesCreated])),
                            message: response.data.message
                        };
                        
                        if (response.data.rows_processed === 0) {
                            // No more rows to process - cleanup is complete
                            this.cleanupStatus.status = 'completed';
                            this.statusMessage = '';
                            this.processing = false;
                            this.loadStats();
                            this.loadFiles();
                        } else {
                            // More rows to process - immediately make another call
                            this.processing = true;
                            this.processChunk();
                        }
                    } catch (error) {
                        this.cleanupStatus = {
                            status: 'failed',
                            message: error.response?.data?.message || error.message
                        };
                        this.processing = false;
                    }
                },
                async startCleanup() {
                    this.processing = true;
                    this.processChunk();
                },
                async stopCleanup() {
                    if (!confirm('Are you sure you want to stop the cleanup process? You can restart it later to continue processing remaining rows.')) {
                        return;
                    }
                    
                    this.processing = false;
                    this.cleanupStatus = {
                        ...this.cleanupStatus,
                        status: 'stopped',
                        message: 'Cleanup stopped'
                    };
                    this.showStatus('Cleanup stopped. Click Start to continue processing remaining rows.', 'info');
                },
                showStatus(message, type = 'info') {
                    this.statusMessage = message;
                    this.statusType = type;
                    setTimeout(() => {
                        this.statusMessage = '';
                    }, 10000);
                },
                formatNumber(num) {
                    return new Intl.NumberFormat().format(num);
                }
            }
        });
    </script>

    <script>
        const apiLogsBase = '<?php echo site_url("api/db_logs/api_logs"); ?>';

        new Vue({
            el: '#api-logs-app',
            data: {
                stats: {},
                files: [],
                cleanupStatus: null,
                loadingStats: false,
                loadingFiles: false,
                processing: false,
                statusMessage: '',
                statusType: 'info',
                showSettings: false,
                chunkSize: 10000,
                timeLimit: 20
            },
            mounted() {
                this.loadStats();
                this.loadFiles();
            },
            methods: {
                async loadStats() {
                    this.loadingStats = true;
                    try {
                        const response = await axios.get(apiLogsBase + '/stats');
                        if (response.data.status === 'success') {
                            this.stats = response.data.data;
                        } else {
                            this.showStatus('Failed to load statistics: ' + response.data.message, 'error');
                        }
                    } catch (error) {
                        this.showStatus('Error loading statistics: ' + (error.response?.data?.message || error.message), 'error');
                    } finally {
                        this.loadingStats = false;
                    }
                },
                async loadFiles() {
                    this.loadingFiles = true;
                    try {
                        const response = await axios.get(apiLogsBase + '/files');
                        if (response.data.status === 'success') {
                            this.files = response.data.data;
                        } else {
                            this.showStatus('Failed to load files: ' + response.data.message, 'error');
                        }
                    } catch (error) {
                        this.showStatus('Error loading files: ' + (error.response?.data?.message || error.message), 'error');
                    } finally {
                        this.loadingFiles = false;
                    }
                },
                async processChunk() {
                    try {
                        const response = await axios.post(apiLogsBase + '/cleanup/chunk', {
                            chunk_size: this.chunkSize,
                            time_limit: this.timeLimit
                        });

                        let filesCreated = response.data.files_created || [];
                        if (!Array.isArray(filesCreated)) { filesCreated = Object.values(filesCreated); }

                        let existingFiles = this.cleanupStatus?.files_created || [];
                        if (!Array.isArray(existingFiles)) { existingFiles = Object.values(existingFiles); }

                        this.cleanupStatus = {
                            rows_processed: (this.cleanupStatus?.rows_processed || 0) + (response.data.rows_processed || 0),
                            rows_deleted:   (this.cleanupStatus?.rows_deleted   || 0) + (response.data.rows_deleted   || 0),
                            files_created:  Array.from(new Set([...existingFiles, ...filesCreated])),
                            message:        response.data.message
                        };

                        if (response.data.rows_processed === 0) {
                            this.cleanupStatus.status = 'completed';
                            this.statusMessage = '';
                            this.processing = false;
                            this.loadStats();
                            this.loadFiles();
                        } else {
                            this.processing = true;
                            this.processChunk();
                        }
                    } catch (error) {
                        this.cleanupStatus = {
                            status:  'failed',
                            message: error.response?.data?.message || error.message
                        };
                        this.processing = false;
                    }
                },
                async startCleanup() {
                    this.processing = true;
                    this.processChunk();
                },
                async stopCleanup() {
                    if (!confirm('Are you sure you want to stop the cleanup process? You can restart it later to continue processing remaining rows.')) {
                        return;
                    }
                    this.processing = false;
                    this.cleanupStatus = {
                        ...this.cleanupStatus,
                        status:  'stopped',
                        message: 'Cleanup stopped'
                    };
                    this.showStatus('Cleanup stopped. Click Start to continue processing remaining rows.', 'info');
                },
                showStatus(message, type = 'info') {
                    this.statusMessage = message;
                    this.statusType = type;
                    setTimeout(() => { this.statusMessage = ''; }, 10000);
                },
                formatNumber(num) {
                    return new Intl.NumberFormat().format(num);
                }
            }
        });
    </script>
</body>
</html>
