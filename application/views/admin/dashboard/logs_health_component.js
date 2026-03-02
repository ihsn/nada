(function () {
    Vue.component('logs-health', {
        props: {
            logsHealth: { type: Object, default: null },
            serverInfo: { type: Object, default: null },
            siteUrl: { type: String, default: '' }
        },
        computed: {
            sitelogsStatus() {
                if (!this.logsHealth || this.logsHealth.sitelogs === null) return 'unknown';
                return this.logsHealth.sitelogs_exceeds_threshold ? 'warning' : 'ok';
            },
            apiLogsStatus() {
                if (!this.logsHealth || this.logsHealth.api_logs === null) return 'unknown';
                return this.logsHealth.api_logs_exceeds_threshold ? 'warning' : 'ok';
            }
        },
        methods: {
            formatCount(n) {
                if (n === null || n === undefined) return '?';
                return Number(n).toLocaleString();
            }
        },
        template: `
<div>
    <!-- Server Info -->
    <div v-if="serverInfo" class="mb-4">
        <div class="text-subtitle-2 mb-2">Server</div>
        <v-list dense class="py-0">
            <v-list-item class="px-0">
                <v-list-item-content class="py-1">
                    <v-list-item-title class="body-2">PHP Version</v-list-item-title>
                </v-list-item-content>
                <v-list-item-action class="my-1">
                    <v-chip x-small label color="blue-grey lighten-4" style="font-family:monospace;">{{ serverInfo.php_version }}</v-chip>
                </v-list-item-action>
            </v-list-item>
            <v-list-item class="px-0">
                <v-list-item-content class="py-1">
                    <v-list-item-title class="body-2">Server Time</v-list-item-title>
                </v-list-item-content>
                <v-list-item-action class="my-1">
                    <span class="caption grey--text">{{ serverInfo.server_time }} {{ serverInfo.server_tz }}</span>
                </v-list-item-action>
            </v-list-item>
        </v-list>
    </div>

    <!-- Log Row Counts -->
    <div class="text-subtitle-2 mb-2">Log Tables</div>

    <v-list dense class="py-0">
        <!-- Sitelogs -->
        <v-list-item class="px-0">
            <v-list-item-content class="py-1">
                <v-list-item-title class="body-2">Site Logs</v-list-item-title>
                <v-list-item-subtitle>
                    <span v-if="sitelogsStatus === 'unknown'" class="grey--text caption">Count unavailable</span>
                    <span v-else class="caption">{{ formatCount(logsHealth.sitelogs) }} rows</span>
                </v-list-item-subtitle>
            </v-list-item-content>
            <v-list-item-action>
                <v-icon v-if="sitelogsStatus === 'ok'" color="success" small>mdi-check-circle</v-icon>
                <v-icon v-else-if="sitelogsStatus === 'warning'" color="warning" small>mdi-alert</v-icon>
                <v-icon v-else color="grey" small>mdi-help-circle-outline</v-icon>
            </v-list-item-action>
        </v-list-item>

        <!-- API Logs -->
        <v-list-item class="px-0">
            <v-list-item-content class="py-1">
                <v-list-item-title class="body-2">API Logs</v-list-item-title>
                <v-list-item-subtitle>
                    <span v-if="apiLogsStatus === 'unknown'" class="grey--text caption">Count unavailable</span>
                    <span v-else class="caption">{{ formatCount(logsHealth.api_logs) }} rows</span>
                </v-list-item-subtitle>
            </v-list-item-content>
            <v-list-item-action>
                <v-icon v-if="apiLogsStatus === 'ok'" color="success" small>mdi-check-circle</v-icon>
                <v-icon v-else-if="apiLogsStatus === 'warning'" color="warning" small>mdi-alert</v-icon>
                <v-icon v-else color="grey" small>mdi-help-circle-outline</v-icon>
            </v-list-item-action>
        </v-list-item>
    </v-list>

    <v-alert
        v-if="sitelogsStatus === 'warning' || apiLogsStatus === 'warning'"
        type="warning"
        dense
        text
        class="mt-2 caption"
    >
        One or more log tables have exceeded the row count threshold.
    </v-alert>

    <div v-if="!logsHealth" class="grey--text caption">Loading log health…</div>

    <div class="mt-2 text-right">
        <a :href="siteUrl + 'admin/logs/cleanup'" class="caption text-decoration-none">
            Cleanup &amp; Archiving →
        </a>
    </div>
</div>
        `
    });
})();
