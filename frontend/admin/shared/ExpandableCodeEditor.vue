<template>
  <div class="expandable-code-editor">
    <div class="d-flex align-center mb-1">
      <div v-if="label" class="text-caption text-medium-emphasis">{{ label }}</div>
      <v-spacer />
      <slot name="toolbar" :expanded="false" />
      <div
        v-if="limit != null"
        class="text-caption ml-2"
        :class="overLimit ? 'text-error' : 'text-medium-emphasis'"
      >
        {{ length }} / {{ limit }}
      </div>
      <v-btn
        v-if="!readonly"
        class="ml-1"
        size="x-small"
        variant="text"
        :title="formatLabel"
        :aria-label="formatLabel"
        :disabled="!canFormat"
        @click="format"
      >
        {{ formatLabel }}
      </v-btn>
      <v-btn
        v-if="!readonly"
        class="ml-1"
        icon="mdi-arrow-expand"
        size="x-small"
        variant="text"
        :title="expandLabel"
        :aria-label="expandLabel"
        @click="expanded = true"
      />
    </div>

    <sh-code-editor
      v-if="!expanded"
      ref="compactEditor"
      :model-value="modelValue"
      :language="language"
      :min-height="minHeight"
      :max-height="maxHeight"
      :readonly="readonly"
      @update:model-value="onInput"
    />
    <div v-else class="expandable-code-editor__placeholder text-caption text-medium-emphasis">
      {{ expandedHint }}
    </div>

    <v-dialog
      v-model="expanded"
      fullscreen
      scrim="true"
      transition="dialog-bottom-transition"
    >
      <v-card class="expandable-code-editor__card d-flex flex-column">
        <v-toolbar density="compact" color="surface" class="expandable-code-editor__toolbar flex-grow-0">
          <v-toolbar-title class="text-subtitle-1">{{ label || expandLabel }}</v-toolbar-title>
          <v-spacer />
          <div
            v-if="limit != null"
            class="text-caption mr-2"
            :class="overLimit ? 'text-error' : 'text-medium-emphasis'"
          >
            {{ length }} / {{ limit }}
          </div>
          <slot name="toolbar" :expanded="true" />
          <v-btn
            v-if="!readonly"
            class="mr-1"
            size="small"
            variant="text"
            :title="formatLabel"
            :aria-label="formatLabel"
            :disabled="!canFormat"
            @click="format"
          >
            {{ formatLabel }}
          </v-btn>
          <v-btn
            icon="mdi-close"
            variant="text"
            :title="doneLabel"
            :aria-label="doneLabel"
            @click="expanded = false"
          />
        </v-toolbar>
        <v-card-text class="expandable-code-editor__body pa-3 flex-grow-1 d-flex flex-column">
          <sh-code-editor
            v-if="expanded"
            ref="expandedEditor"
            :model-value="modelValue"
            :language="language"
            fill
            min-height="100%"
            max-height="none"
            :readonly="readonly"
            @update:model-value="onInput"
          />
        </v-card-text>
        <v-card-actions class="expandable-code-editor__actions flex-grow-0 px-4 py-2">
          <v-spacer />
          <v-btn color="primary" variant="flat" @click="expanded = false">
            {{ doneLabel }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import beautify from 'js-beautify';
import ShCodeEditor from '../site_header/components/CodeEditor.vue';

const beautifyHtml = beautify.html;
const beautifyCss = beautify.css;

defineOptions({ name: 'ExpandableCodeEditor' });

const props = defineProps({
  modelValue: { type: String, default: '' },
  language: { type: String, default: 'html' },
  label: { type: String, default: '' },
  limit: { type: [Number, String], default: null },
  minHeight: { type: String, default: '260px' },
  maxHeight: { type: String, default: '560px' },
  readonly: { type: Boolean, default: false },
  expandLabel: { type: String, default: 'Expand editor' },
  formatLabel: { type: String, default: 'Format' },
  doneLabel: { type: String, default: 'Done' },
  expandedHint: { type: String, default: 'Editing in the expanded window…' },
});

const emit = defineEmits(['update:modelValue']);

const expanded = ref(false);
const compactEditor = ref(null);
const expandedEditor = ref(null);

const length = computed(() => (props.modelValue || '').length);
const canFormat = computed(() => !props.readonly && length.value > 0);
const overLimit = computed(() => {
  if (props.limit == null || props.limit === '') return false;
  const lim = Number(props.limit);
  return Number.isFinite(lim) && length.value > lim;
});

function onInput(value) {
  emit('update:modelValue', value);
}

function format() {
  if (props.readonly) return;
  const raw = props.modelValue || '';
  if (!raw.trim()) return;

  let formatted;
  try {
    if (props.language === 'css') {
      formatted = beautifyCss(raw, {
        indent_size: 2,
        end_with_newline: true,
      });
    } else {
      formatted = beautifyHtml(raw, {
        indent_size: 2,
        wrap_line_length: 0,
        preserve_newlines: true,
        max_preserve_newlines: 2,
        content_unformatted: ['pre', 'code', 'textarea', 'script', 'style'],
        extra_liners: [],
        end_with_newline: true,
      });
    }
  } catch {
    return;
  }

  if (formatted !== raw) {
    emit('update:modelValue', formatted);
  }
}

function insert(text) {
  if (expanded.value) {
    expandedEditor.value?.insert(text);
  } else {
    compactEditor.value?.insert(text);
  }
}

defineExpose({ insert, format, expanded });
</script>

<style scoped>
.expandable-code-editor__placeholder {
  min-height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 6px;
  background: rgba(var(--v-theme-on-surface), 0.02);
}
.expandable-code-editor__card {
  height: 100%;
}
.expandable-code-editor__toolbar {
  border-bottom: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.expandable-code-editor__actions {
  border-top: thin solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.expandable-code-editor__body {
  min-height: 0;
  overflow: hidden;
}
</style>
