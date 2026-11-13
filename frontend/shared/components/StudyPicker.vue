<template>
  <div class="study-picker">
    <div class="d-flex align-center flex-wrap ga-2 mb-2">
      <v-btn size="small" variant="outlined" prepend-icon="mdi-plus" :disabled="disabled" @click="openDialog">
        {{ t('cit_add_studies', 'Add studies') }}
      </v-btn>
      <span class="text-body-2 text-medium-emphasis">
        {{ modelValue.length ? t('cit_n_studies', '%d studies', modelValue.length) : t('cit_no_studies', 'No studies linked') }}
      </span>
    </div>

    <v-list v-if="modelValue.length" density="compact" lines="two" class="study-picker__list">
      <v-list-item v-for="s in modelValue" :key="s.id" :title="s.title" :subtitle="subtitle(s)">
        <template #append>
          <v-btn
            icon="mdi-close"
            size="x-small"
            variant="text"
            :disabled="disabled"
            :title="t('remove', 'Remove')"
            @click="remove(s)"
          />
        </template>
      </v-list-item>
    </v-list>

    <v-dialog v-model="open" max-width="760" scrollable>
      <v-card>
        <v-card-title class="text-subtitle-1">{{ t('cit_find_studies', 'Find studies') }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="keywords"
            density="compact"
            variant="outlined"
            hide-details
            clearable
            autofocus
            prepend-inner-icon="mdi-magnify"
            :label="t('cit_search_studies', 'Search by title, ID or country')"
            @update:model-value="onKeywords"
          />
          <v-progress-linear v-if="loading" indeterminate class="mt-2" />
          <v-alert v-if="error" type="error" density="compact" class="mt-2">{{ error }}</v-alert>

          <v-list v-if="rows.length" density="compact" lines="two" class="mt-2">
            <v-list-item v-for="row in rows" :key="row.id" :title="row.title" :subtitle="subtitle(row)" @click="toggle(row)">
              <template #prepend>
                <v-checkbox-btn :model-value="isSelected(row)" tabindex="-1" @click.stop="toggle(row)" />
              </template>
            </v-list-item>
          </v-list>
          <div v-else-if="!loading" class="py-6 text-medium-emphasis text-center">
            {{ t('no_records_found', 'No records found') }}
          </div>

          <v-pagination v-if="pages > 1" v-model="page" :length="pages" :total-visible="5" density="comfortable" class="mt-2" />
        </v-card-text>
        <v-card-actions>
          <span class="text-body-2 text-medium-emphasis ms-2">
            {{ t('cit_n_studies', '%d studies', modelValue.length) }}
          </span>
          <v-spacer />
          <v-btn color="primary" variant="flat" @click="open = false">{{ t('cit_done', 'Done') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
/**
 * Pick studies from the admin catalog (GET api/admin/catalog), which only lists the studies the signed-in
 * user may see. v-model is a list of {id, idno, title, nation, year_start, year_end, repositoryid}.
 */
import { ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'StudyPicker' });

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const { catalogApiBaseUrl } = useAppConfig();

const open = ref(false);
const keywords = ref('');
const rows = ref([]);
const page = ref(1);
const pages = ref(1);
const loading = ref(false);
const error = ref('');
let timer = null;
let seq = 0;

function normalize(row) {
  return {
    id: Number(row.id),
    idno: row.idno || '',
    title: row.title || '',
    nation: row.nation || '',
    year_start: row.year_start || '',
    year_end: row.year_end || '',
    repositoryid: row.repositoryid || '',
  };
}

function subtitle(s) {
  const years = s.year_start ? (s.year_end && s.year_end !== s.year_start ? `${s.year_start}-${s.year_end}` : String(s.year_start)) : '';
  return [s.idno, s.nation, years].filter(Boolean).join(' · ');
}

function isSelected(row) {
  return props.modelValue.some((s) => s.id === Number(row.id));
}

function toggle(row) {
  if (isSelected(row)) {
    emit('update:modelValue', props.modelValue.filter((s) => s.id !== Number(row.id)));
  } else {
    emit('update:modelValue', [...props.modelValue, normalize(row)]);
  }
}

function remove(s) {
  emit('update:modelValue', props.modelValue.filter((x) => x.id !== s.id));
}

async function load() {
  const mine = ++seq;
  loading.value = true;
  error.value = '';
  try {
    const params = { ps: 15, page: page.value, exclude_metadata: 1 };
    if (keywords.value && keywords.value.trim()) params.keywords = keywords.value.trim();
    const { data } = await axios.get(String(catalogApiBaseUrl.value || ''), { params });
    if (mine !== seq) return;
    const result = data?.result || {};
    rows.value = result.rows || [];
    pages.value = Math.max(1, Number(result.pages) || 1);
  } catch (e) {
    if (mine !== seq) return;
    rows.value = [];
    pages.value = 1;
    error.value =
      e?.response?.status === 403 ? t('cit_access_denied', 'Access denied') : t('cit_request_failed', 'Request failed');
  } finally {
    if (mine === seq) loading.value = false;
  }
}

function onKeywords() {
  clearTimeout(timer);
  timer = setTimeout(() => {
    if (page.value !== 1) page.value = 1;
    else load();
  }, 350);
}

function openDialog() {
  open.value = true;
  if (!rows.value.length) load();
}

watch(page, load);
</script>

<style scoped>
.study-picker__list {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 4px;
  max-height: 280px;
  overflow-y: auto;
}
</style>
