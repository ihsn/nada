<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="bda-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">
      {{ pageTitle }}
    </h1>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">{{ loadError }}</v-alert>

    <v-card max-width="720" elevation="1" class="pa-6">
      <v-form @submit.prevent="submit">
        <div class="mb-4">
          <div class="text-body-2 font-weight-medium mb-1">{{ t('title', 'Title') }} <span class="text-error">*</span></div>
          <v-text-field
            v-model="form.title"
            density="compact"
            variant="outlined"
            hide-details="auto"
            :disabled="saving || loadingDetail"
          />
        </div>

        <div class="mb-6">
          <div class="text-body-2 font-weight-medium mb-1">{{ t('description', 'Description') }}</div>
          <v-textarea v-model="form.description" rows="4" variant="outlined" density="compact" :disabled="saving || loadingDetail" />
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <v-btn type="submit" color="primary" :loading="saving" :disabled="loadingDetail">
            {{ t('update', 'Save') }}
          </v-btn>
          <v-btn variant="text" :to="{ name: 'bda-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
        </div>
      </v-form>
    </v-card>

    <v-snackbar v-model="toast.open" :color="toast.color" location="bottom right">{{ toast.message }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useBulkDataAccessApi } from '../composables/useBulkDataAccessApi';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'BulkDataAccessEditPage' });

const props = defineProps({
  id: { type: String, default: '' },
  isNew: { type: Boolean, default: false },
});

const { t } = useI18n();
const router = useRouter();
const { siteUrl } = useAppConfig();
const { fetchCollection, createCollection, updateCollection } = useBulkDataAccessApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const form = reactive({ title: '', description: '' });
const loadError = ref('');
const loadingDetail = ref(false);
const saving = ref(false);
const toast = ref({ open: false, message: '', color: 'success' });

const pageTitle = computed(() =>
  props.isNew ? t('da_collection_add', 'Add bulk access collection') : t('da_collection_edit', 'Edit bulk access collection')
);

const breadcrumbItems = computed(() => {
  const base = siteBaseUrl.value;
  return [
    { title: t('home', 'Home'), href: `${base}/admin` },
    {
      title: t('bulk_da_collections', 'Bulk data access collections'),
      href: `${base}/admin/da_collections`,
    },
    { title: pageTitle.value, disabled: true },
  ];
});

async function loadDetail() {
  if (props.isNew || !props.id) return;
  loadingDetail.value = true;
  loadError.value = '';
  try {
    const row = await fetchCollection(props.id);
    form.title = row.title || '';
    form.description = row.description || '';
  } catch (e) {
    loadError.value = e?.message || 'Error';
  } finally {
    loadingDetail.value = false;
  }
}

watch(
  () => [props.isNew, props.id],
  async () => {
    if (props.isNew) {
      form.title = '';
      form.description = '';
      loadError.value = '';
      return;
    }
    if (props.id) await loadDetail();
  },
  { immediate: true }
);

async function submit() {
  if (!form.title.trim()) {
    toast.value = { open: true, message: t('title_required', 'Title is required'), color: 'error' };
    return;
  }
  saving.value = true;
  try {
    const payload = { title: form.title.trim(), description: form.description.trim() };
    if (props.isNew) {
      const created = await createCollection(payload);
      toast.value = { open: true, message: t('form_update_success', 'Saved'), color: 'success' };
      router.replace({ name: 'bda-edit', params: { id: String(created.id) } });
    } else {
      await updateCollection(props.id, payload);
      toast.value = { open: true, message: t('form_update_success', 'Saved'), color: 'success' };
    }
  } catch (e) {
    toast.value = { open: true, message: e?.message || 'Error', color: 'error' };
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.bda-breadcrumbs :deep(.v-breadcrumbs-item),
.bda-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.875rem;
}
</style>
