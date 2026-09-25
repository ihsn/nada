<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Citation Search — nada-ai driver
 *
 * Used when the site setting citation_search_provider = nada_ai. Same public interface as the other citation search
 * drivers: search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid), and it sets
 * $search_found_rows.
 *
 * The keyword search is done by nada-ai's lexical citation search, POST /citations/search (nada-ai:
 * docs/citations-search-contract.md). The API returns one page of citation ids in ranked order and the number found;
 * the rows of that page are then loaded from this catalog's database, in that order, so the list looks the same
 * whatever engine ranked it. The API result is the source of truth for the count and the order.
 *
 * nada-ai only holds published citations and only searches text, type and year, so a request it cannot answer is served
 * by the database driver instead (the rule is in build_request): no keyword, unpublished citations, a collection scope,
 * the admin filters (flag, user, url status, notes, no survey attached), a sort the index does not have, or a page
 * bigger than the API's. The same happens when nada-ai is unreachable or fails on its side (timeout, HTTP 5xx or 429).
 * A request the API rejects (HTTP 4xx: bad key, invalid filters, an engine without the citation search) is a
 * configuration or contract error and is raised, not hidden.
 */

require_once dirname(__FILE__) . '/Semantic_search_api_exception.php';

class Citation_search_nada_ai
{
    var $ci;
    var $search_found_rows = 0;

    /** Largest page the API serves (its `limit` maximum). */
    const API_MAX_LIMIT = 100;

    /** Maximum CURLOPT_TIMEOUT for the nada-ai request (seconds). */
    const API_MAX_TIMEOUT_SEC = 15;

    /** Filters only the database search has; a request that uses one is served by the database. */
    private static $database_only_filters = array('flag', 'user', 'has_notes', 'no_survey_attached', 'url_status');

    private $api_url;
    private $api_key;
    private $timeout;

    function __construct($params = array())
    {
        $this->ci =& get_instance();
        $this->ci->config->load('semantic_search');

        $this->api_url = rtrim((string) $this->ci->config->item('semantic_search_url'), '/');
        $this->api_key = (string) $this->ci->config->item('semantic_search_api_key');
        $timeout       = (int) $this->ci->config->item('semantic_search_timeout');
        $this->timeout = min(max(1, $timeout > 0 ? $timeout : self::API_MAX_TIMEOUT_SEC), self::API_MAX_TIMEOUT_SEC);

        $this->ci->load->model('Citation_model');
    }

