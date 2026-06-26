<template>
  <v-app class="admin-templates-app">
    <v-main class="admin-templates-main">
      <v-container fluid :class="containerClass">
        <v-alert
          v-if="message.text"
          :type="message.type"
          closable
          class="mb-4"
          :class="alertClass"
          @click:close="message.text = ''"
        >
          {{ message.text }}
        </v-alert>
        <router-view class="admin-templates-route" />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, provide, watch } from 'vue';
import { useRoute } from 'vue-router';

defineOptions({ name: 'AdminTemplatesApp' });

const route = useRoute();
/** Full-bleed layout for template editor (secondary header + body). */
const containerClass = computed(() =>
  route.name === 'template-detail' ? 'admin-templates-container--detail px-0 py-0' : '',
);

const alertClass = computed(() => (route.name === 'template-detail' ? 'mx-4 mx-md-6' : ''));

const message = reactive({ text: '', type: 'info' });

/** @param {string} text @param {'info'|'success'|'warning'|'error'} [type] */
function setMessage(text, type = 'info') {
  message.text = text;
  message.type = type;
}

provide('setMessage', setMessage);

const FULLHEIGHT_CLASS = 'admin-templates-fullheight';
watch(
  () => route.name,
  (name) => {
    const on = name === 'template-detail';
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
   Only active while the templates app is on the detail route — toggled
   via the .admin-templates-fullheight class on <html>/<body>. */
html.admin-templates-fullheight,
html.admin-templates-fullheight body.admin-vue-body {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
}
html.admin-templates-fullheight body.admin-vue-body .admin-vue-shell {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  box-sizing: border-box;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
html.admin-templates-fullheight body.admin-vue-body .admin-vue-shell > div {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
html.admin-templates-fullheight body.admin-vue-body .admin-vue-shell #content {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
html.admin-templates-fullheight body.admin-vue-body #admin-templates-app {
  flex: 1 1 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
</style>

<style scoped>
.admin-templates-app.v-application {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
  background: #f0f2f5;
}

.admin-templates-app :deep(.v-application__wrap),
.admin-templates-main {
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
}

.admin-templates-container--detail {
  display: flex;
  flex-direction: column;
  flex: 1 1 0;
  height: 100%;
  max-height: 100%;
  min-height: 0;
  overflow: hidden;
}

.admin-templates-container--detail .admin-templates-route {
  flex: 1 1 0;
  min-height: 0;
}
</style>
