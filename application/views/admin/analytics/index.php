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
                                @click="activeView = 'daily'"
                                :class="{'v-list-item--active': activeView === 'daily'}"
                            >
                                <v-list-item-icon>
                                    <v-icon>mdi-calendar-today</v-icon>
                                </v-list-item-icon>
                                <v-list-item-content>
                                    <v-list-item-title>Daily Data</v-list-item-title>
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
                                    <v-col cols="12" md="6">
                                        <v-card>
                                            <v-card-text>
                                                <div class="text-h4 mb-2">{{ todayStats.pageviews || 0 }}</div>
                                                <div class="text-caption text--secondary">Pageviews today</div>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-card>
                                            <v-card-text>
                                                <div class="text-h4 mb-2">{{ todayStats.downloads || 0 }}</div>
                                                <div class="text-caption text--secondary">Downloads today</div>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                    <!-- break -->
                                    <v-col cols="12" md="12">
                                        <v-divider class="my-3"></v-divider>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-card>
                                            <v-card-title class="subtitle-1">Recent Pageviews</v-card-title>
                                            <v-card-text>
                                                <v-simple-table dense v-if="recentPageviews.length > 0">
                                                    <template v-slot:default>
                                                        <thead>
                                                            <tr>
                                                                <th>Time</th>
                                                                <th>Study</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="item in recentPageviews.slice(0, 5)" :key="item.id">
                                                                <td>{{ formatDateTime(item.ts) }}</td>                                                                
                                                                <td><a target="_blank" :href="site_url + '/catalog/' + item.study_id">{{ item.study_title }}</a></td>
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
                                    <v-col cols="12" md="6">
                                        <v-card>
                                            <v-card-title class="subtitle-1">Recent Downloads</v-card-title>
                                            <v-card-text>
                                                <v-simple-table dense v-if="recentDownloads.length > 0">
                                                    <template v-slot:default>
                                                        <thead>
                                                            <tr>
                                                                <th>Time</th>
                                                                <th>Study</th>
                                                                <th>File</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="item in recentDownloads.slice(0, 5)" :key="item.id">
                                                                <td>{{ formatDateTime(item.ts) }}</td>
                                                                <td><a target="_blank" :href="site_url + '/catalog/' + item.study_id">{{ item.study_title }}</a></td>
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
                    <raw-pageviews></raw-pageviews>
                </div>
                
                <div v-show="activeView === 'downloads'">
                    <raw-downloads></raw-downloads>
                </div>
                
                <div v-show="activeView === 'daily'">
                        <v-card class="mt-4">
                            <v-card-title>
                                <v-icon left color="primary">mdi-calendar-today</v-icon>
                                Daily Data
                            </v-card-title>
                            <v-card-text>
                                <v-row class="mb-4">
                                    <v-col cols="12" md="3">
                                        <v-text-field
                                            v-model="dailyFilters.date_from"
                                            label="Date From"
                                            type="date"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <v-text-field
                                            v-model="dailyFilters.date_to"
                                            label="Date To"
                                            type="date"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="2">
                                        <v-text-field
                                            v-model="dailyFilters.study_id"
                                            label="Study ID"
                                            type="number"
                                            outlined
                                            dense
                                            clearable
                                            placeholder="Optional"
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="2">
                                        <v-select
                                            v-model="dailyDataView"
                                            :items="[{text: 'Studies', value: 'studies'}, {text: 'Files', value: 'files'}]"
                                            label="View"
                                            outlined
                                            dense
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12" md="2" class="d-flex">
                                        <v-btn color="primary" @click="loadDailyData" :loading="loadingDaily" class="mr-2">
                                            <v-icon left>mdi-magnify</v-icon>
                                            Filter
                                        </v-btn>
                                        <v-btn @click="clearDailyFilters" outlined>Clear</v-btn>
                                    </v-col>
                                </v-row>
                                <v-data-table
                                    v-if="dailyDataView === 'studies'"
                                    :headers="dailyStudiesHeaders"
                                    :items="dailyStudiesData"
                                    :loading="loadingDaily"
                                    :items-per-page="dailyPagination.limit"
                                    :server-items-length="dailyPagination.total"
                                    :page="dailyPagination.page"
                                    @update:page="onDailyPageChange"
                                    hide-default-footer
                                    class="elevation-1"
                                    no-data-text="No daily data found. Use filters and click Filter."
                                >
                                    <template v-slot:item.title="{ item }">
                                        <div>
                                            <div class="font-weight-medium">{{ item.title || 'Untitled' }}</div>
                                            <div class="text-caption text--secondary">
                                                <span v-if="item.nation">{{ item.nation }}, </span>{{ item.study_year || 'N/A' }}
                                            </div>
                                        </div>
                                    </template>
                                </v-data-table>
                                <v-data-table
                                    v-else
                                    :headers="dailyFilesHeaders"
                                    :items="dailyFilesData"
                                    :loading="loadingDaily"
                                    :items-per-page="dailyPagination.limit"
                                    :server-items-length="dailyPagination.total"
                                    :page="dailyPagination.page"
                                    @update:page="onDailyPageChange"
                                    hide-default-footer
                                    class="elevation-1"
                                    no-data-text="No daily file data found. Use filters and click Filter."
                                >
                                    <template v-slot:item.title="{ item }">
                                        <div>
                                            <div class="font-weight-medium">{{ item.title || 'Untitled' }}</div>
                                            <div class="text-caption text--secondary">
                                                <span v-if="item.nation">{{ item.nation }}, </span>{{ item.study_year || 'N/A' }}
                                            </div>
                                        </div>
                                    </template>
                                    <template v-slot:item.file_name="{ item }">
                                        <span :title="item.file_name" style="max-width: 200px; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ item.file_name }}</span>
                                    </template>
                                </v-data-table>
                                <div class="text-center mt-3">
                                    <v-pagination
                                        v-model="dailyPagination.page"
                                        :length="Math.max(1, Math.ceil(dailyPagination.total / dailyPagination.limit))"
                                        :total-visible="7"
                                        @input="onDailyPageChange"
                                        color="primary"
                                    ></v-pagination>
                                    <div class="text-caption text--secondary mt-2">
                                        Showing {{ ((dailyPagination.page - 1) * dailyPagination.limit) + 1 }}-{{ Math.min(dailyPagination.page * dailyPagination.limit, dailyPagination.total) }} of {{ dailyPagination.total }} items
                                    </div>
                                </div>
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
                                                <v-btn color="primary" :loading="monthlyChartLoading" @click="loadMonthlyTrafficChart">
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
                                    <v-col cols="12" md="2">
                                        <v-text-field
                                            v-model.number="monthlyFilters.year"
                                            label="Year"
                                            type="number"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="2">
                                        <v-select
                                            v-model.number="monthlyFilters.month"
                                            :items="monthOptions"
                                            label="Month"
                                            outlined
                                            dense
                                            clearable
                                        ></v-select>
                                    </v-col>
                                    <v-col cols="12" md="2">
                                        <v-text-field
                                            v-model.number="monthlyFilters.study_id"
                                            label="Study ID"
                                            type="number"
                                            outlined
                                            dense
                                            clearable
                                        ></v-text-field>
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
                                    <v-col cols="12" md="3">
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
                                    <strong>How aggregation works</strong>
                                    <ul class="mt-2 mb-0 pl-4" style="line-height: 1.6;">
                                        <li><strong>Daily:</strong> Fills missing daily aggregates from raw events (pageviews and downloads), up to yesterday. Dates in already-finalized months are skipped.</li>
                                        <li><strong>Monthly:</strong> Rolls daily data into monthly totals for every month that has daily data but no monthly rows yet (oldest first). Handles backlogs (e.g. years of data).</li>
                                        <li><strong>Month-end:</strong> Finalizes past months (marks them complete) and removes their daily rows to save space. Past months are processed oldest first.</li>
                                        <li><strong>Cleanup:</strong> Deletes raw events older than 60 days.</li>
                                        <li><strong>Sync:</strong> Updates study view/download counters on the catalog.</li>
                                    </ul>
                                    <div class="mt-2 text-caption">Processing runs one step per click; keep the page open and wait for each step to finish. You can stop at any time.</div>
                                </v-alert>
                                
                                <div class="mb-4 text-body-2">
                                    <v-icon small class="mr-1">mdi-clock-outline</v-icon>
                                    <strong>Last successful run:</strong>
                                    <span v-if="lastCompletedAt">{{ formatDateTime(lastCompletedAt) }}</span>
                                    <span v-else class="text--secondary">Never</span>
                                </div>
                                
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

                                <!-- Cron job instructions -->
                                <v-card class="mt-4" outlined>
                                    <v-card-title class="subtitle-1">
                                        <v-icon left>mdi-clock-outline</v-icon>
                                        Run aggregation via cron
                                    </v-card-title>
                                    <v-card-text>
                                        <p class="text-body-2 mb-2">
                                            To run the full aggregation pipeline on a schedule (e.g. daily), use the CLI from the server. From your NADA project root:
                                        </p>
                                        <pre class="pa-3 rounded" style="background:#f5f5f5; font-size: 0.9em; overflow-x: auto;">php index.php cli/analytics run_aggregates</pre>
                                        <p class="text-body-2 mt-3 mb-2">
                                            Example: run every day at 2:00 AM. Edit crontab with <code>crontab -e</code> and add:
                                        </p>
                                        <pre class="pa-3 rounded" style="background:#f5f5f5; font-size: 0.9em; overflow-x: auto;">0 2 * * * cd <?php echo isset($nada_base_path) ? htmlspecialchars($nada_base_path) : '/path/to/nada'; ?> &amp;&amp; php index.php cli/analytics run_aggregates</pre>
                                        <p class="text-caption text--secondary mt-2 mb-0">
                                            Replace <code>/path/to/nada</code> with your actual NADA installation path. The CLI runs the same pipeline as "Run Full Aggregation" (daily → monthly → month-end → cleanup → sync) and exits when done.
                                        </p>
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
        const siteUrl = '<?php echo site_url(); ?>';
    </script>
    <script>
    <?php echo $this->load->view('admin/analytics/raw_pageviews_component.js', null, true); ?>
    <?php echo $this->load->view('admin/analytics/raw_downloads_component.js', null, true); ?>
    </script>
    <script>
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
                
                // Daily Data
                dailyDataView: 'studies',
                dailyStudiesHeaders: [
                    { text: 'Date', value: 'date', sortable: true },
                    { text: 'ID', value: 'study_id', sortable: false },
                    { text: 'Title', value: 'title', sortable: false },
                    { text: 'Pageviews', value: 'pageviews', sortable: true },
                    { text: 'Unique Visitors', value: 'unique_visitors', sortable: true },
                    { text: 'Downloads', value: 'downloads', sortable: true }
                ],
                dailyFilesHeaders: [
                    { text: 'Date', value: 'date', sortable: true },
                    { text: 'Study ID', value: 'study_id', sortable: true },
                    { text: 'Title', value: 'title', sortable: false },
                    { text: 'File Name', value: 'file_name', sortable: true },
                    { text: 'Downloads', value: 'downloads', sortable: true }
                ],
                dailyStudiesData: [],
                dailyFilesData: [],
                loadingDaily: false,
                dailyFilters: {
                    date_from: null,
                    date_to: null,
                    study_id: null
                },
                dailyPagination: {
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
                    month: null,
                    study_id: null
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
                lastCompletedAt: null,
                lastCompletedStartedAt: null,
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
                    if (newView === 'daily') {
                        if (this.dailyStudiesData.length === 0 && this.dailyFilesData.length === 0) {
                            this.loadDailyData();
                        }
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
            computed: {
                site_url() {
                    return siteUrl;
                }
            },
            methods: {
                async loadOverview() {
                    const today = this.getTodayDateString();
                    // Load today's pageviews (count + recent list)
                    try {
                        const pvResponse = await axios.get(apiBase + '/raw/pageviews', {
                            params: { limit: 10, offset: 0, date_from: today, date_to: today }
                        });
                        if (pvResponse.data.status === 'success') {
                            this.recentPageviews = pvResponse.data.data || [];
                            this.todayStats.pageviews = pvResponse.data.total || 0;
                        }
                    } catch (error) {
                        console.error('Error loading pageviews:', error);
                    }
                    
                    // Load today's downloads (count + recent list)
                    try {
                        const dlResponse = await axios.get(apiBase + '/raw/downloads', {
                            params: { limit: 10, offset: 0, date_from: today, date_to: today }
                        });
                        if (dlResponse.data.status === 'success') {
                            this.recentDownloads = dlResponse.data.data || [];
                            this.todayStats.downloads = dlResponse.data.total || 0;
                        }
                    } catch (error) {
                        console.error('Error loading downloads:', error);
                    }
                },
                getTodayDateString() {
                    const d = new Date();
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return y + '-' + m + '-' + day;
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
                        if (this.monthlyFilters.study_id) params.study_id = this.monthlyFilters.study_id;
                        
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
                    this.monthlyFilters = { year: null, month: null, study_id: null };
                    this.monthlyPagination.page = 1;
                    this.loadMonthlyData();
                },
                
                async loadDailyData() {
                    this.loadingDaily = true;
                    try {
                        const params = {
                            limit: this.dailyPagination.limit,
                            offset: (this.dailyPagination.page - 1) * this.dailyPagination.limit
                        };
                        if (this.dailyFilters.date_from) params.date_from = this.dailyFilters.date_from;
                        if (this.dailyFilters.date_to) params.date_to = this.dailyFilters.date_to;
                        if (this.dailyFilters.study_id) params.study_id = this.dailyFilters.study_id;
                        
                        const endpoint = this.dailyDataView === 'studies' ? '/daily/studies' : '/daily/files';
                        const response = await axios.get(apiBase + endpoint, { params });
                        
                        if (response.data.status === 'success') {
                            if (this.dailyDataView === 'studies') {
                                this.dailyStudiesData = response.data.data || [];
                            } else {
                                this.dailyFilesData = response.data.data || [];
                            }
                            this.dailyPagination.total = response.data.total || 0;
                        } else {
                            alert('Error loading daily data: ' + (response.data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Error loading daily data:', error);
                        alert('Error loading daily data: ' + (error.response?.data?.message || error.message));
                    } finally {
                        this.loadingDaily = false;
                    }
                },
                
                onDailyPageChange(page) {
                    this.dailyPagination.page = page;
                    this.loadDailyData();
                },
                
                clearDailyFilters() {
                    this.dailyFilters = { date_from: null, date_to: null, study_id: null };
                    this.dailyPagination.page = 1;
                    this.loadDailyData();
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
                                this.loadAggregationStatus(); // refresh last completed time
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
                            this.lastCompletedAt = status.last_completed_at || null;
                            this.lastCompletedStartedAt = status.last_completed_started_at || null;
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
                                this.lastCompletedAt = status.completed_at || this.lastCompletedAt;
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
