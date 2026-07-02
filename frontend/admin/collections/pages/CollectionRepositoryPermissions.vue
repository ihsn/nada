<template>
  <div>
    <div class="permissions-page-header">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push({ path: '/' })" />
      <h1 class="text-h5 font-weight-medium">Collection permissions</h1>
      <v-chip v-if="repositoryLabel" size="small" variant="tonal" color="primary">{{ repositoryLabel }}</v-chip>
    </div>

    <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = null">
      {{ error }}
    </v-alert>

    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

    <template v-if="!loading && catalog">
      <div class="add-user-access-wrap">
        <v-card elevation="1" rounded="lg">
          <v-card-title class="text-subtitle-1 py-3">Add or update user access</v-card-title>
          <v-card-text class="assign-card-body">
            <v-row dense>
              <v-col cols="12" md="5">
                <div class="text-caption text-medium-emphasis mb-1" id="perm-assign-user-label">Search user</div>
                <v-autocomplete
                  id="perm-assign-user"
                  v-model="assign.user"
                  :items="userSearchItems"
                  :loading="searchLoading"
                  item-title="label"
                  item-value="value"
                  placeholder="Type username or email (min 2 characters)"
                  variant="outlined"
                  density="compact"
                  hide-details="auto"
                  clearable
                  return-object
                  aria-labelledby="perm-assign-user-label"
                  @update:search="onUserSearch"
                />
              </v-col>
              <v-col cols="12" md="7">
                <div class="text-caption text-medium-emphasis mb-1" id="perm-assign-perms-label">Permissions</div>
                <v-select
                  id="perm-assign-perms"
                  v-model="assign.permissions"
                  :items="permissionSelectItems"
                  item-title="title"
                  item-value="value"
                  placeholder="Select one or more permissions"
                  multiple
                  chips
                  closable-chips
                  variant="outlined"
                  density="compact"
                  hide-details="auto"
                  clearable
                  class="perm-multiselect"
                  aria-labelledby="perm-assign-perms-label"
                />
              </v-col>
            </v-row>
            <p v-if="assignBarHint" class="assign-bar-hint text-caption text-medium-emphasis">{{ assignBarHint }}</p>
          </v-card-text>
          <v-divider />
          <v-card-actions class="assign-card-actions">
            <v-spacer />
            <v-btn color="primary" variant="flat" :loading="saving" :disabled="!assign.user" @click="submitAssign">
              Save for selected user
            </v-btn>
          </v-card-actions>
        </v-card>
      </div>

      <v-card elevation="1" rounded="lg">
        <v-card-title class="text-subtitle-1 py-3">Users with access</v-card-title>
        <v-data-table
          :headers="headers"
          :items="catalog.users"
          :items-per-page="25"
          density="compact"
          class="elevation-0"
        >
          <template #item.permissions="{ item }">
            <div class="d-flex flex-wrap gap-1 py-1">
              <v-chip v-for="p in item.permissions" :key="p" size="x-small" variant="outlined">{{ p }}</v-chip>
              <span v-if="!item.permissions?.length" class="text-medium-emphasis">—</span>
            </div>
          </template>
          <template #item.actions="{ item }">
            <v-btn size="small" variant="text" color="primary" @click="openEdit(item)">Edit</v-btn>
            <v-btn size="small" variant="text" color="error" @click="confirmRemove(item)">Remove all</v-btn>
          </template>
          <template #no-data>
            <div class="text-medium-emphasis pa-4">No per-user grants for this collection yet.</div>
          </template>
        </v-data-table>
      </v-card>
    </template>

    <v-dialog v-model="editDialog.open" max-width="560" scrollable>
      <v-card v-if="editDialog.user">
        <v-card-title class="text-h6">Edit — {{ editDialog.user.username }}</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <div class="text-caption text-medium-emphasis mb-1" id="perm-edit-perms-label">Permissions</div>
          <v-select
            id="perm-edit-perms"
            v-model="editDialog.permissions"
            aria-labelledby="perm-edit-perms-label"
            :items="permissionSelectItems"
            item-title="title"
            item-value="value"
            multiple
            chips
            closable-chips
            variant="outlined"
            density="compact"
            hide-details="auto"
            clearable
            class="perm-multiselect"
          />
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="editDialog.open = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="saving" @click="saveEdit">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack.open" :color="snack.color" location="bottom right" :timeout="4000">
      {{ snack.message }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRepositoryAclApi } from '../composables/useRepositoryAclApi';

defineOptions({ name: 'CollectionRepositoryPermissions' });

const route = useRoute();
const router = useRouter();
const { fetchRepositoryAcl, saveUserRepositoryAcl } = useRepositoryAclApi();

const repositoryPk = computed(() => route.params.repositoryId);

const loading = ref(true);
const saving = ref(false);
const searchLoading = ref(false);
const error = ref(null);
const catalog = ref(null);

const assign = reactive({
  user: null,
  permissions: [],
});

const userSearchItems = ref([]);
let searchTimer = null;

