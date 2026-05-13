<template>
  <div class="admin-dashboard-page bg-grey-lighten-5">
    <v-container fluid class="px-4 pt-2 pb-4">

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

              <div class="dashboard-left-stack">
              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2">
                    <v-icon size="large" color="primary">mdi-history</v-icon>
                    Recently modified studies
                  </span>
                  <v-spacer />
                  <div class="d-flex flex-wrap align-center justify-end ga-1">
                    <v-btn
                      size="small"
                      variant="text"
                      color="primary"
                      :href="siteUrl + '/admin/collections#/history/central'"
                      prepend-icon="mdi-history"
                    >
                      View History
                    </v-btn>
                    <v-btn
                      size="small"
                      variant="text"
                      color="primary"
                      :href="siteUrl + '/admin/catalog'"
                      prepend-icon="mdi-book-open-variant"
                    >
                      Manage Catalog
                    </v-btn>
                  </div>
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <RecentStudies :studies="stats.recent_studies || []" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2">
                    <v-icon size="large" color="primary">mdi-book-open-variant</v-icon>
                    Catalog
                  </span>
                  <v-spacer />
                  <v-btn size="small" variant="text" color="primary" :href="siteUrl + '/admin/catalog'" prepend-icon="mdi-cog">
                    Manage
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <CatalogStats :catalog="stats.catalog" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2 flex-wrap">
                    <v-icon size="large" color="primary">mdi-folder-multiple</v-icon>
                    Collections
                    <v-chip size="x-small" color="primary" variant="outlined">
                      {{ stats.collections?.length ?? 0 }}
                    </v-chip>
                  </span>
                  <v-spacer />
                  <v-btn size="small" variant="text" color="primary" :href="siteUrl + '/admin/collections'" prepend-icon="mdi-cog">
                    Manage
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text>
                  <CollectionsTable :collections="stats.collections || []" :site-url="siteUrl" />
                </v-card-text>
              </v-card>
              </div>

            </v-col>

            <!-- Right column: Users + System Health + License Requests -->
            <v-col cols="12" md="4">

              <div class="dashboard-right-stack">
              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2 flex-wrap">
                    <v-icon size="large" color="primary">mdi-account-group</v-icon>
                    Users
                    <v-chip size="x-small" color="primary" variant="outlined">
                      {{ stats.users?.total?.toLocaleString() ?? '…' }} total
                    </v-chip>
                  </span>
                  <v-spacer />
                  <v-btn
                    size="small"
                    variant="text"
                    color="primary"
                    :href="siteUrl + '/admin/users'"
                    prepend-icon="mdi-account-cog-outline"
                  >
                    Manage users
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-4">
                  <UsersPanel :users="stats.users" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center ga-2">
                  <span class="d-flex align-center ga-2">
                    <v-icon size="large" color="primary">mdi-monitor-dashboard</v-icon>
                    System Health
                  </span>
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-4">
                  <LogsHealth :logs-health="stats.logs_health" :server-info="stats.server_info" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2 flex-wrap">
                    <v-icon size="large" color="orange-darken-2">mdi-file-document-outline</v-icon>
                    License Requests
                    <v-chip
                      size="x-small"
                      :color="stats.license_requests?.pending > 0 ? 'error' : 'success'"
                    >
                      {{ stats.license_requests?.pending ?? 0 }} pending
                    </v-chip>
                  </span>
                  <v-spacer />
                  <v-btn size="small" variant="text" color="primary" :href="siteUrl + '/admin/licensed_requests'" prepend-icon="mdi-arrow-right">
                    View All
                  </v-btn>
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-0">
                  <LicenseRequestsPanel :license-requests="stats.license_requests" :site-url="siteUrl" />
                </v-card-text>
              </v-card>
              </div>

            </v-col>
          </v-row>
        </template>

    </v-container>
  </div>
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

onMounted(async () => {
  stats.value = await loadStats();
});
</script>

<style scoped>
.admin-dashboard-page {
  /* Fill viewport below fixed admin bar (~64px + shell offset); subtle grey from bg-grey-lighten-5 */
  min-height: calc(100vh - 5rem);
}
</style>

<style>
/* Flex gap between stacked cards; * { margin: 0 } in base.css swamps mb-* on v-card. */
.dashboard-left-stack,
.dashboard-right-stack {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.dashboard-card .dashboard-card-title {
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.45;
  padding: 16px 20px;
  min-height: 0;
}

.dashboard-card {
  transition: box-shadow 0.2s;
}
.dashboard-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
</style>
