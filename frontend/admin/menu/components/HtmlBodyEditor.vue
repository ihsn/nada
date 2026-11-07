<template>
  <div class="html-body-editor">
    <div class="d-flex align-center justify-space-between mb-2">
      <label class="field-label mb-0">{{ label }}</label>
      <v-btn
        v-if="useWysiwyg"
        size="small"
        variant="outlined"
        @click="toggleMode"
      >
        {{ mode === 'visual' ? 'Switch to source' : 'Switch to visual' }}
      </v-btn>
    </div>

    <template v-if="useWysiwyg && mode === 'visual'">
      <div v-if="editor" class="tiptap-toolbar d-flex flex-wrap ga-1 pa-2">
        <v-btn size="x-small" variant="text" :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">H1</v-btn>
        <v-btn size="x-small" variant="text" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</v-btn>
        <v-btn size="x-small" variant="text" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">H3</v-btn>
        <v-divider vertical class="mx-1" />
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()">
          <v-icon size="16">mdi-format-bold</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()">
          <v-icon size="16">mdi-format-italic</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()">
          <v-icon size="16">mdi-format-underline</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('strike') }" @click="editor.chain().focus().toggleStrike().run()">
          <v-icon size="16">mdi-format-strikethrough</v-icon>
        </v-btn>
        <v-divider vertical class="mx-1" />
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">
          <v-icon size="16">mdi-format-list-bulleted</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()">
          <v-icon size="16">mdi-format-list-numbered</v-icon>
        </v-btn>
        <v-divider vertical class="mx-1" />
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive({ textAlign: 'left' }) }" @click="editor.chain().focus().setTextAlign('left').run()">
          <v-icon size="16">mdi-format-align-left</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive({ textAlign: 'center' }) }" @click="editor.chain().focus().setTextAlign('center').run()">
          <v-icon size="16">mdi-format-align-center</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive({ textAlign: 'right' }) }" @click="editor.chain().focus().setTextAlign('right').run()">
          <v-icon size="16">mdi-format-align-right</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive({ textAlign: 'justify' }) }" @click="editor.chain().focus().setTextAlign('justify').run()">
          <v-icon size="16">mdi-format-align-justify</v-icon>
        </v-btn>
        <v-divider vertical class="mx-1" />
        <v-btn icon size="x-small" variant="text" :class="{ 'is-active': editor.isActive('link') }" @click="setLink">
          <v-icon size="16">mdi-link</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" @click="imageDialog = true">
          <v-icon size="16">mdi-image</v-icon>
        </v-btn>
        <v-btn icon size="x-small" variant="text" @click="editor.chain().focus().unsetAllMarks().clearNodes().run()">
          <v-icon size="16">mdi-format-clear</v-icon>
        </v-btn>
      </div>
      <editor-content :editor="editor" class="tiptap-content" />
    </template>

    <div v-show="!useWysiwyg || mode === 'source'" ref="cmEl" class="codemirror-wrap" />

    <FilestoreImageDialog v-model="imageDialog" @select="insertImage" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick, inject } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';
import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import DOMPurify from 'dompurify';
import { APP_CONFIG_KEY } from '@/shared/composables/useAppConfig';
import FilestoreImageDialog from './FilestoreImageDialog.vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: 'Body' },
});
const emit = defineEmits(['update:modelValue']);

const config = inject(APP_CONFIG_KEY, window.APP_CONFIG || {});
const useWysiwyg = computed(() => String(config.useHtmlEditor || 'yes') !== 'no');

const mode = ref('visual');
const imageDialog = ref(false);
const cmEl = ref(null);
let cmView = null;
let syncing = false;

function sanitize(htmlStr) {
  return DOMPurify.sanitize(htmlStr || '', {
    ADD_ATTR: ['target', 'rel', 'style'],
  });
}

function emitValue(htmlStr) {
  emit('update:modelValue', htmlStr || '');
}

const editor = useEditor({
  content: props.modelValue || '',
  extensions: [
    StarterKit,
    Underline,
    Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } }),
    Image.configure({ allowBase64: false }),
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
  ],
  // Keep Enter inside the editor (new paragraph / Shift+Enter hard break).
  // Otherwise the parent <v-form> treats Enter as submit.
  editorProps: {
    handleDOMEvents: {
      keydown: (_view, event) => {
        if (event.key === 'Enter') {
          event.stopPropagation();
        }
        return false;
      },
    },
  },
  onUpdate: ({ editor: ed }) => {
    if (syncing || mode.value !== 'visual') return;
    emitValue(ed.getHTML());
  },
});

