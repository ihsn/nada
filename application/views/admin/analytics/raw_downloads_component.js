(function() {
    Vue.component('raw-downloads', {
        template: `
            <v-card class="mt-4">
                <v-card-title>
                    <v-icon left color="primary">mdi-download</v-icon>
                    Raw Data - Downloads
                </v-card-title>
                <v-card-text>
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
                        <v-col cols="12" md="3">
                            <v-btn color="primary" @click="loadDownloads" :loading="loadingDownloads" class="mr-2">
                                <v-icon left>mdi-magnify</v-icon>
                                Filter
                            </v-btn>
                            <v-btn @click="clearDownloadFilters" outlined>
                                Clear
                            </v-btn>
                        </v-col>
                    </v-row>
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
        `,
        data: function() {
            return {
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
                }
            };
        },
        mounted: function() {
            this.loadDownloads();
        },
        methods: {
            formatDateTime: function(dateString) {
                if (!dateString) return '-';
                var date = new Date(dateString);
                return date.toLocaleString();
            },
            loadDownloads: function() {
                var self = this;
                this.loadingDownloads = true;
                var params = {
                    limit: this.downloadPagination.limit,
                    offset: (this.downloadPagination.page - 1) * this.downloadPagination.limit,
                    sort_by: 'ts',
                    sort_order: 'desc'
                };
                if (this.downloadFilters.date_from) params.date_from = this.downloadFilters.date_from;
                if (this.downloadFilters.date_to) params.date_to = this.downloadFilters.date_to;
                if (this.downloadFilters.file_type) params.file_type = this.downloadFilters.file_type;
                axios.get(apiBase + '/raw/downloads', { params: params })
                    .then(function(response) {
                        if (response.data.status === 'success') {
                            self.downloadData = response.data.data || [];
                            self.downloadPagination.total = response.data.total || 0;
                        } else {
                            alert('Error loading downloads: ' + (response.data.message || 'Unknown error'));
                        }
                    })
                    .catch(function(error) {
                        console.error('Error loading downloads:', error);
                        alert('Error loading downloads: ' + (error.response && error.response.data && error.response.data.message ? error.response.data.message : error.message));
                    })
                    .finally(function() {
                        self.loadingDownloads = false;
                    });
            },
            onDownloadPageChange: function(page) {
                this.downloadPagination.page = page;
                this.loadDownloads();
            },
            clearDownloadFilters: function() {
                this.downloadFilters = { date_from: null, date_to: null, file_type: null };
                this.downloadPagination.page = 1;
                this.loadDownloads();
            }
        }
    });
})();
