<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { extractApiError } from '../composables/apiErrors';
import { useDepositApi } from '../composables/useDepositApi';
import DepositExportItems from './DepositExportItems.vue';

const STATUS_META = {
  draft: { icon: 'mdi-file-document-outline' },
  submitted: { icon: 'mdi-inbox-arrow-down' },
  accepted: { icon: 'mdi-check-circle-outline' },
  processed: { icon: 'mdi-sync' },
  closed: { icon: 'mdi-lock-check-outline' },
};

const { config } = useAppConfig();
const { fetchProjects, deleteProject, requestReopen } = useDepositApi();

const projects = ref(Array.isArray(config.value?.projects) ? config.value.projects : []);
const total = ref(Number(config.value?.total) || projects.value.length);
const page = ref(Math.max(1, Number(config.value?.listPage) || 1));
const pageSize = ref(Math.min(100, Math.max(1, Number(config.value?.pageSize) || 100)));
const totalPages = ref(Math.max(1, Number(config.value?.totalPages) || 1));
const loading = ref(!projects.value.length);
const saving = ref(false);
const loadError = ref('');
const query = ref(String(config.value?.searchQuery || ''));
const queryDebounced = ref(String(config.value?.searchQuery || '').trim());
const statusFilter = ref(String(config.value?.statusFilter || ''));
const SORTABLE = ['status', 'title', 'created_by', 'created_on', 'last_modified'];
const sortBy = ref('created_on');
const sortOrder = ref('desc');
const snackbar = ref({ show: false, text: '', color: 'success' });
let searchTimer = null;

const deleteTarget = ref(null);
const deleteError = ref('');

const reopenTarget = ref(null);
const reopenReason = ref('');
const reopenError = ref('');

const lbl = computed(() => {
  const labels = config.value?.labels || {};
  return {
    myProjects: labels.myProjects || 'My projects',
    newProject: labels.newProject || 'Create new project',
    startProject: labels.startProject || 'To start depositing data, create a new project.',
    projectType: labels.projectType || 'Type',
    status: labels.status || 'Status',
    createdBy: labels.createdBy || 'Created by',
    createdOn: labels.createdOn || 'Date created',
    lastModified: labels.lastModified || 'Last modified',
    actions: labels.actions || 'Actions',
    edit: labels.edit || 'Edit',
    summary: labels.summary || 'Summary',
    email: labels.email || 'Email',
    export: labels.export || 'Export',
    delete: labels.delete || 'Delete',
    reopen: labels.reopen || 'Request reopen',
    reopenReason: labels.reopenReason || 'Reason for reopening',
    reopenRequested: labels.reopenRequested || 'Reopen requested',
    reopenSent: labels.reopenSent || 'Reopen request sent',
    confirmDelete: labels.confirmDelete || 'Delete project',
    confirmDeleteBody:
      labels.confirmDeleteBody || 'Are you sure you want to delete this project? This cannot be undone.',
    cancel: labels.cancel || 'Cancel',
    save: labels.save || 'Save',
    submit: labels.submitProject || 'Submit',
    title: labels.title || 'Title',
    shortname: labels.shortname || 'Short name',
    description: labels.description || 'Description',
    collaborators: labels.collaborators || 'Collaborators',
    collaboratorHelp: labels.collaboratorHelp || 'Add the email addresses of people who can edit this project.',
    titleHelp: labels.titleHelp || 'Provide the full title of your project.',
    shortnameHelp: labels.shortnameHelp || 'Provide a short acronym for your project.',
    descriptionHelp: labels.descriptionHelp || 'Provide a detailed description for your project.',
    searchProjects: labels.searchProjects || 'Search projects',
    allStatuses: labels.allStatuses || 'All',
    noMatchingProjects: labels.noMatchingProjects || 'No matching projects.',
    showingRange: labels.showingRange || 'Showing %s–%s of %s',
    loadFailed: labels.loadFailed || 'Failed to load',
    saveFailed: labels.saveFailed || 'Save failed',
    requestFailed: labels.requestFailed || 'Request failed',
    validationFailed: labels.validationFailed || 'Validation failed',
    projectDeleted: labels.projectDeleted || 'Project deleted',
    add: labels.add || 'Add',
    processedLocked: labels.processedLocked || 'This project has been processed and cannot be reopened.',
    na: labels.na || 'N/A',
    projectId: labels.projectId || 'Project ID',
  };
});

