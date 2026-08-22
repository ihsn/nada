<template>
  <div class="dt-detail">
    <v-progress-linear v-if="loadingDetail" class="mb-2" indeterminate color="primary" rounded />
    <v-alert v-if="detailErr" type="error" rounded="lg" class="mb-3 mx-4 mx-md-6">{{ detailErr }}</v-alert>

    <template v-if="model">
      <div class="dt-editor-frame">
        <div class="dt-detail-toolbar">
          <div class="dt-detail-toolbar-inner d-flex flex-wrap align-center ga-3 py-3 px-4 px-md-6">
            <v-btn
              icon="mdi-arrow-left"
              variant="text"
              size="small"
              class="dt-back-to-index flex-shrink-0"
              :to="{ name: 'display-templates' }"
              title="Back to display templates"
              aria-label="Back to display templates"
            />
            <div class="d-flex flex-column flex-grow-1 min-width-0">
              <span class="text-h6 font-weight-semibold text-truncate">{{ form.name }}</span>
              <span class="text-caption text-medium-emphasis text-truncate">
                {{ model.uid }}<template v-if="isSystemCore"> · system core (read-only)</template>
              </span>
            </div>
            <div class="d-flex flex-wrap align-center ga-2">
              <v-btn color="primary" :loading="saving" :disabled="isSystemCore" prepend-icon="mdi-content-save" variant="flat" @click="save">
                Save<span class="font-weight-regular dt-dirty-marker" :class="{ 'is-dirty': dirty }"> *</span>
              </v-btn>
              <v-btn variant="text" @click="cancelEdit">Cancel</v-btn>
              <v-menu location="bottom end" transition="scale-transition">
                <template #activator="{ props: menuProps }">
                  <v-btn
                    icon="mdi-dots-vertical"
                    variant="text"
                    v-bind="menuProps"
                    title="More actions"
                  />
                </template>
                <v-list density="compact" min-width="220">
                  <v-list-item
                    prepend-icon="mdi-check-decagram-outline"
                    title="Validate"
                    :disabled="validating"
                    @click="runValidate"
                  />
                  <v-list-item
                    prepend-icon="mdi-download-outline"
                    title="Export"
                    @click="downloadExport"
                  />
                  <v-list-item
                    prepend-icon="mdi-content-copy"
                    title="Duplicate"
                    @click="duplicate"
                  />
                  <v-list-item
                    prepend-icon="mdi-star-outline"
                    :title="defaultActionTitle"
                    :disabled="!canSetAsDefault"
                    @click="defaultPrompt = true"
                  />
                  <v-divider class="my-1" />
                  <v-list-item
                    prepend-icon="mdi-delete-outline"
                    title="Delete"
                    base-color="error"
                    :disabled="isSystemCore"
                    @click="deletePrompt = true"
                  />
                </v-list>
              </v-menu>
            </div>
          </div>
        </div>

        <v-alert
          v-if="showDefaultNotPublishedWarning"
          type="warning"
          variant="tonal"
          density="compact"
          rounded="lg"
          class="mx-4 mx-md-6 mt-3 mb-0"
        >
          This template is the site default but its status is <strong>{{ form.status }}</strong>.
          Study description pages only use a <strong>published</strong> default. Set status to
          <strong>published</strong> and save to apply this layout on the catalog.
        </v-alert>

        <div v-if="validationMsg" class="dt-validation-banner mx-4 mx-md-6 mt-2 mb-0">
          <v-alert
            :type="validationAlertType"
            variant="tonal"
            density="compact"
            rounded="lg"
            closable
            @click:close="clearValidationMsg"
          >
            <span class="text-body-2 dt-validation-msg">{{ validationMsg }}</span>
          </v-alert>
        </div>

        <div class="dt-detail-window">
        <div class="dt-structure-body">
          <div class="dt-sidebar-scroll">
            <DisplayTemplateTreePanel
              class="dt-tree-panel-host"
              v-model:tree-view-mode="treeViewMode"
              :root-items="templateRoot.items"
              :nodes="templateRoot.items"
              :expanded="expanded"
              :selected-tid="selectedTid"
              :selected-tids="selectedTidsList"
              :cut-tids="cutTidsList"
              :dragging-tid="draggingTid"
              :hover-drop="hoverDrop"
              :action-flags="actionFlags"
              :readonly="isSystemCore"
              @select-template-root="selectTemplateRoot"
              @select-description="selectDescription"
              @select-node="selectNode"
              @toggle="toggleExpanded"
              @tree-action="onTreeAction"
              @drag-start="onDragStart"
              @drag-end="onDragEnd"
              @drag-hover="onDragHover"
              @node-drop="onNodeDrop"
            />
          </div>
          <div class="dt-main-column">
            <DisplayTemplateMainTabs
              :model-value="mainContentTab"
              :unused-count="unusedFieldCount"
              @update:model-value="onMainTabChange"
            >
              <template #properties>
                <DisplayTemplateInspector
                  :selection-kind="selectionKind"
                  :template-root="templateRoot"
                  :form="form"
                  :model="model"
                  :selected-node="selectedNode"
                  :readonly="isSystemCore"
                  @dirty="markDirty"
                />
              </template>
              <template #add-fields>
                <DisplayTemplateAddFieldsTab
                  :mode="addFieldsMode"
                  :field-groups="availableFieldGroups"
                  :array-props="availableArrayProps"
                  :has-core="fieldRegistry.hasCore"
                  :can-add="canAddToSelectedTarget"
                  :add-target="addTargetSummary"
                  :readonly="isSystemCore"
                  @add-part="addCorePart"
                />
              </template>
              <template #translations>
                <DisplayTemplateTranslationsTab
                  :uid="uidEffective"
                  :template-root="templateRoot"
                  :primary-lang="form.lang || model.lang || 'en'"
                  @dirty="onTranslationsDirty"
                  @languages-updated="onLanguagesUpdated"
                />
              </template>
              <template #json>
                <DisplayTemplateFieldJsonTab
                  :selection-kind="selectionKind"
                  :selected-node="selectedNode"
                />
              </template>
            </DisplayTemplateMainTabs>
          </div>
        </div>
        </div>
      </div>
    </template>

    <v-dialog v-model="defaultPrompt" max-width="440" persistent>
      <v-card rounded="xl">
        <v-card-title class="text-h6 pt-6 px-6">Set as default?</v-card-title>
        <v-card-text class="px-6">This will replace the current default for this type.</v-card-text>
        <v-card-actions class="px-6 pb-6">
          <v-spacer />
          <v-btn variant="text" @click="defaultPrompt = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="settingDefault" @click="doSetDefault">
            Set as default
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

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
        <v-card-title class="text-h6 pt-6 px-6">
          {{ removePendingCount > 1 ? 'Remove nodes?' : 'Remove node?' }}
        </v-card-title>
        <v-card-text class="text-body-1">
          Remove <strong>{{ removeNodePreview }}</strong> from the layout?
          <template v-if="removePendingCount === 1"> Children are removed with it.</template>
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
import { useDisplayTemplatesApi } from '../composables/useDisplayTemplatesApi';
import { useDirtyLeaveGuard } from '../composables/useDirtyLeaveGuard';
import DisplayTemplateTreePanel from '../components/DisplayTemplateTreePanel.vue';
import DisplayTemplateMainTabs from '../components/DisplayTemplateMainTabs.vue';
import DisplayTemplateAddFieldsTab from '../components/DisplayTemplateAddFieldsTab.vue';
import DisplayTemplateInspector from '../components/DisplayTemplateInspector.vue';
import DisplayTemplateFieldJsonTab from '../components/DisplayTemplateFieldJsonTab.vue';
import DisplayTemplateTranslationsTab from '../components/DisplayTemplateTranslationsTab.vue';
import {
  buildAvailableFieldGroups,
  buildAvailableArrayPropParts,
  buildAvailableRootLayoutGroups,
  buildNestedArrayAvailableParts,
  countGroupedFields,
} from '../utils/availableFieldGroups';
import {
  areSiblingSelection,
  moveCutNodesAt,
  prepareCutSelection,
  resolvePasteZone,
} from '../utils/displayTemplateCutPaste';
import {
  cloneJson,
  ensureTreeIds,
  findNodeContextWithParent,
  getPropTreeSectionScope,
  getSectionContainerKey,
  isArrayLike,
  isCustomLayoutField,
  isPropNode,
  isPropTreeSection,
  newCustomArrayProp,
  newCustomField,
  newPropTreeSection,
  newSection,
  newWidgetNode,
  normalizeTemplateRoot,
  resolveArrayPropScope,
  stripTreeIds,
  swapSiblingOrder,
  uniqueCustomColumnKey,
  uniqueCustomFieldKey,
  VIRTUAL_DESCRIPTION_TID,
  VIRTUAL_TEMPLATE_ROOT_TID,
} from '../utils/displayTemplateTree';
import { sanitizeDisplayLayoutTree } from '../utils/displayFieldOptions';
import {
  buildTemplateFieldRegistry,
  canAddPartAtTarget,
  collectUsedKeys,
  formatValidationErrors,
  insertCorePart,
  moveNodeIfAllowed,
  resolveAllowedDropZone,
  validateTree,
} from '../utils/templateFieldRegistry';

