<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Canonical catalog search document builder.
 *
 * Produces study/citation payloads (metadata JSON, core_fields, filters) for
 * external indexers. Solr/OpenSearch adapters can consume this shape later.
 */
class Catalog_search_metadata_extract
{
    const STUDY_DOCTYPE    = 1;
    const CITATION_DOCTYPE = 3;

    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->config->load('search_metadata_extract');
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Facet_model');
        $this->ci->load->model('Catalog_tags_model');
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

        $survey_ids = array_map('intval', array_column($rows, 'survey_uid'));
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
     * @param int $citation_id citations.id
     * @return array|null
     */
    public function build_citation_document(int $citation_id)
    {
        $this->ci->db->select('
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
        ', false);
        $this->ci->db->where('id', $citation_id);
        $row = $this->ci->db->get('citations')->row_array();

        if (empty($row)) {
            return null;
        }

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
            surveys.id as survey_uid,
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
        $sid = (int) $row['survey_uid'];

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
        );

        $user_facets = $relations['user_facets'][$sid] ?? array();
        if (empty($user_facets)) {
            $user_facets = $this->ci->Facet_model->facet_terms_by_study($sid);
        }
        foreach ($user_facets as $facet_name => $term_ids) {
            $filters['fq_' . $facet_name] = array_map('intval', (array) $term_ids);
        }

        $core_fields = array(
            'survey_uid'       => $sid,
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
