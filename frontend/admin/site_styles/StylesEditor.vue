<template>
  <div class="admin-ss-editor">
    <header v-if="server.loaded" class="admin-ss-header">
      <div class="admin-ss-status" :class="`admin-ss-status--${statusKind}`">
        <v-icon :icon="statusIcon" size="16" />
        <span class="admin-ss-status__label">{{ statusLabel }}</span>
      </div>
      <div class="admin-ss-actions">
        <v-btn v-if="hasDraft" size="small" variant="text" :disabled="!!busy" :loading="busy === 'discard'" @click="dialog = 'discard'">
          {{ t('ss_discard', 'Discard draft') }}
        </v-btn>
        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn v-bind="menuProps" size="small" variant="text" icon="mdi-dots-vertical" :title="t('ss_more', 'More')" />
          </template>
          <v-list density="compact">
            <v-list-item prepend-icon="mdi-open-in-new" :title="t('ss_open_tab', 'Open the preview in a new tab')" :href="previewUrl" target="_blank" />
            <v-list-item
              prepend-icon="mdi-restore"
              :title="t('ss_reset', 'Clear published styles')"
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
          {{ t('ss_publish', 'Publish') }}
        </v-btn>
      </div>
    </header>

    <v-alert v-if="loadError" type="error" variant="tonal" class="mb-4">
      {{ loadError }}
      <template #append>
        <v-btn size="small" variant="text" @click="load">{{ t('ss_retry', 'Try again') }}</v-btn>
      </template>
    </v-alert>

    <div v-if="!server.loaded && !loadError" class="d-flex justify-center py-12">
      <v-progress-circular indeterminate color="primary" />
    </div>

    <template v-if="server.loaded && form">
      <v-alert v-if="server.forceDefault" type="warning" variant="tonal" density="compact" class="mb-4">
        {{
          t(
            'ss_forced_default',
            "The site is set to ignore published styles ($config['site_styles_force_default'] in config.php)."
          )
        }}
      </v-alert>
      <v-alert v-if="cacheIssue" type="error" variant="tonal" density="compact" class="mb-4">
        <div>{{ cacheIssue }}</div>
        <div v-if="server.cache.path" class="text-caption mt-1">
          Cache: <code>{{ server.cache.path }}</code>
        </div>
      </v-alert>
      <v-alert v-if="!server.fileInSync" type="warning" variant="tonal" density="compact" class="mb-4">
        {{ t('ss_file_out_of_sync', 'The published styles are not live.') }}
        <template #append>
          <v-btn size="small" variant="text" :loading="busy === 'republish'" :disabled="!!busy" @click="republish">
            {{ t('ss_republish', 'Publish again') }}
          </v-btn>
        </template>
      </v-alert>
      <v-alert v-if="warnings.includes('CSS_CLEANED')" type="warning" variant="tonal" density="compact" class="mb-4">
        {{
          t(
            'ss_css_cleaned',
            'Some CSS was removed: @import, expressions, backslash escapes and url() addresses on other sites are not allowed.'
          )
        }}
      </v-alert>

      <div class="sl-stack">
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{
            t(
              'ss_intro',
              'Optional CSS for the public site body — headings, links, and colors.'
            )
          }}
        </p>

        <div class="sl-field">
          <expandable-code-editor
            v-model="form.css"
            language="css"
            min-height="280px"
            :label="t('ss_css', 'Site CSS')"
            :limit="server.limits.css"
            :expand-label="t('ss_expand', 'Expand editor')"
            :format-label="t('ss_format', 'Format')"
            :done-label="t('ss_done', 'Done')"
            :expanded-hint="t('ss_expanded_hint', 'Editing in the expanded window…')"
          >
            <template #toolbar>
              <v-btn size="x-small" variant="text" :disabled="!server.exampleCss" @click="insertExample">
                {{ t('ss_example', 'Insert example') }}
              </v-btn>
            </template>
          </expandable-code-editor>
          <div class="sl-help">
            {{
              t(
                'ss_css_help',
                'Loaded after the theme stylesheets on every public page. Leave empty and publish to use the theme only.'
              )
            }}
          </div>
        </div>
      </div>
    </template>

    <v-dialog v-model="showDiscard" max-width="440">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('ss_discard_title', 'Discard the draft?') }}</v-card-title>
        <v-card-text>
          {{ t('ss_discard_text', 'Your unpublished changes are deleted and the editor goes back to the published styles.') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('ss_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDiscard">{{ t('ss_discard', 'Discard draft') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="showReset" max-width="440">
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('ss_reset_title', 'Clear published styles?') }}</v-card-title>
        <v-card-text>
          {{ t('ss_reset_text', 'The published CSS and any draft are removed. Visitors see the theme styles only.') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = ''">{{ t('ss_cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" @click="confirmReset">{{ t('ss_reset', 'Clear published styles') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.show" :color="toast.color" timeout="3500">{{ toast.message }}</v-snackbar>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSiteStylesStore } from './composables/useSiteStylesStore';
import ExpandableCodeEditor from '../shared/ExpandableCodeEditor.vue';
import './site-styles.css';

defineOptions({ name: 'AdminSiteStylesEditor' });

const props = defineProps({
  active: { type: Boolean, default: true },
});

const { config } = useAppConfig();
const {
  server,
  form,
  loadError,
  saving,
  busy,
  dirty,
  warnings,
  toast,
  hasDraft,
  canPublish,
  t,
  message,
  load,
  publish,
  republish,
  discard,
  reset,
  insertExample,
} = useSiteStylesStore();

const dialog = ref('');
const previewUrl = computed(() => (config.value?.previewBaseUrl ? `${config.value.previewBaseUrl}/styles` : ''));
const showDiscard = computed({
  get: () => dialog.value === 'discard',
  set: (v) => {
    dialog.value = v ? 'discard' : '';
  },
});
const showReset = computed({
  get: () => dialog.value === 'reset',
  set: (v) => {
    dialog.value = v ? 'reset' : '';
  },
});

const statusKind = computed(() => {
  if (saving.value) return 'saving';
  if (dirty.value) return 'unsaved';
  if (hasDraft.value) return 'draft';
  if (server.isDefault) return 'empty';
  return 'published';
});

const statusIcon = computed(() => {
  switch (statusKind.value) {
    case 'saving':
      return 'mdi-cloud-upload-outline';
    case 'unsaved':
      return 'mdi-pencil-outline';
    case 'draft':
      return 'mdi-file-document-edit-outline';
    case 'empty':
      return 'mdi-palette-outline';
    default:
      return 'mdi-check-circle-outline';
  }
});

const statusLabel = computed(() => {
  switch (statusKind.value) {
    case 'saving':
      return t('ss_status_saving', 'Saving draft…');
    case 'unsaved':
      return t('ss_status_unsaved', 'Unsaved changes');
    case 'draft':
      return t('ss_status_draft', 'Draft saved — not published yet');
    case 'empty':
      return t('ss_status_empty', 'Using theme styles only');
    default:
      return t('ss_status_published', 'Published');
  }
});

const cacheIssue = computed(() => {
  if (!server.cache || server.cache.ok) return '';
  return message(server.cache.code, server.cache.code);
});

async function confirmDiscard() {
  dialog.value = '';
  await discard();
}

async function confirmReset() {
  dialog.value = '';
  await reset();
}

watch(
  () => props.active,
  (on) => {
    if (on && !server.loaded && !loadError.value) load();
  }
);

onMounted(() => {
  if (props.active) load();
});

onBeforeUnmount(() => {
  /* store timers are module-level; leave them for reuse across tabs */
});
</script>
