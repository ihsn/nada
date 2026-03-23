<template>
  <v-card class="mb-4 pa-1" flat>
    <v-row align="center">
      <v-col cols="12" md="5">
        <v-text-field
          v-model="keywords"
          label=""
          :placeholder="t('search_keywords_placeholder')"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          hide-details
          clearable
          variant="solo"
          :disabled="loading"
          @keydown.enter.prevent="onSearch"
          @click:clear="onClear"
        />
      </v-col>
    </v-row>
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