const createLocation = { name: 'create' };
const isEmptyCatalog = computed(
  () => total.value === 0 && !queryDebounced.value && !statusFilter.value
);
const showFilters = computed(
  () => !isEmptyCatalog.value || !!query.value || !!queryDebounced.value || !!statusFilter.value
);
const statusOptions = computed(() =>
  Object.keys(STATUS_META).map((value) => ({
    title: value.charAt(0).toUpperCase() + value.slice(1),
    value,
  }))
);
const rangeFrom = computed(() => (total.value === 0 ? 0 : (page.value - 1) * pageSize.value + 1));
const rangeTo = computed(() => Math.min(total.value, page.value * pageSize.value));
const rangeLabel = computed(() => {
  const tpl = lbl.value.showingRange || 'Showing %s–%s of %s';
  const parts = tpl.split('%s');
  if (parts.length < 4) {
    return `Showing ${rangeFrom.value}–${rangeTo.value} of ${total.value}`;
  }
  return `${parts[0]}${rangeFrom.value}${parts[1]}${rangeTo.value}${parts[2]}${total.value}${parts[3]}`;
});

function showSnack(text, color = 'success') {
  snackbar.value = { show: true, text, color };
}

function statusMeta(status) {
  return STATUS_META[String(status || '').toLowerCase()] || {
    icon: 'mdi-folder-outline',
  };
}

async function loadProjects() {
  loading.value = true;
  loadError.value = '';
  try {
    const data = await fetchProjects({
      page: page.value,
      pageSize: pageSize.value,
      q: queryDebounced.value,
      status: statusFilter.value || '',
      sortBy: sortBy.value,
      sortOrder: sortOrder.value,
    });
    projects.value = data.projects;
    total.value = data.total;
    pageSize.value = Math.min(100, data.pageSize || pageSize.value);
    totalPages.value = Math.max(1, data.totalPages || 1);
    if (data.page && data.page !== page.value) {
      page.value = data.page;
    }
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    loadError.value = extracted.message || lbl.value.loadFailed;
  } finally {
    loading.value = false;
  }
}

function openDelete(project) {
  deleteTarget.value = project;
  deleteError.value = '';
}

async function confirmDelete() {
  const project = deleteTarget.value;
  if (!project) return;
  saving.value = true;
  deleteError.value = '';
  try {
    await deleteProject(project.id);
    deleteTarget.value = null;
    showSnack(lbl.value.projectDeleted);
    if (projects.value.length <= 1 && page.value > 1) {
      page.value -= 1;
    } else {
      await loadProjects();
    }
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    deleteError.value = extracted.message || lbl.value.saveFailed;
  } finally {
    saving.value = false;
  }
}

function openReopen(project) {
  reopenTarget.value = project;
  reopenReason.value = '';
  reopenError.value = '';
}

async function submitReopen() {
  const project = reopenTarget.value;
  if (!project) return;
  saving.value = true;
  reopenError.value = '';
  try {
    await requestReopen(project.id, reopenReason.value);
    reopenTarget.value = null;
    showSnack(lbl.value.reopenSent);
    await loadProjects();
  } catch (e) {
    const extracted = extractApiError(e, lbl.value);
    reopenError.value = extracted.message || lbl.value.saveFailed;
  } finally {
    saving.value = false;
  }
}

watch(query, (value) => {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    const next = String(value || '').trim();
    if (next === queryDebounced.value) return;
    queryDebounced.value = next;
    if (page.value !== 1) {
      page.value = 1;
    }
  }, 300);
});

watch(statusFilter, () => {
  if (page.value !== 1) {
    page.value = 1;
  }
});

