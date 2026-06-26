<template>
  <v-dialog :model-value="modelValue" max-width="500" persistent @update:modelValue="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Group: {{ group?.name }} — assign items</v-card-title>
      <v-card-text>
        <p class="text-body-2 text-medium-emphasis mb-2">
          Items already in this group are checked. Toggle to add or remove.
        </p>
        <v-list v-if="allItems.length">
          <v-list-item
            v-for="item in allItems"
            :key="item.id"
            class="px-0"
            @click="toggleItem(item)"
          >
            <template #prepend>
              <v-checkbox
                :model-value="isInGroup(item.id)"
                hide-details
                density="compact"
                @click.stop="toggleItem(item)"
              />
            </template>
            <v-list-item-title>
              <code class="mr-2">{{ item.code }}</code>
              {{ item.title || '—' }}
            </v-list-item-title>
          </v-list-item>
        </v-list>
        <p v-else class="text-medium-emphasis">No items in this codelist yet. Add items first.</p>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">Close</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';

defineOptions({ name: 'GroupItemsDialog' });

const props = defineProps({
  modelValue: Boolean,
  group: { type: Object, default: null },
  items: { type: Array, default: () => [] },
  groupItemIds: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'add-item', 'remove-item']);

const allItems = computed(() => props.items || []);

function isInGroup(itemId) {
  const n = Number(itemId);
  return (props.groupItemIds || []).some((id) => Number(id) === n);
}

function toggleItem(item) {
  if (isInGroup(item.id)) {
    emit('remove-item', item.id);
  } else {
    emit('add-item', item.id);
  }
}
</script>
