<template>
  <div class="catalog-page-size">
    <span class="catalog-page-size__label">
      {{ t('select_number_of_records_per_page') }}:
    </span>
    <div class="catalog-page-size__options" role="group" :aria-label="t('select_number_of_records_per_page')">
      <button
        v-for="size in PAGE_SIZES"
        :key="size"
        type="button"
        class="catalog-page-size__btn"
        :class="{ 'catalog-page-size__btn--active': modelValue === size }"
        :aria-pressed="modelValue === size"
        @click="emit('update:modelValue', size)"
      >
        {{ size }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from '@/shared/composables/useI18n';

defineOptions({ name: 'CatalogPageSizeSelect' });

defineProps({
  modelValue: { type: Number, required: true },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();

const PAGE_SIZES = [15, 30, 50];
</script>

<style scoped>
.catalog-page-size {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px 12px;
  margin-top: 16px;
}

.catalog-page-size__label {
  font-size: var(--catalog-font-ui, 0.875rem);
  line-height: 1.4;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.catalog-page-size__options {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.catalog-page-size__btn {
  appearance: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 2.25rem;
  padding: 7px 10px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 4px;
  background: #e9ecef;
  color: rgb(var(--v-theme-primary, 21, 101, 192));
  font-size: var(--catalog-font-small, 0.75rem);
  font-weight: 500;
  line-height: 1;
  cursor: pointer;
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.catalog-page-size__btn:hover {
  background: #dee2e6;
  border-color: rgba(15, 23, 42, 0.18);
}

.catalog-page-size__btn:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary, 21, 101, 192));
  outline-offset: 2px;
}

.catalog-page-size__btn--active {
  background: rgb(var(--v-theme-primary, 21, 101, 192));
  border-color: rgb(var(--v-theme-primary, 21, 101, 192));
  color: #fff;
  font-weight: 600;
}

.catalog-page-size__btn--active:hover {
  background: rgb(var(--v-theme-primary, 21, 101, 192));
  filter: brightness(0.95);
}
</style>
