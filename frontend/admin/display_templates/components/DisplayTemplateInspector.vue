<template>
  <div class="dt-inspector">
    <template v-if="selectionKind === 'template_root'">
      <div class="text-h6 font-weight-semibold d-flex align-center flex-wrap ga-2 dt-readonly-heading">
        <v-icon color="primary" size="small">mdi-file-outline</v-icon>
        Template
      </div>
      <div class="dt-readonly-grid">
        <div class="dt-readonly-item">
          <div class="dt-fld-label">UID</div>
          <div class="dt-readonly-value">{{ displayValue(model?.uid) }}</div>
        </div>
        <div class="dt-readonly-item">
          <div class="dt-fld-label">Name</div>
          <div class="dt-readonly-value">{{ displayValue(form.name) }}</div>
        </div>
        <div class="dt-readonly-item">
          <div class="dt-fld-label">Data type</div>
          <div class="dt-readonly-value">{{ displayValue(form.data_type) }}</div>
        </div>
        <div class="dt-readonly-item">
          <div class="dt-fld-label">Status</div>
          <div class="dt-readonly-value">{{ displayValue(form.status) }}</div>
        </div>
        <div v-if="coreFilePath" class="dt-readonly-item dt-readonly-item-span">
          <div class="dt-fld-label">File</div>
          <div class="dt-readonly-value">{{ coreFilePath }}</div>
        </div>
      </div>
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
        <v-col cols="12" md="3">
          <div class="dt-fld">
            <div class="dt-fld-label">Language</div>
            <v-text-field
              v-model="form.lang"
              variant="outlined"
              density="comfortable"
              hide-details
              :readonly="readonly"
              hint="Primary language of titles on the layout (ISO 639-1)."
              persistent-hint
              @update:model-value="$emit('dirty')"
            />
          </div>
        </v-col>
        <v-col v-if="coreFilePath" cols="12">
          <div class="dt-fld">
            <div class="dt-fld-label">File</div>
            <v-text-field
              :model-value="coreFilePath"
              variant="outlined"
              density="comfortable"
              hide-details
              readonly
              hint="JSON on disk (relative to application/). A matching custom/ override is used if present."
              persistent-hint
            />
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
            <div class="dt-fld-label">Note</div>
            <v-textarea v-model="form.description" variant="outlined" rows="3" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
      </v-row>

      <v-divider class="my-6" />

      <div class="text-subtitle-2 font-weight-semibold mb-3 text-medium-emphasis">Layout root (template_json)</div>
      <v-row dense>
        <v-col cols="12">
          <div class="dt-fld">
            <div class="dt-fld-label">Layout title</div>
            <v-text-field v-model="templateRoot.title" variant="outlined" density="comfortable" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </v-col>
      </v-row>
    </template>

    <template v-else-if="selectionKind === 'node' && mode === 'section_like' && node">
      <div class="text-h6 font-weight-semibold mb-4">Section</div>
      <div class="d-flex align-center flex-wrap ga-2 mb-4">
        <v-chip size="small" variant="tonal">{{ node.type }}</v-chip>
        <v-icon size="small" color="medium-emphasis">{{ nodeIcon(node) }}</v-icon>
      </div>
      <div class="dt-opt-list dt-opt-list--identity mb-4">
        <div class="dt-opt-row">
          <div class="dt-opt-label">Key</div>
          <div class="dt-opt-control">
            <v-text-field v-model="node.key" variant="outlined" density="compact" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </div>
        <div class="dt-opt-row">
          <div class="dt-opt-label">Title / label</div>
          <div class="dt-opt-control">
            <v-text-field v-model="node.title" variant="outlined" density="compact" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </div>
      </div>
      <v-card variant="outlined" rounded="lg" class="dt-display-options-card mt-4 mb-2">
        <v-card-title class="text-h6 font-weight-bold py-3 px-4">
          Display options
        </v-card-title>
        <v-card-text class="pt-2 px-4 pb-3">
          <div class="dt-opt-list">
            <div class="dt-opt-row">
              <div class="dt-opt-label">Hide field</div>
              <div class="dt-opt-control">
                <v-switch
                  :model-value="displayHidden"
                  color="primary"
                  density="compact"
                  hide-details
                  class="dt-opt-switch"
                  :disabled="readonly"
                  @update:model-value="onDisplayHiddenChange"
                />
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </template>

    <template v-else-if="selectionKind === 'node' && mode === 'field' && node">
      <div class="text-h6 font-weight-semibold mb-4 d-flex align-center flex-wrap ga-2">
        Field
        <v-chip v-if="isPropNode(node)" size="small" color="secondary" variant="tonal">Array column</v-chip>
        <v-chip v-if="isCustomLayoutField(node)" size="small" class="dt-custom-chip" variant="tonal">Custom</v-chip>
        <v-chip v-if="node.type === 'widget'" size="small" class="dt-widget-chip" variant="tonal">Widget</v-chip>
      </div>
      <div class="dt-opt-list dt-opt-list--identity mb-4">
        <div class="dt-opt-row">
          <div class="dt-opt-label">Key</div>
          <div class="dt-opt-control">
            <v-text-field
              v-model="node.key"
              variant="outlined"
              density="compact"
              hide-details
              :readonly="isPropNode(node) && !isCustomLayoutField(node)"
              @update:model-value="onFieldKeyChange"
            />
          </div>
        </div>
        <div v-if="isPropNode(node)" class="dt-opt-row">
          <div class="dt-opt-label">prop_key</div>
          <div class="dt-opt-control">
            <v-text-field :model-value="node.prop_key || ''" variant="outlined" density="compact" hide-details readonly />
          </div>
        </div>
        <div class="dt-opt-row">
          <div class="dt-opt-label">Title / label</div>
          <div class="dt-opt-control">
            <v-text-field v-model="node.title" variant="outlined" density="compact" hide-details @update:model-value="$emit('dirty')" />
          </div>
        </div>
        <div class="dt-opt-row">
          <div class="dt-opt-label">Type</div>
          <div class="dt-opt-control">
            <v-select
              v-if="!isPropNode(node) || isCustomLayoutField(node)"
              v-model="node.type"
              :items="FIELD_TYPES"
              variant="outlined"
              density="compact"
              hide-details
              @update:model-value="onLayoutTypeChange"
            />
            <v-text-field
              v-else
              :model-value="node.type || ''"
              variant="outlined"
              density="compact"
              hide-details
              readonly
            />
          </div>
        </div>
      </div>

      <v-alert
        v-if="arrayNeedsColumns"
        type="info"
        variant="tonal"
        density="compact"
        class="text-body-2 mb-4"
      >
        This array has no columns. Select the array and use the purple tree action to add a custom column.
      </v-alert>

      <v-card variant="outlined" rounded="lg" class="dt-display-options-card mt-4 mb-2">
        <v-card-title class="text-h6 font-weight-bold py-3 px-4">
          Display options
        </v-card-title>
        <v-card-text class="pt-2 px-4 pb-3">
          <div class="dt-opt-list">
            <div v-if="showDisplayValueMode" class="dt-opt-row">
              <div class="dt-opt-label">Display as</div>
              <div class="dt-opt-control">
                <v-select
                  :model-value="displayValueMode"
                  :items="displayValueModeItems"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="compact"
                  hide-details
                  :disabled="readonly"
                  @update:model-value="onDisplayValueModeChange"
                />
              </div>
            </div>

            <div v-if="showUriAsIconControls" class="dt-opt-row is-nested">
              <div class="dt-opt-label">Show as icon</div>
              <div class="dt-opt-control">
                <v-switch
                  :model-value="displayUriAsIcon"
                  color="primary"
                  density="compact"
                  hide-details
                  class="dt-opt-switch"
                  :disabled="readonly"
                  @update:model-value="onDisplayUriAsIconChange"
                />
              </div>
            </div>

            <div v-if="showDateFormatControls" class="dt-opt-row">
              <div class="dt-opt-label">Date format</div>
              <div class="dt-opt-control">
                <v-select
                  :model-value="displayDateFormat"
                  :items="DATE_FORMAT_ITEMS"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="compact"
                  hide-details
                  :disabled="readonly"
                  @update:model-value="onDisplayDateFormatChange"
                />
              </div>
            </div>

            <div v-if="showFormatControls" class="dt-opt-row">
              <div class="dt-opt-label">Format</div>
              <div class="dt-opt-control">
                <v-select
                  :model-value="displayFormat"
                  :items="DISPLAY_FORMAT_ITEMS"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="compact"
                  hide-details
                  :disabled="readonly"
                  @update:model-value="onDisplayFormatChange"
                />
              </div>
            </div>

            <div v-if="showFieldLayoutControls" class="dt-opt-row">
              <div class="dt-opt-label">Label layout</div>
              <div class="dt-opt-control">
                <v-select
                  :model-value="displayLayout"
                  :items="DISPLAY_LAYOUT_ITEMS"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="compact"
                  hide-details
                  :disabled="readonly"
                  @update:model-value="onDisplayLayoutChange"
                />
              </div>
            </div>

            <div v-if="layoutDefaultRendererKey" class="dt-opt-row">
              <div class="dt-opt-label">Layout</div>
              <div class="dt-opt-control">
                <div class="dt-opt-static">{{ layoutDefaultLabel }} (default)</div>
              </div>
            </div>

            <template v-if="showDefaultRendererParams">
              <div
                v-for="entry in rendererParamEntries"
                :key="'default-' + entry.key"
                class="dt-opt-row is-nested"
              >
                <div class="dt-opt-label">{{ entry.label }}</div>
                <div class="dt-opt-control">
                  <v-text-field
                    :model-value="rendererParamValue(entry.key)"
                    variant="outlined"
                    density="compact"
                    hide-details
                    :disabled="readonly"
                    @update:model-value="(v) => onRendererParamChange(entry.key, v)"
                  />
                </div>
                <p v-if="entry.description" class="dt-opt-hint">{{ entry.description }}</p>
              </div>
            </template>

            <div v-if="showRendererOverride" class="dt-opt-row">
              <div class="dt-opt-label">{{ node.type === 'widget' ? 'Widget type' : 'Renderer' }}</div>
              <div class="dt-opt-control">
                <v-select
                  :model-value="displayRendererValue"
                  :items="fieldRendererItems"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  density="compact"
                  hide-details
                  :clearable="node.type !== 'widget'"
                  :loading="fieldRenderersLoading"
                  :disabled="readonly"
                  :placeholder="node.type === 'widget' ? 'Select widget type' : 'None'"
                  @update:model-value="onDisplayRendererChange"
                />
              </div>
              <p v-if="rendererHint" class="dt-opt-hint">
                {{ rendererHint }}
              </p>
            </div>

            <template v-if="showOverrideRendererParams">
              <div
                v-for="entry in rendererParamEntries"
                :key="'override-' + entry.key"
                class="dt-opt-row is-nested"
              >
                <div class="dt-opt-label">{{ entry.label }}</div>
                <div class="dt-opt-control">
                  <v-text-field
                    :model-value="rendererParamValue(entry.key)"
                    variant="outlined"
                    density="compact"
                    hide-details
                    :disabled="readonly"
                    @update:model-value="(v) => onRendererParamChange(entry.key, v)"
                  />
                </div>
                <p v-if="entry.description" class="dt-opt-hint">{{ entry.description }}</p>
              </div>
            </template>

            <div v-if="showLinkifyControls" class="dt-opt-row">
              <div class="dt-opt-label">Linkify URLs</div>
              <div class="dt-opt-control">
                <v-switch
                  :model-value="displayLinkify"
                  color="primary"
                  density="compact"
                  hide-details
                  class="dt-opt-switch"
                  :disabled="readonly"
                  @update:model-value="onDisplayLinkifyChange"
                />
              </div>
            </div>

            <div class="dt-opt-row">
              <div class="dt-opt-label">Hide field</div>
              <div class="dt-opt-control">
                <v-switch
                  :model-value="displayHidden"
                  color="primary"
                  density="compact"
                  hide-details
                  class="dt-opt-switch"
                  :disabled="readonly"
                  @update:model-value="onDisplayHiddenChange"
                />
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>
    </template>

    <template v-else>
      <div class="text-body-2 text-medium-emphasis pa-4">Select <strong>Description</strong> or a node in the tree.</div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useDisplayTemplatesApi } from '../composables/useDisplayTemplatesApi';
