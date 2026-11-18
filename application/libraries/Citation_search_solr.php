<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once(APPPATH . 'libraries/Citation_csl.php');

/**
 * Citation Search — Solr Backend
 *
 * Drop-in replacement for Citation_search_mysql / Citation_search_sqlsrv
 * when search_engine = 'solr'.
 *
 * Same public interface:
 *   search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid)
 * Sets $this->search_found_rows after each call.
 *
 * Solr returns the matching citation ids; the rows are loaded from the database
 * (Citation_model::get_rows_by_ids), so they have the same shape as the database search.
 *
 * Limitations vs the DB and nada-ai backends:
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
                        // the index holds CSL types (citations.ctype); legacy NADA type names are mapped
                        $types = Citation_csl::to_csl_types($value);
                        if (!empty($types)) {
                            $terms = implode(' OR ', array_map(
                                function ($v) use ($helper) { return 'ctype:' . $helper->escapeTerm((string)$v); },
                                $types
                            ));
                            $query->createFilterQuery('ctype')->setQuery('(' . $terms . ')');
                        } elseif (!empty($value)) {
                            $query->createFilterQuery('ctype')->setQuery('ctype:__none__');
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

        // eDisMax fields for fulltext (notes are internal and not indexed)
        $edismax->setQueryFields('title^3 subtitle authors^2 abstract keywords doi publisher');

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
        $query->setFields(['citation_id']);

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

    /** Solr gives the matching ids in order; the rows come from the database (Citation_model::get_rows_by_ids). */
    private function format_results($resultset): array
    {
        $ids = [];
        foreach ($resultset as $doc) {
            if (isset($doc->citation_id)) {
                $ids[] = (int)$doc->citation_id;
            }
        }
        return $this->ci->Citation_model->get_rows_by_ids($ids);
    }
}
