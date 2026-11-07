<template>
  <v-dialog
    :model-value="modelValue"
    max-width="480"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6 d-flex align-center justify-space-between">
        <span>{{ lbl.transfer_study || 'Transfer ownership' }}</span>
        <v-btn icon variant="text" size="small" :disabled="saving" @click="close">
          <v-icon icon="mdi-close" />
        </v-btn>
      </v-card-title>
      <v-divider />
      <v-card-text class="px-6 py-5">
        <v-alert v-if="error" type="error" density="compact" class="mb-4" closable @click:close="error = ''">
          {{ error }}
        </v-alert>
        <p class="text-body-2 mb-4">
          {{ lbl.select_repository_hint || 'Select the collection from the list below.' }}
        </p>
        <div class="transfer-field">
          <div class="transfer-field-label">{{ lbl.msg_select_repository || 'Collection' }}</div>
          <v-select
            v-model="repositoryid"
            :items="collectionItems"
            item-title="label"
            item-value="value"
            :loading="loadingCollections"
            :disabled="saving || loadingCollections"
            variant="outlined"
            density="compact"
            hide-details="auto"
          />
        </div>
      </v-card-text>
      <v-divider />
      <v-card-actions class="px-6 py-4">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">{{ lbl.cancel || 'Cancel' }}</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="!repositoryid" @click="submit">
          {{ lbl.transfer || 'Transfer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCatalogStudySidebarApi } from '../composables/useCatalogStudySidebarApi';

defineOptions({ name: 'TransferOwnershipDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'transferred']);

const { config } = useAppConfig();
const { listCollections, transferOwnership } = useCatalogStudySidebarApi();
const lbl = computed(() => config.value?.labels || {});

const repositoryid = ref(null);
const collections = ref([]);
const loadingCollections = ref(false);
const saving = ref(false);
const error = ref('');

const collectionItems = computed(() =>
  collections.value.map((c) => ({
    value: c.repositoryid,
    label: `${c.title} (${String(c.repositoryid).toUpperCase()})`,
  }))
);

function close() {
  emit('update:modelValue', false);
}

async function loadCollections() {
  loadingCollections.value = true;
  error.value = '';
  try {
    collections.value = await listCollections();
  } catch (e) {
    error.value = e?.message || 'Failed to load collections';
    collections.value = [];
  } finally {
    loadingCollections.value = false;
  }
}

async function submit() {
  if (!repositoryid.value) return;
  saving.value = true;
  error.value = '';
  try {
    const data = await transferOwnership(repositoryid.value);
    emit('transferred', data);
    close();
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Transfer failed';
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      repositoryid.value = null;
      error.value = '';
      loadCollections();
    } else {
      repositoryid.value = null;
      error.value = '';
    }
  }
);
</script>

<style scoped>
.transfer-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 6px;
  color: rgba(var(--v-theme-on-surface), 0.9);
}
</style>