import {
  FIELD_TYPES,
  isCustomLayoutField,
  isPropNode,
  nodeIcon,
} from '../utils/displayTemplateTree';
import {
  defaultFormatForType,
  DEFAULT_DATE_FORMAT,
  DATE_FORMAT_ITEMS,
  DISPLAY_FORMAT_ITEMS,
  DISPLAY_LAYOUT_ITEMS,
  ensureDisplayOptionsObject,
  normalizeDisplayOptions,
  supportsDisplayFormat,
  supportsFieldLayout,
  supportsLinkify,
  hasActiveDateFormatting,
  hasActiveUri,
  normalizeDisplayLayoutFieldNodeInPlace,
  stripMetadataEditorOnlyKeys,
} from '../utils/displayFieldOptions';
import { buildFieldRendererSelectItems, defaultRendererFallbackLabel, defaultRendererKeyForLayoutType, effectiveRendererKey, migrateLegacyWidgetOptions, resolvedRendererKey, stripDefaultLayoutRenderer, stripEmptyFieldTemplate, stripEmptyRenderer, applyRendererDefaultParams } from '../utils/displayFieldRenderers';

defineOptions({ name: 'DisplayTemplateInspector' });

const { fetchRenderersBySourceType } = useDisplayTemplatesApi();

const props = defineProps({
  selectionKind: { type: String, default: 'template_root' },
  templateRoot: { type: Object, required: true },
  form: { type: Object, required: true },
  model: { type: Object, default: null },
  selectedNode: { type: Object, default: null },
  readonly: { type: Boolean, default: false },
});

