<?php
class Facet_model extends CI_Model {
 

	private $facets=[];
	private $facets_list=[];

    public function __construct()
    {
        parent::__construct();
		$this->config->load('facets');
		$this->load->model("Dataset_model");
		$this->load->helper("array");
 		$this->facets=$this->config->item("user_facets");
		//$this->output->enable_profiler(FALSE);
	}


	function get_facet_options()
	{
		return $this->facets;
	}

	function get_core_facets_list()
	{
		return ['tag','topic','country','year','collection','data_class','dtype','type','region'];
	}
	
	
	/**
	* 
	* Get a single value
	* 
	**/
	function select_single($id)
	{		
		$this->db->select("*");
		$this->db->where('id', (integer)$id); 
		return $this->db->get('facets')->row_array();
	}


	function get_facet_by_name($facet_name)
	{		
		$this->db->select("*");
		$this->db->where('name', $facet_name);
		$result=$this->db->get('facets')->row_array();
		return $result;
	}


	function get_facet_id($facet_name)
	{		
		$this->db->select("*");
		$this->db->where('name', $facet_name);
		$result=$this->db->get('facets')->row_array();

		if($result){
			return $result['id'];
		}

		return false;
	}


	/**
	 * 
	 * 
	 * Return a list of all facets
	 * 
	 */
	function select_all($type=null,$enabled=null)
	{		
		$this->db->select("*");
		
		if($type){
			$this->db->where("facet_type",$type);
		}

		if($enabled){
			$this->db->where("enabled",$enabled);
		}

		$result=$this->db->get('facets')->result_array();

		$output=array();
		foreach($result as $row)
		{
			$output[$row['name']]=$row;
		}

		return $output;
	}

	/**
	 * 
	 * 
	 * Get terms count by facet
	 * 
	 * 
	 */
	function select_terms_counts()
	{		
		$this->db->select("facets.id,facets.name,count(facet_terms.facet_id) as total");

		$this->db->join('facet_terms', 'facet_terms.facet_id = facets.id','left');
		$this->db->group_by('facets.id,facets.name');

		return $this->db->get('facets')->result_array();
	}


	function select_terms_counts_detailed()
	{		
		$this->db->select("facets.id,facets.name,facets.title,facets.facet_type,facets.enabled,count(facet_terms.facet_id) as total");

		$this->db->join('facet_terms', 'facet_terms.facet_id = facets.id','left');
		$this->db->group_by('facets.id,facets.name,facets.title,facets.facet_type,facets.enabled');

		return $this->db->get('facets')->result_array();
	}


	/**
	 * 
	 * 
	 * Get a count of values by facet
	 * 
	 */
	function select_term_value_counts($facet_type=null)
	{		
		$this->db->select("facets.id,facets.name,count(survey_facets.facet_id) as total");

		$this->db->join('survey_facets', 'survey_facets.facet_id = facets.id','left');
		$this->db->group_by('facets.id,facets.name');

		if ($facet_type){
			$this->db->where("facet_type",$facet_type);
		}

		return $this->db->get('facets')->result_array();
	}


	function get_facet_terms($facet_id)
	{		
		$this->db->select("*");
		$this->db->where('facet_id',$facet_id);
		return $this->db->get('facet_terms')->result_array();
	}
	

	/**
	 * 
	 * 
	 * Get facets value by facet
	 * 
	 */
	function get_facet_values($facet_id,$published=null,$sort_by='value', $sort_order='ASC', $study_type=null, $filter_values=array())
	{
		/*
		select facet_terms.*, survey_facets.* from facet_terms
			inner join survey_facets on facet_terms.id=survey_facets.term_id
    		inner join  surveys on survey_facets.sid=surveys.id
				where facet_terms.facet_id=1 
				and surveys.published=1;
		*/


		$this->db->select('facet_terms.id,facet_terms.value as title, count(survey_facets.id) as found');
		$this->db->order_by($sort_by, $sort_order);		
		$this->db->join('survey_facets', 'facet_terms.id = survey_facets.term_id');
		$this->db->join('surveys', 'survey_facets.sid = surveys.id');
		$this->db->where('facet_terms.facet_id',$facet_id);
		$this->db->where('survey_facets.facet_id',$facet_id);

		if($study_type){
			$this->db->where('surveys.type',$study_type);
			if(!empty($filter_values)){
				$this->db->or_where_in('facet_terms.id',$filter_values);
			}			
		}

		if (!empty($published)){
			$this->db->where('surveys.published', $published);
		}

		$this->db->group_by('facet_terms.id,facet_terms.value');

		//max limit for facet values
		$this->db->limit(300);
		
		$query=$this->db->get('facet_terms');

		//echo $this->db->last_query();

		if (!$query){
			return FALSE;
		}
		
		$result= $query->result_array();

		$output=array();
		foreach($result as $row)
		{
			$output[$row['id']]=$row;
		}
		return $output;
	}



	
	

