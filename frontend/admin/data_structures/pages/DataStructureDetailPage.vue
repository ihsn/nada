<template>
  <div class="d-flex flex-column flex-grow-1" style="min-height: 0">
    <DataStructureDetail
      v-if="structure"
      class="flex-grow-1"
      :structure="structure"
      :loading="loading"
      @back="goBack"
      @refresh="loadStructure"
      @deleted="goBack"
    />
    <v-progress-linear v-else-if="loading" indeterminate color="primary" class="mb-4" />
    <v-alert v-else type="error" class="mb-4">
      Data structure not found.
      <v-btn variant="text" class="ml-2" @click="goBack">Back to list</v-btn>
    </v-alert>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import DataStructureDetail from '../components/DataStructureDetail.vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureDetailPage' });

const props = defineProps({
  id: { type: String, required: true },
});

const router = useRouter();
const { loading, fetchDataStructure } = useDataStructuresApi();
const structure = ref(null);

async function loadStructure() {
  const sid = props.id != null ? Number(props.id) : NaN;
  if (!Number.isInteger(sid) || sid < 1) {
    structure.value = null;
    return;
  }
  try {
    structure.value = await fetchDataStructure(sid, true);
  } catch {
    structure.value = null;
  }
}

function goBack() {
  router.push({ name: 'data-structures' });
}

watch(
  () => props.id,
  () => {
    if (props.id) loadStructure();
    else structure.value = null;
  },
  { immediate: true }
);
</script>
