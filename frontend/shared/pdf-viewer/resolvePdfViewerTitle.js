import { joinSiteUrl } from './siteUrl';
import { fetchJson } from '@/shared/http/fetchJson';

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
      const data = await fetchJson(`${apiBase}${sid}`, {
        params: { id_format: 'id' },
      });
      studyTitle = data?.dataset?.title || '';
      studyIdno = data?.dataset?.idno || '';
    } else if (studyIdno) {
      const data = await fetchJson(`${apiBase}${encodeURIComponent(studyIdno)}`);
      studyTitle = data?.dataset?.title || '';
    }

    const rid = Number(resourceId);
    if (studyIdno && Number.isFinite(rid) && rid > 0) {
      const data = await fetchJson(`${apiBase}${encodeURIComponent(studyIdno)}/resources`);
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
