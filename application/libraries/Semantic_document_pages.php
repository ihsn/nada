<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Normalize semantic search PDF passage hits for document study cards.
 *
 * Mirrors nada-ai demo logic: outer hit + inner_hits.variants, qfield=passages,
 * metadata.doc_meta.page (0-based index).
 */
class Semantic_document_pages
{
    private const INNER_HITS_NAME = 'variants';
    private const PASSAGE_QFIELD    = 'passages';

    /**
     * @param array|null $hit Semantic API hit (collapsed parent per idno)
     * @return array<int, array{page_index: int, page: int, total_pages?: int, score?: float, excerpt?: string}>
     */
    public static function from_hit($hit): array
    {
        if (!is_array($hit) || empty($hit)) {
            return [];
        }

        $by_page = [];
        $variants = [self::variant_from_hit($hit)];
        foreach (self::collect_inner_hit_variants($hit) as $inner_hit) {
            $variants[] = self::variant_from_hit($inner_hit);
        }

        foreach ($variants as $variant) {
            if (!self::is_passage_variant($variant)) {
                continue;
            }
            $idx = $variant['page_index'];
            if (
                !isset($by_page[$idx])
                || self::score_is_greater($variant['score'], $by_page[$idx]['score'])
            ) {
                $by_page[$idx] = $variant;
            }
        }

        if (empty($by_page)) {
            return [];
        }

        $pages = array_values($by_page);
        usort($pages, [self::class, 'compare_by_score_desc']);

        $out = [];
        foreach ($pages as $page) {
            $out[] = self::normalize_page_entry($page);
        }

        return $out;
    }

    /**
     * @param array $hit
     * @return array{page_index: int, total_pages: int|null, score: float|null, excerpt: string, qfield: string}
     */
    private static function variant_from_hit(array $hit): array
    {
        $source   = isset($hit['_source']) && is_array($hit['_source']) ? $hit['_source'] : [];
        $metadata = isset($source['metadata']) && is_array($source['metadata']) ? $source['metadata'] : [];
        $doc_meta = isset($metadata['doc_meta']) && is_array($metadata['doc_meta']) ? $metadata['doc_meta'] : [];

        $page_index = null;
        if (isset($doc_meta['page']) && is_numeric($doc_meta['page'])) {
            $page_index = (int) $doc_meta['page'];
        }

        $total_pages = null;
        if (isset($doc_meta['total_pages']) && is_numeric($doc_meta['total_pages'])) {
            $total_pages = (int) $doc_meta['total_pages'];
        }

        $score = isset($hit['_score']) && is_numeric($hit['_score']) ? (float) $hit['_score'] : null;

        $qfield = isset($metadata['qfield']) ? trim((string) $metadata['qfield']) : '';

        $page_content = isset($source['page_content']) ? (string) $source['page_content'] : '';
        $excerpt = self::normalize_excerpt($page_content);

        return [
            'page_index'  => $page_index,
            'total_pages' => $total_pages,
            'score'       => $score,
            'excerpt'     => $excerpt,
            'qfield'      => $qfield,
        ];
    }

    /**
     * @param array $variant
     */
    private static function is_passage_variant(array $variant): bool
    {
        return $variant['qfield'] === self::PASSAGE_QFIELD
            && $variant['page_index'] !== null
            && $variant['page_index'] >= 0;
    }

    /**
     * @param array $hit
     * @return array<int, array>
     */
    private static function collect_inner_hit_variants(array $hit): array
    {
        $inner_hits = $hit['inner_hits'] ?? null;
        if (!is_array($inner_hits) || empty($inner_hits)) {
            return [];
        }

        $bucket = $inner_hits[self::INNER_HITS_NAME] ?? null;
        if (!is_array($bucket)) {
            $first = reset($inner_hits);
            $bucket = is_array($first) ? $first : null;
        }

        if (!is_array($bucket)) {
            return [];
        }

        $hits = $bucket['hits']['hits'] ?? null;
        return is_array($hits) ? $hits : [];
    }

    /**
     * @param array $a
     * @param array $b
     */
    private static function compare_by_score_desc(array $a, array $b): int
    {
        $sa = $a['score'] ?? -INF;
        $sb = $b['score'] ?? -INF;
        if ($sb !== $sa) {
            return $sb <=> $sa;
        }

        $pa = $a['page_index'] ?? 0;
        $pb = $b['page_index'] ?? 0;
        return $pa <=> $pb;
    }

    /**
     * @param float|null $a
     * @param float|null $b
     */
    private static function score_is_greater($a, $b): bool
    {
        if ($a === null) {
            return false;
        }
        if ($b === null) {
            return true;
        }
        return $a > $b;
    }

    /**
     * @param array $variant
     * @return array{page_index: int, page: int, total_pages?: int, score?: float, excerpt?: string}
     */
    private static function normalize_page_entry(array $variant): array
    {
        $entry = [
            'page_index' => (int) $variant['page_index'],
            'page'       => (int) $variant['page_index'] + 1,
        ];

        if ($variant['total_pages'] !== null && $variant['total_pages'] > 0) {
            $entry['total_pages'] = (int) $variant['total_pages'];
        }

        if ($variant['score'] !== null) {
            $entry['score'] = round((float) $variant['score'], 4);
        }

        if ($variant['excerpt'] !== '') {
            $entry['excerpt'] = $variant['excerpt'];
        }

        return $entry;
    }

    private static function normalize_excerpt(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
