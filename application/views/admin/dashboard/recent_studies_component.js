(function () {
    Vue.component('recent-studies', {
        props: {
            studies: { type: Array, default: () => [] },
            siteUrl: { type: String, default: '' }
        },
        data() {
            return {
                headers: [
                    { text: 'Title', value: 'title', sortable: false },
                    { text: 'Changed', value: 'changed_fmt', sortable: false, width: '110px' },
                ]
            };
        },
        template: `
<div>
    <v-data-table
        :headers="headers"
        :items="studies"
        :items-per-page="10"
        hide-default-footer
        dense
        class="elevation-0"
        no-data-text="No studies found"
    >
        <template v-slot:item.title="{ item }">
            <a :href="siteUrl + 'admin/catalog/edit/' + item.id" class="text-decoration-none d-block" :title="item.title">
                <div class="body-2">{{ item.title }}</div>
                <div class="caption grey--text">{{ item.idno }}</div>
            </a>
        </template>
        <template v-slot:item.changed_fmt="{ item }">
            <span class="caption grey--text">{{ item.changed_fmt }}</span>
        </template>
    </v-data-table>
    <v-divider></v-divider>
    <div class="d-flex justify-end pa-2" style="gap:8px;">
        <v-btn x-small text color="primary" :href="siteUrl + 'admin/repositories/history/central'">
            <v-icon x-small left>mdi-history</v-icon>View History
        </v-btn>
        <v-btn x-small text color="primary" :href="siteUrl + 'admin/catalog'">
            <v-icon x-small left>mdi-book-open-variant</v-icon>Manage Catalog
        </v-btn>
    </div>
</div>
        `
    });
})();
