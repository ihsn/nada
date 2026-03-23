<template>
  <div>
    <div v-if="!requests.length" class="d-flex flex-column align-center justify-center py-8 text-medium-emphasis">
      <v-icon size="large" color="grey-lighten-2" class="mb-3">mdi-file-check-outline</v-icon>
      <span class="text-body-2">No pending license requests.</span>
    </div>

    <v-data-table
      v-else
      :headers="headers"
      :items="requests"
      :items-per-page="-1"
      hide-default-footer
      density="compact"
      class="elevation-0"
    >
      <template #item.id="{ item }">
        <a :href="siteUrl + '/admin/licensed_requests/edit/' + item.id" class="text-decoration-none text-caption font-weight-medium">
          #{{ item.id }}
        </a>
      </template>
      <template #item.request_title="{ item }">
        <a :href="siteUrl + '/admin/licensed_requests/edit/' + item.id" class="text-decoration-none text-body-2">
          {{ item.request_title || '—' }}
        </a>
      </template>
      <template #item.username="{ item }">
        <span class="text-caption">{{ item.username || item.email || '—' }}</span>
      </template>
      <template #item.org_rec="{ item }">
        <span class="text-caption text-medium-emphasis">{{ item.org_rec || '—' }}</span>
      </template>
      <template #item.created_fmt="{ item }">
        <span class="text-caption text-medium-emphasis">{{ item.created_fmt }}</span>
      </template>
    </v-data-table>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  licenseRequests: { type: Object, default: null },
  siteUrl: { type: String, default: '' },
});

const headers = [
  { title: 'ID', key: 'id', sortable: false, width: '60px' },
  { title: 'Title', key: 'request_title', sortable: false },
  { title: 'Applicant', key: 'username', sortable: false, width: '120px' },
  { title: 'Organization', key: 'org_rec', sortable: false, width: '140px' },
  { title: 'Date', key: 'created_fmt', sortable: false, width: '160px' },
];

const requests = computed(() => props.licenseRequests?.pending_requests ?? []);
</script>
