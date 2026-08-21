/**
 * @typedef {{ type?: string, label?: string, description?: string }} RendererParamMeta
 * @typedef {{ key?: string, label?: string, field_template?: string, handler?: { id?: string }, supports_layout_builder?: boolean, status?: string, params?: Record<string, RendererParamMeta>, default_params?: Record<string, unknown> }} RendererRecord
 */

/** @type {Record<string, string>} Layout type → implicit catalog renderer when unset */
export const DEFAULT_RENDERER_BY_LAYOUT_TYPE = {
  nested_array: 'field_array_accordion',
  array: 'field_array',
  object: 'field_object_additional',
};

/** @type {Record<string, string>} */
const DEFAULT_RENDERER_FALLBACK_LABELS = {
  field_array_accordion: 'Accordion',
  field_array: 'Table',
  field_object_additional: 'Open object',
};

/**
 * @param {string|undefined} layoutType
 * @returns {string}
 */
export function defaultRendererKeyForLayoutType(layoutType) {
  if (!layoutType) return '';
  return DEFAULT_RENDERER_BY_LAYOUT_TYPE[layoutType] || '';
}

/**
 * Stored renderer, or the implicit default for this layout type.
 * @param {object|null|undefined} displayOptions
 * @param {string|undefined} layoutType
 * @returns {string}
 */
export function resolvedRendererKey(displayOptions, layoutType) {
  const explicit = effectiveRendererKey(displayOptions);
  if (explicit) return explicit;
  return defaultRendererKeyForLayoutType(layoutType);
}

/**
 * @param {object|null|undefined} displayOptions
 * @param {string|undefined} layoutType
 */
export function stripDefaultLayoutRenderer(displayOptions, layoutType) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  const def = defaultRendererKeyForLayoutType(layoutType);
  if (!def) return;
  if (effectiveRendererKey(displayOptions) !== def) return;
  delete displayOptions.renderer;
  delete displayOptions.field_template;
}

/** @type {Record<string, string>} Legacy view basename → registry key */
export const LEGACY_FIELD_TEMPLATE_TO_RENDERER = {
  'field_doi-citation': 'field_doi_citation',
  field_object_additional: 'field_object_additional',
};

/** @type {Record<string, string>} Legacy widget_options.widget_field → registry key */
export const LEGACY_WIDGET_FIELD_TO_RENDERER = {
  'doi-citation': 'field_doi_citation',
  'field_doi-citation': 'field_doi_citation',
  widget_default: 'field_iframe_embed',
  field_widget_default: 'field_iframe_embed',
};

/**
 * @param {object|null|undefined} node
 */
export function migrateLegacyWidgetOptions(node) {
  if (!node || typeof node !== 'object' || node.type !== 'widget') return;
  const wo = node.widget_options && typeof node.widget_options === 'object' ? node.widget_options : null;
  if (!wo) return;

  if (!node.display_options || typeof node.display_options !== 'object' || Array.isArray(node.display_options)) {
    node.display_options = {};
  }
  const o = node.display_options;

  if (!o.renderer || String(o.renderer).trim() === '') {
    const wf = typeof wo.widget_field === 'string' ? wo.widget_field.trim() : '';
    if (wf) {
      o.renderer = LEGACY_WIDGET_FIELD_TO_RENDERER[wf] || wf;
    }
  }
  if ((!o.data_key || String(o.data_key).trim() === '') && typeof wo.data_key === 'string' && wo.data_key.trim()) {
    const dk = wo.data_key.trim();
    if (dk !== 'widgets.default' && dk !== 'iframe_embeds') {
      o.data_key = dk;
    }
  }
  delete node.widget_options;
}

/**
 * @param {RendererRecord} row
 * @returns {string} Registry key stored in display_options.renderer
 */
export function rendererRegistryKey(row) {
  if (row.key) return String(row.key);
  return '';
}

/**
 * Resolve effective renderer key from display_options (renderer or legacy field_template).
 * @param {object|null|undefined} displayOptions
 * @returns {string}
 */
