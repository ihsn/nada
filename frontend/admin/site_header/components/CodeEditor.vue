<template>
  <div ref="host" class="sh-code-editor" :style="{ '--sh-editor-min': minHeight }" />
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';

defineOptions({ name: 'ShCodeEditor' });

const props = defineProps({
  modelValue: { type: String, default: '' },
  language: { type: String, default: 'html' },
  minHeight: { type: String, default: '260px' },
  readonly: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const host = ref(null);
let view = null;

onMounted(() => {
  view = new EditorView({
    doc: props.modelValue || '',
    parent: host.value,
    extensions: [
      basicSetup,
      props.language === 'css' ? css() : html(),
      EditorView.lineWrapping,
      EditorView.editable.of(!props.readonly),
      EditorView.updateListener.of((update) => {
        if (update.docChanged) {
          emit('update:modelValue', update.state.doc.toString());
        }
      }),
      EditorView.theme({
        '&': { fontSize: '13px', backgroundColor: 'rgb(var(--v-theme-surface))' },
        '.cm-scroller': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', minHeight: 'var(--sh-editor-min)', maxHeight: '560px', overflow: 'auto' },
        '&.cm-focused': { outline: 'none' },
      }),
    ],
  });
});

watch(
  () => props.modelValue,
  (value) => {
    if (!view) return;
    const current = view.state.doc.toString();
    if ((value || '') !== current) {
      view.dispatch({ changes: { from: 0, to: current.length, insert: value || '' } });
    }
  }
);

onBeforeUnmount(() => {
  view?.destroy();
  view = null;
});

/** Insert text at the cursor (replaces the selection) */
function insert(text) {
  if (!view) return;
  const { from, to } = view.state.selection.main;
  view.dispatch({
    changes: { from, to, insert: text },
    selection: { anchor: from + text.length },
    scrollIntoView: true,
  });
  view.focus();
}

defineExpose({ insert });
</script>

<style scoped>
.sh-code-editor {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  overflow: hidden;
}
.sh-code-editor:focus-within {
  border-color: rgb(var(--v-theme-primary));
  box-shadow: 0 0 0 1px rgb(var(--v-theme-primary));
}
</style>
