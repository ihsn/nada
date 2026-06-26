<template>
  <div class="license-requests">
    <template v-if="displayedRequests.length === 0">
      <div class="license-requests-empty text-center py-10 px-6 mx-4 mb-4 mt-2">
        <v-avatar color="primary" variant="tonal" size="56" class="mb-4">
          <v-icon size="28">mdi-file-check-outline</v-icon>
        </v-avatar>
        <div class="text-body-1 font-weight-semibold text-medium-emphasis mb-1">No pending license requests</div>
        <div class="text-body-2 text-medium-emphasis">
          New requests will appear here with title, requester, and submitted date.
        </div>
      </div>
    </template>

    <v-list v-else class="license-requests-list bg-transparent py-2" density="comfortable" lines="three">
      <v-list-item
        v-for="item in displayedRequests"
        :key="item.id"
        :href="editUrl(item.id)"
        class="license-request-item"
        rounded="0"
      >
        <template #prepend>
          <v-avatar color="primary" variant="tonal" size="44" class="license-request-avatar">
            <v-icon size="22">mdi-file-document-outline</v-icon>
          </v-avatar>
        </template>

        <v-list-item-title class="license-request-title">
          {{ item.request_title || '—' }}
        </v-list-item-title>

        <v-list-item-subtitle class="license-request-sub">
          <div class="license-request-id-row text-caption">
            <span class="license-request-idno font-weight-medium">#{{ item.id }}</span>
            <span v-if="requesterLabel(item)" class="license-request-requester text-medium-emphasis">
              · {{ requesterLabel(item) }}
            </span>
          </div>
        </v-list-item-subtitle>

        <template #append>
          <div class="license-request-meta">
            <div class="license-request-date">
              {{ displayDate(item) }}
            </div>
          </div>
        </template>
      </v-list-item>
    </v-list>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  licenseRequests: { type: Object, default: null },
  siteUrl: { type: String, default: '' },
});

const requests = computed(() => props.licenseRequests?.pending_requests ?? []);

const displayedRequests = computed(() => requests.value.slice(0, 10));

function editUrl(id) {
  return `${props.siteUrl}/admin/licensed_requests/edit/${id}`;
}

function requesterLabel(item) {
  return item.username || item.email || '';
}

function displayDate(item) {
  const raw = item.created_fmt;
  if (raw && typeof raw === 'string') {
    const parts = raw.trim().split(/\s+/);
    return parts[0] || raw;
  }
  if (!item.created) return '—';
  return new Date(item.created * 1000).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}
</script>

<style scoped>
.license-requests-list :deep(.v-list-item) {
  border-radius: 0;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: transparent;
  margin: 0 12px;
  padding-inline: 8px;
  padding-block: 14px;
  min-height: 88px;
  align-items: start;
  transition:
    background-color 0.18s ease,
    box-shadow 0.18s ease;
}

.license-requests-list :deep(.v-list-item:last-child) {
  border-bottom: none;
}

@media (hover: hover) and (pointer: fine) {
  .license-requests-list :deep(.v-list-item:hover) {
    background: rgba(var(--v-theme-primary), 0.05) !important;
    box-shadow: inset 3px 0 0 rgb(var(--v-theme-primary));
  }
}

.license-requests-list :deep(.v-list-item:focus-visible) {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: -2px;
}

.license-requests-list :deep(.v-list-item__prepend) {
  width: auto;
  align-self: flex-start;
  align-items: flex-start !important;
  padding-top: 0 !important;
}

.license-requests-list :deep(.v-list-item__content) {
  min-width: 0;
  align-self: flex-start !important;
  padding-top: 0 !important;
}

.license-requests-list :deep(.v-list-item__append) {
  align-self: flex-start !important;
  align-items: flex-start !important;
  justify-content: flex-start !important;
  padding-top: 0 !important;
}

.license-request-title {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  letter-spacing: -0.01em;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.license-request-sub {
  margin-top: 4px;
  opacity: 1;
}

.license-request-id-row {
  line-height: 1.45;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.license-request-idno {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 0.75rem;
  letter-spacing: 0.02em;
}

.license-request-requester {
  font-size: 0.75rem;
}

.license-request-meta {
  text-align: end;
  min-width: 5.5rem;
  padding-left: 8px;
}

.license-request-date {
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.35;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

@media (max-width: 599px) {
  .license-requests-list :deep(.v-list-item) {
    flex-wrap: wrap;
    margin: 0 8px;
    padding-inline: 4px;
    padding-block: 16px;
  }

  .license-requests-list :deep(.v-list-item__append) {
    width: 100%;
    padding-left: 60px;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed rgba(var(--v-theme-on-surface), 0.1);
  }

  .license-request-meta {
    text-align: start;
    padding-left: 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
  }
}

.license-requests-empty {
  border: 1px dashed rgba(var(--v-theme-on-surface), 0.18);
  border-radius: 8px;
  background: linear-gradient(
    165deg,
    rgba(var(--v-theme-primary), 0.04) 0%,
    rgba(var(--v-theme-surface-variant), 0.25) 100%
  );
}
</style>
