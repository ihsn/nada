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