defineOptions({ name: 'DisplayTemplateDetailPage' });

const props = defineProps({
  uid: { type: String, required: true },
});

const setMessage = inject('setMessage', () => {});
const route = useRoute();
const router = useRouter();

const {
  fetchTemplate,
  fetchCoreTemplate,
  updateTemplate,
  duplicateTemplate,
  deleteTemplate,
  setDefaultTemplate,
  validatePayload,
  fetchExport,
} = useDisplayTemplatesApi();

const model = ref(null);
const coreTemplate = ref(null);
const coreTemplateParts = ref({});
const isSystemCore = computed(() => {
  const t = model.value;
  return !!(t && (t.template_type === 'system' || t.is_core));
});

const isSiteDefault = computed(() => !!model.value?.default);

const canSetAsDefault = computed(() => !isSiteDefault.value && form.value.status === 'published');

const defaultActionTitle = computed(() => {
  if (form.value.status === 'draft') return 'Draft templates cannot be set as default';
  if (form.value.status === 'archived') return 'Archived templates cannot be set as default';
  if (isSiteDefault.value) return 'Default template';
  return 'Set as default';
});

const showDefaultNotPublishedWarning = computed(() => {
  if (isSystemCore.value || !isSiteDefault.value) return false;
  return form.value.status !== 'published';
});
const loadingDetail = ref(false);
const detailErr = ref('');
const saving = ref(false);
const validating = ref(false);
const validationMsg = ref('');
const validationOk = ref(false);
const validationHasWarnings = ref(false);
const validationAlertType = computed(() => {
  if (!validationOk.value) return 'warning';
  if (validationHasWarnings.value) return 'warning';
  return 'success';
});
const defaultPrompt = ref(false);
const settingDefault = ref(false);
const deletePrompt = ref(false);
const deleting = ref(false);
const dirty = ref(false);
const translationsDirty = ref(false);
const treeViewMode = ref('structure');
const suppressStructureDirty = ref(false);

