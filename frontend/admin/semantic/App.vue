<template>
  <v-app class="admin-semantic-app">
    <v-main class="admin-semantic-page">
      <v-container fluid class="admin-semantic-main px-2 pt-2 pb-6">
        <header class="admin-semantic-page-header">
          <h1 class="admin-semantic-page-title text-h5 font-weight-medium">Semantic search</h1>
        </header>

        <div class="admin-semantic-layout">
          <aside class="admin-semantic-nav">
            <v-list nav density="compact" class="pa-2">
              <v-list-item
                v-for="item in navItems"
                :key="item.name"
                :to="item.to"
                :active="route.name === item.name"
                :color="item.color || 'primary'"
                rounded="lg"
              >
                <template #prepend>
                  <v-icon :icon="item.icon" size="20" />
                </template>
                <v-list-item-title class="text-body-2">{{ item.label }}</v-list-item-title>
                <template v-if="item.badge" #append>
                  <v-chip size="x-small" :color="item.badgeColor" variant="tonal">
                    {{ item.badge }}
                  </v-chip>
                </template>
              </v-list-item>
            </v-list>
          </aside>

          <div class="admin-semantic-panels">
            <header class="admin-semantic-content-header">
              <div>
                <h2 class="admin-semantic-content-title">{{ route.meta.title || '' }}</h2>
                <p v-if="route.meta.subtitle" class="admin-semantic-content-subtitle">
                  {{ route.meta.subtitle }}
                </p>
              </div>
              <div id="semantic-page-actions" class="admin-semantic-content-actions" />
            </header>
            <EngineStatusBanner />
            <SyncHealthBanner />
            <router-view />
          </div>
        </div>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useSemanticApi } from './composables/useSemanticApi.js';
import EngineStatusBanner from './components/EngineStatusBanner.vue';
import SyncHealthBanner from './components/SyncHealthBanner.vue';
import { useSyncHealth } from './composables/useSyncHealth.js';
import './semantic-layout.css';

defineOptions({ name: 'AdminSemanticApp' });

const route = useRoute();
const { getTypeBreakdown, listJobs } = useSemanticApi();
const errorCount = ref(0);
const runningJobs = ref(0);
// the change queue's counts, kept current while there is work (shared with the banner and the activity card)
const { status: syncStatus } = useSyncHealth();
const syncFailed = computed(() => Number(syncStatus.value?.queue?.failed) || 0);
const queuePending = computed(() => Number(syncStatus.value?.queue?.pending) || 0);

const navItems = computed(() => [
  { name: 'overview', label: 'Overview', to: { name: 'overview' }, icon: 'mdi-view-dashboard-outline' },
  {
    name: 'indexing',
    label: 'Indexing',
    to: { name: 'indexing' },
    icon: 'mdi-database-sync-outline',
    badge: errorCount.value || null,
    badgeColor: 'error',
  },
  {
    name: 'activity',
    label: 'Activity',
    to: { name: 'activity' },
    icon: 'mdi-format-list-checks',
    // a failed catalog edit needs attention; otherwise what is waiting in the change queue or running as a job
    badge: syncFailed.value || queuePending.value || runningJobs.value || null,
    badgeColor: syncFailed.value ? 'error' : 'primary',
  },
  { name: 'search', label: 'Test search', to: { name: 'search' }, icon: 'mdi-magnify' },
]);

async function loadNavBadges() {
  try {
    const data = await getTypeBreakdown('survey');
    errorCount.value = (data?.items || []).reduce((sum, row) => sum + (Number(row.errors) || 0), 0);
  } catch {
    /* nav badges are best-effort */
  }
  try {
    const data = await listJobs({ status: 'running', limit: 50 });
    runningJobs.value = (data?.jobs || []).length;
  } catch {
    /* nav badges are best-effort */
  }
}

onMounted(() => {
  loadNavBadges();
});
watch(() => route.name, loadNavBadges);
</script>
