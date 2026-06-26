/**
 * Active filter chip backgrounds — curated for white text (WCAG AA–friendly, dark/mid tones).
 */
export const FILTER_CHIP_BACKGROUNDS = [
  '#5e5e5e',
  '#757575',
  '#455a64',
  '#37474f',
  '#1565c0',
  '#0d47a1',
  '#283593',
  '#4527a0',
  '#6a1b9a',
  '#ad1457',
  '#c62828',
  '#b71c1c',
  '#bf360c',
  '#d84315',
  '#2e7d32',
  '#1b5e20',
  '#558b2f',
  '#00695c',
  '#004d40',
  '#4e342e',
];

/** Stable order for known catalog filter keys → palette index. */
const FILTER_TYPE_ORDER = [
  'sk',
  'country',
  'region',
  'collection',
  'dtype',
  'data_class',
  'tag',
  'year',
];

function hashString(str) {
  let h = 0;
  for (let i = 0; i < str.length; i++) {
    h = (h * 31 + str.charCodeAt(i)) | 0;
  }
  return Math.abs(h);
}

/**
 * Palette index for an active filter chip.
 * Known filter types use a fixed slot; user facets and unknown keys hash chipKey.
 */
export function activeFilterChipColorIndex(key, chipKey, { isYear = false } = {}) {
  const lookupKey = isYear ? 'year' : key;
  const typeIndex = FILTER_TYPE_ORDER.indexOf(lookupKey);
  if (typeIndex >= 0) {
    return typeIndex % FILTER_CHIP_BACKGROUNDS.length;
  }
  return hashString(chipKey) % FILTER_CHIP_BACKGROUNDS.length;
}

export function activeFilterChipBackground(colorIndex) {
  const i = Number(colorIndex);
  if (!Number.isFinite(i)) return FILTER_CHIP_BACKGROUNDS[0];
  return FILTER_CHIP_BACKGROUNDS[((i % FILTER_CHIP_BACKGROUNDS.length) + FILTER_CHIP_BACKGROUNDS.length) % FILTER_CHIP_BACKGROUNDS.length];
}
