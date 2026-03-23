<template>
  <div>
    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
      <h1 class="text-h5 font-weight-medium ml-2">New Collection</h1>
    </div>

    <v-card max-width="800">
      <v-card-text class="pa-5">
        <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = null">
          {{ error }}
        </v-alert>
        <CollectionForm ref="formRef" :form="form" :sections="sections" />
      </v-card-text>
      <v-divider />
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="router.push('/')">Cancel</v-btn>
        <v-btn color="primary" variant="flat" :loading="loading" @click="submit">Create</v-btn>
      </v-card-actions>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import CollectionForm from '../components/CollectionForm.vue';
import { useCollectionsApi } from '../composables/useCollectionsApi';

const router = useRouter();
const { loading, createCollection, getSections } = useCollectionsApi();

const formRef = ref(null);
const error = ref(null);
const sections = ref([]);

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

async function submit() {
  const { valid } = await formRef.value.validate();
  if (!valid) return;
  error.value = null;
  try {
    await createCollection(form);
    router.push('/');
  } catch (e) {
    error.value = e?.response?.data?.message || e.message || 'An error occurred';
  }
}

onMounted(async () => {
  sections.value = await getSections();
});
</script>