	/**
	 * 
	 * 
	 * Create facets
	 * 
	 * 
	 */
	function create_facet($options)
	{
		//allowed fields
		$valid_fields=array(
			'name',
			'title',
			'facet_type',
			'enabled',
			'mappings'
			);

		$data=array();
				
		foreach($options as $key=>$value){
			if (in_array($key,$valid_fields) ){
				$data[$key]=$value;
			}
		}

		if(empty($data['name']) || empty($data['title'])){
			throw new Exception("Missing required fields: name, title");
		}
		
		if (isset($options['mappings'])){
			$data['mappings']=json_encode($options['mappings']);
		}

		$facet=$this->get_facet_by_name($data['name']);

		if($facet){		
			$this->db->where("name",$data['name']);
			return $this->db->update('facets', $data);
		}
		else{
			return $this->db->insert('facets', $data);
		}
	}
	
	
	
	function delete_facet($id)
	{
		$this->db->where('id', $id); 
		$this->db->delete('facets');

		$this->clear_facet_values($id);
		$this->clear_facet_terms($id);
	}

	function clear_facet_terms($facet_id)
	{
		$this->db->where('facet_id', $facet_id);
		return $this->db->delete('facet_terms');
	}

	function clear_facet_values($facet_id)
	{
		$this->db->where('facet_id', $facet_id);
		return $this->db->delete('survey_facets');
	}

	function clear_study_facet_values($sid)
	{
		$this->db->where('sid', $sid);
		return $this->db->delete('survey_facets');
	}


	/**
	 * 
	 * Create or update facet term
	 * 
	 */
	function upsert_facet_term($facet_id,$value)
	{
		$options=array(
			'facet_id'=>$facet_id,
			'value'=>substr($value,0,299)
			);

		$term=$this->facet_term_exists($facet_id, $value);
		
		if (!$term){
			$this->db->insert('facet_terms', $options);
			return $this->db->insert_id();
		}

		return $term['id'];
	}

	/**
	 * 
	 * insert values for facets
	 * 
	 */
	function insert_facet_value($sid,$facet_id,$term_id)
	{
		$options=array(
			'facet_id'=>$facet_id,
			'sid'=>$sid,
			'term_id'=>$term_id			
			);

		return $this->db->insert('survey_facets', $options);
	}


	function get_valid_fields($fields,$options)
	{
		$data=array();
		foreach($options as $key=>$value){
			if (in_array($key,$fields) ){
				$data[$key]=$value;
			}
		}	

		return $data;
	}


	/**
	 * 
	 * Check if a term value exists
	 * 
	 */
	function facet_term_exists($facet_id,$term_value)
	{		
		$this->db->select("*");
		$this->db->where('facet_id', $facet_id);
		$this->db->where('value', $term_value);
		$result=$this->db->get('facet_terms')->result_array();

		if($result && isset($result[0])){
			return $result[0];
		}

		return false;
	}
	

	function facets_list()
	{
		if (empty($this->facets_list)){
			$this->facets_list=$this->select_all($facet_type='user',$enabled=1);
		}

		return $this->facets_list;		
	}
		


