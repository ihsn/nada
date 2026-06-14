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
        prepend-icon="mdi-star"
      >
        {{ t('featured_study') }}
      </v-chip>

      <div class="study-card-inner">
        <div class="study-card-type-icon flex-shrink-0" :title="typeLabel || undefined">
          <v-icon :icon="datasetTypeIconName" size="28" class="type-icon-glyph" />
        </div>

        <div class="study-card-content">
          <div v-if="accessLabel || typeLabel || dataClassBadge" class="study-card-chips">
            <v-chip
              v-if="accessLabel"
              size="x-small"
              variant="flat"
              class="study-access-chip font-weight-medium"
              :style="{ '--chip-bg': accessInfo.bg }"
            >
              <v-icon start size="12">{{ accessInfo.icon }}</v-icon>
              {{ accessLabel }}
            </v-chip>
            <v-chip
              v-if="typeLabel"
              size="x-small"
              color="secondary"
              variant="tonal"
              class="font-weight-medium"
            >
              {{ typeLabel }}
            </v-chip>
            <v-chip
              v-if="dataClassBadge"
              size="x-small"
              variant="flat"
              class="study-card-data-class font-weight-medium"
              :class="`study-card-data-class--${dataClassBadge.code}`"
            >
              {{ dataClassBadge.title }}
            </v-chip>
          </div>

          <div class="study-card-heading">
            <a :href="row.url" class="study-title text-body-1 font-weight-semibold">
              {{ row.title }}
            </a>
            <div v-if="row.subtitle" class="study-subtitle text-caption text-medium-emphasis">
              {{ row.subtitle }}
            </div>
          </div>

          <div
            v-if="row.nation || yearRange || row.authoring_entity"
            class="study-card-meta"
          >
            <div v-if="row.nation || yearRange" class="study-meta-line">
              <v-icon size="14" class="study-meta-line__icon">mdi-map-marker-outline</v-icon>
              <span v-if="row.nation">{{ row.nation }}</span>
              <span v-if="row.nation && yearRange" class="study-meta-line__sep">&middot;</span>
              <span v-if="yearRange">{{ yearRange }}</span>
            </div>
            <div v-if="row.authoring_entity" class="study-meta-line study-meta-line--author">
              {{ row.authoring_entity }}
            </div>
          </div>

          <div v-if="timeseriesDimensions.length" class="study-dimensions">
            <span class="study-dimensions__label">{{ t('dimensions') }}:</span>
            <v-chip
              v-for="dimension in timeseriesDimensions"
              :key="dimension"
              size="x-small"
              variant="tonal"
              color="secondary"
              class="study-dimension-chip"
            >
              {{ dimension }}
            </v-chip>
          </div>

          <div v-if="timeseriesDatabase" class="study-ts-database study-meta-line">
            <span class="study-ts-database__label">{{ t('dataset') }}:</span>
            <a v-if="timeseriesDatabase.url" :href="timeseriesDatabase.url" class="study-ts-database__link">{{ timeseriesDatabase.title }}</a>
            <span v-else>{{ timeseriesDatabase.title }}</span>
          </div>

          <div v-if="showAbstract && row.abstract" class="study-abstract text-body-2 text-medium-emphasis">
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

          <div v-if="collections.length" class="study-collections">
            <v-chip
              v-for="col in collections"
              :key="col.repositoryid + col.title"
              size="x-small"
              variant="outlined"
              :href="collectionUrl(col.repositoryid)"
              tag="a"
              class="collection-chip"
              @click.prevent="goToCollection(col.repositoryid)"
            >
              {{ col.title }}
            </v-chip>
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
import { computed, ref, watch } from 'vue';
import { useI18n } from '@/shared/composables/useI18n';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { formatCatalogDate, formatStudyYearRange } from '../catalogDate';
import { parseTimeseriesDimensions } from '../catalogTimeseriesDimensions';
import { resolveStudyThumbnailUrl } from '../catalogThumbnail';
import { datasetTypeIcon } from '../catalogDatasetTypeIcons';
import { joinSiteUrl, catalogSearchUrl } from '../catalogUrls';
import CatalogStudySemanticPages from './CatalogStudySemanticPages.vue';
import { catalogDatasetTypeLabel } from '../catalogDatasetTypeLabel';
import CatalogStudyVariableMatches from './CatalogStudyVariableMatches.vue';

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
  () => { thumbnailFailed.value = false; }
);

