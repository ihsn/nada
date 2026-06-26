import axios from 'axios';
import { joinSiteUrl } from './siteUrl';

/**
 * Resolve display title: resource title preferred, else study title.
 * @param {{
 *   siteUrl: string,
 *   sid?: number|null,
 *   idno?: string,
 *   resourceId?: number|null,
 * }} opts
 * @returns {Promise<string>}
 */
export async function resolvePdfViewerTitle({ siteUrl, sid, idno, resourceId }) {
  const apiBase = joinSiteUrl(siteUrl, 'api/catalog/').replace(/\/?$/, '/');
  let studyIdno = String(idno || '').trim();
  let studyTitle = '';

  try {
    if (!studyIdno && sid) {
      const { data } = await axios.get(`${apiBase}${sid}`, {
        params: { id_format: 'id' },
        withCredentials: true,
      });
      studyTitle = data?.dataset?.title || '';
      studyIdno = data?.dataset?.idno || '';
    } else if (studyIdno) {
      const { data } = await axios.get(`${apiBase}${encodeURIComponent(studyIdno)}`, {
        withCredentials: true,
      });
      studyTitle = data?.dataset?.title || '';
    }

    const rid = Number(resourceId);
    if (studyIdno && Number.isFinite(rid) && rid > 0) {
      const { data } = await axios.get(`${apiBase}${encodeURIComponent(studyIdno)}/resources`, {
        withCredentials: true,
      });
      const resources = data?.resources;
      if (Array.isArray(resources)) {
        const resource = resources.find((row) => Number(row.resource_id) === rid);
        if (resource?.title) {
          return String(resource.title).trim();
        }
      }
    }
  } catch {
    /* fall through to study title */
  }

  return studyTitle;
}
