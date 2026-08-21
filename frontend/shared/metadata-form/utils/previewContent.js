/**
 * Helpers for ME-style section_container read-only preview.
 */
import { get as lodashGet } from 'lodash';
import { isSectionType } from './enumOptions';
import { normalizeProps } from './enumOptions';

export function isFieldEmpty(value) {
  if (value === '' || value === null || value === undefined) return true;
  if (Array.isArray(value) && value.length === 0) return true;
  if (Array.isArray(value) && value.length === 1) {
    const only = value[0];
    if (only && typeof only === 'object' && Object.keys(only).length === 0) return true;
    if (Array.isArray(only) && only.length === 0) return true;
  }
  if (typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length === 0) {
    return true;
  }
  return false;
}

export function hasDisplayableContent(items, formData) {
  if (!items || !items.length) return false;
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    if (!item) continue;
    if (item.key && !isFieldEmpty(lodashGet(formData, item.key))) {
      return true;
    }
    if (isSectionType(item.type) && item.items && hasDisplayableContent(item.items, formData)) {
      return true;
    }
  }
  return false;
}

export function formatPreviewScalar(value, labels = {}) {
  if (value === null || value === undefined) return '';
  if (typeof value === 'boolean') {
    return value ? labels.trueLabel || 'True' : labels.falseLabel || 'False';
  }
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}

export function formatPreviewArrayRows(value, props, labels = {}) {
  if (!Array.isArray(value) || !value.length) return [];
  const cols = normalizeProps(props);
  return value.map((row) => {
    if (row == null || typeof row !== 'object') {
      return { text: formatPreviewScalar(row, labels) };
    }
    if (!cols.length) {
      return { text: formatPreviewScalar(row, labels) };
    }
    const parts = cols
      .map((c) => {
        const v = row[c.key];
        if (isFieldEmpty(v)) return null;
        return `${c.title || c.key}: ${formatPreviewScalar(v, labels)}`;
      })
      .filter(Boolean);
    return { text: parts.join(' · ') || formatPreviewScalar(row, labels) };
  });
}
