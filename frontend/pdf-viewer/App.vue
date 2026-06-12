<template>
  <v-app class="pdf-viewer-app">
    <v-alert v-if="configError" type="error" class="ma-4">{{ configError }}</v-alert>
    <PdfViewerCore
      v-else
      :stream-url="streamUrl"
      :initial-page="viewerParams.page"
      :page-chips="viewerParams.pageChips"
      :title="displayTitle"
    />
  </v-app>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import PdfViewerCore from '@/shared/pdf-viewer/PdfViewerCore.vue';
import { parseViewerParams, validateViewerParams } from '@/shared/pdf-viewer/pdfViewerParams';
import { buildPdfStreamUrl } from '@/shared/pdf-viewer/pdfStreamUrl';
import { resolvePdfViewerTitle } from '@/shared/pdf-viewer/resolvePdfViewerTitle';

defineOptions({ name: 'PdfViewerApp' });

const { siteUrl } = useAppConfig();
const displayTitle = ref('');

const viewerParams = computed(() =>
  parseViewerParams(typeof window !== 'undefined' ? window.location.search : '')
);

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
</style>
