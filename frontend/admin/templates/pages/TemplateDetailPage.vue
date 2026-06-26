<template>
  <div class="dt-detail">
    <v-progress-linear v-if="loadingDetail" class="mb-2" indeterminate color="primary" rounded />
    <v-alert v-if="detailErr" type="error" rounded="lg" class="mb-3 mx-4 mx-md-6">{{ detailErr }}</v-alert>

    <template v-if="model">
      <div class="dt-editor-frame">
        <v-sheet class="dt-detail-toolbar" elevation="0" rounded="0">
          <div class="dt-detail-toolbar-inner d-flex flex-wrap align-center ga-3 py-3 px-4 px-md-6">
            <div class="d-flex flex-column flex-grow-1 min-width-0">
              <span class="text-h6 font-weight-semibold text-truncate">{{ form.name }}</span>
              <span class="text-caption text-medium-emphasis text-truncate">Display template · UID {{ model.uid }}</span>
            </div>
            <div class="d-flex flex-wrap ga-2">
              <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save" variant="flat" @click="save">
                Save<span class="font-weight-regular dt-dirty-marker" :class="{ 'is-dirty': dirty }"> *</span>
              </v-btn>
              <v-btn variant="tonal" prepend-icon="mdi-check-decagram-outline" :loading="validating" @click="runValidate">
                Validate
              </v-btn>
              <v-btn variant="tonal" prepend-icon="mdi-download-outline" @click="downloadExport">Export</v-btn>
              <v-divider inset vertical class="hidden-sm-and-down opacity-25" />
              <v-btn variant="tonal" prepend-icon="mdi-content-copy" @click="duplicate">Duplicate</v-btn>
              <v-btn variant="tonal" prepend-icon="mdi-star-outline" @click="setDefault">Set default</v-btn>
              <v-btn color="error" variant="text" prepend-icon="mdi-delete-outline" @click="deletePrompt = true">Delete</v-btn>
            </div>
          </div>
        </v-sheet>

        <div class="dt-detail-window bg-background">
          <div class="dt-structure-body">
            <div class="dt-sidebar-scroll">
              <DisplayTemplateTreePanel
                :nodes="displayRoot.sections"
                :expanded="expanded"
                :selected-tid="selectedTid"
                :dragging-tid="draggingTid"
                :hover-drop="hoverDrop"
                :action-flags="actionFlags"
                @select-template-root="selectTemplateRoot"
                @select-description="selectDescription"
                @select-node="selectNode($event)"
                @toggle="toggleExpanded"
                @tree-action="onTreeAction"
                @drag-start="onDragStart"
                @drag-end="onDragEnd"
                @drag-hover="onDragHover"
                @node-drop="onNodeDrop"
              />
            </div>
            <div class="dt-main-scroll">
              <v-sheet border rounded="lg" class="pa-4 pa-md-6 dt-inspector-sheet">
                <DisplayTemplateInspector
                  :selection-kind="selectionKind"
                  :display-root="displayRoot"
                  :form="form"
                  :model="model"
                  :selected-node="selectedNode"
                  :renderers="renderers"
                  @dirty="markDirty"
                  @request-delete="confirmRemoveNode"
                />
              </v-sheet>
            </div>
          </div>
          <v-alert
            v-if="validationMsg"
            :type="validationOk ? 'success' : 'warning'"
            rounded="lg"
            density="comfortable"
            class="mx-4 mx-md-6 mt-0 mb-4 flex-shrink-0"
            closable
            @click:close="clearValidationMsg"
          >
            <pre class="dt-validation-pre text-body-2 overflow-x-auto ma-0">{{ validationMsg }}</pre>
          </v-alert>
        </div>
      </div>
    </template>

    <v-dialog v-model="deletePrompt" max-width="420" persistent>
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-6 px-6">Delete this template?</v-card-title>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="deletePrompt = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="deleting" @click="doDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="removeNodePrompt" max-width="440">
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-6 px-6">Remove node?</v-card-title>
        <v-card-text class="text-body-1">
          Remove <strong>{{ removeNodePreview }}</strong> from the layout? Children are removed with it.
        </v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="removeNodePrompt = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="doRemoveNode">Remove</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { computed, inject, nextTick, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTemplatesApi } from '../composables/useTemplatesApi';
