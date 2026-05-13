<template>
  <div>
    <v-text-field
      v-if="collections.length > 6"
      v-model="search"
      append-inner-icon="mdi-magnify"
      label="Filter collections"
      single-line
      hide-details
      density="compact"
      variant="outlined"
      class="mb-3 collections-filter-field"
    />

    <v-data-table
      :headers="headers"
      :items="filtered"
      :items-per-page="-1"
      hide-default-footer
      density="compact"
      class="elevation-0"
      no-data-text="No collections found"
    >
      <template #item.repo_title="{ item }">
        <a v-if="item.repo_id" :href="siteUrl + '/admin/collections/active/' + item.repo_id + '?destination=admin/catalog'" class="text-decoration-none font-weight-medium">{{ item.repo_title }}</a>
        <a v-else :href="siteUrl + '/admin/collections/active/0?destination=admin/catalog'" class="text-decoration-none font-weight-medium">{{ item.repo_title }}</a>
      </template>

      <template #item.repo_key="{ item }">
        <a v-if="item.repo_id" :href="siteUrl + '/admin/collections/active/' + item.repo_id + '?destination=admin/catalog'" class="text-decoration-none">
          <v-chip size="x-small" variant="outlined" color="primary" style="text-transform:uppercase;font-family:monospace;cursor:pointer;">{{ item.repo_key }}</v-chip>
        </a>
        <v-chip v-else size="x-small" :href="siteUrl + '/admin/collections/active/0'" variant="outlined" color="primary" style="text-transform:uppercase;font-family:monospace;">{{ item.repo_key }}</v-chip>
      </template>

      <template #item.total="{ item }">
        <span>{{ item.total.toLocaleString() }}</span>
      </template>

      <template #item.published="{ item }">
        <span class="text-success font-weight-medium">{{ item.published.toLocaleString() }}</span>
      </template>

      <template #item.unpublished="{ item }">
        <span class="text-warning">{{ item.unpublished.toLocaleString() }}</span>
      </template>

      <template #item.pending_requests="{ item }">
        <v-chip v-if="item.pending_requests > 0" size="x-small" color="error">
          {{ item.pending_requests }}
        </v-chip>
        <span v-else class="text-medium-emphasis">—</span>
      </template>

      <template #item.actions="{ item }">
        <v-menu offset-y location="bottom end">
          <template #activator="{ props: menuProps }">
            <v-btn variant="text" icon size="x-small" v-bind="menuProps" @click.stop>
              <v-icon size="x-small">mdi-dots-vertical</v-icon>
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item :href="item.repo_id ? siteUrl + '/admin/collections/active/' + item.repo_id + '?destination=admin/catalog' : siteUrl + '/admin/catalog'">
              <template #prepend><v-icon size="small">mdi-cog</v-icon></template>
              <v-list-item-title>Manage</v-list-item-title>
            </v-list-item>
            <v-list-item :href="siteUrl + '/admin/collections#/history/' + item.repo_key">
              <template #prepend><v-icon size="small">mdi-history</v-icon></template>
              <v-list-item-title>History</v-list-item-title>
            </v-list-item>
            <v-list-item :href="item.repo_id ? siteUrl + '/admin/licensed_requests?collection=' + item.repo_key : siteUrl + '/admin/licensed_requests'">
              <template #prepend><v-icon size="small">mdi-file-document-outline</v-icon></template>
              <v-list-item-title>License Requests</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-data-table>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  collections: { type: Array, default: () => [] },
  siteUrl: { type: String, default: '' },
});

const search = ref('');

const headers = [
  { title: 'ID', key: 'repo_key', sortable: true, width: '160px' },
  { title: 'Collection', key: 'repo_title', sortable: true },
  { title: 'Total', key: 'total', sortable: true, align: 'end' },
  { title: 'Published', key: 'published', sortable: true, align: 'end' },
  { title: 'Unpublished', key: 'unpublished', sortable: true, align: 'end' },
  { title: 'Pending Requests', key: 'pending_requests', sortable: true, align: 'end' },
  { title: '', key: 'actions', sortable: false, align: 'end', width: '80px' },
];

const filtered = computed(() => {
  if (!search.value) return props.collections;
  const q = search.value.toLowerCase();
  return props.collections.filter(c =>
    c.repo_title?.toLowerCase().includes(q) || c.repo_key?.toLowerCase().includes(q)
  );
});
</script>

<style scoped>
.collections-filter-field :deep(.v-field-label) {
  padding-left: 8px;
}
</style>
