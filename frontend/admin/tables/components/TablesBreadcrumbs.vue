<template>
  <v-breadcrumbs :items="items" class="tables-breadcrumbs px-0 pt-0">
    <template #divider>
      <v-icon icon="mdi-chevron-right" size="16" />
    </template>
  </v-breadcrumbs>
</template>

<script setup>
defineOptions({ name: 'TablesBreadcrumbs' });

defineProps({
  items: { type: Array, required: true },
});
</script>

<style scoped>
.tables-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.tables-breadcrumbs :deep(.v-breadcrumbs-item),
.tables-breadcrumbs :deep(.v-breadcrumbs-divider) {
  font-size: 0.8125rem;
}
</style>
