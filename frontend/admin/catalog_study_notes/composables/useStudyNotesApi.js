import axios from 'axios';
import { useAppConfig } from '@/shared/composables/useAppConfig';

export function useStudyNotesApi() {
  const { config, csrfToken, csrfTokenName } = useAppConfig();

  function catalogBase() {
    return String(config.value?.apiBaseUrl || '').replace(/\/+$/, '') + '/';
  }

  function studyRef() {
    const idno = config.value?.studyIdno;
    if (idno != null && String(idno).trim() !== '') {
      return encodeURIComponent(String(idno).trim());
    }
    const sid = config.value?.studySid;
    if (sid == null || sid === '') throw new Error('studySid missing');
    return encodeURIComponent(String(sid));
  }

  function studyParams() {
    const idno = config.value?.studyIdno;
    if (idno != null && String(idno).trim() !== '') return {};
    return { id_format: 'id' };
  }

  function csrfHeaders() {
    const name = csrfTokenName.value || 'ncsrf';
    const tok = csrfToken.value || '';
    if (!tok) return {};
    return {
      [name]: tok,
      'X-CSRF-TOKEN': tok,
      'X-Requested-With': 'XMLHttpRequest',
    };
  }

  async function listNotes() {
    const { data } = await axios.get(`${catalogBase()}${studyRef()}/notes`, {
      params: studyParams(),
      withCredentials: true,
    });
    if (data.status !== 'success') throw new Error(data.message || 'LOAD_NOTES_FAILED');
    return data;
  }

  async function addNote(payload) {
    const { data } = await axios.post(`${catalogBase()}${studyRef()}/notes`, payload, {
      params: studyParams(),
      withCredentials: true,
      headers: csrfHeaders(),
    });
    if (data.status !== 'success') throw new Error(data.message || 'ADD_NOTE_FAILED');
    return data;
  }

  async function deleteNote(noteId) {
    const { data } = await axios.delete(`${catalogBase()}${studyRef()}/notes/${encodeURIComponent(String(noteId))}`, {
      params: studyParams(),
      withCredentials: true,
      headers: csrfHeaders(),
    });
    if (data.status !== 'success') throw new Error(data.message || 'DELETE_NOTE_FAILED');
    return data;
  }

  return { listNotes, addNote, deleteNote };
}

