<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

if (! class_exists('Catalog_study_sort', false)) {
    require_once APPPATH . 'libraries/Catalog_study_sort.php';
}

/**
 * Catalog search implementation for Apache Solr (Solarium client).
 *
 * Public interface mirrors Catalog_search_mysql:
 *   search($limit, $offset)              → study search
 *   vsearch($limit, $offset)             → variable search
 *   v_quick_search($sid, $limit, $offset)→ variable search within one survey
 */
class Catalog_search_solr
{
    var $ci;

    var $study_keywords    = '';
    var $variable_keywords = '';
    var $topics            = array();
    var $countries         = array();
    var $regions           = array();
    var $from              = 0;
    var $to                = 0;
    var $repo              = '';
    var $collections       = array();
    var $type              = array();
    var $dtype             = array();
    var $sid               = '';
    var $created           = '';
    var $debug             = false;
    var $params            = null;
    var $solr_options      = array();
    var $varcount          = '';

    // Solr field → legacy result key mapping (variables only)
    private $variable_field_map = array(
        'var_label'      => 'labl',
        'var_name'       => 'name',
        'var_question'   => 'qstn',
        'var_categories' => 'catgry',
        'var_survey_id'  => 'sid',
        'var_uid'        => 'uid',
    );

    var $sort_allowed_fields = array(
        'title'      => 'title_sort',
        'nation'     => 'nation_sort',
        'country'    => 'nation_sort',
        'year'       => 'year_start',
        'popularity' => 'total_views',
        'rank'       => 'score',
        'relevance'  => 'score',
    );

    var $sort_allowed_order = array('asc', 'desc');
    var $sort_by    = 'title';
    var $sort_order = 'asc';

    // Allowed field prefixes for field:value query syntax in study search
    private $allowed_search_fields = array(
        'title'       => 'title',
        'nation'      => 'nation',
        'country'     => 'nation',
        'year'        => 'year_start',
        'author'      => 'authoring_entity',
        'abstract'    => 'abstract',
        'keywords'    => 'keywords',
        'methodology' => 'methodology',
        'idno'        => 'idno',
        'type'        => 'dataset_type',
    );

    // Allowed field prefixes for field:value query syntax in variable search
    private $allowed_variable_search_fields = array(
        'var_name'       => 'var_name',
        'var_label'      => 'var_label',
        'var_question'   => 'var_question',
        'var_categories' => 'var_categories',
        'survey_title'   => 'title',
        'survey_nation'  => 'nation',
        'survey_year'    => 'year_start',
    );

    // -------------------------------------------------------------------------
    // Constructor / initialisation
    // -------------------------------------------------------------------------

    function __construct($params = array())
    {
        $this->ci = &get_instance();
        $this->ci->config->load('solr');
        $this->ci->load->model('Facet_model');
        $this->user_facets = $this->ci->Facet_model->select_all('user');

        if ($this->ci->config->item('regional_search') == 'yes') {
            $this->sort_by = 'nation';
        }

        $this->solr_options          = $this->ci->config->item('solr_edismax_options');
        $this->solr_variable_options = $this->ci->config->item('solr_edismax_variable_options');

        if ($this->ci->config->item('solr_debug') == true) {
            $this->debug = true;
        }

        if (count($params) > 0) {
            $this->initialize($params);
        }

        $this->params = $params;
    }

