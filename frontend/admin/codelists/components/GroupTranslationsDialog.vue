<template>
  <v-dialog :model-value="modelValue" max-width="600" persistent @update:modelValue="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>Translations: {{ group?.name }}</v-card-title>
      <v-card-text>
        <v-table v-if="group">
          <thead>
            <tr>
              <th>Language</th>
              <th>Title</th>
              <th width="80" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="(title, lang) in translations" :key="lang">
              <td>{{ langLabel(lang) }}</td>
              <td>{{ title }}</td>
              <td>
                <v-btn size="small" variant="text" color="error" @click="removeTr(lang)">Remove</v-btn>
              </td>
            </tr>
            <tr v-if="Object.keys(translations || {}).length === 0">
              <td colspan="3" class="text-medium-emphasis">No translations yet.</td>
            </tr>
          </tbody>
        </v-table>
        <v-divider class="my-3" />
        <v-form ref="formRef" class="d-flex flex-wrap align-top gap-2">
          <v-select
            v-model="translationForm.lang"
            :items="languageItems"
            item-title="display"
            item-value="code"
            label="Language"
            density="compact"
            style="max-width: 200px"
            :rules="[v => !!v || 'Required']"
          />
          <v-text-field
            v-model="translationForm.title"
            label="Title"
            density="compact"
            style="min-width: 180px"
            :rules="[v => !!v?.trim() || 'Required']"
          />
          <v-btn color="primary" :loading="saving" @click="addTranslation" class="align-top">Add</v-btn>
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="$emit('update:modelValue', false)">Close</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';

defineOptions({ name: 'GroupTranslationsDialog' });

const props = defineProps({
  modelValue: Boolean,
  group: { type: Object, default: null },
  translations: { type: Object, default: () => ({}) },
  enabledLanguages: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'add-translation', 'remove-translation']);

const formRef = ref(null);
const translationForm = reactive({ lang: '', title: '' });
const saving = ref(false);

const languageItems = computed(() =>
  (props.enabledLanguages || []).map((item) => ({
    code: item.code,
    display: item.display || item.code,
  }))
);

function langLabel(lang) {
  const item = (props.enabledLanguages || []).find((e) => e.code === lang);
  if (item) return `${item.display} (${lang})`;
  return lang;
}

function addTranslation() {
  if (!translationForm.lang || !translationForm.title?.trim()) return;
  emit('add-translation', { lang: translationForm.lang, title: translationForm.title.trim() });
  translationForm.title = '';
}

function removeTr(lang) {
  emit('remove-translation', lang);
}

watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      translationForm.lang = '';
      translationForm.title = '';
    }
  }
);
</script>
