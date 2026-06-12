<template>
  <div class="catalog-image-gallery">
    <ul class="image-gallery-list">
      <li
        v-for="row in rows"
        :key="row.id"
        class="image-gallery-item"
      >
        <v-tooltip location="top" :text="row.title">
          <template #activator="{ props: tooltipProps }">
            <a
              v-bind="tooltipProps"
              :href="studyUrl(row)"
              class="image-gallery-link"
              :title="row.title"
            >
              <img
                :src="thumbnailFor(row)"
                :alt="row.title"
                class="image-gallery-img shadow-sm"
                loading="lazy"
                @error="onThumbError(row.id)"
              />
            </a>
          </template>
        </v-tooltip>
      </li>
      <li class="image-gallery-spacer" aria-hidden="true" />
    </ul>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { resolveStudyThumbnailUrl } from '../catalogThumbnail';
import { joinSiteUrl } from '../catalogUrls';

defineOptions({ name: 'CatalogImageGallery' });

const props = defineProps({
  rows: { type: Array, required: true },
});

const { siteUrl, baseUrl } = useAppConfig();
const failedIds = ref(new Set());

watch(
  () => props.rows,
  () => { failedIds.value = new Set(); },
  { deep: true }
);

function blankThumbnailUrl() {
  const root = (baseUrl.value || siteUrl.value || '').replace(/\/+$/, '');
  return root ? `${root}/files/icon-blank.png` : '/files/icon-blank.png';
}

function thumbnailFor(row) {
  if (failedIds.value.has(row.id)) {
    return blankThumbnailUrl();
  }
  return (
    resolveStudyThumbnailUrl(row.thumbnail, baseUrl.value, siteUrl.value, row.changed)
    || blankThumbnailUrl()
  );
}

function onThumbError(id) {
  failedIds.value = new Set([...failedIds.value, id]);
}

function studyUrl(row) {
  if (row.url) return row.url;
  return joinSiteUrl(siteUrl.value, `catalog/${row.id}`);
}
</script>

<style scoped>
.catalog-image-gallery {
  margin-top: 4px;
}

.image-gallery-list {
  display: flex;
  flex-wrap: wrap;
  margin: 0;
  padding: 0;
  list-style: none;
}

.image-gallery-item {
  flex-grow: 1;
  margin: 5px;
  min-width: 120px;
  max-width: 300px;
}

.image-gallery-spacer {
  flex-grow: 10;
  margin: 5px;
  list-style: none;
}

.image-gallery-link {
  display: block;
  line-height: 0;
}

.image-gallery-img {
  height: 150px;
  max-height: 200px;
  min-width: 100%;
  width: 100%;
  max-width: 300px;
  object-fit: cover;
  vertical-align: bottom;
  border-radius: 4px;
  transition: outline 0.15s ease;
}

.image-gallery-link:hover .image-gallery-img {
  outline: 1px solid rgba(100, 100, 100, 0.65);
  outline-offset: -1px;
}
</style>