function setSort(key) {
  if (!SORTABLE.includes(key)) return;
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortOrder.value = key === 'created_on' || key === 'last_modified' ? 'desc' : 'asc';
  }
  if (page.value !== 1) {
    page.value = 1;
  }
}

function sortIcon(key) {
  if (sortBy.value !== key) return 'mdi-unfold-more-horizontal';
  return sortOrder.value === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down';
}

function ariaSort(key) {
  if (sortBy.value !== key) return 'none';
  return sortOrder.value === 'asc' ? 'ascending' : 'descending';
}

watch([page, queryDebounced, statusFilter, sortBy, sortOrder], () => {
  loadProjects();
}, { immediate: true });

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer);
});

onMounted(async () => {
  const flash = String(config.value?.flashMessage || '').trim();
  const flashError = String(config.value?.flashError || '').trim();
  if (flash) showSnack(flash);
  if (flashError) showSnack(flashError, 'error');
});
</script>

<template>
  <v-app class="dd-home-app">
    <v-main class="dd-home-main">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="dd-home">
        <div class="dd-home-bar d-flex align-center justify-space-between flex-wrap ga-3">
          <h1 class="text-h5 font-weight-medium mb-0">{{ lbl.myProjects }}</h1>
          <v-btn color="primary" class="text-none dd-primary-btn" prepend-icon="mdi-plus" :to="createLocation">
            {{ lbl.newProject }}
          </v-btn>
        </div>

        <v-alert v-if="loadError" type="error" variant="tonal" class="mb-4" density="compact" closable>
          {{ loadError }}
        </v-alert>
        <v-progress-linear v-if="loading && projects.length" indeterminate color="primary" class="mb-2" />

        <div v-if="showFilters" class="dd-home-filters mb-4">
          <v-text-field
            v-model="query"
            :placeholder="lbl.searchProjects"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            class="dd-home-search"
          />
          <v-select
            v-model="statusFilter"
            :items="statusOptions"
            :label="lbl.status"
            :placeholder="lbl.allStatuses"
            variant="outlined"
            density="compact"
            hide-details
            clearable
            class="dd-home-status-filter"
          />
        </div>

        <div v-if="loading && !projects.length" class="d-flex justify-center py-12">
          <v-progress-circular indeterminate color="primary" />
        </div>

        <div v-else-if="isEmptyCatalog" class="dd-home-empty">
          <v-icon icon="mdi-folder-plus-outline" size="48" class="mb-3 dd-home-empty-icon" />
          <div class="text-body-1 mb-4">{{ lbl.startProject }}</div>
          <v-btn color="primary" class="text-none dd-primary-btn" prepend-icon="mdi-plus" :to="createLocation">
            {{ lbl.newProject }}
          </v-btn>
        </div>

        <div v-else-if="!projects.length" class="text-medium-emphasis py-8 text-center">
          {{ lbl.noMatchingProjects }}
        </div>

        <div v-else class="dd-home-table-wrap">
          <table class="dd-home-table">
            <thead>
              <tr>
                <th class="dd-col-status" :aria-sort="ariaSort('status')">
                  <button type="button" class="dd-home-sort" @click="setSort('status')">
                    <span>{{ lbl.status }}</span>
                    <v-icon size="16" :class="{ 'dd-home-sort-icon--active': sortBy === 'status' }">
                      {{ sortIcon('status') }}
                    </v-icon>
                  </button>
                </th>
                <th :aria-sort="ariaSort('title')">
                  <button type="button" class="dd-home-sort" @click="setSort('title')">
                    <span>{{ lbl.title }}</span>
                    <v-icon size="16" :class="{ 'dd-home-sort-icon--active': sortBy === 'title' }">
                      {{ sortIcon('title') }}
                    </v-icon>
                  </button>
                </th>
                <th>{{ lbl.projectType }}</th>
                <th :aria-sort="ariaSort('created_by')">
                  <button type="button" class="dd-home-sort" @click="setSort('created_by')">
                    <span>{{ lbl.createdBy }}</span>
                    <v-icon size="16" :class="{ 'dd-home-sort-icon--active': sortBy === 'created_by' }">
                      {{ sortIcon('created_by') }}
                    </v-icon>
                  </button>
                </th>
                <th :aria-sort="ariaSort('created_on')">
                  <button type="button" class="dd-home-sort" @click="setSort('created_on')">
                    <span>{{ lbl.createdOn }}</span>
                    <v-icon size="16" :class="{ 'dd-home-sort-icon--active': sortBy === 'created_on' }">
                      {{ sortIcon('created_on') }}
                    </v-icon>
                  </button>
                </th>
                <th :aria-sort="ariaSort('last_modified')">
                  <button type="button" class="dd-home-sort" @click="setSort('last_modified')">
                    <span>{{ lbl.lastModified }}</span>
                    <v-icon size="16" :class="{ 'dd-home-sort-icon--active': sortBy === 'last_modified' }">
                      {{ sortIcon('last_modified') }}
                    </v-icon>
                  </button>
                </th>
                <th class="dd-col-actions">
                  <span class="d-sr-only">{{ lbl.actions }}</span>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="project in projects" :key="project.id">
                <td class="dd-col-status">
                  <div class="dd-home-status" :class="`dd-status-${project.status}`">
                    <v-icon :icon="statusMeta(project.status).icon" size="28" />
                    <span>{{ project.status }}</span>
                  </div>
                </td>
                <td class="dd-col-title">
                  <router-link class="dd-home-title" :to="{ name: 'study', params: { id: String(project.id) } }">{{ project.title }}</router-link>
                  <div class="dd-home-sub">
                    <span v-if="project.shortname" class="dd-home-shortname">{{ project.shortname }}</span>
                    <span class="dd-home-id" :title="lbl.projectId">#{{ project.id }}</span>
                  </div>
                </td>
                <td class="dd-col-type">
                  <span v-if="project.data_type_label" class="dd-type-pill">{{ project.data_type_label }}</span>
                  <span v-else class="text-medium-emphasis">{{ lbl.na }}</span>
                </td>
                <td class="dd-col-meta">{{ project.created_by || lbl.na }}</td>
                <td class="dd-col-meta">{{ project.created_on || lbl.na }}</td>
                <td class="dd-col-meta">{{ project.last_modified || lbl.na }}</td>
                <td class="dd-col-actions">
                  <v-menu location="bottom end" :close-on-content-click="false">
                    <template #activator="{ props: menuProps }">
                      <v-btn
                        icon
                        variant="text"
                        size="small"
                        v-bind="menuProps"
                        :aria-label="lbl.actions"
                      >
                        <v-icon>mdi-dots-vertical</v-icon>
                      </v-btn>
                    </template>
                    <v-list class="dd-menu-list" density="compact" min-width="180">
                      <v-list-item
                        v-if="project.can_edit"
                        :to="{ name: 'study', params: { id: String(project.id) } }"
                      >
                        <template #prepend>
                          <v-icon size="small">mdi-pencil-outline</v-icon>
                        </template>
                        <v-list-item-title>{{ lbl.edit }}</v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        v-if="project.can_summary"
                        :to="{ name: 'summary', params: { id: String(project.id) } }"
                      >
                        <template #prepend>
                          <v-icon size="small">mdi-printer-outline</v-icon>
                        </template>
                        <v-list-item-title>{{ lbl.summary }}</v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        v-if="project.can_email !== false"
                        :to="{ name: 'email', params: { id: String(project.id) } }"
                      >
                        <template #prepend>
                          <v-icon size="small">mdi-email-outline</v-icon>
                        </template>
                        <v-list-item-title>{{ lbl.email }}</v-list-item-title>
                      </v-list-item>
                      <v-menu
                        v-if="project.can_export !== false"
                        location="end"
                        :close-on-content-click="false"
                      >
                        <template #activator="{ props: exportProps }">
                          <v-list-item v-bind="exportProps">
                            <template #prepend>
                              <v-icon size="small">mdi-download-outline</v-icon>
                            </template>
                            <v-list-item-title>{{ lbl.export }}</v-list-item-title>
                            <template #append>
                              <v-icon size="small">mdi-chevron-right</v-icon>
                            </template>
                          </v-list-item>
                        </template>
                        <v-list class="dd-menu-list" density="compact" min-width="220">
                          <DepositExportItems
                            :project-id="project.id"
                            :data-type="project.data_type"
                            :can-export-ddi="!!project.can_export_ddi"
                          />
                        </v-list>
                      </v-menu>
                      <v-list-item
                        v-if="project.can_delete"
                        @click="openDelete(project)"
                      >
                        <template #prepend>
                          <v-icon size="small" color="error">mdi-delete-outline</v-icon>
                        </template>
                        <v-list-item-title class="text-error">{{ lbl.delete }}</v-list-item-title>
                      </v-list-item>
                      <v-list-item
                        v-if="project.can_reopen"
                        @click="openReopen(project)"
                      >
                        <template #prepend>
                          <v-icon size="small">mdi-lock-open-outline</v-icon>
                        </template>
                        <v-list-item-title>{{ lbl.reopen }}</v-list-item-title>
                      </v-list-item>
                      <v-list-item v-else-if="project.requested_reopen" disabled>
                        <v-list-item-title>{{ lbl.reopenRequested }}</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="total > 0" class="dd-home-pager">
          <div class="text-caption text-medium-emphasis">{{ rangeLabel }}</div>
          <v-pagination
            v-if="totalPages > 1"
            v-model="page"
            :length="totalPages"
            :total-visible="7"
            density="comfortable"
            :disabled="loading"
          />
        </div>
      </div>

      <v-dialog :model-value="!!deleteTarget" max-width="480" @update:model-value="(v) => !v && (deleteTarget = null)">
        <v-card>
          <v-card-title class="text-h6">{{ lbl.confirmDelete }}</v-card-title>
          <v-divider />
          <v-card-text>
            <v-alert v-if="deleteError" type="error" variant="tonal" class="mb-3" density="compact">
              {{ deleteError }}
            </v-alert>
            <p class="mb-2">{{ lbl.confirmDeleteBody }}</p>
            <p v-if="deleteTarget" class="font-weight-medium mb-0">{{ deleteTarget.title }}</p>
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-3">
            <v-spacer />
            <v-btn variant="text" class="text-none" :disabled="saving" @click="deleteTarget = null">
              {{ lbl.cancel }}
            </v-btn>
            <v-btn color="error" class="text-none" :loading="saving" @click="confirmDelete">
              {{ lbl.delete }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog :model-value="!!reopenTarget" max-width="560" @update:model-value="(v) => !v && (reopenTarget = null)">
        <v-card>
          <v-card-title class="text-h6">{{ lbl.reopen }}</v-card-title>
          <v-divider />
          <v-card-text>
            <v-alert v-if="reopenError" type="error" variant="tonal" class="mb-3" density="compact">
              {{ reopenError }}
            </v-alert>
            <p v-if="reopenTarget" class="mb-3">{{ reopenTarget.title }}</p>
            <label class="dd-info-label">{{ lbl.reopenReason }} <span class="text-error">*</span></label>
            <v-textarea v-model="reopenReason" variant="outlined" auto-grow rows="4" hide-details="auto" />
          </v-card-text>
          <v-divider />
          <v-card-actions class="pa-3">
            <v-spacer />
            <v-btn variant="text" class="text-none" :disabled="saving" @click="reopenTarget = null">
              {{ lbl.cancel }}
            </v-btn>
            <v-btn color="primary" class="text-none" :loading="saving" @click="submitReopen">
              {{ lbl.submit }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-main>
  </v-app>
</template>

<style scoped>
.dd-home-app.v-application {
  background: transparent;
  min-height: 0;
}
.dd-home-app :deep(.v-application__wrap) {
  min-height: 0 !important;
}
.dd-home-main {
  padding: 0;
  --v-layout-top: 0px;
}
.dd-home {
  width: 100%;
  margin: 0 auto;
  padding: 8px 0 24px;
}
.dd-home-bar {
  padding: 8px 0 16px;
}
.dd-home-filters {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}
.dd-home-search {
  flex: 1 1 280px;
  max-width: 420px;
}
.dd-home-status-filter {
  flex: 0 1 200px;
  max-width: 220px;
}
.dd-home-app :deep(a.dd-primary-btn.v-btn),
.dd-home-app :deep(a.dd-primary-btn.v-btn:hover),
.dd-home-app :deep(a.dd-primary-btn.v-btn:focus-visible),
.dd-home-app :deep(a.dd-primary-btn.v-btn:visited) {
  color: #fff !important;
  text-decoration: none !important;
}
.dd-home-pager {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 16px 0 8px;
  margin-top: 8px;
}
.dd-home-empty {
  margin: 48px 0;
  padding: 40px 24px;
  text-align: center;
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 12px;
}
.dd-home-empty-icon {
  color: rgba(var(--v-theme-on-surface), 0.35);
}
.dd-home-table-wrap {
  width: 100%;
  overflow-x: auto;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 10px;
  background: #fff;
}
.dd-home-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 760px;
}
.dd-home-table th,
.dd-home-table td {
  padding: 12px 14px;
  text-align: left;
  vertical-align: middle;
}
.dd-home-table thead th {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgba(var(--v-theme-on-surface), 0.55);
  background: rgba(15, 23, 42, 0.03);
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  white-space: nowrap;
}
.dd-home-sort {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  letter-spacing: inherit;
  text-transform: inherit;
  cursor: pointer;
}
.dd-home-sort:hover {
  color: rgba(var(--v-theme-on-surface), 0.87);
}
.dd-home-sort :deep(.v-icon) {
  opacity: 0.35;
}
.dd-home-sort :deep(.dd-home-sort-icon--active) {
  opacity: 1;
  color: rgb(var(--v-theme-primary));
}
.dd-home-table tbody tr {
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
}
.dd-home-table tbody tr:last-child {
  border-bottom: none;
}
.dd-home-table tbody tr:hover {
  background: rgba(21, 101, 192, 0.03);
}
.dd-col-status {
  width: 120px;
  padding-right: 8px;
}
.dd-col-title {
  min-width: 220px;
}
.dd-col-type,
.dd-col-meta {
  white-space: nowrap;
  color: rgba(var(--v-theme-on-surface), 0.7);
  font-size: 0.875rem;
}
.dd-col-actions {
  width: 56px;
  text-align: right;
  padding-right: 8px;
}
.d-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
.dd-home-status {
  width: 80px;
  min-height: 80px;
  padding: 8px 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  color: #fff;
  text-transform: uppercase;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  border-radius: 8px;
  background: #9e9e9e;
}
.dd-home-status :deep(.v-icon) {
  color: inherit;
  opacity: 1;
}
.dd-status-draft {
  background: #fb8c00;
}
.dd-status-submitted {
  background: #0288d1;
}
.dd-status-accepted {
  background: #2e7d32;
}
.dd-status-processed {
  background: #3949ab;
}
.dd-status-closed {
  background: #00695c;
}
.dd-home-title {
  display: block;
  font-size: 0.95rem;
  font-weight: 600;
  line-height: 1.35;
  color: rgb(var(--v-theme-primary)) !important;
  text-decoration: none !important;
}
.dd-home-title:hover {
  text-decoration: underline !important;
}
.dd-home-sub {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px 10px;
  margin-top: 2px;
  font-size: 0.8125rem;
  color: rgba(var(--v-theme-on-surface), 0.55);
}
.dd-home-shortname {
  font-size: inherit;
  color: inherit;
}
.dd-home-id {
  font-variant-numeric: tabular-nums;
}
.dd-type-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(var(--v-theme-primary), 0.08);
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
  font-size: 0.75rem;
}
.dd-info-label {
  display: block;
  margin-bottom: 4px;
  font-size: 0.875rem;
  font-weight: 500;
}
</style>
