<template>
  <v-data-table
    :headers="headers"
    :items="collections"
    :loading="loading"
    items-per-page="25"
    class="elevation-0"
    density="compact"
    @click:row="onRowClick"
  >
    <template #item.thumbnail="{ item }">
      <img
        :src="item.thumbnail ? baseUrl + item.thumbnail : defaultThumb"
        class="thumbnail-img"
        alt=""
        @error="e => e.target.src = defaultThumb"
      />
    </template>

    <template #item.repositoryid="{ item }">
      <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium">
        {{ item.repositoryid }}
      </v-chip>
    </template>

    <template #item.weight="{ item }">
      <input
        :value="item.weight"
        type="number"
        class="weight-input"
        @click.stop
        @change="e => onWeightChange(item, e.target.value)"
        @keydown.enter="e => e.target.blur()"
      />
    </template>

    <template #item.ispublished="{ item }">
      <v-switch
        :model-value="item.ispublished == 1"
        density="compact"
        color="success"
        hide-details
        @click.stop
        @update:model-value="val => $emit('publish-change', { collection: item, published: val ? 1 : 0 })"
      />
    </template>

    <template #item.actions="{ item }">
      <v-menu location="bottom end">
        <template #activator="{ props }">
          <v-btn icon="mdi-dots-vertical" size="x-small" variant="text" v-bind="props" @click.stop />
        </template>
        <v-list density="compact">
          <v-list-item prepend-icon="mdi-pencil" title="Edit" @click="$emit('edit', item)" />
          <v-list-item prepend-icon="mdi-history" title="History" @click="$emit('history', item)" />
          <v-list-item
            prepend-icon="mdi-eye-outline"
            title="Preview"
            :href="siteUrl + '/collections/' + item.repositoryid"
            target="_blank"
          />
          <v-list-item
            prepend-icon="mdi-cog-outline"
            title="Manage studies"
            :href="siteUrl + '/admin/collections/active/' + item.id"
          />
          <v-list-item
            v-if="canManageCollectionAccess || item.can_manage_access"
            prepend-icon="mdi-account-key"
            title="Permissions"
            @click="goPermissions(item)"
          />
          <v-divider />
          <v-list-item prepend-icon="mdi-delete" title="Delete" base-color="error" @click="$emit('delete', item)" />
        </v-list>
      </v-menu>
    </template>

    <template #no-data>
      <div class="text-center pa-4 text-medium-emphasis">No collections found.</div>
    </template>
  </v-data-table>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';

defineOptions({ name: 'AdminCollectionsResults' });

const router = useRouter();
const { baseUrl, siteUrl, config } = useAppConfig();
const defaultThumb = computed(() => `${baseUrl.value}files/thumbnails/thumbnail-default.png`);
const canManageCollectionAccess = computed(() => !!config.value?.canManageCollectionAccess);

function goPermissions(item) {
  router.push({ name: 'collection-permissions', params: { repositoryId: String(item.id) } });
}

defineProps({
  collections: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['edit', 'delete', 'history', 'publish-change', 'weight-change']);

const headers = [
  { title: '', key: 'thumbnail', sortable: false, width: '96px' },
  { title: 'ID', key: 'repositoryid', sortable: true },
  { title: 'Title', key: 'title', sortable: true },
  { title: 'Section', key: 'section_title', sortable: true },
  { title: 'Studies', key: 'study_count', sortable: true, width: '90px' },
  { title: 'Weight', key: 'weight', sortable: true, width: '100px' },
  { title: 'Published', key: 'ispublished', sortable: true, width: '110px' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end', width: '60px' },
];

function onRowClick(_, { item }) {
  emit('edit', item);
}

function onWeightChange(item, value) {
  const weight = parseInt(value, 10);
  if (!isNaN(weight) && weight !== item.weight) {
    emit('weight-change', { collection: item, weight });
  }
}
</script>

<style scoped>
.thumbnail-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 4px;
  display: block;
}
.weight-input {
  width: 60px;
  padding: 2px 4px;
  font-size: 0.85rem;
  border: 1px solid #bbb;
  border-radius: 4px;
  text-align: center;
  appearance: textfield;
}
.weight-input:focus {
  outline: none;
  border-color: #1976d2;
}
:deep(td) {
  padding-top: 5px !important;
  padding-bottom: 5px !important;
  vertical-align: top;
}
:deep(tr) {
  cursor: pointer;
}
:deep(tr:hover td) {
  background-color: rgba(0, 0, 0, 0.04);
}
</style>
