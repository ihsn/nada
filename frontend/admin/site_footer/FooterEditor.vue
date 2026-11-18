<template>
  <div class="admin-sf-editor">
    <header v-if="server.loaded" class="admin-sf-header">
      <div class="admin-sf-source">
        <span class="admin-sf-source__label text-caption text-medium-emphasis">{{ t('sf_choose_source', 'Choose source') }}</span>
        <v-tooltip location="bottom" max-width="280">
          <template #activator="{ props: tipProps }">
            <v-btn
              v-bind="tipProps"
              icon="mdi-help-circle-outline"
              size="x-small"
              variant="text"
              class="admin-sf-source__help"
              :aria-label="t('sf_choose_source_help', 'Pick which option the published site footer uses.')"
            />
          </template>
          <span>{{ t('sf_choose_source_help', 'Pick which option the published site footer uses.') }}</span>
        </v-tooltip>
        <v-select
          v-model="form.source"
          :items="sourceItems"
          density="compact"
          variant="outlined"
          hide-details
          class="admin-sf-source__select"
          @update:model-value="onSiteSourceChange"
        />
      </div>
      <div class="admin-sf-toolbar">
        <div class="admin-sf-status" :class="`admin-sf-status--${statusKind}`">
          <v-icon :icon="statusIcon" size="16" />
          <span class="admin-sf-status__label">{{ statusLabel }}</span>
        </div>
        <div class="admin-sf-actions">
          <v-btn v-if="hasDraft" size="small" variant="text" :disabled="!!busy" :loading="busy === 'discard'" @click="dialog = 'discard'">
            {{ t('sf_discard', 'Discard draft') }}
          </v-btn>
          <v-menu>
            <template #activator="{ props: menuProps }">
              <v-btn v-bind="menuProps" size="small" variant="text" icon="mdi-dots-vertical" :title="t('sf_more', 'More')" />
            </template>
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-open-in-new" :title="t('sf_open_tab', 'Open the preview in a new tab')" :href="previewUrl" target="_blank" />
              <v-list-item
                prepend-icon="mdi-restore"
                :title="t('sf_reset', 'Reset to the theme file footer')"
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
            {{ t('sf_publish', 'Publish') }}
          </v-btn>
        </div>
      </div>
    </header>

    <v-alert v-if="loadError" type="error" variant="tonal" class="mb-4">
      {{ loadError }}
      <template #append>
        <v-btn size="small" variant="text" @click="load">{{ t('sf_retry', 'Try again') }}</v-btn>
      </template>
    </v-alert>

    <div v-if="!server.loaded && !loadError" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <template v-if="server.loaded && form">
      <v-alert v-if="server.forceDefault" type="warning" variant="tonal" density="compact" class="mb-4">
        {{
          t(
            'sf_forced_default',
            "The site is set to always show the theme file footer ($config['site_footer_force_default'] in config.php). Published custom HTML will show after that setting is removed."
          )
        }}
      </v-alert>
      <v-alert v-else-if="server.hasLocalFooter" type="warning" variant="tonal" density="compact" class="mb-4">
        {{
          t(
            'sf_local_footer_banner',
            'footer.local.php is present for this theme, so the public site always uses that file. Remove or rename it to let custom HTML apply.'
          )
        }}
      </v-alert>
      <v-alert v-if="cacheIssue && form.source !== 'theme'" type="error" variant="tonal" density="compact" class="mb-4">
        <div>{{ cacheIssue }}</div>
        <div v-if="server.cache.path" class="text-caption mt-1">
          Cache: <code>{{ server.cache.path }}</code>
        </div>
      </v-alert>
      <v-alert v-if="!server.fileInSync" type="warning" variant="tonal" density="compact" class="mb-4">
        {{ t('sf_file_out_of_sync', 'The published footer is not live.') }}
        <template #append>
          <v-btn size="small" variant="text" :loading="busy === 'republish'" :disabled="!!busy" @click="republish">
            {{ t('sf_republish', 'Publish again') }}
          </v-btn>
        </template>
      </v-alert>

      <v-btn-toggle v-model="editorView" mandatory divided density="compact" variant="outlined" color="primary" class="sf-mode">
        <v-btn value="theme" size="small" prepend-icon="mdi-file-code-outline">{{ t('sf_source_theme', 'Theme file') }}</v-btn>
        <v-btn value="custom" size="small" prepend-icon="mdi-code-tags">{{ t('sf_mode_custom', 'Custom HTML') }}</v-btn>
      </v-btn-toggle>

      <v-expansion-panels v-model="open" multiple variant="accordion" class="sf-panels">
        <v-expansion-panel v-if="editorView === 'theme'" value="theme">
          <v-expansion-panel-title>
            <v-icon icon="mdi-file-code-outline" size="18" class="mr-2" />{{ t('sf_source_theme', 'Theme file') }}
          </v-expansion-panel-title>
          <v-expansion-panel-text><sf-theme-file-panel /></v-expansion-panel-text>
        </v-expansion-panel>

        <template v-if="editorView === 'custom'">
          <v-expansion-panel value="html">
            <v-expansion-panel-title>
              <v-icon icon="mdi-code-tags" size="18" class="mr-2" />{{ t('sf_html', 'Footer HTML') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sf-custom-html-panel /></v-expansion-panel-text>
          </v-expansion-panel>
          <v-expansion-panel value="css">
            <v-expansion-panel-title>
              <v-icon icon="mdi-language-css3" size="18" class="mr-2" />{{ t('sf_css', 'Extra CSS') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sf-css-panel /></v-expansion-panel-text>
          </v-expansion-panel>
        </template>
      </v-expansion-panels>
    </template>

    <v-dialog v-model="showDiscard" max-width="440">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sf_discard_title', 'Discard the draft?') }}</v-card-title>
        <v-card-text>{{ t('sf_discard_text', 'Your unpublished changes are deleted and the editor goes back to the published footer.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('sf_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDiscard">{{ t('sf_discard', 'Discard draft') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showReset" max-width="440">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('sf_reset_title', 'Reset to the theme file footer?') }}</v-card-title>
        <v-card-text>{{ t('sf_reset_text', 'The published footer and any draft are removed. Visitors see the theme PHP footer again.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('sf_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmReset">{{ t('sf_reset', 'Reset to the theme file footer') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.show" :color="toast.color" timeout="3500">{{ toast.message }}</v-snackbar>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteFooterStore } from './composables/useSiteFooterStore';
import SfThemeFilePanel from './components/ThemeFilePanel.vue';
import SfCustomHtmlPanel from './components/CustomHtmlPanel.vue';
import SfCssPanel from './components/CssPanel.vue';
import './site-footer.css';

defineOptions({ name: 'AdminSiteFooterEditor' });

const props = defineProps({
  active: { type: Boolean, default: true },
});

const { config } = useAppConfig();
const {
  server,
  form,
  editorView,
  loadError,
  saving,
  busy,
  dirty,
  toast,
  hasDraft,
  canPublish,
  t,
  message,
  load,
  saveDraft,
  publish,
  republish,
  discard,
  reset,
} = useSiteFooterStore();

const dialog = ref('');
const open = ref(['theme']);
const previewUrl = computed(() => (config.value?.previewBaseUrl ? `${config.value.previewBaseUrl}/footer` : ''));

const sourceItems = computed(() => [
  { title: t('sf_source_theme', 'Theme file'), value: 'theme' },
  { title: t('sf_mode_custom', 'Custom HTML'), value: 'custom' },
]);

const statusKind = computed(() => {
  if (saving.value) return 'saving';
  if (dirty.value) return 'unsaved';
  if (hasDraft.value) return 'draft';
  if (server.isDefault || form.value?.source === 'theme') return 'theme';
  return 'published';
});
const statusIcon = computed(() => {
  switch (statusKind.value) {
    case 'saving':
      return 'mdi-cloud-sync-outline';
    case 'unsaved':
      return 'mdi-pencil-outline';
    case 'draft':
      return 'mdi-file-document-edit-outline';
    case 'theme':
      return 'mdi-file-code-outline';
    default:
      return 'mdi-check-circle-outline';
  }
});
const statusLabel = computed(() => {
  switch (statusKind.value) {
    case 'saving':
      return t('sf_saving', 'Saving draft…');
    case 'unsaved':
      return t('sf_unsaved', 'Unsaved changes');
    case 'draft':
      return t('sf_draft_saved', 'Draft saved · Not published');
    case 'theme':
      return t('sf_status_default', 'Theme file footer');
    default:
      return form.value?.source === 'custom'
        ? t('sf_status_published_custom', 'Published · Custom HTML')
        : t('sf_status_published', 'Published');
  }
});

const showDiscard = computed({
  get: () => dialog.value === 'discard',
  set: (v) => {
    if (!v) dialog.value = '';
  },
});
const showReset = computed({
  get: () => dialog.value === 'reset',
  set: (v) => {
    if (!v) dialog.value = '';
  },
});

const cacheIssue = computed(() => {
  if (server.cache.ok) return '';
  return message(server.cache.code);
});

function openPanelsForView(view) {
  if (view === 'custom') open.value = ['html', 'css'];
  else open.value = ['theme'];
}

function onSiteSourceChange(source) {
  editorView.value = source === 'custom' ? 'custom' : 'theme';
  openPanelsForView(editorView.value);
}

watch(editorView, (view, before) => {
  if (!view || view === before) return;
  openPanelsForView(view);
});

async function confirmDiscard() {
  dialog.value = '';
  await discard();
}
async function confirmReset() {
  dialog.value = '';
  await reset();
}

function onKey(e) {
  if (!props.active) return;
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