const form = ref({
  name: '',
  data_type: '',
  version: '',
  status: 'draft',
  template_type: 'custom',
  lang: 'en',
  organization: '',
  author: '',
  description: '',
});

const templateRoot = ref(normalizeTemplateRoot({}));
const expanded = reactive({});
/** @type {import('vue').Ref<'template_root'|'description'|'node'>} */
const selectionKind = ref('template_root');
const selectedNode = ref(null);
/** @type {import('vue').Ref<string[]>} */
const selectedTids = ref([]);
const selectionAnchorTid = ref('');
/** @type {import('vue').Ref<{ tids: string[], containerKey?: string, parentTid?: string|null }|null>} */
const cutClipboard = ref(null);

const selectedTidsList = computed(() => selectedTids.value);

const cutTidsList = computed(() => cutClipboard.value?.tids ?? []);

const draggingTid = ref('');
const hoverDrop = ref(null);
/** @type {import('vue').Ref<'properties'|'add-fields'>} */
const mainContentTab = ref('properties');

const fieldRegistry = computed(() => buildTemplateFieldRegistry(coreTemplate.value));

const usedKeys = computed(() => collectUsedKeys(templateRoot.value.items));

const addTargetNode = computed(() => {
  if (isSystemCore.value) return null;
  if (selectionKind.value === 'template_root') return templateRoot.value;
  return selectedNode.value;
});

const availableFieldGroups = computed(() => {
  if (isSystemCore.value || !fieldRegistry.value.hasCore) return [];

  if (selectionKind.value === 'template_root') {
    return filterAddableGroups(
      buildAvailableRootLayoutGroups(coreTemplate.value, usedKeys.value),
      templateRoot.value
    );
  }

  const n = selectedNode.value;
  if (!n?._tid) return [];

  if (n.type === 'section_container') {
    return [];
  }

  if (n.type !== 'section') return [];

  const containerKey = getSectionContainerKey(templateRoot.value.items, n._tid);
  return filterAddableGroups(
    buildAvailableFieldGroups(coreTemplate.value, fieldRegistry.value, usedKeys.value, {
      containerKey,
    }),
    n
  );
});

function filterAddableGroups(groups, target) {
  if (!target) return [];
  return groups
    .map((group) => ({
      ...group,
      fields: group.fields.filter((part) =>
        canAddPartAtTarget(templateRoot.value.items, target, part, fieldRegistry.value)
      ),
    }))
    .filter((g) => g.fields.length);
}

const unusedFieldCount = computed(() => {
  if (isArrayPropAddTarget.value) return availableArrayProps.value.length;
  return countGroupedFields(availableFieldGroups.value);
});

const isArrayPropAddTarget = computed(() => {
  if (selectionKind.value !== 'node' || !selectedNode.value) return false;
  const n = selectedNode.value;
  if (isPropTreeSection(n)) return true;
  return isArrayLike(n) && n.type !== 'simple_array';
});

const canAddPropTreeSection = computed(() => {
  if (selectionKind.value !== 'node' || !selectedNode.value) return false;
  const n = selectedNode.value;
  return n.type === 'nested_array' || isPropTreeSection(n) || isPropNode(n);
});

const isSectionAddTarget = computed(() => {
  if (selectionKind.value !== 'node' || !selectedNode.value) return false;
  const type = selectedNode.value.type;
  return type === 'section' || type === 'section_container';
});

/** Core layout fields can only be added under a section (not section_container). */
const isLayoutFieldAddTarget = computed(() => {
  if (selectionKind.value !== 'node' || !selectedNode.value) return false;
  return selectedNode.value.type === 'section';
});

const isContainerLayoutAddTarget = computed(() => {
  if (selectionKind.value !== 'node' || !selectedNode.value) return false;
  return selectedNode.value.type === 'section_container';
});

const isTemplateRootAddTarget = computed(() => selectionKind.value === 'template_root');

const isAvailableFieldsAddTarget = computed(
  () =>
    !isSystemCore.value &&
    (isTemplateRootAddTarget.value ||
      isLayoutFieldAddTarget.value ||
      isArrayPropAddTarget.value)
);

const addFieldsMode = computed(() => (isArrayPropAddTarget.value ? 'array-props' : 'fields'));

const arrayPropScopeKey = computed(() => resolveArrayPropScope(selectedNode.value));

const availableArrayProps = computed(() => {
  const n = selectedNode.value;
  const scope = arrayPropScopeKey.value;
  if (!n || !scope) return [];

  let parts;
  if (n.type === 'nested_array' && !isPropTreeSection(n)) {
    parts = buildNestedArrayAvailableParts(
      fieldRegistry.value,
      usedKeys.value,
      scope,
      n.props
    );
  } else {
    parts = buildAvailableArrayPropParts(fieldRegistry.value, usedKeys.value, scope, {
      excludeSections: true,
    });
  }

  return parts.filter((part) =>
    canAddPartAtTarget(templateRoot.value.items, n, part, fieldRegistry.value)
  );
});