    function search($limit = NULL, $offset = NULL, $filter = NULL, $sort_by = NULL, $sort_order = NULL, $published = NULL, $repositoryid = NULL)
    {
        $request = $this->build_request($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
        if ($request === NULL) {
            return $this->database_search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
        }

        try {
            $response = $this->post_json('/citations/search', $request, array('found', 'hits'));
        } catch (Semantic_search_api_exception $e) {
            if (!$this->is_outage($e)) {
                throw $e;
            }
            return $this->database_search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
        }

        $this->search_found_rows = (int) $response['found'];

        return $this->rows_by_ids(array_map('intval', array_column($response['hits'], 'citation_id')));
    }

    /**
     * The API request for this search, or NULL when nada-ai cannot answer it (see the class comment).
     *
     * @return array|null
     */
    private function build_request($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid)
    {
        $filter = is_array($filter) ? $filter : array();

        $keywords = isset($filter['keywords']) ? trim((string) $filter['keywords']) : '';
        if ($keywords === '') {
            return NULL;
        }
        if (!is_numeric($published) || (int) $published !== 1) {
            return NULL;
        }
        if ($repositoryid !== NULL && $repositoryid !== '' && strtolower($repositoryid) !== 'central') {
            return NULL;
        }
        foreach (self::$database_only_filters as $name) {
            if (!empty($filter[$name])) {
                return NULL;
            }
        }

        $limit = max(1, (int) ($limit ?? 15));
        if ($limit > self::API_MAX_LIMIT) {
            return NULL;
        }

        $sort = strtolower(trim((string) $sort_by));
        if ($sort === '' || $sort === 'rank' || $sort === 'relevance') {
            $api_sort = 'relevance';
        } elseif ($sort === 'title') {
            $api_sort = 'title';
        } elseif ($sort === 'pub_year') {
            $api_sort = 'year';
        } else {
            return NULL;
        }

        $filters = array();
        $ctypes = array_values(array_filter(array_map('strval', (array) ($filter['ctype'] ?? array())), 'strlen'));
        if (!empty($ctypes)) {
            $filters['ctypes'] = $ctypes;
        }
        // the database search only applies a 4-digit year
        foreach (array('from' => 'year_from', 'to' => 'year_to') as $key => $api_key) {
            $year = isset($filter[$key]) ? (string) $filter[$key] : '';
            if (strlen($year) === 4 && is_numeric($year)) {
                $filters[$api_key] = (int) $year;
            }
        }

        $request = array(
            'query'  => $keywords,
            'sort'   => $api_sort,
            'order'  => strtolower((string) $sort_order) === 'desc' ? 'desc' : 'asc',
            'limit'  => $limit,
            'offset' => max(0, (int) ($offset ?? 0)),
        );
        if (!empty($filters)) {
            $request['filters'] = $filters;
        }

        return $request;
    }

    /** The rows of the given citations from this database, in the order given (the row shape of the mysql driver). */
    private function rows_by_ids(array $ids)
    {
        if (empty($ids)) {
            return array();
        }

        $this->ci->db->select('
            citations.id, citations.uuid, citations.title, citations.subtitle, citations.alt_title, citations.authors,
            citations.editors, citations.translators, citations.changed, citations.created, citations.published,
            citations.volume, citations.issue, citations.idnumber, citations.edition, citations.place_publication,
            citations.place_state, citations.publisher, citations.publication_medium, citations.url, citations.page_from,
            citations.page_to, citations.data_accessed, citations.organization, citations.ctype, citations.pub_day,
            citations.pub_month, citations.pub_year, citations.abstract, citations.keywords, citations.notes,
            citations.doi, citations.flag, citations.url_status, citations.owner,
            user_changed.username as changed_by_user, user_created.username as created_by_user', FALSE);
        $this->ci->db->from('citations');
        $this->ci->db->join('users user_created', 'citations.created_by = user_created.id', 'left');
        $this->ci->db->join('users user_changed', 'citations.changed_by = user_changed.id', 'left');
        $this->ci->db->where_in('citations.id', $ids);
        $rows = array();
        foreach ($this->ci->db->get()->result_array() as $row) {
            $rows[(int) $row['id']] = $row;
        }

        $author_map = $this->batch_load_authors($ids);
        $count_map  = $this->batch_load_survey_counts($ids);

        $ordered = array();
        foreach ($ids as $id) {
            if (!isset($rows[$id])) {
                // in the index but not in this database: the index is out of sync, and the page is one row short
                log_message('error', 'Citation_search_nada_ai: citation ' . $id . ' is in the index but not in the database');
                continue;
            }
            $row = $rows[$id];
            $row['authors']      = isset($author_map[$id]) ? $author_map[$id] : array();
            $row['survey_count'] = isset($count_map[$id]) ? $count_map[$id] : 0;
            $ordered[] = $row;
        }

        return $ordered;
    }

    private function batch_load_authors(array $ids)
    {
        $this->ci->db->select('*');
        $this->ci->db->where_in('cid', $ids);
        $this->ci->db->where('author_type', 'author');

        $map = array();
        foreach ($this->ci->db->get('citation_authors')->result_array() as $row) {
            $map[(int) $row['cid']][] = $row;
        }
        return $map;
    }

    private function batch_load_survey_counts(array $ids)
    {
        $this->ci->db->select('citationid, COUNT(sid) as total');
        $this->ci->db->where_in('citationid', $ids);
        $this->ci->db->group_by('citationid');

        $map = array();
        foreach ($this->ci->db->get('survey_citations')->result_array() as $row) {
            $map[(int) $row['citationid']] = (int) $row['total'];
        }
        return $map;
    }

    private function database_search($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid)
    {
        $rows = $this->ci->Citation_model->search_database($limit, $offset, $filter, $sort_by, $sort_order, $published, $repositoryid);
        $this->search_found_rows = $this->ci->Citation_model->search_found_rows;
        return $rows;
    }

    /**
     * @return array the decoded response
     * @throws Semantic_search_api_exception
     */
    private function post_json($path, array $body, array $required_keys)
    {
        $url     = $this->api_url . $path;
        $payload = json_encode($body);
        $headers = array('Content-Type: application/json', 'Accept: application/json');
        if ($this->api_key !== '') {
            $headers[] = 'X-NADA-Admin-Key: ' . $this->api_key;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
        ));

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', "Citation_search_nada_ai::post_json curl error: {$err}");
            throw new Semantic_search_api_exception("Semantic search API request failed: {$err}", $url, 0, $body, '');
        }

        if ($status < 200 || $status >= 300) {
            log_message('error', "Citation_search_nada_ai::post_json HTTP {$status} request: {$payload} response: {$raw}");
            $error   = json_decode((string) $raw, true)['error'] ?? null;
            $message = (is_array($error) && isset($error['code'], $error['message']))
                ? sprintf('Semantic search API returned HTTP %d (%s): %s', $status, $error['code'], $error['message'])
                : "Semantic search API returned HTTP {$status}";
            throw new Semantic_search_api_exception($message, $url, $status, $body, (string) $raw);
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded) || count(array_diff($required_keys, array_keys($decoded))) > 0) {
            log_message('error', "Citation_search_nada_ai::post_json unexpected response: {$raw}");
            throw new Semantic_search_api_exception('Semantic search API returned an unexpected response', $url, $status, $body, (string) $raw);
        }

        return $decoded;
    }

    /** nada-ai unreachable, timed out, or failed on its side (5xx, rate limited). */
    private function is_outage(Semantic_search_api_exception $e)
    {
        return $e->http_status === 0 || $e->http_status === 429 || $e->http_status >= 500;
    }
}