const editDialog = reactive({
  open: false,
  user: null,
  permissions: [],
});

const snack = reactive({ open: false, message: '', color: 'success' });

const repositoryLabel = computed(() => {
  if (!catalog.value?.repository) return '';
  const r = catalog.value.repository;
  return `${r.title} (${r.repositoryid})`;
});

/** When editing an existing user via the assign bar, explain replace semantics + prefilled chips */
const assignBarHint = computed(() => {
  if (!assign.user?.value) return '';
  const row = catalog.value?.users?.find((r) => r.user_id === assign.user.value);
  if (row?.permissions?.length) {
    return 'This user already has access on this collection — their permissions are loaded in the field above. Adjust and save; save replaces study and licensed-request grants for this collection with exactly what you select.';
  }
  return 'Save assigns the permissions you select for this collection.';
});

/** Flat list for v-select multi-select (studies + licensed requests). */
const permissionSelectItems = computed(() => {
  const c = catalog.value;
  if (!c) return [];
  const study = (c.study_permissions || []).map(r => ({
    title: `Studies — ${r.label}`,
    value: r.key,
  }));
  const licensed = (c.licensed_permissions || []).map(r => ({
    title: `Licensed requests — ${r.label}`,
    value: r.key,
  }));
  return [...study, ...licensed];
});

const headers = [
  { title: 'User', key: 'username', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Permissions', key: 'permissions', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end', width: '200px' },
];

async function loadCatalog(userQ = '') {
  loading.value = true;
  error.value = null;
  try {
    const data = await fetchRepositoryAcl(repositoryPk.value, userQ);
    catalog.value = data;
    if (!userQ) {
      userSearchItems.value = [];
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load';
    catalog.value = null;
  } finally {
    loading.value = false;
  }
}

function onUserSearch(q) {
  const term = (q || '').trim();
  if (searchTimer) clearTimeout(searchTimer);
  if (term.length < 2) {
    userSearchItems.value = [];
    return;
  }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true;
    try {
      const data = await fetchRepositoryAcl(repositoryPk.value, term);
      userSearchItems.value = (data.user_search || []).map(u => ({
        value: u.id,
        label: `${u.username} — ${u.email || ''}`,
        raw: u,
      }));
    } catch {
      userSearchItems.value = [];
    } finally {
      searchLoading.value = false;
    }
  }, 300);
}

watch(
  () => route.params.repositoryId,
  () => {
    assign.user = null;
    assign.permissions = [];
    loadCatalog();
  }
);

/** Prefill assign.permissions when choosing a user who already has grants (avoid wiping tiers by saving a partial selection). Re-sync when catalog.users arrives after selection. */
watch(
  () => [assign.user?.value, catalog.value?.users],
  () => {
    if (!assign.user?.value) {
      assign.permissions = [];
      return;
    }
    const row = catalog.value?.users?.find((r) => r.user_id === assign.user.value);
    assign.permissions = row?.permissions?.length ? [...row.permissions] : [];
  },
  { deep: true }
);

onMounted(() => {
  loadCatalog();
});

async function submitAssign() {
  if (!assign.user?.value) return;
  saving.value = true;
  error.value = null;
  try {
    const data = await saveUserRepositoryAcl(repositoryPk.value, assign.user.value, assign.permissions);
    catalog.value = data;
    snack.message = 'Saved';
    snack.color = 'success';
    snack.open = true;
    assign.user = null;
    assign.permissions = [];
    userSearchItems.value = [];
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Save failed';
  } finally {
    saving.value = false;
  }
}

function openEdit(row) {
  editDialog.user = row;
  editDialog.permissions = [...(row.permissions || [])];
  editDialog.open = true;
}

async function saveEdit() {
  if (!editDialog.user) return;
  saving.value = true;
  error.value = null;
  try {
    const data = await saveUserRepositoryAcl(repositoryPk.value, editDialog.user.user_id, editDialog.permissions);
    catalog.value = data;
    editDialog.open = false;
    snack.message = 'Saved';
    snack.color = 'success';
    snack.open = true;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Save failed';
  } finally {
    saving.value = false;
  }
}

async function confirmRemove(row) {
  if (!window.confirm(`Remove all managed permissions for ${row.username} on this collection?`)) return;
  saving.value = true;
  error.value = null;
  try {
    const data = await saveUserRepositoryAcl(repositoryPk.value, row.user_id, []);
    catalog.value = data;
    snack.message = 'Removed';
    snack.color = 'success';
    snack.open = true;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Remove failed';
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.permissions-page-header {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px 12px;
  margin-bottom: 1.5rem;
}

.permissions-page-header h1 {
  margin: 0;
}

.add-user-access-wrap {
  margin-bottom: 2.5rem;
}

.assign-card-body {
  padding: 0 20px 24px;
}

.assign-bar-hint {
  margin-top: 12px;
  margin-bottom: 0;
}

.assign-card-actions {
  padding: 20px 20px 16px;
}

.perm-multiselect :deep(.v-field__input) {
  flex-wrap: wrap;
}
</style>
