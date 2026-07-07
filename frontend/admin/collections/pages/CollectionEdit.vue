<template>
  <div>
    <div class="d-flex align-center flex-wrap mb-4 ga-2">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
      <h1 class="text-h5 font-weight-medium">Edit Collection</h1>
      <v-spacer />
      <v-btn
        v-if="canManageAccess"
        variant="outlined"
        color="primary"
        prepend-icon="mdi-account-key-outline"
        @click="goPermissions"
      >
        User access
      </v-btn>
    </div>

    <v-card max-width="800">
      <v-card-text class="pa-5">
        <div v-if="fetchLoading" class="text-center pa-6">
          <v-progress-circular indeterminate color="primary" />
        </div>
        <template v-else>
          <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = null">
            {{ error }}
          </v-alert>
          <!-- ID read-only display -->
          <div class="field-group">
            <label class="field-label">Collection ID</label>
            <div class="id-display">
              {{ form.repositoryid }}
              <v-btn variant="text" size="x-small" class="ml-2" @click="renameDialog.open = true">Rename</v-btn>
            </div>
          </div>
          <CollectionForm ref="formRef" :form="form" :sections="sections" :id-disabled="true" />
        </template>
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="router.push('/')">Cancel</v-btn>
        <v-btn color="primary" variant="flat" :loading="saveLoading" :disabled="fetchLoading" @click="submit">Save</v-btn>
      </v-card-actions>
    </v-card>

    <!-- Rename Dialog -->
    <v-dialog v-model="renameDialog.open" max-width="400" persistent>
      <v-card>
        <v-card-title class="text-h6 pa-4">Rename Collection ID</v-card-title>
        <v-divider />
        <v-card-text class="pa-4">
          <v-alert v-if="renameDialog.error" type="error" class="mb-4" density="compact" closable @click:close="renameDialog.error = null">
            {{ renameDialog.error }}
          </v-alert>
          <p class="text-body-2 text-medium-emphasis mb-3">
            Current ID: <strong>{{ form.repositoryid }}</strong>
          </p>
          <label class="field-label">New Collection ID</label>
          <v-text-field
            v-model="renameDialog.newId"
            variant="outlined"
            density="compact"
            hide-details="auto"
            autofocus
          />
        </v-card-text>
        <v-divider />
        <v-card-actions class="pa-3">
          <v-spacer />
          <v-btn variant="text" @click="renameDialog.open = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="renameDialog.loading" @click="submitRename">Rename</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import CollectionForm from '../components/CollectionForm.vue';
import { useCollectionsApi } from '../composables/useCollectionsApi';

const router = useRouter();
const route = useRoute();
const { config } = useAppConfig();
const { loading: fetchLoading, getCollection, getSections, updateCollection, renameCollection } = useCollectionsApi();

const formRef = ref(null);
const saveLoading = ref(false);
const error = ref(null);
const sections = ref([]);
const collectionPk = ref(null);
const canManageAccessOnCollection = ref(false);

const canManageAccess = computed(() =>
  !!config.value?.canManageCollectionAccess || canManageAccessOnCollection.value
);

function goPermissions() {
  if (!collectionPk.value) return;
  router.push({ name: 'collection-permissions', params: { repositoryId: String(collectionPk.value) } });
}

const form = reactive({
  repositoryid: '',
  title: '',
  short_text: '',
  long_text: '',
  thumbnail: '',
  thumbnailFile: null,
  weight: 0,
  section: 0,
  ispublished: 0,
});

const renameDialog = reactive({
  open: false,
  newId: '',
  error: null,
  loading: false,
});

async function submit() {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  saveLoading.value = true;
  error.value = null;
  try {
    await updateCollection(form);
    router.push('/');
  } catch (e) {
    error.value = e?.response?.data?.message || e.message || 'An error occurred';
  } finally {
    saveLoading.value = false;
  }
}

async function submitRename() {
  if (!renameDialog.newId.trim()) return;
  renameDialog.loading = true;
  renameDialog.error = null;
  try {
    await renameCollection(form.repositoryid, renameDialog.newId.trim());
    router.push('/');
  } catch (e) {
    renameDialog.error = e?.response?.data?.message || e.message || 'An error occurred';
  } finally {
    renameDialog.loading = false;
  }
}

onMounted(async () => {
  [sections.value] = await Promise.all([
    getSections(),
    (async () => {
      try {
        const collection = await getCollection(route.params.repositoryid);
        collectionPk.value = collection.id;
        canManageAccessOnCollection.value = !!collection.can_manage_access;
        form.repositoryid = collection.repositoryid;
        form.title = collection.title || '';
        form.short_text = collection.short_text || '';
        form.long_text = collection.long_text || '';
        form.thumbnail = collection.thumbnail || '';
        form.weight = collection.weight ?? 0;
        form.section = collection.section ?? 0;
        form.ispublished = collection.ispublished ?? 0;
      } catch {
        error.value = 'Failed to load collection.';
      }
    })(),
  ]);
});
</script>

<style scoped>
.field-group {
  margin-bottom: 16px;
}
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0,0,0,0.7);
  margin-bottom: 4px;
}
.id-display {
  font-size: 0.95rem;
  font-weight: 500;
  display: flex;
  align-items: center;
}
</style>
