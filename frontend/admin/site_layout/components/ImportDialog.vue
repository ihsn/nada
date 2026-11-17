<template>
  <v-dialog :model-value="modelValue" max-width="520" @update:model-value="close">
    <v-card class="sl-popup">
      <v-card-title class="text-subtitle-1">{{ t('sl_import_title', 'Import a site layout') }}</v-card-title>
      <v-card-text class="sl-stack">
        <template v-if="!result">
          <div class="sl-help">
            {{
              t(
                'sl_import_help',
                'Choose a .zip file exported from Site layout. The parts you import become drafts: check them in the preview, then publish. Nothing changes for visitors until you publish.'
              )
            }}
          </div>
          <v-file-input
            v-model="file"
            accept=".zip,application/zip"
            :label="t('sl_import_file', 'Package (.zip)')"
            prepend-icon=""
            prepend-inner-icon="mdi-folder-zip-outline"
            density="compact"
            variant="outlined"
            hide-details
            :loading="busy === 'inspect'"
            @update:model-value="inspect"
          />

          <template v-if="summary">
            <div class="sl-help">
              {{ t('sl_import_from', 'Exported %s · NADA %s · theme %s', exportedDate, summary.package.nada_version || '?', summary.package.theme || '?') }}
            </div>
            <v-alert v-for="(w, i) in summary.warnings" :key="i" type="warning" variant="tonal" density="compact">
              {{ packageMessage(t, w.code, w.detail) }}
            </v-alert>
            <div class="sl-field">
              <div class="sl-label">{{ t('sl_import_parts', 'Import') }}</div>
              <div class="sl-switches">
                <v-checkbox
                  v-for="p in available"
                  :key="p.value"
                  v-model="parts"
                  :value="p.value"
                  color="primary"
                  density="compact"
                  :hint="p.hint"
                  :persistent-hint="!!p.hint"
                  :hide-details="!p.hint"
                >
                  <template #label>{{ p.title }}</template>
                </v-checkbox>
              </div>
            </div>
            <div class="sl-help">
              {{ t('sl_import_not_included', 'Menu items, the site title and languages are not in the package; this site’s own are used.') }}
            </div>
          </template>
        </template>

        <template v-else>
          <v-alert type="success" variant="tonal" density="compact">
            {{ t('sl_imported', 'Imported as drafts: %s. Check the preview on each tab, then publish.', importedNames) }}
          </v-alert>
          <div v-if="result.warnings.length" class="sl-field">
            <div class="sl-label">{{ t('sl_import_notes', 'Notes') }}</div>
            <ul class="sl-help pl-4 mb-0">
              <li v-for="(w, i) in result.warnings" :key="i">
                <strong v-if="w.part">{{ partName(w.part) }}:</strong> {{ packageMessage(t, w.code, w.detail) }}
              </li>
            </ul>
          </div>
        </template>

        <v-alert v-if="error" type="error" variant="tonal" density="compact">
          {{ error }}
          <ul v-if="errorNotes.length" class="pl-4 mb-0 mt-1">
            <li v-for="(w, i) in errorNotes" :key="i">
              <strong v-if="w.part">{{ partName(w.part) }}:</strong> {{ packageMessage(t, w.code, w.detail) }}
            </li>
          </ul>
        </v-alert>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <template v-if="!result">
          <v-btn variant="text" @click="close(false)">{{ t('sl_cancel', 'Cancel') }}</v-btn>
          <v-btn
            color="primary"
            variant="flat"
            prepend-icon="mdi-upload"
            :disabled="!summary || !parts.length || !!busy"
            :loading="busy === 'import'"
            @click="runImport"
          >
            {{ t('sl_import_button', 'Import as drafts') }}
          </v-btn>
        </template>
        <v-btn v-else color="primary" variant="flat" @click="close(false)">{{ t('sl_close', 'Close') }}</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useSiteLayoutApi } from '../composables/useSiteLayoutApi';
import { packageMessage } from '../composables/packageMessages';

defineOptions({ name: 'SlImportDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  /** async (parts) => void: save unsaved edits of these parts before the import replaces their drafts */
  beforeImport: { type: Function, default: null },
});
const emit = defineEmits(['update:modelValue', 'imported']);

const { t } = useI18n();
const api = useSiteLayoutApi();

const file = ref(null);
const summary = ref(null);
const parts = ref([]);
const result = ref(null);
const busy = ref('');
const error = ref('');
const errorNotes = ref([]);

const ORDER = ['header', 'footer', 'styles'];

function partName(p) {
  return { header: t('sl_tab_header', 'Header'), footer: t('sl_tab_footer', 'Footer'), styles: t('sl_tab_styles', 'Styles') }[p] || p;
}

const chosenFile = computed(() => (Array.isArray(file.value) ? file.value[0] : file.value) || null);

const exportedDate = computed(() => {
  const raw = summary.value?.package?.exported;
  const d = raw ? new Date(raw) : null;
  return d && !Number.isNaN(d.getTime()) ? d.toLocaleDateString() : '?';
});

const available = computed(() => {
  const info = summary.value?.parts || {};
  return ORDER.filter((p) => info[p]).map((p) => {
    const notes = [];
    if (info[p].state === 'draft') notes.push(t('sl_import_part_draft', 'exported from a draft'));
    if (info[p].replaces_draft) notes.push(t('sl_import_replaces_draft', 'replaces your current draft'));
    return { value: p, title: partName(p), hint: notes.join(' · ') };
  });
});

const importedNames = computed(() => (result.value?.imported || []).map(partName).join(', '));

function reset() {
  file.value = null;
  summary.value = null;
  parts.value = [];
  result.value = null;
  busy.value = '';
  error.value = '';
  errorNotes.value = [];
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) reset();
  }
);

function close(v) {
  if (!v) emit('update:modelValue', false);
}

async function inspect() {
  summary.value = null;
  parts.value = [];
  error.value = '';
  const f = chosenFile.value;
  if (!f) return;
  busy.value = 'inspect';
  try {
    const data = await api.inspect(f);
    summary.value = { package: data.package || {}, parts: data.parts || {}, warnings: data.warnings || [] };
    parts.value = ORDER.filter((p) => summary.value.parts[p]);
  } catch (e) {
    error.value = packageMessage(t, e?.response?.data?.message || 'UPLOAD_FAILED');
  } finally {
    busy.value = '';
  }
}

async function runImport() {
  const f = chosenFile.value;
  if (!f || !parts.value.length) return;
  busy.value = 'import';
  error.value = '';
  errorNotes.value = [];
  try {
    const chosen = ORDER.filter((p) => parts.value.includes(p));
    if (props.beforeImport) await props.beforeImport(chosen);
    const data = await api.importPackage(f, chosen);
    result.value = { imported: data.imported || [], warnings: data.warnings || [] };
    emit('imported', result.value.imported);
  } catch (e) {
    const data = e?.response?.data || {};
    error.value = packageMessage(t, data.message || 'UPLOAD_FAILED');
    errorNotes.value = Array.isArray(data.warnings) ? data.warnings : [];
  } finally {
    busy.value = '';
  }
}
</script>
