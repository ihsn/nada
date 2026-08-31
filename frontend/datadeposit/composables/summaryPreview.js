import { get as lodashGet } from 'lodash';
import { isSectionNode } from '@/shared/metadata-form/utils/fieldFlags';
import {
  formatPreviewArrayRows,
  formatPreviewScalar,
  isFieldEmpty,
} from '@/shared/metadata-form/utils/previewContent';

function fieldDisplayValue(field, data, labels) {
  const raw = lodashGet(data, field.key);
  if (isFieldEmpty(raw)) return '';
  if (field.type === 'array' || field.type === 'nested_array') {
    return formatPreviewArrayRows(raw, field.props, labels)
      .map((row) => row.text)
      .filter(Boolean)
      .join('\n');
  }
  if (field.type === 'simple_array' && Array.isArray(raw)) {
    return raw.map((value) => formatPreviewScalar(value, labels)).filter(Boolean).join('\n');
  }
  return formatPreviewScalar(raw, labels);
}

/**
 * Walk a deposit form template and group filled fields under the nearest section title.
 */
export function summarySections(items, data, labels = {}) {
  const sections = [];

  function sectionFor(title) {
    const key = title || 'Details';
    let section = sections.find((row) => row.title === key);
    if (!section) {
      section = { title: key, rows: [] };
      sections.push(section);
    }
    return section;
  }

  function walk(nodes, inheritedTitle) {
    (nodes || []).forEach((item) => {
      if (!item || typeof item !== 'object') return;
      if (isSectionNode(item)) {
        walk(item.items, item.title || inheritedTitle);
        return;
      }
      if (!item.key) return;
      const value = fieldDisplayValue(item, data, labels);
      if (!value) return;
      sectionFor(inheritedTitle).rows.push({
        key: item.key,
        title: item.title || item.key,
        value,
      });
    });
  }

  walk(items, '');
  return sections.filter((section) => section.rows.length);
}
