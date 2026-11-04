<template>
  <v-dialog
    :model-value="modelValue"
    max-width="520"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h6 d-flex align-center justify-space-between">
        <span>{{ t('transfer_ownership', 'Transfer ownership') }}</span>
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
          {{ t('select_the_repository_from_the_list_below', 'Select the collection from the list below.') }}
        </p>
        <div class="transfer-field mb-5">
          <div class="transfer-field-label">{{ t('msg_select_repository', 'Collection') }}</div>
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
        <div v-if="studies.length" class="transfer-field">
          <div class="transfer-field-label">
            {{ t('msg_studies_to_transfer', 'Studies to transfer') }}
          </div>
          <ul class="transfer-study-list pl-4 mb-0 text-body-2">
            <li v-for="s in studies" :key="s.id">{{ s.title || s.id }}</li>
          </ul>
        </div>
      </v-card-text>
      <v-divider />
      <v-card-actions class="px-6 py-4">
        <v-spacer />
        <v-btn variant="text" :disabled="saving" @click="close">{{ t('cancel', 'Cancel') }}</v-btn>
        <v-btn color="primary" :loading="saving" :disabled="!repositoryid" @click="submit">
          {{ t('transfer', 'Transfer') }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useCatalogApi } from '../composables/useCatalogApi';

defineOptions({ name: 'TransferOwnershipDialog' });

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  /** @type {{ id: number|string, title?: string }[]} */
  studies: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue', 'transferred']);

const { t } = useI18n();
const { listCollections, transferOwnership } = useCatalogApi();

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
  if (!repositoryid.value || !props.studies.length) return;
  saving.value = true;
  error.value = '';
  let failed = 0;
  try {
    for (const s of props.studies) {
      try {
        await transferOwnership(s.id, repositoryid.value);
      } catch (e) {
        console.error('Transfer failed for id', s.id, e);
        failed += 1;
      }
    }
    if (failed > 0 && failed === props.studies.length) {
      error.value = t('transfer_failed', 'Transfer failed');
      return;
    }
    emit('transferred', {
      repositoryid: repositoryid.value,
      failed,
      total: props.studies.length,
    });
    close();
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
.transfer-study-list {
  max-height: 160px;
  overflow-y: auto;
}
</style>
