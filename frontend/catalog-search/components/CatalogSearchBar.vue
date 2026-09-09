<template>
  <div class="catalog-search-bar">
    <form
      class="search-shell"
      role="search"
      @submit.prevent="onSubmit"
    >
      <label class="visually-hidden" for="catalog-search-keywords">{{ t('keywords') }}</label>

      <input
        id="catalog-search-keywords"
        v-model="localValue"
        type="search"
        class="search-shell__input"
        :placeholder="placeholder"
        autocomplete="off"
        enterkeyhint="search"
        @input="scheduleSearch"
      />

      <Transition name="fade">
        <button
          v-if="localValue"
          type="button"
          class="search-shell__action search-shell__clear"
          :aria-label="t('reset_search')"
          @click="onReset"
        >
          <v-icon size="20" class="search-shell__icon-glyph">$mdi-close-circle</v-icon>
        </button>
      </Transition>

      <button
        type="submit"
        class="search-shell__action search-shell__submit"
        :class="submitDriverClass"
        :aria-label="submitAriaLabel"
        :title="submitTitle"
        :disabled="loading"
      >
        <v-progress-circular
          v-if="loading"
          indeterminate
          size="20"
          width="2"
          class="search-shell__icon-glyph"
        />
        <v-icon v-else size="22" class="search-shell__icon-glyph">$mdi-magnify</v-icon>
      </button>
    </form>
  </div>
</template>

<script setup>
import { computed, onUnmounted } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { searchDriverGroup } from '../catalogSearchDriver';

defineOptions({ name: 'CatalogSearchBar' });

const DEBOUNCE_MS = 400;

const props = defineProps({
  modelValue:     { type: String, default: '' },
  loading:        { type: Boolean, default: false },
  searchProvider: { type: String, default: 'db' },
});
const emit = defineEmits(['update:modelValue', 'search', 'reset']);

const { t } = useI18n();

let debounceTimer = null;

const localValue = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v ?? ''),
});

const placeholder = computed(() => `${t('keywords')}...`);

const driverGroup = computed(() => searchDriverGroup(props.searchProvider));

const submitDriverClass = computed(() =>
  driverGroup.value === 'db' ? null : `search-shell__submit--${driverGroup.value}`
);

const submitTitle = computed(() => undefined);

const submitAriaLabel = computed(() => t('search'));

function scheduleSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => emit('search'), DEBOUNCE_MS);
}

function onSubmit() {
  clearTimeout(debounceTimer);
  emit('search');
}

function onReset() {
  clearTimeout(debounceTimer);
  emit('reset');
}

onUnmounted(() => {
  clearTimeout(debounceTimer);
});
</script>

<style scoped>
.catalog-search-bar {
  width: 100%;
}

.search-shell {
  display: flex;
  align-items: center;
  gap: 6px;
  width: 100%;
  min-height: 52px;
  padding: 6px 14px;
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.1);
  border-radius: 14px;
  box-shadow:
    0 1px 2px rgba(15, 23, 42, 0.04),
    0 6px 20px rgba(15, 23, 42, 0.06);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.search-shell:focus-within {
  border-color: rgba(15, 23, 42, 0.22);
  box-shadow:
    0 0 0 3px rgba(15, 23, 42, 0.06),
    0 6px 20px rgba(15, 23, 42, 0.08);
}

.search-shell__icon-glyph {
  color: rgba(15, 23, 42, 0.42) !important;
}

.search-shell__input {
  flex: 1;
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  font-size: 1rem;
  line-height: 1.4;
  color: rgba(15, 23, 42, 0.92);
  padding: 8px 4px;
}

.search-shell__input::placeholder {
  color: rgba(15, 23, 42, 0.42);
}

.search-shell__input::-webkit-search-cancel-button {
  display: none;
}

.search-shell__action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 6px;
  border-radius: 8px;
  transition: background 0.15s ease, color 0.15s ease;
}

.search-shell__action:hover:not(:disabled) {
  background: rgba(15, 23, 42, 0.06);
}

.search-shell__action:disabled {
  cursor: default;
  opacity: 0.7;
}

/* Full-text index (Solr / OpenSearch) */
.search-shell__submit--fulltext {
  background: rgba(0, 137, 123, 0.1);
}

.search-shell__submit--fulltext:hover:not(:disabled) {
  background: rgba(0, 137, 123, 0.18);
}

.search-shell__submit--fulltext .search-shell__icon-glyph {
  color: #00897b !important;
}

/* Semantic search */
.search-shell__submit--semantic {
  background: rgba(126, 87, 194, 0.1);
}

.search-shell__submit--semantic:hover:not(:disabled) {
  background: rgba(126, 87, 194, 0.18);
}

.search-shell__submit--semantic .search-shell__icon-glyph {
  color: #7e57c2 !important;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
