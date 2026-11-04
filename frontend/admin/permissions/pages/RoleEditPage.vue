<template>
  <div>
    <v-breadcrumbs :items="breadcrumbItems" class="perm-breadcrumbs px-0 pt-0">
      <template #divider>
        <v-icon icon="mdi-chevron-right" size="16" />
      </template>
    </v-breadcrumbs>

    <h1 class="text-h5 font-weight-semibold text-high-emphasis mb-4">
      {{ t('Edit role', 'Edit role') }}
    </h1>

    <v-alert v-if="loadError" type="error" class="perm-flash-alert" closable @click:close="loadError = ''">
      {{ loadError }}
    </v-alert>
    <v-alert v-if="saveError" type="error" class="perm-flash-alert" closable @click:close="saveError = ''">
      {{ saveError }}
    </v-alert>

    <v-progress-linear v-if="pageLoading" indeterminate color="primary" class="mb-4" />

    <v-card v-else-if="role" class="pa-4" elevation="1" max-width="560">
      <v-alert v-if="role.is_locked" type="warning" class="mb-4" density="compact">
        {{ t('system_role_locked', 'System roles cannot be edited.') }}
      </v-alert>

      <div class="perm-field-group">
        <div class="perm-field-label">{{ t('Role name', 'Role name') }}</div>
        <v-text-field
          v-model="form.role"
          class="perm-field-input"
          variant="outlined"
          density="compact"
          maxlength="100"
          :disabled="role.is_locked"
          hide-details="auto"
          autocomplete="off"
        />
      </div>
      <div class="perm-field-group">
        <div class="perm-field-label">{{ t('description', 'Description') }}</div>
        <v-textarea
          v-model="form.description"
          class="perm-field-input"
          variant="outlined"
          density="compact"
          rows="3"
          maxlength="255"
          :disabled="role.is_locked"
          hide-details="auto"
        />
      </div>
      <div class="perm-field-group">
        <div class="perm-field-label">{{ t('weight', 'Weight') }}</div>
        <v-text-field
          v-model.number="form.weight"
          class="perm-field-input"
          variant="outlined"
          density="compact"
          type="number"
          min="0"
          max="999"
          :disabled="role.is_locked"
          hide-details="auto"
          style="max-width: 140px"
        />
      </div>

      <div class="perm-form-actions d-flex ga-2">
        <v-btn
          color="primary"
          :loading="saving"
          :disabled="role.is_locked || !form.role.trim()"
          @click="submit"
        >
          {{ t('update', 'Update') }}
        </v-btn>
        <v-btn variant="text" :to="{ name: 'roles-list' }">{{ t('cancel', 'Cancel') }}</v-btn>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useI18n } from '@/shared/composables/useI18n';
import { usePermissionsApi } from '../composables/usePermissionsApi';

defineOptions({ name: 'RoleEditPage' });

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const router = useRouter();
const { t } = useI18n();
const { siteUrl } = useAppConfig();
const { getRole, updateRole } = usePermissionsApi();

const siteBaseUrl = computed(() => String(siteUrl.value || '').replace(/\/$/, ''));
const breadcrumbItems = computed(() => [
  { title: t('admin', 'Admin'), href: `${siteBaseUrl.value}/admin` },
  { title: t('Manage roles', 'Manage roles'), to: { name: 'roles-list' } },
  { title: t('Edit role', 'Edit role'), disabled: true },
]);

const role = ref(null);
const pageLoading = ref(true);
const loadError = ref('');
const saving = ref(false);
const saveError = ref('');
const form = reactive({ role: '', description: '', weight: 0 });

async function load() {
  pageLoading.value = true;
  loadError.value = '';
  saveError.value = '';
  try {
    const r = await getRole(props.id);
    role.value = r;
    form.role = r.name || '';
    form.description = r.description || '';
    form.weight = Number.isFinite(Number(r.weight)) ? Number(r.weight) : 0;
  } catch (e) {
    role.value = null;
    loadError.value = e.message || 'Failed to load';
  } finally {
    pageLoading.value = false;
  }
}

async function submit() {
  if (!role.value || role.value.is_locked) return;
  saving.value = true;
  saveError.value = '';
  try {
    await updateRole(props.id, {
      role: form.role.trim(),
      description: form.description.trim(),
      weight: form.weight,
    });
    await router.push({
      name: 'roles-list',
      state: {
        flashSuccess: t('form_update_success', 'Your changes have been saved!'),
      },
    });
  } catch (e) {
    saveError.value = e.message || 'Update failed';
  } finally {
    saving.value = false;
  }
}

watch(
  () => props.id,
  () => load(),
  { immediate: true }
);
</script>

<style scoped>
.perm-flash-alert {
  margin-top: 0.75rem;
  margin-bottom: 1rem;
}

.perm-breadcrumbs {
  font-size: 0.8125rem;
  margin-bottom: 0.5rem;
}

.perm-breadcrumbs :deep(.v-breadcrumbs-item),
.perm-breadcrumbs :deep(.v-breadcrumbs-divider) {
  opacity: 0.7;
}

.perm-field-group + .perm-field-group {
  margin-top: 0.75rem;
}

.perm-form-actions {
  margin-top: 1.5rem;
  padding-top: 0.25rem;
}

.perm-field-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.9);
  margin-bottom: 4px;
}

.perm-field-input :deep(.v-field) {
  --v-input-control-height: 36px;
}

.perm-field-input :deep(.v-field__input) {
  min-height: 36px;
  padding-top: 6px;
  padding-bottom: 6px;
}

.perm-field-input :deep(textarea.v-field__input) {
  min-height: auto;
  padding-top: 8px;
  padding-bottom: 8px;
}
</style>
