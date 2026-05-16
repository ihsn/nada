<template>
  <v-card class="admin-catalog-surface" rounded="lg" elevation="1">
    <div class="admin-catalog-search-inner">
      <v-text-field
        v-model="keywords"
        label=""
        :placeholder="t('search_keywords_placeholder')"
        prepend-inner-icon="mdi-magnify"
        density="comfortable"
        hide-details
        clearable
        variant="outlined"
        class="admin-catalog-search-field"
        :disabled="loading"
        @keydown.enter.prevent="onSearch"
        @click:clear="onClear"
      />
    </div>
  </v-card>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'AdminCatalogSearchBar' });

const { t } = useI18n();

const props = defineProps({
  loading: { type: Boolean, default: false },
  initialKeywords: { type: String, default: '' },
});

const emit = defineEmits(['search']);

const keywords = ref('');

// Sync from URL when initialKeywords is set (parent sets it after its onMounted)
watch(
  () => props.initialKeywords,
  (val) => {
    if (val != null && val !== '') keywords.value = val;
  },
  { immediate: true }
);

function onSearch() {
  emit('search', { keywords: keywords.value });
}

function onClear() {
  emit('search', { keywords: '' });
}
</script>
