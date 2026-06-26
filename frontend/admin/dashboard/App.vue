<template>
  <v-app class="admin-dashboard-vapp">
    <div class="admin-dashboard-page">
      <v-container fluid class="px-4 pt-2 pb-4">
        <h1 class="admin-dashboard-page__title text-h5 font-weight-medium mb-4">
          {{ pageTitle }}
        </h1>

        <!-- Error -->
        <v-alert v-if="error" type="error" density="compact" variant="tinted" closable class="mb-4">
          {{ error }}
        </v-alert>

        <!-- Loading: summary skeleton + spinner -->
        <template v-if="loading">
          <v-row>
            <v-col cols="12">
              <div class="dashboard-summary-grid mb-4">
                <v-skeleton-loader
                  v-for="n in 4"
                  :key="'sk-' + n"
                  type="image"
                  height="132"
                  class="rounded-lg"
                />
              </div>
              <div class="text-center py-8">
                <v-progress-circular indeterminate color="primary" size="48" />
                <div class="mt-3 text-medium-emphasis">Loading dashboard…</div>
              </div>
            </v-col>
          </v-row>
        </template>

        <template v-if="!loading && stats">
          <v-row>
            <!-- Left column: summary + Recently modified studies + License requests -->
            <v-col cols="12" md="8">

              <div class="dashboard-left-stack">
              <DashboardSummaryCards
                :site-url="siteUrl"
                :stats="stats"
                :translations="summaryTranslations"
              />

              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2 flex-wrap">
                    <v-icon color="primary">mdi-history</v-icon>
                    Recently modified studies
                    <v-chip
                      v-if="(stats.recent_studies || []).length"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                      class="font-weight-medium"
                    >
                      {{ (stats.recent_studies || []).length }}
                    </v-chip>
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
                <v-card-text class="recent-studies-card-text pa-0">
                  <RecentStudies :studies="stats.recent_studies || []" :site-url="siteUrl" />
                </v-card-text>
              </v-card>

              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2 flex-wrap">
                    <v-icon color="primary">mdi-file-document-outline</v-icon>
                    License Requests
                    <v-chip
                      v-if="(stats.license_requests?.pending ?? 0) > 0"
                      size="x-small"
                      color="error"
                      variant="tonal"
                      class="font-weight-medium"
                    >
                      {{ stats.license_requests?.pending }}
                    </v-chip>
                  </span>
                  <v-spacer />
                  <div class="d-flex flex-wrap align-center justify-end ga-1">
                    <v-btn
                      size="small"
                      variant="text"
                      color="primary"
                      :href="siteUrl + '/admin/licensed_requests'"
                      prepend-icon="mdi-arrow-right"
                    >
                      View All
                    </v-btn>
                  </div>
                </v-card-title>
                <v-divider />
                <v-card-text class="recent-studies-card-text pa-0">
                  <LicenseRequestsPanel :license-requests="stats.license_requests" :site-url="siteUrl" />
                </v-card-text>
              </v-card>
              </div>

            </v-col>

            <!-- Right column: Users + System Health -->
            <v-col cols="12" md="4">

              <div class="dashboard-right-stack">
              <v-card class="dashboard-card">
                <v-card-title class="dashboard-card-title d-flex align-center flex-wrap ga-2">
                  <span class="d-flex align-center ga-2 flex-wrap">
                    <v-icon color="primary">mdi-account-group</v-icon>
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
                    <v-icon color="primary">mdi-monitor-dashboard</v-icon>
                    System Health
                  </span>
                </v-card-title>
                <v-divider />
                <v-card-text class="pa-4">
                  <LogsHealth :logs-health="stats.logs_health" :server-info="stats.server_info" :site-url="siteUrl" />
                </v-card-text>
              </v-card>
              </div>

            </v-col>
          </v-row>
        </template>

      </v-container>
    </div>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed, inject } from 'vue';
import { useDashboardApi } from './composables/useDashboardApi';
import { useAppConfig, APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import LicenseRequestsPanel from './components/LicenseRequestsPanel.vue';
import UsersPanel from './components/UsersPanel.vue';
import LogsHealth from './components/LogsHealth.vue';
import RecentStudies from './components/RecentStudies.vue';
import DashboardSummaryCards from './components/DashboardSummaryCards.vue';

const { siteUrl } = useAppConfig();
const appConfig = inject(APP_CONFIG_KEY, {});
const pageTitle = computed(() => appConfig?.translations?.dashboard ?? 'Dashboard');
const summaryTranslations = computed(() => appConfig?.translations ?? {});
const { loading, error, loadStats } = useDashboardApi();
const stats = ref(null);

onMounted(async () => {
  stats.value = await loadStats();
});
</script>

<style scoped>
.admin-dashboard-vapp.v-application {
  display: block;
  min-height: 0 !important;
  height: auto !important;
}
.admin-dashboard-vapp :deep(.v-application__wrap) {
  min-height: 0 !important;
}
.admin-dashboard-page {
  min-height: calc(100vh - 5rem);
  background-color: #eef2f7;
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
  font-size: 1.0625rem;
  font-weight: 700;
  letter-spacing: 0.015em;
  line-height: 1.4;
  padding: 12px 16px !important;
  min-height: 0 !important;
}

.dashboard-card .dashboard-card-title :deep(.v-btn) {
  font-size: 0.8125rem;
}

.dashboard-card {
  transition: box-shadow 0.2s;
}
.dashboard-card:hover {
  box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}

/* Summary stat strip: responsive columns (1 → 2 → 4) for narrow viewports & left column width. */
.dashboard-summary-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 10px;
  width: 100%;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
@media (min-width: 520px) {
  .dashboard-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
  }
}
@media (min-width: 1180px) {
  .dashboard-summary-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}
</style>
