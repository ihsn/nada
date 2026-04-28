<template>
  <v-app>
    <v-main>
      <v-container fluid class="pa-4">

        <!-- Header -->
        <v-row align="center" class="mb-2">
          <v-col>
            <h1 class="text-h5 font-weight-bold">Dashboard</h1>
          </v-col>
          <v-col cols="auto">
            <v-btn icon size="small" :loading="loading" title="Refresh" @click="refresh">
              <v-icon size="small">mdi-refresh</v-icon>
            </v-btn>
          </v-col>
        </v-row>

        <!-- Error -->
        <v-alert v-if="error" type="error" density="compact" variant="tinted" closable class="mb-4">
          {{ error }}
        </v-alert>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
          <v-progress-circular indeterminate color="primary" size="48" />
          <div class="mt-3 text-medium-emphasis">Loading dashboard…</div>
        </div>

        <template v-if="!loading && stats">
          <v-row>
            <!-- Left column: Recently modified studies + Catalog + Collections -->
            <v-col cols="12" md="8">

              <v-card class="dashboard-card mb-4">
                <v-card-title class="pb-1">
                  <v-icon start color="primary">mdi-history</v-icon>
                  Recently modified studies
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <RecentStudies :studies="stats.recent_studies || []" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card mb-4">
                <v-card-title class="pb-1 d-flex align-center">
                  <v-icon class="mr-1" start color="primary">mdi-book-open-variant</v-icon>
                  Catalog
                  <v-spacer />
                  <v-btn size="x-small" variant="text" color="primary" :href="siteUrl + '/admin/catalog'" prepend-icon="mdi-cog">
                    Manage
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <CatalogStats :catalog="stats.catalog" />
                </v-card-text>
              </v-card>

              <v-card  class="dashboard-card">
                <v-card-title class="pb-1 d-flex align-center">
                  <v-icon class="mr-1" start color="primary">mdi-folder-multiple</v-icon>
                  Collections
                  <v-chip size="x-small" class="ml-2" color="primary" variant="outlined">
                    {{ stats.collections?.length ?? 0 }}
                  </v-chip>
                  <v-spacer />
                  <v-btn size="x-small" variant="text" color="primary" :href="siteUrl + '/admin/collections'" prepend-icon="mdi-cog">
                    Manage
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <CollectionsTable :collections="stats.collections || []" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

            </v-col>

            <!-- Right column: Users + System Health + License Requests -->
            <v-col cols="12" md="4">

              <v-card class="dashboard-card mb-4">
                <v-card-title class="pb-1">
                  <v-icon start color="primary">mdi-account-group</v-icon>
                  Users
                  <v-chip size="x-small" class="ml-2" color="primary" variant="outlined">
                    {{ stats.users?.total?.toLocaleString() ?? '…' }} total
                  </v-chip>
                </v-card-title>
                <v-card-text class="mt-3">
                  <UsersPanel :users="stats.users" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card mb-4">
                <v-card-title class="pb-1">
                  <v-icon start color="primary">mdi-monitor-dashboard</v-icon>
                  System Health
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <LogsHealth :logs-health="stats.logs_health" :server-info="stats.server_info" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card  class="dashboard-card">
                <v-card-title class="pb-1 d-flex align-center">
                  <v-icon start color="orange-darken-2">mdi-file-document-outline</v-icon>
                  License Requests
                  <v-chip
                    size="x-small" class="ml-2"
                    :color="stats.license_requests?.pending > 0 ? 'error' : 'success'"
                  >
                    {{ stats.license_requests?.pending ?? 0 }} pending
                  </v-chip>
                  <v-spacer />
                  <v-btn size="x-small" variant="text" color="primary" :href="siteUrl + '/admin/licensed_requests'" prepend-icon="mdi-arrow-right">
                    View All
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-0">
                  <LicenseRequestsPanel :license-requests="stats.license_requests" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

            </v-col>
          </v-row>
        </template>

      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useDashboardApi } from './composables/useDashboardApi';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import CatalogStats from './components/CatalogStats.vue';
import CollectionsTable from './components/CollectionsTable.vue';
import LicenseRequestsPanel from './components/LicenseRequestsPanel.vue';
import UsersPanel from './components/UsersPanel.vue';
import LogsHealth from './components/LogsHealth.vue';
import RecentStudies from './components/RecentStudies.vue';

const { siteUrl } = useAppConfig();
const { loading, error, loadStats } = useDashboardApi();
const stats = ref(null);

async function refresh() {
  stats.value = await loadStats();
}

onMounted(refresh);
</script>

<style>
.dashboard-card {
  transition: box-shadow 0.2s;
}
.dashboard-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
</style>
