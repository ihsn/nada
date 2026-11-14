<template>
  <v-app class="admin-sh-app">
    <v-main class="admin-sh-page">
      <v-container fluid class="admin-sh-main px-2 pt-2 pb-6">
        <header class="admin-sh-header">
          <div>
            <h1 class="text-h5 font-weight-medium ma-0">{{ t('site_header', 'Site header') }}</h1>
            <div class="text-body-2 text-medium-emphasis">
              {{ t('sh_intro', 'The header at the top of every public page. Changes are saved as a draft; visitors see them only after you publish.') }}
            </div>
          </div>
          <v-spacer />
          <div v-if="server.loaded" class="admin-sh-actions">
            <span class="text-caption text-medium-emphasis mr-1">
              <template v-if="saving">{{ t('sh_saving', 'Saving draft…') }}</template>
              <template v-else-if="dirty">{{ t('sh_unsaved', 'Unsaved changes') }}</template>
              <template v-else-if="hasDraft">{{ t('sh_draft_saved', 'Draft saved') }}</template>
              <template v-else-if="server.isDefault">{{ t('sh_status_default', 'Showing the default header') }}</template>
              <template v-else>{{ t('sh_status_published', 'Published') }}</template>
            </span>
            <v-btn
              v-if="hasDraft"
              size="small"
              variant="text"
              :disabled="!!busy"
              :loading="busy === 'discard'"
              @click="dialog = 'discard'"
            >
              {{ t('sh_discard', 'Discard draft') }}
            </v-btn>
            <v-menu>
              <template #activator="{ props: menuProps }">
                <v-btn v-bind="menuProps" size="small" variant="text" icon="mdi-dots-vertical" :title="t('sh_more', 'More')" />
              </template>
              <v-list density="compact">
                <v-list-item
                  prepend-icon="mdi-restore"
                  :title="t('sh_reset', 'Reset to the default header')"
                  :disabled="server.isDefault && !hasDraft"
                  @click="dialog = 'reset'"
                />
              </v-list>
            </v-menu>
            <v-btn
              color="primary"
              variant="flat"
              size="small"
              prepend-icon="mdi-publish"
              :disabled="!canPublish || !!busy"
              :loading="busy === 'publish'"
              @click="publish"
            >
              {{ t('sh_publish', 'Publish') }}
            </v-btn>
          </div>
        </header>

        <v-alert v-if="loadError" type="error" variant="tonal" class="mb-4">
          {{ loadError }}
          <template #append>
            <v-btn size="small" variant="text" @click="load">{{ t('sh_retry', 'Try again') }}</v-btn>
          </template>
        </v-alert>

        <div v-if="!server.loaded && !loadError" class="d-flex justify-center py-12">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <template v-if="server.loaded && form">
          <v-alert v-if="server.forceDefault" type="warning" variant="tonal" density="compact" class="mb-4">
            {{ t('sh_forced_default', 'The site is set to always show the default header ($config[\'site_header_force_default\'] in config.php). Published changes will show after that setting is removed.') }}
          </v-alert>

          <sh-preview-panel class="mb-4" />

          <v-tabs color="primary" density="compact" class="mb-4 admin-sh-tabs">
            <v-tab :to="{ name: 'design' }" prepend-icon="mdi-palette-outline">
              {{ t('sh_tab_design', 'Design') }}
              <v-chip v-if="form.mode === 'preset'" size="x-small" color="primary" variant="tonal" class="ml-2">{{ t('sh_in_use', 'In use') }}</v-chip>
            </v-tab>
            <v-tab :to="{ name: 'html' }" prepend-icon="mdi-code-tags">
              {{ t('sh_tab_html', 'Custom HTML') }}
              <v-chip v-if="form.mode === 'custom'" size="x-small" color="primary" variant="tonal" class="ml-2">{{ t('sh_in_use', 'In use') }}</v-chip>
            </v-tab>
          </v-tabs>

          <router-view />
        </template>
      </v-container>
    </v-main>

    <v-dialog :model-value="!!dialog" max-width="460" @update:model-value="(v) => !v && (dialog = '')">
      <v-card v-if="dialog === 'discard'">
        <v-card-title class="text-subtitle-1">{{ t('sh_discard_title', 'Discard the draft?') }}</v-card-title>
        <v-card-text>{{ t('sh_discard_text', 'Your unpublished changes are deleted and the editor goes back to the published header.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" variant="flat" @click="confirmDiscard">{{ t('sh_discard', 'Discard draft') }}</v-btn>
        </v-card-actions>
      </v-card>
      <v-card v-else-if="dialog === 'reset'">
        <v-card-title class="text-subtitle-1">{{ t('sh_reset_title', 'Reset to the default header?') }}</v-card-title>
        <v-card-text>{{ t('sh_reset_text', 'The public site goes back to the standard NADA header right away, and your draft and published settings are deleted.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmReset">{{ t('sh_reset_confirm', 'Reset') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.show" :color="toast.color" :timeout="toast.color === 'error' ? 7000 : 3000" location="bottom right">
      {{ toast.message }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useSiteHeaderAutosave } from './composables/useSiteHeaderStore';
import ShPreviewPanel from './components/PreviewPanel.vue';
import './site-header.css';

defineOptions({ name: 'AdminSiteHeaderApp' });

const {
  server,
  form,
  loadError,
  saving,
  busy,
  dirty,
  toast,
  hasDraft,
  canPublish,
  t,
  load,
  saveDraft,
  publish,
  discard,
  reset,
} = useSiteHeaderAutosave();

const dialog = ref('');

async function confirmDiscard() {
  dialog.value = '';
  await discard();
}

async function confirmReset() {
  dialog.value = '';
  await reset();
}

function onKey(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    saveDraft();
  }
}

function onBeforeUnload(e) {
  if (dirty.value || saving.value) {
    e.preventDefault();
    e.returnValue = '';
  }
}

onMounted(() => {
  load();
  window.addEventListener('keydown', onKey);
  window.addEventListener('beforeunload', onBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey);
  window.removeEventListener('beforeunload', onBeforeUnload);
});
</script>