import DisplayTemplateTreePanel from '../components/DisplayTemplateTreePanel.vue';
import DisplayTemplateInspector from '../components/DisplayTemplateInspector.vue';
import {
  cloneJson,
  cloneSubtree,
  ensureTreeIds,
  expandAllContainers,
  findNearestSectionGroup,
  findNodeContextWithParent,
  moveNode,
  newLeafField,
  newSection,
  newSectionGroup,
  normalizeDisplayRoot,
  stripTreeIds,
  swapSiblingOrder,
  VIRTUAL_DESCRIPTION_TID,
  VIRTUAL_TEMPLATE_ROOT_TID,
} from '../utils/displayTemplateTree';

defineOptions({ name: 'TemplateDetailPage' });

const props = defineProps({
  uid: { type: String, required: true },
});

const setMessage = inject('setMessage', () => {});
const route = useRoute();
const router = useRouter();

const {
  fetchTemplate,
  updateTemplate,
  duplicateTemplate,
  deleteTemplate,
  setDefaultTemplate,
  validatePayload,
  fetchExport,
  fetchRenderers,
} = useTemplatesApi();

const renderers = ref([]);
const model = ref(null);
const loadingDetail = ref(false);
const detailErr = ref('');
const saving = ref(false);
const validating = ref(false);
const validationMsg = ref('');
const validationOk = ref(false);
const deletePrompt = ref(false);
const deleting = ref(false);
const dirty = ref(false);
const suppressStructureDirty = ref(false);

const form = ref({
  name: '',
  data_type: '',
  version: '',
  status: 'draft',
  template_type: 'custom',
  organization: '',
  author: '',
  description: '',
});

const displayRoot = ref(normalizeDisplayRoot({}));
const expanded = reactive({});
/** @type {import('vue').Ref<'template_root'|'description'|'node'>} */
const selectionKind = ref('template_root');
const selectedNode = ref(null);

const draggingTid = ref('');
const hoverDrop = ref(null);

const uidEffective = computed(() => props.uid || route.params.uid);

const selectedTid = computed(() => {
  if (selectionKind.value === 'template_root') {
    return VIRTUAL_TEMPLATE_ROOT_TID;
  }
  if (selectionKind.value === 'description') {
    return VIRTUAL_DESCRIPTION_TID;
  }
  return selectedNode.value?._tid || '';
});

const actionFlags = computed(() => {
  const sk = selectionKind.value;
  const node = selectedNode.value;
  const tid = node?._tid;

  let up = false;
  let down = false;
  if (sk === 'node' && tid) {
    const ctx = findNodeContextWithParent(displayRoot.value.sections, tid);
    if (ctx) {
      up = ctx.index > 0;
      down = ctx.index < ctx.siblings.length - 1;
    }
  }

  const canAddField =
    sk === 'node' &&
    node &&
    (node.node_type === 'section' || (node.node_type === 'field' && node.source_type === 'array'));

  return {
    canAddGroup: true,
    canAddSection: true,
    canAddField,
    canClone: sk === 'node' && !!tid,
    canRemove: sk === 'node' && !!tid,
    canMoveUp: up,
    canMoveDown: down,
  };
});

const removeNodePrompt = ref(false);
let removePendingTid = '';

const removeNodePreview = computed(() => {
  if (!selectedNode.value) return '';
  return selectedNode.value.title || selectedNode.value.key || 'node';
});

function markDirty() {
  dirty.value = true;
}

function clearValidationMsg() {
  validationMsg.value = '';
}

function selectTemplateRoot() {
  selectionKind.value = 'template_root';
  selectedNode.value = null;
}

function selectDescription() {
  selectionKind.value = 'description';
  selectedNode.value = null;
}

