<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="rsec-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">
      {{
        isNew
          ? t('repository_section_add', 'Add section')
          : t('repository_section_edit', 'Edit section')
      }}
    </h1>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">{{ loadError }}</v-alert>
    <v-alert v-if="saveError" type="error" class="mb-4" density="compact" closable @click:close="saveError = ''">
      {{ saveError }}
    </v-alert>

    <v-card v-if="!loadError" elevation="1" class="pa-4" max-width="640">
      <div class="mb-4">
        <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">
          {{ t('title', 'Title') }} <span class="text-error">*</span>
        </div>
        <v-text-field
          v-model="form.title"
          density="compact"
          variant="outlined"
          hide-details="auto"
          :error-messages="fieldErrors.title"
          maxlength="255"
        />
      </div>
      <div class="mb-4">
        <div class="text-body-2 font-weight-bold text-high-emphasis mb-1">{{ t('weight', 'Weight') }}</div>
        <v-text-field
          v-model.number="form.weight"
          type="number"
          density="compact"
          variant="outlined"
          hide-details="auto"
          :error-messages="fieldErrors.weight"
          style="max-width: 120px"
        />
      </div>
      <div class="d-flex ga-2">
        <v-btn color="primary" :loading="saving" :disabled="saving || !canEdit" @click="onSave">
          {{ t('update', 'Update') }}
        </v-btn>
        <v-btn variant="text" :to="{ name: 'sections-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useSectionsApi } from '../composables/useSectionsApi';

const props = defineProps({
  isNew: { type: Boolean, default: false },
  id: { type: [String, Number], default: null },
});

defineOptions({ name: 'SectionEditPage' });

const router = useRouter();
const { siteUrl, config } = useAppConfig();
const api = useSectionsApi();

const loading = ref(false);
const saving = ref(false);
const loadError = ref('');
const saveError = ref('');
const form = reactive({ title: '', weight: 0 });
const fieldErrors = reactive({ title: '', weight: '' });

const t = (key, fallback) => config.value?.translations?.[key] || fallback || key;
const canEdit = computed(() => !!config.value?.canEdit);
const siteBase = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));

const breadcrumbItems = computed(() => [
  { title: t('Home', 'Home'), href: `${siteBase.value}/admin` },
  { title: t('Collections', 'Collections'), href: `${siteBase.value}/admin/collections` },
  {
    title: t('repository_sections', 'Collection groups'),
    to: { name: 'sections-list' },
  },
  {
    title: props.isNew ? t('add', 'Add') : t('edit', 'Edit'),
    disabled: true,
  },
]);

function validate() {
  fieldErrors.title = '';
  fieldErrors.weight = '';
  let ok = true;
  if (!String(form.title || '').trim()) {
    fieldErrors.title = 'Title is required';
    ok = false;
  }
  const w = form.weight;
  if (w === '' || w === null || w === undefined || Number.isNaN(Number(w)) || Number(w) < 0) {
    fieldErrors.weight = 'Weight must be a non-negative number';
    ok = false;
  }
  return ok;
}

async function onSave() {
  if (!canEdit.value || !validate()) return;
  saving.value = true;
  saveError.value = '';
  const payload = { title: String(form.title).trim(), weight: Number(form.weight) || 0 };
  try {
    if (props.isNew) {
      await api.create(payload);
    } else {
      await api.update(props.id, payload);
    }
    router.push({
      name: 'sections-list',
      state: { flashSuccess: t('form_update_success', 'Updated successfully') },
    });
  } catch (e) {
    saveError.value = e.message;
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  if (!canEdit.value) {
    loadError.value = 'You do not have permission to edit sections.';
    return;
  }
  if (props.isNew) return;
  loading.value = true;
  try {
    const section = await api.get(props.id);
    form.title = section.title || '';
    form.weight = section.weight ?? 0;
  } catch (e) {
    loadError.value = e.message;
  } finally {
    loading.value = false;
  }
});
</script>

<style>
.rsec-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.rsec-breadcrumbs .v-breadcrumbs-item,
.rsec-breadcrumbs .v-breadcrumbs-divider {
  opacity: 0.7;
}
</style>