const canAddToSelectedTarget = computed(
  () => !isSystemCore.value && isAvailableFieldsAddTarget.value
);

const addTargetSummary = computed(() => {
  if (isArrayPropAddTarget.value && selectedNode.value) {
    const n = selectedNode.value;
    return {
      ready: true,
      label: n.title || n.key || 'Array',
      type: n.type,
      hint: '',
    };
  }
  if (isTemplateRootAddTarget.value) {
    return {
      ready: true,
      label: templateRoot.value.title || 'Template',
      type: 'template',
      hint: '',
    };
  }
  if (isLayoutFieldAddTarget.value && selectedNode.value) {
    const n = selectedNode.value;
    return {
      ready: true,
      label: n.title || n.key || 'Section',
      type: n.type,
      hint: '',
    };
  }
  if (isContainerLayoutAddTarget.value && selectedNode.value) {
    const n = selectedNode.value;
    const label = n.title || n.key || 'this container';
    return {
      ready: false,
      label,
      type: n.type,
      hint: `Select a section in ${label} to add unused core fields.`,
    };
  }
  return {
    ready: false,
    label: '',
    type: '',
    hint: 'Select the template, a section, or an array field in the layout tree.',
  };
});

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
    const ctx = findNodeContextWithParent(templateRoot.value.items, tid);
    if (ctx) {
      up = ctx.index > 0;
      down = ctx.index < ctx.siblings.length - 1;
    }
  }

  const canAddField = !isSystemCore.value && fieldRegistry.value.hasCore;
  const customTarget = resolveCustomInsertTarget();
  const canAddCustom = !!customTarget;

  const tids = selectedTids.value;
  const sameLevel = tids.length > 0 && areSiblingSelection(templateRoot.value.items, tids);
  const canCut = sk === 'node' && sameLevel && !isSystemCore.value;

  const pasteTargetTid = node?._tid;
  const cutTids = cutClipboard.value?.tids ?? [];
  const canPaste =
    cutTids.length > 0
    && sk === 'node'
    && !!pasteTargetTid
    && !cutTids.includes(pasteTargetTid)
    && !isSystemCore.value
    && !!resolvePasteZone(templateRoot.value.items, cutClipboard.value, pasteTargetTid);

  return {
    canAddSection:
      !isSystemCore.value
      && (isSectionAddTarget.value || canAddPropTreeSection.value || selectionKind.value === 'template_root'),
    canAddWidget:
      !isSystemCore.value
      && isLayoutFieldAddTarget.value
      && !isPropTreeSection(selectedNode.value),
    canAddField,
    canAddCustom,
    addCustomTitle: customTarget?.kind === 'prop' ? 'Add custom column' : 'Add custom field',
    canCut,
    canPaste,
    hasClipboard: cutTids.length > 0,
    canRemove:
      sk === 'node'
      && (tids.length > 0 || !!node?._tid)
      && !isSystemCore.value,
    canMoveUp: up && !isSystemCore.value && tids.length <= 1,
    canMoveDown: down && !isSystemCore.value && tids.length <= 1,
  };
});

const removeNodePrompt = ref(false);
/** @type {import('vue').Ref<string[]>} */
const removePendingTids = ref([]);

const removePendingCount = computed(() =>
  removePendingTids.value.length || selectedTids.value.length || (selectedNode.value?._tid ? 1 : 0)
);

const removeNodePreview = computed(() => {
  const count = removePendingTids.value.length || selectedTids.value.length;
  if (count > 1) {
    return `${count} nodes`;
  }
  if (!selectedNode.value) return '';
  return selectedNode.value.title || selectedNode.value.key || 'node';
});

function markDirty() {
  dirty.value = true;
}

const pageDirty = computed(() => dirty.value || translationsDirty.value);
const { confirmLeave } = useDirtyLeaveGuard(pageDirty);

function onMainTabChange(next) {
  if (next === mainContentTab.value) return;
  if (mainContentTab.value === 'translations' && translationsDirty.value && !confirmLeave()) {
    return;
  }
  mainContentTab.value = next;
}

function onTranslationsDirty(value) {
  translationsDirty.value = !!value;
}

function onLanguagesUpdated(languages) {
  if (!model.value) return;
  model.value = { ...model.value, languages: Array.isArray(languages) ? languages : model.value.languages };
}

function clearValidationMsg() {
  validationMsg.value = '';
  validationHasWarnings.value = false;
}

function cancelEdit() {
  router.push({ name: 'display-templates' });
}

function selectTemplateRoot() {
  selectionKind.value = 'template_root';
  selectedNode.value = null;
  selectedTids.value = [];
  selectionAnchorTid.value = '';
}

function selectDescription() {
  selectionKind.value = 'description';
  selectedNode.value = null;
  selectedTids.value = [];
  selectionAnchorTid.value = '';
}

