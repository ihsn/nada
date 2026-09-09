import { cloneJson, isPropNode } from './displayTemplateTree';
import { stripEmptyFieldTemplate, stripEmptyRenderer, stripDefaultLayoutRenderer, migrateLegacyCompositeRenderer, migrateLegacyWidgetOptions } from './displayFieldRenderers';

/** @typedef {'plain'|'markdown'|'html'|'richtext'} DisplayFormat */

export const DEFAULT_DATE_FORMAT = 'Y-m-d';

export const DATE_FORMAT_ITEMS = [
  { value: 'Y-m-d', title: 'ISO (2024-03-15)' },
  { value: 'Y-m', title: 'Year-month (2024-03)' },
  { value: 'Y', title: 'Year only (2024)' },
  { value: 'd/m/Y', title: 'Day / month / year (15/03/2024)' },
  { value: 'm/d/Y', title: 'Month / day / year (03/15/2024)' },
  { value: 'F j, Y', title: 'Long (March 15, 2024)' },
  { value: 'site_default', title: 'Site default' },
];

const ALLOWED_DATE_FORMATS = new Set(DATE_FORMAT_ITEMS.map((i) => i.value));

export const DISPLAY_FORMAT_ITEMS = [
  { value: 'plain', title: 'Plain text' },
  { value: 'markdown', title: 'Markdown' },
  { value: 'html', title: 'HTML' },
  { value: 'richtext', title: 'Rich text (HTML + Markdown)' },
];

export const DISPLAY_LAYOUT_ITEMS = [
  { value: 'stacked', title: 'Stacked (label above value)' },
  { value: 'inline', title: 'Inline (label beside value)' },
];

/** Keys that belong on metadata editor templates only, not display layout nodes. */
export const METADATA_EDITOR_ONLY_KEYS = [
  'required',
  'is_required',
  'help_text',
  'rules',
  'display_type',
  'enum',
  '_ddi_xpath',
];

const SCALAR_LAYOUT_TYPES = new Set(['string', 'text', 'number', 'integer', 'boolean']);
const DATE_LAYOUT_TYPE = 'date';

/**
 * @param {object|null|undefined} displayOptions
 * @returns {boolean}
 */
