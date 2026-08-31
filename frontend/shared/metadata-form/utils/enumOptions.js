/**
 * Normalize ME / legacy enum shapes into { title, value } lists for Vuetify selects.
 */
export function enumToSelectItems(enumValue) {
  if (!enumValue) return [];

  if (Array.isArray(enumValue)) {
    return enumValue.map((item) => {
      if (item == null) return { title: '', value: '' };
      if (typeof item !== 'object') {
        return { title: String(item), value: item };
      }
      if (Object.prototype.hasOwnProperty.call(item, 'code')) {
        return {
          title: item.label != null ? String(item.label) : String(item.code),
          value: item.code,
        };
      }
      if (Object.prototype.hasOwnProperty.call(item, 'abbreviation')) {
        const name = item.name != null ? String(item.name) : String(item.abbreviation);
        return {
          title: `${name} (${item.abbreviation})`,
          value: item.abbreviation,
          raw: item,
        };
      }
      if (Object.prototype.hasOwnProperty.call(item, 'value')) {
        return {
          title: item.label != null ? String(item.label) : String(item.value),
          value: item.value,
        };
      }
      const keys = Object.keys(item);
      if (keys.length === 1) {
        const k = keys[0];
        return { title: String(item[k]), value: k };
      }
      return { title: JSON.stringify(item), value: item };
    });
  }

  if (typeof enumValue === 'object') {
    return Object.keys(enumValue).map((key) => ({
      title: String(enumValue[key]),
      value: key,
    }));
  }

  return [];
}

/**
 * Resolve UI control type from ME field (prefer display_type).
 */
export function resolveDisplayType(field) {
  if (!field || typeof field !== 'object') return 'text';
  if (field.display_type) return String(field.display_type);
  const t = field.type;
  if (t === 'textarea') return 'textarea';
  if (t === 'dropdown') return 'dropdown';
  if (t === 'boolean') return 'dropdown';
  if (t === 'date') return 'date';
  if (t === 'integer' || t === 'number') return 'text';
  return 'text';
}

export function isSectionType(type) {
  return type === 'section' || type === 'section_container';
}

export function isSectionContainer(type) {
  return type === 'section_container';
}

export function isEditableSection(type) {
  return type === 'section';
}

export function isArrayType(type) {
  return type === 'array' || type === 'nested_array' || type === 'simple_array';
}

export function isBoundingBoxDisplay(field) {
  return !!(field && String(field.display_type || '') === 'bounding_box');
}

/**
 * Children of an ME section. Nested-array sections use props[]; top-level use items[].
 */
export function sectionChildDefs(field) {
  if (!field || typeof field !== 'object') return [];
  const fromProps = normalizeProps(field.props);
  if (fromProps.length) return fromProps;
  return normalizeProps(field.items);
}

const BBOX_SIDES = ['west', 'east', 'south', 'north'];
const BBOX_SIDE_RE = {
  west: /west/i,
  east: /east/i,
  south: /south/i,
  north: /north/i,
};

/**
 * Resolve west/east/south/north keys for a bounding_box section.
 * Option keys (and child field keys) are relative to the parent row, not the section key.
 *
 * @returns {Record<string, { key: string, field: object }>}
 */
export function resolveBoundingBoxSides(field) {
  const options =
    field?.bounding_box_options && typeof field.bounding_box_options === 'object'
      ? field.bounding_box_options
      : {};
  const props = sectionChildDefs(field);
  const out = {};
  BBOX_SIDES.forEach((side) => {
    const fromOpt = options[side] != null && options[side] !== '' ? String(options[side]) : '';
    const byKey = fromOpt ? props.find((p) => p.key === fromOpt) : null;
    const byName = props.find((p) => BBOX_SIDE_RE[side].test(p.key || ''));
    const key = fromOpt || byKey?.key || byName?.key;
    if (!key) return;
    out[side] = {
      key,
      field:
        byKey ||
        byName || {
          key,
          type: 'number',
          title: side.charAt(0).toUpperCase() + side.slice(1),
          display_type: 'text',
        },
    };
  });
  return out;
}

/**
 * Normalize props to an array of prop definitions.
 */
export function normalizeProps(props) {
  if (!props) return [];
  if (Array.isArray(props)) return props.filter((p) => p && typeof p === 'object');
  if (typeof props === 'object') {
    return Object.keys(props).map((key) => {
      const p = props[key];
      if (!p || typeof p !== 'object') {
        return { key, title: key, type: 'string', display_type: 'text' };
      }
      return { key: p.key || key, ...p };
    });
  }
  return [];
}

export function emptyRowForProps(props) {
  const row = {};
  normalizeProps(props).forEach((p) => {
    const k = p.key;
    if (!k) return;
    // Sections are UI groups; child keys are relative to the parent row.
    if (p.type === 'section' || p.type === 'section_container') {
      return;
    }
    if (p.type === 'array' || p.type === 'nested_array' || p.type === 'simple_array') {
      row[k] = [];
    } else if (p.type === 'boolean') {
      row[k] = null;
    } else {
      row[k] = '';
    }
  });
  return row;
}
