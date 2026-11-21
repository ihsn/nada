<template>
  <v-card
    variant="outlined"
    class="study-card"
    :class="{ 'study-card--featured': row.featured }"
  >
    <v-card-text class="pa-4">
      <v-chip
        v-if="row.featured"
        size="x-small"
        color="amber-darken-2"
        variant="flat"
        class="mb-2 font-weight-medium"
        prepend-icon="$mdi-star"
      >
        {{ t('featured_study') }}
      </v-chip>

      <div class="study-card-inner">
        <div
          class="study-card-type-icon flex-shrink-0"
          :title="typeLabel || undefined"
          :role="typeLabel ? 'img' : undefined"
          :aria-label="typeLabel || undefined"
        >
          <v-icon :icon="datasetTypeIconName" size="28" class="type-icon-glyph" aria-hidden="true" />
        </div>

        <div class="study-card-content">
          <div v-if="accessLabel || dataClassBadge" class="study-card-chips">
            <v-chip
              v-if="accessLabel"
              size="x-small"
              variant="flat"
              class="study-access-chip font-weight-medium"
              :style="{ '--chip-fg': accessInfo.fg, '--chip-bg': accessInfo.bg }"
              :title="accessTitle"
              :aria-label="accessTitle"
            >
              <v-icon size="13" class="study-access-chip__icon">{{ accessInfo.icon }}</v-icon>
              {{ accessLabel }}
            </v-chip>
            <v-chip
              v-if="dataClassBadge"
              size="x-small"
              variant="flat"
              class="study-card-data-class font-weight-medium"
              :class="`study-card-data-class--${dataClassBadge.code}`"
              :title="dataClassBadge.note || undefined"
            >
              {{ dataClassBadge.title }}
            </v-chip>
          </div>

          <div class="study-card-heading">
            <a :href="row.url" class="study-title text-body-1 font-weight-semibold">
              {{ row.title }}
            </a>
            <div v-if="row.subtitle" class="study-subtitle">
              {{ row.subtitle }}
            </div>
          </div>

          <div
            v-if="row.nation || yearRange || producers.length"
            class="study-card-meta"
          >
            <div v-if="row.nation || yearRange" class="study-meta-line study-meta-line--place">
              <!-- one text run so long country lists wrap naturally; keep on one line (whitespace matters) -->
              <span class="study-meta-line__text"><span v-if="row.nation">{{ nationLabel }}</span> <span v-if="yearRange" class="study-meta-line__years"><span v-if="row.nation" class="study-meta-line__sep">&middot;&nbsp;</span>{{ yearRange }}</span></span>
            </div>
            <div
              v-if="producers.length"
              class="study-meta-line study-meta-line--author"
              :title="producersHidden > 0 ? row.authoring_entity : undefined"
            >
              <span>{{ visibleProducers.join(', ') }}</span>
              <button
                v-if="producersHidden > 0 || producersExpanded"
                type="button"
                class="study-meta-more"
                :aria-expanded="producersExpanded"
                @click.stop="producersExpanded = !producersExpanded"
              >
                {{ producersExpanded ? t('show_fewer', 'Show fewer') : t('n_more', '+%d more', producersHidden) }}
              </button>
            </div>
          </div>

          <div v-if="timeseriesFrequency || timeseriesDimensions.length" class="study-dimensions">
            <v-chip
              v-if="timeseriesFrequency"
              size="x-small"
              variant="tonal"
              class="study-dimension-chip"
              :title="t('frequency')"
              :aria-label="`${t('frequency')}: ${timeseriesFrequency}`"
            >
              {{ timeseriesFrequency }}
            </v-chip>
            <v-chip
              v-for="dimension in timeseriesDimensions"
              :key="dimension"
              size="x-small"
              variant="tonal"
              color="secondary"
              class="study-dimension-chip"
              :title="t('dimensions')"
              :aria-label="`${t('dimensions')}: ${dimension}`"
            >
              {{ dimension }}
            </v-chip>
          </div>

          <div v-if="timeseriesDatabase" class="study-ts-database study-meta-line">
            <span class="study-ts-database__label">{{ t('dataset') }}:</span>
            <a v-if="timeseriesDatabase.url" :href="timeseriesDatabase.url" class="study-ts-database__link">{{ timeseriesDatabase.title }}</a>
            <span v-else>{{ timeseriesDatabase.title }}</span>
          </div>

          <div v-if="showAbstract && row.abstract" class="study-abstract">
            <template v-if="abstractExpanded || !abstractNeedsTruncation">
              {{ row.abstract }}
              <a
                v-if="abstractNeedsTruncation"
                href="#"
                class="abstract-toggle"
                @click.prevent="abstractExpanded = false"
              >{{ t('read_less') }}</a>
            </template>
            <template v-else>
              {{ abstractShort }}&hellip;
              <a href="#" class="abstract-toggle" @click.prevent="abstractExpanded = true">
                {{ t('read_more') }}
              </a>
            </template>
          </div>

          <div v-if="uniqueCollections.length" class="study-collections">
            <v-icon
              icon="$mdi-folder-outline"
              size="14"
              class="study-collections__icon"
              :title="t('collections', 'Collections')"
              :aria-label="t('collections', 'Collections')"
            />
            <a
              v-for="col in visibleCollections"
              :key="col.repositoryid"
              :href="collectionUrl(col.repositoryid)"
              class="collection-chip"
              :title="col.title"
            >
              {{ col.title }}
            </a>
            <button
              v-if="hiddenCollectionCount > 0 || collectionsExpanded"
              type="button"
              class="collection-chip collection-chip--more"
              :aria-expanded="collectionsExpanded"
              @click.stop="collectionsExpanded = !collectionsExpanded"
            >
              {{ collectionsExpanded ? t('show_fewer', 'Show fewer') : t('n_more', '+%d more', hiddenCollectionCount) }}
            </button>
          </div>

          <div class="study-card-footer">
            <span v-if="row.idno" class="study-card-footer__item study-card-footer__idno">
              <span class="study-card-footer__label">{{ t('id') }}:</span>
              <span class="study-card-footer__value">{{ row.idno }}</span>
            </span>
            <span v-if="changedLabel" class="study-card-footer__item">
              <span class="study-card-footer__label">{{ t('last_modified') }}:</span>
              <span class="study-card-footer__value">{{ changedLabel }}</span>
            </span>
            <span v-else-if="createdLabel" class="study-card-footer__item">
              <span class="study-card-footer__label">{{ t('created_on') }}:</span>
              <span class="study-card-footer__value">{{ createdLabel }}</span>
            </span>
            <span v-if="row.total_views > 0" class="study-card-footer__item">
              <span class="study-card-footer__label">{{ t('views') }}:</span>
              <span class="study-card-footer__value">{{ Number(row.total_views).toLocaleString() }}</span>
            </span>
            <span v-if="citationCount > 0" class="study-card-footer__item">
              <span class="study-card-footer__label">{{ t('citations') }}:</span>
              <a
                :href="citationsUrl"
                class="study-card-footer__link"
                :title="t('related_citations', 'Related citations')"
              >
                {{ citationCount.toLocaleString() }}
              </a>
            </span>
          </div>
        </div>

        <div
          v-if="thumbnailSrc && !thumbnailFailed"
          class="study-card-media flex-shrink-0"
        >
          <v-img
            :src="thumbnailSrc"
            width="120"
            max-height="180"
            cover
            class="rounded-lg study-thumbnail"
            @error="thumbnailFailed = true"
          />
        </div>
      </div>
    </v-card-text>

    <CatalogStudySemanticPages :row="row" />

    <CatalogStudyVariableMatches
      :row="row"
      :search-keyword="searchKeyword"
    />
  </v-card>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { formatCatalogDate, formatStudyYearRange } from '../catalogDate';
