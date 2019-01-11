<?php
class Survey_model extends CI_Model {
 
	//fields for the study description
	private $survey_fields=array(
					'id',
					'repositoryid',
					'idno',
					'title',
					'authoring_entity',
					'nation',
					'dirpath',
					'metafile',
					//'link_technical', 
					//'link_study',
					//'link_report',
					//'link_indicator',
					//'link_questionnaire',
					'year_start',
					'year_end',
					'link_da',
					'published',
					'created',
					'changed',
					'varcount',
					'total_views',
					'total_downloads',
					'created',
					'changed',
					'created_by',
					'changed_by',
					'formid',
                    'metadata'
					);

	private $encoded_fields=array(
		"metadata"		
	);
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
		//$this->load->model("Survey_type_model");
	}
	


	//return study IDNO
	function get_study_idno($sid)
	{
		$this->db->select("surveyid");
		$this->db->where("id",$sid);
        $survey=$this->db->get("surveys")->row_array();
        return $survey;
	}

	
	//get the survey by id
    function get_row($sid)
    {
		$this->db->select("id,type,idno,title,year_start as year,nation,published,created, changed, varcount, total_views, total_downloads, surveys.formid,forms.model as data_access_type");		
		$this->db->join('forms','surveys.formid=forms.formid','left');
        $this->db->where("id",$sid);
		$survey=$this->db->get("surveys")->row_array();
		$survey=$this->decode_encoded_fields($survey);		
        return $survey;
	}

	//return survey with metadata and other fields
	function get_row_detailed($sid)
	{
		$this->db->select("surveys.*, forms.model as data_access_type");		
		$this->db->join('forms','surveys.formid=forms.formid','left');
        $this->db->where("id",$sid);
        $survey=$this->db->get("surveys")->row_array();		
		$survey=$this->decode_encoded_fields($survey);		
		return $survey;
	}

	//decode all encoded fields
	function decode_encoded_fields($data)
	{
		foreach($data as $key=>$value){
			if(in_array($key,$this->encoded_fields)){
				$data[$key]=$this->decode_metadata($value);
			}
		}
		return $data;
	}


	

    //returns survey metadata array
    function get_metadata($sid)
    {
        $this->db->select("metadata");
        $this->db->where("id",$sid);
        $survey=$this->db->get("surveys")->row_array();

        if ($survey)
        {
            return $this->decode_metadata($survey['metadata']);
        }
    }

	/**
	*
	* insert new survey and return new survey id
	*
	* @options - array()
	*/
	function insert($type,$options)	
	{
		//var_dump($options['idno']);

		//validate
		if ($this->validate_survey($options)){						
			//create folder for survey
			$options['dirpath']=$this->setup_folder($repositoryid='central', $folder_name=md5($options['surveyid']));
		}

		//encode json fields
		foreach ($this->encoded_fields as $field){
			if(isset($options[$field])){
				$options[$field]=$this->encode_metadata($options[$field]);
			}
		}
						
		$data=array();
		//default values, if no values are passed in $options
		$data['created']=date("U");
		$data['changed']=date("U");

		foreach($options as $key=>$value){
			if (in_array($key,$this->survey_fields) ){
				$data[$key]=$value;
			}
		}
		
		//create new study
		$result=$this->db->insert('surveys', $data); 

		if ($result===false){
			throw new Exception($this->db->error('message'));
		}
		
		return $this->db->insert_id();
	}
	
	
	/**
	*
	* update survey
	*
	* @options - array()
	*/
	function update($sid,$options)
	{
		$data=array();
		
		foreach($options as $key=>$value)
		{
			if (in_array($key,$this->survey_fields) )
			{
				$data[$key]=$value;
			}
		}
		
		$this->db->where('id',$sid);
		$result=$this->db->update('surveys', $data); 

		if ($result===false){
			throw new Exception($this->db->error('message'));
		}
		
		return TRUE;
	}


	/**
	 * 
	 * 
	 * Update survey variables count
	 */
	function  update_varcount($sid,$count=null)
	{
		if($count==null){
			//get a count of variables
			$count=$this->Variable_model->get_variables_count($sid);
		}

		$options=array(
			'varcount'=>$count
		);

		$this->db->where('id',$sid);
		$this->db->update('surveys',$options);	
	}




	/**
	*
	* Build a range of data collection years range
	*
	* It uses the start and end as range and add each year as a new row
	* in the database.
	*
	* e.g. for range 2005-2010, there will be 6 rows in the survey_rows
	*/
	function update_survey_years($sid, $start_year, $end_year)
	{		
		//remove existing dates if any
		$this->db->delete('survey_years',array('sid' => $sid));

		$start=(integer)$start_year;
		$end=(integer)$end_year;

		if ( ($start_year > 0 && $start_year < 1600) || $start_year > 3000 || ($end_year >0 && $end_year < 1600) || $end_year > 3000){
			throw new Exception("INVALID_YEAR_RANGE");
		}

		if ($start==0){
			$start=$end;
		}

		if($end==0){
			$start=$end;
		}

		//build an array of years range
		$years=range($start,$end);

		//insert dates into database
		foreach($years as $year){
			$options=array(
						'sid' => $sid,
						'data_coll_year' => $year);
			//insert
			$result=$this->db->insert('survey_years',$options);

			if ($result===false){
				throw new Exception($this->db->error('message'));
			}
		}
	}
	
	
	/**
	* Delete survey and related data
	*
	*
	*/
	function delete($id)
	{
		$this->db->where('id', $id); 
		$deleted=$this->db->delete('surveys');
		
		if ($deleted)
		{
			//remove variables
			$this->db->where('surveyid_fk', $id); 
			$this->db->delete('variables');		
			
			//remove external resources
			$this->db->where('survey_id', $id); 
			$this->db->delete('resources');					

			//remove topics
			$this->db->where('sid', $id); 
			$this->db->delete('survey_topics');					

			//remove citations
			$this->db->where('sid', $id); 
			$this->db->delete('survey_citations');					

			//remove collection dates
			$this->db->where('sid', $id); 
			$this->db->delete('survey_years');
			
			//remove repos
			$this->db->where('sid', $id); 
			$this->db->delete('survey_repos');

			//remove alias
			$this->db->where('sid', $id); 
			$this->db->delete('survey_aliases');
			
			//remove countries
			$this->db->where('sid', $id); 
			$this->db->delete('survey_countries');

			//remove tags
			$this->db->where('sid', $id); 
			$this->db->delete('survey_tags');
			
			//remove notes
			$this->db->where('sid', $id); 
			$this->db->delete('survey_notes');
		}		
	}
	
	
	public function set_survey_metadata($sid, $metadata)
	{
		return $this->update($sid, array('metadata'=>$metadata));
	}	
	
	
    //get metadata
	public function get_survey_metadata_array($sid)
	{
		$this->db->select("metadata");
		$this->db->where("id",$sid);
		
		$q=$this->db->get("surveys");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['metadata'];
		}
	}
	
	
	
	//is survey published
	public function is_published($sid)
	{
		$this->db->select("published");
		$this->db->where("id",$sid);
		
		$q=$this->db->get("surveys");
		
		if ($q)
		{
			$row=$q->row_array();
			
			return $row['published'];
		}
	}
	
	
	//return an array of all survey types array
	public function get_survey_types_array()
	{
		return $this->Survey_type_model->get_list();
	}
	
	
	//set study publish status
	public function set_publish_status($sid,$status)
	{
		if (!in_array($status,array(0,1)) ){
			throw new Exception("INVALID_STATUS_VALUE");
		}

		$options=array(
			'published'=>$status
		);
		
		$this->update($sid,$options);
	}
    
    
    /**
	* returns internal survey id by SURVEYID
	* checks for ID in both surveys and aliases table
	**/
	function find_by_idno($idno)
	{
		$this->db->select('id');
		$this->db->where('surveyid', $idno); 
		$query=$this->db->get('surveys')->row_array();
		
		if ($query)
		{
			return $query['id'];
		}
		
		//check surveyid in survey aliases
		$this->db->select('sid');
		$this->db->where(array('alternate_id' => $idno) );
		$query=$this->db->get('survey_aliases')->result_array();

		if ($query)
		{
			return $query[0]['sid'];
		}
		
		return false;
	}
    
	
    
    //@topics array(topic, vocab, uri)
    function update_survey_topics($sid,$topics)
	{
        //TODO
	}
	
	
	
    /**
	*
	* @countries array name, abbreviation
	**/
	function update_survey_countries($sid, $countries)
	{
		$this->load->model("Country_model");
        
        //delete existing survey countries
        $this->Country_model->delete_by_sid($sid);
        
        if (!$countries){
            return;
        }
        
		$data=array();
		foreach ($countries as $row)
		{
            $country=$row['name'];
            
			//get country ISO code
			$countryid=$this->Country_model->find_country_by_name($country);
			
			//add to survey_countries
            $options=array(
					'sid'			=>$sid,
					'country_name'	=>$country,
					'cid'			=>$countryid
				);
            $this->db->insert('survey_countries',$options);
		}		
	}


    
    //encode metadata for db storage
    public function encode_metadata($metadata_array)
    {
        return base64_encode(serialize($metadata_array));
    }

    //decode metadata to array
    public function decode_metadata($metadata_encoded)
    {
        return unserialize(base64_decode($metadata_encoded));
    }

	//import RDF
	public function import_rdf($surveyid,$filepath){
		$this->load->model("Resource_model");
		return $this->Resource_model->import_rdf($surveyid,$filepath);
	}


	//list studies by current user
	public function get_studies_by_user($userid,$limit=20)
	{
		$this->db->select("id,idno,title,nation,year_start,year_end,created,created_by,changed,changed_by,published");
		$this->db->where('created_by',$userid);
		$this->db->order_by('created_by','DESC');
		$this->db->limit($limit);
		$result=$this->db->get('surveys')->result_array();
		return $result;
	}

	//list recent studies
	public function get_recent_studies($limit=20)
	{
		$this->db->select("id,surveyid,titl,nation,year_start,year_end,created,created_by,changed,changed_by,published");
		$this->db->order_by('created_by','DESC');
		$this->db->limit($limit);
		$result=$this->db->get('surveys')->result_array();
		return $result;
	}


	public function set_data_access_type($sid,$da_type,$da_link)
	{
		$options=array(
			'formid'=>$da_type,
			'link_da'=>$da_link
		);
		return $this->update($sid,$options);
	}


	//validate survey IDNO
	public function validate_survey_idno($idno)
	{	
		//check if the survey id already exists
		$id=$this->find_by_idno($idno);	

		if ($id){
			$this->form_validation->set_message(__FUNCTION__, 'The ID should be unique.' );
			return false;
		}
		return true;
	}



	//validate survey IDNO
	public function validate_study_type($datatype)
	{	
		//check if the survey id already exists
		$id=$this->Survey_type_model->get_stype_id_by_name($datatype);	

		if (!$id){
			$this->form_validation->set_message(__FUNCTION__, 'The %s is not valid. Supported types are: '. implode(", ", $this->Survey_type_model->get_names_array()));
			return false;
		}
		return true;
	}


	

	/**
	 * 
	 * 
	 * Validate survey
	 * @options - array of survey fields
	 * @is_new - boolean - new survey or updating and existing survey
	 * 
	 **/
	function validate_survey($options,$is_new=true)
	{		
		$this->load->library("form_validation");
		$this->form_validation->reset_validation();
		$this->form_validation->set_data($options);
	
		//validation rules for a new record
		if($is_new){				
			#$this->form_validation->set_rules('surveyid', 'IDNO', 'xss_clean|trim|max_length[255]|required');
			$this->form_validation->set_rules('titl', 'Title', 'required|xss_clean|trim|max_length[255]');	
			$this->form_validation->set_rules('repositoryid', 'Collection ID', 'required|xss_clean|trim|max_length[25]');	
			#$this->form_validation->set_rules('nation', 'Country name', 'required|xss_clean|trim|max_length[255]');	
			$this->form_validation->set_rules('year', 'year', 'required|is_numeric|xss_clean|trim|max_length[4]');	

			
			//survey idno validation rule
			$this->form_validation->set_rules(
				'surveyid', 
				'IDNO',
				array(
					"required",
					array('validate_survey_idno',array($this, 'validate_survey_idno')),				
				)		
			);
		}
		
		if ($this->form_validation->run() == TRUE){
			return TRUE;
		}
		
		//failed
		$errors=$this->form_validation->error_array();
		$error_str=$this->form_validation->error_array_to_string($errors);
		throw new ValidationException("VALIDATION_ERROR: ".$error_str, $errors);
	}


	//create folder for the study
    public function setup_folder($repositoryid, $folder_name)
    {
		$catalog_root=get_catalog_root();

        $repository_folder=$catalog_root.'/'.$repositoryid;
        $survey_folder=$repository_folder.'/'.$folder_name;

        //create the repo folder and survey folder
        @mkdir($survey_folder, 0777, $recursive=true);

        if(!file_exists($repository_folder)){
            throw new Exception("REPO_FOLDER_NOT_CREATED:".$repository_folder);
        }

        if(!file_exists($survey_folder)){
            throw new Exception("SURVEY_FOLDER_NOT_CREATED:".$survey_folder);
        }

		//relative path to catalog_root
        return $repositoryid.'/'.$folder_name;
    }

}//end-class
	
