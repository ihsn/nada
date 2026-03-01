(function() {
    Vue.component('raw-pageviews', {
        template: `
            <v-card class="mt-4">
                <v-card-title>
                    <v-icon left color="primary">mdi-eye</v-icon>
                    Raw Data - Pageviews
                </v-card-title>
                <v-card-text>
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
                        <v-col cols="12" md="4">
                            <v-btn color="primary" @click="loadPageviews" :loading="loadingPageviews" class="mr-2">
                                <v-icon left>mdi-magnify</v-icon>
                                Filter
                            </v-btn>
                            <v-btn @click="clearPageviewFilters" outlined>
                                Clear
                            </v-btn>
                        </v-col>
                    </v-row>
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
        `,
        data: function() {
            return {
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
                }
            };
        },
        mounted: function() {
            this.loadPageviews();
        },
        methods: {
            formatDateTime: function(dateString) {
                if (!dateString) return '-';
                var date = new Date(dateString);
                return date.toLocaleString();
            },
            loadPageviews: function() {
                var self = this;
                this.loadingPageviews = true;
                var params = {
                    limit: this.pageviewPagination.limit,
                    offset: (this.pageviewPagination.page - 1) * this.pageviewPagination.limit,
                    sort_by: 'ts',
                    sort_order: 'desc'
                };
                if (this.pageviewFilters.date_from) params.date_from = this.pageviewFilters.date_from;
                if (this.pageviewFilters.date_to) params.date_to = this.pageviewFilters.date_to;
                axios.get(apiBase + '/raw/pageviews', { params: params })
                    .then(function(response) {
                        if (response.data.status === 'success') {
                            self.pageviewData = response.data.data || [];
                            self.pageviewPagination.total = response.data.total || 0;
                        } else {
                            alert('Error loading pageviews: ' + (response.data.message || 'Unknown error'));
                        }
                    })
                    .catch(function(error) {
                        console.error('Error loading pageviews:', error);
                        alert('Error loading pageviews: ' + (error.response && error.response.data && error.response.data.message ? error.response.data.message : error.message));
                    })
                    .finally(function() {
                        self.loadingPageviews = false;
                    });
            },
            onPageviewPageChange: function(page) {
                this.pageviewPagination.page = page;
                this.loadPageviews();
            },
            clearPageviewFilters: function() {
                this.pageviewFilters = { date_from: null, date_to: null };
                this.pageviewPagination.page = 1;
                this.loadPageviews();
            }
        }
    });
})();
