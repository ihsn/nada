<template>
  <div class="facet-box">
    <button
      type="button"
      class="facet-box__header"
      :aria-expanded="expanded"
      @click="expanded = !expanded"
    >
      <v-icon size="14" class="facet-box__filter-icon">$mdi-filter-outline</v-icon>
      <span class="facet-box__title">{{ title }}</span>
      <v-icon size="18" class="facet-box__chevron">
        {{ expanded ? '$mdi-chevron-up' : '$mdi-chevron-down' }}
      </v-icon>
    </button>

    <div v-if="selectedCount > 0" class="facet-box__subtitle facet-box__subtitle--active">
      <span>{{ selectedCount }} {{ t('selected') }}</span>
      <button
        type="button"
        class="facet-box__clear"
        @click.stop="emit('clear')"
      >
        <v-icon size="12" start>$mdi-close</v-icon>
        {{ t('clear') }}
      </button>
    </div>

    <v-expand-transition>
      <div v-show="expanded" class="facet-box__body">
        <slot />
      </div>
    </v-expand-transition>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogFacetBox' });

defineProps({
  title:         { type: String, required: true },
  selectedCount: { type: Number, default: 0 },
});

const emit = defineEmits(['clear']);

const { t } = useI18n();
const expanded = ref(false);
</script>

<style scoped>
.facet-box {
  background: #f0f4f8;
  border-radius: 8px;
  padding: 12px 14px;
  margin-bottom: 12px;
}

.facet-box__header {
  display: flex;
  align-items: center;
  width: 100%;
  gap: 8px;
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
  text-align: left;
  font-size: var(--catalog-font-ui, 0.875rem);
  font-weight: 600;
  color: var(--catalog-text, #1a2332);
  text-transform: capitalize;
}

.facet-box__title {
  flex: 1;
}

.facet-box__filter-icon {
  opacity: 0.55;
}

.facet-box__subtitle {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
  font-size: var(--catalog-font-ui, 0.875rem);
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}

.facet-box__subtitle--active {
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.facet-box__clear {
  display: inline-flex;
  align-items: center;
  border: none;
  background: transparent;
  padding: 0 0 0 8px;
  font-size: var(--catalog-font-ui, 0.875rem);
  color: var(--catalog-link, #1565c0);
  cursor: pointer;
}

.facet-box__clear:hover {
  text-decoration: underline;
}

.facet-box__body {
  margin-top: 10px;
}
</style>
