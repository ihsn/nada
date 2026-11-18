<template>
  <div class="cit-stack">
    <v-breadcrumbs :items="breadcrumbItems" class="cit-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center flex-wrap ga-2">
      <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-0 flex-grow-1">
        {{ isEdit ? t('edit_citation', 'Edit citation') : t('add_new_citation', 'Add new citation') }}
      </h1>
      <v-btn variant="text" size="small" prepend-icon="mdi-arrow-left" :to="{ name: 'citations-list' }">
        {{ t('cit_all_citations', 'All citations') }}
      </v-btn>
      <v-btn
        v-if="isEdit && citation && citation.published"
        variant="outlined"
        size="small"
        prepend-icon="mdi-open-in-new"
        :href="`${publicBase}${citation.id}`"
        target="_blank"
        rel="noopener"
      >
        {{ t('cit_view_public', 'View on the site') }}
      </v-btn>
      <v-btn v-if="isEdit && can.delete" variant="outlined" size="small" color="error" prepend-icon="mdi-delete" :disabled="saving" @click="remove">
        {{ t('delete', 'Delete') }}
      </v-btn>
      <v-btn v-if="!readOnly" color="primary" size="small" prepend-icon="mdi-content-save" :loading="saving" :disabled="loading" @click="save">
        {{ t('save', 'Save') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" density="compact">{{ loadError }}</v-alert>
    <v-alert v-if="justCreated" type="success" density="compact" closable>
      {{ t('cit_created', 'Citation created. You can now attach a file and check the link.') }}
    </v-alert>
    <v-alert v-if="saveError" type="error" density="compact" closable @click:close="saveError = ''">{{ saveError }}</v-alert>
    <v-alert v-if="readOnly && ready" type="info" variant="tonal" density="compact">
      {{ t('cit_read_only', 'You can view this citation but not change it.') }}
    </v-alert>
    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <v-form v-if="ready" :disabled="readOnly" @submit.prevent="save">
      <v-card v-if="!isEdit && crossrefOn && !readOnly" class="pa-4 mb-4" elevation="1">
        <h2 class="cit-section-title">{{ t('cit_fill_from_doi', 'Have a DOI? Fill the form automatically') }}</h2>
        <div class="d-flex ga-2">
          <v-text-field
            v-model="quickDoi"
            density="compact"
            variant="outlined"
            hide-details
            :placeholder="t('cit_doi_placeholder', '10.1000/xyz123 or https://doi.org/...')"
            prepend-inner-icon="mdi-identifier"
            @keyup.enter.prevent="lookup(quickDoi)"
          />
          <v-btn color="primary" variant="flat" :loading="lookupBusy" :disabled="!quickDoi" @click="lookup(quickDoi)">
            {{ t('cit_look_up', 'Look up') }}
          </v-btn>
        </div>
        <v-alert v-if="lookupError" type="error" density="compact" class="mt-3">{{ lookupError }}</v-alert>
      </v-card>

      <v-row>
        <v-col cols="12" lg="8">
          <div class="cit-stack">
            <v-card class="pa-4" elevation="1">
              <v-row dense>
                <v-col cols="12" md="6">
                  <div class="cit-field-label">{{ t('select_citation_type', 'Citation type') }}</div>
                  <v-select v-model="doc.type" :items="typeItems" density="compact" variant="outlined" hide-details />
                </v-col>
              </v-row>
              <v-alert v-if="!typeDef" type="info" variant="tonal" density="compact" class="mt-3">
                {{ t('cit_unknown_type', 'This citation has the type "%s", which has no special form. The usual fields are shown; saving keeps all its data.', doc.type) }}
              </v-alert>

              <div class="cit-field-label mt-4">{{ t('title', 'Title') }} *</div>
              <v-text-field
                v-model="doc.title"
                density="compact"
                variant="outlined"
                hide-details="auto"
                maxlength="1000"
                :error-messages="errors.title ? [errors.title] : []"
              />

              <v-row dense class="mt-2">
                <v-col v-for="f in fieldDefs" :key="f.name" cols="12" :md="fieldCols(f)">
                  <div class="cit-field-label">{{ f.label }}</div>
                  <CslDateInput v-if="f.input === 'date'" :model-value="doc[f.name]" @update:model-value="(v) => setField(f.name, v)" />
                  <v-textarea
                    v-else-if="f.input === 'textarea'"
                    :model-value="doc[f.name]"
                    rows="3"
                    auto-grow
                    density="compact"
                    variant="outlined"
                    hide-details="auto"
                    @update:model-value="(v) => setField(f.name, v)"
                  />
                  <v-text-field
                    v-else
                    :model-value="doc[f.name]"
                    density="compact"
                    variant="outlined"
                    hide-details="auto"
                    :error-messages="errors[f.name] ? [errors[f.name]] : []"
                    @update:model-value="(v) => setField(f.name, v)"
                  >
                    <template v-if="f.name === 'DOI' && crossrefOn && !readOnly" #append-inner>
                      <v-btn
                        size="x-small"
                        variant="text"
                        prepend-icon="mdi-cloud-search-outline"
                        :loading="lookupBusy"
                        :disabled="!doc.DOI"
                        @click.stop="lookup(doc.DOI)"
                      >
                        {{ t('cit_look_up', 'Look up') }}
                      </v-btn>
                    </template>
                  </v-text-field>
                </v-col>
              </v-row>
              <v-alert v-if="lookupError && isEdit" type="error" density="compact" class="mt-2">{{ lookupError }}</v-alert>

              <v-divider class="my-4" />
              <div v-for="role in roles" :key="role.name" class="mb-4">
                <NameListEditor
                  :model-value="doc[role.name] || []"
                  :label="role.label"
                  @update:model-value="(v) => setField(role.name, v)"
                />
              </div>

              <v-divider class="my-4" />
              <v-row dense>
                <v-col v-for="f in commonDefs" :key="f.name" cols="12" :md="f.input === 'language' ? 6 : 12">
                  <div class="cit-field-label">{{ f.label }}</div>
                  <v-select
                    v-if="f.input === 'language'"
                    :model-value="doc[f.name]"
                    :items="languageItems"
                    density="compact"
                    variant="outlined"
                    hide-details
                    clearable
                    @update:model-value="(v) => setField(f.name, v)"
                  />
                  <v-textarea
                    v-else
                    :model-value="doc[f.name]"
                    :rows="f.name === 'abstract' ? 4 : 2"
                    auto-grow
                    density="compact"
                    variant="outlined"
                    hide-details
                    @update:model-value="(v) => setField(f.name, v)"
                  />
                </v-col>
              </v-row>
            </v-card>

            <v-card class="pa-4" elevation="1">
              <h2 class="cit-section-title">{{ t('cit_related_studies', 'Related studies') }}</h2>
              <StudyPicker v-model="studies" :disabled="readOnly" />
              <div v-if="errors.studies" class="text-error text-body-2 mt-2">{{ errors.studies }}</div>
            </v-card>

            <AttachmentCard
              v-if="isEdit && citation && !readOnly"
              :citation-id="citation.id"
              :attachment="citation.attachment"
              :types="options.attachment_types || []"
              :max-mb="options.max_upload_mb || 0"
              @changed="onAttachmentChanged"
            />
          </div>
        </v-col>

        <v-col cols="12" lg="4">
          <div class="cit-stack">
            <v-card class="pa-4" elevation="1">
              <h2 class="cit-section-title">{{ t('cit_publishing', 'Publishing and review') }}</h2>
              <v-switch
                v-model="published"
                color="success"
                density="compact"
                hide-details
                :label="published ? t('published-status-1', 'Published') : t('published-status-0', 'Draft')"
              />
              <div class="cit-muted mb-3">{{ t('cit_draft_hint', 'Drafts are only visible to administrators.') }}</div>

              <div class="cit-field-label">{{ t('flag_entry_as', 'Flag') }}</div>
              <v-select
                v-model="flag"
                :items="flagItems"
                density="compact"
                variant="outlined"
                hide-details="auto"
                :error-messages="errors.flag ? [errors.flag] : []"
              />
              <a v-if="can.manage_flags && codelistsUrl" class="cit-muted" :href="codelistsUrl" target="_blank" rel="noopener">
                {{ t('cit_manage_flags', 'Manage the list of flags') }}
              </a>

              <div class="cit-field-label mt-4">{{ t('notes', 'Notes (visible only to administrators)') }}</div>
              <v-textarea v-model="notes" rows="3" auto-grow density="compact" variant="outlined" hide-details />
            </v-card>

            <v-card v-if="duplicates.length" class="pa-4" elevation="1">
              <h2 class="cit-section-title">{{ t('cit_similar', 'Similar citations') }}</h2>
              <v-list density="compact" lines="two" class="pa-0">
                <v-list-item
                  v-for="d in duplicates"
                  :key="d.id"
                  :title="d.title"
                  :subtitle="[d.authors_text, d.pub_year, d.published ? '' : t('published-status-0', 'Draft')].filter(Boolean).join(' · ')"
                  :to="{ name: 'citation-edit', params: { id: d.id } }"
                  target="_blank"
                />
              </v-list>
            </v-card>

            <v-card class="pa-4" elevation="1">
              <h2 class="cit-section-title">{{ t('cit_preview', 'Preview') }}</h2>
              <!-- HTML-escaped by the server (Chicago_citation::format_escaped) -->
              <div v-if="previewHtml" class="cit-preview" v-html="previewHtml" />
              <div v-else class="cit-muted">{{ t('cit_preview_empty', 'Enter a title to see the formatted citation.') }}</div>
            </v-card>

            <v-card v-if="isEdit && citation && doc.URL" class="pa-4" elevation="1">
              <h2 class="cit-section-title">{{ t('cit_link_status', 'Link status') }}</h2>
              <div class="d-flex align-center ga-2 flex-wrap">
                <v-chip size="small" :color="linkColor" variant="flat">{{ linkText }}</v-chip>
                <span v-if="citation.url_status_time" class="cit-muted">{{ formatDate(citation.url_status_time, true) }}</span>
              </div>
              <div class="mt-2">
                <v-btn
                  size="small"
                  variant="outlined"
                  prepend-icon="mdi-link-variant"
                  :loading="checkBusy"
                  :disabled="readOnly || urlChanged"
                  @click="checkLink"
                >
                  {{ t('cit_check_link', 'Check the link now') }}
                </v-btn>
              </div>
              <div v-if="urlChanged" class="cit-muted mt-1">{{ t('cit_save_url_first', 'Save the changed URL first.') }}</div>
              <v-alert v-if="checkError" type="error" density="compact" class="mt-2">{{ checkError }}</v-alert>
            </v-card>

            <v-card v-if="isEdit && citation" class="pa-4" elevation="1">
              <h2 class="cit-section-title">{{ t('cit_record', 'Record') }}</h2>
              <div class="cit-muted">
                {{ t('cit_created_on', 'Created') }} {{ formatDate(citation.created) }}
                <span v-if="citation.created_by_user"> {{ t('cit_by', 'by') }} {{ citation.created_by_user }}</span>
              </div>
              <div class="cit-muted">
                {{ t('cit_modified_on', 'Modified') }} {{ formatDate(citation.changed) }}
                <span v-if="citation.changed_by_user"> {{ t('cit_by', 'by') }} {{ citation.changed_by_user }}</span>
              </div>
              <div class="cit-muted">UUID: {{ citation.uuid }}</div>
            </v-card>
          </div>
        </v-col>
      </v-row>
    </v-form>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, toRaw, watch } from 'vue';
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import $dialog from '@/shared/composables/dialog';
import StudyPicker from '@/shared/components/StudyPicker.vue';
import CslDateInput from '../components/CslDateInput.vue';
import NameListEditor from '../components/NameListEditor.vue';
import AttachmentCard from '../components/AttachmentCard.vue';
import { useCitationsApi, apiErrorMessage, apiFieldErrors, formatDate } from '../composables/useCitationsApi';
import { authorsText, cleanDoc } from '../utils';

defineOptions({ name: 'CitationFormPage' });

const props = defineProps({ id: { type: [String, Number], default: null } });

// shown for a citation type the form has no definition for (e.g. a dataset added through the API)
const FALLBACK_FIELDS = ['container-title', 'volume', 'issue', 'page', 'number', 'edition', 'publisher', 'publisher-place', 'genre', 'issued', 'accessed', 'DOI', 'URL'];
const FALLBACK_ROLES = ['author', 'editor', 'translator'];
const DATE_FIELDS = ['issued', 'accessed'];

const { t } = useI18n();
const { siteUrl, config } = useAppConfig();
const api = useCitationsApi();
const route = useRoute();
const router = useRouter();

const isEdit = computed(() => props.id !== null && props.id !== undefined && props.id !== '');
const citationId = computed(() => Number(props.id));
const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const publicBase = computed(() => String(config.value?.publicCitationBaseUrl || `${siteBase.value}/citations/`));
const codelistsUrl = computed(() => String(config.value?.codelistsAdminUrl || ''));
const breadcrumbItems = computed(() => [
  { title: t('home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('title_citations', 'Citations'), to: { name: 'citations-list' } },
  { title: isEdit.value ? t('edit_citation', 'Edit citation') : t('add_new_citation', 'Add new citation'), disabled: true },
]);

const options = ref(null);
const citation = ref(null);
const doc = ref({ type: 'article-journal', title: '' });
const published = ref(false);
const flag = ref('');
const notes = ref('');
const studies = ref([]);
const snapshot = ref('');
const errors = ref({});
const duplicates = ref([]);
const previewHtml = ref('');
const quickDoi = ref('');

const loading = ref(true);
const ready = ref(false);
const saving = ref(false);
const lookupBusy = ref(false);
const checkBusy = ref(false);
const loadError = ref('');
const saveError = ref('');
const lookupError = ref('');
const checkError = ref('');
const toast = ref({ open: false, message: '', color: 'success' });

const can = computed(() => options.value?.can || { create: false, edit: false, delete: false, manage_flags: false });
const readOnly = computed(() => (isEdit.value ? !can.value.edit : !can.value.create));
const crossrefOn = computed(() => !!options.value?.crossref_enabled);
const justCreated = computed(() => route.query.created === '1');

const typeDef = computed(() => (options.value?.ctypes || []).find((c) => c.value === doc.value.type) || null);
const typeItems = computed(() => {
  const items = (options.value?.ctypes || []).map((c) => ({ title: c.label, value: c.value }));
  if (!typeDef.value && doc.value.type) items.push({ title: t(doc.value.type, doc.value.type), value: doc.value.type });
  return items;
});

function fieldLabel(name, type) {
  const typed = t(`cit_f_${name}_${type}`, '');
  return typed || t(`cit_f_${name}`, name);
}
const fieldDefs = computed(() => {
  if (typeDef.value) return typeDef.value.fields;
  return FALLBACK_FIELDS.map((name) => ({ name, label: fieldLabel(name, doc.value.type), input: DATE_FIELDS.includes(name) ? 'date' : 'text' }));
});
const roles = computed(() =>
  typeDef.value ? typeDef.value.roles : FALLBACK_ROLES.map((name) => ({ name, label: t(`cit_f_role_${name}`, name) }))
);
const commonDefs = computed(() => options.value?.common_fields || []);
const languageItems = computed(() => {
  const items = (options.value?.langs || []).map((l) => ({ title: l.label, value: l.code }));
  const current = doc.value.language;
  if (current && !items.some((i) => i.value === current)) items.push({ title: current, value: current });
  return items;
});
const flagItems = computed(() => {
  const items = [{ title: '—', value: '' }, ...(options.value?.flags || []).map((f) => ({ title: f.label, value: f.code }))];
  if (flag.value && !items.some((i) => i.value === flag.value)) {
    items.push({ title: `${flag.value} (${t('cit_flag_removed', 'no longer in the list')})`, value: flag.value });
  }
  return items;
});

function fieldCols(f) {
  return f.input === 'textarea' ? 12 : f.name === 'URL' || f.name === 'container-title' ? 12 : 6;
}

function setField(name, value) {
  doc.value[name] = value;
}

function payload() {
  return {
    metadata: cleanDoc(toRaw(doc.value)),
    published: published.value ? 1 : 0,
    flag: flag.value || '',
    notes: notes.value || '',
    studies: studies.value.map((s) => s.id),
  };
}

const dirty = computed(() => ready.value && JSON.stringify(payload()) !== snapshot.value);

function applyCitation(c) {
  citation.value = c;
  doc.value = JSON.parse(JSON.stringify(c.metadata || {}));
  if (!doc.value.type) doc.value.type = c.ctype;
  if (doc.value.title === undefined) doc.value.title = '';
  published.value = c.published === 1;
  flag.value = c.flag || '';
  notes.value = c.notes || '';
  studies.value = (c.studies || []).map((s) => ({ ...s }));
  snapshot.value = JSON.stringify(payload());
}

function showToast(message, color = 'success') {
  toast.value = { open: true, message, color };
}

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    options.value = await api.options();
    if (isEdit.value) {
      applyCitation(await api.get(citationId.value));
    } else {
      snapshot.value = JSON.stringify(payload());
    }
    ready.value = true;
    snapshot.value = JSON.stringify(payload());
    loadPreview();
  } catch (e) {
    loadError.value = apiErrorMessage(e, t);
  } finally {
    loading.value = false;
  }
}

// ---- preview and duplicate warning --------------------------------------------------------------

let previewTimer = null;
let dupTimer = null;
let previewSeq = 0;

async function loadPreview() {
  const mine = ++previewSeq;
  const metadata = cleanDoc(toRaw(doc.value));
  if (!metadata.title) {
    previewHtml.value = '';
    return;
  }
  try {
    const html = await api.preview(metadata);
    if (mine === previewSeq) previewHtml.value = html;
  } catch {
    if (mine === previewSeq) previewHtml.value = '';
  }
}

async function checkDuplicates() {
  const title = String(doc.value.title || '').trim();
  const doi = String(doc.value.DOI || '').trim();
  if (title.length < 8 && !doi) {
    duplicates.value = [];
    return;
  }
  try {
    duplicates.value = await api.duplicates({
      title,
      container_title: doc.value['container-title'] || '',
      authors_text: authorsText(doc.value),
      doi,
      exclude_id: isEdit.value ? citationId.value : undefined,
    });
  } catch {
    duplicates.value = [];
  }
}

watch(
  () => JSON.stringify(cleanDoc(toRaw(doc.value))),
  () => {
    if (!ready.value) return;
    clearTimeout(previewTimer);
    previewTimer = setTimeout(loadPreview, 600);
  }
);

watch(
  () => [doc.value.title, doc.value.DOI, doc.value['container-title'], authorsText(doc.value)].join('|'),
  () => {
    // only once the user changed something, so opening a citation does not list "duplicates" of itself
    if (!ready.value || !dirty.value) return;
    clearTimeout(dupTimer);
    dupTimer = setTimeout(checkDuplicates, 800);
  }
);

// ---- DOI lookup ----------------------------------------------------------------------------------

const hasContent = computed(() => !!(String(doc.value.title || '').trim() || (doc.value.author || []).length));

async function lookup(doi) {
  const value = String(doi || '').trim();
  if (!value) return;
  lookupError.value = '';
  lookupBusy.value = true;
  try {
    const res = await api.doi(value);
    if (hasContent.value) {
      const ok = await $dialog.confirm({
        title: t('cit_look_up', 'Look up'),
        message: t('cit_confirm_overwrite', 'Replace the data in the form with the record from Crossref?'),
        confirmText: t('cit_replace', 'Replace'),
        cancelText: t('cancel', 'Cancel'),
      });
      if (!ok) return;
    }
    doc.value = { ...res.metadata, custom: doc.value.custom };
    if (!doc.value.title) doc.value.title = '';
    duplicates.value = (res.duplicates || []).filter((d) => !isEdit.value || d.id !== citationId.value);
    quickDoi.value = '';
    showToast(t('cit_doi_filled', 'The form was filled from the DOI registry. Check it before saving.'));
  } catch (e) {
    lookupError.value = apiErrorMessage(e, t);
  } finally {
    lookupBusy.value = false;
  }
}

// ---- save, delete --------------------------------------------------------------------------------

async function save() {
  if (readOnly.value || saving.value) return;
  errors.value = {};
  saveError.value = '';
  const body = payload();
  if (!body.metadata.title) {
    errors.value = { title: t('cit_err_title_required', 'The title is required.') };
    return;
  }
  saving.value = true;
  try {
    if (isEdit.value) {
      applyCitation(await api.update(citationId.value, body));
      showToast(t('form_update_success', 'Saved.'));
      loadPreview();
    } else {
      const created = await api.create(body);
      snapshot.value = JSON.stringify(payload());
      await router.replace({ name: 'citation-edit', params: { id: String(created.id) }, query: { created: '1' } });
    }
  } catch (e) {
    errors.value = apiFieldErrors(e, t);
    const status = e?.response?.status;
    saveError.value = status === 422 ? t('cit_validation_failed', 'Please correct the highlighted fields.') : apiErrorMessage(e, t);
  } finally {
    saving.value = false;
  }
}

async function remove() {
  const ok = await $dialog.confirm({
    title: t('delete', 'Delete'),
    message: t('cit_confirm_delete_one', 'Delete the citation "%s"? This cannot be undone.', doc.value.title || ''),
    confirmText: t('delete', 'Delete'),
    cancelText: t('cancel', 'Cancel'),
  });
  if (!ok) return;
  try {
    await api.remove(citationId.value);
    snapshot.value = JSON.stringify(payload());
    await router.push({ name: 'citations-list', query: { notice: 'deleted' } });
  } catch (e) {
    saveError.value = apiErrorMessage(e, t);
  }
}

// ---- link status and attachment ------------------------------------------------------------------

const urlChanged = computed(() => !!citation.value && String(doc.value.URL || '').trim() !== String(citation.value.metadata?.URL || '').trim());
const linkColor = computed(() => ({ 1: 'success', 0: 'error', 2: 'warning', 3: 'grey' })[citation.value?.url_status] || 'grey');
const linkText = computed(() => {
  const s = citation.value?.url_status;
  if (s === null || s === undefined) return t('cit_link_unchecked', 'Not checked yet');
  const hit = (options.value?.url_status || []).find((x) => x.code === s);
  return hit ? hit.label : String(s);
});

async function checkLink() {
  checkBusy.value = true;
  checkError.value = '';
  try {
    const res = await api.checkUrl(citationId.value);
    citation.value = { ...citation.value, url_status: res.url_status, url_status_time: res.checked_at };
  } catch (e) {
    checkError.value = apiErrorMessage(e, t);
  } finally {
    checkBusy.value = false;
  }
}

function onAttachmentChanged(attachment) {
  citation.value = { ...citation.value, attachment };
}

// ---- leaving with unsaved changes ----------------------------------------------------------------

onBeforeRouteLeave(async () => {
  if (!dirty.value || saving.value) return true;
  return $dialog.confirm({
    title: t('cit_unsaved', 'Unsaved changes'),
    message: t('cit_unsaved_message', 'You have changes that are not saved. Leave this page and lose them?'),
    confirmText: t('cit_leave', 'Leave'),
    cancelText: t('cit_stay', 'Stay'),
  });
});

function beforeUnload(e) {
  if (dirty.value) {
    e.preventDefault();
    e.returnValue = '';
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', beforeUnload);
  load();
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', beforeUnload);
  clearTimeout(previewTimer);
  clearTimeout(dupTimer);
});
</script>
