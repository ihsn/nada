<template>
  <v-breadcrumbs :items="items" class="catalog-study-edit-breadcrumbs px-0 pt-0 pb-2">
    <template #divider>
      <v-icon icon="mdi-chevron-right" size="16" />
    </template>
  </v-breadcrumbs>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  homeUrl: { type: String, required: true },
  catalogUrl: { type: String, required: true },
  editUrl: { type: String, required: true },
  homeLabel: { type: String, required: true },
  catalogLabel: { type: String, required: true },
  editLabel: { type: String, required: true },
});

const items = computed(() => [
  { title: props.homeLabel, href: props.homeUrl },
  { title: props.catalogLabel, href: props.catalogUrl },
  { title: props.editLabel, href: props.editUrl },
]);
</script>

<style scoped>
.catalog-study-edit-breadcrumbs :deep(.v-breadcrumbs-item),
.catalog-study-edit-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
