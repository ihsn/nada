<template>
  <v-app class="admin-solr-app">
    <v-main class="admin-solr-page">
      <v-container fluid class="admin-solr-main px-2 pt-2 pb-6">
        <header class="admin-solr-page-header">
          <h1 class="admin-solr-page-title text-h5 font-weight-medium">Solr</h1>
        </header>

        <div class="admin-solr-layout">
          <aside class="admin-solr-nav">
            <div class="admin-solr-nav-section">Navigation</div>
            <v-list nav density="compact" class="pa-2 pt-0">
              <v-list-item
                v-for="item in navItems"
                :key="item.name"
                :to="item.to"
                :active="route.name === item.name"
                color="primary"
                rounded="lg"
              >
                <template #prepend>
                  <v-icon :icon="item.icon" size="20" />
                </template>
                <v-list-item-title class="text-body-2">{{ item.label }}</v-list-item-title>
              </v-list-item>
            </v-list>

            <v-divider />
            <div class="admin-solr-nav-section">Core operations</div>
            <div class="admin-solr-nav-actions">
              <v-btn
                size="small"
                color="success"
                variant="tonal"
                prepend-icon="mdi-check"
                :loading="busyAction === 'commit'"
                :disabled="!!busyAction"
                block
                @click="commit"
              >
                Commit
              </v-btn>
              <v-btn
                size="small"
                color="error"
                variant="tonal"
                prepend-icon="mdi-delete-outline"
                :disabled="!!busyAction || isAnyIndexing"
                block
                @click="clearDialog = true"
              >
                Clear index
              </v-btn>
              <v-btn
                size="small"
                color="info"
                variant="tonal"
                prepend-icon="mdi-heart-pulse"
                :loading="ping.loading"
                block
                @click="refreshPing"
              >
                Ping
              </v-btn>
            </div>

            <v-divider />
            <div class="admin-solr-nav-section">Control</div>
            <div class="admin-solr-nav-actions">
              <v-btn
                size="small"
                color="warning"
                variant="tonal"
                prepend-icon="mdi-stop"
                :disabled="!isAnyIndexing"
                block
                @click="stopAll"
              >
                Stop all
              </v-btn>
            </div>
          </aside>

          <div class="admin-solr-panels">
            <header class="admin-solr-content-header">
              <div>
                <h2 class="admin-solr-content-title">{{ route.meta.title || '' }}</h2>
                <p v-if="route.meta.subtitle" class="admin-solr-content-subtitle">
                  {{ route.meta.subtitle }}
                </p>
              </div>
            </header>
            <router-view />
          </div>
        </div>
      </v-container>
    </v-main>

    <v-dialog v-model="clearDialog" max-width="480">
      <v-card>
        <v-card-title class="text-h6">Clear Solr index?</v-card-title>
        <v-card-text>
          This deletes all documents in the configured core. Schema and the core itself stay.
          You will need to re-index studies, variables, and citations.
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="clearDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            variant="flat"
            :loading="busyAction === 'clear'"
            @click="onClear"
          >
            Clear index
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4500">
      {{ toast.message }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useSolrOps } from './composables/useSolrOps.js';
import './solr-layout.css';

defineOptions({ name: 'AdminSolrApp' });

const route = useRoute();
const {
  ping,
  toast,
  busyAction,
  isAnyIndexing,
  stopAll,
  refreshPing,
  refreshDashboard,
  commit,
  clearIndex,
} = useSolrOps();

const clearDialog = ref(false);

const navItems = [
  { name: 'overview', label: 'Dashboard', to: { name: 'overview' }, icon: 'mdi-view-dashboard-outline' },
  { name: 'index-studies', label: 'Index studies', to: { name: 'index-studies' }, icon: 'mdi-database-outline' },
  { name: 'index-variables', label: 'Index variables', to: { name: 'index-variables' }, icon: 'mdi-format-list-bulleted' },
  { name: 'index-by-survey', label: 'Index by survey', to: { name: 'index-by-survey' }, icon: 'mdi-poll' },
  { name: 'index-citations', label: 'Index citations', to: { name: 'index-citations' }, icon: 'mdi-format-quote-close' },
  { name: 'schema', label: 'Schema', to: { name: 'schema' }, icon: 'mdi-cog-outline' },
  { name: 'core', label: 'Core', to: { name: 'core' }, icon: 'mdi-server' },
];

async function onClear() {
  clearDialog.value = false;
  await clearIndex();
}

onMounted(() => {
  refreshDashboard();
});
</script>
