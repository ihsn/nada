<template>
  <div class="admin-sh-editor">
    <header v-if="server.loaded" class="admin-sh-header">
      <div class="admin-sh-source">
        <span class="admin-sh-source__label text-caption text-medium-emphasis">{{ t('sh_choose_source', 'Choose source') }}</span>
        <v-tooltip location="bottom" max-width="280">
          <template #activator="{ props: tipProps }">
            <v-btn
              v-bind="tipProps"
              icon="mdi-help-circle-outline"
              size="x-small"
              variant="text"
              class="admin-sh-source__help"
              :aria-label="t('sh_choose_source_help', 'Pick which option the published site header uses.')"
            />
          </template>
          <span>{{ t('sh_choose_source_help', 'Pick which option the published site header uses.') }}</span>
        </v-tooltip>
        <v-select
          v-model="form.source"
          :items="sourceItems"
          density="compact"
          variant="outlined"
          hide-details
          class="admin-sh-source__select"
          @update:model-value="onSiteSourceChange"
        />
      </div>
      <div class="admin-sh-toolbar">
        <div class="admin-sh-status" :class="`admin-sh-status--${statusKind}`">
          <v-icon :icon="statusIcon" size="16" />
          <span class="admin-sh-status__label">{{ statusLabel }}</span>
        </div>
        <div class="admin-sh-actions">
          <v-btn v-if="hasDraft" size="small" variant="text" :disabled="!!busy" :loading="busy === 'discard'" @click="dialog = 'discard'">
            {{ t('sh_discard', 'Discard draft') }}
          </v-btn>
          <v-menu>
            <template #activator="{ props: menuProps }">
              <v-btn v-bind="menuProps" size="small" variant="text" icon="mdi-dots-vertical" :title="t('sh_more', 'More')" />
            </template>
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-open-in-new" :title="t('sh_open_tab', 'Open the preview in a new tab')" :href="previewUrl" target="_blank" />
              <v-list-item
                prepend-icon="mdi-restore"
                :title="t('sh_reset', 'Reset to the theme file header')"
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
        {{ t('sh_forced_default', 'The site is set to always show the theme file header ($config[\'site_header_force_default\'] in config.php). Published builder or custom HTML will show after that setting is removed.') }}
      </v-alert>
      <v-alert v-else-if="server.hasLocalHeader" type="warning" variant="tonal" density="compact" class="mb-4">
        {{ t('sh_local_header_banner', 'header.local.php is present for this theme, so the public site always uses that file. Remove or rename it to let the builder or custom HTML apply.') }}
      </v-alert>
      <v-alert
        v-if="cacheIssue && form.source !== 'theme'"
        type="error"
        variant="tonal"
        density="compact"
        class="mb-4"
      >
        <div>{{ cacheIssue }}</div>
        <div v-if="server.cache.path" class="text-caption mt-1">
          {{ t('sh_cache_path', 'Cache file') }}: <code>{{ server.cache.path }}</code>
        </div>
      </v-alert>
      <v-alert
        v-if="uploadIssue && (form.source !== 'theme' || editorView !== 'theme')"
        type="error"
        variant="tonal"
        density="compact"
        class="mb-4"
      >
        <div>{{ uploadIssue }}</div>
        <div v-if="server.upload.path" class="text-caption mt-1">
          {{ t('sh_upload_path', 'Image folder') }}: <code>{{ server.upload.path }}</code>
        </div>
      </v-alert>
      <v-alert v-if="!server.fileInSync" type="warning" variant="tonal" density="compact" class="mb-4">
        {{ t('sh_file_out_of_sync', 'The published header is not live.') }}
        <template #append>
          <v-btn size="small" variant="text" :loading="busy === 'republish'" :disabled="!!busy" @click="republish">
            {{ t('sh_republish', 'Publish again') }}
          </v-btn>
        </template>
      </v-alert>

      <v-btn-toggle v-model="editorView" mandatory divided density="compact" variant="outlined" color="primary" class="sh-mode">
        <v-btn value="theme" prepend-icon="mdi-file-code-outline">{{ t('sh_source_theme', 'Theme file') }}</v-btn>
        <v-btn value="builder" prepend-icon="mdi-view-dashboard-edit-outline">{{ t('sh_mode_builder', 'Builder') }}</v-btn>
        <v-btn value="custom" prepend-icon="mdi-code-tags">{{ t('sh_mode_custom', 'Custom HTML') }}</v-btn>
      </v-btn-toggle>

      <v-expansion-panels v-model="open" multiple variant="accordion" class="sh-panels">
        <template v-if="editorView === 'theme'">
          <v-expansion-panel value="theme" data-panel="theme">
            <v-expansion-panel-title>
              <v-icon icon="mdi-file-code-outline" size="18" class="mr-2" />{{ t('sh_source_theme', 'Theme file') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-theme-file-panel /></v-expansion-panel-text>
          </v-expansion-panel>
        </template>
        <template v-else-if="editorView === 'builder'">
          <v-expansion-panel value="layout" data-panel="layout">
            <v-expansion-panel-title>
              <v-icon icon="mdi-view-compact-outline" size="18" class="mr-2" />{{ t('sh_layout', 'Layout') }}
              <span class="sh-panel-sub">{{ presetTitle }}</span>
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-preset-gallery /></v-expansion-panel-text>
          </v-expansion-panel>
          <v-expansion-panel v-for="r in rows" :key="r.id" :value="r.id" :data-panel="r.id">
            <v-expansion-panel-title>
              <v-icon :icon="r.icon" size="18" class="mr-2" />{{ r.label }}
              <span class="sh-panel-sub">{{ rowSummary(r.id) }}</span>
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-row-panel :row-id="r.id" /></v-expansion-panel-text>
          </v-expansion-panel>
        </template>
        <v-expansion-panel v-else value="html" data-panel="html">
          <v-expansion-panel-title>
            <v-icon icon="mdi-code-tags" size="18" class="mr-2" />{{ t('sh_html', 'Header HTML') }}
          </v-expansion-panel-title>
          <v-expansion-panel-text><sh-custom-html-panel /></v-expansion-panel-text>
        </v-expansion-panel>

        <template v-if="editorView !== 'theme'">
          <v-expansion-panel value="brand" data-panel="brand">
            <v-expansion-panel-title>
              <v-icon icon="mdi-card-account-details-outline" size="18" class="mr-2" />{{ t('sh_brand', 'Brand and logo') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-brand-panel /></v-expansion-panel-text>
          </v-expansion-panel>
          <v-expansion-panel value="background" data-panel="background">
            <v-expansion-panel-title>
              <v-icon icon="mdi-palette-outline" size="18" class="mr-2" />{{ t('sh_colors', 'Background and colours') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-background-panel /></v-expansion-panel-text>
          </v-expansion-panel>
          <v-expansion-panel value="login" data-panel="login">
            <v-expansion-panel-title>
              <v-icon icon="mdi-account-circle-outline" size="18" class="mr-2" />{{ t('sh_menu_login', 'Menu, login and languages') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-login-language-panel /></v-expansion-panel-text>
          </v-expansion-panel>
          <v-expansion-panel value="css" data-panel="css">
            <v-expansion-panel-title>
              <v-icon icon="mdi-language-css3" size="18" class="mr-2" />{{ t('sh_css', 'Extra CSS') }}
            </v-expansion-panel-title>
            <v-expansion-panel-text><sh-css-panel /></v-expansion-panel-text>
          </v-expansion-panel>
        </template>
      </v-expansion-panels>
    </template>

    <v-dialog :model-value="!!dialog" max-width="460" @update:model-value="(v) => !v && (dialog = '')">
      <v-card v-if="dialog === 'discard'">
        <v-card-title class="text-subtitle-1">{{ t('sh_discard_title', 'Discard the draft?') }}</v-card-title>
        <v-card-text>{{ t('sh_discard_text', 'Your unpublished changes are deleted and the editor goes back to the published header.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDiscard">{{ t('sh_discard', 'Discard draft') }}</v-btn>
        </v-card-actions>
      </v-card>
      <v-card v-else-if="dialog === 'reset'">
        <v-card-title class="text-subtitle-1">{{ t('sh_reset_title', 'Reset to the theme file header?') }}</v-card-title>
        <v-card-text>{{ t('sh_reset_text', 'The public site goes back to the theme PHP header right away, and your draft and published builder settings are deleted.') }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('sh_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmReset">{{ t('sh_reset_confirm', 'Use theme file') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.show" :color="toast.color" :timeout="toast.color === 'error' ? 7000 : 3000" location="bottom right">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { SLOTS, useSiteHeaderAutosave } from './composables/useSiteHeaderStore';
import ShPresetGallery from './components/PresetGallery.vue';
import ShRowPanel from './components/RowPanel.vue';
import ShBrandPanel from './components/BrandPanel.vue';
import ShBackgroundPanel from './components/BackgroundPanel.vue';
import ShLoginLanguagePanel from './components/LoginLanguagePanel.vue';
import ShCustomHtmlPanel from './components/CustomHtmlPanel.vue';
import ShCssPanel from './components/CssPanel.vue';
import ShThemeFilePanel from './components/ThemeFilePanel.vue';
import './site-header.css';

defineOptions({ name: 'AdminSiteHeaderEditor' });

const props = defineProps({
  active: { type: Boolean, default: true },
});

const {
  server,
  form,
  editorView,
  syncModeFromSource,
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
} = useSiteHeaderAutosave();
const { config } = useAppConfig();

const dialog = ref('');
const open = ref(['theme']);
const previewUrl = computed(() => (config.value?.previewBaseUrl ? `${config.value.previewBaseUrl}/header` : ''));

const sourceItems = computed(() => [
  { title: t('sh_source_theme', 'Theme file'), value: 'theme' },
  { title: t('sh_mode_builder', 'Builder'), value: 'builder' },
  { title: t('sh_mode_custom', 'Custom HTML'), value: 'custom' },
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
      return t('sh_saving', 'Saving draft…');
    case 'unsaved':
      return t('sh_unsaved', 'Unsaved changes');
    case 'draft':
      return t('sh_draft_saved', 'Draft saved · Not published');
    case 'theme':
      return t('sh_status_default', 'Theme file header');
    default:
      return publishedSourceLabel.value;
  }
});
const publishedSourceLabel = computed(() => {
  const src = form.value?.source;
  if (src === 'custom') return t('sh_status_published_custom', 'Published · Custom HTML');
  if (src === 'builder') return t('sh_status_published_builder', 'Published · Builder');
  return t('sh_status_published', 'Published');
});

const cacheIssue = computed(() => {
  if (server.cache?.ok !== false || !server.cache?.code) return '';
  return message(server.cache.code);
});

const uploadIssue = computed(() => {
  if (server.upload?.ok !== false || !server.upload?.code) return '';
  return message(server.upload.code);
});

const rows = computed(() => [
  { id: 'top', label: t('sh_row_top', 'Top bar'), icon: 'mdi-page-layout-header' },
  { id: 'main', label: t('sh_row_main', 'Main row'), icon: 'mdi-dock-top' },
  { id: 'bottom', label: t('sh_row_bottom', 'Bottom bar'), icon: 'mdi-page-layout-footer' },
]);

const presetTitle = computed(() => server.presets.find((p) => p.id === form.value?.preset)?.title || '');

function rowSummary(id) {
  const row = form.value.rows[id];
  if (!row.enabled) return t('sh_off', 'Off');
  const n = SLOTS.reduce((sum, s) => sum + row[s].length, 0);
  if (!n) return t('sh_empty', 'Empty');
  return n === 1 ? t('sh_one_item', '1 item') : t('sh_n_items', '%d items', n);
}

function openPanelsForView(view) {
  if (view === 'custom') open.value = ['html'];
  else if (view === 'builder') open.value = ['layout', 'main'];
  else open.value = ['theme'];
}

function onSiteSourceChange(source) {
  syncModeFromSource();
  editorView.value = source;
  openPanelsForView(source);
}

watch(editorView, (view, before) => {
  if (!view || view === before) return;
  openPanelsForView(view);
});

async function openRow(id) {
  if (editorView.value !== 'builder') return;
  if (!open.value.includes(id)) open.value = [...open.value, id];
  await nextTick();
  setTimeout(() => {
    document.querySelector(`[data-panel="${id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 220);
}

defineExpose({ openRow });

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
