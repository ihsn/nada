<template>
  <div class="semantic-search-debug">
    <div class="semantic-search-debug-header">
      Semantic Search Debug
      <span v-if="elapsed != null" class="semantic-search-debug-elapsed">{{ elapsed }}s</span>
    </div>

    <details open>
      <summary class="semantic-search-debug-summary">API Request</summary>
      <pre class="semantic-search-debug-pre">{{ requestJson }}</pre>
    </details>

    <details open class="semantic-search-debug-section">
      <summary class="semantic-search-debug-summary">API Response</summary>
      <pre class="semantic-search-debug-pre">{{ responseJson }}</pre>
    </details>

    <details class="semantic-search-debug-section">
      <summary class="semantic-search-debug-summary">
        Matched IDNOs ({{ idnoCount }} from API, {{ dbRowsFound }} found in DB)
      </summary>
      <pre class="semantic-search-debug-pre">{{ idnoList }}</pre>
    </details>
  </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'SemanticSearchDebug' });

const props = defineProps({
  debug: { type: Object, required: true },
});

function formatJson(value) {
  if (value == null) return '';
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return String(value);
  }
}

const elapsed = computed(() => {
  const raw = props.debug?.elapsed;
  if (raw == null) return null;
  return Number(raw).toFixed(3);
});

const requestJson = computed(() => formatJson(props.debug?.request));
const responseJson = computed(() => formatJson(props.debug?.response));

const idnoCount = computed(() => {
  const idnos = props.debug?.idnos;
  return Array.isArray(idnos) ? idnos.length : 0;
});

const dbRowsFound = computed(() => Number(props.debug?.db_rows_found ?? 0));

const idnoList = computed(() => {
  const idnos = props.debug?.idnos;
  if (!Array.isArray(idnos) || !idnos.length) return '';
  return idnos.join('\n');
});
</script>

<style scoped>
.semantic-search-debug {
  padding: 16px;
  background: #1e1e1e;
  color: #d4d4d4;
  border-radius: 6px;
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: 12px;
  line-height: 1.5;
}

.semantic-search-debug-header {
  color: #9cdcfe;
  font-weight: bold;
  margin-bottom: 8px;
}

.semantic-search-debug-elapsed {
  color: #6a9955;
  font-weight: normal;
  margin-left: 12px;
}

.semantic-search-debug-section {
  margin-top: 12px;
}

.semantic-search-debug-summary {
  cursor: pointer;
  color: #ce9178;
  margin-bottom: 4px;
  list-style-position: inside;
}

.semantic-search-debug-pre {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-all;
  color: #d4d4d4;
  max-height: 480px;
  overflow: auto;
}
</style>