import { parseTimeseriesDimensions, parseTimeseriesFrequency } from '../catalogTimeseriesDimensions';
import { resolveStudyThumbnailUrl } from '../catalogThumbnail';
import { datasetTypeIcon } from '../catalogDatasetTypeIcons';
import { joinSiteUrl, catalogSearchUrl } from '../catalogUrls';
import { catalogDatasetTypeLabel } from '../catalogDatasetTypeLabel';

const CatalogStudySemanticPages = defineAsyncComponent(
  () => import('./CatalogStudySemanticPages.vue')
);
const CatalogStudyVariableMatches = defineAsyncComponent(
  () => import('./CatalogStudyVariableMatches.vue')
);

defineOptions({ name: 'CatalogStudyCard' });

const props = defineProps({
  row:                 { type: Object, required: true },
  collections:         { type: Array,  default: () => [] },
  citations:           { type: Object, default: null },
  dataClassifications: { type: Object, default: null },
  showAbstract:        { type: Boolean, default: false },
  searchKeyword:       { type: String, default: '' },
});

const { t } = useI18n();
const { siteUrl, baseUrl } = useAppConfig();

const abstractExpanded = ref(false);
const thumbnailFailed = ref(false);

watch(
  () => props.row.id,
  () => {
    thumbnailFailed.value = false;
    collectionsExpanded.value = false;
    producersExpanded.value = false;
  }
);

