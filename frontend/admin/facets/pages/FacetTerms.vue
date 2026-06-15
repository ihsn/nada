<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="facets-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <div class="d-flex align-center mb-4">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="router.push('/')" />
      <h1 class="text-h5 font-weight-medium ml-2">
        <span class="text-medium-emphasis font-weight-regular">Facets / </span>{{ facetName }}
      </h1>
    </div>

    <v-card v-if="loading" elevation="1">
      <v-card-text class="text-center py-12">
        <v-progress-circular indeterminate color="primary" size="64" class="mb-4" />
        <p class="text-medium-emphasis">Loading terms…</p>
      </v-card-text>
    </v-card>

    <template v-else>
      <v-alert v-if="error" type="error" class="mb-4" density="compact" closable @click:close="error = null">
        {{ error }}
      </v-alert>

      <v-card v-if="!terms.length" elevation="1">
        <v-card-text class="text-center py-12">
          <v-icon size="64" color="grey" class="mb-4">mdi-tag-off-outline</v-icon>
          <h2 class="text-h6 mb-2">No terms found</h2>
          <p class="text-medium-emphasis">This facet has no indexed terms yet. Run the indexer to populate it.</p>
        </v-card-text>
      </v-card>

      <v-card v-else elevation="1">
        <v-card-text class="pa-4 pb-2">
          <span class="text-body-2 text-medium-emphasis">{{ terms.length }} terms</span>
        </v-card-text>
        <v-data-table
          :headers="headers"
          :items="terms"
          item-value="id"
          class="elevation-0"
          :items-per-page="50"
        />
      </v-card>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useFacetsApi } from '../composables/useFacetsApi';

const props = defineProps({ id: { type: String, required: true } });

const router = useRouter();
const { siteUrl } = useAppConfig();
const { loading, getTerms } = useFacetsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const facetName   = ref('');
const terms       = ref([]);
const error       = ref(null);

const breadcrumbItems = computed(() => [
  { title: 'Admin',          href: `${siteBaseUrl.value}/admin` },
  { title: 'Facets',         href: `${siteBaseUrl.value}/admin/facets` },
  { title: facetName.value || 'Terms', disabled: true },
]);

const headers = [
  { title: 'ID',   key: 'id',    sortable: false, width: 100 },
  { title: 'Term', key: 'value', sortable: false },
];

onMounted(async () => {
  try {
    const result     = await getTerms(props.id);
    facetName.value  = result.facet?.name || props.id;
    terms.value      = result.terms;
  } catch (e) {
    error.value = e?.response?.data?.message || e.message;
  }
});
</script>

<style scoped>
.facets-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}
.facets-breadcrumbs :deep(.v-breadcrumbs-item),
.facets-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
