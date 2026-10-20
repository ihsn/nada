<template>
  <nav v-if="hasPrev || hasNext" class="catalog-pager" :aria-label="t('pagination', 'Pagination')">
    <div class="catalog-pager__bar">
      <div class="catalog-pager__status">
        <span class="catalog-pager__status-page">{{ pageOfText }}</span>
        <span class="catalog-pager__status-range">{{ rangeText }}</span>
      </div>

      <div class="catalog-pager__controls">
        <button
          type="button"
          class="catalog-pager__btn catalog-pager__btn--arrow"
          :disabled="!hasPrev"
          :aria-label="t('pager_prev', 'Previous page')"
          @click="goPrev"
        >
          <v-icon icon="$prev" size="16" />
        </button>

        <template v-for="(item, i) in items" :key="item.type === 'page' ? `p${item.page}` : `g${i}`">
          <span v-if="item.type === 'gap'" class="catalog-pager__gap" aria-hidden="true">…</span>
          <button
            v-else
            type="button"
            class="catalog-pager__btn"
            :class="{ 'catalog-pager__btn--current': item.current }"
            :aria-current="item.current ? 'page' : undefined"
            :aria-label="t('pager_page_n', 'Page %s', item.page.toLocaleString())"
            @click="!item.current && emit('page', item.page)"
          >
            {{ item.page.toLocaleString() }}
          </button>
        </template>

        <button
          type="button"
          class="catalog-pager__btn catalog-pager__btn--arrow"
          :disabled="!hasNext"
          :aria-label="t('pager_next', 'Next page')"
          @click="goNext"
        >
          <v-icon icon="$next" size="16" />
        </button>
      </div>
    </div>

    <p v-if="pastNumbered" class="catalog-pager__note">
      {{ t(
        'pager_past_numbered',
        'Results past %s are not numbered. Refine the search or reverse the sort order to reach the end.',
        maxOffset.toLocaleString()
      ) }}
    </p>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { catalogResultsRange } from '../catalogResultsRange';
import { catalogPagerModel } from '../catalogPager';

/** Pager of a result list: "Page N of M" and ‹ 1 … window … › (see catalogPagerModel for what is shown when). */
defineOptions({ name: 'CatalogPager' });

const props = defineProps({
  results: { type: Object, required: true },
  query: { type: Object, required: true },
  /** Page numbers shown around the current page (site setting catalog_pager_numbers). */
  windowSize: { type: Number, default: 5 },
});
const emit = defineEmits(['page', 'cursor']);

const { t } = useI18n();

const model = computed(() => catalogPagerModel(props.results, props.query, props.windowSize));
const items = computed(() => model.value.items);
const hasPrev = computed(() => model.value.hasPrev);
const hasNext = computed(() => model.value.hasNext);
const pastNumbered = computed(() => model.value.pastNumbered);
const maxOffset = computed(() => model.value.maxOffset);

const pageOfText = computed(() =>
  t(
    'pager_page_of',
    'Page %s of %s',
    model.value.current.toLocaleString(),
    `${model.value.totalPages.toLocaleString()}${model.value.lowerBound ? '+' : ''}`
  )
);

/** Small screens show the rows on the page instead of "Page N of M". */
const rangeText = computed(() => {
  const r = catalogResultsRange(props.results, props.query);
  return t(
    'pager_range',
    '%s–%s of %s',
    r.from.toLocaleString(),
    r.to.toLocaleString(),
    `${r.total.toLocaleString()}${r.lowerBound ? '+' : ''}`
  );
});

function go(target) {
  if (target.cursor) emit('cursor', target.cursor);
  else emit('page', target.page);
}

function goNext() {
  go(model.value.next());
}

function goPrev() {
  go(model.value.prev());
}
</script>

<style scoped>
.catalog-pager {
  margin-top: 24px;
  font-size: 0.8125rem;
}

.catalog-pager__bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 8px 16px;
}

.catalog-pager__status {
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.catalog-pager__status-range {
  display: none;
}

.catalog-pager__controls {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.catalog-pager__btn {
  appearance: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 6px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: inherit;
  font: inherit;
  font-variant-numeric: tabular-nums;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.catalog-pager__btn:hover:not(:disabled):not(.catalog-pager__btn--current) {
  background: rgba(15, 23, 42, 0.06);
}

.catalog-pager__btn:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary, 21, 101, 192));
  outline-offset: 1px;
}

.catalog-pager__btn:disabled {
  opacity: 0.35;
  cursor: default;
}

.catalog-pager__btn--current {
  background: rgb(var(--v-theme-primary, 21, 101, 192));
  color: #fff;
  font-weight: 600;
  cursor: default;
}

.catalog-pager__gap {
  min-width: 16px;
  text-align: center;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.catalog-pager__note {
  margin: 8px 0 0;
  font-size: 0.75rem;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

@media (max-width: 599px) {
  .catalog-pager__bar {
    justify-content: center;
  }

  .catalog-pager__status {
    width: 100%;
    text-align: center;
  }

  .catalog-pager__status-page {
    display: none;
  }

  .catalog-pager__status-range {
    display: inline;
  }
}
</style>