function selectNode(node) {
  selectionKind.value = 'node';
  selectedNode.value = node;
}

function toggleExpanded(tid) {
  expanded[tid] = !expanded[tid];
}

watch(
  displayRoot,
  () => {
    if (!suppressStructureDirty.value) markDirty();
  },
  { deep: true }
);

function hydrateDisplayRoot(templateJsonObj) {
  suppressStructureDirty.value = true;
  const r = normalizeDisplayRoot(templateJsonObj);
  ensureTreeIds(r.sections);
  r.data_type = r.data_type || form.value.data_type;
  displayRoot.value = r;
  Object.keys(expanded).forEach((k) => delete expanded[k]);
  expandAllContainers(displayRoot.value.sections, expanded);
  expanded[VIRTUAL_TEMPLATE_ROOT_TID] = true;
  nextTick(() => {
    suppressStructureDirty.value = false;
  });
}

function tp(v) {
  if (typeof v === 'object' && v !== null && !Array.isArray(v)) return v;
  try {
    return JSON.parse(String(v ?? '{}'));
  } catch {
    return {};
  }
}

function applyModel(t) {
  form.value.name = t.name || '';
  form.value.data_type = t.data_type || '';
  form.value.version = t.version || '';
  form.value.status = t.status || 'draft';
  form.value.template_type = t.template_type || 'custom';
  form.value.organization = t.organization != null ? String(t.organization) : '';
  form.value.author = t.author != null ? String(t.author) : '';
  form.value.description = t.description != null ? String(t.description) : '';

  hydrateDisplayRoot(tp(t.template_json));

  selectTemplateRoot();
  validationMsg.value = '';
  dirty.value = false;
}

function buildTemplateJsonObject() {
  const o = stripTreeIds(cloneJson(displayRoot.value));
  o.data_type = form.value.data_type;
  return o;
}

watch(
  uidEffective,
  async (uid) => {
    if (!uid) return;
    loadingDetail.value = true;
    detailErr.value = '';
    validationMsg.value = '';
    model.value = null;
    selectTemplateRoot();
    try {
      renderers.value = await fetchRenderers();
    } catch {
      renderers.value = [];
    }
    try {
      const t = await fetchTemplate(uid);
      model.value = t;
      applyModel(t);
    } catch (e) {
      detailErr.value = e?.message || String(e);
    } finally {
      loadingDetail.value = false;
    }
  },
  { immediate: true }
);

function onDragStart(tid) {
  draggingTid.value = tid;
  hoverDrop.value = null;
}

function onDragEnd() {
  draggingTid.value = '';
  hoverDrop.value = null;
}

function onDragHover(payload) {
  hoverDrop.value = payload;
}

function onNodeDrop({ dragTid, targetTid, zone }) {
  draggingTid.value = '';
  hoverDrop.value = null;
  const ok = moveNode(displayRoot.value.sections, dragTid, targetTid, zone);
  if (!ok) {
    setMessage('Could not move that node here.', 'warning');
    return;
  }
  if (zone === 'into') {
    expanded[targetTid] = true;
  }
  markDirty();
}

function onTreeAction(id) {
  switch (id) {
    case 'add-group':
      addSectionGroup();
      break;
    case 'add-section':
      addSection();
      break;
    case 'add-field':
      addField();
      break;
    case 'clone':
      cloneSubtreeBelow();
      break;
    case 'remove':
      confirmRemoveNode();
      break;
    case 'move-up':
      moveSelectedSibling('up');
      break;
    case 'move-down':
      moveSelectedSibling('down');
      break;
    default:
      break;
  }
}

function moveSelectedSibling(direction) {
  const tid = selectedNode.value?._tid;
  if (!tid) return;
  if (swapSiblingOrder(displayRoot.value.sections, tid, direction === 'up' ? 'up' : 'down')) {
    markDirty();
  }
}

