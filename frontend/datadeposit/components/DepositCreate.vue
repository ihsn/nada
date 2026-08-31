<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from '../composables/apiErrors';
import { useDepositApi } from '../composables/useDepositApi';
import DepositBreadcrumb from './DepositBreadcrumb.vue';

const { config } = useAppConfig();
const router = useRouter();
const { createProject } = useDepositApi();

const saving = ref(false);
const createError = ref('');
const createErrors = ref([]);

const projectTypes = computed(() =>
  Array.isArray(config.value?.projectTypes) ? config.value.projectTypes : []
);

const form = ref({
  data_type: defaultDataType(),
  title: '',
  shortname: '',
  description: '',
  collaborators: [''],
});

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    newProject: labels.newProject || 'Create new project',
    projectType: labels.projectType || 'Type',
    projectTypeHelp: labels.projectTypeHelp || 'Select the kind of data you are depositing.',
    myProjects: labels.myProjects || 'My projects',
    title: labels.title || 'Title',
    shortname: labels.shortname || 'Short name',
    description: labels.description || 'Description',
    collaborators: labels.collaborators || 'Collaborators',
    collaboratorHelp:
      labels.collaboratorHelp || 'Add the email addresses of people who can edit this project.',
    titleHelp: labels.titleHelp || 'Provide the full title of your project.',
    shortnameHelp: labels.shortnameHelp || 'Provide a short acronym for your project.',
    descriptionHelp: labels.descriptionHelp || 'Provide a detailed description for your project.',
    add: labels.add || 'Add',
    cancel: labels.cancel || 'Cancel',
    save: labels.save || 'Save',
    saveFailed: labels.saveFailed || 'Save failed',
    validationFailed: labels.validationFailed || 'Validation failed',
  };
});

const breadcrumbItems = computed(() => [
  { title: lbl.value.myProjects, to: { name: 'projects' } },
  { title: lbl.value.newProject },
]);

function defaultDataType() {
  const types = Array.isArray(config.value?.projectTypes) ? config.value.projectTypes : [];
  return types[0]?.value || 'survey';
}

function addCollaborator() {
  form.value.collaborators.push('');
}

function removeCollaborator(index) {
  form.value.collaborators.splice(index, 1);
  if (!form.value.collaborators.length) {
    form.value.collaborators.push('');
  }
}

