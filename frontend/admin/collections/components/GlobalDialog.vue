<template>
  <Teleport to="body">
    <v-dialog
      :model-value="visible"
      max-width="400"
      persistent
      @update:model-value="onDialogUpdate"
    >
      <v-card>
        <v-card-title class="text-subtitle-1">{{ title }}</v-card-title>
        <v-card-text>{{ message }}</v-card-text>
        <v-card-actions>
          <v-spacer />
          <template v-if="mode === 'confirm'">
            <v-btn variant="text" @click="$dialog.onCancel()">
              {{ cancelText }}
            </v-btn>
            <v-btn color="primary" variant="flat" @click="$dialog.onConfirm()">
              {{ confirmText }}
            </v-btn>
          </template>
          <template v-else>
            <v-btn color="primary" variant="flat" @click="$dialog.onAlertClose()">
              {{ confirmText }}
            </v-btn>
          </template>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </Teleport>
</template>

<script setup>
import { $dialog } from '@/shared/composables/dialog';

defineOptions({ name: 'GlobalDialog' });

const { visible, title, message, confirmText, cancelText, mode } = $dialog.state;

function onDialogUpdate(open) {
  if (!open) $dialog.onCancel();
}
</script>