function selectNode(node, event) {
  if (!node?._tid) return;
  const tid = node._tid;
  const items = templateRoot.value.items;
  const ctx = findNodeContextWithParent(items, tid);
  if (!ctx) return;

  if (event?.shiftKey && selectionAnchorTid.value) {
    const anchorCtx = findNodeContextWithParent(items, selectionAnchorTid.value);
    if (anchorCtx && anchorCtx.siblings === ctx.siblings) {
      const start = Math.min(anchorCtx.index, ctx.index);
      const end = Math.max(anchorCtx.index, ctx.index);
      /** @type {string[]} */
      const range = [];
      for (let i = start; i <= end; i += 1) {
        const n = ctx.siblings[i];
        if (n?._tid) range.push(n._tid);
      }
      selectedTids.value = range;
      selectionKind.value = 'node';
      selectedNode.value = node;
      return;
    }
  }

  if ((event?.metaKey || event?.ctrlKey) && selectionAnchorTid.value) {
    const anchorCtx = findNodeContextWithParent(items, selectionAnchorTid.value);
    if (anchorCtx && anchorCtx.siblings === ctx.siblings) {
      const next = new Set(selectedTids.value);
      if (next.has(tid)) next.delete(tid);
      else next.add(tid);
      if (!next.size) next.add(tid);
      selectedTids.value = [...next];
      selectionKind.value = 'node';
      selectedNode.value = node;
      return;
    }
  }

  selectedTids.value = [tid];
  selectionAnchorTid.value = tid;
  selectionKind.value = 'node';
  selectedNode.value = node;
}

function toggleExpanded(tid) {
  expanded[tid] = !expanded[tid];
}

watch(
  templateRoot,
  () => {
    if (!suppressStructureDirty.value) markDirty();
  },
  { deep: true }
);

