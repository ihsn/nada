<template>
  <div>
    <CodelistDetail
      v-if="codelist"
      :codelist="codelist"
      :loading="loading"
      :enabled-languages="enabledLanguages"
      @back="goBack"
      @refresh="loadCodelist"
      @error="setMessage($event, 'error')"
    />
    <v-progress-linear v-else-if="loading" indeterminate color="primary" class="mb-4" />
    <v-alert v-else type="error" class="mb-4">
      Codelist not found.
      <v-btn variant="text" class="ml-2" @click="goBack">Back to codelists</v-btn>
    </v-alert>
  </div>
</template>

<script setup>
import { ref, computed, watch, inject } from 'vue';
import { useRouter } from 'vue-router';
import CodelistDetail from '../components/CodelistDetail.vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useCodelistsApi } from '../composables/useCodelistsApi';

defineOptions({ name: 'CodelistDetailPage' });

const props = defineProps({
  id: { type: String, required: true },
});

const router = useRouter();
const setMessage = inject('setMessage', () => {});

const { config } = useAppConfig();
const enabledLanguages = computed(() => {
  const list = config.value?.enabledLanguages;
  return Array.isArray(list) ? list : [];
});

const { loading, fetchCodelist } = useCodelistsApi();
const codelist = ref(null);

async function loadCodelist() {
  const id = props.id != null ? Number(props.id) : NaN;
  if (!Number.isInteger(id) || id < 1) {
    codelist.value = null;
    return;
  }
  try {
    const data = await fetchCodelist(id);
    codelist.value = data;
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Failed to load codelist', 'error');
    codelist.value = null;
  }
}

function goBack() {
  router.push({ name: 'codelists' });
}

watch(
  () => props.id,
  (newId) => {
    if (newId) loadCodelist();
    else codelist.value = null;
  },
  { immediate: true }
);
</script>
