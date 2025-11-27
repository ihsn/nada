<?php
class Api_logs_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
    }
	
	function search($limit = NULL, $offset = NULL, $filter = NULL, $sort_by = NULL, $sort_order = NULL)
    {
		$this->db->start_cache();

		$this->db->select('api_logs.*, users.email as user_email');
		$this->db->from('api_logs');
		$this->db->join('users', 'api_logs.user_id = users.id', 'left');
		
		// Fields that support exact match (indexed, small cardinality)
		$exact_match_fields = array('method', 'authorized', 'response_code');
		
		// Fields that support prefix match (can use index with LIKE 'keyword%')
		$prefix_match_fields = array('uri', 'api_key', 'ip_address');
		
		if ($filter)
		{			
			foreach($filter as $f)
			{
				$field = $f['field'];
				
				// Time range filter (very efficient with index)
				if ($field == 'time_range') {
					if (isset($f['time_from']) && $f['time_from'] !== null) {
						$this->db->where('api_logs.time >=', (int)$f['time_from']);
					}
					if (isset($f['time_to']) && $f['time_to'] !== null) {
						$this->db->where('api_logs.time <=', (int)$f['time_to']);
					}
					continue;
				}
				
				$keywords = isset($f['keywords']) ? trim($f['keywords']) : '';
				
				if (empty($keywords)) {
					continue;
				}
				
				// Exact match for specific fields (fast, uses index)
				if (in_array($field, $exact_match_fields)) {
					$this->db->where('api_logs.' . $field, $keywords);
				}
				// Exact match for user_id (already optimized)
				else if ($field == 'user_id') {
					$this->db->where('api_logs.user_id', (int)$keywords);
				}
				// Prefix match for URI, API key, IP (can use index)
				else if (in_array($field, $prefix_match_fields)) {
					// Use prefix match (keyword%) instead of full wildcard (%keyword%)
					// This allows index usage on the leftmost characters
					$this->db->like('api_logs.' . $field, $keywords, 'after');
				}
				// "All fields" search - use prefix match where possible
				else if ($field == 'all') {
					$first_condition = true;
					foreach ($exact_match_fields as $exact_field) {
						if ($first_condition) {
							$this->db->where('api_logs.' . $exact_field, $keywords);
							$first_condition = false;
						} else {
							$this->db->or_where('api_logs.' . $exact_field, $keywords);
						}
					}
					foreach ($prefix_match_fields as $prefix_field) {
						if ($first_condition) {
							$this->db->like('api_logs.' . $prefix_field, $keywords, 'after');
							$first_condition = false;
						} else {
							$this->db->or_like('api_logs.' . $prefix_field, $keywords, 'after');
						}
					}
				}
			}
		}
		
		$this->db->stop_cache();

		if ($sort_by != '' && $sort_order != '')
		{
			// Prefix sort_by with table name if it's an api_logs field
			$api_logs_fields = array('id', 'uri', 'method', 'params', 'user_id', 'api_key', 'ip_address', 'time', 'rtime', 'authorized', 'response_code');
			if (in_array($sort_by, $api_logs_fields)) {
				$this->db->order_by('api_logs.' . $sort_by, $sort_order);
			} else {
				$this->db->order_by($sort_by, $sort_order);
			}
		}
		
	  	$this->db->limit($limit, $offset);
        $query = $this->db->get();
		
		$this->db->flush_cache();
		
		if (!$query)
		{	
			return FALSE;
		}
		$result = $query->result_array();
		return $result;
    }
  	
    function search_count($filter = NULL)
    {
        $this->db->start_cache();
        
        // Fields that support exact match (indexed, small cardinality)
        $exact_match_fields = array('method', 'authorized', 'response_code');
        
        // Fields that support prefix match (can use index with LIKE 'keyword%')
        $prefix_match_fields = array('uri', 'api_key', 'ip_address');
        
        if ($filter)
        {			
            foreach($filter as $f)
            {
                $field = $f['field'];
                
                // Time range filter (very efficient with index)
                if ($field == 'time_range') {
                    if (isset($f['time_from']) && $f['time_from'] !== null) {
                        $this->db->where('api_logs.time >=', (int)$f['time_from']);
                    }
                    if (isset($f['time_to']) && $f['time_to'] !== null) {
                        $this->db->where('api_logs.time <=', (int)$f['time_to']);
                    }
                    continue;
                }
                
                $keywords = isset($f['keywords']) ? trim($f['keywords']) : '';
                
                if (empty($keywords)) {
                    continue;
                }
                
                // Exact match for specific fields (fast, uses index)
                if (in_array($field, $exact_match_fields)) {
                    $this->db->where('api_logs.' . $field, $keywords);
                }
                // Exact match for user_id (already optimized)
                else if ($field == 'user_id') {
                    $this->db->where('api_logs.user_id', (int)$keywords);
                }
                // Prefix match for URI, API key, IP (can use index)
                else if (in_array($field, $prefix_match_fields)) {
                    // Use prefix match (keyword%) instead of full wildcard (%keyword%)
                    $this->db->like('api_logs.' . $field, $keywords, 'after');
                }
                // "All fields" search - use prefix match where possible
                else if ($field == 'all') {
                    $first_condition = true;
                    foreach ($exact_match_fields as $exact_field) {
                        if ($first_condition) {
                            $this->db->where('api_logs.' . $exact_field, $keywords);
                            $first_condition = false;
                        } else {
                            $this->db->or_where('api_logs.' . $exact_field, $keywords);
                        }
                    }
                    foreach ($prefix_match_fields as $prefix_field) {
                        if ($first_condition) {
                            $this->db->like('api_logs.' . $prefix_field, $keywords, 'after');
                            $first_condition = false;
                        } else {
                            $this->db->or_like('api_logs.' . $prefix_field, $keywords, 'after');
                        }
                    }
                }
            }
        }
        
        $this->db->stop_cache();
        $count = $this->db->count_all_results('api_logs');
        $this->db->flush_cache();
        
        return $count;
    }
	
	function select_single($id)
	{
		$this->db->select('api_logs.*, users.email as user_email');
		$this->db->from('api_logs');
		$this->db->join('users', 'api_logs.user_id = users.id', 'left');
		$this->db->where('api_logs.id', $id); 
		return $this->db->get()->row_array();
	}

}

