<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Citation Search — Solr Backend
 *
 * Drop-in replacement for Citation_search_mysql / Citation_search_sqlsrv
 * when search_provider = 'solr'.
 *
 * Same public interface:
 *   search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid)
 * Sets $this->search_found_rows after each call.
 *
 * Limitations vs DB / OpenSearch backends:
 *   - authors returned as raw string (not structured array); Solr index stores
 *     the citations.authors text column, not the citation_authors table rows.
 *   - survey_count always 0 — not stored in Solr index.
 *   - no_survey_attached, has_notes, user, url_status, flag filters
 *     silently ignored — fields not in Solr citation index.
 *   - repositoryid filter silently ignored — survey linkage not in Solr index.
 *
 * Supported filters: keywords, ctype, from (pub_year >=), to (pub_year <=).
 */
class Citation_search_solr
{
    public $search_found_rows = 0;

    private $ci;
    private $solr_client;

    private $sort_fields = [
        'title'    => 'title_sort',
        'authors'  => 'cit_authors_sort',
        'pub_year' => 'pub_date',
        'created'  => 'created',
        'changed'  => 'changed',
        'ctype'    => 'ctype',
    ];

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->config->load('solr');
        $this->initialize_solr();
    }

    private function initialize_solr(): void
    {
        require_once(FCPATH . 'vendor/autoload.php');
        $config = [
            'endpoint' => [
                'localhost' => [
                    'host' => $this->ci->config->item('solr_host'),
                    'port' => $this->ci->config->item('solr_port'),
                    'path' => '/',
                    'core' => $this->ci->config->item('solr_collection'),
                ],
            ],
        ];
        $this->solr_client = new Solarium\Client(new Curl(), new EventDispatcher(), $config);
    }

    // =========================================================================
    // Public API
    // =========================================================================

    public function search(
        $limit        = null,
        $offset       = null,
        $filter       = null,
        $sort_by      = null,
        $sort_order   = null,
        $published    = null,
        $repositoryid = null
    ): array
    {
        $limit  = max(1, (int)($limit  ?? 15));
        $offset = max(0, (int)($offset ?? 0));

        $query   = $this->solr_client->createSelect();
        $helper  = $query->getHelper();
        $edismax = $query->getEDisMax();

        // Always restrict to citation documents
        $query->createFilterQuery('doctype')->setQuery('doctype:3');

        // Published filter
        if (is_numeric($published)) {
            $query->createFilterQuery('published')->setQuery('published:' . (int)$published);
        }

        $has_keywords = false;

        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                switch ($key) {

                    case 'keywords':
                        if (is_string($value) && trim($value) !== '') {
                            $escaped = preg_replace('/([+\-&|(){}[\]^"~*?:\/\\\\])/', '\\\\$1', trim($value));
                            $query->setQuery($escaped);
                            $has_keywords = true;
                        }
                        break;

                    case 'ctype':
                        if (is_array($value) && !empty($value)) {
                            $terms = implode(' OR ', array_map(
                                function ($v) use ($helper) { return 'ctype:' . $helper->escapeTerm((string)$v); },
                                $value
                            ));
                            $query->createFilterQuery('ctype')->setQuery('(' . $terms . ')');
                        }
                        break;

                    case 'from':
                        if (strlen((string)$value) === 4 && is_numeric($value)) {
                            $query->createFilterQuery('pub_year_from')
                                  ->setQuery('pub_date:[' . (int)$value . ' TO *]');
                        }
                        break;

                    case 'to':
                        if (strlen((string)$value) === 4 && is_numeric($value)) {
                            $query->createFilterQuery('pub_year_to')
                                  ->setQuery('pub_date:[* TO ' . (int)$value . ']');
                        }
                        break;

                    // flag, user, has_notes, no_survey_attached, url_status:
                    // not in Solr citation index — silently ignored
                }
            }
        }

        // eDisMax fields for fulltext
        $edismax->setQueryFields('title^3 subtitle authors^2 abstract keywords notes doi organization');

        // Sort
        $dir = (strtolower((string)$sort_order) === 'asc')
            ? $query::SORT_ASC
            : $query::SORT_DESC;

        if (!empty($sort_by) && isset($this->sort_fields[$sort_by])) {
            $query->addSort($this->sort_fields[$sort_by], $dir);
        } elseif ($has_keywords) {
            $query->addSort('score', $query::SORT_DESC);
        } else {
            $query->addSort('title_sort', $query::SORT_ASC);
        }

        $query->setStart($offset)->setRows($limit);
        $query->setFields([
            'id', 'citation_id', 'citation_uuid',
            'title', 'subtitle', 'authors',
            'volume', 'issue', 'edition',
            'place_publication', 'publisher',
            'ctype', 'abstract', 'keywords', 'notes', 'doi',
            'published', 'pub_date',
        ]);

        try {
            $resultset = $this->solr_client->select($query);
        } catch (Exception $e) {
            // Sort field may not exist in schema — retry without sort
            if (!empty($sort_by) && isset($this->sort_fields[$sort_by])) {
                log_message('error', 'Citation_search_solr::search sort failed, retrying without sort: ' . $e->getMessage());
                try {
                    $query->clearSorts();
                    $resultset = $this->solr_client->select($query);
                } catch (Exception $e2) {
                    log_message('error', 'Citation_search_solr::search: ' . $e2->getMessage());
                    $this->search_found_rows = 0;
                    return [];
                }
            } else {
                log_message('error', 'Citation_search_solr::search: ' . $e->getMessage());
                $this->search_found_rows = 0;
                return [];
            }
        }

        $this->search_found_rows = (int)$resultset->getNumFound();

        return $this->format_results($resultset);
    }

    // =========================================================================
    // Result formatting
    // =========================================================================

    private function format_results($resultset): array
    {
        $rows = [];
        foreach ($resultset as $doc) {
            $rows[] = [
                'id'                 => $doc->citation_id    ?? null,
                'uuid'               => $doc->citation_uuid  ?? null,
                'title'              => $doc->title          ?? null,
                'subtitle'           => $doc->subtitle       ?? null,
                'alt_title'          => null,
                'authors'            => $doc->authors        ?? null,
                'editors'            => null,
                'translators'        => null,
                'abstract'           => $doc->abstract       ?? null,
                'keywords'           => $doc->keywords       ?? null,
                'notes'              => $doc->notes          ?? null,
                'organization'       => null,
                'doi'                => $doc->doi            ?? null,
                'url'                => null,
                'ctype'              => $doc->ctype          ?? null,
                'pub_year'           => $doc->pub_date       ?? null,
                'pub_month'          => null,
                'pub_day'            => null,
                'volume'             => $doc->volume         ?? null,
                'issue'              => $doc->issue          ?? null,
                'edition'            => $doc->edition        ?? null,
                'place_publication'  => $doc->place_publication ?? null,
                'place_state'        => null,
                'publisher'          => $doc->publisher      ?? null,
                'page_from'          => null,
                'page_to'            => null,
                'publication_medium' => null,
                'flag'               => null,
                'url_status'         => null,
                'owner'              => null,
                'published'          => $doc->published      ?? null,
                'created'            => null,
                'changed'            => null,
                'survey_count'       => 0,
                'idnumber'           => null,
                'data_accessed'      => null,
                'changed_by_user'    => null,
                'created_by_user'    => null,
            ];
        }
        return $rows;
    }
}
