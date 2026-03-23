<template>
  <v-form ref="formRef">

    <div class="field-group" v-if="!idDisabled">
      <label class="field-label">Collection ID <span class="required">*</span></label>
      <v-text-field
        v-model="form.repositoryid"
        variant="outlined"
        density="compact"
        hide-details="auto"
        :rules="[v => !!v || 'Collection ID is required']"
      />
    </div>

    <div class="field-group">
      <label class="field-label">Title <span class="required">*</span></label>
      <v-text-field
        v-model="form.title"
        variant="outlined"
        density="compact"
        hide-details="auto"
        :rules="[v => !!v || 'Title is required']"
      />
    </div>

    <div class="field-group">
      <label class="field-label">Short description</label>
      <v-textarea
        v-model="form.short_text"
        variant="outlined"
        density="compact"
        hide-details="auto"
        rows="3"
      />
    </div>

    <div class="field-group">
      <label class="field-label">Long description <span class="hint">(HTML allowed)</span></label>
      <div class="codemirror-wrap">
        <div ref="editorEl" />
      </div>
      <div class="token-hint mt-1">
        <a class="token-toggle" @click.prevent="showTokens = !showTokens">Available tokens</a>
        <div v-if="showTokens" class="token-list mt-1">
          <code>[site-base-url]</code> — Website URL<br>
          <code>[search-box:repositoryID]</code> — Search box for collection<br>
          <code>[latest-entries:repositoryID]</code> — Latest entries for collection<br>
          <code>[counts-by-type:repositoryID]</code> — Counts per data type<br>
          <code>[cards-featured-entries:repositoryID]</code> — Featured entries cards
        </div>
      </div>
    </div>

    <div class="field-group">
      <label class="field-label">Thumbnail</label>
      <div class="thumbnail-row">
        <div class="thumbnail-preview" v-if="thumbnailPreviewUrl">
          <img :src="thumbnailPreviewUrl" alt="Thumbnail preview" />
        </div>
        <div class="thumbnail-preview thumbnail-placeholder" v-else>
          <v-icon icon="mdi-image-outline" size="40" color="grey-lighten-1" />
        </div>
        <div class="thumbnail-inputs">
          <label class="field-label-sm">Current path</label>
          <v-text-field
            v-model="form.thumbnail"
            variant="outlined"
            density="compact"
            hide-details="auto"
            placeholder="files/images/example.png"
            class="mb-2"
          />
          <label class="field-label-sm">Upload new file (gif, jpg, png — max 300KB)</label>
          <input type="file" accept=".gif,.jpg,.jpeg,.png" class="file-input" @change="onFileChange" />
        </div>
      </div>
    </div>

    <v-row dense class="mt-1">
      <v-col cols="12" sm="4">
        <div class="field-group">
          <label class="field-label">Section</label>
          <v-select
            v-model="form.section"
            :items="sectionItems"
            item-title="label"
            item-value="value"
            variant="outlined"
            density="compact"
            hide-details="auto"
          />
        </div>
      </v-col>
      <v-col cols="12" sm="3">
        <div class="field-group">
          <label class="field-label">Weight</label>
          <v-text-field
            v-model.number="form.weight"
            type="number"
            variant="outlined"
            density="compact"
            hide-details="auto"
          />
        </div>
      </v-col>
      <v-col cols="12" sm="3" class="d-flex align-end pb-1">
        <div class="field-group mb-0">
          <label class="field-label">Published</label>
          <v-switch
            v-model="form.ispublished"
            :true-value="1"
            :false-value="0"
            color="success"
            density="compact"
            hide-details
          />
        </div>
      </v-col>
    </v-row>

  </v-form>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { useAppConfig } from '@/shared/composables/useAppConfig';

const props = defineProps({
  form: { type: Object, required: true },
  sections: { type: Array, default: () => [] },
  idDisabled: { type: Boolean, default: false },
});

const formRef = ref(null);
const editorEl = ref(null);
const showTokens = ref(false);
let cmView = null;

const { baseUrl } = useAppConfig();

// Section items for dropdown: [{label, value}]
const sectionItems = computed(() => [
  { label: 'None', value: 0 },
  ...props.sections.map(s => ({ label: s.title, value: s.id })),
]);

// Thumbnail preview — file takes priority over stored path
const thumbnailPreviewUrl = computed(() => {
  if (props.form.thumbnailFile instanceof File) {
    return URL.createObjectURL(props.form.thumbnailFile);
  }
  if (props.form.thumbnail) {
    return baseUrl.value + props.form.thumbnail;
  }
  return null;
});

function onFileChange(e) {
  const file = e.target.files?.[0];
  if (file) props.form.thumbnailFile = file;
}

// Expose validate() to parent pages
async function validate() {
  return formRef.value?.validate();
}
defineExpose({ validate });

// CodeMirror setup
onMounted(async () => {
  await nextTick();
  cmView = new EditorView({
    doc: props.form.long_text || '',
    extensions: [
      basicSetup,
      html(),
      EditorView.updateListener.of(update => {
        if (update.docChanged) {
          props.form.long_text = update.state.doc.toString();
        }
      }),
      EditorView.theme({
        '&': { border: '1px solid #bdbdbd', borderRadius: '4px', fontSize: '13px' },
        '.cm-scroller': { fontFamily: 'monospace', minHeight: '250px', maxHeight: '500px', overflow: 'auto' },
        '&.cm-focused': { outline: '2px solid #1976d2', outlineOffset: '-1px' },
      }),
    ],
    parent: editorEl.value,
  });
});

// Sync external form.long_text changes into editor (e.g. when edit page loads data)
watch(() => props.form.long_text, (val) => {
  if (!cmView) return;
  const current = cmView.state.doc.toString();
  if (val !== current) {
    cmView.dispatch({
      changes: { from: 0, to: current.length, insert: val || '' },
    });
  }
});

onBeforeUnmount(() => {
  cmView?.destroy();
});
</script>

<style scoped>
.field-group {
  margin-bottom: 16px;
}
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0,0,0,0.7);
  margin-bottom: 4px;
}
.field-label-sm {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  color: rgba(0,0,0,0.6);
  margin-bottom: 4px;
}
.required {
  color: #d32f2f;
  margin-left: 2px;
}
.hint {
  font-weight: 400;
  color: rgba(0,0,0,0.45);
  font-size: 0.75rem;
}
.codemirror-wrap {
  border-radius: 4px;
  overflow: hidden;
}
.token-hint {
  font-size: 0.78rem;
  color: rgba(0,0,0,0.55);
}
.token-toggle {
  cursor: pointer;
  color: #1976d2;
  text-decoration: none;
}
.token-toggle:hover {
  text-decoration: underline;
}
.token-list {
  background: #f5f5f5;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 8px 12px;
  line-height: 1.9;
  font-size: 0.78rem;
}
.token-list code {
  background: #eeeeee;
  padding: 1px 4px;
  border-radius: 3px;
  font-size: 0.78rem;
}
.thumbnail-row {
  display: flex;
  gap: 16px;
  align-items: flex-start;
}
.thumbnail-preview {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fafafa;
}
.thumbnail-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.thumbnail-inputs {
  flex: 1;
}
.file-input {
  font-size: 0.85rem;
  color: rgba(0,0,0,0.7);
}
</style>