/** Solid backgrounds — nada52 corporate palette; white text for readability. */
const ACCESS_MAP = {
  open:                { icon: 'mdi-lock-open-variant',    bg: '#814C89' }, // accent-01
  cc40:                { icon: 'mdi-creative-commons',     bg: '#814C89' },
  public:              { icon: 'mdi-account-check',        bg: '#529600' }, // accent-03
  direct:              { icon: 'mdi-database-arrow-down',  bg: '#7FB142' }, // accent-04
  licensed:            { icon: 'mdi-lock',                 bg: '#0079BD' }, // action-color
  enclave:             { icon: 'mdi-shield-lock',          bg: '#283593' },
  remote:              { icon: 'mdi-link-variant',         bg: '#0079BD' },
  data_na:             { icon: 'mdi-minus-circle-outline', bg: '#546E7A' }, // secondary
  research:            { icon: 'mdi-flask-outline',        bg: '#0079BD' },
  research_public:     { icon: 'mdi-flask-outline',        bg: '#529600' },
  research_license:    { icon: 'mdi-flask-outline',        bg: '#0079BD' },
  research_public_lic: { icon: 'mdi-flask-outline',        bg: '#0079BD' },
};

const DEFAULT_ACCESS = { icon: 'mdi-file-document-outline', bg: '#546E7A' };

const accessInfo = computed(() => ACCESS_MAP[props.row.form_model] ?? DEFAULT_ACCESS);

const accessLabel = computed(() => {
  const key = 'legend_data_' + (props.row.form_model || 'data_na');
  return t(key, props.row.form_model ?? '');
});

const datasetType = computed(() => props.row.type || props.row.dtype || 'survey');

const datasetTypeIconName = computed(() => datasetTypeIcon(datasetType.value));

const typeLabel = computed(() => {
  if (!props.row.type && !props.row.dtype) return '';
  return catalogDatasetTypeLabel(t, datasetType.value);
});

const timeseriesDimensions = computed(() => parseTimeseriesDimensions(props.row));

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
  return { code, title: t('data_class_' + code, item.title) };
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

function collectionUrl(repositoryid) {
  return catalogSearchUrl(siteUrl.value, repositoryid);
}

function goToCollection(repositoryid) {
  window.location.assign(collectionUrl(repositoryid));
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

.collection-chip {
  text-decoration: none;
  position: relative;
  z-index: 1;
}

.collection-chip:hover {
  background-color: #000 !important;
  border-color: #000 !important;
  color: #fff !important;
}

.collection-chip:hover :deep(.v-chip__overlay),
.collection-chip:hover :deep(.v-chip__underlay) {
  opacity: 0 !important;
}

.collection-chip:hover :deep(.v-chip__content) {
  color: #fff !important;
}

.abstract-toggle {
  font-size: 0.875rem;
  color: #1976d2;
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
  width: 36px;
  flex-shrink: 0;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 3px;
}

.type-icon-glyph {
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68)) !important;
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
  margin-bottom: 2px;
  width: 100%;
}

.study-card-chips :deep(.v-chip__content) {
  font-size: 0.75rem;
}

.study-access-chip {
  background-color: var(--chip-bg) !important;
  color: #fff !important;
}

.study-access-chip :deep(.v-icon) {
  color: #fff !important;
}

.study-card-heading {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.study-card-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.study-meta-line {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  font-size: 0.9375rem;
  line-height: 1.45;
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
}

.study-meta-line__icon {
  opacity: 0.55;
  flex-shrink: 0;
}

.study-meta-line__sep {
  opacity: 0.4;
}

.study-meta-line--author {
  font-size: 0.875rem;
  font-style: italic;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.study-abstract {
  font-size: 0.9375rem;
  line-height: 1.5;
  margin-top: 2px;
}

.study-dimensions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.study-dimensions__label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--catalog-text-muted, rgba(26, 35, 50, 0.68));
}

.study-dimension-chip {
  font-size: 0.75rem;
  line-height: 1.2;
}

.study-card-data-class {
  margin-left: auto;
  flex-shrink: 0;
}

.study-card-data-class :deep(.v-chip__content) {
  font-size: 0.75rem;
}

.study-card-data-class--public {
  background-color: #f3edf4 !important;
  color: #714177 !important;
}

.study-card-data-class--official {
  background-color: #e8f5e9 !important;
  color: #2e7d32 !important;
}

.study-card-data-class--confidential {
  background-color: #f1f8e9 !important;
  color: #558b2f !important;
}

.study-collections {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 2px;
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
  font-size: 1.0625rem;
  line-height: 1.4;
  color: #1565c0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.study-title:hover {
  text-decoration: underline;
}

.study-subtitle {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.35;
}

.study-card-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 16px;
  margin-top: 4px;
  padding-top: 6px;
  border-top: 1px solid var(--catalog-border-subtle, rgba(15, 23, 42, 0.11));
  font-size: 0.8125rem;
  line-height: 1.5;
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.56));
}

.collection-chip :deep(.v-chip__content) {
  font-size: 0.75rem;
}

.study-card-footer__item {
  display: inline-flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.25rem;
  white-space: nowrap;
}

.study-card-footer__label {
  color: var(--catalog-text-faint, rgba(26, 35, 50, 0.56));
  font-weight: 400;
}

.study-card-footer__value {
  color: var(--catalog-text-secondary, rgba(26, 35, 50, 0.82));
  font-weight: 500;
}

.study-card-footer__link {
  color: #1565c0;
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
