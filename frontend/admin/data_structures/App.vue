<template>
  <v-app class="admin-data-structures-app d-flex flex-column ds-app" style="min-height: 100vh">
    <v-main class="flex-grow-1 d-flex flex-column ds-main" style="min-height: 0">
      <v-container fluid class="flex-grow-1 d-flex flex-column pb-6 px-4 px-sm-6" style="min-height: 0">
        <header class="ds-page-header d-flex align-center justify-space-between flex-wrap gap-3 flex-shrink-0 mb-5">
          <h1 class="text-h4 font-weight-semibold text-high-emphasis mb-0">Data structures</h1>
          <div v-if="showListToolbarActions" class="d-flex align-center gap-2 flex-shrink-0">
            <v-btn variant="tonal" size="small" prepend-icon="mdi-upload" @click="openImportDialog">Import SDMX XML</v-btn>
            <v-btn color="primary" size="small" prepend-icon="mdi-plus" @click="goCreate">Add data structure</v-btn>
          </div>
        </header>

        <div class="ds-flash-wrapper flex-shrink-0">
          <v-expand-transition>
            <v-alert
              v-if="message.text"
              :type="message.type"
              closable
              rounded="lg"
              variant="tonal"
              density="comfortable"
              class="mb-4 ds-app-flash-message"
              @click:close="message.text = ''"
            >
              {{ message.text }}
            </v-alert>
          </v-expand-transition>
        </div>

        <router-view class="admin-data-structures-router flex-grow-1 d-flex flex-column min-height-0" />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { reactive, provide, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

defineOptions({ name: 'AdminDataStructuresApp' });

const route = useRoute();
const router = useRouter();

const message = reactive({ text: '', type: 'info' });

const showListToolbarActions = computed(() => route.name === 'data-structures');

function openImportDialog() {
  router.push({
    name: 'data-structures',
    query: { ...route.query, openImport: String(Date.now()) },
  });
}

function goCreate() {
  router.push({ name: 'data-structure-create' });
}

function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);
</script>

<style scoped>
.ds-app {
  background: rgb(var(--v-theme-surface));
}
.ds-main {
  background: linear-gradient(
    180deg,
    rgba(var(--v-theme-primary), 0.04) 0,
    rgb(var(--v-theme-surface)) 160px
  );
}
/*
  Vuetify VAlert defaults to flex: 1 1 so it grows inside column flex layouts (fills space).
 */
.ds-flash-wrapper {
  flex: 0 0 auto;
  width: 100%;
}
.ds-app-flash-message {
  flex: 0 0 auto !important;
}
</style>