export function displayOptionsHasDateFormat(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return false;
  const df = displayOptions.date_format;
  return df !== undefined && df !== null && String(df).trim() !== '';
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function hasActiveDateFormatting(node) {
  if (!node || typeof node !== 'object') return false;
  if (hasActiveUri(node)) return false;
  if (node.type === DATE_LAYOUT_TYPE) return true;
  return displayOptionsHasDateFormat(node.display_options);
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function hasActiveUri(node) {
  return !!node?.display_options?.is_uri;
}

/**
 * Copy ME rules.is_uri onto display_options before rules are stripped.
 * @param {object|null|undefined} node
 */
export function inferLegacyUriDisplayOptions(node) {
  if (!node || typeof node !== 'object') return;
  const rules = node.rules;
  if (!rules || typeof rules !== 'object' || Array.isArray(rules)) return;
  if (!rules.is_uri) return;
  const opts = ensureDisplayOptionsObject(node);
  if (opts.is_uri === undefined) opts.is_uri = true;
  if (opts.uri_as_icon === undefined) opts.uri_as_icon = true;
}

/**
 * @param {string|undefined} type
 * @returns {DisplayFormat}
 */
export function defaultFormatForType(type) {
  return type === 'text' ? 'richtext' : 'plain';
}

/**
 * @param {string|undefined} displayType Legacy ME display_type before strip.
 * @param {string|undefined} layoutType Node type.
 * @returns {DisplayFormat}
 */
export function inferFormatFromLegacy(displayType, layoutType) {
  if (displayType === 'textarea') return 'richtext';
  return defaultFormatForType(layoutType);
}

/**
 * @param {string|undefined} displayType
 * @returns {boolean}
 */
export function inferLinkifyFromLegacy(displayType, layoutType) {
  if (displayType === 'textarea') return true;
  if (layoutType === 'text') return true;
  return false;
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function supportsDisplayFormat(node) {
  if (!node || typeof node !== 'object') return false;
  if (hasActiveDateFormatting(node)) return false;
  if (hasActiveUri(node)) return false;
  const t = node.type;
  if (t === DATE_LAYOUT_TYPE) return false;
  if (SCALAR_LAYOUT_TYPES.has(t || '')) return true;
  if (isPropNode(node) && SCALAR_LAYOUT_TYPES.has(t || '')) return true;
  return false;
}

/**
 * Top-level scalar fields may use stacked vs inline label layout (Tier 2).
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function supportsFieldLayout(node) {
  if (!node || typeof node !== 'object') return false;
  if (isPropNode(node)) return false;
  if (hasActiveUri(node)) {
    const t = node.type;
    return t === 'string' || t === 'text';
  }
  return supportsDisplayFormat(node);
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function supportsLinkify(node) {
  if (!supportsDisplayFormat(node)) return false;
  const fmt = node?.display_options?.format ?? defaultFormatForType(node?.type);
  return fmt === 'plain';
}

/**
 * String scalars (and legacy layout type date) may use display_options.date_format.
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function supportsDateFormat(node) {
  if (!node || typeof node !== 'object') return false;
  if (hasActiveUri(node)) return false;
  const t = node.type;
  if (t === DATE_LAYOUT_TYPE) return true;
  if (t === 'string') return true;
  if (isPropNode(node) && t === 'string') return true;
  return false;
}

/**
 * Scalar string/text fields and array columns may be flagged as a URI.
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function supportsIsUri(node) {
  if (!node || typeof node !== 'object') return false;
  if (hasActiveDateFormatting(node)) return false;
  const t = node.type;
  if (t === 'string' || t === 'text') return true;
  return false;
}

/**
 * Legacy layout type date → string + date_format; ME display_type date → date_format on string.
 * @param {object} node
 */
export function normalizeDateLayoutFieldNodeInPlace(node) {
  if (!node || typeof node !== 'object') return;

  if (node.display_type === 'date' && node.type === 'string') {
    const opts = ensureDisplayOptionsObject(node);
    if (!displayOptionsHasDateFormat(opts)) {
      opts.date_format = DEFAULT_DATE_FORMAT;
    }
  }

  if (node.type === DATE_LAYOUT_TYPE) {
    node.type = 'string';
    const opts = ensureDisplayOptionsObject(node);
    if (!displayOptionsHasDateFormat(opts)) {
      opts.date_format = DEFAULT_DATE_FORMAT;
    }
  }
}

/**
 * @param {object} displayOptions
 */
export function normalizeDateFormatOption(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  const df = displayOptions.date_format;
  if (df === undefined || df === null || df === '') {
    displayOptions.date_format = DEFAULT_DATE_FORMAT;
    return;
  }
  if (!ALLOWED_DATE_FORMATS.has(String(df))) {
    displayOptions.date_format = DEFAULT_DATE_FORMAT;
  }
}

/**
 * @param {object|null|undefined} node
 * @returns {object}
 */
export function ensureDisplayOptionsObject(node) {
  if (!node || typeof node !== 'object') return {};
  if (
    !node.display_options ||
    typeof node.display_options !== 'object' ||
    Array.isArray(node.display_options)
  ) {
    node.display_options = {};
  }
  return node.display_options;
}

/**
 * @param {object} displayOptions
 */
export function normalizeDisplayUriOptions(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (displayOptions.is_uri === true) {
    displayOptions.is_uri = true;
    if (typeof displayOptions.uri_as_icon !== 'boolean') {
      displayOptions.uri_as_icon = true;
    }
    delete displayOptions.format;
    delete displayOptions.linkify;
    delete displayOptions.date_format;
    return;
  }
  delete displayOptions.is_uri;
  delete displayOptions.uri_as_icon;
}

/**
 * @param {object} displayOptions
 */
export function normalizeDisplayHiddenOption(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (displayOptions.hidden === true) {
    displayOptions.hidden = true;
    return;
  }
  delete displayOptions.hidden;
}

/**
 * @param {object} displayOptions
 */
export function normalizeFieldLayoutOption(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (displayOptions.layout === 'inline') {
    displayOptions.layout = 'inline';
    return;
  }
  delete displayOptions.layout;
}

/**
 * Legacy field_text_inline renderer → layout inline.
 * @param {object} displayOptions
 */
export function migrateLegacyInlineFieldTemplate(displayOptions) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  if (displayOptions.field_template === 'field_text_inline') {
    displayOptions.layout = 'inline';
    delete displayOptions.field_template;
  }
}

/**
 * @param {object|null|undefined} node
 * @returns {boolean}
 */
export function isDisplayNodeHidden(node) {
  return !!node?.display_options?.hidden;
}

/**
 * Normalize display_options.format and strip invalid values.
 * @param {object} displayOptions
 * @param {string|undefined} layoutType
 */
export function normalizeDisplayOptions(displayOptions, layoutType) {
  if (!displayOptions || typeof displayOptions !== 'object') return;
  migrateLegacyInlineFieldTemplate(displayOptions);
  migrateLegacyCompositeRenderer(displayOptions);
  stripEmptyFieldTemplate(displayOptions);
  stripEmptyRenderer(displayOptions);
  stripDefaultLayoutRenderer(displayOptions, layoutType);

  if (displayOptions.renderer) {
    delete displayOptions.format;
    delete displayOptions.linkify;
    delete displayOptions.layout;
    delete displayOptions.field_template;
    delete displayOptions.is_uri;
    delete displayOptions.uri_as_icon;
    normalizeDisplayHiddenOption(displayOptions);
    return;
  }

  if (displayOptions.field_template) {
    delete displayOptions.format;
    delete displayOptions.linkify;
    delete displayOptions.layout;
    delete displayOptions.is_uri;
    delete displayOptions.uri_as_icon;
    normalizeDisplayHiddenOption(displayOptions);
    return;
  }

  const usesDateFormatting =
    layoutType === DATE_LAYOUT_TYPE || displayOptionsHasDateFormat(displayOptions);

  if (usesDateFormatting) {
    delete displayOptions.format;
    delete displayOptions.linkify;
    delete displayOptions.layout;
    delete displayOptions.is_uri;
    delete displayOptions.uri_as_icon;
    normalizeDateFormatOption(displayOptions);
    normalizeDisplayHiddenOption(displayOptions);
    return;
  }

  if (displayOptions.is_uri === true) {
    normalizeDisplayUriOptions(displayOptions);
    normalizeFieldLayoutOption(displayOptions);
    normalizeDisplayHiddenOption(displayOptions);
    return;
  }

  delete displayOptions.is_uri;
  delete displayOptions.uri_as_icon;
  delete displayOptions.date_format;

  const fmt = displayOptions.format;
  if (fmt === 'inline') {
    displayOptions.format = 'plain';
  }
  if (fmt !== undefined && fmt !== 'plain' && fmt !== 'markdown' && fmt !== 'html' && fmt !== 'richtext') {
    delete displayOptions.format;
  }
  if (displayOptions.linkify !== undefined && typeof displayOptions.linkify !== 'boolean') {
    delete displayOptions.linkify;
  }
  const effectiveFormat =
    displayOptions.format !== undefined
      ? displayOptions.format
      : supportsDisplayFormat({ type: layoutType, display_options: displayOptions })
        ? defaultFormatForType(layoutType)
        : 'plain';
  if (effectiveFormat !== 'plain') {
    delete displayOptions.linkify;
  }
  if (displayOptions.field_template !== undefined && displayOptions.field_template === '') {
    delete displayOptions.field_template;
  }
  normalizeFieldLayoutOption(displayOptions);
  normalizeDisplayHiddenOption(displayOptions);
  if (supportsDisplayFormat({ type: layoutType, display_options: displayOptions })) {
    if (displayOptions.format === undefined) {
      displayOptions.format = defaultFormatForType(layoutType);
    }
  }
}

/**
 * Normalize a single layout field node in place (ME strip + display_options).
 * @param {object|null|undefined} node
 * @param {boolean} [recurseChildren]
 */
export function normalizeDisplayLayoutFieldNodeInPlace(node, recurseChildren = true) {
  if (!node || typeof node !== 'object') return;
  if (Array.isArray(node)) {
    node.forEach((child) => normalizeDisplayLayoutFieldNodeInPlace(child, recurseChildren));
    return;
  }

  inferLegacyUriDisplayOptions(node);
  migrateLegacyWidgetOptions(node);
  const legacyDisplayType = node.display_type;
  normalizeDateLayoutFieldNodeInPlace(node);

  for (const key of METADATA_EDITOR_ONLY_KEYS) {
    delete node[key];
  }

  const layoutType = node.type;
  const opts =
    node.display_options && typeof node.display_options === 'object' && !Array.isArray(node.display_options)
      ? node.display_options
      : {};

  if (hasActiveDateFormatting({ ...node, display_options: opts })) {
    normalizeDisplayOptions(opts, layoutType);
    node.display_options = opts;
  } else if (hasActiveUri({ ...node, display_options: opts })) {
    normalizeDisplayOptions(opts, layoutType);
    node.display_options = opts;
  } else if (supportsDisplayFormat(node)) {
    if (opts.format === undefined) {
      opts.format = inferFormatFromLegacy(legacyDisplayType, layoutType);
    }
    if (opts.linkify === undefined) {
      opts.linkify = inferLinkifyFromLegacy(legacyDisplayType, layoutType);
    }
    normalizeDisplayOptions(opts, layoutType);
    node.display_options = opts;
  } else if (Object.keys(opts).length) {
    normalizeDisplayOptions(opts, layoutType);
    node.display_options = opts;
  } else if (node.display_options) {
    delete node.display_options;
  }

  if (!recurseChildren) return;

  if (Array.isArray(node.items)) {
    node.items.forEach((child) => normalizeDisplayLayoutFieldNodeInPlace(child, true));
  }
  if (Array.isArray(node.props)) {
    node.props.forEach((child) => normalizeDisplayLayoutFieldNodeInPlace(child, true));
  }
}

/**
 * @param {object|null|undefined} root Template root or subtree
 */
export function sanitizeDisplayLayoutTree(root) {
  normalizeDisplayLayoutFieldNodeInPlace(root);
}

/**
 * Remove ME-only properties and normalize display_options for display template_json.
 * @param {object} node
 * @returns {object}
 */
export function sanitizeDisplayLayoutFieldNode(node) {
  if (!node || typeof node !== 'object') return node;
  const copy = cloneJson(node);
  normalizeDisplayLayoutFieldNodeInPlace(copy);
  return copy;
}

/**
 * @param {string|undefined} type
 * @returns {object}
 */
export function defaultDisplayOptionsForNewField(type = 'string') {
  return {
    format: defaultFormatForType(type),
    linkify: type === 'text',
  };
}

/**
 * Remove metadata-editor-only keys from a live tree node (in place).
 * @param {object|null|undefined} node
 */
export function stripMetadataEditorOnlyKeys(node) {
  if (!node || typeof node !== 'object') return;
  inferLegacyUriDisplayOptions(node);
  normalizeDateLayoutFieldNodeInPlace(node);
  for (const key of METADATA_EDITOR_ONLY_KEYS) {
    delete node[key];
  }
}
