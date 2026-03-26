<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Resolve catalog study/variable listing sort parameters.
 *
 * browse (no full-text keywords): apply site default or title; explicit user sort preserved.
 * keyword search: relevance (score) desc unless user picked a non-relevance field.
 */
class Catalog_study_sort
{
    /** @var string[] */
    public static $allowed_sort_fields = array('year', 'title', 'nation', 'country', 'popularity', 'rank', 'relevance');

    /** @var string[] */
    public static $allowed_sort_order = array('asc', 'desc');

    /**
     * @param string $fulltext_keywords Trimmed or raw; empty = browse mode
     * @param string $sort_by           Request sort field (may be invalid)
     * @param string $sort_order        Request order (may be invalid)
     * @param string $default_by        catalog_default_sort_by
     * @param string $default_order     catalog_default_sort_order
     * @return array{0:string,1:string} [ sort_by, sort_order ]
     */
    public static function resolve($fulltext_keywords, $sort_by, $sort_order, $default_by, $default_order)
    {
        $ft = trim((string) $fulltext_keywords);
        $sb = trim((string) $sort_by);
        $so = strtolower(trim((string) $sort_order));

        if (! in_array($sb, self::$allowed_sort_fields, true)) {
            $sb = '';
        }
        if (! in_array($so, self::$allowed_sort_order, true)) {
            $so = '';
        }

        if ($ft === '') {
            if ($sb === '') {
                $dby = trim((string) $default_by);
                $sb  = in_array($dby, self::$allowed_sort_fields, true) ? $dby : 'title';
                $dor = strtolower(trim((string) $default_order));
                $so  = in_array($dor, self::$allowed_sort_order, true) ? $dor : 'asc';
            } elseif ($so === '') {
                $so = 'asc';
            }

            return array($sb, $so);
        }

        if ($sb === '' || $sb === 'rank' || $sb === 'relevance') {
            return array('relevance', 'desc');
        }
        if ($so === '') {
            $so = 'asc';
        }

        return array($sb, $so);
    }
}
