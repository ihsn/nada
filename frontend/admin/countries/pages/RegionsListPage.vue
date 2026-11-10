<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="ctry-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex flex-wrap align-start justify-space-between ga-3 mb-4">
      <div>
        <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-1">
          {{ t('Regions', 'Regions') }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{
            t(
              'regions_page_intro',
              'Top-level groups hold regions; each region lists its countries for the catalog country filter.'
            )
          }}
        </p>
      </div>
      <v-btn color="primary" prepend-icon="mdi-plus" :to="{ name: 'region-new' }">
        {{ t('create_region', 'Create region') }}
      </v-btn>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-card v-if="loading && !regions.length" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="48" />
      </v-card-text>
    </v-card>

    <v-card v-else-if="!groups.length" elevation="1">
      <v-card-text class="text-center text-medium-emphasis py-10">
        {{ t('no_records_found', 'No records found') }}
      </v-card-text>
    </v-card>

    <div v-else class="d-flex flex-column ga-6">
      <v-card v-for="group in groups" :key="group.key" elevation="1">
        <div class="ctry-group-header d-flex align-center ga-2 px-4 py-3 flex-wrap">
          <v-icon icon="mdi-folder-outline" class="text-medium-emphasis" />
          <div class="min-w-0">
            <router-link
              v-if="group.parent"
              :to="{ name: 'region-edit', params: { id: group.parent.id } }"
              class="ctry-link text-subtitle-1 font-weight-semibold"
            >
              {{ group.parent.title }}
            </router-link>
            <span v-else class="text-subtitle-1 font-weight-semibold">
              {{ t('regions_without_group', 'Without a group') }}
            </span>
            <div class="text-caption text-medium-emphasis">
              <template v-if="group.parent">#{{ group.parent.id }} · {{ t('weight', 'Weight') }} {{ group.parent.weight }} · </template>
              {{ group.children.length }} {{ t('Regions', 'regions') }}
            </div>
          </div>
          <v-spacer />
          <template v-if="group.parent">
            <v-btn
              size="small"
              variant="tonal"
              prepend-icon="mdi-plus"
              :to="{ name: 'region-new', query: { pid: group.parent.id } }"
            >
              {{ t('add_region', 'Add region') }}
            </v-btn>
            <v-btn
              icon
              variant="text"
              size="small"
              :title="t('edit', 'Edit')"
              :to="{ name: 'region-edit', params: { id: group.parent.id } }"
            >
              <v-icon size="20" icon="mdi-pencil-outline" />
            </v-btn>
            <v-btn icon variant="text" size="small" color="error" :title="t('delete', 'Delete')" @click="openDelete(group.parent)">
              <v-icon size="20" icon="mdi-delete-outline" />
            </v-btn>
          </template>
        </div>
        <v-divider />
        <v-table v-if="group.children.length" density="comfortable" class="ctry-table text-body-2">
          <thead>
            <tr>
              <th style="width: 80px">{{ t('ID', 'ID') }}</th>
              <th>{{ t('Region', 'Region') }}</th>
              <th class="text-end" style="width: 100px">{{ t('weight', 'Weight') }}</th>
              <th class="text-end" style="width: 120px">{{ t('Countries', 'Countries') }}</th>
              <th class="ctry-col-actions" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="region in group.children" :key="region.id">
              <td>{{ region.id }}</td>
              <td>
                <router-link :to="{ name: 'region-edit', params: { id: region.id } }" class="ctry-link font-weight-medium">
                  {{ region.title }}
                </router-link>
              </td>
              <td class="text-end">{{ region.weight }}</td>
              <td class="text-end">{{ region.countries_count }}</td>
              <td class="text-end text-no-wrap">
                <v-btn
                  icon
                  variant="text"
                  size="small"
                  :title="t('edit', 'Edit')"
                  :to="{ name: 'region-edit', params: { id: region.id } }"
                >
                  <v-icon size="20" icon="mdi-pencil-outline" />
                </v-btn>
                <v-btn icon variant="text" size="small" color="error" :title="t('delete', 'Delete')" @click="openDelete(region)">
                  <v-icon size="20" icon="mdi-delete-outline" />
                </v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>
        <div v-else class="text-body-2 text-medium-emphasis px-4 py-4">
          {{ t('regions_group_empty', 'No regions in this group yet.') }}
        </div>
      </v-card>
    </div>

    <v-dialog v-model="deleteDialog.open" max-width="460">
      <v-card>
        <v-card-title class="text-h6">{{ t('delete', 'Delete') }}</v-card-title>
        <v-card-text>
          <v-alert v-if="deleteDialog.error" type="error" class="mb-3" density="compact">
            {{ deleteDialog.error }}
          </v-alert>
          <p class="mb-2">
            {{ t('js_confirm_delete', 'Are you sure you want to delete the selected item(s)?') }}
            <strong v-if="deleteDialog.item">“{{ deleteDialog.item.title }}”</strong>
          </p>
          <v-alert v-if="deleteChildren" type="warning" variant="tonal" density="compact">
            {{ t('region_delete_children_warning', 'Its %s regions are deleted too.', deleteChildren) }}
          </v-alert>
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog.open = false">{{ t('cancel', 'Cancel') }}</v-btn>
          <v-btn color="error" variant="flat" :loading="deleteDialog.loading" @click="confirmDelete">
            {{ t('delete', 'Delete') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right" :timeout="4000">
      {{ toast.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useCountriesApi } from '../composables/useCountriesApi';
import { useFlashToast } from '../composables/useFlashToast';

defineOptions({ name: 'RegionsListPage' });

const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { listRegions, deleteRegion } = useCountriesApi();
const { toast, showToast, consumeFlash } = useFlashToast();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Regions', 'Regions'), disabled: true },
]);

const regions = ref([]);
const loading = ref(true);
const loadError = ref('');
const deleteDialog = reactive({ open: false, item: null, error: '', loading: false });

const groups = computed(() => {
  const parents = regions.value.filter((r) => r.pid === 0);
  const parentIds = new Set(parents.map((p) => p.id));
  const out = parents.map((parent) => ({
    key: parent.id,
    parent,
    children: regions.value.filter((r) => r.pid === parent.id),
  }));
  const orphans = regions.value.filter((r) => r.pid !== 0 && !parentIds.has(r.pid));
  if (orphans.length) out.push({ key: 'orphans', parent: null, children: orphans });
  return out;
});

const deleteChildren = computed(() =>
  deleteDialog.item ? regions.value.filter((r) => r.pid === deleteDialog.item.id).length : 0
);

async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    regions.value = await listRegions();
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loading.value = false;
  }
}

function openDelete(item) {
  deleteDialog.item = item;
  deleteDialog.error = '';
  deleteDialog.open = true;
}

async function confirmDelete() {
  deleteDialog.loading = true;
  deleteDialog.error = '';
  try {
    await deleteRegion(deleteDialog.item.id);
    deleteDialog.open = false;
    showToast(t('form_update_success', 'Your changes have been saved!'));
    await load();
  } catch (e) {
    deleteDialog.error = e.message;
  } finally {
    deleteDialog.loading = false;
  }
}

onMounted(() => {
  consumeFlash();
  load();
});
</script>

<style scoped>
.ctry-group-header {
  background: rgba(var(--v-theme-on-surface), 0.02);
}
</style>
