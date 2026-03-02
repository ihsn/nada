(function () {
    Vue.component('collections-table', {
        props: {
            collections: { type: Array, default: () => [] },
            siteUrl: { type: String, default: '' }
        },
        data() {
            return {
                search: '',
                headers: [
                    { text: 'ID', value: 'repo_key', sortable: true, width: '160px' },
                    { text: 'Collection', value: 'repo_title', sortable: true },
                    { text: 'Total', value: 'total', sortable: true, align: 'right' },
                    { text: 'Published', value: 'published', sortable: true, align: 'right' },
                    { text: 'Unpublished', value: 'unpublished', sortable: true, align: 'right' },
                    { text: 'Pending Requests', value: 'pending_requests', sortable: true, align: 'right' },
                    { text: '', value: 'actions', sortable: false, align: 'right', width: '80px' },
                ]
            };
        },
        template: `
<div>
    <v-text-field
        v-if="collections.length > 6"
        v-model="search"
        append-icon="mdi-magnify"
        label="Filter collections"
        single-line
        hide-details
        dense
        outlined
        class="mb-3"
    ></v-text-field>

    <v-data-table
        :headers="headers"
        :items="collections"
        :search="search"
        :items-per-page="-1"
        hide-default-footer
        dense
        class="elevation-0"
        no-data-text="No collections found"
    >
        <template v-slot:item.repo_title="{ item }">
            <a v-if="item.repo_id" :href="siteUrl + 'admin/repositories/active/' + item.repo_id + '?destination=admin/catalog'" class="text-decoration-none font-weight-medium">{{ item.repo_title }}</a>
            <span v-else class="font-weight-medium">{{ item.repo_title }}</span>
        </template>
        <template v-slot:item.repo_key="{ item }">
            <a v-if="item.repo_id" :href="siteUrl + 'admin/repositories/active/' + item.repo_id + '?destination=admin/catalog'" class="text-decoration-none">
                <v-chip x-small label color="blue-grey lighten-4" style="text-transform:uppercase;font-family:monospace;cursor:pointer;">{{ item.repo_key }}</v-chip>
            </a>
            <v-chip v-else x-small label color="blue-grey lighten-4" style="text-transform:uppercase;font-family:monospace;">{{ item.repo_key }}</v-chip>
        </template>
        <template v-slot:item.total="{ item }">
            <span>{{ item.total.toLocaleString() }}</span>
        </template>
        <template v-slot:item.published="{ item }">
            <span class="success--text font-weight-medium">{{ item.published.toLocaleString() }}</span>
        </template>
        <template v-slot:item.unpublished="{ item }">
            <span class="warning--text">{{ item.unpublished.toLocaleString() }}</span>
        </template>
        <template v-slot:item.pending_requests="{ item }">
            <v-chip v-if="item.pending_requests > 0" x-small color="error" dark>
                {{ item.pending_requests }}
            </v-chip>
            <span v-else class="grey--text">—</span>
        </template>
        <template v-slot:item.actions="{ item }">
            <v-menu offset-y left>
                <template v-slot:activator="{ on, attrs }">
                    <v-btn icon x-small v-bind="attrs" v-on="on">
                        <v-icon x-small>mdi-dots-vertical</v-icon>
                    </v-btn>
                </template>
                <v-list dense>
                    <v-list-item :href="item.repo_id ? siteUrl + 'admin/repositories/active/' + item.repo_id + '?destination=admin/catalog' : siteUrl + 'admin/catalog'">
                        <v-list-item-icon class="mr-2"><v-icon small>mdi-cog</v-icon></v-list-item-icon>
                        <v-list-item-title>Manage</v-list-item-title>
                    </v-list-item>
                    <v-list-item :href="siteUrl + 'admin/repositories/history/' + item.repo_key">
                        <v-list-item-icon class="mr-2"><v-icon small>mdi-history</v-icon></v-list-item-icon>
                        <v-list-item-title>History</v-list-item-title>
                    </v-list-item>
                    <v-list-item :href="item.repo_id ? siteUrl + 'admin/licensed_requests?collection=' + item.repo_key : siteUrl + 'admin/licensed_requests'">
                        <v-list-item-icon class="mr-2"><v-icon small>mdi-file-document-outline</v-icon></v-list-item-icon>
                        <v-list-item-title>License Requests</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </template>
    </v-data-table>
</div>
        `
    });
})();
