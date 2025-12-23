<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Analytics Reports'; ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    
    <!-- Vuetify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    
    <!-- Vue.js and Axios -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
    <!-- Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <style>
        .v-list-item--active {
            background-color: rgba(25, 118, 210, 0.08) !important;
            border-left: 3px solid #1976D2;
        }
        .v-list-item--active .v-list-item__icon .v-icon {
            color: #1976D2 !important;
        }
        .v-list-item--active .v-list-item__title {
            color: #1976D2 !important;
            font-weight: 500;
        }
        .sidebar-nav {
            height: 100vh;
            overflow-y: auto;
            background-color: #f8f9fa !important;
        }
        .main-content {
            background-color: transparent !important;
        }
    </style>
</head>
<body>
    <v-app id="app">
        <v-container fluid class="pa-0">
            <v-row no-gutters>
                <!-- Sidebar Column -->
                <v-col cols="12" md="3" lg="2" class="sidebar-nav">
                    <v-card flat tile height="100vh" class="d-flex flex-column" color="#f8f9fa">
                        <v-list dense nav class="flex-grow-1" style="background:#e9ecef !important;">
                            <v-list-item class="px-2">
                                <v-list-item-content>
                                    <v-list-item-title class="title">
                                        <v-icon left color="primary">mdi-chart-bar</v-icon>
                                        Analytics
                                    </v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                            <v-divider></v-divider>
                            
                            <v-list-item
                                @click="activeView = 'overview'"
                                :class="{'v-list-item--active': activeView === 'overview'}"
                            >
                                <v-list-item-icon>
                                    <v-icon>mdi-view-dashboard</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>Overview</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                            
                            <v-list-item
                                @click="activeView = 'pageviews'"
                                :class="{'v-list-item--active': activeView === 'pageviews'}"
                            >
                                <v-list-item-icon>
                                    <v-icon>mdi-eye</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>Raw Data - Pageviews</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                            
                            <v-list-item
                                @click="activeView = 'downloads'"
                                :class="{'v-list-item--active': activeView === 'downloads'}"
                            >
                                <v-list-item-icon>
                                    <v-icon>mdi-download</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>Raw Data - Downloads</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                            
                            <v-list-item
                                @click="activeView = 'monthly'"
                                :class="{'v-list-item--active': activeView === 'monthly'}"
                            >
                                <v-list-item-icon>
                                    <v-icon>mdi-calendar-month</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>Monthly Data</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                            
                            <v-list-item
                                @click="activeView = 'aggregations'"
                                :class="{'v-list-item--active': activeView === 'aggregations'}"
                            >
                                <v-list-item-icon>
                                    <v-icon>mdi-cog</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>Aggregations</v-list-item-title>
                                </v-list-item-content>
                            </v-list-item>
                        </v-list>
                    </v-card>
                </v-col>
                
                <!-- Main Content Column -->
                <v-col cols="12" md="9" lg="10" class="main-content">
                    <v-container fluid class="pa-4">
                        <h1 class="mb-4">Analytics Reports</h1>
                
                <div v-show="activeView === 'overview'">
                        <v-card class="mt-4">
                            <v-card-title>
                                <v-icon left color="primary">mdi-view-dashboard</v-icon>
                                Overview
                            </v-card-title>
                            <v-card-text>
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <v-card>
                                            <v-card-title class="subtitle-1">Today's Statistics</v-card-title>
                                            <v-card-text>
                                                <div class="text-h4 mb-2">{{ todayStats.pageviews || 0 }}</div>
                                                <div class="text-caption text--secondary">Pageviews</div>
                                                <v-divider class="my-3"></v-divider>
                                                <div class="text-h4 mb-2">{{ todayStats.downloads || 0 }}</div>
                                                <div class="text-caption text--secondary">Downloads</div>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-card>
                                            <v-card-title class="subtitle-1">Recent Pageviews</v-card-title>
                                            <v-card-text>
                                                <v-simple-table dense v-if="recentPageviews.length > 0">
                                                    <template v-slot:default>
                                                        <thead>
                                                            <tr>
                                                                <th>Time</th>
                                                                <th>Study ID</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="item in recentPageviews.slice(0, 5)" :key="item.id">
                                                                <td>{{ formatDateTime(item.ts) }}</td>
                                                                <td>{{ item.study_id }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </template>
                                                </v-simple-table>
                                                <div v-else class="text-center text--secondary py-4">
                                                    No recent pageviews
                                                </div>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-card>
                                            <v-card-title class="subtitle-1">Recent Downloads</v-card-title>
                                            <v-card-text>
                                                <v-simple-table dense v-if="recentDownloads.length > 0">
                                                    <template v-slot:default>
                                                        <thead>
                                                            <tr>
                                                                <th>Time</th>
                                                                <th>Study ID</th>
                                                                <th>File</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="item in recentDownloads.slice(0, 5)" :key="item.id">
                                                                <td>{{ formatDateTime(item.ts) }}</td>
                                                                <td>{{ item.study_id }}</td>
                                                                <td class="text-truncate" style="max-width: 150px;">{{ item.file_name }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </template>
                                                </v-simple-table>
                                                <div v-else class="text-center text--secondary py-4">
                                                    No recent downloads
                                                </div>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                </div>
                
                <div v-show="activeView === 'pageviews'">
                        <v-card class="mt-4">
                            <v-card-title>
                                <v-icon left color="primary">mdi-eye</v-icon>
                                Raw Data - Pageviews
                            </v-card-title>
                            <v-card-text>
                                <!-- Filters -->
                                <v-row class="mb-4">
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="pageviewFilters.date_from"
                                            label="Date From"
                                            type="date"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="pageviewFilters.date_to"
                                            label="Date To"
                                            type="date"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="4" class="d-flex align-center">
                                        <v-btn color="primary" @click="loadPageviews" :loading="loadingPageviews" class="mr-2">
                                            <v-icon left>mdi-magnify</v-icon>
                                            Filter
                                        </v-btn>
                                        <v-btn @click="clearPageviewFilters" outlined>
                                            Clear
                                        </v-btn>
                                    </v-col>
                                </v-row>
                                
                                <!-- Data Table -->
                                <v-data-table
                                    :headers="pageviewHeaders"
                                    :items="pageviewData"
                                    :loading="loadingPageviews"
                                    :items-per-page="pageviewPagination.limit"
                                    :server-items-length="pageviewPagination.total"
                                    :page="pageviewPagination.page"
                                    @update:page="onPageviewPageChange"
                                    class="elevation-1"
                                    no-data-text="No pageview data found"
                                >
                                    <template v-slot:item.ts="{ item }">
                                        {{ formatDateTime(item.ts) }}
                                    </template>
                                    <template v-slot:item.session_id="{ item }">
                                        <span v-if="item.session_id" class="font-monospace" style="font-size: 0.85em;">
                                            {{ item.session_id.substring(0, 16) }}...
                                        </span>
                                        <span v-else class="text--secondary">-</span>
                                    </template>
                                    <template v-slot:item.user_agent="{ item }">
                                        <span v-if="item.user_agent" :title="item.user_agent" style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ item.user_agent }}
                                        </span>
                                        <span v-else class="text--secondary">-</span>
                                    </template>
                                </v-data-table>
                            </v-card-text>
                        </v-card>
                </div>
                
                <div v-show="activeView === 'downloads'">
                        <v-card class="mt-4">
                            <v-card-title>
                                <v-icon left color="primary">mdi-download</v-icon>
                                Raw Data - Downloads
                            </v-card-title>
                            <v-card-text>
                                <!-- Filters -->
                                <v-row class="mb-4">
                                    <v-col cols="12" md="3">
                                        <v-text-field
                                            v-model="downloadFilters.date_from"
                                            label="Date From"
                                            type="date"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <v-text-field
                                            v-model="downloadFilters.date_to"
                                            label="Date To"
                                            type="date"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <v-text-field
                                            v-model="downloadFilters.file_type"
                                            label="File Type"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="3" class="d-flex align-center">
                                        <v-btn color="primary" @click="loadDownloads" :loading="loadingDownloads" class="mr-2">
                                            <v-icon left>mdi-magnify</v-icon>
                                            Filter
                                        </v-btn>
                                        <v-btn @click="clearDownloadFilters" outlined>
                                            Clear
                                        </v-btn>
                                    </v-col>
                                </v-row>
                                
                                <!-- Data Table -->
                                <v-data-table
                                    :headers="downloadHeaders"
                                    :items="downloadData"
                                    :loading="loadingDownloads"
                                    :items-per-page="downloadPagination.limit"
                                    :server-items-length="downloadPagination.total"
                                    :page="downloadPagination.page"
                                    @update:page="onDownloadPageChange"
                                    class="elevation-1"
                                    no-data-text="No download data found"
                                >
                                    <template v-slot:item.ts="{ item }">
                                        {{ formatDateTime(item.ts) }}
                                    </template>
                                    <template v-slot:item.file_name="{ item }">
                                        <span :title="item.file_name" style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ item.file_name }}
                                        </span>
                                    </template>
                                </v-data-table>
                            </v-card-text>
                        </v-card>
                </div>
                
                <div v-show="activeView === 'monthly'">
                        <v-card class="mt-4">
                            <v-card-title>
                                <v-icon left color="primary">mdi-calendar-month</v-icon>
                                Monthly Data
                            </v-card-title>
                            <v-card-text>
                                <!-- Monthly Traffic Line Chart -->
                                <v-row class="mb-6">
                                    <v-col cols="12">
                                        <div class="d-flex align-center justify-space-between mb-2">
                                            <div>
                                                <h3 class="mb-0">Monthly Traffic</h3>
                                                <small class="grey--text">Pageviews, Unique Visitors, Downloads</small>
                                            </div>
                                            <div class="d-flex align-center">
                                                <v-select
                                                    v-model="monthlyChartMonths"
                                                    :items="monthlyChartMonthOptions"
                                                    label="Period"
                                                    dense
                                                    outlined
                                                    hide-details
                                                    style="max-width: 150px;"
                                                    class="mr-2"
                                                    @change="loadMonthlyTrafficChart"
                                                ></v-select>
                                                <v-btn small color="primary" :loading="monthlyChartLoading" @click="loadMonthlyTrafficChart">
                                                    <v-icon left>mdi-refresh</v-icon>
                                                    Refresh
                                                </v-btn>
                                            </div>
                                        </div>
                                        <div style="position:relative; height:360px;">
                                            <canvas id="monthlyLineChart"></canvas>
                                        </div>
                                    </v-col>
                                </v-row>
                                <!-- Filters -->
                                <v-row class="mb-4">
                                    <v-col cols="12" md="3">
                                        <v-text-field
                                            v-model.number="monthlyFilters.year"
                                            label="Year"
                                            type="number"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <v-select
                                            v-model.number="monthlyFilters.month"
                                            :items="monthOptions"
                                            label="Month"
                                            outlined
                                            dense
                                            clearable
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <v-select
                                            v-model="monthlyDataView"
                                            :items="[{text: 'Studies', value: 'studies'}, {text: 'Files', value: 'files'}]"
                                            label="View"
                                            outlined
                                            dense
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12" md="3" >
                                        <v-btn color="primary" @click="loadMonthlyData" :loading="loadingMonthly" class="mr-2">
                                            <v-icon left>mdi-magnify</v-icon>
                                            Filter
                                        </v-btn>
                                        <v-btn @click="clearMonthlyFilters" outlined>
                                            Clear
                                        </v-btn>
                                    </v-col>
                                </v-row>
                                
                                <!-- Studies Table -->
                                <v-data-table
                                    v-if="monthlyDataView === 'studies'"
                                    :headers="monthlyStudiesHeaders"
                                    :items="monthlyStudiesData"
                                    :loading="loadingMonthly"
                                    :items-per-page="monthlyPagination.limit"
                                    :server-items-length="monthlyPagination.total"
                                    :page="monthlyPagination.page"
                                    @update:page="onMonthlyPageChange"
                                    server-side-pagination
                                    disable-sort
                                    hide-default-footer
                                    class="elevation-1"
                                    no-data-text="No monthly data found"
                                >
                                    <template v-slot:item.title="{ item }">
                                        <div>
                                            <div class="font-weight-medium">{{ item.title || 'Untitled' }}</div>
                                            <div class="text-caption text--secondary">
                                                <span v-if="item.nation">{{ item.nation }}, </span>{{ item.study_year || 'N/A' }}
                                            </div>
                                        </div>
                                    </template>
                                    <template v-slot:item.finalized="{ item }">                                        
                                            <v-icon left small>{{ item.finalized==1 ? 'mdi-check-circle' : 'mdi-clock-outline' }}</v-icon>                                                                                    
                                    </template>
                                </v-data-table>
                                
                                <!-- Custom Pagination for Studies -->
                                <div v-if="monthlyDataView === 'studies'" class="text-center mt-3">
                                    <v-pagination
                                        v-model="monthlyPagination.page"
                                        :length="Math.ceil(monthlyPagination.total / monthlyPagination.limit)"
                                        :total-visible="7"
                                        @input="onMonthlyPageChange"
                                        color="primary"
                                    ></v-pagination>
                                    <div class="text-caption text--secondary mt-2">
                                        Showing {{ ((monthlyPagination.page - 1) * monthlyPagination.limit) + 1 }}-{{ Math.min(monthlyPagination.page * monthlyPagination.limit, monthlyPagination.total) }} of {{ monthlyPagination.total }} items
                                    </div>
                                </div>
                                
                                <!-- Files Table -->
                                <v-data-table
                                    v-else
                                    :headers="monthlyFilesHeaders"
                                    :items="monthlyFilesData"
                                    :loading="loadingMonthly"
                                    :items-per-page="monthlyPagination.limit"
                                    :server-items-length="monthlyPagination.total"
                                    :page="monthlyPagination.page"
                                    @update:page="onMonthlyPageChange"
                                    hide-default-footer
                                    class="elevation-1"
                                    no-data-text="No monthly file data found"
                                >
                                    <template v-slot:item.title="{ item }">
                                        <div>
                                            <div class="font-weight-medium">{{ item.title || 'Untitled' }}</div>
                                            <div class="text-caption text--secondary">
                                                <span v-if="item.nation">{{ item.nation }}, </span>{{ item.study_year || 'N/A' }}
                                            </div>
                                        </div>
                                    </template>
                                    <template v-slot:item.finalized="{ item }">
                                        <v-icon left small>{{ item.finalized==1 ? 'mdi-check-circle' : 'mdi-clock-outline' }}</v-icon>
                                    </template>
                                </v-data-table>
                                
                                <!-- Custom Pagination for Files -->
                                <div v-if="monthlyDataView === 'files'" class="text-center mt-3">
                                    <v-pagination
                                        v-model="monthlyPagination.page"
                                        :length="Math.ceil(monthlyPagination.total / monthlyPagination.limit)"
                                        :total-visible="7"
                                        @input="onMonthlyPageChange"
                                        color="primary"
                                    ></v-pagination>
                                    <div class="text-caption text--secondary mt-2">
                                        Showing {{ ((monthlyPagination.page - 1) * monthlyPagination.limit) + 1 }}-{{ Math.min(monthlyPagination.page * monthlyPagination.limit, monthlyPagination.total) }} of {{ monthlyPagination.total }} items
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>
                </div>
                
                <div v-show="activeView === 'aggregations'">
                        <v-card class="mt-4">
                            <v-card-title>
                                <v-icon left color="primary">mdi-cog</v-icon>
                                Data Aggregations
                            </v-card-title>
                            <v-card-text>
                                <v-alert type="info" class="mb-4">
                                    Run aggregations to process raw event data into daily, monthly, and all-time totals.
                                    Processing is done in batches to avoid database timeouts.
                                </v-alert>
                                
                                <!-- Aggregation Controls -->
                                <v-card class="mb-4">
                                    <v-card-title class="subtitle-1">Quick Actions</v-card-title>
                                    <v-card-text>
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-btn 
                                                    color="primary" 
                                                    large 
                                                    block 
                                                    @click="runFullAggregation"
                                                    :loading="aggregationRunning"
                                                    :disabled="aggregationRunning"
                                                    class="mb-2"
                                                >
                                                    <v-icon left>mdi-play-circle</v-icon>
                                                    Run Full Aggregation
                                                </v-btn>
                                                <div class="text-caption text--secondary text-center">
                                                    Auto-detects missing dates and processes all steps
                                                </div>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-btn 
                                                    color="secondary" 
                                                    large 
                                                    block 
                                                    @click="stopAggregation"
                                                    :disabled="!aggregationRunning"
                                                    class="mb-2"
                                                >
                                                    <v-icon left>mdi-stop-circle</v-icon>
                                                    Stop Aggregation
                                                </v-btn>
                                                <div class="text-caption text--secondary text-center">
                                                    Cancel current aggregation process
                                                </div>
                                            </v-col>
                                        </v-row>
                                    </v-card-text>
                                </v-card>
                                
                                
                                <!-- Progress/Status -->
                                <v-card v-if="aggregationStatus">
                                    <v-card-title class="subtitle-1">
                                        <v-icon left :color="getStatusColor(aggregationStatus.type)">{{ getStatusIcon(aggregationStatus.type) }}</v-icon>
                                        {{ aggregationStatus.title }}
                                    </v-card-title>
                                    <v-card-text>
                                        <p>{{ aggregationStatus.message }}</p>
                                        
                                        <v-progress-linear
                                            v-if="aggregationStatus.progress !== undefined"
                                            :value="aggregationStatus.progress"
                                            color="primary"
                                            height="25"
                                            class="mb-2"
                                        >
                                            <strong>{{ aggregationStatus.progress }}%</strong>
                                        </v-progress-linear>
                                        
                                        <div v-if="aggregationStatus.details" class="text-caption text--secondary">
                                            {{ aggregationStatus.details }}
                                        </div>
                                        
                                        <v-alert
                                            v-if="aggregationStatus.errors && aggregationStatus.errors.length > 0"
                                            type="error"
                                            class="mt-3"
                                        >
                                            <div v-for="(error, idx) in aggregationStatus.errors" :key="idx">
                                                {{ error }}
                                            </div>
                                        </v-alert>
                                    </v-card-text>
                                </v-card>
                            </v-card-text>
                        </v-card>
                    </div>
                </v-container>
            </v-col>
        </v-row>
    </v-container>
