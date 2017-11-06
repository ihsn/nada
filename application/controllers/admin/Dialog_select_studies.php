<?php
/**
*
* Dialog for study selection
**/
class Dialog_select_studies extends MY_Controller {

	var $active_repo=NULL;
	var $sess_id=NULL;
	var $selected_surveys=array();
 
    public function __construct()
    {
        parent::__construct();
       	$this->load->model('Catalog_admin_search_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		$this->load->helper("catalog");
		$this->template->set_template('blank_iframe');
		
		//load language file
		$this->lang->load('general');
    	$this->lang->load('catalog_search');
		$this->lang->load('catalog_admin');

		//$this->output->enable_profiler(TRUE);
	}
	
	
	/**
	*
	* @id	session key
	**/
	public function index($skey){
		//$this->Catalog_model->active_repo=NULL;
		
		//add/remove excluded items in the session
		$this->update_excluded_items($skey);		
		
		$db_rows=$this->_search($skey);	
		$db_rows['attached_studies']=$this->get_items($skey,'selected');
		$db_rows['excluded_studies']=$this->get_items($skey,'excluded');
		$db_rows['sess_id']=$skey;
		$this->load->view('dialogs/dialog_select_studies', $db_rows);
	}
	
	
	private function get_items($skey,$section='selected')
	{
		$sess_data=(array)$this->session->userdata($skey);
		if (isset($sess_data[$section]))
		{
			return $sess_data[$section];
		}
		
		return array();	
	}

	
	
	
	private function _search($skey)
	{
		//records to show per page
		$limit = $this->input->get("ps");
		
		if($limit===FALSE || !is_numeric($limit))
		{
			$limit=100;
		}
		
		//comma seperated list of excluded studies
		$excluded= $this->get_items($skey,'excluded');		
				
		//current page
		$offset=$this->input->get('per_page');//$this->uri->segment(4);

		//filter to further limit search
		$filter=array();
		
		//exclude studies
		if(count($excluded)>0)
		{
			$filter=array(	
						sprintf('surveys.id not in (%s)',implode(",",$excluded))
						);
		}

		
		if($this->input->get("show_selected_only")==1)
		{
			$selected_items=$this->get_items($skey,'selected');
			if(count($selected_items)>0)
			{
				array_push($filter, sprintf('surveys.id in (%s)',implode(",", $selected_items) ));
			}	
		}
		
		
		$allowed_fields=array('titl','nation','surveyid','proddate','authenty');
		
		$field=$this->input->get("field");
		$keywords=$this->input->get("keywords");
		
		$search_options=array();
		if (in_array($field,$allowed_fields))
		{
			$search_options[$field]=$keywords;
		}
		
		$this->Catalog_admin_search_model->set_active_repo('');
		
		//survey rows
		$data['rows']=$this->Catalog_admin_search_model->search($search_options,$limit,$offset, $filter);

		//total records in the db
		$total = $this->Catalog_admin_search_model->search_count;

		if ($offset>$total)
		{
			$offset=0;//$total-$limit;
			$limit=15;
			//search again
			$data['rows']=$this->Catalog_admin_search_model->search($search_options,$limit, $offset,$filter);
		}
		
		

		//set pagination options
		$base_url = site_url('admin/dialog_select_studies/index/'.$skey);
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $limit;
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('id', 'sort_by','sort_order','keywords', 'field','ps','show_selected_only'));//pass any additional querystrings
		$config['next_link'] = t('page_next');
		$config['num_links'] = 5;
		$config['prev_link'] = t('page_prev');
		$config['first_link'] = t('page_first');
		$config['last_link'] = t('last');
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $data;		
	}
	
	
	private function update_excluded_items($skey)
	{
		$excluded= $this->input->get("excluded");
		$excluded= explode(",",$excluded);
		foreach($excluded as $key=>$value)
		{
			if(!is_numeric($value)) 
			{
				$excluded=array();break;
			}
			
			$excluded[$key]=(int)($value);
		}
		
		if (count($excluded)>0)
		{
			$sess_data=$this->get_session($skey);
			$sess_data['excluded']=$excluded;			
			$this->session->set_userdata(array($skey=>$sess_data));
		}
		
		
	}
	
	
	private function get_session($skey)
	{
		$sess_data=$this->session->userdata($skey);
		
		//create empty error if not an array
		if (!is_array($sess_data))
		{
			$sess_data=array('selected'=>array(),'excluded'=>array());
		}
		
		return $sess_data;
	}
	
	/**
	*
	* Add surveys to session by key
	**/	
	public function add($skey,$sid,$isajax=0)
	{
		$id_list=explode(",",$sid);
		
		foreach($id_list as $key=>$value)
		{
			if (!is_numeric($value))
			{
				unset($id_list[$key]);
			}
		}
		
		//get session data by key
		$sess_data=$this->session->userdata($skey);
		
		//create empty error if not an array
		if (!is_array($sess_data))
		{
			$sess_data=array('selected'=>array(),'excluded'=>array());
		}

		//add survey to array 
		foreach($id_list as $key=>$value)
		{
			if (!in_array($value,$sess_data))
			{
				$sess_data['selected'][]=(int)$value;
			}
		}
				
		//update session
		$related_surveys[$skey] = $sess_data;
		$this->session->set_userdata($related_surveys);
		
	}
	
	
	/**
	*
	* Remove surveys from session using key
	**/
	public function remove($skey,$sid,$isajax=0)
	{
		if (!is_numeric($sid))
		{
			show_error("INVALID_ID");
		}
	
		//get session data by key
		$sess_data=$this->session->userdata($skey);
		
		//create empty error if not an array
		if (!is_array($sess_data))
		{
			$sess_data=array('selected'=>array(),'excluded'=>array());
		}
		
		//remove survey from array 
		foreach($sess_data['selected'] as $key=>$value)
		{
			if ($value==$sid)
			{
				unset($sess_data['selected'][$key]);
				break;
			}
		}
		

		//update session
		$related_surveys[$skey] = $sess_data;
		$this->session->set_userdata($related_surveys);
		
		if($isajax==0)
		{
		//	redirect('/admin/related_surveys/index/'.$skey.'/?per_page='.$this->session->userdata('oldurl'));
		}
	}
	
	/**
	*
	* Remove all session data for a key
	**/
	function clear_all($skey)
	{
		$this->session->unset_userdata($skey);
	}
	
	function get_list($skey)
	{
		header('Content-type: application/json');
		$sess_data=$this->session->userdata($skey);
		$output=array();
		if(!is_array($sess_data['selected']))
		{
			$sess_data['selected']=array();
		}
		$output['selected']=implode(",",$sess_data['selected']);
		echo json_encode($output);
	}
	
	
	function dump($skey)
	{
		echo '<pre>';
		$sess_data=$this->session->userdata($skey);
		var_dump($sess_data);
	}
	
}
	
 