function addField() {
  const node = selectedNode.value;
  if (!node) {
    setMessage('Select a section or a table (array) field first.', 'info');
    return;
  }
  const leaf = newLeafField(`field_${Date.now()}`, 'New field');
  if (node.node_type === 'section') {
    if (!Array.isArray(node.fields)) node.fields = [];
    node.fields.push(leaf);
    expanded[node._tid] = true;
    markDirty();
    selectNode(leaf);
    return;
  }
  if (node.node_type === 'field' && node.source_type === 'array') {
    if (!Array.isArray(node.fields)) node.fields = [];
    const sub = newLeafField(`${node.key}.column_${Date.now()}`, 'New column');
    sub.is_prop = true;
    sub.parent_array_key = node.key;
    sub.path = sub.key;
    node.fields.push(sub);
    expanded[node._tid] = true;
    markDirty();
    selectNode(sub);
    return;
  }
  setMessage('Select a section or a table field to add a field.', 'info');
}

function addSectionGroup() {
  const sections = displayRoot.value.sections;
  const g = newSectionGroup(`section_group_${Date.now()}`, 'New section group');
  const tid = selectedTid.value;
  if (tid && tid !== VIRTUAL_DESCRIPTION_TID) {
    const ctx = findNodeContextWithParent(sections, tid);
    if (ctx && !ctx.parentNode) {
      sections.splice(ctx.index + 1, 0, g);
      expanded[g._tid] = true;
      markDirty();
      selectNode(g);
      return;
    }
  }
  sections.push(g);
  expanded[g._tid] = true;
  markDirty();
  selectNode(g);
}

function addSection() {
  const sections = displayRoot.value.sections;
  const tid = selectedTid.value;
  const sec = newSection(`section_${Date.now()}`, 'New section');

  if (tid && tid !== VIRTUAL_DESCRIPTION_TID) {
    const grp = findNearestSectionGroup(sections, tid);
    if (grp) {
      if (!Array.isArray(grp.sections)) grp.sections = [];
      grp.sections.push(sec);
      expanded[grp._tid] = true;
      markDirty();
      selectNode(sec);
      return;
    }
  }

  const g = newSectionGroup(`section_group_${Date.now()}`, 'New section group');
  g.sections.push(sec);
  sections.push(g);
  expanded[g._tid] = true;
  markDirty();
  selectNode(sec);
}

function cloneSubtreeBelow() {
  const tid = selectedTid.value;
  if (!tid || tid === VIRTUAL_DESCRIPTION_TID || tid === VIRTUAL_TEMPLATE_ROOT_TID) return;
  const ctx = findNodeContextWithParent(displayRoot.value.sections, tid);
  if (!ctx) return;
  const copy = cloneSubtree(ctx.node);
  ctx.siblings.splice(ctx.index + 1, 0, copy);
  expanded[copy._tid] = true;
  markDirty();
  selectNode(copy);
}

async function save() {
  saving.value = true;
  validationMsg.value = '';
  try {
    let template_json;
    try {
      template_json = buildTemplateJsonObject();
    } catch (e) {
      setMessage(e?.message || String(e), 'error');
      saving.value = false;
      return;
    }
    const updated = await updateTemplate(uidEffective.value, {
      name: (form.value.name || '').trim(),
      status: form.value.status,
      version: form.value.version || null,
      organization: (form.value.organization || '').trim() || null,
      author: (form.value.author || '').trim() || null,
      description: (form.value.description || '').trim() || null,
      template_json,
    });
    model.value = updated || model.value;
    if (updated) applyModel(updated);
    setMessage('Saved.', 'success');
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    saving.value = false;
  }
}