/**
 * Tonal access badges: tinted background, coloured text/icon. Hues follow the nada52
 * palette but each access type gets its own so they stay distinguishable.
 */
const ACCESS_MAP = {
  open:                { icon: '$mdi-lock-open-variant',    fg: '#6b3a73', bg: '#f4edf5' },
  cc40:                { icon: '$mdi-creative-commons',     fg: '#6b3a73', bg: '#f4edf5' },
  public:              { icon: '$mdi-account-check',        fg: '#3f6f00', bg: '#eef5e4' },
  direct:              { icon: '$mdi-database-arrow-down',  fg: '#00695c', bg: '#e6f4f1' },
  licensed:            { icon: '$mdi-lock',                 fg: '#005a8c', bg: '#e5f1f9' },
  enclave:             { icon: '$mdi-shield-lock',          fg: '#283593', bg: '#ebedf8' },
  data_enclave:        { icon: '$mdi-shield-lock',          fg: '#283593', bg: '#ebedf8' },
  remote:              { icon: '$mdi-open-in-new',          fg: '#3d5a6c', bg: '#ebf0f3' },
  data_na:             { icon: '$mdi-minus-circle-outline', fg: '#5b6770', bg: '#eff1f3' },
  research:            { icon: '$mdi-flask-outline',        fg: '#005a8c', bg: '#e5f1f9' },
  research_public:     { icon: '$mdi-flask-outline',        fg: '#3f6f00', bg: '#eef5e4' },
  research_license:    { icon: '$mdi-flask-outline',        fg: '#005a8c', bg: '#e5f1f9' },
  research_public_lic: { icon: '$mdi-flask-outline',        fg: '#005a8c', bg: '#e5f1f9' },
};

/** Empty/unknown form_model is shown as "not available", so use the same icon. */
const accessKey = computed(() => props.row.form_model || 'data_na');

const accessInfo = computed(() => ACCESS_MAP[accessKey.value] ?? ACCESS_MAP.data_na);

/** Full legend text, e.g. "Data available from external repository" (tooltip / a11y). */
const accessTitle = computed(() => t('legend_data_' + accessKey.value, accessKey.value));

/** Short card label, e.g. "External repository"; falls back to the full legend text. */
const accessLabel = computed(() => {
  if (datasetType.value === 'timeseries') return '';
  return t('access_short_' + accessKey.value, accessTitle.value);
});

const datasetType = computed(() => props.row.type || props.row.dtype || 'survey');

const datasetTypeIconName = computed(() => datasetTypeIcon(datasetType.value));

const typeLabel = computed(() => {
  if (!props.row.type && !props.row.dtype) return '';
  return catalogDatasetTypeLabel(t, datasetType.value);
});

const timeseriesDimensions = computed(() => parseTimeseriesDimensions(props.row));
const timeseriesFrequency  = computed(() => parseTimeseriesFrequency(props.row));

const timeseriesDatabase = computed(() => {
  if (props.row?.type !== 'timeseries') return null;
  const title = props.row?.ts_db_title;
  if (!title) return null;
  const dbId = props.row?.ts_db_study_id;
  const url = dbId ? joinSiteUrl(siteUrl.value, `catalog/${dbId}`) : null;
  return { title, url };
});

const dataClassBadge = computed(() => {
  if (props.row?.type !== 'survey') return null;
  const id = props.row?.data_class_id;
  if (id == null || id === '' || !props.dataClassifications) return null;
  const item = props.dataClassifications[id] ?? props.dataClassifications[String(id)];
  if (!item?.code || !item?.title) return null;
  const code = String(item.code).toLowerCase();
  const noteKey = 'data_class_note_' + code;
  const note = t(noteKey, '');
  return { code, title: t('data_class_' + code, item.title), note: note === noteKey ? '' : note };
});

const citationCount = computed(() => {
  const map = props.citations;
  if (!map || typeof map !== 'object') return 0;
  const n = map[props.row.id] ?? map[String(props.row.id)];
  const count = Number(n);
  return Number.isFinite(count) && count > 0 ? count : 0;
});

const citationsUrl = computed(() =>
  joinSiteUrl(siteUrl.value, `catalog/${props.row.id}/related_citations`)
);

// Keep the "+N more" suffix (added by Dataset_model::get_country_names_string) on the last name's line.
const nationLabel = computed(() =>
  String(props.row.nation || '').replace(/ \+(\d+)$/, ' +$1')
);