const emit = defineEmits(['dirty']);

function displayValue(value) {
  const text = String(value ?? '').trim();
  return text || '—';
}

const coreFilePath = computed(() => {
  const raw = props.model?.file_path || props.model?.file || '';
  return String(raw).trim();
});

const node = computed(() => props.selectedNode);

const mode = computed(() => {
  if (props.selectionKind !== 'node' || !node.value) return 'empty';
  const t = node.value.type;
  if (t === 'section_container' || t === 'section') return 'section_like';
  return 'field';
});

const hasActiveDateFormattingOnNode = computed(() => hasActiveDateFormatting(node.value));

const hasActiveUriOnNode = computed(() => hasActiveUri(node.value));

const layoutDefaultRendererKey = computed(() => defaultRendererKeyForLayoutType(node.value?.type));

const hasCompositeRenderer = computed(() => {
  const key = resolvedRendererKey(node.value?.display_options, node.value?.type);
  return typeof key === 'string' && key.length > 0;
});

const arrayNeedsColumns = computed(() => {
  const n = node.value;
  if (!n || props.readonly) return false;
  if (!isCustomLayoutField(n)) return false;
  if (n.type !== 'array' && n.type !== 'nested_array') return false;
  return !Array.isArray(n.props) || n.props.length === 0;
});

