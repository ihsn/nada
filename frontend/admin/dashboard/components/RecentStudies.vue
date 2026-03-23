<template>
  <div>
    <v-data-table
      :headers="headers"
      :items="studies"
      :items-per-page="10"
      hide-default-footer
      density="compact"
      class="elevation-0"
      no-data-text="No studies found"
    >
      <template #item.title="{ item }">
        <a :href="siteUrl + '/admin/catalog/edit/' + item.id" class="text-decoration-none d-block" :title="item.title">
          <div class="text-body-2">{{ item.title }}</div>
          <div class="text-caption text-medium-emphasis">{{ item.idno }}</div>
        </a>
      </template>
      <template #item.changed_fmt="{ item }">
        <span class="text-caption text-medium-emphasis">{{ formatDate(item.changed) }}</span>
      </template>
    </v-data-table>

    <v-divider />
    <div class="d-flex justify-end pa-2" style="gap:8px;">
      <v-btn size="x-small" variant="text" color="primary" :href="siteUrl + '/admin/collections#/history/central'" prepend-icon="mdi-history">
        View History
      </v-btn>
      <v-btn size="x-small" variant="text" color="primary" :href="siteUrl + '/admin/catalog'" prepend-icon="mdi-book-open-variant">
        Manage Catalog
      </v-btn>
    </div>
  </div>
</template>

<script setup>
defineProps({
  studies: { type: Array, default: () => [] },
  siteUrl: { type: String, default: '' },
});

function formatDate(ts) {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
}

const headers = [
  { title: 'Title', key: 'title', sortable: false },
  { title: 'Changed', key: 'changed_fmt', sortable: false, width: '110px' },
];
</script>
