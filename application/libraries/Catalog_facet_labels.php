<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sidebar facets from nada-ai's facet counts (POST /studies/search `facets`, see nada-ai
 * docs/studies-search-facets.md).
 *
 * nada-ai counts ids; this adds the labels from the reference tables (no joins over surveys) and returns each facet in
 * the shape Search_helper_model / Facet_model give the sidebar, so CatalogFacets.vue reads both alike. A value this
 * catalog no longer knows (deleted country, unpublished repository, ...) is left out.
 */
class Catalog_facet_labels
{
    /** Sidebar filter name (facets_<tab> setting) => nada-ai facet key. */
    const FACET_KEYS = array(
        'country'    => 'countries',
        'region'     => 'regions',
        'collection' => 'collections',
        'dtype'      => 'form_ids',
        'data_class' => 'data_class_ids',
        'tag'        => 'tags',
        'database'   => 'databases',
        'year'       => 'years',
    );

    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /**
     * The nada-ai `facets` request for the sidebar filters that are shown: all of them when $enabled is empty (the
     * sidebar's own rule), user facets by name.
     *
     * @param string[] $enabled     enabled sidebar filters (facets_<tab>)
     * @param string[] $user_facets names of the user-defined facets
     * @param bool     $central     collections are shown in the central catalog only
     * @return array{keys: string[], user: string[]}
     */
    public function request(array $enabled, array $user_facets, $central)
    {
        $keys = array();
        foreach (self::FACET_KEYS as $filter => $key) {
            if ($filter === 'collection' && !$central) {
                continue;
            }
            if (empty($enabled) || in_array($filter, $enabled, true)) {
                $keys[] = $key;
            }
        }
        $user = array();
        foreach ($user_facets as $name) {
            if (empty($enabled) || in_array($name, $enabled, true)) {
                $user[] = $name;
            }
        }
        return array('keys' => $keys, 'user' => $user);
    }

    /**
     * The sidebar facets from nada-ai's counts.
     *
     * @param array $counts      the response's `facets`: {values: {key: {buckets: [{value, count}]}}, user: {...}, years}
     * @param array $user_facets Facet_model rows of the user-defined facets (id, name, title)
     * @return array facets keyed as Catalog_browse_service::load_facets_data() keys them
     */
    public function build(array $counts, array $user_facets)
    {
        $values = isset($counts['values']) && is_array($counts['values']) ? $counts['values'] : array();
        $found = function ($key) use ($values) {
            $out = array();
            foreach ($values[$key]['buckets'] ?? array() as $bucket) {
                $out[(string) $bucket['value']] = (int) $bucket['count'];
            }
            return $out;
        };

        $facets = array(
            'countries'    => $this->countries($found('countries')),
            'regions'      => $this->regions($found('regions')),
            'repositories' => $this->repositories($found('collections')),
            'da_types'     => $this->data_access_types($found('form_ids')),
            'data_class'   => $this->data_classifications($found('data_class_ids')),
            'tags'         => $this->tags($found('tags')),
            'databases'    => $this->databases($found('databases')),
        );

        $years = $counts['years'] ?? null;
        $facets['years'] = array(
            'min_year' => is_array($years) && $years['min'] !== null ? (int) $years['min'] : 0,
            'max_year' => is_array($years) && $years['max'] !== null ? (int) $years['max'] : 0,
        );

        $user_counts = isset($counts['user']) && is_array($counts['user']) ? $counts['user'] : array();
        foreach ($user_facets as $facet) {
            $buckets = array();
            foreach ($user_counts[$facet['name']]['buckets'] ?? array() as $bucket) {
                $buckets[(string) $bucket['value']] = (int) $bucket['count'];
            }
            $facets[$facet['name']] = array(
                'type'   => 'user',
                'title'  => $facet['title'],
                'values' => $this->facet_terms((int) $facet['id'], $buckets),
            );
        }

        return $facets;
    }

    /** @param array<string, int> $found id => count */
    private function countries(array $found)
    {
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('countryid as id, name as title, iso as iso3');
        $this->ci->db->where_in('countryid', array_map('intval', array_keys($found)));
        $this->ci->db->order_by('name', 'ASC');
        $out = array();
        foreach ($this->ci->db->get('countries')->result_array() as $row) {
            $row['found'] = $found[(string) $row['id']];
            $out[$row['id']] = $row;
        }
        return $out;
    }