const displayValueMode = computed(() => {
  if (hasCompositeRenderer.value) return 'composite';
  if (hasActiveUriOnNode.value) return 'uri';
  if (hasActiveDateFormattingOnNode.value) return 'date';
  return 'text';
});

const displayValueModeItems = computed(() => {
  const t = node.value?.type;
  const items = [];
  if (t === 'string' || t === 'text' || t === 'number' || t === 'integer' || t === 'boolean') {
    items.push({ title: 'Text', value: 'text' });
  }
  if (t === 'string' || t === 'text') {
    items.push({ title: 'URI', value: 'uri' });
  }
  if (t === 'string' || t === 'date') {
    items.push({ title: 'Date', value: 'date' });
  }
  return items;
});

const showDisplayValueMode = computed(
  () => displayValueModeItems.value.length > 1 && !hasCompositeRenderer.value
);

const showFormatControls = computed(
  () => displayValueMode.value === 'text' && supportsDisplayFormat(node.value)
);

const showLinkifyControls = computed(
  () => displayValueMode.value === 'text' && supportsLinkify(node.value)
);

const showFieldLayoutControls = computed(
  () =>
    supportsFieldLayout(node.value) &&
    !hasCompositeRenderer.value &&
    displayValueMode.value !== 'date'
);

const showDateFormatControls = computed(() => displayValueMode.value === 'date');

const showUriAsIconControls = computed(() => displayValueMode.value === 'uri');

const showFieldTemplateControl = computed(() => {
  const t = node.value?.type;
  if (!t || t === 'section' || t === 'section_container') return false;
  return true;
});

/** @deprecated alias */
const hasCustomFieldTemplate = hasCompositeRenderer;

const displayFormat = computed(() => {
  const n = node.value;
  if (!n) return 'plain';
  const fmt = n.display_options?.format;
  if (fmt === 'plain' || fmt === 'markdown' || fmt === 'html' || fmt === 'richtext') return fmt;
  return defaultFormatForType(n.type);
});

