<template>
  <v-card class="admin-catalog-results-card admin-catalog-surface" rounded="lg" elevation="1">
    <div class="catalog-results-toolbar catalog-results-toolbar--padded">
      <v-row class="mb-0 align-center">
      <v-col cols="6" class="d-flex align-center text-body-2">
        <span>
          {{ t('showing_studies_range', 'Showing %s - %s of %s studies', firstItem, lastItem, totalStudies) }}
        </span>
      </v-col>
      <v-col cols="6" class="d-flex justify-end">
        <v-pagination
          v-if="totalPages > 1"
          v-model="currentPage"
          :length="totalPages"
          :total-visible="7"
          density="compact"
          color="primary"
          @update:model-value="onPageChange"
        />
      </v-col>
      </v-row>
    </div>
    <div class="catalog-results-toolbar catalog-results-toolbar--padded">
      <v-row class="mb-0 align-center">
      <v-col cols="4" class="d-flex align-center ga-1">
        <v-checkbox
          v-model="selectAll"
          :indeterminate="isIndeterminate"
          hide-details
          density="compact"
          class="ma-0 pa-0"
        />
        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn color="primary" variant="text" icon v-bind="menuProps" :title="t('batch_actions')">
              <v-icon>mdi-dots-vertical</v-icon>
            </v-btn>
          </template>
          <v-list density="compact" class="menu-list-compact">
            <v-list-item @click="batchAction('publish')">
              <template #prepend><v-icon size="small">mdi-check-circle</v-icon></template>
              <v-list-item-title class="text-caption">{{ t('publish') }}</v-list-item-title>
            </v-list-item>
            <v-list-item @click="batchAction('unpublish')">
              <template #prepend><v-icon size="small">mdi-cancel</v-icon></template>
              <v-list-item-title class="text-caption">{{ t('unpublished') }}</v-list-item-title>
            </v-list-item>
            <v-list-item @click="batchAction('delete')">
              <template #prepend><v-icon size="small" color="error">mdi-trash-can</v-icon></template>
              <v-list-item-title class="text-caption">{{ t('delete_study') }}</v-list-item-title>
            </v-list-item>
            <v-list-item @click="batchAction('transfer_ownership')">
              <template #prepend><v-icon size="small" color="primary">mdi-transfer-right</v-icon></template>
              <v-list-item-title class="text-caption">{{ t('transfer_ownership') }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </v-col>
      <v-col cols="8" class="d-flex justify-end align-center">
        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn variant="text" v-bind="menuProps">
              {{ t('sort_by') }}
              <v-icon end>mdi-chevron-down</v-icon>
            </v-btn>
          </template>
          <v-list density="compact" class="menu-list-compact">
            <v-list-item
              v-for="opt in sortOptions"
              :key="opt.value"
              :active="currentSort === opt.value"
              @click="onSortChange(opt)"
            >
              <v-list-item-title class="text-caption">{{ opt.label }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </v-col>
      </v-row>
    </div>

    <v-table class="admin-catalog-table" hover density="comfortable">
      <tbody>
        <tr v-for="study in studies" :key="study.id">
          <td class="text-center align-top">
            <v-checkbox
              v-model="selected"
              :value="study.id"
              hide-details
              density="compact"
              class="ma-0 pa-0"
            />
          </td>
          <td class="text-center align-top" :title="study.type">
            <v-img
              :title="study.type"
              :src="studyTypeImage(study.type)"
              width="44"
              height="44"
              cover
              class="study-type-thumb rounded mx-auto d-block"
            />
          </td>
          <td class="align-top">
            <div class="study-row-detail">
            <div class="study-row-detail__title-line text-title-medium font-weight-bold">
              <a :href="editUrl(study)" class="text-decoration-none">{{ study.title }}</a>
              <v-chip v-if="study.type" size="x-small">{{ (study.type || '').toUpperCase() }}</v-chip>
            </div>
            <div class="study-row-detail__meta text-caption text-medium-emphasis">
              {{ study.nation }}
              <span class="mr-2" v-if="displayYearRange(study)">{{ displayYearRange(study) }}</span>
              <span class="text-medium-emphasis">{{ t('idno') }}: {{ study.idno }}</span>
            </div>
            <div v-if="study.abstract" class="text-caption text-medium-emphasis study-abstract">
              {{ study.abstract }}
            </div>
            <div class="study-row-detail__chips-row text-caption">
              <v-chip v-if="study.form_model" size="x-small" color="primary" variant="flat" class="font-weight-medium">{{ (study.form_model || '').toUpperCase() }}</v-chip>
              <v-chip v-if="study.pending_lic_requests" color="error" size="x-small">{{ study.pending_lic_requests }} {{ t('pending') }}</v-chip>
            </div>
            <div v-if="sortedRepositories(study).length || (Array.isArray(study.tags) && study.tags.length > 0)" class="study-row-detail__chips-row text-caption">
              <template v-if="sortedRepositories(study).length">
                <span class="text-medium-emphasis chip-label">{{ t('repositories') }}:</span>
                <v-chip
                  v-for="repo in sortedRepositories(study)"
                  :key="repo.id"
                  size="x-small"
                  :color="String(repo?.isadmin) === '1' ? 'primary' : undefined"
                  variant="tonal"
                  :title="String(repo?.isadmin) === '1' ? t('owner_repository') : undefined"
                >
                  {{ (repo.repositoryid && repo.repositoryid.trim() ? repo.repositoryid : 'central').trim().toUpperCase() }}
                </v-chip>
              </template>
              <template v-if="Array.isArray(study.tags) && study.tags.length > 0">
                <span class="text-medium-emphasis chip-label">{{ t('tags') }}:</span>
                <v-chip
                  v-for="tag in study.tags"
                  :key="tag"
                  size="x-small"
                >{{ tag }}</v-chip>
              </template>
            </div>

            <div class="study-meta-bar text-caption">
              <span class="study-meta-bar__item">
                <span class="study-meta-bar__key">{{ t('created') }}:</span>
                <span class="study-meta-bar__value">{{ formatDate(study.created) }}</span>
              </span>
              <span class="study-meta-bar__sep" aria-hidden="true">·</span>
              <span class="study-meta-bar__item">
                <span class="study-meta-bar__key">{{ t('last_changed') }}:</span>
                <span class="study-meta-bar__value">{{ formatDate(study.changed) }}</span>
              </span>
              <span class="study-meta-bar__sep" aria-hidden="true">·</span>
              <span class="study-meta-bar__item">
                <span class="study-meta-bar__key">{{ t('changed_by') }}:</span>
                <span class="study-meta-bar__value">{{ study.changed_by_user ?? study.created_by_user ?? '—' }}</span>
              </span>
            </div>
            </div>

          </td>
          <td class="text-center align-top">
            <div class="study-actions-inline">
              <v-switch
                :model-value="Number(study.published) === 1"
                :title="Number(study.published) === 1 ? t('published') : t('draft')"
                color="success"
                hide-details
                density="compact"
                :disabled="publishLoading === study.id"
                :loading="publishLoading === study.id"
                @update:model-value="(v) => onPublishClick(study, v)"
              />
              <v-menu>
                <template #activator="{ props: menuProps }">
                  <v-btn variant="text" icon size="small" v-bind="menuProps">
                    <v-icon>mdi-dots-vertical</v-icon>
                  </v-btn>
                </template>
              <v-list density="compact" class="menu-list-compact">
                <v-list-item :href="editUrl(study)">
                  <template #prepend><v-icon size="small" color="primary">mdi-pencil</v-icon></template>
                  <v-list-item-title class="text-caption">{{ t('edit') }}</v-list-item-title>
                </v-list-item>
                <v-list-item :href="transferOwnershipUrl(study)">
                  <template #prepend><v-icon size="small" color="primary">mdi-transfer-right</v-icon></template>
                  <v-list-item-title class="text-caption">{{ t('transfer_ownership') }}</v-list-item-title>
                </v-list-item>
                <v-list-item :href="deleteUrl(study)">
                  <template #prepend><v-icon size="small" color="error">mdi-delete</v-icon></template>
                  <v-list-item-title class="text-caption">{{ t('delete_study') }}</v-list-item-title>
                </v-list-item>                
              </v-list>
              </v-menu>
            </div>
          </td>
        </tr>
      </tbody>
    </v-table>

    <div class="admin-catalog-results-footer">
      <v-row class="mt-2 align-center">
        <v-col cols="12" sm="auto" class="d-flex align-center justify-center justify-sm-start ga-2 pb-2 pb-sm-0">
          <span class="text-body-2 text-medium-emphasis text-no-wrap">{{ t('items_per_page', 'Per page') }}</span>
          <v-select
            :model-value="itemsPerPage"
            :items="pageSizeOptions"
            density="compact"
            variant="outlined"
            hide-details
            class="admin-catalog-page-size-select"
            @update:model-value="onItemsPerPageChange"
          />
        </v-col>
        <v-col cols="12" sm class="d-flex justify-center">
          <v-pagination
            v-if="totalPages > 1"
            v-model="currentPage"
            :length="totalPages"
            :total-visible="10"
            density="compact"
            color="primary"
            @update:model-value="onPageChange"
          />
        </v-col>
      </v-row>
    </div>
  </v-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCatalogApi } from '../composables/useCatalogApi';
import { useI18n } from '@/shared/composables/useI18n';
import $dialog from '@/shared/composables/dialog';

defineOptions({ name: 'AdminCatalogResults' });

const { t } = useI18n();

const props = defineProps({
  studies: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  pagination: { type: Object, default: () => ({ page: 1, itemsPerPage: 15 }) },
  totalStudies: { type: Number, default: 0 },
  currentSort: { type: String, default: '' },
});

const emit = defineEmits(['pagination-change', 'sort-change', 'publish-change', 'refresh']);

const { siteUrl, baseUrl } = useAppConfig();
const { updateOptions, deleteStudy } = useCatalogApi();
const selected = ref([]);
const publishLoading = ref(null);

const sortOptions = [
  { label: t('sort_title_asc'), value: 'title_asc' },
  { label: t('sort_title_desc'), value: 'title_desc' },
  { label: t('sort_country_asc'), value: 'country_asc' },
  { label: t('sort_country_desc'), value: 'country_desc' },
  { label: t('sort_id_asc'), value: 'id_asc' },
  { label: t('sort_id_desc'), value: 'id_desc' },
  { label: t('sort_idno_asc'), value: 'idno_asc' },
  { label: t('sort_idno_desc'), value: 'idno_desc' },
  { label: t('sort_modified_desc'), value: 'modified_desc' },
  { label: t('sort_modified_asc'), value: 'modified_asc' },
  { label: t('sort_created_desc'), value: 'created_desc' },
  { label: t('sort_created_asc'), value: 'created_asc' },
];

const currentSort = computed(() => props.currentSort || null);

function onSortChange(opt) {
  emit('sort-change', { sort: opt.value });
}

/** Allowed page sizes for admin catalog (must stay in sync with App.vue URL parsing). */
const pageSizeOptions = [15, 50, 100];

const itemsPerPage = computed(() => props.pagination?.itemsPerPage ?? 15);
const totalPages = computed(() =>
  Math.ceil(props.totalStudies / itemsPerPage.value) || 1
);
const currentPage = computed({
  get: () => props.pagination?.page ?? 1,
  set: (v) => onPageChange(v),
});
const selectAll = computed({
  get: () =>
    selected.value.length === props.studies.length && props.studies.length > 0,
  set: (val) => {
    selected.value = val ? props.studies.map((s) => s.id) : [];
  },
});
const isIndeterminate = computed(
  () =>
    selected.value.length > 0 && selected.value.length < props.studies.length
);
const firstItem = computed(() => {
  if (!props.totalStudies) return 0;
  return (currentPage.value - 1) * itemsPerPage.value + 1;
});
const lastItem = computed(() => {
  if (!props.totalStudies) return 0;
  const last = currentPage.value * itemsPerPage.value;
  return Math.min(last, props.totalStudies);
});

const defaultThumbnailUrl = computed(() => `${baseUrl.value}/files/images/icon-blank.png`);

function thumbnailUrl(study) {
  if (study?.thumbnail) {
    return `${baseUrl.value}/files/thumbnails/${study.thumbnail}`;
  }
  return defaultThumbnailUrl.value;
}

function editUrl(study) {
  return `${siteUrl.value}/admin/catalog/edit/${study.id}`;
}
function deleteUrl(study) {
  return `${siteUrl.value}/admin/catalog/delete/${study.id}`;
}

function transferOwnershipUrl(study) {
  return `${siteUrl.value}/admin/catalog/transfer/${study.id}`;
}

function unlinkUrl(study) {
  const repoId = study.repositories?.[0]?.repositoryid ?? '';
  return `${siteUrl.value}admin/catalog/unlink/${repoId}/${study.id}`;
}

function studyTypeIcon(type) {
  const icons = {
    'survey': 'mdi-table',
    'study': 'mdi-book',
    'dataset': 'mdi-database',
    'other': 'mdi-help-circle',
  };
  return icons[type] || 'mdi-help-circle';
}

function studyTypeImage(type) {
  const images = {
    'survey': 'images/microdata.svg',
    'table': 'images/table.svg',
    'document': 'images/document.svg',
    'geospatial': 'images/geospatial.svg',
    'image': 'images/image.svg',
    'video': 'images/video.svg',
    'script': 'images/script.svg',
    'other': 'images/other.svg',
  };
  return images[type] || 'images/custom.svg';
} 


function sortedRepositories(study) {
  const repos = [...(study?.repositories ?? [])];
  return repos.sort((a, b) => (String(b?.isadmin) === '1' ? 1 : 0) - (String(a?.isadmin) === '1' ? 1 : 0));
}

function isMeaningfulYear(v) {
  if (v == null) return false;
  const s = String(v).trim();
  return s !== '' && s !== '0';
}

function displayYearRange(study) {
  const start = study?.year_start;
  const end = study?.year_end;
  const hasStart = isMeaningfulYear(start);
  const hasEnd = isMeaningfulYear(end);
  if (!hasStart && !hasEnd) return '';
  const startStr = hasStart ? String(start).trim() : '';
  const endStr = hasEnd ? String(end).trim() : '';
  if (hasStart && hasEnd && startStr !== endStr) return `(${startStr} - ${endStr})`;
  if (hasStart) return `(${startStr})`;
  if (hasEnd) return `(${endStr})`;
  return '';
}

function formatDate(ts) {
  if (!ts) return '';
  return new Date(ts * 1000).toLocaleDateString();
}

function onPageChange(page) {
  emit('pagination-change', {
    page: Number(page),
    itemsPerPage: itemsPerPage.value,
  });
}

function onItemsPerPageChange(val) {
  const n = Number(val);
  if (!pageSizeOptions.includes(n)) return;
  emit('pagination-change', {
    page: 1,
    itemsPerPage: n,
  });
}

const selectedStudies = computed(() =>
  props.studies.filter((s) => selected.value.includes(s.id))
);

async function batchAction(action) {
  const studies = selectedStudies.value;
  const n = studies.length;
  if (n === 0) {
    await $dialog.alert({ title: t('no_selection'), message: t('select_at_least_one_study') });
    return;
  }

  const ids = studies.map((s) => s.id).filter((id) => id != null && id !== '');

  if (action === 'transfer_ownership') {
    const idsParam = ids.join(',');
    window.location.href = `${siteUrl.value}/admin/catalog/transfer/${idsParam}`;
    return;
  }

  const confirmed = await $dialog.confirm({
    title: t('confirm'),
    message: t('confirm_message'),
    confirmText: action === 'delete' ? t('delete_study') : t('confirm'),
  });
  if (!confirmed) return;

  let failed = 0;

  if (action === 'publish') {
    for (const id of ids) {
      try {
        await updateOptions(id, { published: 1 });
      } catch (e) {
        console.error('Batch publish failed for id', id, e);
        failed++;
      }
    }
  } else if (action === 'unpublish') {
    for (const id of ids) {
      try {
        await updateOptions(id, { published: 0 });
      } catch (e) {
        console.error('Batch unpublish failed for id', id, e);
        failed++;
      }
    }
  } else if (action === 'delete') {
    for (const id of ids) {
      try {
        await deleteStudy(id);
      } catch (e) {
        console.error('Batch delete failed for id', id, e);
        failed++;
      }
    }
  }

  selected.value = [];
  emit('refresh');

  if (failed > 0) {
    await $dialog.alert({
      title: t('partially_completed'),
      message: t('batch_failed_count', `${failed} of ${n} failed. Check the console for details.`, failed, n),
    });
  }
}

async function onPublishClick(study, published) {
  const target = published ? t('published') : t('draft');
  const ok = await $dialog.confirm({
    title: t('confirm'),
    message: t('confirm_set_study_status', 'Set this study to %s?', target),
  });
  if (!ok) return;
  publishLoading.value = study.id;
  emit('publish-change', {
    studyId: study.id,
    idno: study.idno,
    published: published ? 1 : 0,
    study,
  });
}

// Clear per-row loading when studies refetch
watch(
  () => props.studies,
  () => {
    publishLoading.value = null;
  },
  { deep: true }
);
</script>

<style scoped>
.menu-list-compact :deep(.v-list-item) {
  min-height: 32px;
  padding-top: 2px;
  padding-bottom: 2px;
}
.study-abstract {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-width: 100%;
  min-width: 0;
}

.study-row-detail__title-line a {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}
.study-row-detail__title-line a:hover {
  text-decoration: underline;
  text-underline-offset: 2px;
}

.admin-catalog-page-size-select {
  width: 88px;
  flex-shrink: 0;
}
</style>
