<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="menu-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
      <h1 class="text-h5 font-weight-semibold ml-2 mb-0">{{ pageTitle }}</h1>
    </div>

    <v-alert v-if="loadError" type="error" class="mb-4" density="compact">{{ loadError }}</v-alert>
    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <v-card v-else elevation="1" class="pa-4">
      <v-alert v-if="saveError" type="error" class="mb-4" density="compact" closable @click:close="saveError = null">
        {{ saveError }}
      </v-alert>

      <v-form ref="formRef" validate-on="submit" @submit.prevent="submit">
        <div class="field-group">
          <label class="field-label">Title <span class="text-error">*</span></label>
          <v-text-field v-model="form.title" density="comfortable" hide-details="auto" :rules="[required]" />
        </div>

        <div class="field-group">
          <label class="field-label">Parent page</label>
          <v-select
            v-model="form.pid"
            :items="parentItems"
            item-title="title"
            item-value="id"
            density="comfortable"
            hide-details="auto"
          />
        </div>

        <div class="field-group">
          <label class="field-label">URL <span class="text-error">*</span></label>
          <v-text-field
            v-model="form.url"
            density="comfortable"
            :hide-details="urlSubmitError ? true : 'auto'"
            :rules="[required]"
            :hint="isPage ? 'Letters, numbers, and hyphens only (e.g. about-us). No spaces, slashes, or full URLs.' : undefined"
            :persistent-hint="isPage && !urlSubmitError"
            @update:model-value="urlSubmitError = null"
          />
          <div v-if="urlSubmitError" class="text-error text-caption mt-1">{{ urlSubmitError }}</div>
          <div v-else-if="isPage && pageUrlIsValid && urlPreview" class="text-caption text-medium-emphasis mt-1">
            <a :href="urlPreview" target="_blank" rel="noopener noreferrer">{{ urlPreview }}</a>
          </div>
        </div>

        <div v-if="isPage" class="field-group">
          <HtmlBodyEditor ref="bodyEditor" v-model="form.body" label="Body" />
        </div>

        <v-row dense class="field-group">
          <v-col cols="12" sm="4">
            <label class="field-label">Open in</label>
            <v-select
              v-model="form.target"
              :items="[
                { title: 'Same window', value: 0 },
                { title: 'New window', value: 1 },
              ]"
              density="comfortable"
              hide-details
            />
          </v-col>
          <v-col cols="12" sm="4">
            <label class="field-label">Weight</label>
            <v-text-field v-model.number="form.weight" type="number" min="0" density="comfortable" hide-details />
          </v-col>
          <v-col cols="12" sm="4">
            <label class="field-label">Published</label>
            <v-select
              v-model="form.published"
              :items="[
                { title: 'Yes', value: 1 },
                { title: 'No', value: 0 },
              ]"
              density="comfortable"
              hide-details
            />
          </v-col>
        </v-row>

        <div class="d-flex justify-end ga-2 mt-6">
          <v-btn variant="text" @click="router.push('/')">Cancel</v-btn>
          <v-btn color="primary" variant="flat" type="button" :loading="saving" @click="submit">Save</v-btn>
        </div>
      </v-form>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useMenuApi } from '../composables/useMenuApi';
import HtmlBodyEditor from '../components/HtmlBodyEditor.vue';

const props = defineProps({
  id: { type: [String, Number], default: null },
  isNew: { type: Boolean, default: false },
  linktype: { type: Number, default: 0 },
});

const router = useRouter();
const { siteUrl } = useAppConfig();
const { getMenu, createMenu, updateMenu, listParents } = useMenuApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const formRef = ref(null);
const bodyEditor = ref(null);
const pageLoading = ref(false);
const loadError = ref(null);
const saveError = ref(null);
const urlSubmitError = ref(null);
const saving = ref(false);
const parents = ref([]);