async function submitCreate() {
  saving.value = true;
  createError.value = '';
  createErrors.value = [];
  try {
    const data = await createProject({
      data_type: form.value.data_type || 'survey',
      title: form.value.title,
      shortname: form.value.shortname,
      description: form.value.description,
      collaborators: form.value.collaborators,
    });
    const id = data.id;
    if (id) {
      await router.push({ name: 'study', params: { id: String(id) }, query: { step: 'info' } });
      return;
    }
    await router.push({ name: 'projects' });
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    createError.value = extracted.message || lbl.value.saveFailed;
    createErrors.value = extracted.errors || [];
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <v-app class="dd-create-app">
    <v-main class="dd-create-main">
      <div class="dd-create">
        <DepositBreadcrumb :items="breadcrumbItems" />
        <h1 class="text-h5 font-weight-medium mb-6">{{ lbl.newProject }}</h1>

        <v-alert v-if="createError" type="error" variant="tonal" class="mb-4" density="compact" closable>
          <div>{{ createError }}</div>
          <div v-for="(err, i) in createErrors" :key="i" class="text-body-2 mt-1">
            {{ err.message }}
          </div>
        </v-alert>

        <div class="dd-info-field">
          <label class="dd-info-label">{{ lbl.projectType }} <span class="text-error">*</span></label>
          <p class="text-caption text-medium-emphasis mb-3">{{ lbl.projectTypeHelp }}</p>
          <div class="dd-type-grid" role="radiogroup" :aria-label="lbl.projectType">
            <button
              v-for="type in projectTypes"
              :key="type.value"
              type="button"
              role="radio"
              class="dd-type-card"
              :class="{ 'is-selected': form.data_type === type.value }"
              :aria-checked="form.data_type === type.value"
              @click="form.data_type = type.value"
            >
              <v-icon :icon="type.icon || 'mdi-folder-outline'" size="28" />
              <span>{{ type.title }}</span>
            </button>
          </div>
        </div>

        <div class="dd-info-field">
          <label class="dd-info-label">{{ lbl.title }} <span class="text-error">*</span></label>
          <p class="text-caption text-medium-emphasis mb-1">{{ lbl.titleHelp }}</p>
          <v-text-field v-model="form.title" variant="outlined" density="comfortable" hide-details="auto" />
        </div>
        <div class="dd-info-field">
          <label class="dd-info-label">{{ lbl.shortname }} <span class="text-error">*</span></label>
          <p class="text-caption text-medium-emphasis mb-1">{{ lbl.shortnameHelp }}</p>
          <v-text-field
            v-model="form.shortname"
            variant="outlined"
            density="comfortable"
            hide-details="auto"
          />
        </div>
        <div class="dd-info-field">
          <label class="dd-info-label">{{ lbl.description }}</label>
          <p class="text-caption text-medium-emphasis mb-1">{{ lbl.descriptionHelp }}</p>
          <v-textarea
            v-model="form.description"
            variant="outlined"
            auto-grow
            rows="3"
            hide-details="auto"
          />
        </div>
        <div class="dd-info-field">
          <div class="d-flex align-start justify-space-between ga-2 mb-1">
            <div>
              <div class="dd-info-label">{{ lbl.collaborators }}</div>
              <p class="text-caption text-medium-emphasis mb-0">{{ lbl.collaboratorHelp }}</p>
            </div>
            <v-btn size="small" variant="text" class="text-none" prepend-icon="mdi-plus" @click="addCollaborator">
              {{ lbl.add }}
            </v-btn>
          </div>
          <div v-for="(_, i) in form.collaborators" :key="i" class="d-flex align-center ga-2 mb-2">
            <v-text-field
              v-model="form.collaborators[i]"
              variant="outlined"
              density="comfortable"
              hide-details
              class="flex-grow-1"
            />
            <v-btn
              icon="mdi-close"
              variant="text"
              size="small"
              :disabled="form.collaborators.length < 2"
              @click="removeCollaborator(i)"
            />
          </div>
        </div>

        <div class="dd-create-actions">
          <v-btn :to="{ name: 'projects' }" variant="text" class="text-none" :disabled="saving">
            {{ lbl.cancel }}
          </v-btn>
          <v-btn color="primary" class="text-none" :loading="saving" @click="submitCreate">
            {{ lbl.save }}
          </v-btn>
        </div>
      </div>
    </v-main>
  </v-app>
</template>

<style scoped>
.dd-create-app.v-application {
  background: transparent;
  min-height: 0;
}
.dd-create-app :deep(.v-application__wrap) {
  min-height: 0 !important;
}
.dd-create-main {
  padding: 0;
  --v-layout-top: 0px;
}
.dd-create {
  width: 100%;
  max-width: 760px;
  margin: 0 auto 24px;
  padding: 8px 0 24px;
}
.dd-info-field {
  margin-bottom: 20px;
}
.dd-info-label {
  display: block;
  margin-bottom: 4px;
  font-size: 0.875rem;
  font-weight: 500;
}
.dd-type-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
}
.dd-type-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 96px;
  padding: 12px 8px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.16);
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.02);
  color: inherit;
  cursor: pointer;
  text-align: center;
  font-size: 0.85rem;
  font-weight: 500;
  line-height: 1.25;
}
.dd-type-card:hover {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.04);
}
.dd-type-card.is-selected {
  border-color: rgb(var(--v-theme-primary));
  background: rgba(var(--v-theme-primary), 0.1);
  box-shadow: inset 0 0 0 1px rgb(var(--v-theme-primary));
}
.dd-create-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding-top: 8px;
}
</style>
