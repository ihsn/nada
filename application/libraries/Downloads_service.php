<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 * 
 * Downloads service class
 * 
 *  Provides access to downloadable files (microdata and documentation) for studies.
 * 
 * Features:
 *  - Search/browse all downloadable files across all studies
 *     - Pagination
 *     - Sorting
 *     - Filtering [countries, years, data access type, study IDNO]
 *     - Search [TODO]
 *  - List downloads for a study
 *  - Download files:
 *     - download by resource ID
 *     - download by file-name
 * 
 */
class Downloads_service{

    private $ci;

    function __construct($params=array()){
        $this->ci =& get_instance();
        $this->ci->load->database();
    }

    /**
     * 
     * 
     * Search for downloads across all studies
     * 
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @param string $sort_by
     * @param string $sort_order
     * @return array
     * 
     * Filters:
     *  - type - string: Category (microdata, data, documentation, doc) or specific code (doc/qst, dat/micro, tbl, aud, etc.)
     *  - countries - array of country codes
     *  - years - array of years or year range [start, end]
     *  - data_access_type - string (e.g. 'public', 'licensed', 'data_na', etc.)
     *  - idno - string (study IDNO)
     * 
     * 
     */
    function search($limit=15, $offset=0, $filters=array(), $sort_by='', $sort_order='asc')
    {
        $this->ci->db->select('resources.resource_id, resources.title, resources.dctype, 
                              resources.dcformat, resources.filename, resources.author, 
                              resources.dcdate, resources.description, resources.filesize, resources.changed,
                              surveys.id as survey_id, surveys.idno, surveys.title as study_title, 
                              surveys.nation, surveys.year_start, surveys.year_end,
                              forms.model as data_access_type');
        
        $this->ci->db->from('resources');
        $this->ci->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
        $this->ci->db->join('forms', 'forms.formid = surveys.formid', 'left');
        
        $this->_apply_filters($filters);
        
        $this->_apply_sorting($sort_by, $sort_order);
        
        $this->ci->db->limit($limit, $offset);
        
        $result = $this->ci->db->get()->result_array();
        
        $formatted_results = $this->_format_results($result);
        
        $total = $this->search_count($filters);
        
        return array(
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'found' => count($formatted_results),
            'results' => $formatted_results
        );
    }
    
    
    /**
     * 
     * Get total count of downloadable files matching filters
     * 
     * @param array $filters
     * @return int
     * 
     */
    function search_count($filters=array())
    {
        $this->ci->db->select('COUNT(DISTINCT resources.resource_id) as total');
        $this->ci->db->from('resources');
        $this->ci->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
        $this->ci->db->join('forms', 'forms.formid = surveys.formid', 'left');
        
        $this->_apply_filters($filters);
        
        $result = $this->ci->db->get()->row_array();
        
        return (int)$result['total'];
    }
    
    
    /**
     * 
     * Apply filters to the query
     * 
     * @param array $filters
     * 
     */
    private function _apply_filters($filters)
    {
        if (empty($filters)) {
            return;
        }
        
        if (isset($filters['countries']) && !empty($filters['countries'])) {
            $countries = is_array($filters['countries']) ? $filters['countries'] : array($filters['countries']);
            $where_countries = array();
            foreach ($countries as $country) {
                $where_countries[] = "surveys.nation LIKE " . $this->ci->db->escape('%' . $country . '%');
            }
            if (!empty($where_countries)) {
                $this->ci->db->where('(' . implode(' OR ', $where_countries) . ')', NULL, FALSE);
            }
        }
        
        if (isset($filters['years']) && !empty($filters['years'])) {
            if (is_array($filters['years'])) {
                if (count($filters['years']) == 2) {
                    $year_start = (int)$filters['years'][0];
                    $year_end = (int)$filters['years'][1];
                    $this->ci->db->where("(surveys.year_start >= {$year_start} AND surveys.year_end <= {$year_end})", NULL, FALSE);
                } else {
                    $years_list = array_map('intval', $filters['years']);
                    $years_where = array();
                    foreach ($years_list as $year) {
                        $years_where[] = "(surveys.year_start <= {$year} AND surveys.year_end >= {$year})";
                    }
                    if (!empty($years_where)) {
                        $this->ci->db->where('(' . implode(' OR ', $years_where) . ')', NULL, FALSE);
                    }
                }
            } else {
                $year = (int)$filters['years'];
                $this->ci->db->where("(surveys.year_start <= {$year} AND surveys.year_end >= {$year})", NULL, FALSE);
            }
        }
        
        if (isset($filters['data_access_type']) && !empty($filters['data_access_type'])) {
            $this->ci->db->where('forms.model', $filters['data_access_type']);
        }
        
        if (isset($filters['type']) && !empty($filters['type'])) {
            $type = strtolower($filters['type']);
            
            // Handle category filters
            if ($type === 'microdata' || $type === 'data') {
                // Match all known data type codes
                $data_types = array('dat', 'dat/micro');
                $this->ci->db->where_in('resources.resource_type', $data_types);
            } elseif ($type === 'documentation' || $type === 'doc') {
                // Match all types EXCEPT microdata (includes documents, tables, audio, video, etc.)
                $data_types = array('dat', 'dat/micro');
                $this->ci->db->where_not_in('resources.resource_type', $data_types);
            } else {
                // Direct resource_type code match (e.g., 'doc/qst', 'tbl', 'aud', etc.)
                $this->ci->db->where('resources.resource_type', $type);
            }
        }
        
        if (isset($filters['idno']) && !empty($filters['idno'])) {
            $this->ci->db->where('surveys.idno', $filters['idno']);
        }
    }
    
    
    /**
     * 
     * Apply sorting to the query
     * 
     * @param string $sort_by
     * @param string $sort_order
     * 
     */
    private function _apply_sorting($sort_by, $sort_order)
    {
        $allowed_sort_fields = array(
            'title' => 'resources.title',
            'study_title' => 'surveys.title',
            'nation' => 'surveys.nation',
            'year_start' => 'surveys.year_start',
            'year_end' => 'surveys.year_end',
            'dcdate' => 'resources.dcdate',
            'data_access_type' => 'forms.model'
        );
        
        $sort_order = strtolower($sort_order);
        if (!in_array($sort_order, array('asc', 'desc'))) {
            $sort_order = 'asc';
        }
        
        if (!empty($sort_by) && isset($allowed_sort_fields[$sort_by])) {
            $this->ci->db->order_by($allowed_sort_fields[$sort_by], $sort_order);
        } else {
            $this->ci->db->order_by('surveys.title', 'asc');
            $this->ci->db->order_by('resources.title', 'asc');
        }
    }
    
    
    /**
     * 
     * Format results for API output
     * 
     * @param array $results
     * @param bool $include_study - Whether to include study element (default: true)
     * @param string $link_type - Type of links to include: 'full' or 'minimal' (default: 'full')
     * @return array
     * 
     */
    private function _format_results($results, $include_study=true, $link_type='full')
    {
        $formatted = array();
        
        foreach ($results as $row) {
            $is_url= is_url($row['filename']);
            
            $file_size_formatted = $this->_format_file_size($row['filesize']);
            
            $item = array(
                'resource_id' => $row['resource_id'],
                'title' => $row['title'],
                'dctype' => $row['dctype'],
                'dcformat' => $row['dcformat'],
                'filename' => $row['filename'],
                'external_link' => $is_url,
                'file_size' => $file_size_formatted,
                'file_size_bytes' => $row['filesize'],
                'author' => $row['author'],                
                'dcdate' => $row['dcdate'],
                'description' => $row['description'],
                'changed' => date('c', $row['changed'])
            );

            if ($include_study) {
                $item['study_idno'] = $row['idno'];
                $item['data_access_type'] = $row['data_access_type'];
            }

            $item = array_filter($item, function($value) {
                return $value !== null && $value !== '';
            });
            
            if ($link_type === 'minimal') {
                $download_url = $is_url ? $row['filename'] : site_url('api/downloads/download/' . $row['idno'] . '/' . $row['resource_id']. '?filename=' . $row['filename']);
                $item['links'] = array(
                    'info' => site_url('api/downloads/info/' . $row['idno'] . '/' . $row['resource_id']),
                    'download' => $download_url,
                    'study' => site_url('api/catalog/' . $row['idno'])
                );
            } else {
                $download_url = $is_url ? $row['filename'] : site_url('api/downloads/download/' . $row['idno'] . '/' . $row['resource_id']. '?filename=' . $row['filename']);
                $item['links'] = array(
                    'info' => site_url('api/downloads/info/' . $row['idno'] . '/' . $row['resource_id']),
                    'download' => $download_url,
                    'study' => site_url('api/catalog/' . $row['idno'])
                );
            }
            
            $formatted[] = $item;
        }
        
        return $formatted;
    }


    /**
     * 
     * Format file size to human readable format
     * 
     * @param int $bytes
     * @return string
     * 
     */
    private function _format_file_size($bytes)
    {
        if (empty($bytes) || $bytes <= 0) {
            return '';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }




    /**
     * 
     * List all downloadable files for a specific study
     * 
     * @param int $sid - Survey ID
     * @param string $type - Resource type: Category (microdata, data, documentation, doc) or specific code (doc/qst, dat/micro, tbl, etc.)
     * @return array
     * 
     */
    function list_downloads($sid, $type=null)
    {
        $this->ci->db->select('resources.resource_id, resources.title, 
                              resources.dctype, resources.dcformat, resources.filename, 
                              resources.author, resources.dcdate, resources.description, 
                              resources.filesize, resources.changed,
                              surveys.id as survey_id, surveys.idno, surveys.title as study_title, 
                              surveys.nation, surveys.year_start, surveys.year_end,
                              forms.model as data_access_type');
        
        $this->ci->db->from('resources');
        $this->ci->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
        $this->ci->db->join('forms', 'forms.formid = surveys.formid', 'left');
        
        $this->ci->db->where('surveys.id', $sid);
        
        if ($type) {
            $type = strtolower($type);
            
            // Handle category filters
            if ($type === 'microdata' || $type === 'data') {
                // Match all known data type codes
                $data_types = array('dat', 'dat/micro');
                $this->ci->db->where_in('resources.resource_type', $data_types);
            } elseif ($type === 'documentation' || $type === 'doc') {
                // Match all types EXCEPT microdata (includes documents, tables, audio, video, etc.)
                $data_types = array('dat', 'dat/micro');
                $this->ci->db->where_not_in('resources.resource_type', $data_types);
            } else {
                // Direct resource_type code match (e.g., 'doc/qst', 'tbl', 'aud', etc.)
                $this->ci->db->where('resources.resource_type', $type);
            }
        }
        
        $this->ci->db->order_by('resources.changed', 'desc');
        
        $result = $this->ci->db->get()->result_array();
        
        return $this->_format_results($result, true, 'minimal');
    }

    /**
     * 
     * Get information for a specific downloadable file
     * 
     * @param int $sid - Survey ID
     * @param int $file_id - Resource ID
     * @return array|null
     * 
     */
    function download_file($sid, $file_id)
    {
        $this->ci->db->select('resources.resource_id, resources.title, 
                              resources.dctype, resources.dcformat, resources.filename, 
                              resources.author, resources.dcdate, resources.description, 
                              resources.filesize, resources.changed,
                              surveys.id as survey_id, surveys.idno, surveys.title as study_title, 
                              surveys.nation, surveys.year_start, surveys.year_end,
                              forms.model as data_access_type');
        
        $this->ci->db->from('resources');
        $this->ci->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
        $this->ci->db->join('forms', 'forms.formid = surveys.formid', 'left');
        
        $this->ci->db->where('surveys.id', $sid);
        $this->ci->db->where('resources.resource_id', $file_id);
        
        $result = $this->ci->db->get()->row_array();
        
        if (!$result) {
            return null;
        }
        
        $formatted = $this->_format_results(array($result));
        return $formatted[0];
    }



    /**
     * 
     * 
     * Get information for a specific resource
     * 
     */
    function get_resource_info($sid, $resource_id, $formatted=false)
    {

        $this->ci->db->select('resources.resource_id, resources.title, 
                              resources.dctype, resources.dcformat, resources.filename, 
                              resources.author, resources.dcdate, resources.description, 
                              resources.filesize, resources.changed,
                              surveys.id as survey_id, surveys.idno, surveys.title as study_title, 
                              surveys.nation, surveys.year_start, surveys.year_end,
                              forms.model as data_access_type');
        
        $this->ci->db->from('resources');
        $this->ci->db->join('surveys', 'surveys.id = resources.survey_id', 'inner');
        $this->ci->db->join('forms', 'forms.formid = surveys.formid', 'left');
        
        $this->ci->db->where('surveys.id', $sid);
        $this->ci->db->where('resources.resource_id', $resource_id);
        
        $result = $this->ci->db->get()->row_array();
        
        if (!$result) {
            return null;
        }

        if ($formatted) {
            return $this->_format_results(array($result));
        }

        return $result;
    }


}

/* End of file Downloads_service.php */
/* Location: ./application/libraries/Downloads_service.php */

