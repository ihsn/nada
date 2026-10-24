<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Canonical catalog search document builder.
 *
 * Produces study/citation payloads (metadata JSON, core_fields, filters) for
 * external indexers (nada-ai).
 */
class Catalog_search_metadata_extract
{
    const STUDY_DOCTYPE    = 1;
    const VARIABLE_DOCTYPE = 2;
    const CITATION_DOCTYPE = 3;

    /** all_text stops growing past this many bytes (one very large study must not bloat a page of records). */
    const ALL_TEXT_MAX_BYTES = 200000;

    /** Metadata keys whose values are not text to search: the metadata about the metadata file, ids and links. */
    const ALL_TEXT_SKIP_KEYS = array('doc_desc', 'idno', 'uri', 'url', 'uuid', 'email', 'schematype');

    /** Core fields searched on their own: all_text leaves out a value equal to one of them. */
    const ALL_TEXT_OWN_FIELDS = array('idno', 'title', 'nation', 'authoring_entity', 'abstract', 'methodology');

    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('search_metadata_extract');
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Facet_model');
        $this->ci->load->model('Catalog_tags_model');
        $this->ci->load->model('Survey_resource_model');
    }

    /**
     * @return string
     */
    public function schema_version()
    {
        return (string) $this->ci->config->item('search_metadata_extract_schema_version') ?: '1.0';
    }

    /**
     * @return array{base_url: string, repository_id: string|null}
     */
    public function site_context()
    {
        return array(
            'base_url'       => rtrim((string) $this->ci->config->item('base_url'), '/'),
            'repository_id'  => $this->ci->config->item('repository_identifier') ?: null,
        );
    }

    /**
     * Build one study export document (published and unpublished).
     *
     * @param int   $survey_id
     * @param array $options include_metadata (bool, default true), include_admin_metadata (bool, default false)
     * @return array|null
     */
    public function build_study_document(int $survey_id, array $options = array())
    {
        $row = $this->load_study_row($survey_id);
        if (empty($row)) {
            return null;
        }

        $relations = $this->load_study_relations(array($survey_id));
        return $this->assemble_study_document($row, $relations, $options);
    }

    /**
     * The export documents of the given studies, in the order given (nada-ai fetches one page of queue rows at once).
     * A study whose document cannot be built is reported in errors instead of failing the others.
     *
     * @param int[] $survey_ids
     * @param array $options include_metadata, include_admin_metadata, include_resources
     * @return array{studies: array, missing: int[], errors: array<int, array{catalog_id: int, message: string}>}
     */
    public function build_study_documents_by_ids(array $survey_ids, array $options = array())
    {
        $survey_ids = array_values(array_unique(array_filter(array_map('intval', $survey_ids), function ($id) {
            return $id > 0;
        })));
        if (empty($survey_ids)) {
            return array('studies' => array(), 'missing' => array(), 'errors' => array());
        }

        $this->ci->db->select($this->study_select_fields(), false);
        $this->ci->db->from('surveys');
        $this->ci->db->join('forms', 'surveys.formid = forms.formid', 'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');
        $this->ci->db->where_in('surveys.id', $survey_ids);
        $rows = array();
        foreach ($this->ci->db->get()->result_array() as $row) {
            $rows[(int) $row['catalog_id']] = $row;
        }

        $relations = $this->load_study_relations(array_keys($rows));

        $studies = array();
        $missing = array();
        $errors  = array();
        foreach ($survey_ids as $id) {
            if (!isset($rows[$id])) {
                $missing[] = $id;
                continue;
            }
            try {
                $studies[] = $this->assemble_study_document($rows[$id], $relations, $options);
            } catch (Exception $e) {
                log_message('error', 'Catalog_search_metadata_extract: study ' . $id . ': ' . $e->getMessage());
                $errors[] = array('catalog_id' => $id, 'message' => $e->getMessage());
            }
        }

        return array('studies' => $studies, 'missing' => $missing, 'errors' => $errors);
    }

    /**
     * @param int   $offset
     * @param int   $limit
     * @param array $options  include_metadata, include_admin_metadata, types (array of survey type strings)
     * @return array{studies: array, offset: int, limit: int, total: int, has_more: bool}
     */
    public function build_study_batch(int $offset, int $limit, array $options = array())
    {
        $types = !empty($options['types']) ? (array) $options['types'] : array();

        if (!empty($types)) {
            $this->ci->db->where_in('type', $types);
        }
        $total = (int) $this->ci->db->count_all_results('surveys');

        $this->ci->db->select($this->study_select_fields(), false);
        $this->ci->db->from('surveys');
        $this->ci->db->join('forms', 'surveys.formid = forms.formid', 'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');
        if (!empty($types)) {
            $this->ci->db->where_in('surveys.type', $types);
        }
        $this->ci->db->order_by('surveys.id', 'ASC');
        $this->ci->db->limit($limit, $offset);
        $rows = $this->ci->db->get()->result_array();

        $survey_ids = array_map('intval', array_column($rows, 'catalog_id'));
        $relations  = $this->load_study_relations($survey_ids);

        $studies = array();
        foreach ($rows as $row) {
            $studies[] = $this->assemble_study_document($row, $relations, $options);
        }

        return array(
            'studies'  => $studies,
            'offset'   => $offset,
            'limit'    => $limit,
            'total'    => $total,
            'has_more' => ($offset + count($studies)) < $total,
        );
    }

    /**
     * Build one variable export document.
     *
     * @param int $uid variables.uid
     * @return array|null
     */
    public function build_variable_document(int $uid)
    {
        $rows = $this->load_variable_rows(null, null, $uid);
        if (empty($rows)) {
            return null;
        }

        return $this->assemble_variable_document($rows[0]);
    }

    /**
     * One page of the variables belonging to one study, for syncing a study's variables whenever the study itself is
     * (re)indexed. Paged so a study with a very large number of variables never has to fit in one response: see
     * build_variable_page() for the paging rules.
     *
     * @param int      $survey_id surveys.id
     * @param int      $limit     page size
     * @param int      $offset    rows to skip; ignored when $after_uid is given
     * @param int|null $after_uid keyset cursor: only variables with a larger uid (use the previous next_after_uid)
     * @return array{variables: array, limit: int, offset: int, has_more: bool, next_after_uid: int|null, total: int|null}
     */
    public function build_variables_by_survey(int $survey_id, int $limit, int $offset = 0, $after_uid = null)
    {
        $page = $this->build_variable_page($limit, $offset, $after_uid, $survey_id);

        // The count is only worth its query once per walk: on the first page.
        $page['total'] = null;
        if ($offset === 0 && $after_uid === null) {
            $this->ci->db->where('sid', $survey_id);
            $page['total'] = (int) $this->ci->db->count_all_results('variables');
        }
        return $page;
    }

    /**
     * One page of every variable in the catalog, for the initial backfill.
     *
     * @param int   $offset
     * @param int   $limit
     * @param array $options  types (array of survey type strings), after_uid (keyset cursor)
     * @return array{variables: array, limit: int, offset: int, has_more: bool, next_after_uid: int|null, total: int|null}
     */
    public function build_variable_batch(int $offset, int $limit, array $options = array())
    {
        $types     = !empty($options['types']) ? (array) $options['types'] : array();
        $after_uid = isset($options['after_uid']) ? (int) $options['after_uid'] : null;

        $page = $this->build_variable_page($limit, $offset, $after_uid, null, $types);

        // COUNT(*) over the whole join is the expensive part of a page at millions of rows, and the total does not
        // change between pages of one walk, so it is computed on the first page only.
        $page['total'] = null;
        if ($offset === 0 && $after_uid === null) {
            $this->ci->db->from('variables');
            $this->ci->db->join('surveys', 'surveys.id = variables.sid', 'inner');
            if (!empty($types)) {
                $this->ci->db->where_in('surveys.type', $types);
            }
            $page['total'] = (int) $this->ci->db->count_all_results();
        }
        return $page;
    }

    /**
     * A page of variable documents, ordered by uid.
     *
     * Two ways to page: keyset ($after_uid = the previous page's next_after_uid; every page costs the same however
     * deep) or plain $offset (the database walks past every skipped row, so deep pages get slower). One row more than
     * $limit is read to know whether another page exists, instead of counting the whole table on every request.
     *
     * @return array{variables: array, limit: int, offset: int, has_more: bool, next_after_uid: int|null}
     */
    private function build_variable_page(int $limit, int $offset, $after_uid, $survey_id = null, array $types = array())
    {
        $rows     = $this->load_variable_rows($limit + 1, $offset, null, $survey_id, $types, $after_uid);
        $has_more = count($rows) > $limit;
        if ($has_more) {
            array_pop($rows);
        }

        $variables = array();
        foreach ($rows as $row) {
            $variables[] = $this->assemble_variable_document($row);
        }

        return array(
            'variables'      => $variables,
            'limit'          => $limit,
            'offset'         => $offset,
            'has_more'       => $has_more,
            'next_after_uid' => ($has_more && !empty($rows)) ? (int) end($rows)['uid'] : null,
        );
    }

    /** Columns of a citation export document (see citation_document_from_row). */
    const CITATION_EXPORT_FIELDS = '
            id,
            uuid,
            title,
            subtitle,
            authors,
            volume,
            issue,
            edition,
            place_publication,
            publisher,
            ctype,
            abstract,
            keywords,
            notes,
            doi,
            published,
            pub_year
        ';

    /**
     * @param int $citation_id citations.id
     * @return array|null
     */
    public function build_citation_document(int $citation_id)
    {
        $this->ci->db->select(self::CITATION_EXPORT_FIELDS, false);
        $this->ci->db->where('id', $citation_id);
        $row = $this->ci->db->get('citations')->row_array();

        return empty($row) ? null : $this->citation_document_from_row($row);
    }

    /**
     * One page of citation documents, keyset-paged on citations.id like the variables export: `after_id` is the
     * previous page's `next_after_id` (or `offset` for a plain offset), and `total` is only counted on the first page.
     *
     * @return array{citations: array, limit: int, offset: int, total: int|null, has_more: bool, next_after_id: int|null}
     */
    public function build_citation_batch(int $limit, int $offset = 0, $after_id = null)
    {
        $this->ci->db->select(self::CITATION_EXPORT_FIELDS, false);
        $this->ci->db->from('citations');
        if ($after_id !== null) {
            $this->ci->db->where('id >', (int) $after_id);
        }
        $this->ci->db->order_by('id', 'ASC');
        $this->ci->db->limit($limit + 1, $after_id !== null ? 0 : $offset);
        $rows = $this->ci->db->get()->result_array();

        $has_more = count($rows) > $limit;
        if ($has_more) {
            array_pop($rows);
        }

        $total = null;
        if ($offset === 0 && $after_id === null) {
            $total = (int) $this->ci->db->count_all('citations');
        }

        return array(
            'citations'     => array_map(array($this, 'citation_document_from_row'), $rows),
            'limit'         => $limit,
            'offset'        => $offset,
            'total'         => $total,
            'has_more'      => $has_more,
            'next_after_id' => ($has_more && !empty($rows)) ? (int) end($rows)['id'] : null,
        );
    }

    /**
     * @param array $row a citations row selected with CITATION_EXPORT_FIELDS
     * @return array
     */
    private function citation_document_from_row(array $row)
    {
        $pub_date = isset($row['pub_year']) ? (int) $row['pub_year'] : null;
        unset($row['pub_year']);

        return array(
            'metadata'      => array(
                'title'             => $row['title'] ?? null,
                'subtitle'          => $row['subtitle'] ?? null,
                'authors'           => $row['authors'] ?? null,
                'abstract'          => $row['abstract'] ?? null,
                'keywords'          => $row['keywords'] ?? null,
                'notes'             => $row['notes'] ?? null,
                'doi'               => $row['doi'] ?? null,
                'volume'            => $row['volume'] ?? null,
                'issue'             => $row['issue'] ?? null,
                'edition'           => $row['edition'] ?? null,
                'place_publication' => $row['place_publication'] ?? null,
                'publisher'         => $row['publisher'] ?? null,
            ),
            'core_fields'   => array(
                'citation_id'   => (int) $row['id'],
                'citation_uuid' => $row['uuid'] ?? null,
                'title'         => $row['title'] ?? null,
                'authors'       => $row['authors'] ?? null,
                'abstract'      => $row['abstract'] ?? null,
                'keywords'      => $row['keywords'] ?? null,
                'notes'         => $row['notes'] ?? null,
                'doi'           => $row['doi'] ?? null,
            ),
            'filters'       => array(
                'doctype'   => self::CITATION_DOCTYPE,
                'published' => isset($row['published']) ? (int) $row['published'] : 0,
                'ctype'     => $row['ctype'] ?? null,
                'pub_date'  => $pub_date,
            ),
        );
    }

    /**
     * @param int $survey_id
     * @return array|null
     */
    private function load_study_row(int $survey_id)
    {
        $this->ci->db->select($this->study_select_fields(), false);
        $this->ci->db->from('surveys');
        $this->ci->db->join('forms', 'surveys.formid = forms.formid', 'left');
        $this->ci->db->join('repositories', 'surveys.repositoryid = repositories.repositoryid', 'left');
        $this->ci->db->where('surveys.id', $survey_id);
        $row = $this->ci->db->get()->row_array();

        return $row ?: null;
    }

    /**
     * @return string
     */
    private function study_select_fields()
    {
        return "
            1 as doctype,
            surveys.id as catalog_id,
            surveys.idno as idno,
            surveys.doi,
            surveys.formid,
            surveys.thumbnail,
            surveys.type as dataset_type,
            surveys.title,
            nation,
            surveys.authoring_entity,
            forms.model as form_model,
            surveys.year_start,
            surveys.year_end,
            surveys.repositoryid as repositoryid,
            repositories.title as repo_title,
            surveys.created,
            surveys.changed,
            surveys.varcount,
            surveys.published,
            surveys.total_views,
            surveys.total_downloads,
            surveys.keywords,
            surveys.abstract,
            surveys.var_keywords,
            surveys.data_class_id,
            surveys.link_da,
            surveys.metadata
        ";
    }

    /**
     * @param int[] $survey_ids
     * @return array
     */
    private function load_study_relations(array $survey_ids)
    {
        $out = array(
            'countries'     => array(),
            'repositories'  => array(),
            'years'         => array(),
            'topics'        => array(),
            'ts_databases'  => array(),
            'user_facets'   => array(),
            'regions_map'   => array(),
        );

        if (empty($survey_ids)) {
            return $out;
        }

        $this->ci->db->select('sid, cid');
        $this->ci->db->where_in('sid', $survey_ids);
        $this->ci->db->where('cid >', 0, false);
        foreach ($this->ci->db->get('survey_countries')->result_array() as $r) {
            $sid = (int) $r['sid'];
            if (!isset($out['countries'][$sid])) {
                $out['countries'][$sid] = array();
            }
            $out['countries'][$sid][] = (int) $r['cid'];
        }

        $this->ci->db->select('sid, repositoryid');
        $this->ci->db->where_in('sid', $survey_ids);
        foreach ($this->ci->db->get('survey_repos')->result_array() as $r) {
            $sid = (int) $r['sid'];
            if (!isset($out['repositories'][$sid])) {
                $out['repositories'][$sid] = array();
            }
            $out['repositories'][$sid][] = $r['repositoryid'];
        }

        $this->ci->db->select('sid, data_coll_year');
        $this->ci->db->where_in('sid', $survey_ids);
        $this->ci->db->where('data_coll_year >', 0);
        foreach ($this->ci->db->get('survey_years')->result_array() as $r) {
            $sid = (int) $r['sid'];
            if (!isset($out['years'][$sid])) {
                $out['years'][$sid] = array();
            }
            $out['years'][$sid][] = (int) $r['data_coll_year'];
        }

        $this->ci->db->select('sid, tid');
        $this->ci->db->where_in('sid', $survey_ids);
        foreach ($this->ci->db->get('survey_topics')->result_array() as $r) {
            $out['topics'][(int) $r['sid']][] = (int) $r['tid'];
        }

        // the timeseries databases a series belongs to (a series row is the study)
        $this->ci->db->select('series_id, db_idno');
        $this->ci->db->where_in('series_id', $survey_ids);
        foreach ($this->ci->db->get('timeseries_db_links')->result_array() as $r) {
            $out['ts_databases'][(int) $r['series_id']][] = (string) $r['db_idno'];
        }

        $out['user_facets'] = $this->ci->Facet_model->facet_terms_by_studies($survey_ids);

        $all_country_ids = array();
        foreach ($out['countries'] as $cids) {
            $all_country_ids = array_merge($all_country_ids, $cids);
        }
        $all_country_ids = array_values(array_unique($all_country_ids));

        if (!empty($all_country_ids)) {
            $this->ci->db->select('country_id, region_id');
            $this->ci->db->where_in('country_id', $all_country_ids);
            foreach ($this->ci->db->get('region_countries')->result_array() as $r) {
                $cid = (int) $r['country_id'];
                if (!isset($out['regions_map'][$cid])) {
                    $out['regions_map'][$cid] = array();
                }
                $out['regions_map'][$cid][] = (int) $r['region_id'];
            }
        }

        return $out;
    }

    /**
     * @param array $row
     * @param array $relations
     * @param array $options
     * @return array
     */
    private function assemble_study_document(array $row, array $relations, array $options = array())
    {
        $sid = (int) $row['catalog_id'];

        $countries = $relations['countries'][$sid] ?? array();
        $secondary_repos = $relations['repositories'][$sid] ?? array();
        $repositories = $this->merge_repository_ids($row['repositoryid'] ?? null, $secondary_repos);
        $years = $relations['years'][$sid] ?? array();
        $regions = $this->derive_regions($countries, $relations['regions_map']);

        $decoded = $this->ci->Dataset_model->decode_metadata($row['metadata'] ?? null);
        $methodology = $this->parse_methodology($decoded);

        $filters = array(
            'doctype'       => self::STUDY_DOCTYPE,
            'published'     => isset($row['published']) ? (int) $row['published'] : 0,
            'dataset_type'  => $row['dataset_type'] ?? null,
            'formid'        => isset($row['formid']) ? (int) $row['formid'] : null,
            'form_model'    => $row['form_model'] ?? null,
            'year_start'    => isset($row['year_start']) ? (int) $row['year_start'] : null,
            'year_end'      => isset($row['year_end']) ? (int) $row['year_end'] : null,
            'years'         => array_values($years),
            'repositoryid'  => $row['repositoryid'] ?? null,
            'repositories'  => $repositories,
            'countries'     => array_values($countries),
            'regions'       => array_values($regions),
            'data_class_id' => isset($row['data_class_id']) ? (int) $row['data_class_id'] : null,
            'tags'          => $this->ci->Catalog_tags_model->survey_tags_list($sid),
            'topics'        => array_values(array_unique($relations['topics'][$sid] ?? array())),
            'ts_databases'  => array_values(array_unique($relations['ts_databases'][$sid] ?? array())),
        );

        $user_facets = $relations['user_facets'][$sid] ?? array();
        if (empty($user_facets)) {
            $user_facets = $this->ci->Facet_model->facet_terms_by_study($sid);
        }
        foreach ($user_facets as $facet_name => $term_ids) {
            $filters['fq_' . $facet_name] = array_map('intval', (array) $term_ids);
        }

        $core_fields = array(
            'catalog_id'       => $sid,
            'idno'             => $row['idno'] ?? null,
            'doi'              => $row['doi'] ?? null,
            'title'            => $row['title'] ?? null,
            'nation'           => $row['nation'] ?? null,
            'authoring_entity' => $row['authoring_entity'] ?? null,
            'abstract'         => $row['abstract'] ?? null,
            'keywords'         => $row['keywords'] ?? null,
            'methodology'      => $methodology !== '' ? $methodology : null,
            'var_keywords'     => isset($row['var_keywords']) && $row['var_keywords'] !== ''
                ? $row['var_keywords']
                : null,
            'formid'           => isset($row['formid']) ? (int) $row['formid'] : null,
            'form_model'       => $row['form_model'] ?? null,
            'dataset_type'     => $row['dataset_type'] ?? null,
            'year_start'       => isset($row['year_start']) ? (int) $row['year_start'] : null,
            'year_end'         => isset($row['year_end']) ? (int) $row['year_end'] : null,
            'repositoryid'     => $row['repositoryid'] ?? null,
            'repo_title'       => $row['repo_title'] ?? null,
            'published'        => isset($row['published']) ? (int) $row['published'] : 0,
            'data_class_id'    => isset($row['data_class_id']) ? (int) $row['data_class_id'] : null,
            'created'          => isset($row['created']) ? (int) $row['created'] : null,
            'changed'          => isset($row['changed']) ? (int) $row['changed'] : null,
            'varcount'         => isset($row['varcount']) ? (int) $row['varcount'] : 0,
            'total_views'      => isset($row['total_views']) ? (int) $row['total_views'] : 0,
            'total_downloads'  => isset($row['total_downloads']) ? (int) $row['total_downloads'] : 0,
            'thumbnail'        => $row['thumbnail'] ?? null,
            'link_da'          => $row['link_da'] ?? null,
        );

        $include_metadata = true;
        if (array_key_exists('include_metadata', $options)) {
            $include_metadata = filter_var($options['include_metadata'], FILTER_VALIDATE_BOOLEAN);
        }

        $include_admin_metadata = false;
        if (array_key_exists('include_admin_metadata', $options)) {
            $include_admin_metadata = filter_var($options['include_admin_metadata'], FILTER_VALIDATE_BOOLEAN);
        }

        $include_resources = false;
        if (array_key_exists('include_resources', $options)) {
            $include_resources = filter_var($options['include_resources'], FILTER_VALIDATE_BOOLEAN);
        }

        // every other text value of the metadata, for search: only with the full record (a partial update -- published,
        // filters -- leaves the one indexed as it is)
        if ($include_metadata) {
            $core_fields['all_text'] = $this->build_all_text($decoded, $core_fields);
        }

        $doc = array(
            'core_fields'   => $core_fields,
            'filters'       => $filters,
        );

        if ($include_metadata) {
            $doc['metadata'] = $this->ci->Dataset_model->get_metadata($sid);
        }

        if ($include_admin_metadata) {
            $doc['admin_metadata'] = $this->load_admin_metadata($sid);
        }

        if ($include_resources) {
            $resources = $this->ci->Survey_resource_model->get_survey_resources($sid);
            $idno = $row['idno'] ?? null;
            if ($idno !== null) {
                foreach ($resources as &$r) {
                    $r['dataset_idno'] = $idno;
                }
                unset($r);
            }
            $doc['resources'] = $this->ci->Survey_resource_model->generate_api_download_link($resources, true);
        }

        return $doc;
    }

    /**
     * @param string|null $primary
     * @param array       $secondary
     * @return string[]
     */
    private function merge_repository_ids($primary, array $secondary)
    {
        $out = array();
        if ($primary !== null && $primary !== '') {
            $out[] = (string) $primary;
        }
        foreach ($secondary as $rid) {
            if ($rid !== null && $rid !== '') {
                $out[] = (string) $rid;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @param int[] $country_ids
     * @param array $regions_by_country
     * @return int[]
     */
    private function derive_regions(array $country_ids, array $regions_by_country)
    {
        $out = array();
        foreach ($country_ids as $cid) {
            if (isset($regions_by_country[$cid])) {
                $out = array_merge($out, $regions_by_country[$cid]);
            }
        }
        return array_values(array_unique(array_map('intval', $out)));
    }

    /**
     * @param int $survey_id
     * @return array|null
     */
    private function load_admin_metadata(int $survey_id)
    {
        if (! $this->ci->db->table_exists('study_admin_metadata')) {
            return null;
        }

        $this->ci->load->model('Study_admin_metadata_model');
        return $this->ci->Study_admin_metadata_model->get_metadata($survey_id);
    }

    /**
     * Load variable rows joined with the owning survey's fields.
     *
     * Pass $uid to load a single variable. Otherwise rows come ordered by uid, narrowed to one study ($survey_id) or
     * to survey types ($types), and cut to a page with $limit plus either $after_uid (keyset: uid greater than it) or
     * $offset. No $limit returns every matching row.
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param int|null $uid
     * @param int|null $survey_id
     * @param string[] $types
     * @param int|null $after_uid
     * @return array
     */
    private function load_variable_rows(
        $limit = null,
        $offset = null,
        $uid = null,
        $survey_id = null,
        array $types = array(),
        $after_uid = null
    ) {
        $this->ci->db->select(
            'variables.uid, variables.sid, variables.name, variables.labl, variables.qstn, variables.catgry,
             surveys.title'
        );
        $this->ci->db->from('variables');
        $this->ci->db->join('surveys', 'surveys.id = variables.sid', 'inner');

        if ($uid !== null) {
            $this->ci->db->where('variables.uid', $uid);
        } else {
            if ($survey_id !== null) {
                $this->ci->db->where('variables.sid', $survey_id);
            } elseif (!empty($types)) {
                $this->ci->db->where_in('surveys.type', $types);
            }
            if ($after_uid !== null) {
                $this->ci->db->where('variables.uid >', (int) $after_uid);
            }
            $this->ci->db->order_by('variables.uid', 'asc');
            if ($limit !== null) {
                // with a keyset cursor the offset is always 0: the cursor already skipped the earlier rows
                $this->ci->db->limit($limit, $after_uid !== null ? 0 : (int) $offset);
            }
        }

        return $this->ci->db->get()->result_array();
    }

    /**
     * One variable as nada-ai indexes it: the variable, and its study's id and title (to sort by). Nothing else about
     * the study: nada-ai reads its published state, type, countries, years, collections and data access from its
     * study index at search time, so a change to the study never has to reach its variables.
     *
     * @param array $row one row from load_variable_rows()
     * @return array
     */
    private function assemble_variable_document(array $row)
    {
        return array(
            'core_fields' => array(
                'uid'        => (int) $row['uid'],
                'catalog_id' => (int) $row['sid'],
                'name'       => $row['name'] ?? null,
                'label'      => $row['labl'] ?? null,
                'question'   => $row['qstn'] ?? null,
                'categories' => $row['catgry'] ?? null,
                'title'      => $row['title'] ?? null,
            ),
        );
    }

    /**
     * Every text value of the study's metadata, one array element per value, in metadata order: what search reads
     * beyond the fields it searches on their own. Leaves out ids, links, values without a letter (numbers, dates,
     * codes), values equal to a core field searched on its own, repeats, and the metadata about the metadata file.
     * HTML is reduced to its text. Stops at ALL_TEXT_MAX_BYTES.
     *
     * @param array|null $metadata
     * @param array      $core_fields
     * @return string[]
     */
    private function build_all_text($metadata, array $core_fields)
    {
        if (empty($metadata) || !is_array($metadata)) {
            return array();
        }
        $seen = array();
        foreach (self::ALL_TEXT_OWN_FIELDS as $field) {
            $text = $this->all_text_value($core_fields[$field] ?? null);
            if ($text !== '') {
                $seen[mb_strtolower($text)] = true;
            }
        }
        $values = array();
        $size = 0;
        $this->collect_all_text($metadata, $values, $seen, $size);
        return $values;
    }

    /**
     * @param mixed    $node
     * @param string[] $values
     * @param array    $seen
     * @param int      $size
     * @return bool false once ALL_TEXT_MAX_BYTES is reached
     */
    private function collect_all_text($node, array &$values, array &$seen, &$size)
    {
        if (is_array($node)) {
            foreach ($node as $key => $child) {
                if (is_string($key) && in_array(strtolower($key), self::ALL_TEXT_SKIP_KEYS, true)) {
                    continue;
                }
                if (!$this->collect_all_text($child, $values, $seen, $size)) {
                    return false;
                }
            }
            return true;
        }

        $text = $this->all_text_value($node);
        if ($text === '' || !preg_match('/\pL/u', $text) || preg_match('~^(https?://|www\.|mailto:)\S*$~i', $text)
            || filter_var($text, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        $key = mb_strtolower($text);
        if (isset($seen[$key])) {
            return true;
        }
        if ($size + strlen($text) > self::ALL_TEXT_MAX_BYTES) {
            return false;
        }
        $seen[$key] = true;
        $values[] = $text;
        $size += strlen($text);
        return true;
    }

    /**
     * A metadata value as plain text: HTML reduced to its text, whitespace collapsed; '' for anything but a string.
     *
     * @param mixed $value
     * @return string
     */
    private function all_text_value($value)
    {
        if (!is_string($value)) {
            return '';
        }
        // only markup is stripped: "age<15 and >49" is text, not a tag
        $text = preg_match('~</?[a-z][^>]*>~i', $value) ? strip_tags($value) : $value;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param array|null $metadata
     * @return string
     */
    private function parse_methodology($metadata)
    {
        if (empty($metadata)) {
            return '';
        }
        $value = $this->ci->Dataset_model->get_array_nested_value(
            (array) $metadata,
            'study_desc.method.method_notes',
            '.'
        );
        return is_string($value) ? trim($value) : '';
    }
}
