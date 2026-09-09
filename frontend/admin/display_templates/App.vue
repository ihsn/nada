<template>
  <v-app class="admin-display-templates-app">
    <v-snackbar
      v-model="snackbar.show"
      :color="snackbarColor"
      :timeout="4500"
      location="top"
      multi-line
      max-width="560"
    >
      <span class="text-body-2">{{ snackbar.text }}</span>
    </v-snackbar>
    <v-main class="admin-display-templates-main">
      <v-container fluid :class="containerClass">
        <v-alert
          v-if="errorAlert.text"
          type="error"
          variant="tonal"
          density="compact"
          closable
          class="admin-display-templates-error mb-4"
          :class="errorAlertClass"
          @click:close="errorAlert.text = ''"
        >
          <span class="text-body-2">{{ errorAlert.text }}</span>
        </v-alert>
        <router-view class="admin-display-templates-route" />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, provide, watch } from 'vue';
import { useRoute } from 'vue-router';

defineOptions({ name: 'AdminDisplayTemplatesApp' });

const route = useRoute();
/** Full-bleed layout for display template detail (secondary header + body). */
const containerClass = computed(() =>
  route.name === 'display-template-detail' ? 'admin-display-templates-container--detail px-0 py-0' : '',
);

const errorAlertClass = computed(() =>
  route.name === 'display-template-detail' ? 'mx-4 mx-md-6' : '',
);

const snackbar = reactive({ show: false, text: '', type: 'info' });
const errorAlert = reactive({ text: '' });

const snackbarColor = computed(() => {
  const map = { success: 'success', warning: 'warning', info: 'info' };
  return map[snackbar.type] || 'info';
});

/** @param {string} text @param {'info'|'success'|'warning'|'error'} [type] */
function setMessage(text, type = 'info') {
  const value = String(text ?? '').trim();
  if (!value) return;
  if (type === 'error') {
    errorAlert.text = value;
    return;
  }
  snackbar.text = value;
  snackbar.type = type;
  snackbar.show = true;
}

provide('setMessage', setMessage);

const FULLHEIGHT_CLASS = 'admin-display-templates-fullheight';
watch(
  () => route.name,
  (name) => {
    const on = name === 'display-template-detail';
    document.documentElement.classList.toggle(FULLHEIGHT_CLASS, on);
    document.body.classList.toggle(FULLHEIGHT_CLASS, on);
  },
  { immediate: true },
);
onBeforeUnmount(() => {
  document.documentElement.classList.remove(FULLHEIGHT_CLASS);
  document.body.classList.remove(FULLHEIGHT_CLASS);
});
</script>

<style>
/* Full-height pipeline through the shared admin_vue.php shell.
   Only active while the display template manager is on the detail route — toggled
   via the .admin-display-templates-fullheight class on <html>/<body>. */
html.admin-display-templates-fullheight,
html.admin-display-templates-fullheight body.admin-vue-body {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
  background: #f0f2f5;
}
html.admin-display-templates-fullheight body.admin-vue-body .admin-vue-shell {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  box-sizing: border-box;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
html.admin-display-templates-fullheight body.admin-vue-body .admin-vue-shell > div {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
html.admin-display-templates-fullheight body.admin-vue-body .admin-vue-shell #content {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
html.admin-display-templates-fullheight body.admin-vue-body #admin-display-templates-app {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
</style>

<style scoped>
.admin-display-templates-app.v-application {
  --dt-page-bg: #f0f2f5;
  --dt-panel-bg: #ffffff;
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
  background: var(--dt-page-bg) !important;
}

.admin-display-templates-app :deep(.v-application__wrap),
.admin-display-templates-app :deep(.v-main),
.admin-display-templates-main {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
  background: var(--dt-page-bg) !important;
}

.admin-display-templates-app :deep(.v-container) {
  background: transparent;
}

.admin-display-templates-error {
  flex: 0 0 auto;
}

.admin-display-templates-container--detail {
  display: flex;
  flex-direction: column;
  flex: 1 1 0;
  height: 100%;
  max-height: 100%;
  min-height: 0;
  overflow: hidden;
}

.admin-display-templates-container--detail .admin-display-templates-route {
  flex: 1 1 0;
  min-height: 0;
}
</style>