function hydrateTemplateRoot(templateJsonObj) {
  suppressStructureDirty.value = true;
  const r = normalizeTemplateRoot(templateJsonObj);
  sanitizeDisplayLayoutTree(r);
  ensureTreeIds(r.items);
  if (!r.title && form.value.name) {
    r.title = form.value.name;
  }
  templateRoot.value = r;
  Object.keys(expanded).forEach((k) => delete expanded[k]);
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

function applyModel(t, baseline = {}) {
  form.value.name = t.name || '';
  form.value.data_type = t.data_type || '';
  form.value.version = t.version || '';
  form.value.status = t.status || 'draft';
  form.value.template_type = t.template_type || 'custom';
  form.value.lang = t.lang || 'en';
  form.value.organization = t.organization != null ? String(t.organization) : '';
  form.value.author = t.author != null ? String(t.author) : '';
  form.value.description = t.description != null ? String(t.description) : '';

  coreTemplate.value = baseline.coreTemplate ?? null;
  coreTemplateParts.value = baseline.coreTemplateParts ?? {};

  hydrateTemplateRoot(tp(t.template_json));

  cutClipboard.value = null;
  selectedTids.value = [];
  selectionAnchorTid.value = '';

  selectTemplateRoot();
  validationMsg.value = '';
  dirty.value = false;
  translationsDirty.value = false;
}

/** After save: sync server metadata without rebuilding the tree (keeps selection & expansion). */
function applySavedTemplate(t, { wasSiteDefault = false } = {}) {
  if (!t) return;
  model.value = t;
  form.value.name = t.name ?? form.value.name;
  form.value.version = t.version ?? form.value.version;
  form.value.status = t.status ?? form.value.status;
  form.value.lang = t.lang ?? form.value.lang;
  form.value.organization = t.organization != null ? String(t.organization) : form.value.organization;
  form.value.author = t.author != null ? String(t.author) : form.value.author;
  form.value.description = t.description != null ? String(t.description) : form.value.description;
  if (wasSiteDefault) {
    model.value.default = true;
  }
  suppressStructureDirty.value = true;
  sanitizeDisplayLayoutTree(templateRoot.value);
  nextTick(() => {
    suppressStructureDirty.value = false;
    dirty.value = false;
  });
}

function buildTemplateJsonObject() {
  const o = stripTreeIds(cloneJson(templateRoot.value));
  if (!o.type) o.type = 'template';
  if (!Array.isArray(o.items)) o.items = [];
  sanitizeDisplayLayoutTree(o);
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
      const loaded = await fetchTemplate(uid);
      const t = loaded?.template;
      if (!t) {
        throw new Error('Template not found');
      }
      model.value = t;
      let coreTemplateData = loaded.coreTemplate;
      let coreTemplatePartsData = loaded.coreTemplateParts;
      if (!coreTemplateData && t.data_type) {
        try {
          const coreLoaded = await fetchCoreTemplate(t.data_type);
          coreTemplateData = coreLoaded.coreTemplate;
          coreTemplatePartsData = coreLoaded.coreTemplateParts;
        } catch {
          // Baseline is optional until dedicated display cores exist for every type.
        }
      }
      applyModel(t, {
        coreTemplate: coreTemplateData,
        coreTemplateParts: coreTemplatePartsData ?? {},
      });
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
  if (isSystemCore.value || treeViewMode.value === 'preview') return;
  const ok = moveNodeIfAllowed(templateRoot.value.items, dragTid, targetTid, zone);
  if (!ok) {
    setMessage('That node cannot be moved here (fields stay within their section container).', 'warning');
    return;
  }
  if (zone === 'into') {
    expanded[targetTid] = true;
  }
  markDirty();
}

function addCorePart(part) {
  if (isSystemCore.value) return;
  const target = addTargetNode.value;
  if (!part) return;
  if (!target || !isAvailableFieldsAddTarget.value) {
    mainContentTab.value = 'add-fields';
    setMessage('Select the template, a section, or an array field in the layout tree first.', 'info');
    return;
  }
  if (!canAddPartAtTarget(templateRoot.value.items, target, part, fieldRegistry.value)) {
    setMessage('That item cannot be added to the selected node.', 'warning');
    return;
  }
  const inserted = insertCorePart(templateRoot.value.items, target, part, fieldRegistry.value);
  if (!inserted) {
    setMessage('Could not add that item.', 'warning');
    return;
  }
  if (target.type === 'template') {
    expanded[VIRTUAL_TEMPLATE_ROOT_TID] = true;
  } else if (target._tid) {
    expanded[target._tid] = true;
  }
  markDirty();
  if (part.kind === 'prop') {
    // Keep array selected so more columns can be added from the same list.
    return;
  }
  selectNode(inserted);
  mainContentTab.value = 'properties';
}

/**
 * Custom field: selected layout section.
 * Custom column: selected custom array / nested_array.
 * @returns {{ kind: 'field'|'prop', parent: object }|null}
 */
function resolveCustomInsertTarget() {
  if (isSystemCore.value) return null;
  if (selectionKind.value !== 'node') return null;
  const n = selectedNode.value;
  if (!n) return null;

  if (n.type === 'section' && !isPropTreeSection(n)) {
    return { kind: 'field', parent: n };
  }
  if (
    isCustomLayoutField(n)
    && (n.type === 'array' || n.type === 'nested_array')
  ) {
    return { kind: 'prop', parent: n };
  }
  return null;
}

function addCustomFromTree() {
  if (isSystemCore.value) return;
  const target = resolveCustomInsertTarget();
  if (!target) {
    setMessage('Select a section to add a custom field, or a custom array to add a column.', 'info');
    return;
  }
  if (target.kind === 'prop') {
    addUntitledCustomProp(target.parent);
    return;
  }
  addUntitledCustomField(target.parent);
}

function addUntitledCustomField(parent) {
  const key = uniqueCustomFieldKey(usedKeys.value, fieldRegistry.value.fields);
  const node = newCustomField(key, 'Untitled', 'string');
  if (!Array.isArray(parent.items)) parent.items = [];
  parent.items.push(node);
  if (parent._tid) expanded[parent._tid] = true;
  markDirty();
  selectNode(node);
  mainContentTab.value = 'properties';
}

function addUntitledCustomProp(parent) {
  const scope = resolveArrayPropScope(parent);
  if (!scope) {
    setMessage('Could not resolve the array scope for this column.', 'warning');
    return;
  }
  const columnKey = uniqueCustomColumnKey(scope, usedKeys.value, fieldRegistry.value.props);
  const node = newCustomArrayProp(scope, columnKey, 'Untitled', 'string');
  if (!Array.isArray(parent.props)) parent.props = [];
  parent.props.push(node);
  if (parent._tid) expanded[parent._tid] = true;
  markDirty();
  selectNode(node);
  mainContentTab.value = 'properties';
}

function onTreeAction(id) {
  switch (id) {
    case 'add-section':
      addSection();
      break;
    case 'add-widget':
      addWidget();
      break;
    case 'add-custom':
      addCustomFromTree();
      break;
    case 'add-field':
      mainContentTab.value = 'add-fields';
      if (!fieldRegistry.value.hasCore) {
        setMessage('No core template is loaded for this type.', 'info');
      } else if (!isAvailableFieldsAddTarget.value) {
        setMessage('Select the template, a section, or an array field in the layout tree.', 'info');
      }
      break;
    case 'cut':
      cutSelectedNodes();
      break;
    case 'paste':
      pasteCutNodes();
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

function cutSelectedNodes() {
  if (isSystemCore.value) return;
  const tids = selectedTids.value;
  if (!tids.length) {
    setMessage('Select one or more nodes at the same level to cut.', 'info');
    return;
  }
  if (!areSiblingSelection(templateRoot.value.items, tids)) {
    setMessage('Cut only works for nodes that share the same parent.', 'warning');
    return;
  }
  const clip = prepareCutSelection(templateRoot.value.items, tids);
  if (!clip?.tids?.length) {
    setMessage('Could not cut the selected nodes.', 'warning');
    return;
  }
  cutClipboard.value = {
    tids: clip.tids,
    containerKey: clip.containerKey,
    parentTid: clip.parentTid,
  };
  setMessage(
    `${clip.tids.length} node${clip.tids.length === 1 ? '' : 's'} marked for move — select a target and paste.`,
    'info'
  );
}

function pasteCutNodes() {
  if (isSystemCore.value || !cutClipboard.value?.tids?.length) return;
  const target = selectedNode.value;
  if (!target?._tid) {
    setMessage('Select where to paste in the layout tree.', 'info');
    return;
  }
  if (cutClipboard.value.tids.includes(target._tid)) {
    setMessage('Select a different node as the paste target.', 'warning');
    return;
  }
  const zone = resolvePasteZone(templateRoot.value.items, cutClipboard.value, target._tid);
  if (!zone) {
    setMessage('Cannot paste here (fields stay within their section container).', 'warning');
    return;
  }
  const movedTids = [...cutClipboard.value.tids];
  const ok = moveCutNodesAt(templateRoot.value.items, cutClipboard.value, target._tid, zone);
  if (!ok) {
    setMessage('Could not move nodes here.', 'warning');
    return;
  }
  cutClipboard.value = null;
  expanded[target._tid] = true;
  const items = templateRoot.value.items;
  const movedNodes = movedTids
    .map((tid) => findNodeContextWithParent(items, tid)?.node)
    .filter(Boolean);
  selectedTids.value = movedTids.filter((tid) => findNodeContextWithParent(items, tid));
  selectionAnchorTid.value = selectedTids.value[0] || '';
  selectedNode.value = movedNodes[movedNodes.length - 1] || target;
  selectionKind.value = 'node';
  markDirty();
  setMessage('Moved.', 'success');
}

function moveSelectedSibling(direction) {
  if (isSystemCore.value) return;
  const tid = selectedNode.value?._tid;
  if (!tid) return;
  if (swapSiblingOrder(templateRoot.value.items, tid, direction === 'up' ? 'up' : 'down')) {
    markDirty();
  }
}

function widgetInsertParent() {
  const selected = selectedNode.value;
  if (!selected || selected.type !== 'section' || isPropTreeSection(selected)) return null;
  return selected;
}

function addWidget() {
  if (isSystemCore.value) return;
  const parent = widgetInsertParent();
  if (!parent) {
    setMessage('Select a section to add a widget.', 'info');
    return;
  }
  if (!Array.isArray(parent.items)) parent.items = [];
  const widget = newWidgetNode();
  parent.items.push(widget);
  expanded[parent._tid] = true;
  markDirty();
  selectNode(widget);
  mainContentTab.value = 'properties';
}

function addSection() {
  if (isSystemCore.value) return;
  const items = templateRoot.value.items;
  const tid = selectedTid.value;
  const selected = selectedNode.value;

  // Prop-tree sections inside nested_array (ME props-tree).
  if (selected && (selected.type === 'nested_array' || isPropNode(selected))) {
    let parentArr;
    let scopeKey;

    if (selected.type === 'nested_array') {
      if (!Array.isArray(selected.props)) selected.props = [];
      parentArr = selected.props;
      scopeKey = selected.key || '';
    } else if (isPropTreeSection(selected) && Array.isArray(selected.props)) {
      parentArr = selected.props;
      scopeKey = selected.prop_key || '';
    } else {
      const ctx = tid ? findNodeContextWithParent(items, tid) : null;
      parentArr = ctx?.siblings;
      scopeKey = tid ? getPropTreeSectionScope(items, tid) : '';
    }

    if (parentArr && scopeKey) {
      const sec = newPropTreeSection(scopeKey, 'Untitled');
      parentArr.push(sec);
      expanded[selected._tid] = true;
      expanded[sec._tid] = true;
      markDirty();
      selectNode(sec);
      return;
    }
  }

  const sec = newSection(`section_${Date.now()}`, 'New section');

  if (selected?.type === 'section_container' && !isPropTreeSection(selected)) {
    if (!Array.isArray(selected.items)) selected.items = [];
    selected.items.push(sec);
    expanded[selected._tid] = true;
    markDirty();
    selectNode(sec);
    mainContentTab.value = 'properties';
    return;
  }

  if (selected?.type === 'section' && !isPropTreeSection(selected) && selected._tid) {
    const ctx = findNodeContextWithParent(items, selected._tid);
    if (ctx) {
      ctx.siblings.splice(ctx.index + 1, 0, sec);
      if (ctx.parentNode?._tid) expanded[ctx.parentNode._tid] = true;
      markDirty();
      selectNode(sec);
      mainContentTab.value = 'properties';
      return;
    }
  }

  if (selectionKind.value === 'template_root' || !selected) {
    items.push(sec);
    markDirty();
    selectNode(sec);
    mainContentTab.value = 'properties';
    return;
  }

  setMessage('Select a section or section container to add a section.', 'info');
}

async function save() {
  if (isSystemCore.value) return;
  saving.value = true;
  validationMsg.value = '';
  const wasSiteDefault = !!model.value?.default;
  try {
    let template_json;
    try {
      template_json = buildTemplateJsonObject();
    } catch (e) {
      setMessage(e?.message || String(e), 'error');
      saving.value = false;
      return;
    }
    const localValidation = validateTree(template_json.items, fieldRegistry.value);
    const hasWarnings = !!(localValidation.warnings?.length);
    if (!localValidation.valid) {
      validationOk.value = false;
      validationHasWarnings.value = hasWarnings;
      validationMsg.value = formatValidationErrors(localValidation);
      setMessage('Fix validation errors before saving.', 'warning');
      saving.value = false;
      return;
    }
    validationOk.value = true;
    validationHasWarnings.value = hasWarnings;
    validationMsg.value = hasWarnings ? formatValidationErrors(localValidation) : '';
    const updated = await updateTemplate(uidEffective.value, {
      name: (form.value.name || '').trim(),
      status: form.value.status,
      version: form.value.version || null,
      organization: (form.value.organization || '').trim() || null,
      author: (form.value.author || '').trim() || null,
      description: (form.value.description || '').trim() || null,
      lang: (form.value.lang || '').trim() || 'en',
      template_json,
    });
    model.value = updated || model.value;
    if (updated) {
      applySavedTemplate(updated, { wasSiteDefault });
    }
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
      validationHasWarnings.value = false;
      validationMsg.value = e?.message || String(e);
      validating.value = false;
      return;
    }
    const localValidation = validateTree(template_json.items, fieldRegistry.value);
    if (!localValidation.valid) {
      validationOk.value = false;
      validationHasWarnings.value = !!(localValidation.warnings?.length);
      validationMsg.value = formatValidationErrors(localValidation);
      validating.value = false;
      return;
    }
    validationOk.value = true;
    validationHasWarnings.value = !!(localValidation.warnings?.length);
    validationMsg.value = formatValidationErrors(localValidation);
    const result = await validatePayload({
      uid: uidEffective.value,
      name: form.value.name,
      data_type: form.value.data_type,
      template_json,
    });
    validationOk.value = !!(result?.valid ?? true);
    validationHasWarnings.value = Array.isArray(result?.warnings) && result.warnings.length > 0;
    validationMsg.value = formatValidationResult(result);
  } catch (e) {
    validationOk.value = false;
    validationHasWarnings.value = false;
    validationMsg.value = e?.message || String(e);
  } finally {
    validating.value = false;
  }
}

function formatValidationResult(result) {
  if (result == null) return '';
  if (typeof result === 'string') return result;
  const errors = Array.isArray(result.errors) ? result.errors.filter(Boolean) : [];
  const warnings = Array.isArray(result.warnings) ? result.warnings.filter(Boolean) : [];
  if (result.valid && !warnings.length) return 'Template is valid.';
  if (result.valid && warnings.length) {
    return ['Template is valid with warnings:', ...warnings].join('\n');
  }
  if (errors.length) return errors.join('\n');
  return 'Validation failed.';
}

async function duplicate() {
  try {
    const t = await duplicateTemplate(uidEffective.value);
    setMessage('Duplicated.', 'success');
    if (t?.uid) router.replace({ name: 'display-template-detail', params: { uid: t.uid } });
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  }
}

async function doSetDefault() {
  if (!canSetAsDefault.value) return;
  settingDefault.value = true;
  try {
    await setDefaultTemplate(form.value.data_type, uidEffective.value);
    if (model.value) {
      model.value = { ...model.value, default: true };
    }
    defaultPrompt.value = false;
    setMessage('Default updated.', 'success');
  } catch (e) {
    setMessage(e?.message || String(e), 'error');
  } finally {
    settingDefault.value = false;
  }
}

async function doDelete() {
  deleting.value = true;
  try {
    await deleteTemplate(uidEffective.value);
    deletePrompt.value = false;
    dirty.value = false;
    setMessage('Deleted.', 'success');
    router.push({ name: 'display-templates' });
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
  if (isSystemCore.value) return;
  const tids = selectedTids.value.length ? selectedTids.value : [selectedNode.value?._tid].filter(Boolean);
  if (!tids.length) return;
  removePendingTids.value = [...tids];
  removeNodePrompt.value = true;
}

function doRemoveNode() {
  if (isSystemCore.value) return;
  const tids = removePendingTids.value.length
    ? removePendingTids.value
    : [selectedNode.value?._tid].filter(Boolean);
  removePendingTids.value = [];
  removeNodePrompt.value = false;
  if (!tids.length) return;

  const sorted = tids
    .map((tid) => findNodeContextWithParent(templateRoot.value.items, tid))
    .filter(Boolean)
    .sort((a, b) => b.index - a.index);

  for (const ctx of sorted) {
    ctx.siblings.splice(ctx.index, 1);
  }

  if (cutClipboard.value?.tids?.some((tid) => tids.includes(tid))) {
    cutClipboard.value = null;
  }

  selectedTids.value = [];
  selectionAnchorTid.value = '';
  selectedNode.value = null;
  selectionKind.value = 'template_root';
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
  display: flex;
  flex-direction: column;
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
  background: transparent;
  border: none;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  box-shadow: none;
}
.dt-back-to-index {
  margin-inline-start: -6px;
}
.dt-detail-window {
  width: 100%;
  margin: 0;
  border: none;
  border-radius: 0;
  flex: 1 1 0;
  min-height: 0;
  overflow: hidden;
  background: transparent;
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
.dt-tree-panel-host {
  flex: 1 1 0;
  min-height: 0;
  overflow: hidden;
}
.dt-main-column {
  min-width: 0;
  min-height: 0;
  height: 100%;
  overflow: hidden;
}
.min-width-0 {
  min-width: 0;
}
.dt-validation-banner {
  flex: 0 0 auto;
}
.dt-validation-banner :deep(.v-alert) {
  flex: 0 0 auto;
}
.dt-validation-msg {
  display: block;
  max-height: 8rem;
  overflow-y: auto;
  white-space: pre-line;
  word-break: break-word;
}
.dt-dirty-marker {
  visibility: hidden;
}
.dt-dirty-marker.is-dirty {
  visibility: visible;
}
.font-json :deep(textarea) {
  font-family: ui-monospace, monospace;
  font-size: 0.85rem;
  line-height: 1.4;
}
@media (max-width: 959.98px) {
  .dt-structure-body {
    grid-template-columns: 1fr;
    grid-template-rows: minmax(200px, 36vh) minmax(200px, 1fr);
  }
}
</style>
