<template>
  <div class="semantic-recent-item">
    <v-icon :icon="state.icon" :color="state.color" size="18" class="semantic-recent-icon" :title="state.text" />
    <div class="semantic-recent-body">
      <div class="semantic-recent-title">
        <a v-if="editUrl" :href="editUrl" target="_blank" rel="noopener" title="Edit the study">{{ label }}</a>
        <span v-else>{{ label }}</span>
      </div>
      <div class="semantic-recent-meta">
        <span :class="`text-${state.color}`">{{ state.text }}</span>
        <template v-for="part in details" :key="part"> · {{ part }}</template>
      </div>
      <div v-if="item.error" class="semantic-recent-error" :title="item.error">{{ item.error }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { changeClassLabel } from '../typeLabels.js';

defineOptions({ name: 'SemanticRecentChangeItem' });

/** One recent change: state icon, title (linked to the study's edit page), what changed and when, any error. */
const props = defineProps({
  item: { type: Object, required: true },
  editUrl: { type: String, default: null },
});

const STATES = {
  waiting: { text: 'Waiting', color: 'primary', icon: 'mdi-clock-outline' },
  indexed: { text: 'Indexed', color: 'success', icon: 'mdi-check-circle-outline' },
  failed: { text: 'Failed', color: 'error', icon: 'mdi-alert-circle-outline' },
};

const label = computed(() => props.item.title || props.item.object_key || `#${props.item.object_id}`);
const state = computed(
  () => STATES[props.item.status] || { text: props.item.status, color: 'grey', icon: 'mdi-help-circle-outline' }
);

function when(ts) {
  if (!ts) return '';
  const minutes = Math.round((Date.now() / 1000 - Number(ts)) / 60);
  if (minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes} min ago`;
  if (minutes < 24 * 60) return `${Math.round(minutes / 60)} h ago`;
  return new Date(Number(ts) * 1000).toLocaleString();
}

const details = computed(() =>
  [
    props.item.change ? changeClassLabel(props.item.change) : null,
    props.item.object_type === 'citation' ? 'Citation' : null,
    props.item.object_key && props.item.title ? props.item.object_key : null,
    when(props.item.at),
  ].filter(Boolean)
);
</script>

<style scoped>
.semantic-recent-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 0;
  min-width: 0;
}

.semantic-recent-icon {
  margin-top: 1px;
  flex-shrink: 0;
}

.semantic-recent-body {
  min-width: 0;
  flex: 1 1 auto;
}

.semantic-recent-title {
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.semantic-recent-title a {
  color: inherit;
  text-decoration: none;
}

.semantic-recent-title a:hover {
  text-decoration: underline;
}

.semantic-recent-meta {
  font-size: 0.75rem;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), 0.6);
  margin-top: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.semantic-recent-error {
  font-size: 0.75rem;
  color: rgb(var(--v-theme-error));
  margin-top: 2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