</v-app>

    <script>
        const apiBase = '<?php echo site_url("api/analytics"); ?>';
        const baseUrl = '<?php echo base_url(); ?>';
        
        new Vue({
            el: '#app',
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
            }),
            data: {
                drawer: true,
                activeView: 'overview',
                
                // Overview
                todayStats: { pageviews: 0, downloads: 0 },
                recentPageviews: [],
                recentDownloads: [],
                
                // Pageviews
                pageviewHeaders: [
                    { text: 'Time', value: 'ts', sortable: true },
                    { text: 'Study ID', value: 'study_id', sortable: true },
                    { text: 'Session ID', value: 'session_id', sortable: false },
                    { text: 'User Agent', value: 'user_agent', sortable: false },
                    { text: 'Referrer', value: 'referrer', sortable: false }
                ],
                pageviewData: [],
                loadingPageviews: false,
                pageviewFilters: {
                    date_from: null,
                    date_to: null
                },
                pageviewPagination: {
                    page: 1,
                    limit: 50,
                    total: 0
                },
                
                // Downloads
                downloadHeaders: [
                    { text: 'Time', value: 'ts', sortable: true },
                    { text: 'Study ID', value: 'study_id', sortable: true },
                    { text: 'File Name', value: 'file_name', sortable: true },
                    { text: 'File Type', value: 'file_type', sortable: true },
                    { text: 'User Agent', value: 'user_agent', sortable: false }
                ],
                downloadData: [],
                loadingDownloads: false,
                downloadFilters: {
                    date_from: null,
                    date_to: null,
                    file_type: null
                },
                downloadPagination: {
                    page: 1,
                    limit: 50,
                    total: 0
                },
                
                // Monthly Data
                monthlyDataView: 'studies',
                monthlyStudiesHeaders: [
                    { text: 'ID', value: 'study_id', sortable: false },
                    { text: 'Title', value: 'title', sortable: false },
                    { text: 'Year', value: 'year', sortable: true },
                    { text: 'Month', value: 'month', sortable: true },                    
                    { text: 'Pageviews', value: 'pageviews', sortable: true },
                    { text: 'Unique Visitors', value: 'unique_visitors', sortable: true },
                    { text: 'Downloads', value: 'downloads', sortable: true },
                    { text: 'Status', value: 'finalized', sortable: true }
                ],
                monthlyFilesHeaders: [
                    { text: 'Study ID', value: 'study_id', sortable: true },
                    { text: 'Title', value: 'title', sortable: false },
                    { text: 'Year', value: 'year', sortable: true },
                    { text: 'Month', value: 'month', sortable: true },                    
                    { text: 'File Name', value: 'file_name', sortable: true },
                    { text: 'Downloads', value: 'downloads', sortable: true },
                    { text: 'Status', value: 'finalized', sortable: true }
                ],
                monthlyStudiesData: [],
                monthlyFilesData: [],
                loadingMonthly: false,
                monthlyFilters: {
                    year: null,
                    month: null
                },
                monthlyPagination: {
                    page: 1,
                    limit: 50,
                    total: 0
                },
                // Monthly Traffic Chart
                monthlyChart: null,
                monthlyChartLoading: false,
                monthlyChartMonths: 3,
                monthlyChartMonthOptions: [
                    { text: 'Last 3 months', value: 3 },
                    { text: 'Last 6 months', value: 6 },
                    { text: 'Last 12 months', value: 12 },
                    { text: 'Last 24 months', value: 24 }
                ],
                monthlyChartData: {
                    labels: [],
                    pageviews: [],
                    unique_visitors: [],
                    downloads: []
                },
                
                // Aggregations
                aggregationRunning: false,
                aggregationStatus: null,
                aggregationYear: new Date().getFullYear(),
                aggregationMonth: new Date().getMonth() + 1,
                aggregationStopRequested: false,
                
                // Common
                monthOptions: [
                    { text: 'January', value: 1 },
                    { text: 'February', value: 2 },
                    { text: 'March', value: 3 },
                    { text: 'April', value: 4 },
                    { text: 'May', value: 5 },
                    { text: 'June', value: 6 },
                    { text: 'July', value: 7 },
                    { text: 'August', value: 8 },
                    { text: 'September', value: 9 },
                    { text: 'October', value: 10 },
                    { text: 'November', value: 11 },
                    { text: 'December', value: 12 }
                ]
            },
            mounted() {
                this.loadOverview();
                this.loadAggregationStatus();
            },
            watch: {
                activeView(newView) {
                    if (newView === 'pageviews' && this.pageviewData.length === 0) {
                        this.loadPageviews();
                    } else if (newView === 'downloads' && this.downloadData.length === 0) {
                        this.loadDownloads();
                    } else if (newView === 'monthly') {
                        if (this.monthlyStudiesData.length === 0) {
                            this.loadMonthlyData();
                        }
                        if (!this.monthlyChart) {
                            this.loadMonthlyTrafficChart();
                        }
                    }
                }
            },
            methods: {
                async loadOverview() {
                    // Load recent pageviews
                    try {
                        const pvResponse = await axios.get(apiBase + '/raw/pageviews', {
                            params: { limit: 10, offset: 0 }
                        });
                        if (pvResponse.data.status === 'success') {
                            this.recentPageviews = pvResponse.data.data || [];
                            this.todayStats.pageviews = pvResponse.data.total || 0;
                        }
                    } catch (error) {
                        console.error('Error loading pageviews:', error);
                    }
                    
                    // Load recent downloads
                    try {
                        const dlResponse = await axios.get(apiBase + '/raw/downloads', {
                            params: { limit: 10, offset: 0 }
                        });
                        if (dlResponse.data.status === 'success') {
                            this.recentDownloads = dlResponse.data.data || [];
                            this.todayStats.downloads = dlResponse.data.total || 0;
                        }
                    } catch (error) {
                        console.error('Error loading downloads:', error);
                    }
                },
                
                async loadPageviews() {
                    this.loadingPageviews = true;
                    try {
                        const params = {
                            limit: this.pageviewPagination.limit,
                            offset: (this.pageviewPagination.page - 1) * this.pageviewPagination.limit,
                            sort_by: 'ts',
                            sort_order: 'desc'
                        };
                        
                        if (this.pageviewFilters.date_from) params.date_from = this.pageviewFilters.date_from;
                        if (this.pageviewFilters.date_to) params.date_to = this.pageviewFilters.date_to;
                        
                        const response = await axios.get(apiBase + '/raw/pageviews', { params });
                        
                        if (response.data.status === 'success') {
                            this.pageviewData = response.data.data || [];
                            this.pageviewPagination.total = response.data.total || 0;
                        } else {
                            alert('Error loading pageviews: ' + (response.data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error loading pageviews:', error);
                        alert('Error loading pageviews: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.loadingPageviews = false;
                    }
                },
                
                onPageviewPageChange(page) {
                    this.pageviewPagination.page = page;
                    this.loadPageviews();
                },
                
                clearPageviewFilters() {
                    this.pageviewFilters = { date_from: null, date_to: null };
                    this.pageviewPagination.page = 1;
                    this.loadPageviews();
                },
                
                async loadDownloads() {
                    this.loadingDownloads = true;
                    try {
                        const params = {
                            limit: this.downloadPagination.limit,
                            offset: (this.downloadPagination.page - 1) * this.downloadPagination.limit,
                            sort_by: 'ts',
                            sort_order: 'desc'
                        };
                        
                        if (this.downloadFilters.date_from) params.date_from = this.downloadFilters.date_from;
                        if (this.downloadFilters.date_to) params.date_to = this.downloadFilters.date_to;
                        if (this.downloadFilters.file_type) params.file_type = this.downloadFilters.file_type;
                        
                        const response = await axios.get(apiBase + '/raw/downloads', { params });
                        
                        if (response.data.status === 'success') {
                            this.downloadData = response.data.data || [];
                            this.downloadPagination.total = response.data.total || 0;
                        } else {
                            alert('Error loading downloads: ' + (response.data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error loading downloads:', error);
                        alert('Error loading downloads: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.loadingDownloads = false;
                    }
                },
                
                onDownloadPageChange(page) {
                    this.downloadPagination.page = page;
                    this.loadDownloads();
                },
                
                clearDownloadFilters() {
                    this.downloadFilters = { date_from: null, date_to: null, file_type: null };
                    this.downloadPagination.page = 1;
                    this.loadDownloads();
                },
                
                async loadMonthlyData() {
                    this.loadingMonthly = true;
                    try {
                        const params = {
                            limit: this.monthlyPagination.limit,
                            offset: (this.monthlyPagination.page - 1) * this.monthlyPagination.limit
                        };
                        
                        if (this.monthlyFilters.year) params.year = this.monthlyFilters.year;
                        if (this.monthlyFilters.month) params.month = this.monthlyFilters.month;
                        
                        const endpoint = this.monthlyDataView === 'studies' ? '/monthly/studies' : '/monthly/files';
                        const response = await axios.get(apiBase + endpoint, { params });
                        
                        if (response.data.status === 'success') {
                            if (this.monthlyDataView === 'studies') {
                                this.monthlyStudiesData = response.data.data || [];
                            } else {
                                this.monthlyFilesData = response.data.data || [];
                            }
                            this.monthlyPagination.total = response.data.total || 0;
                        } else {
                            alert('Error loading monthly data: ' + (response.data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error loading monthly data:', error);
                        alert('Error loading monthly data: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.loadingMonthly = false;
                    }
                },
                
                onMonthlyPageChange(page) {
                    this.monthlyPagination.page = page;
                    this.loadMonthlyData();
                },
                
                clearMonthlyFilters() {
                    this.monthlyFilters = { year: null, month: null };
                    this.monthlyPagination.page = 1;
                    this.loadMonthlyData();
                },

                // Fetch totals for last N months and render line chart
                async loadMonthlyTrafficChart() {
                    this.monthlyChartLoading = true;
                    try {
                        const resp = await axios.get(`${apiBase}/monthly/totals`, {
                            params: { months: this.monthlyChartMonths }
                        });
                        
                        if (resp.data.status === 'success') {
                            const data = resp.data.data || [];
                            const labels = data.map(m => m.label);
                            const pageviews = data.map(m => m.pageviews);
                            const uniqueVisitors = data.map(m => m.unique_visitors);
                            const downloads = data.map(m => m.downloads);
                            
                            this.monthlyChartData = { labels, pageviews, unique_visitors: uniqueVisitors, downloads };
                            this.renderMonthlyLineChart();
                        } else {
                            throw new Error(resp.data.message || 'Failed to load monthly totals');
                        }
                    } catch (error) {
                        console.error('Monthly chart error:', error);
                        alert('Error loading monthly chart: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.monthlyChartLoading = false;
                    }
                },

                // Create or update the line chart
                renderMonthlyLineChart() {
                    const ctx = document.getElementById('monthlyLineChart');
                    if (!ctx) return;

                    if (this.monthlyChart) {
                        this.monthlyChart.destroy();
                        this.monthlyChart = null;
                    }

                    const { labels, pageviews, unique_visitors, downloads } = this.monthlyChartData;

                    this.monthlyChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Pageviews',
                                    data: pageviews,
                                    borderColor: '#1976D2',
                                    backgroundColor: 'rgba(25,118,210,0.12)',
                                    fill: true,
                                    tension: 0.25,
                                    pointRadius: 2
                                },
                                {
                                    label: 'Unique Visitors',
                                    data: unique_visitors,
                                    borderColor: '#43A047',
                                    backgroundColor: 'rgba(67,160,71,0.12)',
                                    fill: true,
                                    tension: 0.25,
                                    pointRadius: 2
                                },
                                {
                                    label: 'Downloads',
                                    data: downloads,
                                    borderColor: '#FF7043',
                                    backgroundColor: 'rgba(255,112,67,0.12)',
                                    fill: true,
                                    tension: 0.25,
                                    pointRadius: 2
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { position: 'top' },
                                tooltip: { enabled: true }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { grid: { color: 'rgba(0,0,0,0.06)' }, beginAtZero: true }
                            }
                        }
                    });
                },
                
                async runFullAggregation() {
                    if (!confirm('This will run the full aggregation process. This may take several minutes. Continue?')) {
                        return;
                    }
                    
                    this.aggregationRunning = true;
                    this.aggregationStopRequested = false;
                    this.aggregationStatus = {
                        type: 'info',
                        title: 'Running Aggregation',
                        message: 'Starting aggregation process...',
                        progress: 0
                    };
                    
                    // Polling loop
                    const pollAggregation = async () => {
                        if (this.aggregationStopRequested) {
                            try {
                                await axios.post(apiBase + '/aggregate/stop');
                            } catch (e) {
                                console.error('Error stopping aggregation:', e);
                            }
                            this.aggregationStatus = {
                                type: 'warning',
                                title: 'Stopped',
                                message: 'Aggregation was stopped by user'
                            };
                            this.aggregationRunning = false;
                            return;
                        }
                        
                        try {
                            const response = await axios.post(apiBase + '/aggregate/run');
                            
                            if (response.data.status === 'error') {
                                this.aggregationStatus = {
                                    type: 'error',
                                    title: 'Error',
                                    message: response.data.message || 'Aggregation failed'
                                };
                                this.aggregationRunning = false;
                                return;
                            }
                            
                            // Update status
                            this.aggregationStatus = {
                                type: 'info',
                                title: 'Processing',
                                message: response.data.message || 'Processing...',
                                progress: response.data.progress || 0,
                                details: `Step: ${response.data.current_step || 'N/A'}, Item: ${response.data.current_item || 'N/A'}`
                            };
                            
                            // Check if done
                            if (!response.data.has_more) {
                                this.aggregationStatus = {
                                    type: 'success',
                                    title: 'Completed',
                                    message: response.data.message || 'Aggregation completed successfully!',
                                    progress: 100
                                };
                                this.aggregationRunning = false;
                                this.loadOverview();
                                return;
                            }
                            
                            // Continue polling
                            setTimeout(pollAggregation, 1000);
                            
                        } catch (error) {
                            this.aggregationStatus = {
                                type: 'error',
                                title: 'Error',
                                message: error.response?.data?.message || error.message || 'Aggregation failed'
                            };
                            this.aggregationRunning = false;
                        }
                    };
                    
                    // Start polling
                    pollAggregation();
                },
                
                async stopAggregation() {
                    this.aggregationStopRequested = true;
                    try {
                        await axios.post(apiBase + '/aggregate/stop');
                    } catch (error) {
                        console.error('Error stopping aggregation:', error);
                    }
                },
                
                async loadAggregationStatus() {
                    try {
                        const response = await axios.get(apiBase + '/aggregate/status');
                        if (response.data.status === 'success') {
                            const status = response.data.data;
                            if (status.status === 'running') {
                                this.aggregationRunning = true;
                                this.aggregationStatus = {
                                    type: 'info',
                                    title: 'Running',
                                    message: status.message || 'Processing...',
                                    progress: status.progress_percent || 0,
                                    details: `Step: ${status.current_step || 'N/A'}, Item: ${status.current_item || 'N/A'}`
                                };
                            } else if (status.status === 'completed') {
                                this.aggregationStatus = {
                                    type: 'success',
                                    title: 'Completed',
                                    message: status.message || 'Completed',
                                    progress: 100
                                };
                            } else if (status.status === 'failed') {
                                this.aggregationStatus = {
                                    type: 'error',
                                    title: 'Failed',
                                    message: status.error_message || status.message || 'Failed'
                                };
                            }
                        }
                    } catch (error) {
                        console.error('Error loading aggregation status:', error);
                    }
                },
                
                // Legacy methods - now just call runFullAggregation
                async runDailyAggregation() {
                    alert('Use "Run Aggregations" button for unified processing');
                },
                
                async runMonthlyAggregation() {
                    alert('Use "Run Aggregations" button for unified processing');
                },
                
                async runUpdateTotals() {
                    alert('Use "Run Aggregations" button for unified processing');
                },
                
                formatDateTime(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    return date.toLocaleString();
                },
                
                getStatusColor(type) {
                    const colors = {
                        'info': 'info',
                        'success': 'success',
                        'warning': 'warning',
                        'error': 'error'
                    };
                    return colors[type] || 'info';
                },
                
                getStatusIcon(type) {
                    const icons = {
                        'info': 'mdi-information',
                        'success': 'mdi-check-circle',
                        'warning': 'mdi-alert',
                        'error': 'mdi-alert-circle'
                    };
                    return icons[type] || 'mdi-information';
                }
            }
        });
    </script>
</body>
</html>