const displayLinkify = computed(() => !!node.value?.display_options?.linkify);

const displayLayout = computed(() =>
  node.value?.display_options?.layout === 'inline' ? 'inline' : 'stacked'
);

const displayHidden = computed(() => !!node.value?.display_options?.hidden);

const displayIsUri = computed(() => !!node.value?.display_options?.is_uri);

const displayUriAsIcon = computed(() => {
  if (!displayIsUri.value) return false;
  const v = node.value?.display_options?.uri_as_icon;
  if (v === false) return false;
  return true;
});

const displayDateFormat = computed(() => {
  const df = node.value?.display_options?.date_format;
  if (typeof df === 'string' && df.length) return df;
  return null;
});

const displayRendererValue = computed(() => {
  const key = effectiveRendererKey(node.value?.display_options);
  const def = layoutDefaultRendererKey.value;
  if (!key || (def && key === def)) return null;
  return key;
});

/** @deprecated */
const displayFieldRendererValue = displayRendererValue;

const rendererRecordsByKey = ref({});

const layoutDefaultLabel = computed(() => {
  const key = layoutDefaultRendererKey.value;
  if (!key) return '';
  const rec = rendererRecordsByKey.value[key];
  return rec?.label || defaultRendererFallbackLabel(key);
});

const activeRendererRecord = computed(() => {
  const key = resolvedRendererKey(node.value?.display_options, node.value?.type);
  if (!key) return null;
  return rendererRecordsByKey.value[key] || null;
});

const rendererHint = computed(() => {
  if (node.value?.type === 'widget') {
    if (displayRendererValue.value) return '';
    return 'Required. DOI citation or iframe embed.';
  }
  if (layoutDefaultRendererKey.value) {
    if (displayRendererValue.value) return '';
    return 'Optional. Use a different layout than the default.';
  }
  if (!hasCompositeRenderer.value) {
    return 'Optional. Specialized display (tags, map, …) replaces text, URI, and date options.';
  }
  return '';
});

const rendererParamEntries = computed(() => {
  const params = activeRendererRecord.value?.params;
  if (!params || typeof params !== 'object') return [];
  return Object.entries(params).map(([key, meta]) => {
    const description =
      typeof meta?.description === 'string' ? meta.description.trim() : '';
    return {
      key,
      label: meta?.label || key,
      description,
    };
  });
});

const showDefaultRendererParams = computed(
  () =>
    hasCompositeRenderer.value &&
    rendererParamEntries.value.length > 0 &&
    !displayRendererValue.value
);

const showOverrideRendererParams = computed(
  () =>
    hasCompositeRenderer.value &&
    rendererParamEntries.value.length > 0 &&
    !!displayRendererValue.value
);

function rendererParamValue(paramKey) {
  const v = node.value?.display_options?.[paramKey];
  if (v === undefined || v === null) return '';
  return String(v);
}

const fieldRendererItems = ref([]);
const fieldRenderersLoading = ref(false);

const showRendererOverride = computed(() => {
  if (node.value?.type === 'widget') return true;
  if (!showFieldTemplateControl.value || displayValueMode.value === 'date') return false;
  if (fieldRenderersLoading.value || displayRendererValue.value) return true;
  return fieldRendererItems.value.length > 0;
});

async function loadFieldRendererOptions() {
  const n = node.value;
  const layoutType = n?.type;
  if (!n || mode.value !== 'field' || !layoutType || layoutType === 'section' || layoutType === 'section_container') {
    fieldRendererItems.value = [];
    return;
  }
  fieldRenderersLoading.value = true;
  try {
    const rows = await fetchRenderersBySourceType(layoutType, props.form?.data_type);
    const map = {};
    for (const row of rows || []) {
      if (row.key) map[String(row.key)] = row;
    }
    rendererRecordsByKey.value = map;
    fieldRendererItems.value = buildFieldRendererSelectItems(
      rows,
      displayRendererValue.value,
      defaultRendererKeyForLayoutType(layoutType)
    );
  } catch {
    rendererRecordsByKey.value = {};
    fieldRendererItems.value = buildFieldRendererSelectItems(
      [],
      displayRendererValue.value,
      defaultRendererKeyForLayoutType(layoutType)
    );
  } finally {
    fieldRenderersLoading.value = false;
  }
}

