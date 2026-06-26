<template>
  <v-app class="pdf-viewer-app" :class="{ 'pdf-viewer-app--embedded': embedded }">
    <v-alert v-if="configError" type="error" class="ma-4">{{ configError }}</v-alert>
    <PdfViewerCore
      v-else
      :stream-url="streamUrl"
      :download-url="downloadUrl"
      :initial-page="viewerParams.page"
      :page-chips="viewerParams.pageChips"
      :title="displayTitle"
      :embedded="embedded"
    />
  </v-app>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import PdfViewerCore from '@/shared/pdf-viewer/PdfViewerCore.vue';
import { parseViewerParams, validateViewerParams } from '@/shared/pdf-viewer/pdfViewerParams';
import { buildPdfStreamUrl, buildResourceDownloadUrl } from '@/shared/pdf-viewer/pdfStreamUrl';
import { resolvePdfViewerTitle } from '@/shared/pdf-viewer/resolvePdfViewerTitle';

defineOptions({ name: 'PdfViewerApp' });

const { siteUrl } = useAppConfig();
const displayTitle = ref('');

const viewerParams = computed(() =>
  parseViewerParams(typeof window !== 'undefined' ? window.location.search : '')
);

const embedded = computed(() => {
  if (typeof window === 'undefined') return false;
  return new URLSearchParams(window.location.search).get('embed') === '1';
});

const configError = computed(() => validateViewerParams(viewerParams.value));

const streamUrl = computed(() => {
  if (configError.value) return '';
  const p = viewerParams.value;
  return buildPdfStreamUrl(siteUrl.value, {
    source: p.source,
    sid: p.sid,
    idno: p.idno,
    resourceId: p.resourceId,
  });
});

const downloadUrl = computed(() => {
  if (configError.value) return '';
  const p = viewerParams.value;
  return buildResourceDownloadUrl(siteUrl.value, {
    source: p.source,
    sid: p.sid,
    idno: p.idno,
    resourceId: p.resourceId,
  });
});

watch(
  [viewerParams, configError, siteUrl],
  async () => {
    if (configError.value) {
      displayTitle.value = '';
      return;
    }

    const p = viewerParams.value;
    const title = await resolvePdfViewerTitle({
      siteUrl: siteUrl.value,
      sid: p.sid,
      idno: p.idno,
      resourceId: p.resourceId,
    });

    displayTitle.value = title;
    if (title && typeof document !== 'undefined') {
      document.title = title;
    }
  },
  { immediate: true }
);
</script>

<style>
html, body, #pdf-viewer-app {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
}

.pdf-viewer-app {
  background: #525659 !important;
}

/* Embedded in catalog search iframe: constrain Vuetify shell so inner scroll works */
.pdf-viewer-app--embedded {
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.pdf-viewer-app--embedded .v-application__wrap {
  min-height: 0 !important;
  height: 100% !important;
  max-height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
</style>
