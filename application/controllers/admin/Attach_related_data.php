<?php
/**
*
* Attach related data
**/
class Attach_related_data extends MY_Controller {

	var $active_repo=NULL;
	var $sess_id=NULL;
	var $selected_surveys=array();

  public function __construct()
  {
				parent::__construct();

				$this->load->model('Catalog_admin_search_model');
				$this->load->model("Related_study_model");
				$this->load->model("Catalog_model");

				$this->load->library('pagination');
				$this->load->helper('querystring_helper','url');
				$this->load->helper('form');
				$this->load->helper("catalog");
				$this->template->set_template('admin_blank');

				//load language file
				$this->lang->load('general');
				$this->lang->load('catalog_search');
				$this->lang->load('catalog_admin');

		//$this->output->enable_profiler(TRUE);
	}


	/**
	*
	* @id	survey id
	**/
	public function index($sid)
	{

		$survey=$this->Catalog_model->get_survey($sid);

		if (!$survey)
		{
			show_error("SURVEY NOT FOUND");
		}

		$db_rows=$this->_search($sid);

		$db_rows['survey_id']=$sid;
		$db_rows['survey_title']=$survey['title'];

		$related_studies=$this->Related_study_model->get_relationships($sid);
		$db_rows['attached_studies']=$this->Related_study_model->get_related_studies_id_list($sid);
		$db_rows['relationship_types']=$this->Related_study_model->get_relationship_types_array();

		foreach($related_studies as $row){
			$db_rows['survey_relationships'][$row['sid']]=array(
				'sid'=>$row['sid'],
				'relationship_id'=>$row['relationship_id']
			);
		}



		//echo '<pre>';
		//var_dump($db_rows['related_studies']);
		//echo '</pre>';

		$content=$this->load->view('catalog/select_related_studies', $db_rows,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}


	private function _search($skey){

		//records to show per page
		$limit = $this->input->get("ps");

		if($limit===FALSE || !is_numeric($limit)){
			$limit=100;
		}

		//comma seperated list of excluded studies
		$excluded= array();

		//current page
		$offset=$this->input->get('per_page');//$this->uri->segment(4);

		//filter to further limit search
		$filter=array();

		//exclude studies
		if(count($excluded)>0){
			$filter=array(sprintf('surveys.id not in (%s)',implode(",",$excluded)));
		}


		if($this->input->get("show_selected_only")==1){
			$selected_items=$this->get_items($skey,'selected');

			if(count($selected_items)>0){
				array_push($filter, sprintf('surveys.id in (%s)',implode(",", $selected_items) ));
			}
		}

		$allowed_fields=array('titl','nation','surveyid','proddate','authenty');
		$field=$this->input->get("field");
		$keywords=$this->input->get("keywords");

		$search_options=array();
		if (in_array($field,$allowed_fields)){
			$search_options[$field]=$keywords;
		}

		$this->Catalog_admin_search_model->set_active_repo('');

		//survey rows
		$data['rows']=$this->Catalog_admin_search_model->search($search_options,$limit,$offset, $filter);

		//total records in the db
		$total = $this->Catalog_admin_search_model->search_count;

		if ($offset>$total){
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

}
