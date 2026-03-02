(function () {
    Vue.component('catalog-stats', {
        props: {
            catalog: { type: Object, default: null }
        },
        computed: {
            byType() {
                return this.catalog ? this.catalog.by_type : [];
            }
        },
        template: `
<div>
    <v-row v-if="catalog">
        <v-col cols="12" sm="4">
            <v-card outlined class="text-center py-4">
                <div class="text-h4 font-weight-bold primary--text">{{ catalog.total.toLocaleString() }}</div>
                <div class="text-caption text-uppercase grey--text mt-1">Total Studies</div>
            </v-card>
        </v-col>
        <v-col cols="12" sm="4">
            <v-card outlined class="text-center py-4">
                <div class="text-h4 font-weight-bold success--text">{{ catalog.published.toLocaleString() }}</div>
                <div class="text-caption text-uppercase grey--text mt-1">Published</div>
            </v-card>
        </v-col>
        <v-col cols="12" sm="4">
            <v-card outlined class="text-center py-4">
                <div class="text-h4 font-weight-bold warning--text">{{ catalog.unpublished.toLocaleString() }}</div>
                <div class="text-caption text-uppercase grey--text mt-1">Unpublished</div>
            </v-card>
        </v-col>
    </v-row>

    <v-simple-table v-if="byType.length" dense class="mt-3">
        <thead>
            <tr>
                <th>Study Type</th>
                <th class="text-right">Total</th>
                <th class="text-right">Published</th>
                <th class="text-right">Unpublished</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in byType" :key="row.type">
                <td>
                    <v-chip x-small label color="blue-grey lighten-4" class="text-capitalize">{{ row.type }}</v-chip>
                </td>
                <td class="text-right">{{ row.total.toLocaleString() }}</td>
                <td class="text-right success--text">{{ row.published.toLocaleString() }}</td>
                <td class="text-right warning--text">{{ row.unpublished.toLocaleString() }}</td>
            </tr>
        </tbody>
    </v-simple-table>

    <div v-if="!catalog" class="text-center grey--text py-6">
        <v-progress-circular indeterminate color="primary" size="24"></v-progress-circular>
    </div>
</div>
        `
    });
})();
