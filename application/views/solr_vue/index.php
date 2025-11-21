<html>

<head>            
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.20/lodash.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css" crossorigin="anonymous" />          
    <style>
        .progress {
            height: 20px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin: 10px 0;
        }
        .progress-bar {
            height: 100%;
            background-color: #007bff;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-info {
            color: #31708f;
            background-color: #d9edf7;
            border-color: #bce8f1;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .form-controlx {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        /* Enhanced Progress Tracking Styles */
        .progress-container {
            margin: 15px 0;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .progress-percentage {
            font-weight: bold;
            color: #007bff;
            font-size: 1.1em;
        }
        
        .progress {
            height: 25px;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(45deg, #007bff, #0056b3);
            border-radius: 12px;
            transition: width 0.5s ease;
            position: relative;
        }
        
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .progress-stats {
            margin: 15px 0;
            padding: 15px;
            background: rgba(0,123,255,0.05);
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            color: #495057;
        }
        
        .stat-item i {
            color: #007bff;
            width: 16px;
        }
        
        .batch-info {
            margin-top: 10px;
            padding: 8px 12px;
            background: rgba(0,0,0,0.05);
            border-radius: 4px;
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }
        
        .completion-stats {
            margin-top: 10px;
            padding: 10px;
            background: rgba(40,167,69,0.1);
            border-radius: 6px;
        }
        
        .completion-stats strong {
            color: #155724;
        }
        
        .completion-stats small {
            color: #6c757d;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            color: #856404;
        }
        
        .stopped-stats {
            margin-top: 10px;
            padding: 10px;
            background: rgba(255,193,7,0.1);
            border-radius: 6px;
        }
        
        .stopped-stats strong {
            color: #856404;
        }
        
        .stopped-stats small {
            color: #6c757d;
        }
        
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        
        .card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .last-processed {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        /* Enhanced Sidebar Navigation */
        .sidebar-nav {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px 15px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .nav-section {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .nav-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .nav-section-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .sidebar-nav .btn {
            font-size: 0.85rem;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .sidebar-nav .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .sidebar-nav .btn i {
            margin-right: 6px;
            width: 14px;
            text-align: center;
        }
        
        /* Main content improvements */
        .main-content {
            padding: 0 15px;
        }
        
        .main-content .card {
            margin-bottom: 20px;
        }
        
        /* Enhanced card headers */
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        .card-header i {
            font-size: 1.2em;
        }
        
        /* Better form styling */
        .form-groupx {
            margin-bottom: 1rem;
        }
        
        .form-groupx label {
            font-weight: 500;
            color: #495057;
        }
        
        .form-controlx {
            border-radius: 6px;
            border: 1px solid #ced4da;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-controlx:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Enhanced button styling */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* Better spacing for sections */
        .card-body h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        /* Improved alert styling */
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .sidebar-nav {
                position: relative;
                top: 0;
                margin-bottom: 20px;
            }
            
            
            .main-content {
                padding: 0 10px;
            }
        }
        
        @media (max-width: 992px) {
            .col-lg-7 {
                margin-bottom: 20px;
            }
        }
        
        /* Schema Setup Actions button spacing */
        .schema-setup-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .schema-setup-actions .btn {
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        /* Schema Validation Actions button spacing */
        .schema-validation-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .schema-validation-actions .btn {
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        /* Fallback for browsers that don't support gap */
        @supports not (gap: 8px) {
            .schema-setup-actions .btn:not(:last-child),
            .schema-validation-actions .btn:not(:last-child) {
                margin-right: 8px;
            }
        }
        
        /* Debug content scrollable area */
        .debug-content {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
        }
        
        .debug-content pre {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-size: 0.85rem;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Custom scrollbar styling for debug content */
        .debug-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .debug-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .debug-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .debug-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Home Dashboard Styles */
        .home-dashboard .stat-box {
            padding: 10px;
        }
        
        .home-dashboard .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            line-height: 1;
        }
        
        .home-dashboard .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .home-dashboard .d-grid.gap-2 {
            display: grid;
            gap: 8px;
        }
        
        .home-dashboard .schema-info,
        .home-dashboard .field-stats {
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .home-dashboard .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .home-dashboard .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .home-dashboard .sync-item {
            padding: 8px 0;
        }
        
        .home-dashboard .progress-sm {
            height: 6px;
            border-radius: 3px;
        }
    </style>          
</head>

<body>
<div id="app">

    <div class="container-fluid">
        <div class="row" id="body-row">
            <!-- Left Sidebar -->
            <div class="col-md-3 col-lg-2">                
                <div class="sidebar-nav">
                    <div class="nav-section mb-3">
                        <h6 class="nav-section-title">Navigation</h6>
                        <button type="button" class="btn btn-light btn-sm btn-block mb-2" v-on:click="toggleTab('home')">
                            <i class="fas fa-home"></i> Dashboard
                        </button>
            </div>

                    <div class="nav-section mb-3">
                        <h6 class="nav-section-title">Index Operations</h6>
                        <button type="button" class="btn btn-primary btn-sm btn-block mb-2" v-on:click="toggleTab('index-datasets')">
                            <i class="fas fa-database"></i> Index Datasets
                        </button>
                        <button type="button" class="btn btn-primary btn-sm btn-block mb-2" v-on:click="toggleTab('index-variables')">
                            <i class="fas fa-list"></i> Index Variables
                        </button>
                        <button type="button" class="btn btn-primary btn-sm btn-block mb-2" v-on:click="toggleTab('index-variables-by-survey')">
                            <i class="fas fa-poll"></i> Index by Survey
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm btn-block mb-2" disabled>
                            <i class="fas fa-quote-left"></i> Index Citations
                        </button>
                    </div>
                    
                    <div class="nav-section mb-3">
                        <h6 class="nav-section-title">Core Operations</h6>
                        <button type="button" class="btn btn-success btn-sm btn-block mb-2" v-on:click="commitSolr">
                            <i class="fas fa-check"></i> Commit
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-block mb-2" v-on:click="clearSolr">
                            <i class="fas fa-trash"></i> Clear Index
                        </button>
                        <button type="button" class="btn btn-info btn-sm btn-block mb-2" v-on:click="pingSolr" :disabled="pinging">
                            <i class="fas fa-heartbeat" :class="{'fa-spin': pinging}"></i> 
                            {{pinging ? 'Pinging...' : 'Ping SOLR'}}
                        </button>
                    </div>
                    
                    <div class="nav-section mb-3">
                        <h6 class="nav-section-title">Management</h6>
                        <button type="button" class="btn btn-success btn-sm btn-block mb-2" v-on:click="toggleTab('schema-management')">
                            <i class="fas fa-cogs"></i> Schema Management
                        </button>
                        <button type="button" class="btn btn-info btn-sm btn-block mb-2" v-on:click="toggleTab('core-management')">
                            <i class="fas fa-server"></i> Core Management
                        </button>
                    </div>
                    
                    <div class="nav-section">
                        <h6 class="nav-section-title">Control</h6>
                        <button type="button" class="btn btn-warning btn-sm btn-block" v-on:click="stopAllProcessing" :disabled="!isAnyProcessing">
                            <i class="fas fa-stop"></i> Stop All Processing
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10">
                    <div class="main-content" id="main-content">                
                    
                    <!-- Home Dashboard -->
                    <div v-if="active_container=='home'" class="home-dashboard">

                        <!-- System Status Row -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-server"></i> System Status
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="ping_status.data" class="connection-status mb-2">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="status-indicator" :class="ping_status.data.result.status === 'OK' ? 'status-online' : 'status-offline'"></div>
                                                <span class="ml-2 font-weight-bold" :class="ping_status.data.result.status === 'OK' ? 'text-success' : 'text-danger'">
                                                    {{ping_status.data.result.status === 'OK' ? 'Online' : 'Offline'}}
                                                </span>
                                            </div>
                                            <div v-if="lastPingTime" class="mb-1">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> {{lastPingTime}}
                                                </small>
                                            </div>
                                            <div v-if="ping_status.data.result.note" class="mb-1">
                                                <small class="text-info">
                                                    <i class="fas fa-info-circle"></i> {{ping_status.data.result.note}}
                                                </small>
                                            </div>
                                        </div>
                                        <div v-else class="text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Checking...
                                        </div>
                                        <hr v-if="configured_core" class="my-2">
                                        <div v-if="core_check_loading" class="text-center text-muted">
                                            <small><i class="fas fa-spinner fa-spin"></i> Loading...</small>
                                        </div>
                                        <div v-else-if="configured_core">
                                            <div class="mb-1">
                                                <small class="text-muted">Core:</small>
                                                <span class="badge badge-primary badge-sm ml-1">{{configured_core}}</span>
                                                <span v-if="configured_core_exists" class="badge badge-success badge-sm ml-1">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                                <span v-else class="badge badge-warning badge-sm ml-1">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                            </div>
                                            <div v-if="configured_core_exists && configured_core_status" class="core-details">
                                                <div class="mb-1" v-if="configured_core_status.index && configured_core_status.index.numDocs !== undefined">
                                                    <small class="text-muted">Docs:</small>
                                                    <strong class="ml-1">{{configured_core_status.index.numDocs.toLocaleString()}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="configured_core_status.index && configured_core_status.index.size">
                                                    <small class="text-muted">Size:</small>
                                                    <strong class="ml-1">{{configured_core_status.index.size}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="configured_core_status.uptime">
                                                    <small class="text-muted">Uptime:</small>
                                                    <strong class="ml-1">{{formatUptime(configured_core_status.uptime)}}</strong>
                                                </div>
                                            </div>
                                            
                                        </div>
                                        <div v-else class="text-muted">
                                            <small><i class="fas fa-exclamation-triangle"></i> No core configured</small>
                                        </div>
                                        
                                        <div v-if="system_info.loading" class="text-center text-muted mt-3">
                                            <small><i class="fas fa-spinner fa-spin"></i> Loading system info...</small>
                                        </div>
                                        <div v-else-if="system_info.data && system_info.data.data && system_info.data.data.result && !system_info.data.data.result.error" class="system-info mt-3 pt-3 border-top">
                                            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Solr Information</h6>
                                            <div class="mb-1" v-if="system_info.data.data.result.solr_version">
                                                <small class="text-muted">Solr:</small>
                                                <strong class="ml-1">{{system_info.data.data.result.solr_version}}</strong>
                                            </div>
                                            <div class="mb-1" v-if="system_info.data.data.result.lucene_version">
                                                <small class="text-muted">Lucene:</small>
                                                <strong class="ml-1">{{system_info.data.data.result.lucene_version}}</strong>
                                            </div>
                                            <div class="mb-1" v-if="system_info.data.data.result.jvm_name">
                                                <small class="text-muted">JVM:</small>
                                                <strong class="ml-1">{{system_info.data.data.result.jvm_name}}</strong>
                                            </div>
                                            <div class="mb-1" v-if="system_info.data.data.result.jvm_version">
                                                <small class="text-muted">JVM Version:</small>
                                                <strong class="ml-1">{{system_info.data.data.result.jvm_version}}</strong>
                                            </div>
                                            
                                            <div v-if="system_info.data.data.result.system" class="mt-3 pt-2 border-top">
                                                <h6 class="mb-2"><i class="fas fa-server"></i> System Resources</h6>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.name">
                                                    <small class="text-muted">OS:</small>
                                                    <strong class="ml-1">{{system_info.data.data.result.system.name}} {{system_info.data.data.result.system.version}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.availableProcessors">
                                                    <small class="text-muted">CPU Cores:</small>
                                                    <strong class="ml-1">{{system_info.data.data.result.system.availableProcessors}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.totalPhysicalMemorySize">
                                                    <small class="text-muted">Total Memory:</small>
                                                    <strong class="ml-1">{{formatBytes(system_info.data.data.result.system.totalPhysicalMemorySize)}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.freePhysicalMemorySize">
                                                    <small class="text-muted">Free Memory:</small>
                                                    <strong class="ml-1">{{formatBytes(system_info.data.data.result.system.freePhysicalMemorySize)}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.totalMemorySize">
                                                    <small class="text-muted">JVM Memory:</small>
                                                    <strong class="ml-1">{{formatBytes(system_info.data.data.result.system.totalMemorySize)}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.freeMemorySize">
                                                    <small class="text-muted">JVM Free:</small>
                                                    <strong class="ml-1">{{formatBytes(system_info.data.data.result.system.freeMemorySize)}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.systemLoadAverage !== undefined">
                                                    <small class="text-muted">System Load:</small>
                                                    <strong class="ml-1">{{system_info.data.data.result.system.systemLoadAverage.toFixed(2)}}</strong>
                                                </div>
                                                <div class="mb-1" v-if="system_info.data.data.result.system.processCpuLoad !== undefined">
                                                    <small class="text-muted">CPU Usage:</small>
                                                    <strong class="ml-1">{{(system_info.data.data.result.system.processCpuLoad * 100).toFixed(1)}}%</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-chart-bar"></i> Index Statistics
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="index_counts.data">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="stat-box">
                                                        <div class="stat-number text-primary">{{index_counts.data.result.datasets.toLocaleString()}}</div>
                                                        <div class="stat-label">Documents</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-box">
                                                        <div class="stat-number text-info">{{index_counts.data.result.variables.toLocaleString()}}</div>
                                                        <div class="stat-label">Variables</div>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-box">
                                                        <div class="stat-number text-warning">{{index_counts.data.result.citations.toLocaleString()}}</div>
                                                        <div class="stat-label">Citations</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="my-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <small class="text-muted">Last Dataset:</small><br>
                                                    <strong class="small">{{index_counts.data.result.last_dataset || 'N/A'}}</strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Last Variable:</small><br>
                                                    <strong class="small">{{index_counts.data.result.last_variable || 'N/A'}}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Loading statistics...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-exchange-alt"></i> Sync Status
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="index_counts.data && db_counts.data">
                                            <div class="sync-item mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small">Documents</span>
                                                    <span class="small" :class="getSyncStatus('datasets')">
                                                        {{getSyncPercentage('datasets')}}%
                                                    </span>
                                                </div>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar" :class="getSyncStatus('datasets')" 
                                                         :style="{width: getSyncPercentage('datasets') + '%'}"></div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-muted">DB: {{db_counts.data.result.datasets.toLocaleString()}}</small>
                                                    <small class="text-muted">Idx: {{index_counts.data.result.datasets.toLocaleString()}}</small>
                                                </div>
                                            </div>
                                            
                                            <div class="sync-item mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small">Variables</span>
                                                    <span class="small" :class="getSyncStatus('variables')">
                                                        {{getSyncPercentage('variables')}}%
                                                    </span>
                                                </div>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar" :class="getSyncStatus('variables')" 
                                                         :style="{width: getSyncPercentage('variables') + '%'}"></div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-muted">DB: {{db_counts.data.result.variables.toLocaleString()}}</small>
                                                    <small class="text-muted">Idx: {{index_counts.data.result.variables.toLocaleString()}}</small>
                                                </div>
                                            </div>
                                            
                                            <div class="sync-item">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="small">Citations</span>
                                                    <span class="small" :class="getSyncStatus('citations')">
                                                        {{getSyncPercentage('citations')}}%
                                                    </span>
                                                </div>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar" :class="getSyncStatus('citations')" 
                                                         :style="{width: getSyncPercentage('citations') + '%'}"></div>
                                                </div>
                                                <div class="d-flex justify-content-between mt-1">
                                                    <small class="text-muted">DB: {{db_counts.data.result.citations.toLocaleString()}}</small>
                                                    <small class="text-muted">Idx: {{index_counts.data.result.citations.toLocaleString()}}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-center text-muted">
                                            <i class="fas fa-spinner fa-spin"></i> Loading sync data...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- index datasets -->
                    <div v-if="active_container=='index-datasets'" class="index-datasets">
                
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-database text-primary mr-2"></i>
                            <h5 class="mb-0">Index Datasets</h5>
                        </div>
                        <div class="card-body">
                            <div class="card-text">
                                <p>Reindex all entries in the catalog</p>
                                <div class="form-groupx row">
                                    <div class="col-2">Rows</div>
                                    <div class="col"><input class="form-controlx" size="6" type="text" v-model="dataset_rows_limit" /> <span class="text-secondary">No. of documents to process per request</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-2">Start row#</div>
                                    <div class="col-md-1"><input class="form-controlx" size="6" type="text" v-model="dataset_start_row" /></div>
                                    <div class="col-md-9" v-if="dataset_stopped && dataset_last_row_processed > 0">
                                        <small class="text-info">
                                            <i class="fas fa-info-circle"></i> Last processed ID: <strong>{{dataset_last_row_processed}}</strong> 
                                            <button type="button" class="btn btn-link btn-sm p-0 ml-1" v-on:click="resumeFromLastProcessed">
                                                (Use this to resume)
                                            </button>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-primary btn-sm" v-on:click="indexDatasetOnClick" :disabled="indexing_running">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" v-on:click="resumeDatasetProcessing" :disabled="indexing_running || !dataset_stopped || !dataset_last_row_processed">
                                            <i class="fas fa-redo"></i> Resume
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" v-on:click="stopDatasetProcessing" :disabled="!indexing_running">
                                            <i class="fas fa-stop"></i> Stop
                                        </button>
                                </div>

                                    <div v-if="indexing_processed || indexing_running" class="progress-section">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small font-weight-bold">Progress</span>
                                            <span class="small" v-if="dataset_total_count > 0">
                                                {{getDatasetProgressPercentage().toFixed(1)}}% 
                                                ({{indexing_status.toLocaleString()}} / {{dataset_total_count.toLocaleString()}})
                                            </span>
                                            <span class="small" v-else>
                                                {{indexing_status.toLocaleString()}} processed
                                            </span>
                                    </div>
                                        <div class="progress" style="height: 20px;" v-if="dataset_total_count > 0">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 :style="{width: getDatasetProgressPercentage() + '%'}"
                                                 :aria-valuenow="getDatasetProgressPercentage()" 
                                                 aria-valuemin="0" 
                                                 :aria-valuemax="dataset_total_count">
                                                {{getDatasetProgressPercentage().toFixed(1)}}%
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                Last processed ID: <strong>{{dataset_last_row_processed}}</strong>
                                            </small>
                                </div>
                            </div>

                                    <div v-if="dataset_stopped" class="alert alert-warning mt-2">
                                        <strong><i class="fas fa-pause"></i> Dataset Processing Stopped</strong><br>
                                        <small>Indexed {{indexing_status.toLocaleString()}} datasets 
                                            <span v-if="dataset_total_count > 0">({{getDatasetProgressPercentage().toFixed(1)}}%)</span>
                                            • Last processed ID: {{dataset_last_row_processed}}
                                        </small>
                                    </div>
                                    
                                    <div v-if="!indexing_running && !dataset_stopped && indexing_processed === 0 && dataset_total_count > 0" class="alert alert-info mt-2">
                                        <small>
                                            <i class="fas fa-info-circle"></i> Total surveys in database: <strong>{{dataset_total_count.toLocaleString()}}</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                    <!-- end index datasets -->



                    <!-- index variables -->
                    <div v-if="active_container=='index-variables'" class="index-variables">
                
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-list text-info mr-2"></i>
                            <h5 class="mb-0">Index Variables</h5>
                        </div>
                        <div class="card-body">
                            <div class="card-text">
                                <p>Reindex all variable entries in the catalog</p>
                                <div class="form-groupx row">
                                    <div class="col-2">Rows</div>
                                    <div class="col"><input class="form-controlx" size="6" type="text" v-model="var_rows_limit" /> <span class="text-secondary">No. of documents to process per request</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-2">Start row#</div>
                                    <div class="col-md-1"><input class="form-controlx" size="6" type="text" v-model="var_start_row" /></div>
                                    <div class="col-md-9" v-if="var_stopped && variable_last_row_processed > 0">
                                        <small class="text-info">
                                            <i class="fas fa-info-circle"></i> Last processed ID: <strong>{{variable_last_row_processed}}</strong> 
                                            <button type="button" class="btn btn-link btn-sm p-0 ml-1" v-on:click="resumeVariableFromLastProcessed">
                                                (Use this to resume)
                                            </button>
                                        </small>
                                    </div>
                                    <div class="col-md-9" v-else-if="index_counts.data && index_counts.data.result && index_counts.data.result.last_variable">
                                        <small class="text-info">
                                            <i class="fas fa-info-circle"></i> Last indexed variable ID: <strong>{{index_counts.data.result.last_variable}}</strong>
                                            <button type="button" class="btn btn-link btn-sm p-0 ml-1" v-on:click="useLastVariableId">
                                                (Use this to resume)
                                            </button>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-primary btn-sm" v-on:click="indexVariablesOnClick" :disabled="var_processing">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" v-on:click="resumeVariableProcessing" :disabled="var_processing || !var_stopped || !variable_last_row_processed">
                                            <i class="fas fa-redo"></i> Resume
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" v-on:click="stopVariableProcessing" :disabled="!var_processing">
                                            <i class="fas fa-stop"></i> Stop
                                        </button>
                                    </div>

                                    <div v-if="indexing_processed || var_processing" class="progress-section">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small font-weight-bold">Progress</span>
                                            <span class="small" v-if="variable_total_count > 0">
                                                {{getVariableProgressPercentage().toFixed(1)}}% 
                                                ({{indexing_status.toLocaleString()}} / {{variable_total_count.toLocaleString()}})
                                            </span>
                                            <span class="small" v-else>
                                                {{indexing_status.toLocaleString()}} processed
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 20px;" v-if="variable_total_count > 0">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 :style="{width: getVariableProgressPercentage() + '%'}"
                                                 :aria-valuenow="getVariableProgressPercentage()" 
                                                 aria-valuemin="0" 
                                                 :aria-valuemax="variable_total_count">
                                                {{getVariableProgressPercentage().toFixed(1)}}%
                                            </div>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                Last processed ID: <strong>{{variable_last_row_processed}}</strong>
                                            </small>
                                        </div>
                                    </div>

                                    <div v-if="var_stopped" class="alert alert-warning mt-2">
                                        <strong><i class="fas fa-pause"></i> Variable Processing Stopped</strong><br>
                                        <small>Indexed {{indexing_status.toLocaleString()}} variables 
                                            <span v-if="variable_total_count > 0">({{getVariableProgressPercentage().toFixed(1)}}%)</span>
                                            • Last processed ID: {{variable_last_row_processed}}
                                        </small>
                                    </div>
                                    
                                    <div v-if="!var_processing && !var_stopped && indexing_processed === 0 && variable_total_count > 0" class="alert alert-info mt-2">
                                        <small>
                                            <i class="fas fa-info-circle"></i> Total variables in database: <strong>{{variable_total_count.toLocaleString()}}</strong>
                                        </small>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                    <!-- end index variables -->

                    <!-- index variables by survey -->
                    <div v-if="active_container=='index-variables-by-survey'" class="index-variables-by-survey">
                                        
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-poll text-success mr-2"></i>
                            <h5 class="mb-0">Index Variables by Survey</h5>
                    </div>
                        <div class="card-body">
                            <div class="card-text">
                                <p>Index all variables for a specific survey with survey metadata included</p>
                                <div class="form-groupx row">
                                    <div class="col-2">Survey ID</div>
                                    <div class="col-md-2"><input class="form-controlx" size="6" type="text" v-model="survey_id" placeholder="e.g., 123 (leave empty for all surveys)" /> <span class="text-secondary">Survey ID to process (optional - leave empty for all surveys)</span></div>
            </div>
                                <div class="form-groupx row">
                                    <div class="col-2">Chunk Size</div>
                                    <div class="col-md-2"><input class="form-controlx" size="6" type="text" v-model="survey_var_chunk_size" /> <span class="text-secondary">Variables per batch (default: 200)</span></div>
                                </div>
                                <div class="row">
                                    <div class="col-2">Start Variable ID</div>
                                    <div class="col-md-2"><input class="form-controlx" size="6" type="text" v-model="survey_var_start_id" placeholder="0 for all" /></div>
                                </div>
                                <div class="row">
                                    <div class="col-2">Include Survey Metadata</div>
                                    <div class="col-md-2">
                                        <input type="checkbox" v-model="include_survey_metadata" id="include_metadata" />
                                        <label for="include_metadata">Include survey metadata with variables</label>
                                    </div>
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary btn-sm" v-on:click="indexVariablesBySurveyOnClick" :disabled="survey_var_processing">Start</button>
                                    <button type="button" class="btn btn-warning btn-sm" v-on:click="stopSurveyVarProcessing" :disabled="!survey_var_processing">Stop</button>
                                </div>

                                <div class="col">
                                    <div v-if="survey_var_processing">
                                        <div class="alert alert-info">
                                            <strong v-if="survey_id && survey_id.trim() !== ''">Processing Survey {{survey_id}}</strong>
                                            <strong v-else>Processing All Surveys ({{current_survey_index + 1}} of {{total_surveys}})</strong><br>
                                            <div v-if="survey_var_progress.total_variables">
                                                <div class="progress-container">
                                                    <div class="progress-header">
                                                        <span>Progress: {{survey_var_progress.processed}} / {{survey_var_progress.total_variables}} variables</span>
                                                        <span class="progress-percentage">{{survey_var_progress.percentage}}%</span>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" :style="{width: survey_var_progress.percentage + '%'}"></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="progress-stats">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="stat-item">
                                                                <i class="fas fa-clock"></i>
                                                                <span>Duration: {{processing_duration}}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="stat-item">
                                                                <i class="fas fa-tachometer-alt"></i>
                                                                <span>Speed: {{variables_per_second}} vars/sec</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="stat-item">
                                                                <i class="fas fa-hourglass-half"></i>
                                                                <span>ETA: {{estimated_time_remaining}}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="stat-item">
                                                                <i class="fas fa-memory"></i>
                                                                <span>Memory: {{memory_usage}}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="batch-info">
                                                    <small v-if="survey_id && survey_id.trim() !== ''">Current batch: {{current_batch_size}} variables • Last processed ID: {{survey_var_last_processed}}</small>
                                                    <small v-else>Current survey: {{all_survey_ids[current_survey_index]}} • Processed surveys: {{processed_surveys}}/{{total_surveys}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="survey_var_completed">
                                        <div class="alert alert-success">
                                            <strong>Completed!</strong><br>
                                            <div class="completion-stats">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Survey {{survey_id}}:</strong> {{survey_var_final_result.rows_processed}} variables processed<br>
                                                        <small>Survey metadata included: {{survey_var_final_result.survey_metadata_included ? 'Yes' : 'No'}}</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Final Statistics:</strong><br>
                                                        <small>Total Duration: {{processing_duration}} • Average Speed: {{variables_per_second}} vars/sec</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="survey_var_stopped">
                                        <div class="alert alert-warning">
                                            <strong>Processing Stopped</strong><br>
                                            <div class="stopped-stats">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Survey {{survey_id}}:</strong> {{survey_var_progress.processed}} variables processed ({{survey_var_progress.percentage}}%)<br>
                                                        <small>Last processed ID: {{survey_var_last_processed}}</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Processing Statistics:</strong><br>
                                                        <small>Duration: {{processing_duration}} • Speed: {{variables_per_second}} vars/sec</small>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <small class="text-muted">You can resume from the last processed ID: {{survey_var_last_processed}}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                    <!-- end index variables by survey -->

                    <!-- schema management -->
                    <div v-if="active_container=='schema-management'" class="schema-management">
                
            <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <i class="fas fa-cogs text-warning mr-2"></i>
                            <h5 class="mb-0">Schema Management</h5>
                        </div>
                <div class="card-body">
                    <div class="card-text">
                                <p>Manage SOLR schema fields and field definitions</p>
                                
                                <!-- Schema Overview -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h6>Schema Overview</h6>
                                        <div v-if="schema_info.loading" class="alert alert-info">
                                            <i class="fas fa-spinner fa-spin"></i> Loading schema information...
                                        </div>
                                        <div v-else-if="schema_info.data && schema_info.data.data && schema_info.data.data.result">
                                            <div class="alert alert-info">
                                                <strong>Schema Status:</strong> {{schema_info.data.data.result.schema.name || 'Default'}}<br>
                                                <strong>Version:</strong> {{schema_info.data.data.result.schema.version || 'N/A'}}<br>
                                                <strong>Unique Key:</strong> {{schema_info.data.data.result.schema.uniqueKey || 'N/A'}}
                                            </div>
                                        </div>
                                        <div v-else-if="schema_info.error" class="alert alert-danger">
                                            <strong>Error loading schema:</strong> {{schema_info.error.message || 'Unknown error'}}
                                        </div>
                                        <div v-else class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Schema information not available. Click "Refresh Schema Info" to load.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Field Statistics</h6>
                                        <div v-if="schema_stats.loading" class="alert alert-info">
                                            <i class="fas fa-spinner fa-spin"></i> Loading field statistics...
                                        </div>
                                        <div v-else-if="schema_stats.data && schema_stats.data.data && schema_stats.data.data.result">
                                            <div class="alert alert-success">
                                                <strong>Total Fields:</strong> {{schema_stats.data.data.result.total_fields || calculatedStats.total_fields || 'N/A'}}<br>
                                                <strong>Variable Fields:</strong> {{schema_stats.data.data.result.variable_fields || schema_stats.data.data.result.variable_fields_count || calculatedStats.variable_fields || 'N/A'}}<br>
                                                <strong>Survey Fields:</strong> {{schema_stats.data.data.result.survey_fields || schema_stats.data.data.result.survey_fields_count || calculatedStats.survey_fields || 'N/A'}}<br>
                                                <strong>Copy Fields:</strong> {{schema_stats.data.data.result.copy_fields || schema_stats.data.data.result.copy_fields_count || calculatedStats.copy_fields || 'N/A'}}
                                            </div>
                                            <!-- Debug info for field statistics (remove in production) -->
                                            <div v-if="showDebugInfo" class="mt-2">
                                                <small class="text-muted">
                                                    <strong>Debug - Available keys:</strong> {{Object.keys(schema_stats.data.data.result).join(', ')}}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <strong>Calculated stats:</strong> {{JSON.stringify(calculatedStats)}}
                                                </small>
                                            </div>
                                        </div>
                                        <div v-else-if="schema_stats.error" class="alert alert-danger">
                                            <strong>Error loading statistics:</strong> {{schema_stats.error.message || 'Unknown error'}}
                                        </div>
                                        <div v-else class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Field statistics not available. Click "Refresh Schema Info" to load.
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Schema Setup Actions -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <h6>Schema Setup Actions</h6>
                                        <div class="schema-setup-actions mb-3">
                                            <button type="button" class="btn btn-primary btn-sm" v-on:click="setupVariableFields" :disabled="schema_processing">
                                                <i class="fas fa-cog"></i> Setup Variable Fields
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" v-on:click="setupSurveyFields" :disabled="schema_processing">
                                                <i class="fas fa-cog"></i> Setup Survey Fields
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm" v-on:click="setupCompleteSchema" :disabled="schema_processing">
                                                <i class="fas fa-cogs"></i> Setup Complete Schema
                                            </button>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" v-model="replace_existing_fields" id="replaceFields">
                                            <label class="form-check-label" for="replaceFields">
                                                Replace existing fields
                                            </label>
                                        </div>
                                    </div>
                        </div>

                                <!-- Schema Validation -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <h6>Schema Validation & Refresh</h6>
                                        <div class="schema-validation-actions">
                                            <button type="button" class="btn btn-info btn-sm" v-on:click="validateSchema" :disabled="schema_processing">
                                                <i class="fas fa-check-circle"></i> Validate Schema
                                            </button>
                                            <button type="button" class="btn btn-info btn-sm" v-on:click="refreshSchemaInfo" :disabled="schema_processing">
                                                <i class="fas fa-sync-alt"></i> Refresh Schema Info
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" v-on:click="clearSchemaResults" :disabled="schema_processing">
                                                <i class="fas fa-trash"></i> Clear Results
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" v-on:click="toggleDebugInfo">
                                                <i class="fas" :class="showDebugInfo ? 'fa-eye-slash' : 'fa-eye'"></i> {{showDebugInfo ? 'Hide' : 'Show'}} Debug
                                            </button>
                        </div>
                    </div>                    
                </div>

                                <!-- Schema Processing Status -->
                                <div v-if="schema_processing" class="alert alert-info">
                                    <strong>Processing Schema...</strong><br>
                                    <small>{{schema_processing_message}}</small>
            </div>

                                <!-- Debug Information (only visible in development) -->
                                <div class="row mt-3" v-if="showDebugInfo">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">Debug Information</h6>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggleDebugInfo">
                                                    <i class="fas fa-eye-slash"></i> Hide
                                                </button>
                                            </div>
                                        </div>
                                        <div class="alert alert-light debug-content">
                                            <strong>Schema Info State:</strong><br>
                                            <pre>{{JSON.stringify(schema_info, null, 2)}}</pre>
                                            <strong>Schema Stats State:</strong><br>
                                            <pre>{{JSON.stringify(schema_stats, null, 2)}}</pre>
                                        </div>
                                    </div>
                                </div>

                                <!-- Schema Results -->
                                <div v-if="schema_results.length > 0">
                                    <h6>Schema Operation Results</h6>
                                    <div v-for="(result, index) in schema_results" :key="index" class="alert" :class="result.status === 'success' ? 'alert-success' : 'alert-danger'">
                                        <strong>{{result.operation}}</strong><br>
                                        <small>{{result.message}}</small>
                                        <div v-if="result.details" class="mt-2">
                                            <details>
                                                <summary>Details</summary>
                                                <pre class="mt-2">{{JSON.stringify(result.details, null, 2)}}</pre>
                                            </details>
                                        </div>
                                    </div>
            </div>

                                <!-- Field Management -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h6>Field Management</h6>
                                        <div class="btn-group mb-2" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" v-on:click="loadSchemaFields" :disabled="schema_processing">
                                                <i class="fas fa-list"></i> Load Fields
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" v-on:click="loadFieldTypes" :disabled="schema_processing">
                                                <i class="fas fa-tags"></i> Load Field Types
                                            </button>
                                        </div>
                                        
                                        <!-- Fields List -->
                                        <div v-if="schema_fields.length > 0" class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Current Fields ({{schema_fields.length}})</h6>
                                                <div class="d-flex align-items-center">
                                                    <label class="mr-2 mb-0">Show:</label>
                                                    <select class="form-control form-control-sm" style="width: auto;" @change="changeFieldsPerPage($event.target.value)" :value="fieldsPerPage">
                                                        <option value="10">10</option>
                                                        <option value="25">25</option>
                                                        <option value="50">50</option>
                                                        <option value="100">100</option>
                                                        <option value="0">All</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="table-responsive">
                                                <table class="table table-sm table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Field Name</th>
                                                            <th>Type</th>
                                                            <th>Indexed</th>
                                                            <th>Stored</th>
                                                            <th>Multi-valued</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="field in (fieldsPerPage === 0 ? schema_fields : paginatedFields)" :key="field.name">
                                                            <td>{{field.name}}</td>
                                                            <td>{{field.type}}</td>
                                                            <td><span class="badge" :class="field.indexed ? 'badge-success' : 'badge-secondary'">{{field.indexed ? 'Yes' : 'No'}}</span></td>
                                                            <td><span class="badge" :class="field.stored ? 'badge-success' : 'badge-secondary'">{{field.stored ? 'Yes' : 'No'}}</span></td>
                                                            <td><span class="badge" :class="field.multiValued ? 'badge-warning' : 'badge-secondary'">{{field.multiValued ? 'Yes' : 'No'}}</span></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Pagination Controls -->
                                            <div v-if="fieldsPerPage > 0 && totalPages > 1" class="d-flex justify-content-between align-items-center mt-3">
                                                <div>
                                                    <small class="text-muted">
                                                        Showing {{(fieldsPage - 1) * fieldsPerPage + 1}} to {{Math.min(fieldsPage * fieldsPerPage, schema_fields.length)}} of {{schema_fields.length}} fields
                                                    </small>
                                                </div>
                                                <nav>
                                                    <ul class="pagination pagination-sm mb-0">
                                                        <li class="page-item" :class="{disabled: fieldsPage === 1}">
                                                            <button class="page-link" @click="prevPage" :disabled="fieldsPage === 1">
                                                                <i class="fas fa-chevron-left"></i>
                                                            </button>
                                                        </li>
                                                        
                                                        <li v-for="page in Math.min(5, totalPages)" :key="page" class="page-item" :class="{active: fieldsPage === page}">
                                                            <button class="page-link" @click="goToPage(page)">
                                                                {{page}}
                                                            </button>
                                                        </li>
                                                        
                                                        <li v-if="totalPages > 5" class="page-item disabled">
                                                            <span class="page-link">...</span>
                                                        </li>
                                                        
                                                        <li v-if="totalPages > 5" class="page-item" :class="{active: fieldsPage === totalPages}">
                                                            <button class="page-link" @click="goToPage(totalPages)">
                                                                {{totalPages}}
                                                            </button>
                                                        </li>
                                                        
                                                        <li class="page-item" :class="{disabled: fieldsPage === totalPages}">
                                                            <button class="page-link" @click="nextPage" :disabled="fieldsPage === totalPages">
                                                                <i class="fas fa-chevron-right"></i>
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </nav>
                                            </div>
                                            
                                            <div v-else-if="fieldsPerPage === 0" class="text-muted mt-2">
                                                <small>Showing all {{schema_fields.length}} fields</small>
                                            </div>
                                        </div>

                                        <!-- Field Types List -->
                                        <div v-if="field_types.length > 0" class="mt-3">
                                            <h6>Available Field Types ({{field_types.length}})</h6>
                                            <div class="row">
                                                <div v-for="fieldType in field_types.slice(0, 12)" :key="fieldType" class="col-md-2 mb-1">
                                                    <span class="badge badge-light">{{fieldType}}</span>
                                                </div>
                                            </div>
                                            <div v-if="field_types.length > 12" class="text-muted">
                                                Showing first 12 field types of {{field_types.length}} total types
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    </div>
                    <!-- end schema management -->

                    <!-- Core Management -->
                    <div v-if="active_container=='core-management'" class="core-management">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-server text-info mr-2"></i>
                                <h5 class="mb-0">Core Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="card-text">
                                    <p>Check and create the configured Solr core/collection</p>

                                    {{configured_core}}
                                    
                                    <!-- Configured Core Status -->
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <h6>Configured Core Status</h6>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <button type="button" class="btn btn-primary btn-sm" v-on:click="checkConfiguredCore" :disabled="core_check_loading">
                                                    <i class="fas fa-sync-alt" :class="{'fa-spin': core_check_loading}"></i> 
                                                    {{core_check_loading ? 'Checking...' : 'Check Core Status'}}
                                                </button>
                                            </div>
                                            
                                            <div v-if="core_check_loading" class="alert alert-info">
                                                <i class="fas fa-spinner fa-spin"></i> Checking configured core...
                                            </div>
                                            
                                            <div v-else-if="configured_core">
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                <h6 class="mb-1">Configured Core: <strong>{{configured_core}}</strong></h6>
                                                                <small class="text-muted">From: <code>application/config/solr.php</code></small>
                                                            </div>
                                                            <div>
                                                                <span v-if="configured_core_exists" class="badge badge-success badge-lg">
                                                                    <i class="fas fa-check-circle"></i> Exists
                                                                </span>
                                                                <span v-else class="badge badge-danger badge-lg">
                                                                    <i class="fas fa-times-circle"></i> Not Found
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <div v-if="configured_core_exists && configured_core_status" class="mt-3">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <small class="text-muted">Instance Directory:</small><br>
                                                                    <strong>{{configured_core_status.instanceDir || 'N/A'}}</strong>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <small class="text-muted">Config:</small><br>
                                                                    <strong>{{configured_core_status.config || 'N/A'}}</strong>
                                                                </div>
                                                                <div class="col-md-6 mt-2">
                                                                    <small class="text-muted">Schema:</small><br>
                                                                    <strong>{{configured_core_status.schema || 'N/A'}}</strong>
                                                                </div>
                                                                <div class="col-md-6 mt-2">
                                                                    <small class="text-muted">Uptime:</small><br>
                                                                    <strong v-if="configured_core_status.uptime">{{formatUptime(configured_core_status.uptime)}}</strong>
                                                                    <strong v-else>N/A</strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div v-if="!configured_core_exists" class="mt-3">
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle"></i> 
                                                                The configured core "<strong>{{configured_core}}</strong>" does not exist in Solr.
                                                            </div>
                                                            <button type="button" 
                                                                    class="btn btn-success btn-sm" 
                                                                    v-on:click="createConfiguredCore" 
                                                                    :disabled="core_processing">
                                                                <i class="fas fa-plus"></i> Create Core "{{configured_core}}"
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div v-else-if="!core_check_loading && !configured_core" class="alert alert-danger">
                                                <i class="fas fa-exclamation-circle"></i> 
                                                No core configured in <code>application/config/solr.php</code>. 
                                                Please set <code>$config['solr_collection']</code> in the config file.
                                            </div>
                                            <div v-else-if="!core_check_loading && configured_core" class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> 
                                                Configured core: <strong>{{configured_core}}</strong> - Click "Check Core Status" to verify.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Core Processing Status -->
                                    <div v-if="core_processing" class="alert alert-info">
                                        <strong>Processing Core Operation...</strong><br>
                                        <small>{{core_processing_message}}</small>
                                    </div>

                                    <!-- Core Operation Results -->
                                    <div v-if="core_results.length > 0">
                                        <h6>Core Operation Results</h6>
                                        <div v-for="(result, index) in core_results" :key="index" class="alert" 
                                             :class="result.status === 'success' ? 'alert-success' : 'alert-danger'">
                                            <strong>{{result.operation}}</strong><br>
                                            <small>{{result.message}}</small>
                                            <div v-if="result.details" class="mt-2">
                                                <details>
                                                    <summary>Details</summary>
                                                    <pre class="mt-2">{{JSON.stringify(result.details, null, 2)}}</pre>
                                                </details>
            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end core management -->

                                        
                    </div>
                <!--end-->
            </div>
            <!-- Main Col END -->


        </div><!--end app -->
    </div>
</div>






<script>

        var app = new Vue({
            el: '#app',
            data:{
                solr_base_url:'',
                ping_status:{},
                index_counts:{},
                db_counts:{},
                pinging: false,
                lastPingTime: '',
                lastUpdateTime: '',
                active_container:'home',
                errors:[],
                dataset_rows_limit:15,
                dataset_start_row:0,
                dataset_last_row_processed:0,
                dataset_total_count:0,
                indexing_running:false,
                indexing_status:0,
                indexing_processed:0,
                dataset_stopped:false,
                
                var_rows_limit:1000,
                var_start_row:0,
                variable_last_row_processed:0,
                variable_total_count:0,
                var_processing:false,
                var_completed:false,
                var_stopped:false,

                // Survey variables processing
                survey_id:'',
                survey_var_chunk_size:200,
                survey_var_start_id:0,
                include_survey_metadata:true,
                survey_var_processing:false,
                survey_var_completed:false,
                survey_var_stopped:false,
                survey_var_progress:{
                    processed:0,
                    total_variables:0,
                    percentage:0
                },
                survey_var_last_processed:0,
                survey_var_final_result:{},
                
                // Enhanced progress tracking
                start_time: null,
                variables_per_second: 0,
                estimated_time_remaining: 'Calculating...',
                memory_usage: '0 MB',
                processing_duration: '00:00:00',
                current_batch_size: 0,
                total_processed: 0,
                retry_count: 0,
                max_retries: 3,
                
                // All surveys processing
                all_survey_ids: [],
                current_survey_index: 0,
                total_surveys: 0,
                processed_surveys: 0,

                // Schema management
                schema_info: {
                    data: null,
                    loading: false,
                    error: null
                },
                schema_stats: {
                    data: null,
                    loading: false,
                    error: null
                },
                schema_processing: false,
                schema_processing_message: '',
                schema_results: [],
                replace_existing_fields: false,
                schema_fields: [],
                field_types: [],
                showDebugInfo: false,
                fieldsPage: 1,
                fieldsPerPage: 25,

                // Core management
                configured_core: '',
                configured_core_exists: false,
                configured_core_status: null,
                core_check_loading: false,
                core_processing: false,
                core_processing_message: '',
                core_results: [],
                
                // System info
                system_info: {
                    loading: false,
                    data: null
                },

            },
            mounted: function () {
               this.pingSolr();
               this.checkConfiguredCore();
               this.getSystemInfo();
            },
            computed: {
                isAnyProcessing: function() {
                    return this.indexing_running || this.var_processing || this.survey_var_processing || this.schema_processing;
                },
                calculatedStats: function() {
                    return this.calculateFieldStats();
                },
                paginatedFields: function() {
                    const start = (this.fieldsPage - 1) * this.fieldsPerPage;
                    const end = start + this.fieldsPerPage;
                    return this.schema_fields.slice(start, end);
                },
                totalPages: function() {
                    return Math.ceil(this.schema_fields.length / this.fieldsPerPage);
                }
            },
            methods: {    
                
                pingSolr: function (){
                    this.pinging = true;
                    let url=CI.base_url + '/api/solr/ping';
                    vm=this;
                    
                    console.log('Pinging SOLR at URL:', url);
                    
                    axios.get(url)
                    .then(function (response) {
                        console.log('SOLR Ping Response:', response);                        
                        vm.ping_status=response;
                        vm.lastPingTime = new Date().toLocaleTimeString();
                        vm.lastUpdateTime = new Date().toLocaleString();
                        
                        // Show success message
                        if (response.data && response.data.status === 'success') {
                            console.log('SOLR ping successful:', response.data.result);
                        }
                        
                        vm.getIndexCounts();
                        vm.getDbCounts();
                    })
                    .catch(function (error) {
                        console.error('SOLR Ping Error:', error);
                        vm.lastPingTime = new Date().toLocaleTimeString();
                        vm.lastUpdateTime = new Date().toLocaleString();
                        
                        // Set error status
                        vm.ping_status = {
                            data: {
                                result: {
                                    status: 'ERROR',
                                    message: error.response ? error.response.data?.message || error.message : 'Connection failed'
                                }
                            }
                        };
                        
                        // Show error message to user
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            vm.showError('SOLR ping failed: ' + (error.response.data?.message || error.response.statusText));
                        } else if (error.request) {
                            console.error('No response received:', error.request);
                            vm.showError('SOLR ping failed: No response from server. Check SOLR configuration.');
                        } else {
                            console.error('Request setup error:', error.message);
                            vm.showError('SOLR ping failed: ' + error.message);
                        }
                    })
                    .then(function () {
                        // always executed
                        vm.pinging = false;
                        console.log("SOLR ping request completed");
                    });
                },
                testSolrConnection: function() {
                    console.log('Testing SOLR connection...');
                    
                    // Test direct connection to SOLR
                    const solrUrl = 'http://localhost:8983/solr/nada/admin/ping';
                    console.log('Testing direct SOLR URL:', solrUrl);
                    
                    axios.get(solrUrl, { timeout: 5000 })
                    .then(function(response) {
                        console.log('Direct SOLR ping successful:', response);
                        alert('Direct SOLR connection successful!\n\nResponse: ' + JSON.stringify(response.data, null, 2));
                    })
                    .catch(function(error) {
                        console.error('Direct SOLR ping failed:', error);
                        let errorMsg = 'Direct SOLR connection failed.\n\n';
                        
                        if (error.code === 'ECONNREFUSED') {
                            errorMsg += 'Connection refused. SOLR server may not be running on localhost:8983';
                        } else if (error.code === 'ETIMEDOUT') {
                            errorMsg += 'Connection timed out. SOLR server may be slow or unreachable';
                        } else if (error.response) {
                            errorMsg += 'HTTP ' + error.response.status + ': ' + error.response.statusText;
                        } else {
                            errorMsg += error.message;
                        }
                        
                        alert(errorMsg);
                    });
                },
                commitSolr: function (){
                    let url=CI.base_url + '/api/solr/commit';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);
                        vm.pingSolr();
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                clearSolr: function (){
                    if (!confirm("Are you sure you want to clear the index? This will remove all documents from the index?")){
                        return false;
                    }
                    
                    let url=CI.base_url + '/api/solr/clear_index';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);
                        vm.pingSolr();
                        alert("All documents have been removed from the index");
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                getIndexCounts: function(){
                    let url=CI.base_url + '/api/solr/index_counts';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);                        
                        vm.index_counts=response;                        
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                getDbCounts: function(){
                    let url=CI.base_url + '/api/solr/db_counts';
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        console.log(response);                        
                        vm.db_counts=response;                        
                        // Set total count for datasets
                        if (response.data && response.data.result && response.data.result.datasets) {
                            vm.dataset_total_count = response.data.result.datasets;
                        }
                        // Set total count for variables
                        if (response.data && response.data.result && response.data.result.variables) {
                            vm.variable_total_count = response.data.result.variables;
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                getDatasetProgressPercentage: function() {
                    if (this.dataset_total_count === 0 || this.indexing_status === 0) {
                        return 0;
                    }
                    return (this.indexing_status / this.dataset_total_count) * 100;
                },
                resumeFromLastProcessed: function() {
                    if (this.dataset_last_row_processed > 0) {
                        this.dataset_start_row = this.dataset_last_row_processed;
                    }
                },
                resumeVariableFromLastProcessed: function() {
                    if (this.variable_last_row_processed > 0) {
                        this.var_start_row = this.variable_last_row_processed;
                    }
                },
                useLastVariableId: function() {
                    if (this.index_counts.data && this.index_counts.data.result && this.index_counts.data.result.last_variable) {
                        this.var_start_row = this.index_counts.data.result.last_variable;
                    }
                },
                getVariableProgressPercentage: function() {
                    if (this.variable_total_count === 0 || this.indexing_status === 0) {
                        return 0;
                    }
                    return (this.indexing_status / this.variable_total_count) * 100;
                },
                resumeVariableProcessing: function() {
                    // Use last processed ID as start row if available
                    if (this.variable_last_row_processed > 0) {
                        this.var_start_row = this.variable_last_row_processed;
                    }
                    // Reset stopped flag and start indexing
                    this.var_stopped = false;
                    this.indexing_processed = 0;
                    this.indexVariablesOnClick();
                },
                resumeVariableFromLastProcessed: function() {
                    if (this.variable_last_row_processed > 0) {
                        this.var_start_row = this.variable_last_row_processed;
                    }
                },
                useLastVariableId: function() {
                    if (this.index_counts.data && this.index_counts.data.result && this.index_counts.data.result.last_variable) {
                        this.var_start_row = this.index_counts.data.result.last_variable;
                    }
                },
                getVariableProgressPercentage: function() {
                    if (this.variable_total_count === 0 || this.indexing_status === 0) {
                        return 0;
                    }
                    return (this.indexing_status / this.variable_total_count) * 100;
                },
                resumeVariableProcessing: function() {
                    // Use last processed ID as start row if available
                    if (this.variable_last_row_processed > 0) {
                        this.var_start_row = this.variable_last_row_processed;
                    }
                    // Reset stopped flag and start indexing
                    this.var_stopped = false;
                    this.indexing_processed = 0;
                    this.indexVariablesOnClick();
                },
                resumeDatasetProcessing: function() {
                    // Use last processed ID as start row if available
                    if (this.dataset_last_row_processed > 0) {
                        this.dataset_start_row = this.dataset_last_row_processed;
                    }
                    // Reset stopped flag and start indexing
                    this.dataset_stopped = false;
                    this.indexing_processed = 0;
                    this.indexing_running = true;
                    this.indexDatasets(this.dataset_start_row, this.dataset_rows_limit);
                },
                toggleTab: function(tab){
                    this.active_container=tab;
                    this.indexing_processed=0;
                    // Reset survey variables state when switching tabs
                    if (tab !== 'index-variables-by-survey') {
                        this.survey_var_processing = false;
                        this.survey_var_completed = false;
                        this.survey_var_stopped = false;
                        this.survey_var_progress = {
                            processed: 0,
                            total_variables: 0,
                            percentage: 0
                        };
                    }
                    
                    // Reset other processing states when switching tabs
                    if (tab !== 'index-datasets') {
                        this.indexing_running = false;
                        this.dataset_stopped = false;
                    }
                    
                    if (tab !== 'index-variables') {
                        this.var_processing = false;
                        this.var_stopped = false;
                    }
                    
                    // Refresh core information when switching to home
                    if (tab === 'home') {
                        this.checkConfiguredCore();
                        this.pingSolr();
                    }
                    
                    // Load database counts when switching to index-datasets tab
                    if (tab === 'index-datasets') {
                        this.getDbCounts();
                    }
                    
                    // Load variable counts when switching to index-variables tab
                    if (tab === 'index-variables') {
                        this.getDbCounts();
                        // Auto-populate start row from last indexed variable if available
                        if (this.index_counts.data && this.index_counts.data.result && this.index_counts.data.result.last_variable && this.var_start_row === 0) {
                            this.var_start_row = this.index_counts.data.result.last_variable;
                        }
                    }
                    
                    // Load schema information when switching to schema management
                    if (tab === 'schema-management') {
                        console.log('Switching to schema management tab, refreshing schema info...');
                        this.refreshSchemaInfo();
                    }
                    
                    // Check configured core when switching to core management
                    if (tab === 'core-management') {
                        console.log('Switching to core management tab, checking configured core...');
                        this.checkConfiguredCore();
                    }
                },
                clearAll: function(){
                    alert('clear all');
                },
                indexDatasetOnClick: function()
                {
                    this.indexing_processed=0;
                    this.dataset_stopped=false;
                    this.indexing_running=true;
                    this.indexDatasets(this.dataset_start_row,this.dataset_rows_limit);
                },
                indexDatasets: function (start_row=0, limit=5, processed=0)
                {
                    // Check if processing was stopped
                    if (!this.indexing_running) {
                        console.log('Dataset processing stopped by user');
                        return;
                    }
                    
                    let url=CI.base_url + '/api/solr/import_surveys_batch/'+start_row + '/'+limit;
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        // Check if processing was stopped during request
                        if (!vm.indexing_running) {
                            console.log('Dataset processing stopped during request');
                            return;
                        }
                        
                        last_row_id=response.data.result.last_row_id;
                        rows_processed=response.data.result.rows_processed;                        

                        if(last_row_id>0){
                            processed+=rows_processed;
                            vm.indexing_processed=processed;
                            vm.indexing_status=processed;
                            vm.dataset_last_row_processed=last_row_id;
                            
                            // Continue only if processing hasn't been stopped
                            if (vm.indexing_running) {
                            vm.indexDatasets(last_row_id,vm.dataset_rows_limit,processed);
                            }
                        }
                        else{
                            vm.indexing_status=processed;
                            vm.indexing_running=false;
                            vm.commitSolr();
                            return true;
                        }                        
                    })
                    .catch(function (error) {
                        // Only handle error if processing hasn't been stopped
                        if (vm.indexing_running) {
                        console.log(error);
                            vm.indexing_running = false;
                        }
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                indexVariablesOnClick: function()
                {
                    this.indexing_processed=0;
                    this.var_stopped=false;
                    this.var_processing=true;
                    this.indexVariables(this.var_start_row,this.var_rows_limit);
                },
                indexVariables: function (start_row=0, limit=5, processed=0)
                {
                    // Check if processing was stopped
                    if (!this.var_processing) {
                        console.log('Variable processing stopped by user');
                        return;
                    }
                    
                    let url=CI.base_url + '/api/solr/import_variables_batch/'+start_row + '/'+limit;
                    vm=this;
                    axios.get(url)
                    .then(function (response) {
                        // Check if processing was stopped during request
                        if (!vm.var_processing) {
                            console.log('Variable processing stopped during request');
                            return;
                        }
                        
                        last_row_id=response.data.result.last_row_id;
                        rows_processed=response.data.result.rows_processed;                        

                        if(last_row_id>0){
                            processed+=rows_processed;
                            vm.indexing_processed=processed;
                            vm.indexing_status=processed;
                            vm.variable_last_row_processed=last_row_id;
                            
                            // Continue only if processing hasn't been stopped
                            if (vm.var_processing) {
                            vm.indexVariables(last_row_id,vm.var_rows_limit,processed);
                            }
                        }
                        else{
                            vm.indexing_status=processed;
                            vm.var_processing=false;
                            vm.commitSolr();
                            return true;
                        }                        
                    })
                    .catch(function (error) {
                        // Only handle error if processing hasn't been stopped
                        if (vm.var_processing) {
                        console.log(error);
                            vm.var_processing = false;
                        }
                    })
                    .then(function () {
                        // always executed
                        console.log("request completed");
                    });
                },
                indexVariablesBySurveyOnClick: function()
                {
                    this.survey_var_processing = true;
                    this.survey_var_completed = false;
                    this.survey_var_stopped = false;
                    this.survey_var_progress = {
                        processed: 0,
                        total_variables: 0,
                        percentage: 0
                    };
                    this.survey_var_last_processed = 0;
                    
                    // Initialize enhanced progress tracking
                    this.start_time = Date.now();
                    this.variables_per_second = 0;
                    this.estimated_time_remaining = 'Calculating...';
                    this.processing_duration = '00:00:00';
                    this.current_batch_size = 0;
                    this.total_processed = 0;
                    this.retry_count = 0;
                    
                    // If survey_id is provided, process that specific survey
                    if (this.survey_id && this.survey_id.trim() !== '') {
                        this.indexVariablesBySurvey(this.survey_id, this.survey_var_start_id, this.survey_var_chunk_size);
                    } else {
                        // Process all surveys
                        this.indexAllSurveysVariables();
                    }
                },
                indexVariablesBySurvey: function(survey_id, start_id=0, chunk_size=200, is_part_of_batch=false)
                {
                    // Check if processing was stopped
                    if (!this.survey_var_processing) {
                        console.log('Processing stopped by user');
                        return;
                    }
                    
                    let url = CI.base_url + '/api/solr/import_variables_by_survey_batch/' + survey_id + '/' + start_id + '/' + chunk_size;
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        // Check again if processing was stopped during the request
                        if (!vm.survey_var_processing) {
                            console.log('Processing stopped during request');
                            return;
                        }
                        
                        console.log('Survey variables response:', response.data);
                        
                        const result = response.data.result;
                        
                        // Update progress
                        vm.survey_var_progress.total_variables = result.total_variables;
                        vm.survey_var_progress.processed = result.rows_processed;
                        vm.survey_var_progress.percentage = result.progress_percentage;
                        vm.survey_var_last_processed = result.last_row_id;
                        vm.current_batch_size = result.rows_processed;
                        
                        // Update enhanced progress tracking
                        vm.updateProgressStats();
                        
                        // Check if processing is complete
                        if (result.progress_percentage >= 100 || result.rows_processed === 0) {
                            // If this is part of batch processing, move to next survey
                            if (is_part_of_batch) {
                                vm.processed_surveys++;
                                vm.current_survey_index++;
                                vm.processNextSurvey();
                            } else {
                                vm.survey_var_processing = false;
                                vm.survey_var_completed = true;
                                vm.survey_var_final_result = result;
                                vm.commitSolr();
                                console.log('Survey variables processing completed');
                            }
                        } else {
                            // Continue with next batch only if processing hasn't been stopped
                            if (vm.survey_var_processing) {
                                setTimeout(function() {
                                    vm.indexVariablesBySurvey(survey_id, result.last_row_id, chunk_size, is_part_of_batch);
                                }, 100); // Small delay to prevent overwhelming the server
                            }
                        }
                    })
                    .catch(function (error) {
                        // Only handle error if processing hasn't been stopped
                        if (vm.survey_var_processing) {
                            console.log('Error processing survey variables:', error);
                            vm.handleProcessingError(error);
                        }
                    });
                },
                stopSurveyVarProcessing: function()
                {
                    if (confirm('Are you sure you want to stop processing? This will halt the current batch and any remaining variables will not be processed.')) {
                        this.survey_var_processing = false;
                        this.survey_var_completed = false;
                        
                        // Show stopped status
                        this.showStoppedStatus();
                        
                        console.log('Survey variables processing stopped by user');
                        console.log('Last processed ID:', this.survey_var_last_processed);
                        console.log('Variables processed so far:', this.survey_var_progress.processed);
                    }
                },
                updateProgressStats: function()
                {
                    if (this.start_time && this.survey_var_progress.processed > 0) {
                        const elapsed = (Date.now() - this.start_time) / 1000;
                        
                        // Calculate processing speed
                        this.variables_per_second = Math.round(this.survey_var_progress.processed / elapsed);
                        
                        // Calculate ETA
                        const remaining = this.survey_var_progress.total_variables - this.survey_var_progress.processed;
                        if (this.variables_per_second > 0) {
                            const eta_seconds = remaining / this.variables_per_second;
                            this.estimated_time_remaining = this.formatTime(eta_seconds);
                        }
                        
                        // Update processing duration
                        this.processing_duration = this.formatTime(elapsed);
                        
                        // Simulate memory usage (in real implementation, this would come from backend)
                        this.memory_usage = this.calculateMemoryUsage();
                    }
                },
                formatTime: function(seconds)
                {
                    if (seconds < 60) {
                        return Math.round(seconds) + 's';
                    } else if (seconds < 3600) {
                        const minutes = Math.floor(seconds / 60);
                        const secs = Math.round(seconds % 60);
                        return minutes + 'm ' + secs + 's';
                    } else {
                        const hours = Math.floor(seconds / 3600);
                        const minutes = Math.floor((seconds % 3600) / 60);
                        const secs = Math.round(seconds % 60);
                        return hours + 'h ' + minutes + 'm ' + secs + 's';
                    }
                },
                calculateMemoryUsage: function()
                {
                    // Simulate memory usage based on processed variables
                    const baseMemory = 50; // MB
                    const memoryPerVariable = 0.01; // MB per variable
                    const estimatedMemory = baseMemory + (this.survey_var_progress.processed * memoryPerVariable);
                    return Math.round(estimatedMemory) + ' MB';
                },
                handleProcessingError: function(error)
                {
                    console.error('Processing error:', error);
                    
                    // Auto-retry logic
                    if (this.retry_count < this.max_retries) {
                        this.retry_count++;
                        console.log(`Retrying... Attempt ${this.retry_count}/${this.max_retries}`);
                        
                        setTimeout(() => {
                            this.indexVariablesBySurvey(this.survey_id, this.survey_var_last_processed, this.survey_var_chunk_size);
                        }, 5000); // Wait 5 seconds before retry
                    } else {
                        this.survey_var_processing = false;
                        this.showError('Processing failed after ' + this.max_retries + ' retries: ' + (error.response?.data?.message || error.message));
                    }
                },
                showError: function(message)
                {
                    alert('Error: ' + message);
                },
                showStoppedStatus: function()
                {
                    this.survey_var_stopped = true;
                    this.survey_var_processing = false;
                    this.survey_var_completed = false;
                    
                    // Update the start variable ID field to allow resuming
                    this.survey_var_start_id = this.survey_var_last_processed;
                },
                stopDatasetProcessing: function()
                {
                    if (confirm('Are you sure you want to stop dataset processing? This will halt the current batch and any remaining datasets will not be processed.')) {
                        this.indexing_running = false;
                        this.dataset_stopped = true;
                        
                        console.log('Dataset processing stopped by user');
                        console.log('Last processed ID:', this.dataset_last_row_processed);
                        console.log('Datasets processed so far:', this.indexing_status);
                    }
                },
                stopVariableProcessing: function()
                {
                    if (confirm('Are you sure you want to stop variable processing? This will halt the current batch and any remaining variables will not be processed.')) {
                        this.var_processing = false;
                        this.var_stopped = true;
                        
                        console.log('Variable processing stopped by user');
                        console.log('Last processed ID:', this.variable_last_row_processed);
                        console.log('Variables processed so far:', this.indexing_status);
                    }
                },
                stopAllProcessing: function()
                {
                    if (confirm('Are you sure you want to stop ALL processing operations? This will halt all current indexing operations.')) {
                        // Stop dataset processing
                        if (this.indexing_running) {
                            this.indexing_running = false;
                            this.dataset_stopped = true;
                        }
                        
                        // Stop variable processing
                        if (this.var_processing) {
                            this.var_processing = false;
                            this.var_stopped = true;
                        }
                        
                        // Stop survey variable processing
                        if (this.survey_var_processing) {
                            this.survey_var_processing = false;
                            this.survey_var_stopped = true;
                        }
                        
                        console.log('All processing operations stopped by user');
                        alert('All processing operations have been stopped.');
                    }
                },
                indexAllSurveysVariables: function()
                {
                    // Get all survey IDs from the database
                    let url = CI.base_url + '/api/solr/get_all_survey_ids';
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        if (!vm.survey_var_processing) {
                            console.log('Processing stopped during survey ID fetch');
                            return;
                        }
                        
                        const surveyIds = response.data.result.survey_ids;
                        console.log('Found', surveyIds.length, 'surveys to process');
                        
                        if (surveyIds.length === 0) {
                            vm.survey_var_processing = false;
                            vm.survey_var_completed = true;
                            vm.survey_var_final_result = {
                                survey_id: 'all',
                                rows_processed: 0,
                                total_variables: 0,
                                last_row_id: null,
                                survey_metadata_included: true,
                                progress_percentage: 100,
                                chunk_size: vm.survey_var_chunk_size
                            };
                            vm.commitSolr();
                            return;
                        }
                        
                        // Start processing the first survey
                        vm.current_survey_index = 0;
                        vm.all_survey_ids = surveyIds;
                        vm.total_surveys = surveyIds.length;
                        vm.processed_surveys = 0;
                        
                        vm.processNextSurvey();
                    })
                    .catch(function (error) {
                        console.log('Error fetching survey IDs:', error);
                        vm.survey_var_processing = false;
                        vm.showError('Error fetching survey IDs: ' + (error.response?.data?.message || error.message));
                    });
                },
                processNextSurvey: function()
                {
                    if (!this.survey_var_processing) {
                        console.log('Processing stopped during survey processing');
                        return;
                    }
                    
                    if (this.current_survey_index >= this.all_survey_ids.length) {
                        // All surveys processed
                        this.survey_var_processing = false;
                        this.survey_var_completed = true;
                        this.survey_var_final_result = {
                            survey_id: 'all',
                            rows_processed: this.survey_var_progress.processed,
                            total_variables: this.survey_var_progress.total_variables,
                            last_row_id: this.survey_var_last_processed,
                            survey_metadata_included: true,
                            progress_percentage: 100,
                            chunk_size: this.survey_var_chunk_size,
                            total_surveys_processed: this.processed_surveys
                        };
                        this.commitSolr();
                        console.log('All surveys processing completed');
                        return;
                    }
                    
                    const currentSurveyId = this.all_survey_ids[this.current_survey_index];
                    console.log('Processing survey', currentSurveyId, '(', this.current_survey_index + 1, 'of', this.total_surveys, ')');
                    
                    // Process current survey
                    this.indexVariablesBySurvey(currentSurveyId, 0, this.survey_var_chunk_size, true);
                },

                // Schema Management Methods
                refreshSchemaInfo: function() {
                    console.log('Refreshing schema info...');
                    this.schema_info.loading = true;
                    this.schema_stats.loading = true;
                    
                    // Load schema information
                    this.loadSchemaInfo();
                    this.loadSchemaStats();
                },
                
                loadSchemaInfo: function() {
                    let url = CI.base_url + '/api/solr/schema';
                    console.log('Loading schema info from:', url);
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        console.log('Schema info response:', response);
                        vm.schema_info = {
                            data: response,
                            loading: false
                        };
                    })
                    .catch(function (error) {
                        console.log('Error loading schema info:', error);
                        vm.schema_info = {
                            data: null,
                            loading: false,
                            error: {
                                message: error.response?.data?.message || error.message || 'Failed to load schema information',
                                status: error.response?.status || 'Unknown',
                                details: error.response?.data
                            }
                        };
                    });
                },
                
                loadSchemaStats: function() {
                    let url = CI.base_url + '/api/solr/schema_stats';
                    console.log('Loading schema stats from:', url);
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        console.log('Schema stats response:', response);
                        vm.schema_stats = {
                            data: response,
                            loading: false
                        };
                    })
                    .catch(function (error) {
                        console.log('Error loading schema stats:', error);
                        vm.schema_stats = {
                            data: null,
                            loading: false,
                            error: {
                                message: error.response?.data?.message || error.message || 'Failed to load schema statistics',
                                status: error.response?.status || 'Unknown',
                                details: error.response?.data
                            }
                        };
                    });
                },
                
                setupVariableFields: function() {
                    if (!confirm('This will setup variable fields in the SOLR schema. Continue?')) {
                        return;
                    }
                    
                    this.schema_processing = true;
                    this.schema_processing_message = 'Setting up variable fields...';
                    
                    let url = CI.base_url + '/api/solr/schema_setup_variables';
                    if (this.replace_existing_fields) {
                        url += '?replace=true';
                    }
                    
                    vm = this;
                    axios.get(url)
                    .then(function (response) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Setup Variable Fields',
                            status: 'success',
                            message: 'Variable fields setup completed successfully',
                            details: response.data
                        });
                        
                        // Refresh schema info
                        vm.refreshSchemaInfo();
                    })
                    .catch(function (error) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Setup Variable Fields',
                            status: 'error',
                            message: 'Failed to setup variable fields: ' + (error.response?.data?.message || error.message),
                            details: error.response?.data
                        });
                    });
                },
                
                setupSurveyFields: function() {
                    if (!confirm('This will setup survey fields in the SOLR schema. Continue?')) {
                        return;
                    }
                    
                    this.schema_processing = true;
                    this.schema_processing_message = 'Setting up survey fields...';
                    
                    let url = CI.base_url + '/api/solr/schema_setup_surveys';
                    if (this.replace_existing_fields) {
                        url += '?replace=true';
                    }
                    
                    vm = this;
                    axios.get(url)
                    .then(function (response) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Setup Survey Fields',
                            status: 'success',
                            message: 'Survey fields setup completed successfully',
                            details: response.data
                        });
                        
                        // Refresh schema info
                        vm.refreshSchemaInfo();
                    })
                    .catch(function (error) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Setup Survey Fields',
                            status: 'error',
                            message: 'Failed to setup survey fields: ' + (error.response?.data?.message || error.message),
                            details: error.response?.data
                        });
                    });
                },
                
                setupCompleteSchema: function() {
                    if (!confirm('This will setup the complete SOLR schema (variables + surveys). This may take a moment. Continue?')) {
                        return;
                    }
                    
                    this.schema_processing = true;
                    this.schema_processing_message = 'Setting up complete schema...';
                    
                    let url = CI.base_url + '/api/solr/schema_setup_complete';
                    if (this.replace_existing_fields) {
                        url += '?replace=true';
                    }
                    
                    vm = this;
                    axios.get(url)
                    .then(function (response) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Setup Complete Schema',
                            status: 'success',
                            message: 'Complete schema setup completed successfully',
                            details: response.data
                        });
                        
                        // Refresh schema info
                        vm.refreshSchemaInfo();
                    })
                    .catch(function (error) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Setup Complete Schema',
                            status: 'error',
                            message: 'Failed to setup complete schema: ' + (error.response?.data?.message || error.message),
                            details: error.response?.data
                        });
                    });
                },
                
                validateSchema: function() {
                    this.schema_processing = true;
                    this.schema_processing_message = 'Validating schema...';
                    
                    let url = CI.base_url + '/api/solr/schema_validate';
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Schema Validation',
                            status: 'success',
                            message: 'Schema validation completed',
                            details: response.data
                        });
                    })
                    .catch(function (error) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Schema Validation',
                            status: 'error',
                            message: 'Schema validation failed: ' + (error.response?.data?.message || error.message),
                            details: error.response?.data
                        });
                    });
                },
                
                loadSchemaFields: function() {
                    this.schema_processing = true;
                    this.schema_processing_message = 'Loading schema fields...';
                    
                    let url = CI.base_url + '/api/solr/schema_fields';
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        // Get detailed field information from schema
                        vm.loadDetailedFieldInfo();
                    })
                    .catch(function (error) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Load Schema Fields',
                            status: 'error',
                            message: 'Failed to load schema fields: ' + (error.response?.data?.message || error.message),
                            details: error.response?.data
                        });
                    });
                },
                
                loadDetailedFieldInfo: function() {
                    // Get detailed field information from the schema
                    if (this.schema_info.data && this.schema_info.data.data && this.schema_info.data.data.result && this.schema_info.data.data.result.schema) {
                        const schema = this.schema_info.data.data.result.schema;
                        if (schema.fields) {
                            this.schema_fields = schema.fields;
                        }
                    }
                },
                
                loadFieldTypes: function() {
                    this.schema_processing = true;
                    this.schema_processing_message = 'Loading field types...';
                    
                    let url = CI.base_url + '/api/solr/schema_field_types';
                    vm = this;
                    
                    axios.get(url)
                    .then(function (response) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        if (response.data && response.data.result) {
                            vm.field_types = response.data.result;
                        }
                    })
                    .catch(function (error) {
                        vm.schema_processing = false;
                        vm.schema_processing_message = '';
                        
                        vm.schema_results.unshift({
                            operation: 'Load Field Types',
                            status: 'error',
                            message: 'Failed to load field types: ' + (error.response?.data?.message || error.message),
                            details: error.response?.data
                        });
                    });
                },
                
                clearSchemaResults: function() {
                    this.schema_results = [];
                },
                
                toggleDebugInfo: function() {
                    this.showDebugInfo = !this.showDebugInfo;
                },
                
                // Pagination methods for fields
                goToPage: function(page) {
                    if (page >= 1 && page <= this.totalPages) {
                        this.fieldsPage = page;
                    }
                },
                
                nextPage: function() {
                    if (this.fieldsPage < this.totalPages) {
                        this.fieldsPage++;
                    }
                },
                
                prevPage: function() {
                    if (this.fieldsPage > 1) {
                        this.fieldsPage--;
                    }
                },
                
                changeFieldsPerPage: function(perPage) {
                    this.fieldsPerPage = parseInt(perPage);
                    this.fieldsPage = 1; // Reset to first page
                },
                
                // Calculate field statistics from schema fields
                calculateFieldStats: function() {
                    if (!this.schema_fields || this.schema_fields.length === 0) {
                        return {
                            total_fields: 0,
                            variable_fields: 0,
                            survey_fields: 0,
                            copy_fields: 0
                        };
                    }
                    
                    let stats = {
                        total_fields: this.schema_fields.length,
                        variable_fields: 0,
                        survey_fields: 0,
                        copy_fields: 0
                    };
                    
                    this.schema_fields.forEach(field => {
                        const fieldName = field.name.toLowerCase();
                        if (fieldName.includes('variable') || fieldName.startsWith('var_')) {
                            stats.variable_fields++;
                        } else if (fieldName.includes('survey') || fieldName.includes('study') || 
                                   ['title', 'nation', 'idno', 'year_start', 'year_end', 'years', 
                                    'dataset_type', 'repositories', 'countries', 'regions', 
                                    'methodology', 'keywords', 'authoring_entity', 'abstract', 
                                    'doctype', 'published', 'varcount'].includes(fieldName)) {
                            stats.survey_fields++;
                        } else if (fieldName.includes('copy') || fieldName.includes('_copy')) {
                            stats.copy_fields++;
                        }
                    });
                    
                    return stats;
                },
                
                // Enhanced Sidebar Methods
                getSyncPercentage: function(type) {
                    if (!this.index_counts.data || !this.db_counts.data) {
                        return 0;
                    }
                    
                    const indexCount = this.index_counts.data.result[type] || 0;
                    const dbCount = this.db_counts.data.result[type] || 0;
                    
                    if (dbCount === 0) {
                        return 100;
                    }
                    
                    return Math.round((indexCount / dbCount) * 100);
                },
                
                getSyncStatus: function(type) {
                    const percentage = this.getSyncPercentage(type);
                    
                    if (percentage >= 95) {
                        return 'text-success';
                    } else if (percentage >= 80) {
                        return 'text-warning';
                    } else {
                        return 'text-danger';
                    }
                },
                
                getIndexHealthClass: function() {
                    if (!this.ping_status.data) {
                        return 'badge-secondary';
                    }
                    
                    if (this.ping_status.data.result.status === 'OK') {
                        return 'badge-success';
                    } else {
                        return 'badge-danger';
                    }
                },
                
                getIndexHealthText: function() {
                    if (!this.ping_status.data) {
                        return 'Unknown';
                    }
                    
                    return this.ping_status.data.result.status === 'OK' ? 'Healthy' : 'Unhealthy';
                },
                
                getOverallSyncStatus: function() {
                    if (!this.index_counts.data || !this.db_counts.data) {
                        return 'badge-secondary';
                    }
                    
                    const datasetsSync = this.getSyncPercentage('datasets');
                    const variablesSync = this.getSyncPercentage('variables');
                    const citationsSync = this.getSyncPercentage('citations');
                    
                    const avgSync = (datasetsSync + variablesSync + citationsSync) / 3;
                    
                    if (avgSync >= 95) {
                        return 'badge-success';
                    } else if (avgSync >= 80) {
                        return 'badge-warning';
                    } else {
                        return 'badge-danger';
                    }
                },
                
                getOverallSyncText: function() {
                    if (!this.index_counts.data || !this.db_counts.data) {
                        return 'Unknown';
                    }
                    
                    const datasetsSync = this.getSyncPercentage('datasets');
                    const variablesSync = this.getSyncPercentage('variables');
                    const citationsSync = this.getSyncPercentage('citations');
                    
                    const avgSync = Math.round((datasetsSync + variablesSync + citationsSync) / 3);
                    
                    if (avgSync >= 95) {
                        return 'Synced';
                    } else if (avgSync >= 80) {
                        return 'Partial';
                    } else {
                        return 'Out of Sync';
                    }
                },
                
                // Core Management Methods
                checkConfiguredCore: function() {
                    this.core_check_loading = true;
                    let url = CI.base_url + '/api/solr/core_check_configured';
                    let vm = this;
                    
                    axios.get(url)
                        .then(function (response) {
                            console.log('Configured core check response:', response);
                            console.log('Response data:', response.data);
                            
                            // Handle both 'success' and 'error' status (error means not configured, not a failure)
                            if (response.data.status === 'success') {
                                vm.configured_core = response.data.configured_core || '';
                                vm.configured_core_exists = response.data.exists || false;
                                vm.configured_core_status = response.data.core_status || null;
                            } else if (response.data.status === 'error') {
                                // 'error' status means no core configured in config file
                                vm.configured_core = response.data.configured_core || '';
                                vm.configured_core_exists = false;
                                vm.configured_core_status = null;
                                // Don't show this as an error result - it's just informational
                            } else if (response.data.status === 'failed') {
                                // 'failed' status means an actual error occurred
                                vm.addCoreResult('error', 'Check Core', 'Failed to check core: ' + (response.data.message || 'Unknown error'));
                                vm.configured_core = '';
                                vm.configured_core_exists = false;
                                vm.configured_core_status = null;
                            } else {
                                vm.addCoreResult('error', 'Check Core', 'Unexpected response: ' + JSON.stringify(response.data));
                                vm.configured_core = '';
                                vm.configured_core_exists = false;
                                vm.configured_core_status = null;
                            }
                        })
                        .catch(function (error) {
                            console.error('Error checking configured core:', error);
                            console.error('Error response:', error.response);
                            vm.addCoreResult('error', 'Check Core', 'Error: ' + (error.response?.data?.message || error.message || 'Unknown error'));
                            vm.configured_core = '';
                            vm.configured_core_exists = false;
                            vm.configured_core_status = null;
                        })
                        .then(function () {
                            vm.core_check_loading = false;
                        });
                },
                getSystemInfo: function() {
                    this.system_info.loading = true;
                    let url = CI.base_url + '/api/solr/system_info';
                    let vm = this;
                    
                    axios.get(url)
                        .then(function (response) {
                            console.log('System info response:', response);
                            console.log('System info data:', response.data);
                            vm.system_info.data = response;
                        })
                        .catch(function (error) {
                            console.error('Failed to get system info:', error);
                            vm.system_info.data = null;
                        })
                        .then(function () {
                            vm.system_info.loading = false;
                        });
                },
                
                createConfiguredCore: function() {
                    if (!this.configured_core) {
                        alert('No core name configured');
                        return;
                    }
                    
                    if (!confirm('Create core "' + this.configured_core + '"? This will create a new Solr core/collection with default configuration.')) {
                        return;
                    }
                    
                    this.core_processing = true;
                    this.core_processing_message = 'Creating core: ' + this.configured_core;
                    
                    let url = CI.base_url + '/api/solr/core_create/' + encodeURIComponent(this.configured_core);
                    let vm = this;
                    
                    axios.get(url)
                        .then(function (response) {
                            console.log('Core created:', response);
                            if (response.data.status === 'success') {
                                vm.addCoreResult('success', 'Create Core', 'Core "' + vm.configured_core + '" created successfully', response.data.result);
                                // Re-check core status
                                vm.checkConfiguredCore();
                            } else {
                                vm.addCoreResult('error', 'Create Core', 'Failed to create core: ' + (response.data.message || 'Unknown error'), response.data);
                            }
                        })
                        .catch(function (error) {
                            console.error('Error creating core:', error);
                            vm.addCoreResult('error', 'Create Core', 'Error: ' + (error.response?.data?.message || error.message || 'Unknown error'), error.response?.data);
                        })
                        .then(function () {
                            vm.core_processing = false;
                            vm.core_processing_message = '';
                        });
                },
                
                addCoreResult: function(status, operation, message, details) {
                    this.core_results.unshift({
                        status: status,
                        operation: operation,
                        message: message,
                        details: details,
                        timestamp: new Date().toLocaleString()
                    });
                    
                    // Keep only last 10 results
                    if (this.core_results.length > 10) {
                        this.core_results = this.core_results.slice(0, 10);
                    }
                },
                
                formatUptime: function(uptime) {
                    if (!uptime) return 'N/A';
                    
                    // Uptime is in milliseconds
                    const seconds = Math.floor(uptime / 1000);
                    const minutes = Math.floor(seconds / 60);
                    const hours = Math.floor(minutes / 60);
                    const days = Math.floor(hours / 24);
                    
                    if (days > 0) {
                        return days + 'd ' + (hours % 24) + 'h';
                    } else if (hours > 0) {
                        return hours + 'h ' + (minutes % 60) + 'm';
                    } else if (minutes > 0) {
                        return minutes + 'm ' + (seconds % 60) + 's';
                    } else {
                        return seconds + 's';
                    }
                },
                
                formatDate: function(dateString) {
                    if (!dateString) return 'N/A';
                    try {
                        const date = new Date(dateString);
                        return date.toLocaleString();
                    } catch (e) {
                        return dateString;
                    }
                },
                formatBytes: function(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                }
            }

        })
    </script>


    
</body>

</html>