function ensureCodeMirror() {
  if (cmView || !cmEl.value) return;
  cmView = new EditorView({
    doc: props.modelValue || '',
    extensions: [
      basicSetup,
      html(),
      EditorView.updateListener.of((update) => {
        if (!update.docChanged || syncing || (useWysiwyg.value && mode.value !== 'source')) return;
        emitValue(update.state.doc.toString());
      }),
      EditorView.theme({
        '&': { border: '1px solid rgba(0,0,0,0.2)', borderRadius: '4px', fontSize: '13px' },
        '.cm-scroller': { fontFamily: 'monospace', minHeight: '280px', maxHeight: '520px', overflow: 'auto' },
        '&.cm-focused': { outline: '2px solid rgb(var(--v-theme-primary))', outlineOffset: '-1px' },
      }),
    ],
    parent: cmEl.value,
  });
}

function setCmContent(val) {
  if (!cmView) return;
  const current = cmView.state.doc.toString();
  if (current === (val || '')) return;
  cmView.dispatch({ changes: { from: 0, to: current.length, insert: val || '' } });
}

function toggleMode() {
  if (!useWysiwyg.value) return;
  syncing = true;
  if (mode.value === 'visual') {
    const htmlStr = editor.value?.getHTML() || '';
    emitValue(htmlStr);
    mode.value = 'source';
    nextTick(() => {
      ensureCodeMirror();
      setCmContent(htmlStr);
      syncing = false;
    });
  } else {
    const htmlStr = cmView ? cmView.state.doc.toString() : props.modelValue || '';
    emitValue(htmlStr);
    mode.value = 'visual';
    nextTick(() => {
      editor.value?.commands.setContent(htmlStr || '', false);
      syncing = false;
    });
  }
}

function setLink() {
  if (!editor.value) return;
  const prev = editor.value.getAttributes('link').href || '';
  const url = window.prompt('URL', prev);
  if (url === null) return;
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    return;
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function insertImage(url) {
  if (!url || !editor.value) return;
  editor.value.chain().focus().setImage({ src: url }).run();
}

watch(
  () => props.modelValue,
  (val) => {
    if (syncing) return;
    const next = val || '';
    if (mode.value === 'visual' && editor.value) {
      const current = editor.value.getHTML();
      if (current !== next) {
        syncing = true;
        editor.value.commands.setContent(next, false);
        syncing = false;
      }
    } else if (cmView) {
      setCmContent(next);
    }
  }
);

onMounted(async () => {
  await nextTick();
  if (!useWysiwyg.value) {
    mode.value = 'source';
    ensureCodeMirror();
    setCmContent(props.modelValue || '');
  } else if (editor.value && props.modelValue) {
    editor.value.commands.setContent(props.modelValue, false);
  }
});

onBeforeUnmount(() => {
  cmView?.destroy();
  cmView = null;
  editor.value?.destroy();
});

/** Sanitize HTML before parent submits (optional helper). */
function getSanitizedHtml() {
  const raw = mode.value === 'source' && cmView
    ? cmView.state.doc.toString()
    : (editor.value?.getHTML() || props.modelValue || '');
  return sanitize(raw);
}
defineExpose({ getSanitizedHtml });
</script>

<style scoped>
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.7);
  margin-bottom: 4px;
}
.tiptap-toolbar {
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-bottom: none;
  border-radius: 4px 4px 0 0;
  background: #fafafa;
}
.tiptap-toolbar .is-active {
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
}
.tiptap-content {
  border: 1px solid rgba(0, 0, 0, 0.2);
  border-radius: 0 0 4px 4px;
  min-height: 280px;
  background: #fff;
}
.tiptap-content :deep(.tiptap),
.tiptap-content :deep(.ProseMirror) {
  min-height: 280px;
  padding: 12px 14px;
  outline: none;
}
.tiptap-content :deep(.ProseMirror img) {
  max-width: 100%;
  height: auto;
}
.codemirror-wrap {
  min-height: 280px;
}
</style>