function syncJsonFieldsFromNode() {
  const n = node.value;
  if (!n || mode.value !== 'field') return;

  stripMetadataEditorOnlyKeys(n);
  migrateLegacyWidgetOptions(n);
  normalizeDisplayLayoutFieldNodeInPlace(n, false);

  if (supportsDisplayFormat(n)) {
    const o = ensureDisplayOptionsObject(n);
    if (o.format === undefined) o.format = defaultFormatForType(n.type);
    if (o.linkify === undefined) o.linkify = n.type === 'text';
  }

  if (hasActiveDateFormatting(n)) {
    const o = ensureDisplayOptionsObject(n);
    normalizeDisplayOptions(o, n.type);
  }

  const opts = n.display_options;
  if (opts && typeof opts === 'object' && !Array.isArray(opts)) {
    stripEmptyFieldTemplate(opts);
    stripEmptyRenderer(opts);
  }
}

watch(() => props.selectedNode?._tid, () => syncJsonFieldsFromNode(), { immediate: true });

watch(
  () => [node.value?.type, node.value?._tid, mode.value, props.form?.data_type],
  () => {
    loadFieldRendererOptions();
  },
  { immediate: true }
);

function onDisplayFormatChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  o.format = value;
  if (value !== 'plain') {
    delete o.linkify;
  }
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onDisplayLayoutChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  if (value === 'inline') {
    o.layout = 'inline';
  } else {
    delete o.layout;
  }
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onLayoutTypeChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  n.type = value;
  const o = ensureDisplayOptionsObject(n);
  if (value !== 'string') {
    delete o.date_format;
  }
  if (value === 'array' || value === 'nested_array') {
    if (!Array.isArray(n.props)) n.props = [];
  }
  if (supportsDisplayFormat(n)) {
    if (o.format === undefined) o.format = defaultFormatForType(value);
    if (o.linkify === undefined) o.linkify = value === 'text';
    normalizeDisplayOptions(o, value);
  } else {
    delete o.format;
    delete o.linkify;
  }
  loadFieldRendererOptions();
  emit('dirty');
}

function onDisplayDateFormatChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  if (value == null || value === '') {
    delete o.date_format;
    if (supportsDisplayFormat(n)) {
      if (o.format === undefined) o.format = defaultFormatForType(n.type);
      if (o.linkify === undefined) o.linkify = n.type === 'text';
    }
    normalizeDisplayOptions(o, n.type);
  } else {
    o.date_format = value;
    normalizeDisplayOptions(o, n.type);
  }
  emit('dirty');
}

function onDisplayLinkifyChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  ensureDisplayOptionsObject(n).linkify = !!value;
  emit('dirty');
}

function onDisplayValueModeChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  delete o.renderer;
  delete o.field_template;
  if (value === 'uri') {
    o.is_uri = true;
    if (typeof o.uri_as_icon !== 'boolean') {
      o.uri_as_icon = false;
    }
    delete o.date_format;
    delete o.format;
    delete o.linkify;
  } else if (value === 'date') {
    delete o.is_uri;
    delete o.uri_as_icon;
    delete o.format;
    delete o.linkify;
    if (!o.date_format) {
      o.date_format = DEFAULT_DATE_FORMAT;
    }
  } else {
    delete o.is_uri;
    delete o.uri_as_icon;
    delete o.date_format;
    if (o.format === undefined) o.format = defaultFormatForType(n.type);
    if (o.linkify === undefined) o.linkify = n.type === 'text';
  }
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onDisplayHiddenChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  if (value) {
    o.hidden = true;
  } else {
    delete o.hidden;
  }
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onDisplayIsUriChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  if (value) {
    o.is_uri = true;
    if (typeof o.uri_as_icon !== 'boolean') {
      o.uri_as_icon = false;
    }
    delete o.date_format;
    delete o.format;
    delete o.linkify;
  } else {
    delete o.is_uri;
    delete o.uri_as_icon;
    if (supportsDisplayFormat({ ...n, display_options: { ...o } })) {
      if (o.format === undefined) o.format = defaultFormatForType(n.type);
      if (o.linkify === undefined) o.linkify = n.type === 'text';
    }
  }
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onDisplayUriAsIconChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  o.is_uri = true;
  o.uri_as_icon = !!value;
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onDisplayRendererChange(value) {
  const n = node.value;
  if (!n || props.readonly) return;
  const o = ensureDisplayOptionsObject(n);
  const def = defaultRendererKeyForLayoutType(n.type);
  if (value == null || value === '' || String(value).trim() === def) {
    delete o.renderer;
    delete o.field_template;
  } else {
    const trimmed = String(value).trim();
    if (trimmed) {
      o.renderer = trimmed;
      delete o.field_template;
      applyRendererDefaultParams(o, rendererRecordsByKey.value[trimmed]);
    } else {
      delete o.renderer;
    }
  }
  stripEmptyRenderer(o);
  stripEmptyFieldTemplate(o);
  stripDefaultLayoutRenderer(o, n.type);
  normalizeDisplayOptions(o, n.type);
  emit('dirty');
}

