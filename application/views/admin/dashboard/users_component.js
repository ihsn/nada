(function () {
    Vue.component('users-panel', {
        props: {
            users: { type: Object, default: null },
            siteUrl: { type: String, default: '' }
        },
        computed: {
            recentLogins() {
                return this.users ? this.users.recent_logins : [];
            }
        },
        template: `
<div>
    <v-row v-if="users" dense class="mb-3">
        <v-col cols="4" class="text-center">
            <div class="text-h5 font-weight-bold success--text">{{ users.active.toLocaleString() }}</div>
            <div class="text-caption grey--text">Active</div>
        </v-col>
        <v-col cols="4" class="text-center">
            <div class="text-h5 font-weight-bold error--text">{{ users.disabled.toLocaleString() }}</div>
            <div class="text-caption grey--text">Disabled</div>
        </v-col>
        <v-col cols="4" class="text-center">
            <div class="text-h5 font-weight-bold grey--text">{{ users.never_logged_in.toLocaleString() }}</div>
            <div class="text-caption grey--text">Never login</div>
        </v-col>
    </v-row>

    <div class="text-subtitle-2 mb-2">Recent Logins</div>
    <v-simple-table dense v-if="recentLogins.length">
        <thead>
            <tr>
                <th>User</th>
                <th class="text-right">Last Login</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="u in recentLogins" :key="u.id">
                <td>
                    <a :href="siteUrl + 'admin/users/edit/' + u.id" class="text-decoration-none">
                        {{ u.username }}
                    </a>
                </td>
                <td class="text-right caption">{{ u.last_login_fmt }}</td>
            </tr>
        </tbody>
    </v-simple-table>

    <div v-if="!users" class="text-center grey--text py-4">
        <v-progress-circular indeterminate color="primary" size="20"></v-progress-circular>
    </div>

    <div class="mt-3 text-right">
        <a :href="siteUrl + 'admin/users'" class="caption text-decoration-none">Manage users →</a>
    </div>
</div>
        `
    });
})();
