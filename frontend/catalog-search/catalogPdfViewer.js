import { buildPdfViewerUrl } from '@/shared/pdf-viewer/pdfViewerUrl';

/**
 * Matched pages of a document study (`row.semantic_document_pages`), best first. Each names the PDF it is in
 * (`resource_id`, `resource_title`).
 * @param {object} row
 * @returns {object[]}
 */
export function semanticPageHits(row) {
  return Array.isArray(row?.semantic_document_pages) ? row.semantic_document_pages : [];
}

/**
 * The matched pages grouped by PDF, in the order of each PDF's best page.
 * @param {object} row
 * @returns {{ resourceId: number, title: string, pages: object[] }[]}
 */
export function semanticPdfGroups(row) {
  const groups = new Map();
  for (const hit of semanticPageHits(row)) {
    const resourceId = Number(hit?.resource_id);
    if (!groups.has(resourceId)) {
      groups.set(resourceId, { resourceId, title: hit?.resource_title || '', pages: [] });
    }
    groups.get(resourceId).pages.push(hit);
  }
  return [...groups.values()];
}

/**
 * What the viewer needs to open the PDF a matched page is in, at that page.
 * @param {object} row
 * @param {object|null} pageHit
 * @returns {{
 *   sid: number,
 *   resourceId: number,
 *   title: string,
 *   initialPage: number,
 *   pageChips: number[],
 * }|null} null when the page does not name a PDF of the study
 */
export function semanticPdfContext(row, pageHit) {
  const sid = Number(row?.id);
  const resourceId = Number(pageHit?.resource_id);
  if (!(sid > 0) || !(resourceId > 0)) return null;

  // chips: the matched pages of this PDF only
  const pageChips = [];
  for (const hit of semanticPageHits(row)) {
    const page = Number(hit?.page);
    if (Number(hit?.resource_id) === resourceId && page > 0 && !pageChips.includes(page)) {
      pageChips.push(page);
    }
  }

  const page = Number(pageHit?.page);
  return {
    sid,
    resourceId,
    title: pageHit?.resource_title || row?.title || '',
    initialPage: page > 0 ? page : pageChips[0] || 1,
    pageChips,
  };
}

/**
 * Viewer URL for catalog semantic PDF preview.
 * @param {string} siteUrl
 * @param {ReturnType<typeof semanticPdfContext>} context
 * @param {{ embed?: boolean }} [options]
 * @returns {string}
 */
export function buildPdfViewerUrlFromContext(siteUrl, context, options = {}) {
  if (!context) return '';
  return buildPdfViewerUrl(siteUrl, {
    source: 'resource',
    sid: context.sid,
    resourceId: context.resourceId,
    page: context.initialPage,
    pages: context.pageChips,
    embed: options.embed === true,
  });
}
