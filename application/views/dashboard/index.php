<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- MDI Icons -->
    <link href="<?php echo base_url(); ?>javascript/mdi/css/materialdesignicons.min.css" rel="stylesheet">

    <!-- Vuetify CSS -->
    <link href="<?php echo base_url(); ?>javascript/vuetify.min.css" rel="stylesheet">

    <!-- Vue.js, Axios, Vuetify JS -->
    <script src="<?php echo base_url(); ?>javascript/vue.min.js"></script>
    <script src="<?php echo base_url(); ?>javascript/axios.min.js"></script>
    <script src="<?php echo base_url(); ?>javascript/vuetify.min.js"></script>

    <style>
        /* Contain Vuetify inside admin5 template without full-page takeover */
        #dashboard-app .v-application--wrap {
            min-height: unset;
        }
        #dashboard-app .v-application {
            background-color: #f5f5f5 !important;
        }
        #dashboard-app {
            margin-top: 8px;
            background-color: #f5f5f5;
        }
        .dashboard-card {
            transition: box-shadow 0.2s;
        }
        .dashboard-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
        }
        .section-heading {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9e9e9e;
        }
    </style>
</head>
<body>

<div id="dashboard-app">
<v-app>
    <v-main>
        <v-container fluid class="pa-4">

            <!-- Header row -->
            <v-row align="center" class="mb-2">
                <v-col>
                    <h1 class="text-h5 font-weight-bold"><?php echo t('Dashboard'); ?></h1>
                </v-col>
                <v-col cols="auto">
                    <v-btn icon small :loading="loading" @click="loadStats" title="Refresh">
                        <v-icon small>mdi-refresh</v-icon>
                    </v-btn>
                </v-col>
            </v-row>

            <!-- Error alert -->
            <v-alert v-if="error" type="error" dense text dismissible class="mb-4">
                {{ error }}
            </v-alert>

            <!-- Loading skeleton -->
            <div v-if="loading" class="text-center py-12">
                <v-progress-circular indeterminate color="primary" size="48"></v-progress-circular>
                <div class="mt-3 grey--text">Loading dashboard&hellip;</div>
            </div>

            <template v-if="!loading && stats">

                <!-- Row 1: Catalog + Collections (left) + Users & Health (right) -->
                <v-row>
                    <v-col cols="12" md="8">

                        <!-- License Requests card -->
                        <v-card class="dashboard-card mb-4" outlined>
                            <v-card-title class="pb-1">
                                <v-icon left color="orange darken-2">mdi-file-document-outline</v-icon>
                                License Requests
                                <v-chip
                                    x-small class="ml-2"
                                    :color="stats.license_requests && stats.license_requests.pending > 0 ? 'error' : 'success'"
                                    dark>
                                    {{ stats.license_requests ? stats.license_requests.pending : 0 }} pending
                                </v-chip>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text class="pa-0">
                                <license-requests-panel
                                    :license-requests="stats.license_requests"
                                    :site-url="siteUrl"
                                ></license-requests-panel>
                            </v-card-text>
                        </v-card>

                        <v-card class="dashboard-card mb-4" outlined>
                            <v-card-title class="pb-1">
                                <v-icon left color="primary">mdi-book-open-variant</v-icon>
                                Catalog
                                <v-spacer></v-spacer>
                                <v-btn x-small text color="primary" :href="siteUrl + 'admin/catalog'">
                                    <v-icon x-small left>mdi-cog</v-icon>Manage
                                </v-btn>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text>
                                <catalog-stats :catalog="stats.catalog"></catalog-stats>
                            </v-card-text>
                        </v-card>

                        <v-card class="dashboard-card" outlined>
                            <v-card-title class="pb-1">
                                <v-icon left color="primary">mdi-folder-multiple</v-icon>
                                Collections
                                <v-chip x-small class="ml-2" color="primary" outlined>
                                    {{ stats.collections ? stats.collections.length : 0 }}
                                </v-chip>
                                <v-spacer></v-spacer>
                                <v-btn x-small text color="primary" :href="siteUrl + 'admin/repositories'">
                                    <v-icon x-small left>mdi-cog</v-icon>Manage
                                </v-btn>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text>
                                <collections-table :collections="stats.collections || []" :site-url="siteUrl"></collections-table>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <v-col cols="12" md="4">
                        <v-card class="dashboard-card mb-4" outlined>
                            <v-card-title class="pb-1">
                                <v-icon left color="primary">mdi-account-group</v-icon>
                                Users
                                <v-chip x-small class="ml-2" color="primary" outlined>
                                    {{ stats.users ? stats.users.total.toLocaleString() : '&hellip;' }} total
                                </v-chip>
                                <v-spacer></v-spacer>
                                <v-btn x-small text color="primary" :href="siteUrl + 'admin/users'">
                                    <v-icon x-small left>mdi-cog</v-icon>Manage
                                </v-btn>
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text>
                                <users-panel
                                    :users="stats.users"
                                    :site-url="siteUrl"
                                ></users-panel>
                            </v-card-text>
                        </v-card>

                        <v-card class="dashboard-card mb-4" outlined>
                            <v-card-title class="pb-1">
                                <v-icon left color="primary">mdi-monitor-dashboard</v-icon>
                                System Health
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text>
                                <logs-health
                                    :logs-health="stats.logs_health"
                                    :server-info="stats.server_info"
                                    :site-url="siteUrl"
                                ></logs-health>
                            </v-card-text>
                        </v-card>

                        <v-card class="dashboard-card" outlined>
                            <v-card-title class="pb-1">
                                <v-icon left color="primary">mdi-history</v-icon>
                                Recently Modified Studies
                            </v-card-title>
                            <v-divider></v-divider>
                            <v-card-text>
                                <recent-studies
                                    :studies="stats.recent_studies || []"
                                    :site-url="siteUrl"
                                ></recent-studies>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

            </template>

        </v-container>
    </v-main>
