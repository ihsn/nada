<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="ctry-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">{{ pageTitle }}</h1>

    <v-alert v-if="loadError" type="error" class="mb-4" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>

    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <v-card v-else-if="!loadError" class="pa-4" elevation="1" max-width="560">
      <v-alert v-if="saveError" type="error" class="mb-4" density="compact" closable @click:close="saveError = ''">
        {{ saveError }}
      </v-alert>

      <div class="ctry-field-group">
        <div class="ctry-field-label">{{ t('name', 'Name') }} *</div>
        <v-text-field
          v-model="form.name"
          variant="outlined"
          density="compact"
          maxlength="65"
          hide-details="auto"
          autocomplete="off"
          autofocus
        />
      </div>
      <div class="ctry-field-group">
        <div class="ctry-field-label">{{ t('iso', 'ISO') }} *</div>
        <v-text-field
          v-model="form.iso"
          variant="outlined"
          density="compact"
          maxlength="3"
          hide-details="auto"
          autocomplete="off"
          style="max-width: 140px"
          @update:model-value="form.iso = String($event || '').toUpperCase()"
        />
      </div>
      <div class="ctry-field-group">
        <div class="ctry-field-label">{{ t('Aliases', 'Aliases') }}</div>
        <v-combobox
          v-model="form.aliases"
          variant="outlined"
          density="compact"
          multiple
          chips
          closable-chips
          :delimiters="[',']"
          hide-details="auto"
          :hint="t('country_aliases_hint', 'Press Enter after each alias.')"
          persistent-hint
        />
      </div>

      <p v-if="country?.studies" class="text-caption text-medium-emphasis mt-4 mb-0">
        {{ t('country_used_by_studies', 'Used by %s studies.', country.studies) }}
      </p>

      <div class="ctry-form-actions d-flex ga-2">
        <v-btn color="primary" :loading="saving" :disabled="!canSave" @click="submit">
          {{ isNew ? t('create', 'Create') : t('update', 'Update') }}
        </v-btn>
        <v-btn variant="text" :to="{ name: 'countries-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { useCountriesApi } from '../composables/useCountriesApi';

defineOptions({ name: 'CountryEditPage' });

const props = defineProps({
  id: { type: [String, Number], default: null },
});

const router = useRouter();
const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { getCountry, createCountry, updateCountry } = useCountriesApi();

const isNew = computed(() => !props.id);
const pageTitle = computed(() =>
  isNew.value ? t('add_country', 'Add country') : t('edit_country', 'Edit country')
);
const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Countries', 'Countries'), to: { name: 'countries-list' } },
  { title: pageTitle.value, disabled: true },
]);

const country = ref(null);
const pageLoading = ref(false);
const loadError = ref('');
const saving = ref(false);
const saveError = ref('');
const form = reactive({ name: '', iso: '', aliases: [] });
const canSave = computed(() => form.name.trim() !== '' && /^[A-Z0-9]{2,3}$/.test(form.iso.trim()));

async function load() {
  loadError.value = '';
  saveError.value = '';
  country.value = null;
  Object.assign(form, { name: '', iso: '', aliases: [] });
  if (isNew.value) return;
  pageLoading.value = true;
  try {
    country.value = await getCountry(props.id);
    Object.assign(form, {
      name: country.value.name,
      iso: country.value.iso,
      aliases: [...country.value.aliases],
    });
  } catch (e) {
    loadError.value = e.message;
  } finally {
    pageLoading.value = false;
  }
}

async function submit() {
  saving.value = true;
  saveError.value = '';
  const payload = {
    name: form.name.trim(),
    iso: form.iso.trim(),
    aliases: form.aliases.map((a) => String(a).trim()).filter(Boolean),
  };
  try {
    if (isNew.value) {
      await createCountry(payload);
    } else {
      await updateCountry(props.id, payload);
    }
    await router.push({
      name: 'countries-list',
      state: { flashSuccess: t('form_update_success', 'Your changes have been saved!') },
    });
  } catch (e) {
    saveError.value = e.message;
  } finally {
    saving.value = false;
  }
}

watch(() => props.id, load, { immediate: true });
</script>