    function initialize($params = array())
    {
        foreach ($params as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $this->validate_parameter($key, $val);
            }
        }
        $this->initialize_solr();
    }

    private function initialize_solr()
    {
        require('vendor/autoload.php');
        $config = array(
            'endpoint' => array(
                'localhost' => array(
                    'host' => $this->ci->config->item('solr_host'),
                    'port' => $this->ci->config->item('solr_port'),
                    'path' => '/',
                    'core' => $this->ci->config->item('solr_collection'),
                ),
            ),
        );
        $this->solr_client = new Solarium\Client(new Curl(), new EventDispatcher(), $config);
    }

    // -------------------------------------------------------------------------
    // Public search methods
    // -------------------------------------------------------------------------

    function search($limit = 15, $offset = 0)
    {
        $query   = $this->solr_client->createSelect();
        $helper  = $query->getHelper();
        $edismax = $query->getEDisMax();

        // Facet: dataset types — exclude own tag so the type filter doesn't
        // collapse counts for the active type
        $query->getFacetSet()
              ->createFacetField('dataset_types')
              ->setField('dataset_type')
              ->getLocalParameters()->addExcludes(['tag_dataset_type']);

        // Base filters
        $query->createFilterQuery('published')->setQuery('published:1');
        $query->createFilterQuery('doctype')->setQuery('doctype:1');

        // Keywords
        if ($this->study_keywords) {
            $query->setQuery($this->escape_keywords($this->study_keywords, $helper));
        }

        // Filters
        $this->apply_filter($query, 'dataset_type', $this->_build_dataset_type_query(), 'tag_dataset_type');
        $this->apply_filter($query, 'countries',    $this->_build_countries_query());
        $this->apply_filter($query, 'regions',      $this->_build_regions_query());
        $this->apply_filter($query, 'topics',       $this->_build_topics_query());
        $this->apply_filter($query, 'collections',  $this->_build_collections_query());
        $this->apply_filter($query, 'dtype',        $this->_build_dtype_query());
        $this->apply_filter($query, 'varcount',     $this->_build_varcount_query());
        $this->apply_filter($query, 'created',      $this->_build_created_query());

        if (!empty($this->repo)) {
            $query->createFilterQuery('repo')
                  ->setQuery('repositories:' . $helper->escapeTerm((string)$this->repo));
        }

        $years = $this->_build_years_query();
        if ($years) {
            foreach ($years as $k => $y) {
                $query->createFilterQuery('years' . $k)->setQuery($y);
            }
        }

        // User-defined facets
        foreach ($this->user_facets as $fc) {
            if (array_key_exists($fc['name'], $this->params)) {
                $fq = $this->_build_facet_query('fq_' . $fc['name'], $this->params[$fc['name']]);
                if ($fq) {
                    $query->createFilterQuery('fq_' . $fc['name'])->setQuery($fq);
                }
            }
        }

        $edismax->setQueryFields($this->solr_options['qf']);
        $edismax->setMinimumMatch($this->solr_options['mm']);
        $this->apply_sorting($query, $this->study_keywords);

        $query->setStart($offset)->setRows($limit);
        $query->setFields(array(
            'id:survey_uid', 'idno', 'doi',
            'type:dataset_type', 'title', 'subtitle', 'nation',
            'abstract',
            'formid', 'form_model', 'repositoryid', 'repo_title',
            'total_views', 'total_downloads', 'link_da',
            'created', 'changed', 'year_start', 'year_end',
            'authoring_entity', 'data_class_id',
            'rank:score', 'thumbnail', 'varcount',
        ));

        if ($this->debug) {
            $query->getDebug();
        }

        $resultset = $this->solr_client->select($query);

        // Dataset type facet counts
        $facet       = $resultset->getFacetSet()->getFacet('dataset_types');
        $type_counts = array();
        if ($facet) {
            foreach ($facet as $value => $count) {
                $type_counts[$value] = $count;
            }
        }
        if (empty($type_counts)) {
            $type_counts = $this->get_dataset_type_counts_from_solr();
        }

        $docs = $resultset->getData()['response']['docs'];

        $result = array(
            'found'                 => $resultset->getNumFound(),
            'total'                 => $this->solr_total_count(1),
            'limit'                 => $limit,
            'offset'                => $offset,
            'rows'                  => $docs,
            'citations'             => $this->fetch_citation_counts($docs),
            'search_counts_by_type' => $type_counts,
        );

        if ($this->debug) {
            $req = $this->solr_client->createRequest($query);
            $result['debug'] = array(
                'request_uri' => urldecode($req->getUri()),
                'solr_debug'  => $resultset->getDebug(),
                'type_counts' => $type_counts,
            );
        }

        return $result;
    }

    function vsearch($limit = 15, $offset = 0)
    {
        $query   = $this->solr_client->createSelect();
        $helper  = $query->getHelper();
        $edismax = $query->getEDisMax();

        $query->setFields(array(
            'var_uid', 'vid', 'fid',
            'var_label', 'var_name', 'var_question',
            'var_survey_id', 'title', 'nation', 'idno',
        ));

        // Base filter: variables only
        $query->createFilterQuery('doctype')->setQuery('doctype:2');

        // Keywords — use variable_keywords, not study_keywords
        if ($this->variable_keywords) {
            $query->setQuery($this->escape_keywords($this->variable_keywords, $helper));
        }

        // Filters
        $this->apply_filter($query, 'countries',    $this->_build_countries_query());
        $this->apply_filter($query, 'dtype',        $this->_build_dtype_query());
        $this->apply_filter($query, 'dataset_type', $this->_build_dataset_type_query());
        $this->apply_filter($query, 'collections',  $this->_build_collections_query());

        if (!empty($this->repo)) {
            $query->createFilterQuery('repo')
                  ->setQuery('repositories:' . $helper->escapeTerm((string)$this->repo));
        }

        $years = $this->_build_years_query_for_variables();
        if ($years) {
            foreach ($years as $k => $y) {
                $query->createFilterQuery('years' . $k)->setQuery($y);
            }
        }

        $edismax->setQueryFields($this->solr_variable_options['qf']);
        $edismax->setMinimumMatch($this->solr_variable_options['mm']);

        $this->apply_sorting($query, $this->variable_keywords);

        $query->setStart($offset)->setRows($limit);

        $resultset = $this->solr_client->select($query);
        $docs      = $this->map_variable_fields($resultset->getData()['response']['docs']);

        $result = array(
            'found'  => $resultset->getNumFound(),
            'total'  => $this->solr_total_count(2),
            'limit'  => $limit,
            'offset' => $offset,
            'rows'   => $docs,
        );

        if ($this->debug) {
            $req = $this->solr_client->createRequest($query);
            $result['debug'] = array(
                'request_uri'       => urldecode($req->getUri()),
                'variable_keywords' => $this->variable_keywords,
            );
        }

        return $result;
    }

    function v_quick_search($surveyid = null, $limit = 50, $offset = 0)
    {
        $surveyid = (int)$surveyid;
        if ($surveyid <= 0) {
            return array('found' => 0, 'total' => 0, 'limit' => $limit, 'offset' => $offset, 'rows' => array());
        }

        $query   = $this->solr_client->createSelect();
        $helper  = $query->getHelper();
        $edismax = $query->getEDisMax();

        $query->setFields(array(
            'var_uid', 'vid', 'fid',
            'var_label', 'var_name', 'var_question',
        ));

        $query->createFilterQuery('doctype')->setQuery('doctype:2');
        $query->createFilterQuery('survey')->setQuery('var_survey_id:' . $surveyid);

        if ($this->variable_keywords) {
            $query->setQuery($this->escape_keywords($this->variable_keywords, $helper));
        }

        $edismax->setQueryFields($this->solr_variable_options['qf']);
        $edismax->setMinimumMatch($this->solr_variable_options['mm']);

        $this->apply_sorting($query, $this->variable_keywords);

        $query->setStart($offset)->setRows($limit);

        $resultset = $this->solr_client->select($query);
        $docs      = $this->map_variable_fields($resultset->getData()['response']['docs']);

        $this->ci->db->where('sid', $surveyid);
        $total = $this->ci->db->count_all_results('variables');

        $result = array(
            'found'  => $resultset->getNumFound(),
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
            'rows'   => $docs,
        );

        if ($this->debug) {
            $req = $this->solr_client->createRequest($query);
            $result['debug'] = array(
                'request_uri'       => urldecode($req->getUri()),
                'variable_keywords' => $this->variable_keywords,
                'survey_id'         => $surveyid,
            );
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Filter builders — each returns a Solr filter string or false
    // -------------------------------------------------------------------------

    private function _build_topics_query()
    {
        $ids = array_filter(array_map('intval', (array)$this->topics));
        if (empty($ids)) return false;
        return 'topics_id:(' . implode(' OR ', $ids) . ')';
    }

    private function _build_countries_query()
    {
        $countries = (array)$this->countries;
        if (empty($countries)) return false;

        if (!is_numeric($countries[0])) {
            $countries = $this->get_country_id_by_name($countries);
        }

        $ids = array_filter(array_map('intval', $countries));
        if (empty($ids)) return false;
        return 'countries:(' . implode(' OR ', $ids) . ')';
    }

    private function _build_regions_query()
    {
        $ids = array_filter(array_map('intval', (array)$this->regions));
        if (empty($ids)) return false;
        return 'regions:(' . implode(' OR ', $ids) . ')';
    }

    private function _build_years_query()
    {
        $from = (int)$this->from;
        $to   = (int)$this->to;
        if ($from === 0 && $to === 0) return false;
        $f = $from > 0 ? $from : '*';
        $t = $to   > 0 ? $to   : '*';
        return array(sprintf('years:[%s TO %s]', $f, $t));
    }

    private function _build_years_query_for_variables()
    {
        $from = (int)$this->from;
        $to   = (int)$this->to;
        if ($from === 0 && $to === 0) return false;
        $f = $from > 0 ? $from : '*';
        $t = $to   > 0 ? $to   : '*';
        // Variables use year_start from the parent survey (not the multivalue 'years' field)
        return array(sprintf('year_start:[%s TO %s]', $f, $t));
    }

    private function _build_dataset_type_query()
    {
        $types = array_filter(array_map('trim', (array)$this->type));
        if (empty($types)) return false;
        // Strip all chars that are not safe in a Solr term
        $safe = array_filter(array_map(function($t) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '', $t);
        }, $types));
        if (empty($safe)) return false;
        return 'dataset_type:(' . implode(' OR ', $safe) . ')';
    }

    private function _build_collections_query()
    {
        $repos = array_filter(array_map('trim', (array)$this->collections));
        if (empty($repos)) return false;
        $safe = array_filter(array_map(function($r) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '', $r);
        }, $repos));
        if (empty($safe)) return false;
        return 'repositories:(' . implode(' OR ', $safe) . ')';
    }

    private function _build_dtype_query()
    {
        $ids = array_filter(array_map('intval', (array)$this->dtype));
        if (empty($ids)) return false;
        return 'formid:(' . implode(' OR ', $ids) . ')';
    }

    private function _build_varcount_query()
    {
        if ($this->varcount === '>0' || $this->varcount === 'with_vars' || $this->varcount === '1') {
            return 'varcount:[1 TO *]';
        }
        if ($this->varcount === '0') {
            return 'varcount:0';
        }
        return false;
    }

    private function _build_created_query()
    {
        $parts = explode('-', $this->created);
        $start = strtotime($parts[0] ?? '');
        if (!$start) return false;
        $end = (isset($parts[1]) && strtotime($parts[1]))
            ? strtotime($parts[1]) + 86399
            : $start + 86399;
        return sprintf('created:[%d TO %d]', $start, $end);
    }

    protected function _build_facet_query($facet_name, $values)
    {
        $values = array_filter(array_map('intval', (array)$values));
        if (empty($values)) return false;
        return $facet_name . ':(' . implode(' OR ', $values) . ')';
    }

    // -------------------------------------------------------------------------
    // Helper: apply a filter query only when value is non-false
    // -------------------------------------------------------------------------

    private function apply_filter($query, $key, $value, $tag = null)
    {
        if ($value === false || $value === '' || $value === null) return;
        $fq = $query->createFilterQuery($key)->setQuery($value);
        if ($tag) {
            $fq->addTag($tag);
        }
    }

    // -------------------------------------------------------------------------
    // Sorting
    // -------------------------------------------------------------------------

    private function apply_sorting($query, $fulltext_keywords)
    {
        $def_by  = $this->ci->config->item('catalog_default_sort_by');
        $def_ord = $this->ci->config->item('catalog_default_sort_order');
        list($sort_by, $sort_order) = Catalog_study_sort::resolve(
            $fulltext_keywords,
            $this->sort_by,
            $this->sort_order,
            $def_by,
            $def_ord
        );

        $asc  = $query::SORT_ASC;
        $desc = $query::SORT_DESC;
        $dir  = ($sort_order === 'asc') ? $asc : $desc;

        $query->addSort($this->sort_allowed_fields[$sort_by], $dir);

        switch ($sort_by) {
            case 'country':
            case 'nation':
                $query->addSort($this->sort_allowed_fields['year'],  $desc);
                $query->addSort($this->sort_allowed_fields['title'], $asc);
                break;
            case 'title':
                $query->addSort($this->sort_allowed_fields['year'],  $desc);
                break;
            case 'year':
                $query->addSort($this->sort_allowed_fields['nation'], $asc);
                $query->addSort($this->sort_allowed_fields['title'],  $asc);
                break;
            case 'rank':
            case 'relevance':
                $query->addSort($this->sort_allowed_fields['year'],  $desc);
                $query->addSort($this->sort_allowed_fields['title'], $asc);
                break;
        }
    }

    // -------------------------------------------------------------------------
    // Result formatting
    // -------------------------------------------------------------------------

    private function map_variable_fields(array $docs): array
    {
        return array_map(function($doc) {
            $out = array();
            foreach ($doc as $field => $value) {
                $out[$this->variable_field_map[$field] ?? $field] = $value;
            }
            return $out;
        }, $docs);
    }

    private function fetch_citation_counts(array $docs): array
    {
        $ids = array_filter(array_map('intval', array_column($docs, 'id')));
        if (empty($ids)) return array();
        $this->ci->db->select('sid, count(sid) as total');
        $this->ci->db->where_in('sid', $ids);
        $this->ci->db->group_by('sid');
        $rows = $this->ci->db->get('survey_citations')->result_array();
        $result = array();
        foreach ($rows as $row) {
            $result[$row['sid']] = (int)$row['total'];
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Solr utility
    // -------------------------------------------------------------------------

    function solr_total_count($doctype = 1)
    {
        $query = $this->solr_client->createSelect();
        $query->setQuery('doctype:' . (int)$doctype);
        $query->createFilterQuery('published')->setQuery('published:1');
        if (!empty($this->repo)) {
            $helper = $query->getHelper();
            $query->createFilterQuery('repo')
                  ->setQuery('repositories:' . $helper->escapeTerm((string)$this->repo));
        }
        $query->setStart(0)->setRows(0);
        return $this->solr_client->select($query)->getNumFound();
    }

    private function get_dataset_type_counts_from_solr()
    {
        try {
            $query = $this->solr_client->createSelect();
            $query->setQuery('doctype:1 AND published:1');
            $query->setRows(0);
            $query->getFacetSet()->createFacetField('dataset_types')->setField('dataset_type');
            $facet  = $this->solr_client->select($query)->getFacetSet()->getFacet('dataset_types');
            $counts = array();
            if ($facet) {
                foreach ($facet as $value => $count) {
                    $counts[$value] = $count;
                }
            }
            return $counts ?: array('survey' => $this->solr_total_count(1));
        } catch (Exception $e) {
            log_message('error', 'Solr: failed to get type counts — ' . $e->getMessage());
            return array('survey' => $this->solr_total_count(1));
        }
    }

    private function get_country_id_by_name(array $names)
    {
        $this->ci->db->select('countryid');
        $this->ci->db->where_in('name', $names);
        $rows = $this->ci->db->get('countries')->result_array();
        return array_column($rows, 'countryid');
    }

    // -------------------------------------------------------------------------
    // Input validation / sanitization
    // -------------------------------------------------------------------------

    private function validate_parameter($key, $value)
    {
        switch ($key) {
            case 'from':
            case 'to':
                return max(0, (int)$value);
            case 'study_keywords':
            case 'variable_keywords':
                return $this->sanitize_keywords((string)$value);
            case 'repo':
            case 'created':
                return trim(strip_tags((string)$value));
            case 'varcount':
                return in_array($value, array('0', '>0', 'with_vars', '1')) ? $value : '';
            case 'countries':
            case 'regions':
            case 'topics':
            case 'collections':
            case 'type':
            case 'dtype':
                if (!is_array($value)) return array();
                return array_values(array_filter(array_map(function($item) {
                    return is_numeric($item) ? (int)$item : trim(strip_tags((string)$item));
                }, $value)));
            case 'sort_by':
                return array_key_exists($value, $this->sort_allowed_fields) ? $value : '';
            case 'sort_order':
                return in_array(strtolower($value), $this->sort_allowed_order) ? strtolower($value) : 'asc';
            case 'debug':
                return (bool)$value;
            default:
                return is_string($value) ? trim(strip_tags($value)) : $value;
        }
    }

    private function sanitize_keywords($keywords)
    {
        $keywords = trim(strip_tags($keywords));
        $keywords = $this->sanitize_query($keywords);
        if (strlen($keywords) > 500) {
            $keywords = substr($keywords, 0, 500);
        }
        return $keywords;
    }

    private function sanitize_query($query)
    {
        if (empty($query)) return '';
        $query = preg_replace('/[\x{200B}\x{00AD}\p{C}]+/u', '', $query);
        $query = preg_replace('/\s+/u', ' ', $query);
        return trim($query);
    }

    // -------------------------------------------------------------------------
    // Keyword escaping / query building
    // -------------------------------------------------------------------------

    private function escape_keywords($keywords, $helper)
    {
        if (empty($keywords)) return '';
        $keywords = $this->sanitize_query($keywords);
        if (empty($keywords)) return '';

        // field:value syntax — e.g. title:health nation:Kenya
        if (preg_match('/[a-zA-Z_]+:/', $keywords)) {
            $parsed = $this->parse_field_specific_query($keywords);
            return $this->build_field_specific_solr_query($parsed, $helper);
        }

        // "exact phrase"
        if (preg_match('/^".*"$/', $keywords)) {
            return preg_replace('/([+&\-|(){}[\]^~*?:\/\\\\])/', '\\\\$1', $keywords);
        }

        // +must -exclude
        if (preg_match('/[+\-]/', $keywords)) {
            return $this->process_advanced_query($keywords);
        }

        // plain keywords
        return preg_replace('/([+\-&|(){}[\]^"~*?:\/\\\\])/', '\\\\$1', $keywords);
    }

    private function process_advanced_query($query)
    {
        return implode(' ', array_map(
            array($this, 'process_query_token'),
            $this->tokenize_query($query, true)
        ));
    }

    /**
     * Tokenize on whitespace while respecting quoted phrases.
     * When $allow_single_quotes is true, single quotes are also phrase delimiters.
     */
    private function tokenize_query($query, $allow_single_quotes = false)
    {
        $tokens     = array();
        $current    = '';
        $in_quotes  = false;
        $quote_char = '';

        for ($i = 0; $i < strlen($query); $i++) {
            $c        = $query[$i];
            $is_quote = ($c === '"' || ($allow_single_quotes && $c === "'"));

            if ($is_quote && !$in_quotes) {
                $in_quotes  = true;
                $quote_char = $c;
                $current   .= $c;
            } elseif ($c === $quote_char && $in_quotes) {
                $in_quotes  = false;
                $quote_char = '';
                $current   .= $c;
            } elseif ($c === ' ' && !$in_quotes) {
                if ($current !== '') $tokens[] = $current;
                $current = '';
            } else {
                $current .= $c;
            }
        }

        // Unclosed quote — strip the opening quote character
        if ($in_quotes && $current !== '') {
            $current = substr($current, 1);
        }
        if ($current !== '') $tokens[] = $current;

        return $tokens;
    }

    private function process_query_token($token)
    {
        // +term or -term
        if (preg_match('/^([+\-])(.+)$/', $token, $m)) {
            $term    = trim($m[2], '"\'');
            $escaped = preg_replace('/([+&\-|(){}[\]^~*?:\/\\\\])/', '\\\\$1', $term);
            return $m[1] . $escaped;
        }

        // "quoted phrase"
        if (preg_match('/^["\'].*["\']$/', $token)) {
            return preg_replace('/([+&\-|(){}[\]^~*?:\/\\\\])/', '\\\\$1', $token);
        }

        // plain term
        return preg_replace('/([+\-&|(){}[\]^"~*?:\/\\\\])/', '\\\\$1', $token);
    }

    private function parse_field_specific_query($keywords, $search_type = 'survey')
    {
        $allowed = ($search_type === 'variable')
            ? $this->allowed_variable_search_fields
            : $this->allowed_search_fields;

        $parsed = array('field_queries' => array(), 'general_terms' => array());

        foreach ($this->tokenize_query($keywords) as $token) {
            if (preg_match('/^([a-zA-Z_]+):(.+)$/', $token, $m)) {
                $field = strtolower($m[1]);
                $value = trim($m[2], '"\'');
                if (isset($allowed[$field])) {
                    $parsed['field_queries'][$allowed[$field]][] = $value;
                } else {
                    $parsed['general_terms'][] = $token;
                }
            } else {
                $parsed['general_terms'][] = $token;
            }
        }

        return $parsed;
    }

    private function build_field_specific_solr_query($parsed, $helper)
    {
        $parts = array();

        foreach ($parsed['field_queries'] as $field => $values) {
            foreach ($values as $v) {
                $parts[] = $field . ':' . $helper->escapeTerm($v);
            }
        }

        if (!empty($parsed['general_terms'])) {
            $general = implode(' ', $parsed['general_terms']);
            $trimmed = trim($general);

            if (preg_match('/^".*"$/', $trimmed)) {
                $parts[] = preg_replace('/([+&\-|(){}[\]^~*?:\/\\\\])/', '\\\\$1', $trimmed);
            } elseif (preg_match('/[+\-]/', $trimmed)) {
                $parts[] = $this->process_advanced_query($trimmed);
            } else {
                $parts[] = preg_replace('/([+\-&|(){}[\]^"~*?:\/\\\\])/', '\\\\$1', $general);
            }
        }

        return implode(' AND ', $parts);
    }
}
