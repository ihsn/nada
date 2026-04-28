<template>
  <div class="d-flex flex-column gap-4">
    <v-btn variant="text" prepend-icon="mdi-arrow-left" class="align-self-start" @click="goBack">Back to list</v-btn>
    <v-card max-width="900" rounded="xl" border class="align-self-start w-100">
      <v-card-item class="pb-0 pt-6 px-6">
        <v-card-title class="text-h6 font-weight-semibold pa-0">New data structure</v-card-title>
        <v-card-subtitle class="pa-0 mt-2 text-wrap">
          Identity (name + agency + semver) is permanent for this row; create a new version later to change it.
        </v-card-subtitle>
      </v-card-item>
      <v-card-text class="px-6 pb-6 pt-4">
        <DataStructureStructureForm :structure="null" @cancel="goBack" @saved="onSaved" />
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { inject } from 'vue';
import { useRouter } from 'vue-router';
import DataStructureStructureForm from '../components/DataStructureStructureForm.vue';
import { useDataStructuresApi } from '../composables/useDataStructuresApi';

defineOptions({ name: 'DataStructureCreatePage' });

const router = useRouter();
const setMessage = inject('setMessage', () => {});
const { createDataStructure } = useDataStructuresApi();

function goBack() {
  router.push({ name: 'data-structures' });
}

async function onSaved(evt) {
  if (evt.isEdit) return;
  try {
    const p = { ...evt.payload };
    if (p.idno === '') delete p.idno;
    const row = await createDataStructure(p);
    setMessage('Data structure created.', 'success');
    const id = row?.id;
    if (id != null) {
      router.replace({ name: 'data-structure-detail', params: { id: String(id) } });
    } else {
      goBack();
    }
  } catch (e) {
    setMessage(e?.response?.data?.message || e?.message || 'Create failed', 'error');
  }
}
</script>