export function effectiveRendererKey(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return '';
  if (typeof displayOptions.renderer === 'string' && displayOptions.renderer.trim()) {
    return displayOptions.renderer.trim();
  }
  const ft =
    typeof displayOptions.field_template === 'string' ? displayOptions.field_template.trim() : '';
  if (!ft || ft === 'field_text_inline') return '';
  if (LEGACY_FIELD_TEMPLATE_TO_RENDERER[ft]) return LEGACY_FIELD_TEMPLATE_TO_RENDERER[ft];
  return ft;
}

/**
 * @param {RendererRecord} row
 * @returns {string}
 * @deprecated Use rendererRegistryKey
 */
export function rendererFieldTemplateValue(row) {
  return rendererRegistryKey(row);
}

/**
 * @param {string} rendererKey
 * @returns {string}
 */
export function defaultRendererFallbackLabel(rendererKey) {
  return DEFAULT_RENDERER_FALLBACK_LABELS[rendererKey] || rendererKey;
}

/**
 * Override options only (implicit layout default is omitted from the list).
 * @param {RendererRecord[]} rows
 * @param {string|null|undefined} [currentRendererKey]
 * @param {string|null|undefined} [defaultRendererKey]
 * @returns {{ title: string, value: string }[]}
 */
export function buildFieldRendererSelectItems(rows, currentRendererKey = '', defaultRendererKey = '') {
  /** @type {{ title: string, value: string }[]} */
  const items = [];
  const defaultKey = typeof defaultRendererKey === 'string' ? defaultRendererKey.trim() : '';

  for (const row of rows || []) {
    if (row.status === 'inactive') continue;
    if (row.supports_layout_builder === false) continue;
    const value = rendererRegistryKey(row);
    if (!value) continue;
    if (defaultKey && value === defaultKey) continue;
    items.push({
      title: row.label || row.key || value,
      value,
    });
  }

  const current = typeof currentRendererKey === 'string' ? currentRendererKey.trim() : '';
  if (current && current !== defaultKey && !items.some((i) => i.value === current)) {
    items.push({ title: `${current} (not in registry)`, value: current });
  }

  return items;
}

/**
 * @param {object|null|undefined} displayOptions
 */
export function stripEmptyRenderer(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (
    displayOptions.renderer === '' ||
    displayOptions.renderer === null ||
    displayOptions.renderer === undefined
  ) {
    delete displayOptions.renderer;
  } else if (typeof displayOptions.renderer === 'string') {
    const t = displayOptions.renderer.trim();
    if (t) displayOptions.renderer = t;
    else delete displayOptions.renderer;
  }
}

/**
 * @param {object|null|undefined} displayOptions
 */
export function stripEmptyFieldTemplate(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (
    displayOptions.field_template === '' ||
    displayOptions.field_template === null ||
    displayOptions.field_template === undefined
  ) {
    delete displayOptions.field_template;
  } else if (typeof displayOptions.field_template === 'string') {
    const t = displayOptions.field_template.trim();
    if (t) displayOptions.field_template = t;
    else delete displayOptions.field_template;
  }
}

/**
 * Move legacy field_template → renderer where possible.
 * @param {object|null|undefined} displayOptions
 */
export function migrateLegacyCompositeRenderer(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (displayOptions.renderer) {
    delete displayOptions.field_template;
    return;
  }
  const ft =
    typeof displayOptions.field_template === 'string' ? displayOptions.field_template.trim() : '';
  if (!ft || ft === 'field_text_inline') return;
  const key = LEGACY_FIELD_TEMPLATE_TO_RENDERER[ft] || ft;
  displayOptions.renderer = key;
  delete displayOptions.field_template;
}

/**
 * @param {object} displayOptions
 * @param {RendererRecord|null|undefined} record
 */
export function applyRendererDefaultParams(displayOptions, record) {
  if (!displayOptions || typeof displayOptions !== 'object' || !record) return;
  const defaults = record.default_params;
  if (!defaults || typeof defaults !== 'object') return;
  for (const [paramKey, defaultVal] of Object.entries(defaults)) {
    if (
      displayOptions[paramKey] === undefined ||
      displayOptions[paramKey] === null ||
      displayOptions[paramKey] === ''
    ) {
      displayOptions[paramKey] = defaultVal;
    }
  }
}
