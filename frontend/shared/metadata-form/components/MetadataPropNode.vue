<script setup>
/**
 * One prop from a nested_array / nested-section props[] tree.
 * Sections keep parentPath (child keys are row-relative); other types append prop.key.
 */
import { computed } from 'vue';
import { pathJoin } from '../composables/createMetadataFormStore';
import { isSectionType } from '../utils/enumOptions';
import MetadataArrayField from './MetadataArrayField.vue';
import MetadataFieldInput from './MetadataFieldInput.vue';
import MetadataNestedSection from './MetadataNestedSection.vue';
import MetadataSimpleArrayField from './MetadataSimpleArrayField.vue';

const props = defineProps({
  prop: { type: Object, required: true },
  parentPath: { type: String, required: true },
});

const isSection = computed(() => isSectionType(props.prop.type));
const fieldPath = computed(() => pathJoin(props.parentPath, props.prop.key));
</script>

<template>
  <MetadataNestedSection
    v-if="isSection"
    :field="prop"
    :parent-path="parentPath"
  />
  <MetadataArrayField
    v-else-if="prop.type === 'array' || prop.type === 'nested_array'"
    :field="prop"
    :path="fieldPath"
  />
  <MetadataSimpleArrayField
    v-else-if="prop.type === 'simple_array'"
    :field="prop"
    :path="fieldPath"
  />
  <MetadataFieldInput
    v-else-if="prop.key"
    :field="prop"
    :path="fieldPath"
  />
</template>
