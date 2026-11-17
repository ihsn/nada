<template>
  <svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg" class="sh-thumb" aria-hidden="true">
    <rect x="0" y="0" width="160" height="64" :fill="headerBg" />
    <g v-for="row in layout" :key="row.id">
      <rect v-if="row.bg" x="0" :y="row.y" width="160" :height="row.h" :fill="row.bg" />
      <line
        v-if="row.border"
        x1="0"
        x2="160"
        :y1="row.y + row.h - 0.5"
        :y2="row.y + row.h - 0.5"
        :stroke="row.borderColor || 'currentColor'"
        :stroke-opacity="row.borderColor ? 1 : 0.2"
        :stroke-width="row.borderWidth || 1"
      />
      <template v-for="(el, i) in row.els" :key="i">
        <rect v-if="el.kind === 'rect'" :x="el.x" :y="el.y" :width="el.w" :height="el.h" rx="1.5" :fill="row.fg" :fill-opacity="el.o" />
        <circle v-else :cx="el.x" :cy="el.y" :r="el.r" :fill="row.fg" :fill-opacity="el.o" />
      </template>
    </g>
  </svg>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'ShPresetThumb' });

const props = defineProps({
  rows: { type: Object, required: true },
  scope: { type: String, default: 'header' },
  headerStyle: { type: Object, default: () => ({}) },
});

/** Width of each item drawn in the thumbnail */
const WIDTH = { brand: 34, menu: 44, login: 8, language: 16, search: 28, text: 16 };

function draw(item, x, cy) {
  switch (item.type) {
    case 'brand':
      return [
        { kind: 'rect', x, y: cy - 5, w: 30, h: 6, o: 0.85 },
        { kind: 'rect', x, y: cy + 2.5, w: 18, h: 2.5, o: 0.4 },
      ];
    case 'menu':
      return [0, 1, 2].map((k) => ({ kind: 'rect', x: x + k * 15, y: cy - 2, w: 12, h: 4, o: 0.55 }));
    case 'login':
      return [{ kind: 'circle', x: x + 4, y: cy, r: 3.2, o: 0.7 }];
    case 'language':
      return [{ kind: 'rect', x, y: cy - 1.5, w: 16, h: 3, o: 0.4 }];
    case 'search':
      return [
        { kind: 'rect', x, y: cy - 3, w: 22, h: 6, o: 0.35 },
        { kind: 'circle', x: x + 24, y: cy, r: 2.5, o: 0.55 },
      ];
    default:
      return [{ kind: 'rect', x, y: cy - 1.5, w: 16, h: 3, o: 0.5 }];
  }
}

const headerBg = computed(() => (props.scope === 'header' && props.headerStyle?.bg_color) || 'transparent');

const layout = computed(() => {
  const ids = ['top', 'main', 'bottom'].filter((id) => {
    const r = props.rows?.[id];
    return r?.enabled && (r.left?.length || r.center?.length || r.right?.length);
  });
  const weight = (id) => {
    const st = props.rows[id].style || {};
    const base = id === 'main' ? 26 : 13;
    return Math.max(base, Math.min(60, (st.min_height || 0) / 4));
  };
  const total = ids.reduce((sum, id) => sum + weight(id), 0) || 1;
  let y = 0;
  return ids.map((id) => {
    const r = props.rows[id];
    const st = r.style || {};
    const h = (weight(id) / total) * 64;
    const cy = y + h / 2;
    const els = [];
    let x = 6;
    for (const it of r.left || []) {
      els.push(...draw(it, x, cy));
      x += WIDTH[it.type] + 4;
    }
    let xr = 154;
    for (const it of [...(r.right || [])].reverse()) {
      xr -= WIDTH[it.type];
      els.push(...draw(it, xr, cy));
      xr -= 4;
    }
    const center = r.center || [];
    let xc = 80 - (center.reduce((s, it) => s + WIDTH[it.type] + 4, 0) - 4) / 2;
    for (const it of center) {
      els.push(...draw(it, xc, cy));
      xc += WIDTH[it.type] + 4;
    }
    const ownBg = props.scope === 'rows' && st.bg_color;
    const fg = (props.scope === 'rows' ? st.text_color : props.headerStyle?.text_color) || 'currentColor';
    const row = {
      id,
      y,
      h,
      bg: ownBg || '',
      border: !!st.border_bottom,
      borderColor: st.border_bottom_color || '',
      borderWidth: Math.max(1, Math.min(3, Number(st.border_bottom_width) || 1)),
      fg,
      els,
    };
    y += h;
    return row;
  });
});
</script>

<style scoped>
.sh-thumb {
  display: block;
  width: 100%;
  height: 100%;
}
</style>
