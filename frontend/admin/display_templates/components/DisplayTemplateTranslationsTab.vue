<template>
  <div class="dt-translations">
    <div class="dt-trans-sticky">
      <div class="d-flex flex-wrap align-center justify-space-between ga-3">
        <div class="text-h6 font-weight-semibold">Translations</div>
        <div class="d-flex flex-wrap align-center ga-2">
          <v-btn size="small" variant="tonal" prepend-icon="mdi-plus" @click="addDlg = true">Add language</v-btn>
          <v-btn
            size="small"
            color="primary"
            variant="flat"
            prepend-icon="mdi-content-save"
            :disabled="!canSave"
            :loading="saving"
            @click="save"
          >
            Save translations<span class="font-weight-regular dt-dirty-marker" :class="{ 'is-dirty': dirty }"> *</span>
          </v-btn>
        </div>
      </div>

      <v-alert v-if="error" type="error" variant="tonal" rounded="lg" class="mt-3" closable @click:close="error = ''">
        {{ error }}
      </v-alert>

      <div v-if="overlayLangs.length" class="dt-trans-toolbar d-flex flex-wrap align-center ga-3">
        <v-select
          :model-value="activeLang"
          :items="overlayLangItems"
          item-title="title"
          item-value="value"
          placeholder="Language"
          variant="outlined"
          density="compact"
          hide-details
          class="dt-lang-select"
          @update:model-value="onActiveLangChange"
        />
        <v-text-field
          v-model="query"
          placeholder="Filter"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          prepend-inner-icon="mdi-magnify"
          class="dt-filter"
        />
        <v-chip size="small" variant="tonal">{{ translatedCount }} / {{ rows.length }} translated</v-chip>
        <v-btn
          size="small"
          variant="text"
          color="error"
          prepend-icon="mdi-delete-outline"
          :disabled="!activeLang"
          @click="removeDlg = true"
        >
          Remove {{ activeLang }}
        </v-btn>
      </div>

      <v-alert v-else type="info" variant="tonal" rounded="lg" class="mt-3">
        Add a language to translate labels. The primary language ({{ primaryLabel }}) is edited on the layout.
      </v-alert>

      <v-progress-linear v-if="loading" class="mt-3" indeterminate color="primary" rounded />
    </div>

    <div class="dt-trans-body">
      <v-table v-if="activeLang && filteredRows.length" density="compact" class="dt-trans-table">
        <thead>
          <tr>
            <th class="dt-col-source">Source ({{ primaryLang }})</th>
            <th>Translation ({{ activeLang }})</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredRows" :key="row.key">
            <td class="dt-col-source">
              <div>{{ row.title || '—' }}</div>
              <div class="dt-key">{{ row.key }}</div>
            </td>
            <td>
              <v-text-field
                :model-value="draft[row.key] || ''"
                variant="outlined"
                density="compact"
                hide-details
                @update:model-value="onDraft(row.key, $event)"
              />
            </td>
          </tr>
        </tbody>
      </v-table>

      <div v-else-if="activeLang && !loading" class="text-body-2 text-medium-emphasis pa-4">
        No keys match this filter.
      </div>
    </div>

    <v-dialog v-model="addDlg" max-width="480">
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-6 px-6">Add language</v-card-title>
        <v-card-text class="px-6">
          <v-autocomplete
            v-model="addLang"
            :items="addLangItems"
            item-title="title"
            item-value="value"
            label="Language"
            variant="outlined"
            density="comfortable"
            hide-details
            auto-select-first
          />
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="addDlg = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :disabled="!addLang" :loading="adding" @click="addLanguage">
            Add
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="removeDlg" max-width="440">
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-6 px-6">Remove language?</v-card-title>
        <v-card-text class="px-6">
          Translations for this language will be removed.
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="removeDlg = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="removing" @click="removeLanguage">Remove</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { collectTranslationRows } from '../utils/displayTemplateTree';
import { useDisplayTemplatesApi } from '../composables/useDisplayTemplatesApi';

defineOptions({ name: 'DisplayTemplateTranslationsTab' });

const props = defineProps({
  uid: { type: String, required: true },
  templateRoot: { type: Object, default: () => ({}) },
  primaryLang: { type: String, default: 'en' },
});

const emit = defineEmits(['dirty', 'saved', 'languages-updated']);

const { fetchTranslations, addTranslationLang, saveTranslationLang, removeTranslationLang } = useDisplayTemplatesApi();

const loading = ref(false);
const saving = ref(false);
const adding = ref(false);
const removing = ref(false);
const error = ref('');
const query = ref('');
const activeLang = ref('');
const overlays = ref(/** @type {Record<string, Record<string, string>>} */ ({}));
const isoLanguages = ref(/** @type {Record<string, { name?: string, display?: string }>} */ ({}));
const draft = ref(/** @type {Record<string, string>} */ ({}));
const dirty = ref(false);
const addDlg = ref(false);
const addLang = ref('');
const removeDlg = ref(false);

const primaryLang = computed(() => String(props.primaryLang || 'en').toLowerCase());

const rows = computed(() => collectTranslationRows(props.templateRoot));

const overlayLangs = computed(() =>
  Object.keys(overlays.value)
    .map((code) => String(code).toLowerCase())
    .filter((code) => code && code !== primaryLang.value)
    .sort()
);

const overlayLangItems = computed(() =>
  overlayLangs.value.map((code) => ({ value: code, title: langLabel(code) }))
);

