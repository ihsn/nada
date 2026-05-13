<template>
  <div class="recent-studies">
    <template v-if="displayedStudies.length === 0">
      <div class="recent-studies-empty text-center py-10 px-4">
        <v-avatar color="primary" variant="tonal" size="56" class="mb-4">
          <v-icon size="28">mdi-history</v-icon>
        </v-avatar>
        <div class="text-body-1 font-weight-medium text-medium-emphasis mb-1">No recent activity</div>
        <div class="text-body-2 text-disabled">Modified studies will appear here.</div>
      </div>
    </template>

    <v-list v-else class="recent-studies-list bg-transparent pa-0" lines="two">
      <v-list-item
        v-for="item in displayedStudies"
        :key="item.id"
        :href="siteUrl + '/admin/catalog/edit/' + item.id"
        class="recent-study-item px-0 py-2"
      >
        <template #prepend>
          <v-avatar color="primary" variant="tonal" size="40">
            <v-icon size="20">mdi-file-document-outline</v-icon>
          </v-avatar>
        </template>

        <v-list-item-title class="recent-study-title text-wrap">
          {{ item.title }}
        </v-list-item-title>
        <v-list-item-subtitle class="recent-study-sub text-wrap mt-0.5">
          <span class="font-mono text-caption">{{ item.idno }}</span>
        </v-list-item-subtitle>

        <template #append>
          <div class="recent-study-meta text-end">
            <v-chip size="x-small" variant="tonal" color="primary" class="font-weight-medium">
              {{ formatDate(item.changed) }}
            </v-chip>
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

function formatDate(ts) {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}
</script>

<style scoped>
.recent-studies-list :deep(.v-list-item) {
  border: none;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 0;
  background: transparent;
  margin-bottom: 0;
  transition: background 0.15s ease;
}

.recent-studies-list :deep(.v-list-item:last-child) {
  border-bottom: none;
}

.recent-studies-list :deep(.v-list-item:hover) {
  background: rgba(var(--v-theme-primary), 0.06) !important;
  box-shadow: none;
}

.recent-studies-list :deep(.v-list-item:focus-visible) {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

.recent-study-title {
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.35;
}

.recent-study-sub {
  opacity: 0.85;
}

.recent-studies-empty {
  border: 1px dashed rgba(var(--v-border-color), 0.35);
  border-radius: 12px;
  background: rgba(var(--v-theme-surface-variant), 0.35);
}
</style>
