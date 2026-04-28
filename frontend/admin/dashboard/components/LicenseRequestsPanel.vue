<template>
  <div>
    <div v-if="!requests.length" class="d-flex flex-column align-center justify-center py-8 text-medium-emphasis">
      <v-icon size="large" color="grey-lighten-2" class="mb-3">mdi-file-check-outline</v-icon>
      <span class="text-body-2">No pending license requests.</span>
    </div>

    <v-row v-else dense class="lic-req-grid ma-0">
      <v-col
        v-for="item in requests"
        :key="item.id"
        cols="12"
        class="py-1 px-0"
      >
        <a
          :href="editUrl(item.id)"
          class="lic-req-wrap text-decoration-none"
        >
          <v-card variant="flat" rounded="0" class="lic-req-card">
            <v-card-text class="py-2 pr-2 pl-5">
              <div class="lic-req-layout">
                <span class="text-caption font-weight-bold text-primary lic-req-id">#{{ item.id }}</span>
                <span class="text-body-2 lic-req-title">{{ item.request_title || '—' }}</span>
                <div class="lic-req-row2 text-caption text-medium-emphasis">
                  <span class="lic-req-meta">{{ item.username || item.email || '—' }}</span>
                  <span class="lic-req-dot" aria-hidden="true">·</span>
                  <span class="lic-req-meta">{{ item.org_rec || '—' }}</span>
                  <span class="lic-req-dot" aria-hidden="true">·</span>
                  <span class="lic-req-meta">{{ item.created_fmt }}</span>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </a>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  licenseRequests: { type: Object, default: null },
  siteUrl: { type: String, default: '' },
});

const requests = computed(() => props.licenseRequests?.pending_requests ?? []);

function editUrl(id) {
  return `${props.siteUrl}/admin/licensed_requests/edit/${id}`;
}
</script>

<style scoped>
.lic-req-grid {
  margin: 0;
}

.lic-req-wrap {
  display: block;
  color: inherit;
}

.lic-req-card {
  border: none !important;
  box-shadow: none !important;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06) !important;
  transition: background-color 0.12s ease;
}

.lic-req-grid :deep(.v-col:last-child) .lic-req-card {
  border-bottom: none !important;
}

.lic-req-wrap:hover .lic-req-card {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.lic-req-layout {
  display: grid;
  grid-template-columns: auto 1fr;
  column-gap: 8px;
  row-gap: 2px;
  align-items: start;
}

.lic-req-id {
  grid-column: 1;
  grid-row: 1;
  white-space: nowrap;
  line-height: 1.35;
  padding-top: 1px;
}

.lic-req-title {
  grid-column: 2;
  grid-row: 1;
  min-width: 0;
  color: rgb(var(--v-theme-on-surface));
  font-weight: 500;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.lic-req-row2 {
  grid-column: 2;
  grid-row: 2;
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0 4px;
  line-height: 1.4;
  min-width: 0;
}

.lic-req-meta {
  min-width: 0;
  word-break: break-word;
}

.lic-req-dot {
  opacity: 0.45;
  user-select: none;
}
</style>
