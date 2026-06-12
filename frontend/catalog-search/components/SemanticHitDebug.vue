<template>
  <div v-if="hit" class="semantic-hit-debug" @click.stop>
    <details>
      <summary class="semantic-hit-debug-summary">
        Semantic hit
        <span v-if="score != null" class="semantic-hit-debug-score">— score {{ score }}</span>
        <span v-if="qfield" class="semantic-hit-debug-qfield">({{ qfield }})</span>
      </summary>
      <pre class="semantic-hit-debug-json">{{ formatted }}</pre>
    </details>
  </div>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'SemanticHitDebug' });

const props = defineProps({
  hit: { type: Object, default: null },
});

const score = computed(() => {
  const raw = props.hit?._score;
  return raw == null ? null : Number(raw).toFixed(4);
});

const qfield = computed(() => props.hit?._source?.metadata?.qfield ?? null);

const formatted = computed(() =>
  props.hit ? JSON.stringify(props.hit, null, 2) : ''
);
</script>

<style scoped>
.semantic-hit-debug {
  margin-top: -8px;
  margin-bottom: 12px;
  padding: 8px 10px;
  background: #1e1e1e;
  color: #d4d4d4;
  border-radius: 0 0 10px 10px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-top: none;
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: 11px;
  line-height: 1.4;
}

.semantic-hit-debug-summary {
  cursor: pointer;
  color: #ce9178;
  list-style-position: inside;
}

.semantic-hit-debug-score {
  color: #9cdcfe;
  font-weight: normal;
}

.semantic-hit-debug-qfield {
  color: #6a9955;
  font-weight: normal;
}

.semantic-hit-debug-json {
  margin: 8px 0 0;
  white-space: pre-wrap;
  word-break: break-all;
  color: #d4d4d4;
  max-height: 320px;
  overflow: auto;
}
</style>
