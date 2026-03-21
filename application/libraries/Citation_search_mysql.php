<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Citation_search_mysql
{
    var $ci;
    var $errors            = array();
    var $search_found_rows = 0;

    function __construct($params = array())
    {
        $this->ci =& get_instance();
        if (count($params) > 0) {
            $this->initialize($params);
        }
    }

    function initialize($params = array())
    {
        foreach ($params as $key => $val) {
            if (isset($this->$key)) {
                $this->$key = $val;
            }
        }
    }

    function search($limit = NULL, $offset = NULL, $filter = NULL, $sort_by = NULL, $sort_order = NULL, $published = NULL, $repositoryid = NULL)
    {
        $select_fields = 'SQL_CALC_FOUND_ROWS
            citations.id,
            citations.uuid,
            citations.title,
            citations.subtitle,
            citations.alt_title,
            citations.authors,
            citations.editors,
            citations.translators,
            citations.changed,
            citations.created,
            citations.published,
            citations.volume,
            citations.issue,
            citations.idnumber,
            citations.edition,
            citations.place_publication,
            citations.place_state,
            citations.publisher,
            citations.publication_medium,
            citations.url,
            citations.page_from,
            citations.page_to,
            citations.data_accessed,
            citations.organization,
            citations.ctype,
            citations.pub_day,
            citations.pub_month,
            citations.pub_year,
            citations.abstract,
            citations.keywords,
            citations.notes,
            citations.doi,
            citations.flag,
            citations.url_status,
            citations.owner,
            user_changed.username as changed_by_user,
            user_created.username as created_by_user';

        $db_fields = array(
            'id'              => 'citations.id',
            'title'           => 'citations.title',
            'subtitle'        => 'citations.subtitle',
            'alt_title'       => 'citations.alt_title',
            'authors'         => 'citations.authors',
            'editors'         => 'citations.editors',
            'translators'     => 'citations.translators',
            'place_publication'=> 'citations.place_publication',
            'publisher'       => 'citations.publisher',
            'url'             => 'citations.url',
            'place_state'     => 'citations.place_state',
            'pub_year'        => 'citations.pub_year',
            'changed'         => 'citations.changed',
            'created'         => 'citations.created',
            'ctype'           => 'citations.ctype',
            'keywords'        => 'citations.keywords',
            'notes'           => 'citations.notes',
            'doi'             => 'citations.doi',
            'flag'            => 'citations.flag',
            'published'       => 'citations.published',
            'owner'           => 'citations.owner',
            'changed_by_user' => 'user_changed.username',
            'created_by_user' => 'user_created.username',
            'url_status'      => 'citations.url_status',
        );

        $fulltext_index = 'citations.title,citations.subtitle,citations.authors,citations.doi,citations.keywords,citations.abstract,citations.notes,citations.organization';

        $this->ci->db->select($select_fields, FALSE);
        $this->ci->db->from('citations');
        $this->ci->db->join('users user_created', 'citations.created_by = user_created.id', 'left');
        $this->ci->db->join('users user_changed', 'citations.changed_by = user_changed.id', 'left');

        if ($repositoryid != NULL && strtolower($repositoryid) !== 'central') {
            $this->ci->db->where(
                'citations.id IN (
                    SELECT citationid FROM survey_citations sc
                    INNER JOIN surveys ON sc.sid = surveys.id
                    INNER JOIN survey_repos sr ON sr.sid = surveys.id
                    WHERE sr.repositoryid = ' . $this->ci->db->escape($repositoryid) . ')',
                NULL, FALSE
            );
        }

        if (is_numeric($published)) {
            $this->ci->db->where('citations.published', $published);
        }

        $sort_on_rank = false;

        if ($filter) {
            foreach ($filter as $search_field => $keywords) {
                switch ($search_field) {
                    case 'keywords':
                        if (trim($keywords) === '') { break; }
                        $this->ci->db->where(
                            sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE)', $fulltext_index, $this->ci->db->escape($keywords)),
                            NULL, FALSE
                        );
                        $this->ci->db->select(
                            sprintf('MATCH(%s) AGAINST(%s IN BOOLEAN MODE) as rank_', $fulltext_index, $this->ci->db->escape($keywords)),
                            FALSE
                        );
                        $sort_on_rank = true;
                        break;

                    case 'ctype':
                        if (is_array($keywords) && !empty($keywords)) {
                            $this->ci->db->where_in('citations.ctype', $keywords);
                        }
                        break;

                    case 'from':
                        if (strlen($keywords) === 4 && is_numeric($keywords)) {
                            $this->ci->db->where('citations.pub_year >=', (int)$keywords, FALSE);
                        }
                        break;

                    case 'to':
                        if (strlen($keywords) === 4 && is_numeric($keywords)) {
                            $this->ci->db->where('citations.pub_year <=', (int)$keywords, FALSE);
                        }
                        break;

                    case 'flag':
                        if (!empty($keywords)) {
                            $this->ci->db->where_in('citations.flag', $keywords);
                        }
                        break;

                    case 'user':
                        if (is_array($keywords) && count($keywords) > 0) {
                            $this->ci->db->where_in('changed_by', $keywords);
                        }
                        break;

                    case 'has_notes':
                        if (!empty($keywords)) {
                            $this->ci->db->where("(notes IS NOT NULL AND notes != '')", NULL, FALSE);
                        }
                        break;

                    case 'no_survey_attached':
                        if (!empty($keywords)) {
                            $this->ci->db->where('NOT EXISTS (SELECT 1 FROM survey_citations WHERE survey_citations.citationid = citations.id)', NULL, FALSE);
                        }
                        break;

                    case 'url_status':
                        if (!empty($keywords)) {
                            $this->ci->db->where_in('citations.url_status', $keywords);
                        }
                        break;
                }
            }
        }

        if ($sort_by !== '' && $sort_order !== '') {
            if (array_key_exists($sort_by, $db_fields)) {
                $this->ci->db->order_by($db_fields[$sort_by], $sort_order);
            } elseif ($sort_on_rank) {
                $this->ci->db->order_by('rank_', 'desc');
            }
        }

        $this->ci->db->limit($limit, $offset);
        $query = $this->ci->db->get();

        if (!$query) {
            return FALSE;
        }

        $result = $query->result_array();

        $found_row           = $this->ci->db->query('SELECT FOUND_ROWS() as rowcount', FALSE)->row_array();
        $this->search_found_rows = (int)$found_row['rowcount'];

        if (!empty($result)) {
            $ids         = array_column($result, 'id');
            $author_map  = $this->batch_load_authors($ids);
            $count_map   = $this->batch_load_survey_counts($ids);

            foreach ($result as &$row) {
                $row['authors']      = $author_map[$row['id']]  ?? array();
                $row['survey_count'] = $count_map[$row['id']]   ?? 0;
            }
            unset($row);
        }

        return $result;
    }

    function search_count()
    {
        return $this->search_found_rows;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function batch_load_authors(array $ids): array
    {
        if (empty($ids)) {
            return array();
        }
        $this->ci->db->select('*');
        $this->ci->db->where_in('cid', $ids);
        $this->ci->db->where('author_type', 'author');
        $rows = $this->ci->db->get('citation_authors')->result_array();

        $map = array();
        foreach ($rows as $row) {
            $map[$row['cid']][] = $row;
        }
        return $map;
    }

    private function batch_load_survey_counts(array $ids): array
    {
        if (empty($ids)) {
            return array();
        }
        $this->ci->db->select('citationid, COUNT(sid) as total');
        $this->ci->db->where_in('citationid', $ids);
        $this->ci->db->group_by('citationid');
        $rows = $this->ci->db->get('survey_citations')->result_array();

        $map = array();
        foreach ($rows as $row) {
            $map[(int)$row['citationid']] = (int)$row['total'];
        }
        return $map;
    }
}