function onRendererParamChange(paramKey, value) {
  const n = node.value;
  if (!n || props.readonly || !paramKey) return;
  const o = ensureDisplayOptionsObject(n);
  const trimmed = value == null ? '' : String(value).trim();
  if (trimmed) {
    o[paramKey] = trimmed;
  } else {
    delete o[paramKey];
    applyRendererDefaultParams(o, activeRendererRecord.value);
  }
  emit('dirty');
}

/** @deprecated */
function onDisplayFieldTemplateChange(value) {
  onDisplayRendererChange(value);
}

function onFieldKeyChange() {
  const n = node.value;
  if (n && isPropNode(n) && typeof n.key === 'string') {
    const parts = String(n.prop_key || '').split('.');
    parts[parts.length - 1] = n.key;
    n.prop_key = parts.join('.');
  }
  emit('dirty');
}
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
.dt-readonly-heading {
  margin: 0 0 2rem;
}
.dt-readonly-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px 32px;
}
.dt-readonly-item {
  min-width: 0;
}
.dt-readonly-item-span {
  grid-column: 1 / -1;
}
.dt-readonly-item .dt-fld-label {
  margin-bottom: 8px;
}
.dt-readonly-value {
  font-size: 0.9375rem;
  line-height: 1.45;
  word-break: break-word;
}
@media (max-width: 959.98px) {
  .dt-readonly-grid {
    grid-template-columns: 1fr;
  }
}
.dt-opt-list {
  display: flex;
  flex-direction: column;
  padding-top: 8px;
}
.dt-opt-row {
  display: grid;
  grid-template-columns: 11rem minmax(0, 1fr);
  column-gap: 16px;
  row-gap: 4px;
  align-items: center;
  min-height: 40px;
  padding: 10px 0 8px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}
.dt-opt-list .dt-opt-row:last-child {
  border-bottom: none;
}
.dt-opt-list--identity .dt-opt-row {
  border-bottom: none;
  min-height: 36px;
  padding: 4px 0;
}
.dt-opt-row.is-nested .dt-opt-label {
  padding-left: 1.25rem;
}
.dt-opt-row--top {
  align-items: start;
  padding-top: 10px;
}
.dt-opt-label {
  font-size: 0.75rem;
  line-height: 1.3;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.65);
}
.dt-opt-control {
  min-width: 0;
}
.dt-opt-static {
  font-size: 0.875rem;
  line-height: 1.4;
  font-weight: 500;
  color: rgba(var(--v-theme-on-surface), 0.87);
}
.dt-opt-hint {
  grid-column: 2;
  margin: 0 0 2px;
  font-size: 0.75rem;
  line-height: 1.35;
  color: rgba(var(--v-theme-on-surface), 0.55);
}
.dt-opt-switch {
  margin-inline-start: -4px;
}
.dt-opt-switch :deep(.v-selection-control) {
  min-height: 32px;
  justify-content: flex-start;
}
.dt-display-options-card {
  border-color: rgba(var(--v-theme-on-surface), 0.1) !important;
}
.dt-display-options-card :deep(.v-card-title) {
  line-height: 1.3;
  font-size: 16px;
  font-weight: 700;
}
.font-monospace :deep(textarea) {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace;
  font-size: 0.8rem;
  line-height: 1.4;
}
.dt-custom-chip {
  color: #7b1fa2 !important;
  background: rgba(123, 31, 162, 0.12) !important;
}
.dt-widget-chip {
  color: #e64a19 !important;
  background: rgba(230, 74, 25, 0.12) !important;
}
</style>
