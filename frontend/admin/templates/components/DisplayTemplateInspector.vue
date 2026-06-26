<template>
  <div class="dt-inspector">
    <template v-if="selectionKind === 'template_root'">
      <div class="text-h6 font-weight-semibold mb-4 d-flex align-center flex-wrap ga-2">
        <v-icon color="primary" size="small">mdi-file-document-edit-outline</v-icon>
        Template
      </div>
      <v-alert type="info" variant="tonal" density="comfortable" class="mb-6 text-body-2">
        This node represents the whole display template. Open <strong>Description</strong> in the tree to edit the
        catalogue record and layout summary. Use the structure under Template to edit sections and fields.
      </v-alert>
      <v-row dense>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">UID</div>
            <v-text-field :model-value="model?.uid || ''" variant="outlined" density="comfortable" hide-details readonly />
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">Name</div>
            <v-text-field :model-value="form.name || ''" variant="outlined" density="comfortable" hide-details readonly />
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">Data type</div>
            <v-text-field :model-value="form.data_type || ''" variant="outlined" density="comfortable" hide-details readonly />
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">Status</div>
            <v-text-field :model-value="form.status || ''" variant="outlined" density="comfortable" hide-details readonly />
          </div>
        </v-col>
      </v-row>
    </template>

    <template v-else-if="selectionKind === 'description'">
      <div class="text-h6 font-weight-semibold mb-4 d-flex align-center flex-wrap ga-2">
        <v-icon color="primary" size="small">mdi-ballot-outline</v-icon>
        Description
      </div>

      <div class="text-subtitle-2 font-weight-semibold mb-3 text-medium-emphasis">Catalogue record</div>
      <v-row dense>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">Name</div>
            <v-text-field v-model="form.name" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
        <v-col cols="12" md="3">
          <div class="dt-fld">
            <div class="dt-fld-label">Status</div>
            <v-select
              v-model="form.status"
              :items="['draft', 'published', 'archived']"
              variant="outlined"
              density="comfortable"
              hide-details
              @update:model-value="$emit('dirty')"
            />
          </div>
        </v-col>
        <v-col cols="12" md="3">
          <div class="dt-fld">
            <div class="dt-fld-label">Data type</div>
            <v-text-field v-model="form.data_type" variant="outlined" density="comfortable" hide-details readonly />
          </div>
        </v-col>
        <v-col cols="12" md="3">
          <div class="dt-fld">
            <div class="dt-fld-label">Version</div>
            <v-text-field v-model="form.version" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
        <v-col cols="12" md="3">
          <div class="dt-fld">
            <div class="dt-fld-label">Template type</div>
            <v-text-field v-model="form.template_type" variant="outlined" density="comfortable" hide-details readonly />
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">Organization</div>
            <v-text-field v-model="form.organization" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
        <v-col cols="12" md="6">
          <div class="dt-fld">
            <div class="dt-fld-label">Author</div>
            <v-text-field v-model="form.author" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
        <v-col cols="12">
          <div class="dt-fld">
            <div class="dt-fld-label">Description</div>
            <v-textarea v-model="form.description" variant="outlined" rows="3" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
      </v-row>

      <v-divider class="my-6" />

      <div class="text-subtitle-2 font-weight-semibold mb-3 text-medium-emphasis">Display layout (stored in template_json)</div>
      <v-row dense>
        <v-col cols="12">
          <div class="dt-fld">
            <div class="dt-fld-label">Layout title</div>
            <v-text-field v-model="displayRoot.title" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
        <v-col cols="12">
          <div class="dt-fld">
            <div class="dt-fld-label">Layout description</div>
            <v-textarea v-model="layoutDescription" variant="outlined" rows="2" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
      </v-row>
    </template>

    <template v-else-if="selectionKind === 'node' && mode === 'section_like' && node">
      <div class="text-h6 font-weight-semibold mb-4">Section</div>
      <div class="d-flex align-center flex-wrap ga-2 mb-4">
        <v-chip size="small" variant="tonal">{{ node.node_type }}</v-chip>
        <v-icon size="small" color="medium-emphasis">{{ nodeIcon(node) }}</v-icon>
      </div>
      <div class="dt-fld">
        <div class="dt-fld-label">Key</div>
        <v-text-field v-model="node.key" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
      </div>
      <div class="dt-fld">
        <div class="dt-fld-label">Title / label</div>
        <v-text-field v-model="node.title" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
      </div>
    </template>

    <template v-else-if="selectionKind === 'node' && mode === 'field' && node">
      <div class="text-h6 font-weight-semibold mb-4 d-flex align-center flex-wrap ga-2">
        Field
        <v-chip v-if="node.is_prop" size="small" color="secondary" variant="tonal">Repeating column</v-chip>
      </div>
      <div class="dt-fld">
        <div class="dt-fld-label">Key</div>
        <v-text-field v-model="node.key" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
      </div>
      <div class="dt-fld">
        <div class="dt-fld-label">Title / label override</div>
        <v-text-field v-model="node.title" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
      </div>
      <div class="dt-fld">
        <div class="dt-fld-label">Source type</div>
        <v-text-field :model-value="node.source_type || ''" variant="outlined" density="comfortable" hide-details readonly />
      </div>
      <div class="dt-fld">
        <div class="dt-fld-label">Path</div>
        <v-text-field :model-value="node.path ?? ''" variant="outlined" density="comfortable" hide-details readonly />
      </div>

      <div class="dt-fld">
        <div class="dt-fld-label">Renderer</div>
        <v-select
          v-model="node.renderer"
          :items="compatibleRenderers"
          item-title="label"
          item-value="key"
          variant="outlined"
          density="comfortable"
          hide-details
          clearable
          @update:model-value="$emit('dirty')"
        />
      </div>

      <div class="dt-fld">
        <div class="dt-fld-label">Renderer options (JSON)</div>
        <v-textarea
          v-model="rendererOptionsJson"
          variant="outlined"
          rows="8"
          hide-details
          class="font-monospace text-body-2"
          autocomplete="off"
          spellcheck="false"
        />
      </div>
      <v-alert v-if="rendererOptionsErr" type="warning" density="compact" variant="tonal" class="text-body-2">
        {{ rendererOptionsErr }}
      </v-alert>

      <div class="d-flex flex-wrap ga-2 mt-6">
        <v-btn color="error" variant="tonal" prepend-icon="mdi-delete-outline" @click="$emit('request-delete')">
          Remove from tree
        </v-btn>
      </div>
    </template>

    <template v-else>
      <div class="text-body-2 text-medium-emphasis pa-4">Select <strong>Description</strong> or a node in the tree.</div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { nodeIcon } from '../utils/displayTemplateTree';