const form = reactive({
  title: '',
  url: '',
  body: '',
  pid: 0,
  target: 0,
  weight: 0,
  published: 1,
  linktype: Number(props.linktype) === 1 ? 1 : 0,
});

const isPage = computed(() => form.linktype !== 1);
const pageTitle = computed(() => {
  if (props.isNew) return form.linktype === 1 ? 'Add link' : 'Add page';
  return form.linktype === 1 ? 'Edit link' : 'Edit page';
});

const breadcrumbItems = computed(() => [
  { title: 'Admin', href: `${siteBaseUrl.value}/admin` },
  { title: 'Menu', href: `${siteBaseUrl.value}/admin/menu` },
  { title: pageTitle.value, disabled: true },
]);

const parentItems = computed(() => [
  { id: 0, title: '— None (top level) —' },
  ...parents.value,
]);

const PAGE_URL_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

const pageUrlIsValid = computed(() => PAGE_URL_PATTERN.test(String(form.url || '').trim()));

const urlPreview = computed(() => {
  const slug = String(form.url || '').trim();
  if (!slug || !pageUrlIsValid.value) return '';
  return `${siteBaseUrl.value}/${slug}`;
});

const required = (v) => (!!String(v || '').trim() || 'Required');

const PAGE_URL_ERROR =
  'Use only lowercase letters, numbers, and hyphens (no spaces, slashes, or special characters)';

async function loadParents(excludeId = null) {
  try {
    parents.value = await listParents(excludeId);
  } catch {
    parents.value = [];
  }
}

async function loadItem() {
  if (props.isNew || !props.id) {
    form.linktype = Number(props.linktype) === 1 ? 1 : 0;
    await loadParents(null);
    return;
  }
  pageLoading.value = true;
  loadError.value = null;
  try {
    const item = await getMenu(props.id);
    form.title = item.title || '';
    form.url = item.url || '';
    form.body = item.body || '';
    form.pid = Number(item.pid) || 0;
    form.target = Number(item.target) ? 1 : 0;
    form.weight = Number(item.weight) || 0;
    form.published = Number(item.published) ? 1 : 0;
    form.linktype = Number(item.linktype) === 1 ? 1 : 0;
    await loadParents(item.id);
  } catch (e) {
    loadError.value = e?.response?.data?.message || e.message || 'Failed to load menu item';
  } finally {
    pageLoading.value = false;
  }
}

async function submit() {
  saveError.value = null;
  urlSubmitError.value = null;

  const valid = await formRef.value?.validate();
  if (valid && valid.valid === false) return;

  const url = form.url.trim();
  if (isPage.value && !PAGE_URL_PATTERN.test(url)) {
    urlSubmitError.value = PAGE_URL_ERROR;
    return;
  }

  saving.value = true;
  try {
    let body = form.body;
    if (isPage.value && bodyEditor.value?.getSanitizedHtml) {
      body = bodyEditor.value.getSanitizedHtml();
    }
    const payload = {
      title: form.title.trim(),
      url,
      body: isPage.value ? body : '',
      pid: Number(form.pid) || 0,
      target: Number(form.target) ? 1 : 0,
      weight: Number(form.weight) || 0,
      published: Number(form.published) ? 1 : 0,
      linktype: form.linktype,
    };
    if (props.isNew) {
      await createMenu(payload);
    } else {
      await updateMenu(props.id, payload);
    }
    router.push('/');
  } catch (e) {
    const msg = e?.response?.data?.message || e.message || 'Save failed';
    // Surface URL validation from the API on the field when relevant
    if (isPage.value && /url/i.test(msg)) {
      urlSubmitError.value = msg;
    } else {
      saveError.value = msg;
    }
  } finally {
    saving.value = false;
  }
}

onMounted(loadItem);
</script>

<style scoped>
.menu-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.menu-breadcrumbs :deep(.v-breadcrumbs-item),
.menu-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
.field-group {
  margin-bottom: 16px;
}
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.7);
  margin-bottom: 4px;
}
</style>
