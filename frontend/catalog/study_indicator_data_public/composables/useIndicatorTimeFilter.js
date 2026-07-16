import { ref, reactive, computed, watch, unref } from 'vue';
import {
  clamp,
  parseLeadingCalendarYear,
  resolveTimeBoundsYears,
  parseSubPeriodStr,
  QUARTER_OPTIONS,
  MONTH_OPTIONS,
} from '@/shared/timeseries/indicatorTimeFilterUtils.js';

/**
 * Shared time-period filter state for chart and table sidebars.
 * @param {{ filterDraft: object; schema: import('vue').MaybeRefOrGetter<object|null>; timeBounds: import('vue').MaybeRefOrGetter<object|null|undefined> }} options
 */
export function useIndicatorTimeFilter({ filterDraft, schema, timeBounds }) {
  const yearSliderLocal = ref([2000, 2020]);
  const subPeriodDraft = reactive({ fromYear: null, fromSub: null, toYear: null, toSub: null });

  const timeBoundsYears = computed(() =>
    resolveTimeBoundsYears(unref(schema), unref(timeBounds))
  );

  const canUseYearSlider = computed(() => {
    const b = timeBoundsYears.value;
    if (!b) return false;
    const span = b.max - b.min;
    if (!Number.isFinite(span) || span < 0) return false;
    if (b.min < 1 || b.max > 9999 || span > 8000) return false;
    return true;
  });

  const subPeriodMode = computed(() => {
    const selected = filterDraft.dPeriodicity[0];
    if (!selected) return null;
    const code = String(selected).toUpperCase();
    if (code === 'M') return 'monthly';
    if (code === 'Q') return 'quarterly';
    return null;
  });

  const subPeriodYearOptions = computed(() => {
    const b = timeBoundsYears.value;
    if (!b) return [];
    const arr = [];
    for (let y = b.min; y <= b.max; y++) arr.push({ title: String(y), value: y });
    return arr;
  });

  const timeFacetShowSummary = computed(() => {
    if (subPeriodMode.value) {
      return !!(String(filterDraft.from ?? '').trim() || String(filterDraft.to ?? '').trim());
    }
    if (canUseYearSlider.value) {
      const b = timeBoundsYears.value;
      if (!b) return false;
      const lo = Number(yearSliderLocal.value[0]);
      const hi = Number(yearSliderLocal.value[1]);
      if (!Number.isFinite(lo) || !Number.isFinite(hi)) return false;
      return lo !== b.min || hi !== b.max;
    }
    return !!(String(filterDraft.from ?? '').trim() || String(filterDraft.to ?? '').trim());
  });

  const timeFacetSummaryCount = computed(() => {
    if (subPeriodMode.value) {
      let n = 0;
      if (String(filterDraft.from ?? '').trim()) n += 1;
      if (String(filterDraft.to ?? '').trim()) n += 1;
      return n;
    }
    if (canUseYearSlider.value) {
      const lo = Number(yearSliderLocal.value[0]);
      const hi = Number(yearSliderLocal.value[1]);
      if (!Number.isFinite(lo) || !Number.isFinite(hi)) return 0;
      return Math.max(0, Math.trunc(hi) - Math.trunc(lo) + 1);
    }
    let n = 0;
    if (String(filterDraft.from ?? '').trim()) n += 1;
    if (String(filterDraft.to ?? '').trim()) n += 1;
    return n;
  });

  function syncYearSliderLocalFromFilters() {
    const b = timeBoundsYears.value;
    if (!b) return;
    const useSlider = canUseYearSlider.value;
    const fy = parseLeadingCalendarYear(filterDraft.from);
    const ty = parseLeadingCalendarYear(filterDraft.to);
    const fromEmpty = !String(filterDraft.from ?? '').trim();
    const toEmpty = !String(filterDraft.to ?? '').trim();
    if (fy != null && ty != null) {
      const lo = clamp(fy, b.min, b.max);
      const hi = clamp(ty, b.min, b.max);
      yearSliderLocal.value = [lo, hi].sort((x, y) => x - y);
      if (useSlider) {
        filterDraft.from = String(yearSliderLocal.value[0]);
        filterDraft.to = String(yearSliderLocal.value[1]);
      }
      return;
    }
    if (fy != null && toEmpty) {
      yearSliderLocal.value = [clamp(fy, b.min, b.max), b.max].sort((x, y) => x - y);
      if (useSlider) {
        filterDraft.from = String(yearSliderLocal.value[0]);
      }
      return;
    }
    if (ty != null && fromEmpty) {
      yearSliderLocal.value = [b.min, clamp(ty, b.min, b.max)].sort((x, y) => x - y);
      if (useSlider) {
        filterDraft.to = String(yearSliderLocal.value[1]);
      }
      return;
    }
    yearSliderLocal.value = [b.min, b.max];
  }

  function onYearSliderInput(val) {
    const b = timeBoundsYears.value;
    if (!b || !Array.isArray(val) || val.length < 2) return;
    const lo = clamp(Math.min(Number(val[0]), Number(val[1])), b.min, b.max);
    const hi = clamp(Math.max(Number(val[0]), Number(val[1])), b.min, b.max);
    yearSliderLocal.value = [lo, hi];
    filterDraft.from = String(lo);
    filterDraft.to = String(hi);
  }

  function syncSubPeriodDraftFromFilterDraft() {
    const mode = subPeriodMode.value;
    if (!mode) return;
    const b = timeBoundsYears.value;
    const fr = parseSubPeriodStr(filterDraft.from, mode);
    const to = parseSubPeriodStr(filterDraft.to, mode);
    const newFromYear = fr.year ?? (b?.min ?? null);
    const newFromSub = fr.sub ?? 1;
    const newToYear = to.year ?? (b?.max ?? null);
    const newToSub = to.sub ?? (mode === 'quarterly' ? 4 : 12);
    if (subPeriodDraft.fromYear !== newFromYear) subPeriodDraft.fromYear = newFromYear;
    if (subPeriodDraft.fromSub !== newFromSub) subPeriodDraft.fromSub = newFromSub;
    if (subPeriodDraft.toYear !== newToYear) subPeriodDraft.toYear = newToYear;
    if (subPeriodDraft.toSub !== newToSub) subPeriodDraft.toSub = newToSub;
  }

  function subPeriodDraftToFromTo() {
    const mode = subPeriodMode.value;
    if (!mode) return;
    let newFrom = '';
    let newTo = '';
    if (subPeriodDraft.fromYear != null && subPeriodDraft.fromSub != null) {
      newFrom =
        mode === 'quarterly'
          ? `${subPeriodDraft.fromYear}-Q${subPeriodDraft.fromSub}`
          : `${subPeriodDraft.fromYear}-${String(subPeriodDraft.fromSub).padStart(2, '0')}`;
    } else if (subPeriodDraft.fromYear != null) {
      newFrom = String(subPeriodDraft.fromYear);
    }
    if (subPeriodDraft.toYear != null && subPeriodDraft.toSub != null) {
      newTo =
        mode === 'quarterly'
          ? `${subPeriodDraft.toYear}-Q${subPeriodDraft.toSub}`
          : `${subPeriodDraft.toYear}-${String(subPeriodDraft.toSub).padStart(2, '0')}`;
    } else if (subPeriodDraft.toYear != null) {
      newTo = String(subPeriodDraft.toYear);
    }
    filterDraft.from = newFrom;
    filterDraft.to = newTo;
  }

  function clearFacetTime() {
    const b = timeBoundsYears.value;
    if (subPeriodMode.value && b) {
      subPeriodDraft.fromYear = b.min;
      subPeriodDraft.fromSub = 1;
      subPeriodDraft.toYear = b.max;
      subPeriodDraft.toSub = subPeriodMode.value === 'quarterly' ? 4 : 12;
      subPeriodDraftToFromTo();
      return;
    }
    if (canUseYearSlider.value && b) {
      yearSliderLocal.value = [b.min, b.max];
      filterDraft.from = String(b.min);
      filterDraft.to = String(b.max);
    } else {
      filterDraft.from = '';
      filterDraft.to = '';
    }
  }

  function syncTimeFilterUi() {
    if (subPeriodMode.value) {
      syncSubPeriodDraftFromFilterDraft();
    } else if (canUseYearSlider.value) {
      syncYearSliderLocalFromFilters();
    }
  }

  watch(
    () => [
      unref(timeBounds)?.min,
      unref(timeBounds)?.max,
      unref(schema)?.reporting_year_bounds?.min,
      unref(schema)?.reporting_year_bounds?.max,
      canUseYearSlider.value,
      subPeriodMode.value,
    ],
    () => {
      syncTimeFilterUi();
    }
  );

  watch(
    () => [filterDraft.from, filterDraft.to],
    () => {
      syncTimeFilterUi();
    }
  );

  watch(subPeriodDraft, () => {
    if (subPeriodMode.value) {
      subPeriodDraftToFromTo();
    }
  });

  return {
    yearSliderLocal,
    subPeriodDraft,
    timeBoundsYears,
    canUseYearSlider,
    subPeriodMode,
    subPeriodYearOptions,
    quarterOptions: QUARTER_OPTIONS,
    monthOptions: MONTH_OPTIONS,
    timeFacetShowSummary,
    timeFacetSummaryCount,
    onYearSliderInput,
    clearFacetTime,
    syncTimeFilterUi,
  };
}