/** Producers are stored joined with ", "; split, trim and drop exact duplicates. */
const PRODUCERS_VISIBLE = 2;
const producersExpanded = ref(false);

const producers = computed(() => {
  const seen = new Set();
  return String(props.row.authoring_entity || '')
    .split(/,\s+/)
    .map((p) => p.trim())
    .filter((p) => {
      const key = p.toLowerCase();
      if (!p || seen.has(key)) return false;
      seen.add(key);
      return true;
    });
});

/** Only collapse when it saves at least two names; otherwise "+1 more" costs as much as the name. */
const producersCollapsible = computed(() => producers.value.length > PRODUCERS_VISIBLE + 1);

const visibleProducers = computed(() =>
  producersExpanded.value || !producersCollapsible.value
    ? producers.value
    : producers.value.slice(0, PRODUCERS_VISIBLE)
);

const producersHidden = computed(() =>
  producersCollapsible.value ? producers.value.length - PRODUCERS_VISIBLE : 0
);

const yearRange = computed(() =>
  formatStudyYearRange(props.row.year_start, props.row.year_end)
);

const thumbnailSrc = computed(() =>
  resolveStudyThumbnailUrl(
    props.row.thumbnail,
    baseUrl.value,
    siteUrl.value,
    props.row.changed
  )
);

const abstractShort = computed(() => {
  const text = props.row.abstract || '';
  return text.length > 250 ? text.slice(0, 250) : text;
});

const abstractNeedsTruncation = computed(() =>
  (props.row.abstract || '').length > 250
);

const changedLabel = computed(() => formatCatalogDate(props.row.changed));
const createdLabel = computed(() => formatCatalogDate(props.row.created));

const COLLECTIONS_VISIBLE = 3;
const collectionsExpanded = ref(false);

/** One pill per collection id; skip entries without an id or title. */
const uniqueCollections = computed(() => {
  const seen = new Set();
  return (props.collections || []).filter((col) => {
    const id = col?.repositoryid;
    if (!id || !col.title || seen.has(id)) return false;
    seen.add(id);
    return true;
  });
});

const visibleCollections = computed(() =>
  collectionsExpanded.value
    ? uniqueCollections.value
    : uniqueCollections.value.slice(0, COLLECTIONS_VISIBLE)
);

const hiddenCollectionCount = computed(() =>
  Math.max(0, uniqueCollections.value.length - COLLECTIONS_VISIBLE)
);

function collectionUrl(repositoryid) {
  return catalogSearchUrl(siteUrl.value, repositoryid);
}
</script>

<style scoped>
.study-card {
  --catalog-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
  --catalog-shadow-hover: 0 4px 12px rgba(15, 23, 42, 0.1);
  transition: box-shadow 0.18s ease, border-color 0.18s ease;
  border-radius: 10px !important;
  background: var(--catalog-surface, #fff) !important;
  border: 1px solid var(--catalog-border, rgba(15, 23, 42, 0.16)) !important;
  box-shadow: var(--catalog-shadow) !important;
  overflow: hidden;
}

.study-card--featured {
  border-color: rgba(230, 162, 0, 0.55) !important;
  background: linear-gradient(135deg, #fff8e8 0%, var(--catalog-surface, #fff) 100%) !important;
}

.study-card:hover {
  border-color: rgba(21, 101, 192, 0.45) !important;
  box-shadow: var(--catalog-shadow-hover) !important;
}

.study-card :deep(a:not(.study-title)) {
  text-decoration: none;
  color: inherit;
}

.study-ts-database__link {
  color: rgb(var(--v-theme-primary)) !important;
  text-decoration: none;
}

.study-ts-database__link:hover {
  text-decoration: underline;
}

.abstract-toggle {
  font-size: var(--catalog-font-ui, 0.875rem);
  color: var(--catalog-link, #1565c0);
  text-decoration: none;
  margin-left: 0.25rem;
  white-space: nowrap;
}

.abstract-toggle:hover {
  text-decoration: underline;
}

.study-card-inner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.study-card-type-icon {
  width: 32px;
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 1px;
}

.type-icon-glyph {
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62)) !important;
}

.study-card-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.study-card-chips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  width: 100%;
}

.study-card-chips :deep(.v-chip__content) {
  font-size: 0.75rem;
}

.study-access-chip {
  background-color: var(--chip-bg) !important;
  color: var(--chip-fg) !important;
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--chip-fg) 18%, transparent);
}

.study-access-chip :deep(.v-chip__underlay),
.study-access-chip :deep(.v-chip__overlay) {
  opacity: 0 !important;
}

.study-access-chip__icon {
  margin-inline-end: 4px;
  color: var(--chip-fg) !important;
}

