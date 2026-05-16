<template>
  <div class="recent-studies">
    <template v-if="displayedStudies.length === 0">
      <div class="recent-studies-empty text-center py-10 px-6 mx-4 mb-4 mt-2">
        <v-avatar color="primary" variant="tonal" size="56" class="mb-4">
          <v-icon size="28">mdi-history</v-icon>
        </v-avatar>
        <div class="text-body-1 font-weight-semibold text-medium-emphasis mb-1">No recent activity</div>
        <div class="text-body-2 text-medium-emphasis">
          Studies you edit will show up here with title, ID, and last modified date.
        </div>
      </div>
    </template>

    <v-list v-else class="recent-studies-list bg-transparent py-2" density="comfortable" lines="three">
      <v-list-item
        v-for="item in displayedStudies"
        :key="item.id"
        :href="siteUrl + '/admin/catalog/edit/' + item.id"
        class="recent-study-item"
        rounded="0"
      >
        <template #prepend>
          <v-avatar color="primary" variant="tonal" size="44" class="recent-study-avatar">
            <v-icon size="22">mdi-file-document-outline</v-icon>
          </v-avatar>
        </template>

        <v-list-item-title class="recent-study-title">
          {{ item.title }}
        </v-list-item-title>

        <v-list-item-subtitle class="recent-study-sub">
          <div class="recent-study-id-row text-caption">
            <span class="recent-study-idno font-weight-medium">{{ item.idno }}</span>
            <span v-if="item.repo_title" class="recent-study-repo text-medium-emphasis">
              · {{ item.repo_title }}
            </span>
          </div>
          <div class="recent-study-chips d-flex flex-wrap align-center ga-1 mt-2">
            <v-chip v-if="item.type" size="x-small" label variant="outlined" color="primary" class="text-capitalize">
              {{ item.type }}
            </v-chip>
            <v-chip
              size="x-small"
              label
              variant="outlined"
              :color="isPublished(item) ? 'success' : 'warning'"
              class="font-weight-medium text-capitalize"
            >
              {{ isPublished(item) ? 'Published' : 'Unpublished' }}
            </v-chip>
          </div>
        </v-list-item-subtitle>

        <template #append>
          <div class="recent-study-meta">
            <div class="recent-study-date">
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
  studies: { type: Array, default: () => [] },
  siteUrl: { type: String, default: '' },
});

const displayedStudies = computed(() => props.studies.slice(0, 10));

function isPublished(item) {
  return item.published === 1 || item.published === true || item.published === '1';
}

function displayDate(item) {
  if (item.changed_fmt && typeof item.changed_fmt === 'string') {
    const parts = item.changed_fmt.trim().split(/\s+/);
    return parts[0] || item.changed_fmt;
  }
  if (!item.changed) return '—';
  return new Date(item.changed * 1000).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}
</script>

<style scoped>
.recent-studies-list :deep(.v-list-item) {
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

.recent-studies-list :deep(.v-list-item:last-child) {
  border-bottom: none;
}

@media (hover: hover) and (pointer: fine) {
  .recent-studies-list :deep(.v-list-item:hover) {
    background: rgba(var(--v-theme-primary), 0.05) !important;
    box-shadow: inset 3px 0 0 rgb(var(--v-theme-primary));
  }
}

.recent-studies-list :deep(.v-list-item:focus-visible) {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: -2px;
}

.recent-studies-list :deep(.v-list-item__prepend) {
  width: auto;
  align-self: flex-start;
  align-items: flex-start !important;
  padding-top: 0 !important;
}

.recent-studies-list :deep(.v-list-item__content) {
  min-width: 0;
  align-self: flex-start !important;
  padding-top: 0 !important;
}

.recent-studies-list :deep(.v-list-item__append) {
  align-self: flex-start !important;
  align-items: flex-start !important;
  justify-content: flex-start !important;
  padding-top: 0 !important;
}

.recent-study-title {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  letter-spacing: -0.01em;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.recent-study-sub {
  margin-top: 4px;
  opacity: 1;
}

.recent-study-id-row {
  line-height: 1.45;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.recent-study-idno {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
  font-size: 0.75rem;
  letter-spacing: 0.02em;
}

.recent-study-repo {
  font-size: 0.75rem;
}

.recent-study-meta {
  text-align: end;
  min-width: 5.5rem;
  padding-left: 8px;
}

.recent-study-date {
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.35;
  letter-spacing: 0.01em;
  color: rgba(var(--v-theme-on-surface), 0.55);
}

@media (max-width: 599px) {
  .recent-studies-list :deep(.v-list-item) {
    flex-wrap: wrap;
    margin: 0 8px;
    padding-inline: 4px;
    padding-block: 16px;
  }

  .recent-studies-list :deep(.v-list-item__append) {
    width: 100%;
    padding-left: 60px;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed rgba(var(--v-theme-on-surface), 0.1);
  }

  .recent-study-meta {
    text-align: start;
    padding-left: 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
  }
}

.recent-studies-empty {
  border: 1px dashed rgba(var(--v-theme-on-surface), 0.18);
  border-radius: 8px;
  background: linear-gradient(
    165deg,
    rgba(var(--v-theme-primary), 0.04) 0%,
    rgba(var(--v-theme-surface-variant), 0.25) 100%
  );
}
</style>