    private function regions(array $found)
    {
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('parent_regions.title as group_name, regions.id, regions.title, regions.weight');
        $this->ci->db->join('regions as parent_regions', 'parent_regions.id = regions.pid', 'inner');
        $this->ci->db->where_in('regions.id', array_map('intval', array_keys($found)));
        $this->ci->db->order_by('parent_regions.id, regions.weight');
        $out = array();
        foreach ($this->ci->db->get('regions')->result_array() as $row) {
            $row['found'] = $found[(string) $row['id']];
            $out[$row['id']] = $row;
        }
        return $out;
    }

    private function repositories(array $found)
    {
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('r.repositoryid as id, r.pid, r.title, r.repositoryid, rsections.title as group_name');
        $this->ci->db->join('repository_sections rsections', 'r.section = rsections.id', 'left');
        $this->ci->db->where('r.ispublished', 1);
        $this->ci->db->where_in('r.repositoryid', array_map('strval', array_keys($found)));
        $this->ci->db->order_by('r.weight');
        $out = array();
        foreach ($this->ci->db->get('repositories r')->result_array() as $row) {
            $row['found'] = $found[(string) $row['repositoryid']];
            $out[$row['id']] = $row;
        }
        return $out;
    }

    private function data_access_types(array $found)
    {
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('formid as id, model as code');
        $this->ci->db->where_in('formid', array_map('intval', array_keys($found)));
        $out = array();
        foreach ($this->ci->db->get('forms')->result_array() as $row) {
            $row['title'] = t('legend_data_' . $row['code']);
            $row['found'] = $found[(string) $row['id']];
            $out[(string) $row['id']] = $row;
        }
        return $out;
    }

    /** false when data classifications are turned off, as Search_helper_model::get_active_data_classifications(). */
    private function data_classifications(array $found)
    {
        $this->ci->load->model('Configurations_model');
        if (!$this->ci->Configurations_model->is_data_classifications_enabled()) {
            return false;
        }
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('id, code');
        $this->ci->db->where_in('id', array_map('intval', array_keys($found)));
        $out = array();
        foreach ($this->ci->db->get('data_classifications')->result_array() as $row) {
            $row['title'] = t('data_class_' . $row['code']);
            $row['found'] = $found[(string) $row['id']];
            $out[(string) $row['id']] = $row;
        }
        return $out;
    }

    /** Tags are their own labels; ordered as Search_helper_model::get_active_tags() orders them. */
    private function tags(array $found)
    {
        $tags = array_keys($found);
        rsort($tags, SORT_STRING);
        $out = array();
        foreach ($tags as $tag) {
            $out[$tag] = array('id' => $tag, 'title' => $tag, 'found' => $found[$tag]);
        }
        return $out;
    }

    private function databases(array $found)
    {
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('id, idno, title');
        $this->ci->db->where('type', 'timeseriesdb');
        $this->ci->db->where('published', 1);
        $this->ci->db->where_in('idno', array_map('strval', array_keys($found)));
        $this->ci->db->order_by('title', 'ASC');
        $out = array();
        foreach ($this->ci->db->get('surveys')->result_array() as $row) {
            $out[$row['idno']] = array(
                'id'    => (int) $row['id'],
                'idno'  => $row['idno'],
                'title' => $row['title'],
                'found' => $found[(string) $row['idno']],
            );
        }
        return $out;
    }

    private function facet_terms($facet_id, array $found)
    {
        if (empty($found)) {
            return array();
        }
        $this->ci->db->select('id, value as title');
        $this->ci->db->where('facet_id', $facet_id);
        $this->ci->db->where_in('id', array_map('intval', array_keys($found)));
        $this->ci->db->order_by('value', 'ASC');
        $out = array();
        foreach ($this->ci->db->get('facet_terms')->result_array() as $row) {
            $row['found'] = $found[(string) $row['id']];
            $out[$row['id']] = $row;
        }
        return $out;
    }
}