.study-card-heading {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.study-card-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.study-meta-line {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  font-size: var(--catalog-font-ui, 0.875rem);
  line-height: 1.5;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
}

.study-meta-line__sep {
  opacity: 0.4;
}

.study-meta-line--place {
  flex-wrap: nowrap;
  align-items: flex-start;
}

.study-meta-line__text {
  min-width: 0;
}

.study-meta-line__years {
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}

.study-meta-line--author {
  display: block;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.study-meta-more {
  margin-inline-start: 6px;
  padding: 0;
  border: 0;
  background: transparent;
  font: inherit;
  color: var(--catalog-link, #1565c0);
  white-space: nowrap;
  cursor: pointer;
}

.study-meta-more:hover {
  text-decoration: underline;
}

.study-abstract {
  font-size: 0.9375rem;
  line-height: 1.55;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
}

.study-dimensions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.study-dimension-chip {
  font-size: 0.75rem;
  line-height: 1.2;
}

/* Outline (not filled) so it reads as a different kind of label from the access badge */
.study-card-data-class {
  flex-shrink: 0;
  background-color: transparent !important;
  /* Same border strength as the access badge, so neither looks larger */
  box-shadow: inset 0 0 0 1px color-mix(in srgb, currentColor 35%, transparent);
}

.study-card-data-class :deep(.v-chip__underlay),
.study-card-data-class :deep(.v-chip__overlay) {
  opacity: 0 !important;
}

.study-card-data-class :deep(.v-chip__content) {
  font-size: 0.75rem;
}

.study-card-data-class--public {
  color: #6b3a73 !important;
}

.study-card-data-class--official {
  color: #2e6b30 !important;
}

.study-card-data-class--confidential {
  color: #8a5300 !important;
}

.study-collections {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.study-collections__icon {
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62)) !important;
  margin-inline-end: 2px;
}

/* Soft secondary tags: they label the study, they shouldn't outweigh the title */
.study-card .collection-chip {
  display: inline-flex;
  align-items: center;
  max-width: 260px;
  height: 22px;
  padding: 0 9px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  border: 1px solid rgba(15, 23, 42, 0.1);
  border-radius: 11px;
  background: rgba(15, 23, 42, 0.04);
  font: inherit;
  font-size: 0.75rem;
  line-height: 20px;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  text-decoration: none;
  cursor: pointer;
  position: relative;
  z-index: 1;
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.study-card .collection-chip:hover {
  background: rgba(21, 101, 192, 0.08);
  border-color: rgba(21, 101, 192, 0.3);
  color: var(--catalog-link, #1565c0);
}

.study-card .collection-chip:focus-visible {
  outline: 2px solid var(--catalog-link, #1565c0);
  outline-offset: 1px;
}

.study-card .collection-chip--more {
  background: transparent;
  border-color: transparent;
  color: var(--catalog-link, #1565c0);
  padding: 0 4px;
}

.study-card .collection-chip--more:hover {
  background: transparent;
  border-color: transparent;
  text-decoration: underline;
}

.study-card-media {
  width: 120px;
  flex-shrink: 0;
  display: flex;
  justify-content: flex-end;
  align-items: flex-start;
}

.study-thumbnail {
  width: 100%;
  max-height: 180px;
}

.study-title {
  margin: 0;
  font-size: var(--catalog-font-title, 1.25rem);
  line-height: 1.35;
  font-weight: 600;
  color: var(--catalog-link, #1565c0);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-wrap: pretty;
}

.study-title:hover {
  text-decoration: underline;
}

.study-subtitle {
  margin: 0;
  font-size: var(--catalog-font-ui, 0.875rem);
  line-height: 1.45;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
}

.study-card-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 16px;
  margin-top: 4px;
  padding-top: 8px;
  border-top: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.11));
  font-size: var(--catalog-font-small, 0.75rem);
  line-height: 1.5;
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
}


.study-card-footer__item {
  display: inline-flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.25rem;
  white-space: nowrap;
}

.study-card-footer__label {
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.62));
  font-weight: 400;
}

.study-card-footer__value {
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  font-weight: 500;
}

.study-card-footer__link {
  color: var(--catalog-link, #1565c0);
  font-weight: 500;
  text-decoration: none;
}

.study-card-footer__link:hover {
  text-decoration: underline;
}

.study-card-footer__idno .study-card-footer__value {
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-weight: 400;
  letter-spacing: 0.01em;
}

.study-card :deep(.study-semantic-pages-block:last-child) {
  border-radius: 0 0 9px 9px;
}
</style>