const addLangItems = computed(() => {
  const used = new Set([primaryLang.value, ...overlayLangs.value]);
  return Object.keys(isoLanguages.value)
    .filter((code) => !used.has(code))
    .sort()
    .map((code) => ({ value: code, title: langLabel(code) }));
});

const primaryLabel = computed(() => langLabel(primaryLang.value));

const filteredRows = computed(() => {
  const q = String(query.value || '').trim().toLowerCase();
  if (!q) return rows.value;
  return rows.value.filter(
    (row) =>
      row.key.toLowerCase().includes(q) ||
      row.title.toLowerCase().includes(q) ||
      String(draft.value[row.key] || '').toLowerCase().includes(q)
  );
});

const translatedCount = computed(() =>
  rows.value.filter((row) => String(draft.value[row.key] || '').trim() !== '').length
);

const canSave = computed(() => dirty.value && !!activeLang.value);

function langLabel(code) {
  const info = isoLanguages.value[code];
  const name = info?.display || info?.name || code;
  return `${name} (${code})`;
}

function applyBundle(bundle) {
  overlays.value = bundle?.overlays && typeof bundle.overlays === 'object' ? { ...bundle.overlays } : {};
  isoLanguages.value =
    bundle?.iso_languages && typeof bundle.iso_languages === 'object' ? bundle.iso_languages : {};
  const langs = overlayLangs.value;
  if (!langs.includes(activeLang.value)) {
    activeLang.value = langs[0] || '';
  }
  hydrateDraft();
  emit('languages-updated', Array.isArray(bundle?.languages) ? bundle.languages : [primaryLang.value]);
}

function hydrateDraft() {
  const map = overlays.value[activeLang.value] || {};
  const next = {};
  for (const row of rows.value) {
    next[row.key] = map[row.key] != null ? String(map[row.key]) : '';
  }
  draft.value = next;
  setDirty(false);
}

function confirmDiscard() {
  if (!dirty.value) return true;
  return window.confirm('You have unsaved changes. Leave this page?');
}

function setDirty(value) {
  dirty.value = !!value;
  emit('dirty', dirty.value);
}

function onDraft(key, value) {
  draft.value = { ...draft.value, [key]: value };
  setDirty(true);
}

function onActiveLangChange(next) {
  const lang = String(next || '');
  if (lang === activeLang.value) return;
  if (!confirmDiscard()) return;
  activeLang.value = lang;
}

async function load() {
  if (!props.uid) return;
  loading.value = true;
  error.value = '';
  try {
    const bundle = await fetchTranslations(props.uid);
    applyBundle(bundle);
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    loading.value = false;
  }
}

async function addLanguage() {
  if (!addLang.value) return;
  adding.value = true;
  error.value = '';
  try {
    const bundle = await addTranslationLang(props.uid, addLang.value);
    applyBundle(bundle);
    activeLang.value = addLang.value;
    addDlg.value = false;
    addLang.value = '';
    emit('saved');
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    adding.value = false;
  }
}

async function save() {
  if (!canSave.value) return;
  saving.value = true;
  error.value = '';
  try {
    const translations = {};
    for (const [key, value] of Object.entries(draft.value)) {
      const text = String(value || '').trim();
      if (text) translations[key] = text;
    }
    const bundle = await saveTranslationLang(props.uid, activeLang.value, translations);
    applyBundle(bundle);
    emit('saved');
    setDirty(false);
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    saving.value = false;
  }
}

async function removeLanguage() {
  if (!activeLang.value) return;
  removing.value = true;
  error.value = '';
  try {
    const bundle = await removeTranslationLang(props.uid, activeLang.value);
    applyBundle(bundle);
    removeDlg.value = false;
    emit('saved');
    setDirty(false);
  } catch (e) {
    error.value = e?.message || String(e);
  } finally {
    removing.value = false;
  }
}

watch(
  () => props.uid,
  () => {
    activeLang.value = '';
    overlays.value = {};
    draft.value = {};
    setDirty(false);
    load();
  },
  { immediate: true }
);

watch(activeLang, () => {
  hydrateDraft();
});

watch(
  () => rows.value.map((row) => row.key).join('\n'),
  () => {
    if (!activeLang.value) return;
    const map = overlays.value[activeLang.value] || {};
    const next = { ...draft.value };
    for (const row of rows.value) {
      if (!(row.key in next)) {
        next[row.key] = map[row.key] != null ? String(map[row.key]) : '';
      }
    }
    draft.value = next;
  }
);
</script>

<style scoped>
.dt-translations {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
}
.dt-trans-sticky {
  flex: 0 0 auto;
  z-index: 2;
  background: var(--dt-panel-bg, #fff);
  padding-bottom: 4px;
}
.dt-trans-toolbar {
  margin: 16px 0 12px;
}
.dt-trans-body {
  flex: 1 1 0;
  min-height: 0;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.dt-lang-select {
  max-width: 220px;
}
.dt-filter {
  max-width: 220px;
}
.dt-trans-table :deep(th) {
  position: sticky;
  top: 0;
  z-index: 1;
  white-space: nowrap;
  background: var(--dt-panel-bg, #fff);
}
.dt-col-source {
  width: 42%;
  vertical-align: top;
}
.dt-key {
  margin-top: 2px;
  font-size: 0.75rem;
  line-height: 1.35;
  color: rgba(var(--v-theme-on-surface), 0.45);
  word-break: break-word;
}
.dt-dirty-marker {
  opacity: 0;
}
.dt-dirty-marker.is-dirty {
  opacity: 1;
}
</style>