async function runValidate() {
  validating.value = true;
  validationMsg.value = '';
  try {
    let template_json;
    try {
      template_json = buildTemplateJsonObject();
    } catch (e) {
      validationOk.value = false;
      validationMsg.value = e?.message || String(e);
      setMessage(validationMsg.value, 'error');
      validating.value = false;
      return;
    }
    const result = await validatePayload({
      uid: uidEffective.value,
      name: form.value.name,
      data_type: form.value.data_type,
      template_json,
    });
    validationOk.value = !!(result.valid ?? result?.ok ?? true);
    validationMsg.value = typeof result === 'string' ? result : JSON.stringify(result, null, 2);
  } catch (e) {
    validationOk.value = false;
    validationMsg.value = e?.message || String(e);
    setMessage(validationMsg.value, 'error');
  } finally {
    validating.value = false;
  }
}

async function duplicate() {
  try {
    const t = await duplicateTemplate(uidEffective.value);
    setMessage('Duplicated.', 'success');
    if (t?.uid) router.replace({ name: 'template-detail', params: { uid: t.uid } });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

async function setDefault() {
  try {
    await setDefaultTemplate(form.value.data_type, uidEffective.value);
    setMessage('Default updated.', 'success');
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

async function doDelete() {
  deleting.value = true;
  try {
    await deleteTemplate(uidEffective.value);
    deletePrompt.value = false;
    setMessage('Deleted.', 'success');
    router.push({ name: 'templates' });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    deleting.value = false;
  }
}

async function downloadExport() {
  try {
    const res = await fetchExport(uidEffective.value);
    const blob = new Blob([JSON.stringify(res, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `display-template-${uidEffective.value}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

function confirmRemoveNode() {
  if (selectionKind.value !== 'node') return;
  const tid = selectedNode.value?._tid;
  if (!tid) return;
  removePendingTid = tid;
  removeNodePrompt.value = true;
}

function doRemoveNode() {
  const ctx = findNodeContextWithParent(displayRoot.value.sections, removePendingTid);
  if (ctx) ctx.siblings.splice(ctx.index, 1);
  removeNodePrompt.value = false;
  removePendingTid = '';
  selectTemplateRoot();
  markDirty();
}
</script>

<style scoped>
.dt-detail {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  max-height: 100%;
  overflow: hidden;
}
.dt-editor-frame {
  display: grid;
  grid-template-rows: auto minmax(0, 1fr);
  flex: 1 1 0;
  height: auto;
  max-height: 100%;
  min-height: 0;
  overflow: hidden;
}
.dt-detail-toolbar {
  z-index: 8;
  width: 100%;
  min-height: 0;
  margin: 0;
  background: #fafbfc;
  border: none;
  box-shadow: none;
}
.dt-detail-window {
  width: 100%;
  margin: 0;
  border: none;
  border-radius: 0;
  height: 100%;
  max-height: 100%;
  min-height: 0;
  overflow: hidden;
  display: grid;
  grid-template-rows: 1fr auto;
}
.dt-structure-body {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(260px, min(400px, 30vw)) minmax(0, 1fr);
  grid-template-rows: minmax(0, 1fr);
  gap: 12px 16px;
  padding: 12px 16px;
  align-items: stretch;
  box-sizing: border-box;
  overflow: hidden;
}
.dt-sidebar-scroll {
  min-width: 0;
  min-height: 0;
  height: 100%;
  max-height: 100%;
  align-self: stretch;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.dt-main-scroll {
  min-width: 0;
  min-height: 0;
  height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.min-width-0 {
  min-width: 0;
}
.dt-validation-pre {
  font-family: ui-monospace, monospace;
  font-size: 0.85rem;
  line-height: 1.4;
  white-space: pre-wrap;
}
.dt-dirty-marker {
  visibility: hidden;
}
.dt-dirty-marker.is-dirty {
  visibility: visible;
}
.dt-inspector-sheet {
  min-height: 100%;
  box-sizing: border-box;
}
.font-json :deep(textarea) {
  font-family: ui-monospace, monospace;
  font-size: 0.85rem;
  line-height: 1.4;
}
.bg-background {
  background: rgb(var(--v-theme-background));
}
@media (max-width: 959.98px) {
  .dt-structure-body {
    grid-template-columns: 1fr;
    grid-template-rows: minmax(200px, 36vh) minmax(200px, 1fr);
  }
}
</style>
