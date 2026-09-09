import { reactive, toRaw } from 'vue';
import { get as lodashGet, set as lodashSet, cloneDeep } from 'lodash';

/**
 * Nested metadata form store (dot-path get/set on a reactive object).
 */
export function createMetadataFormStore(initial = {}) {
  const state = reactive({
    data: cloneDeep(initial && typeof initial === 'object' ? initial : {}),
  });

  function getValue(path) {
    if (path == null || path === '') return undefined;
    return lodashGet(state.data, path);
  }

  function ensurePath(path) {
    const parts = String(path).split('.');
    let cur = state.data;
    for (let i = 0; i < parts.length - 1; i++) {
      const part = parts[i];
      const next = parts[i + 1];
      const nextIsIndex = /^\d+$/.test(next);
      const curVal = cur[part];
      if (curVal === undefined || curVal === null || typeof curVal !== 'object') {
        cur[part] = nextIsIndex ? [] : {};
      } else if (nextIsIndex && !Array.isArray(curVal)) {
        cur[part] = [];
      }
      cur = cur[part];
    }
    return { parent: cur, last: parts[parts.length - 1] };
  }

  function setValue(path, value) {
    if (path == null || path === '') return;
    const { parent, last } = ensurePath(path);
    parent[last] = value;
  }

  function replaceData(obj) {
    const next = cloneDeep(obj && typeof obj === 'object' ? obj : {});
    // Clear keys then assign so Vue keeps the same reactive root when possible
    Object.keys(state.data).forEach((k) => {
      delete state.data[k];
    });
    Object.assign(state.data, next);
  }

  function getPayload() {
    return cloneDeep(toRaw(state.data));
  }

  return {
    state,
    getValue,
    setValue,
    replaceData,
    getPayload,
  };
}

export function pathJoin(base, key) {
  if (!base) return key;
  if (key == null || key === '') return base;
  return `${base}.${key}`;
}

/** Strip empty strings/arrays/objects for cleaner payloads (light cleanup). */
export function pruneEmpty(value) {
  if (Array.isArray(value)) {
    const next = value.map(pruneEmpty).filter((v) => v !== undefined && v !== null && v !== '');
    return next.length ? next : undefined;
  }
  if (value && typeof value === 'object') {
    const out = {};
    Object.keys(value).forEach((k) => {
      if (k === 'merge_options') {
        out[k] = value[k];
        return;
      }
      const pruned = pruneEmpty(value[k]);
      if (pruned !== undefined && pruned !== null && pruned !== '') {
        out[k] = pruned;
      }
    });
    return Object.keys(out).length ? out : undefined;
  }
  return value;
}