defineOptions({ name: 'DisplayTemplateInspector' });

const props = defineProps({
  selectionKind: { type: String, default: 'template_root' },
  displayRoot: { type: Object, required: true },
  form: { type: Object, required: true },
  model: { type: Object, default: null },
  selectedNode: { type: Object, default: null },
  renderers: { type: Array, default: () => [] },
});

defineEmits(['dirty', 'request-delete']);

const node = computed(() => props.selectedNode);

const layoutDescription = computed({
  get() {
    return props.displayRoot.description != null ? String(props.displayRoot.description) : '';
  },
  set(v) {
    props.displayRoot.description = v;
  },
});

const mode = computed(() => {
  if (props.selectionKind !== 'node' || !node.value) {
    return 'empty';
  }
  return node.value.node_type === 'field' ? 'field' : 'section_like';
});

const compatibleRenderers = computed(() => {
  const st = node.value?.source_type;
  if (!st || !props.renderers?.length) {
    return props.renderers;
  }
  return props.renderers.filter(
    (r) => Array.isArray(r.supported_source_types) && r.supported_source_types.includes(st)
  );
});

const rendererOptionsJson = ref('{}');
const rendererOptionsErr = ref('');
const suppressOptionsWriteback = ref(false);

function syncRendererOptionsFromNode() {
  const n = node.value;
  if (!n || n.node_type !== 'field') {
    return;
  }
  try {
    const o =
      n.renderer_options && typeof n.renderer_options === 'object' && !Array.isArray(n.renderer_options)
        ? n.renderer_options
        : {};
    suppressOptionsWriteback.value = true;
    rendererOptionsJson.value = JSON.stringify(o, null, 2);
    rendererOptionsErr.value = '';
  } catch {
    suppressOptionsWriteback.value = true;
    rendererOptionsJson.value = '{}';
  }
}

watch(
  () => props.selectedNode?._tid,
  () => syncRendererOptionsFromNode(),
  { immediate: true }
);

watch(rendererOptionsJson, (text) => {
  if (suppressOptionsWriteback.value) {
    suppressOptionsWriteback.value = false;
    return;
  }
  const n = node.value;
  if (!n || n.node_type !== 'field') {
    return;
  }
  try {
    const parsed = JSON.parse(text || '{}');
    if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
      throw new Error('Options must be a JSON object');
    }
    n.renderer_options = parsed;
    rendererOptionsErr.value = '';
  } catch (e) {
    rendererOptionsErr.value = /** @type {Error} */ (e).message || 'Invalid JSON';
  }
});
</script>

<style scoped>
.dt-fld-label {
  font-size: 0.75rem;
  line-height: 1.25;
  color: rgba(var(--v-theme-on-surface), 0.65);
  margin-bottom: 6px;
  font-weight: 500;
}
.dt-fld {
  margin-bottom: 16px;
}
.font-monospace :deep(textarea) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
  font-size: 0.8rem;
  line-height: 1.4;
}
</style>