	function extract_facet_values($type,$metadata)
	{
		$output=array();
		$facets=$this->facets_list();

		//iterate over all facets
		foreach($facets as $facet_key=>$facet){
			if(isset($facet['enabled']) && $facet['enabled']==true){

				if($facet_key==''){
					continue;
				}

				if (!isset($facet["mappings"])){
					continue;
				}

				$mappings=json_decode($facet['mappings'],true);

				//field xpath				
				$field_path=isset($mappings[$type]['field']) ? str_replace("/",".",$mappings[$type]['field']) : null;
				
				//field column if an array field
				$field_column=isset($mappings[$type]['subfield']) ? $mappings[$type]['subfield'] : null;

				//filter field values
				$filter_field=isset($mappings[$type]['filter']) ? $mappings[$type]['filter'] : null;
				$filter_value=isset($mappings[$type]['filter_value']) ? $mappings[$type]['filter_value'] : null;

				if (!$field_path){
					continue;
				}

				//get data for the facet
				$facet_data=array_data_get($metadata, $field_path);
			
				if(is_array_of_array($facet_data)){
					$facet_data=$this->array_of_array_flatten($facet_data);
				}

				//extract data
				if (!empty($facet_data) && $field_column!=null){							
					//filter data
					if(!empty($filter_field) && !empty($filter_value)){
						$filtered_data=array();
						foreach($facet_data as $row){
							if (isset($row[$filter_field]) && trim(strtolower($row[$filter_field]))==trim(strtolower($filter_value)) ){
								$filtered_data[]=$row[$field_column];
							}
						}
						$output[$facet_key]=(array)$filtered_data;
					}
					else{
						$column_data=array_column($facet_data, $field_column);
						$output[$facet_key]=(array)$column_data;
					}
				}else{
					$output[$facet_key]=(array)$facet_data;
				}
			}
		}

		
		return $output;
	}




	/**
	 *
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function reindex($start_row=NULL, $limit=100, $loop=TRUE)
	{
		//echo "starting at: ".$start_row."\r\n";
		set_time_limit(0);

		$this->db->select("id");
    	$this->db->limit($limit);
		$this->db->order_by('id ASC');

		if ($start_row){
			$this->db->where("id >",$start_row,false);
		}

	  	$rows=$this->db->get("surveys")->result_array();

		//echo "\r\n".count($rows). "rows found\r\n";

		if (!$rows){
			return false;
		}

		$last_row_id=NULL;

		//row id
		$last_row_id=$rows[ count($rows)-1]['id'];

		//reindex
		foreach($rows as $row){
			$this->index_facets($row['id']);
		}

		if($loop ==true){
			$this->reindex($last_row_id,$limit,$loop);
		}

		return array(
			'rows_processed'=>count($rows),
			'last_row_id'=>$last_row_id
		);
	}

	function clear_index()
	{
		$this->db->where("id>",0);
		$this->db->delete("facet_terms");

		$this->db->where("id>",0);
		$this->db->delete("survey_facets");
	}

	function index_facets($sid)
    {
        $study=$this->Dataset_model->get_row_detailed($sid); 
        
        //extract facets
        $facet_data=$this->extract_facet_values($type=$study['type'],$study['metadata']);

        //remove all existing facet terms for study
        $this->clear_study_facet_values($sid);

        //upsert facets
        foreach($facet_data as $facet_key=>$facet_values)
        {
            $facet_id=$this->get_facet_id($facet_key);

            if(empty($facet_id)){
                //create facet
                $facet_id=$this->create_facet(array('name'=>$facet_key, 'title'=>$facet_key));
            }

            foreach($facet_values as $facet_value){
                
                if(empty($facet_value)){
                    continue;
                }

				//nested structures - e.g. study_desc/title_statement
                if(is_array($facet_value)){

                    throw new Exception("Facet value cannot be a nested array. " . $facet_key . json_encode($facet_data));
					//continue;
                }

                //create a term for facet if not already exists
                $term_id=$this->upsert_facet_term($facet_id,$facet_value);

                //upsert facet value
                $this->insert_facet_value($sid,$facet_id,$term_id);
            }
        }

		$this->events->emit('db.after.update', 'surveys', $sid,'facet');
    }


	/**
	 * 
	 * 
	 * Return an array of all filters + terms for a study
	 * 
	 */
	function facet_terms_by_study($sid)
	{
		$this->db->select("facets.name,survey_facets.term_id");
		$this->db->join('facets', 'survey_facets.facet_id = facets.id','inner');
		$this->db->where('sid',$sid);
		$result=$this->db->get('survey_facets')->result_array();
		
		$output=array();
		foreach($result as $row){
			$output[$row['name']][]=$row['term_id'];
		}

		return $output;
	}

	function array_of_array_flatten($arr)
	{
		$output=array();
		if(is_array_of_array($arr)){
			foreach($arr as $values){
				if (!is_array($values)){
					$output[]=$values;
				}else{
					foreach($values as $nested_row){
						$output[]=$nested_row;
					}
				}
			}
		}else{
			return $arr;
		}
		return $output;
	}

  
}
