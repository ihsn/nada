(function () {
    Vue.component('license-requests-panel', {
        props: {
            licenseRequests: { type: Object, default: null },
            siteUrl: { type: String, default: '' }
        },
        data() {
            return {
                headers: [
                    { text: 'ID',          value: 'id',            sortable: false, width: '60px' },
                    { text: 'Title',        value: 'request_title', sortable: false },
                    { text: 'Applicant',    value: 'username',      sortable: false, width: '120px' },
                    { text: 'Organization', value: 'org_rec',       sortable: false, width: '140px' },
                    { text: 'Date',         value: 'created_fmt',   sortable: false, width: '100px' },
                ]
            };
        },
        computed: {
            requests() {
                return (this.licenseRequests && this.licenseRequests.pending_requests) || [];
            },
            pending() {
                return this.licenseRequests ? this.licenseRequests.pending : 0;
            }
        },
        template: `
<div>
    <div v-if="requests.length === 0" class="d-flex flex-column align-center justify-center py-8 grey--text">
        <v-icon large color="grey lighten-2" class="mb-3">mdi-file-check-outline</v-icon>
        <span class="body-2">No pending license requests.</span>
    </div>
    <v-data-table
        v-else
        :headers="headers"
        :items="requests"
        :items-per-page="-1"
        hide-default-footer
        dense
        class="elevation-0"
    >
        <template v-slot:item.id="{ item }">
            <a :href="siteUrl + 'admin/licensed_requests/edit/' + item.id" class="text-decoration-none caption font-weight-medium">
                #{{ item.id }}
            </a>
        </template>
        <template v-slot:item.request_title="{ item }">
            <a :href="siteUrl + 'admin/licensed_requests/edit/' + item.id" class="text-decoration-none body-2">
                {{ item.request_title || '—' }}
            </a>
        </template>
        <template v-slot:item.username="{ item }">
            <span class="caption">{{ item.username || item.email || '—' }}</span>
        </template>
        <template v-slot:item.org_rec="{ item }">
            <span class="caption grey--text">{{ item.org_rec || '—' }}</span>
        </template>
        <template v-slot:item.created_fmt="{ item }">
            <span class="caption grey--text">{{ item.created_fmt }}</span>
        </template>
    </v-data-table>
    <v-divider v-if="requests.length > 0"></v-divider>
    <div class="d-flex justify-end pa-2">
        <v-btn x-small text color="primary" :href="siteUrl + 'admin/licensed_requests'">
            <v-icon x-small left>mdi-arrow-right</v-icon>View All
        </v-btn>
    </div>
</div>
        `
    });
})();
