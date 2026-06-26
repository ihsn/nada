import { buildPdfStreamUrl } from '@/shared/pdf-viewer/pdfStreamUrl';
import { buildPdfViewerUrl } from '@/shared/pdf-viewer/pdfViewerUrl';
import { resolvePrimaryPdfResourceId } from '@/shared/pdf-viewer/resolvePrimaryPdfResource';

/**
 * @param {object} row
 * @returns {number[]}
 */
function semanticPageNumbers(row) {
  return (Array.isArray(row?.semantic_document_pages) ? row.semantic_document_pages : [])
    .map((hit) => hit?.page)
    .filter((n) => Number.isFinite(Number(n)) && Number(n) > 0)
    .map((n) => Number(n));
}

/**
 * Resolve stream URL and page state for semantic document PDF viewing.
 * @param {{
 *   row: object,
 *   pageHit?: object,
 *   siteUrl: string,
 *   apiBaseUrl: string,
 * }} opts
 * @returns {Promise<{
 *   streamUrl: string,
 *   initialPage: number,
 *   pageChips: number[],
 *   resourceId: number,
 *   sid: number|string,
 *   idno: string,
 * }|null>}
 */
export async function resolveSemanticPdfContext({ row, pageHit, siteUrl, apiBaseUrl }) {
  const idno = row?.idno;
  if (!idno) return null;

  const resourceId = await resolvePrimaryPdfResourceId(idno, apiBaseUrl);
  if (!resourceId) return null;

  const pages = semanticPageNumbers(row);
  const page = pageHit?.page != null ? Number(pageHit.page) : pages[0] || 1;

  return {
    streamUrl: buildPdfStreamUrl(siteUrl, {
      source: 'resource',
      sid: row.id,
      resourceId,
    }),
    initialPage: page,
    pageChips: pages,
    resourceId,
    sid: row.id,
    idno,
  };
}

/**
 * Viewer URL for catalog semantic PDF preview.
 * @param {string} siteUrl
 * @param {Awaited<ReturnType<typeof resolveSemanticPdfContext>>} context
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
