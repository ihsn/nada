<template>
  <v-app class="admin-sl-app">
    <v-main class="admin-sl-page">
      <v-container fluid class="admin-sl-main px-2 pt-2 pb-6">
        <header class="admin-sl-header">
          <div>
            <h1 class="text-h5 font-weight-medium ma-0">{{ t('site_layout', 'Site layout') }}</h1>
            <div class="text-body-2 text-medium-emphasis">
              {{
                t(
                  'sl_intro',
                  'Header, footer, and optional site CSS.'
                )
              }}
            </div>
          </div>
          <div class="admin-sl-header__actions">
            <v-btn size="small" variant="text" prepend-icon="mdi-download" @click="exportOpen = true">
              {{ t('sl_export', 'Export') }}
            </v-btn>
            <v-btn size="small" variant="text" prepend-icon="mdi-upload" @click="importOpen = true">
              {{ t('sl_import', 'Import') }}
            </v-btn>
          </div>
        </header>

        <div
          class="sl-editor"
          :class="{ 'sl-editor--wide': (tab === 'header' && headerWide) || tab === 'styles' }"
        >
          <v-card class="sl-editor__settings" border flat>
            <v-tabs v-model="tab" color="primary" density="compact" class="admin-sl-tabs">
              <v-tab value="header">{{ t('sl_tab_header', 'Header') }}</v-tab>
              <v-tab value="footer">{{ t('sl_tab_footer', 'Footer') }}</v-tab>
              <v-tab value="styles">{{ t('sl_tab_styles', 'Styles') }}</v-tab>
            </v-tabs>

            <div class="sl-editor__body">
              <div v-show="tab === 'header'">
                <header-editor ref="headerEditor" :active="tab === 'header'" />
              </div>
              <div v-show="tab === 'footer'">
                <footer-editor :active="tab === 'footer'" />
              </div>
              <div v-show="tab === 'styles'">
                <styles-editor :active="tab === 'styles'" />
              </div>
            </div>
          </v-card>

          <div class="sl-editor__preview">
            <sh-preview-panel v-if="tab === 'header'" @edit-row="onEditRow" />
            <sf-preview-panel v-else-if="tab === 'footer'" />
            <ss-preview-panel v-else />
          </div>
        </div>
      </v-container>
    </v-main>

    <sl-export-dialog v-model="exportOpen" @done="notify" />
    <sl-import-dialog v-model="importOpen" :before-import="saveBeforeImport" @imported="reloadParts" />
    <v-snackbar v-model="toast.show" color="success" timeout="3000" location="bottom right">{{ toast.message }}</v-snackbar>
  </v-app>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useSiteHeaderStore } from '../site_header/composables/useSiteHeaderStore';
import { useSiteFooterStore } from '../site_footer/composables/useSiteFooterStore';
import { useSiteStylesStore } from '../site_styles/composables/useSiteStylesStore';
import SlExportDialog from './components/ExportDialog.vue';
import SlImportDialog from './components/ImportDialog.vue';
import HeaderEditor from '../site_header/HeaderEditor.vue';
import FooterEditor from '../site_footer/FooterEditor.vue';
import StylesEditor from '../site_styles/StylesEditor.vue';
import ShPreviewPanel from '../site_header/components/PreviewPanel.vue';
import SfPreviewPanel from '../site_footer/components/PreviewPanel.vue';
import SsPreviewPanel from '../site_styles/components/PreviewPanel.vue';
import './site-layout.css';

defineOptions({ name: 'AdminSiteLayoutApp' });

const { t } = useI18n();
const headerStore = useSiteHeaderStore();
const { editorView: headerEditorView } = headerStore;
const stores = { header: headerStore, footer: useSiteFooterStore(), styles: useSiteStylesStore() };

const exportOpen = ref(false);
const importOpen = ref(false);
const toast = reactive({ show: false, message: '' });

function notify(message) {
  toast.message = message;
  toast.show = true;
}

/** Save pending edits first, so a late autosave cannot overwrite the imported draft */
async function saveBeforeImport(parts) {
  for (const p of parts) {
    const s = stores[p];
    if (s && (s.dirty.value || s.saving.value)) await s.saveDraft();
  }
}

async function reloadParts(parts) {
  await Promise.all(parts.map((p) => stores[p]?.load()));
  if (parts.length) tab.value = parts[0];
}
const tab = ref(initialTab());
const headerEditor = ref(null);

const headerWide = computed(() => headerEditorView.value === 'custom');

function initialTab() {
  try {
    const q = new URLSearchParams(window.location.search).get('tab');
    if (q === 'footer' || q === 'header' || q === 'styles') return q;
    const hash = String(window.location.hash || '').replace(/^#/, '');
    if (hash === 'footer' || hash === 'header' || hash === 'styles') return hash;
  } catch (_) {
    /* ignore */
  }
  return 'header';
}

function syncUrl(value) {
  try {
    const url = new URL(window.location.href);
    if (value === 'header') url.searchParams.delete('tab');
    else url.searchParams.set('tab', value);
    url.hash = '';
    window.history.replaceState({}, '', url.toString());
  } catch (_) {
    /* ignore */
  }
}

function onEditRow(id) {
  headerEditor.value?.openRow?.(id);
}

watch(tab, syncUrl);

onMounted(() => syncUrl(tab.value));
</script>
