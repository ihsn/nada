<script setup>
/**
 * Interactive bounding box: OSM map + west/east/south/north fields.
 * Option keys are relative to parentPath (the nested_array row).
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { pathJoin } from '../composables/createMetadataFormStore';
import { useMetadataFormStore } from '../composables/useMetadataFormStore';
import { useMetadataFormLabels } from '../composables/useMetadataFormLabels';
import { resolveBoundingBoxSides } from '../utils/enumOptions';
import { loadLeaflet } from '../utils/loadLeaflet';
import MetadataFieldInput from './MetadataFieldInput.vue';

const props = defineProps({
  field: { type: Object, required: true },
  parentPath: { type: String, required: true },
});

const store = useMetadataFormStore();
const labels = useMetadataFormLabels();
const mapEl = ref(null);
const drawing = ref(false);
const mapError = ref('');

const sides = computed(() => resolveBoundingBoxSides(props.field));
const sideOrder = ['west', 'east', 'south', 'north'];
const visibleSides = computed(() => sideOrder.filter((side) => sides.value[side]));

function sidePath(side) {
  const spec = sides.value[side];
  return spec ? pathJoin(props.parentPath, spec.key) : '';
}

function parseCoord(value) {
  if (value === '' || value == null) return null;
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
}

const coords = computed(() => ({
  west: parseCoord(store.getValue(sidePath('west'))),
  east: parseCoord(store.getValue(sidePath('east'))),
  south: parseCoord(store.getValue(sidePath('south'))),
  north: parseCoord(store.getValue(sidePath('north'))),
}));

const hasBounds = computed(() =>
  sideOrder.every((side) => Number.isFinite(coords.value[side]))
);

function writeCoord(side, raw) {
  const spec = sides.value[side];
  const path = sidePath(side);
  if (!spec || !path) return;
  if (raw === '' || raw == null) {
    store.setValue(path, '');
    return;
  }
  const n = Number(raw);
  if (!Number.isFinite(n)) return;
  const rounded = Math.round(n * 1e6) / 1e6;
  const t = spec.field?.type;
  store.setValue(path, t === 'integer' ? Math.round(rounded) : rounded);
}

function applyLatLngBounds(bounds) {
  const south = bounds.getSouth();
  const north = bounds.getNorth();
  const west = bounds.getWest();
  const east = bounds.getEast();
  writeCoord('south', south);
  writeCoord('north', north);
  writeCoord('west', west);
  writeCoord('east', east);
}

function clearBox() {
  sideOrder.forEach((side) => writeCoord(side, ''));
}

let L = null;
let map = null;
let rect = null;
let previewRect = null;
let startLatLng = null;
let draggingBox = false;

function leafletBoundsFromCoords(c) {
  if (!L) return null;
  if (![c.west, c.east, c.south, c.north].every((n) => Number.isFinite(n))) return null;
  return L.latLngBounds(
    [Math.min(c.south, c.north), c.west],
    [Math.max(c.south, c.north), c.east]
  );
}

function syncRectangle(c, { fit = false } = {}) {
  if (!map || !L) return;
  const bounds = leafletBoundsFromCoords(c);
  if (!bounds) {
    if (rect) {
      map.removeLayer(rect);
      rect = null;
    }
    return;
  }
  if (!rect) {
    rect = L.rectangle(bounds, {
      color: '#1976d2',
      weight: 2,
      fillColor: '#1976d2',
      fillOpacity: 0.12,
    }).addTo(map);
  } else {
    rect.setBounds(bounds);
  }
  if (fit) {
    map.fitBounds(bounds.pad(0.2), { maxZoom: 8 });
  }
}

function setDrawCursor(on) {
  if (!map) return;
  map.getContainer().style.cursor = on ? 'crosshair' : '';
}

function beginDraw() {
  drawing.value = true;
  if (!map) return;
  map.dragging.disable();
  map.boxZoom.disable();
  setDrawCursor(true);
}

function endDraw() {
  drawing.value = false;
  draggingBox = false;
  startLatLng = null;
  if (previewRect && map) {
    map.removeLayer(previewRect);
    previewRect = null;
  }
  if (!map) return;
  map.dragging.enable();
  map.boxZoom.enable();
  setDrawCursor(false);
}

function onMapMouseDown(e) {
  if (!drawing.value || !L) return;
  draggingBox = true;
  startLatLng = e.latlng;
}

function onMapMouseMove(e) {
  if (!drawing.value || !draggingBox || !startLatLng || !L || !map) return;
  const bounds = L.latLngBounds(startLatLng, e.latlng);
  if (!previewRect) {
    previewRect = L.rectangle(bounds, {
      color: '#1976d2',
      weight: 1,
      dashArray: '4 4',
      fillOpacity: 0.08,
    }).addTo(map);
  } else {
    previewRect.setBounds(bounds);
  }
}

function onMapMouseUp(e) {
  if (!drawing.value || !draggingBox || !startLatLng || !L) {
    draggingBox = false;
    return;
  }
  const bounds = L.latLngBounds(startLatLng, e.latlng);
  const tiny =
    Math.abs(bounds.getSouth() - bounds.getNorth()) < 1e-8 &&
    Math.abs(bounds.getWest() - bounds.getEast()) < 1e-8;
  if (!tiny) {
    applyLatLngBounds(bounds);
    syncRectangle(
      {
        west: bounds.getWest(),
        east: bounds.getEast(),
        south: bounds.getSouth(),
        north: bounds.getNorth(),
      },
      { fit: true }
    );
  }
  endDraw();
}

async function initMap() {
  if (!mapEl.value) return;
  try {
    L = await loadLeaflet();
  } catch (e) {
    mapError.value = e?.message || 'Map unavailable';
    return;
  }
  map = L.map(mapEl.value, {
    worldCopyJump: true,
    scrollWheelZoom: true,
  });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    minZoom: 1,
    maxZoom: 18,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  if (hasBounds.value) {
    map.setView([0, 0], 2);
    syncRectangle(coords.value, { fit: true });
  } else {
    map.setView([20, 0], 2);
  }

  map.on('mousedown', onMapMouseDown);
  map.on('mousemove', onMapMouseMove);
  map.on('mouseup', onMapMouseUp);
  document.addEventListener('mouseup', onDocMouseUp);
  await nextTick();
  map.invalidateSize();
}

function onDocMouseUp(e) {
  if (!drawing.value || !draggingBox || !map || !L || !startLatLng) return;
  const container = map.getContainer();
  if (e.target && container.contains(e.target)) return;
  const point = map.mouseEventToLatLng(e);
  onMapMouseUp({ latlng: point });
}

onMounted(async () => {
  await nextTick();
  await initMap();
});

onBeforeUnmount(() => {
  document.removeEventListener('mouseup', onDocMouseUp);
  endDraw();
  if (map) {
    map.off();
    map.remove();
    map = null;
  }
  rect = null;
  previewRect = null;
  L = null;
});

watch(
  coords,
  (c) => {
    syncRectangle(c, { fit: false });
  },
  { deep: true }
);

function onToggleDraw() {
  if (drawing.value) {
    endDraw();
  } else {
    beginDraw();
  }
}
</script>

<template>
  <div class="mf-bbox">
    <p class="text-caption text-medium-emphasis mb-2">
      {{ drawing ? labels.boundingBoxDrawHint : labels.boundingBoxHint }}
    </p>
    <v-alert v-if="mapError" type="warning" variant="tonal" density="compact" class="mb-2">
      {{ mapError }}
    </v-alert>
    <div v-show="!mapError" ref="mapEl" class="mf-bbox-map" />
    <div class="d-flex ga-2 flex-wrap mb-3 mt-2">
      <v-btn
        v-if="!mapError"
        size="small"
        class="text-none"
        :color="drawing ? 'warning' : 'primary'"
        :variant="drawing ? 'flat' : 'tonal'"
        prepend-icon="mdi-vector-rectangle"
        @click="onToggleDraw"
      >
        {{ drawing ? labels.cancelDrawBoundingBox : labels.drawBoundingBox }}
      </v-btn>
      <v-btn
        size="small"
        variant="text"
        class="text-none"
        :disabled="!hasBounds"
        prepend-icon="mdi-close"
        @click="clearBox"
      >
        {{ labels.clearBoundingBox }}
      </v-btn>
    </div>
    <div class="mf-bbox-fields">
      <MetadataFieldInput
        v-for="side in visibleSides"
        :key="side"
        :field="sides[side].field"
        :path="sidePath(side)"
      />
    </div>
  </div>
</template>

<style scoped>
.mf-bbox {
  margin-bottom: 8px;
}
.mf-bbox-map {
  height: 300px;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  background: #dcdcdc;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  z-index: 0;
}
.mf-bbox-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 16px;
}
@media (max-width: 600px) {
  .mf-bbox-fields {
    grid-template-columns: 1fr;
  }
}
</style>