</v-app>
</div>

<script>
    const siteUrl = '<?php echo rtrim(site_url(), "/"); ?>/';
</script>
<script>
<?php echo $this->load->view('admin/dashboard/license_requests_component.js', null, true); ?>
<?php echo $this->load->view('admin/dashboard/catalog_stats_component.js',   null, true); ?>
<?php echo $this->load->view('admin/dashboard/collections_component.js',     null, true); ?>
<?php echo $this->load->view('admin/dashboard/users_component.js',            null, true); ?>
<?php echo $this->load->view('admin/dashboard/recent_studies_component.js',  null, true); ?>
<?php echo $this->load->view('admin/dashboard/logs_health_component.js',     null, true); ?>
</script>
<script>
    new Vue({
        el: '#dashboard-app',
        vuetify: new Vuetify({
            theme: {
                themes: {
                    light: {
                        primary:   '#1976D2',
                        secondary: '#424242',
                        accent:    '#82B1FF',
                        error:     '#FF5252',
                        info:      '#2196F3',
                        success:   '#4CAF50',
                        warning:   '#FB8C00'
                    }
                }
            }
        }),
        data: {
            loading: true,
            stats:   null,
            error:   null,
            siteUrl: siteUrl
        },
        mounted() {
            this.loadStats();
        },
        methods: {
            loadStats() {
                this.loading = true;
                this.error   = null;
                var self = this;
                axios.get(siteUrl + 'api/dashboard/stats')
                    .then(function (response) {
                        if (response.data && response.data.data) {
                            self.stats = response.data.data;
                        } else {
                            self.error = 'Unexpected response format from dashboard API.';
                        }
                        self.loading = false;
                    })
                    .catch(function (err) {
                        self.error   = 'Failed to load dashboard data: ' +
                            (err.response ? err.response.statusText : err.message);
                        self.loading = false;
                    });
            }
        }
    });
</script>
</body>
</html>