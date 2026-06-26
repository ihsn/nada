<template>
  <v-app>
    <v-main class="catalog-study-notes-vue">
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4500">
        {{ snackbar.text }}
      </v-snackbar>

      <div class="csn-top-row">
        <div class="text-subtitle-1 font-weight-bold">{{ lbl.title || 'Notes' }}</div>
      </div>

      <v-card variant="flat" elevation="0" rounded="lg" class="csn-compose-card">
        <v-card-text class="csn-compose-card-body">
          <div class="text-subtitle-2 font-weight-semibold mb-3">
            {{ lbl.compose_title || 'Add note' }}
          </div>
          <div class="csn-compose-fields">
            <v-select
              v-model="noteType"
              :items="noteTypes"
              item-title="title"
              item-value="value"
              density="compact"
              variant="outlined"
              hide-details
              :placeholder="lbl.select_note_type || 'Note type'"
              class="csn-type"
            />
            <v-textarea
              v-model="noteText"
              auto-grow
              rows="3"
              density="compact"
              variant="outlined"
              hide-details
              :placeholder="lbl.placeholder || 'Type note...'"
            />
          </div>
          <div class="d-flex flex-wrap justify-end csn-compose-actions">
            <v-btn color="primary" size="small" class="text-none" :disabled="!canSubmit || saving" :loading="saving" @click="submitNote">
              {{ lbl.add_note || 'Submit' }}
            </v-btn>
          </div>
        </v-card-text>
      </v-card>

      <v-card variant="flat" elevation="0" rounded="lg" class="csn-section">
        <div class="text-subtitle-2 font-weight-semibold mb-2">
          {{ lbl.title || 'Notes' }}
          <span class="text-caption text-medium-emphasis font-weight-regular">({{ notes.length }})</span>
        </div>

        <div v-if="notes.length === 0" class="text-caption text-medium-emphasis py-6 px-2">
          {{ lbl.no_records || 'No records found' }}
        </div>
        <v-data-table
          v-else
          :headers="tableHeaders"
          :items="noteRows"
          item-value="id"
          density="compact"
          class="csn-table elevation-0 border rounded"
          :items-per-page="-1"
          hide-default-footer
        >
          <template #item.note="{ item }">
            <div class="csn-note-cell">{{ item.note }}</div>
          </template>
          <template #item.actions="{ item }">
            <v-btn
              size="x-small"
              variant="text"
              color="error"
              icon="mdi-delete"
              :title="lbl.remove || 'Remove'"
              :loading="deletingId === item.id"
              @click.prevent="removeNote(item.raw)"
            />
          </template>
        </v-data-table>
      </v-card>
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAppConfig } from '@/shared/composables/useAppConfig';
import { useStudyNotesApi } from './composables/useStudyNotesApi';

const { config } = useAppConfig();
const { listNotes, addNote, deleteNote } = useStudyNotesApi();

const lbl = computed(() => config.value?.labels || {});
const notes = ref([]);
const noteType = ref('admin');
const noteText = ref('');
const saving = ref(false);
const deletingId = ref(null);
const snackbar = ref({ show: false, text: '', color: 'surface' });

const noteTypes = computed(() => [
  { value: 'admin', title: lbl.value.admin_note || 'Admin note' },
  { value: 'reviewer', title: lbl.value.reviewer_note || 'Reviewer note' },
  { value: 'public', title: lbl.value.public_note || 'Public note' },
]);

const tableHeaders = computed(() => [
  { title: lbl.value.col_type || 'Type', key: 'typeTitle', sortable: false, width: '140px' },
  { title: lbl.value.col_when || 'When', key: 'whenLine', sortable: false, width: '220px' },
  { title: lbl.value.col_note || 'Note', key: 'note', sortable: false },
  { title: lbl.value.col_actions || 'Actions', key: 'actions', sortable: false, align: 'end', width: '56px' },
]);

const canSubmit = computed(() => String(noteText.value || '').trim() !== '' && String(noteType.value || '').trim() !== '');

const noteRows = computed(() =>
  notes.value.map((n) => ({
    id: n.id,
    raw: n,
    typeTitle: typeLabel(n.type),
    whenLine: `${n.username || 'user'} — ${formatDate(n.created)}`,
    note: n.note ?? '',
  })),
);

function showSnack(text, color = 'surface') {
  snackbar.value = { show: true, text, color };
}

function typeLabel(v) {
  if (v === 'admin') return lbl.value.admin_note || 'Admin note';
  if (v === 'reviewer') return lbl.value.reviewer_note || 'Reviewer note';
  if (v === 'public') return lbl.value.public_note || 'Public note';
  return v || '';
}

function formatDate(ts) {
  if (!ts) return '';
  let d = null;
  if (typeof ts === 'number' || /^\d+$/.test(String(ts))) {
    d = new Date(Number(ts) * 1000);
  } else {
    d = new Date(String(ts));
  }
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleString();
}

async function loadNotes() {
  const data = await listNotes();
  notes.value = Array.isArray(data.notes) ? data.notes : [];
}

async function submitNote() {
  if (!canSubmit.value) return;
  saving.value = true;
  try {
    await addNote({ type: noteType.value, note: noteText.value.trim() });
    noteText.value = '';
    await loadNotes();
    showSnack(lbl.value.saved || 'Saved', 'success');
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    saving.value = false;
  }
}

async function removeNote(note) {
  if (!note?.id) return;
  if (!window.confirm(lbl.value.confirm_remove || 'Remove note?')) return;
  deletingId.value = note.id;
  try {
    await deleteNote(note.id);
    await loadNotes();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  } finally {
    deletingId.value = null;
  }
}

onMounted(async () => {
  try {
    await loadNotes();
  } catch (e) {
    showSnack(String(e?.message || e), 'error');
  }
});
</script>

<style scoped>
.catalog-study-notes-vue {
  padding: 1.5rem 1.25rem 2rem;
}

.csn-top-row {
  width: 100%;
  margin-bottom: 1.5rem;
}

.csn-compose-card {
  background-color: rgba(var(--v-theme-on-surface), 0.06);
  margin-bottom: 1.5rem;
}

.csn-compose-card-body {
  padding: 1.25rem 1.5rem;
}

.csn-compose-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.csn-compose-actions {
  margin-top: 1.25rem;
}

.csn-section {
  background: transparent;
}

.csn-type {
  min-width: 200px;
  max-width: 320px;
}

.csn-table {
  border-radius: 8px;
  overflow: hidden;
}

.csn-table :deep(th),
.csn-table :deep(td) {
  font-size: 13px;
  vertical-align: top;
}

.csn-table :deep(th) {
  padding-top: 10px !important;
  padding-bottom: 10px !important;
}

.csn-note-cell {
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.45;
  max-width: min(56vw, 720px);
}
</style>
