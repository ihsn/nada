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
        }
        .card {
            margin-bottom: 30px;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        .search-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pagination-info {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div id="app" class="main-container">
        <div class="container-fluid px-4">
            <h1 class="mb-4">API Logs</h1>
            
            <!-- Search and Filter Form -->
            <div class="search-form">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Search Keywords</label>
                        <input 
                            type="text" 
                            v-model="searchKeywords" 
                            class="form-control" 
                            placeholder="Enter search term..."
                            @keyup.enter="loadLogs">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Search Field</label>
                        <select v-model="searchField" class="form-select">
                            <option value="">All Fields</option>
                            <option value="uri">URI</option>
                            <option value="method">Method</option>
                            <option value="api_key">API Key</option>
                            <option value="ip_address">IP Address</option>
                            <option value="authorized">Authorized</option>
                            <option value="response_code">Response Code</option>
                            <option value="user_id">User ID</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input 
                            type="date" 
                            v-model="dateFrom" 
                            class="form-control"
                            @change="loadLogs">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input 
                            type="date" 
                            v-model="dateTo" 
                            class="form-control"
                            @change="loadLogs">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select v-model="sortBy" class="form-select" @change="loadLogs">
                            <option value="time">Time</option>
                            <option value="uri">URI</option>
                            <option value="method">Method</option>
                            <option value="rtime">Response Time</option>
                            <option value="response_code">Response Code</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Order</label>
                        <select v-model="sortOrder" class="form-select" @change="loadLogs">
                            <option value="desc">Descending</option>
                            <option value="asc">Ascending</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button 
                            @click="loadLogs" 
                            class="btn btn-primary"
                            :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
                            <span v-else>Search</span>
                        </button>
                        <button 
                            @click="clearFilters" 
                            class="btn btn-secondary ms-2">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Logs Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">API Logs</h2>
                    <div class="pagination-info" v-if="pagination">
                        Showing {{ pagination.offset + 1 }} - {{ Math.min(pagination.offset + pagination.per_page, pagination.total_rows) }} of {{ formatNumber(pagination.total_rows) }}
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="loading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading logs...</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>ID</th>
                                    <th>Time</th>
                                    <th>Method</th>
                                    <th>URI</th>
                                    <th>Response Code</th>
                                    <th>Response Time</th>
                                    <th>Authorized</th>
                                    <th>IP Address</th>
                                    <th>User ID</th>
                                    <th>API Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in logs" :key="log.id">
                                    <td>{{ log.id }}</td>
                                    <td>
                                        <small>{{ log.time_formatted }}</small>
                                    </td>
                                    <td>
                                        <span class="badge" :class="getMethodBadgeClass(log.method)">
                                            {{ log.method.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-truncate d-inline-block" style="max-width: 300px;" :title="log.uri">
                                            {{ log.uri }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge" :class="getResponseCodeBadgeClass(log.response_code)">
                                            {{ log.response_code }}
                                        </span>
                                    </td>
                                    <td>
                                        <span v-if="log.rtime_formatted" >
                                            {{ log.rtime_formatted }}
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="log.authorized == '1' ? 'badge-success' : 'badge-danger'">
                                            {{ log.authorized == '1' ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td><small>{{ log.ip_address }}</small></td>
                                    <td>{{ log.user_id || '-' }}</td>
                                    <td>
                                        <small class="font-monospace">{{ log.api_key.substring(0, 10) }}...</small>
                                    </td>
                                </tr>
                                <tr v-if="logs.length === 0">
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No logs found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="pagination && pagination.total_pages > 1" class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <button 
                                @click="goToPage(1)" 
                                :disabled="!pagination.has_prev || loading"
                                class="btn btn-sm btn-outline-primary">
                                First
                            </button>
                            <button 
                                @click="goToPreviousPage" 
                                :disabled="!pagination.has_prev || loading"
                                class="btn btn-sm btn-outline-primary ms-2">
                                Previous
                            </button>
                        </div>
                        <div>
                            <span class="pagination-info">
                                Page {{ pagination.current_page }} of {{ pagination.total_pages }}
                            </span>
                        </div>
                        <div>
                            <button 
                                @click="goToNextPage" 
                                :disabled="!pagination.has_next || loading"
                                class="btn btn-sm btn-outline-primary">
                                Next
                            </button>
                            <button 
                                @click="goToPage(pagination.total_pages)" 
                                :disabled="!pagination.has_next || loading"
                                class="btn btn-sm btn-outline-primary ms-2">
                                Last
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const apiBase = '<?php echo site_url("api/api_logs"); ?>';
        
        new Vue({
            el: '#app',
            data: {
                logs: [],
                pagination: null,
                loading: false,
                searchKeywords: '',
                searchField: '',
                dateFrom: '',
                dateTo: '',
                sortBy: 'time',
                sortOrder: 'desc',
                currentOffset: 0,
                limit: 50
            },
            mounted() {
                this.loadLogs();
            },
            methods: {
                async loadLogs() {
                    this.loading = true;
                    try {
                        const params = {
                            limit: this.limit,
                            offset: this.currentOffset,
                            sort_by: this.sortBy,
                            sort_order: this.sortOrder
                        };
                        
                        if (this.searchKeywords && this.searchField) {
                            params.keywords = this.searchKeywords;
                            params.field = this.searchField;
                        } else if (this.searchKeywords) {
                            params.keywords = this.searchKeywords;
                            params.field = 'all';
                        }
                        
                        // Date range filter
                        if (this.dateFrom) {
                            // Convert date to Unix timestamp (start of day)
                            const date = new Date(this.dateFrom);
                            date.setHours(0, 0, 0, 0);
                            params.time_from = Math.floor(date.getTime() / 1000);
                        }
                        if (this.dateTo) {
                            // Convert date to Unix timestamp (end of day)
                            const date = new Date(this.dateTo);
                            date.setHours(23, 59, 59, 999);
                            params.time_to = Math.floor(date.getTime() / 1000);
                        }
                        
                        const response = await axios.get(apiBase + '/index', { params });
                        
                        if (response.data.status === 'success') {
                            this.logs = response.data.data;
                            this.pagination = response.data.pagination;
                        } else {
                            alert('Failed to load logs: ' + (response.data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        alert('Error loading logs: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.loading = false;
                    }
                },
                goToPage(page) {
                    this.currentOffset = (page - 1) * this.limit;
                    this.loadLogs();
                },
                goToNextPage() {
                    if (this.pagination && this.pagination.has_next) {
                        this.currentOffset += this.limit;
                        this.loadLogs();
                    }
                },
                goToPreviousPage() {
                    if (this.pagination && this.pagination.has_prev) {
                        this.currentOffset = Math.max(0, this.currentOffset - this.limit);
                        this.loadLogs();
                    }
                },
                getMethodBadgeClass(method) {
                    const classes = {
                        'GET': 'bg-primary',
                        'POST': 'bg-success',
                        'PUT': 'bg-warning',
                        'DELETE': 'bg-danger',
                        'PATCH': 'bg-info'
                    };
                    return classes[method] || 'bg-secondary';
                },
                getResponseCodeBadgeClass(code) {
                    if (code >= 200 && code < 300) {
                        return 'badge-success';
                    } else if (code >= 300 && code < 400) {
                        return 'badge-warning';
                    } else if (code >= 400) {
                        return 'badge-danger';
                    }
                    return 'bg-secondary';
                },
                formatNumber(num) {
                    return new Intl.NumberFormat().format(num);
                },
                clearFilters() {
                    this.searchKeywords = '';
                    this.searchField = '';
                    this.dateFrom = '';
                    this.dateTo = '';
                    this.currentOffset = 0;
                    this.loadLogs();
                }
            }
        });
    </script>
</body>
</html>

