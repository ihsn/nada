<?php
class Citations extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		
		$this->template->set_template('admin');
		$this->load->model('Citation_model');
		$this->load->model('Resource_model');
		$this->load->helper(array ('querystring_helper','url', 'form') );
		$this->load->library( array('form_validation','pagination') );
		
		$this->lang->load('general');
		$this->lang->load('citations');
		//$this->output->enable_profiler(TRUE);
	}
 
	function index()
	{					
		//get records
		$data['rows']=$this->_search();
		
		//load the contents of the page into a variable
		$content=$this->load->view('citations/index', $data,true);
		
		//page title
		$this->template->write('title', t('title_citations'),true);	
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}
	
	
	/**
	* returns the paginated result
	* 
	* supports: sorting, searching, pagination
	*/
	function _search()
	{	
		//citations session id
		$session_id="citations";

		//reset if reset param is set
		if ($this->input->get('reset'))
		{
			$this->session->unset_userdata($session_id);
		}
	
		//all keys that needs to be persisted
		$get_keys_array=array('ps','offset','sort_order','sort_by','keywords','field');
		
		//session array
		$sess_data=array();
		
		//add get values to session array
		foreach($get_keys_array as $key)
		{
				$value=get_post_sess('citations',$key);
				if ($value)
				{
					$sess_data[$key]=$value;
				}	
		}
				
		//store values to session
		$this->session->set_userdata(array($session_id=>$sess_data));
		
		$this->per_page = 	get_post_sess($session_id,"ps");
		$this->field=		get_post_sess($session_id,'field');
		$this->keywords=	get_post_sess($session_id,'keywords');
		$this->offset=		get_post_sess($session_id,'offset');//current page
		$this->sort_order=	get_post_sess($session_id,'sort_order') ? get_post_sess($session_id,'sort_order') : 'desc';
		$this->sort_by=		get_post_sess($session_id,'sort_by') ? get_post_sess($session_id,'sort_by') : 'changed';
		
		if (!is_numeric($this->per_page))
		{
			$this->per_page=20;
		}
				
		//filter
		$filter=NULL;

		//simple search
		if ($this->keywords){
			$filter[0]['field']=$this->field;
			$filter[0]['keywords']=$this->keywords;
		}		
		
		//records
		$rows=$this->Citation_model->search($this->per_page, $this->offset,$filter, $this->sort_by, $this->sort_order);

		//total records in the db
		$this->total = $this->Citation_model->search_count();

		if ($this->offset>$this->total)
		{
			$this->offset=$this->total-$this->per_page;
			
			//search again
			$rows=$this->Citation_model->search($this->per_page, $this->offset,$filter, $this->sort_by, $this->sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/citations');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $this->total;
		$config['per_page'] = $this->per_page;
		$config['query_string_segment']="offset"; 
		$config['page_query_string'] = TRUE;
		$config['additional_querystring']=get_querystring( array('keywords', 'field','ps'));//pass any additional querystrings
		$config['num_links'] = 1;
		$config['full_tag_open'] = '<span class="page-nums">' ;
		$config['full_tag_close'] = '</span>';
		
		//intialize pagination
		$this->pagination->initialize($config); 
		return $rows;		
	}

	function add()
	{
		$this->edit(NULL);
	}

	function edit($id=NULL)
	{
		//for related surveys dialog box
		//$this->template->add_css('javascript/jquery/themes/ui-lightness/jquery-ui-1.7.2.custom.css');
		//$this->template->add_js('javascript/jquery/ui/ui.core.js');
		//$this->template->add_js('javascript/jquery/ui/jquery-ui-1.7.2.custom.js');
		$this->template->add_css('javascript/jquery/themes/base/minified/jquery-ui.min.css');
		$this->template->add_js('javascript/jquery/ui/minified/jquery-ui.custom.min.js');

		$this->citation_id=$id;//needed for the citation edit/add view
		
		//form action url
		$this->html_form_url=site_url().'/admin/citations';		
		
		if ($id!=NULL && !is_numeric($id) )
		{
			show_404();
		}
		
		if ($id==NULL)
		{
			//form/page title
			$data['form_title']=t('add_new_citation');
			
			//form url
			$this->html_form_url.='/add';
		}
		else
		{
			$data['form_title']=t('edit_citation');
			
			//form url
			$this->html_form_url.='/edit/'.$id;
		}
		
		
       //validate form input
		$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('authors', 'Authors', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('url', 'URL', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('volume', 'Volume', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('issue', 'Issue', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('pub_year', 'Year', 'xss_clean|trim|max_length[4]|is_numeric');
		$this->form_validation->set_rules('doi', 'DOI', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('flag', t('flag_as'), 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('published', t('published'), 'xss_clean|trim|is_numeric');
		
		//ignore the form submit if only changing the citation type
		if ($this->input->post("select")==FALSE)
		{		
			//add/update record
			if ($this->form_validation->run() == TRUE) 
			{ 
				$this->_update($id);
			}
			else
			{
				//loading the form for the first time			
				if ($id!=NULL && $this->input->post("submit")===FALSE)
				{
					//load data from database
					$db_row=$this->Citation_model->select_single($id);			
					if (!$db_row)
					{
						show_error('INVALID ID');
					}
					
					if ($db_row['authors'])
					{
						$this->_set_post_from_db($db_row);
					}
					$data=array_merge($data,$db_row);
				}
			}
		}		

		//flash data message
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		
		//load list of all surveys		
		$survey_list['surveys']=$this->Citation_model->get_all_surveys(NULL);
		
		//surveys attached to the citations
		$selected_surveys=array();
		
		//attached survey from the postback data
		if ($this->input->post("sid"))
		{
			//get the selected surveys from sid
			$survey_id_arr=$this->input->post("sid");
		
			//get survey info from db
			$selected_surveys=$this->Citation_model->get_surveys($survey_id_arr);
		}
		else
		{	
			//see if the edited citation has surveys attached, otherwise assign empty array
			$selected_surveys=isset($data['related_surveys']) ? $data['related_surveys'] : array();
		}
		
		//IDs of selected surveys
		$data['selected_surveys_id_arr']=$this->_get_related_surveys_array($selected_surveys);
		
		//load list of formatted surveys list for choosing related surveys			
		$data['survey_list']=$this->load->view('citations/selected_surveys', array('selected_surveys'=>$selected_surveys),TRUE);	
			
		//load form			
		$content=$this->load->view('citations/edit', $data,TRUE);
			
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', $data['form_title'],true);
		$this->template->render();				
	}



	/**
	*
	* Returns formatted selected survey list from session
	**/
	function selected_surveys($skey,$isajax=1)
	{
		//get survey id array from session
		$sess=(array)$this->session->userdata($skey);
		
		if(!isset($sess['selected']))
		{
			return false;
		}
		
		if(!count($sess['selected'])>0)
		{
			return false;
		}
				
		//get survey info from db
		$data['selected_surveys']=$this->Citation_model->get_surveys($sess['selected']);
		
		//load formatted list
		$output=$this->load->view("citations/selected_surveys",$data,TRUE);
		
		if ($isajax==1)
		{
			echo $output;
		}
		else
		{
			return $output;
		}
		
	}

	/**
	*
	* Returns an array of survey IDs
	*
	**/
	function _get_related_surveys_array($surveys)
	{
		$result=array();
		foreach($surveys as $survey)
		{
			$result[]=$survey['id'];
		}
		return $result;
	}

	//load authors, editors, translators into the POST array
	function _set_post_from_db($db_row)
	{
		$keys=array(
				'author'=>'authors',
				'editor'=>'editors',
				'translator'=>'translators',
				);
		foreach($keys as $key=>$value)
		{
			$authors=($db_row[$value]);//unserialize authors,editors,translators
		
			if (is_array($authors))
			{
				$fname=array();
				$lname=array();
				$initial=array();

				foreach($authors as $author)
				{
					$fname[]=$author['fname'];
					$lname[]=$author['lname'];
					$initial[]=$author['initial'];
				}
	
				$_POST[$key.'_fname']=$fname;
				$_POST[$key.'_lname']=$lname;
				$_POST[$key.'_initial']=$initial;
			}
		}	
	}
	
	
	/**
	* Add/update a citation row using POST data
	*
	**/
	function _update($id)
	{
		$options=array();
		$post_arr=$_POST;
					
		//read post values to pass to db
		foreach($post_arr as $key=>$value)
		{
			$options[$key]=$this->security->xss_clean($this->input->post($key));
		}					

		//
		if (isset($options['pub_year']))
		{
			$options['pub_year']=(integer)$options['pub_year'];
		}	
		if (isset($options['pub_day']))
		{
			$options['pub_day']=$options['pub_day'];
		}	
		
		//process authors from the postback data
		$options['authors']=($this->_process_authors('author'));
		$options['editors']=($this->_process_authors('editor'));
		$options['translators']=($this->_process_authors('translator'));
		
		//reset fields for which there is not data posted
		$reset_fields=array(
					'subtitle','alt_title','authors','editors','translators','volume',
					'issue', 'idnumber', 'edition', 'place_publication', 'place_state',
					'publisher', 'url','page_from', 'page_to',
					'data_accessed','organization', 'pub_day','pub_month', 'pub_year','abstract');
		
		foreach($reset_fields as $field)
		{
			//check if the field is not in the postback data
			if (!array_key_exists($field,$options))
			{
				//add a null value for the non-existent field
				$options[$field]='';
			}
		}
		
		try
		{
			if ($id==NULL)
			{	
				//insert record, returns the new id
				$id=$this->Citation_model->insert($options);
				$db_result=FALSE;
				
				if($id!==FALSE)
				{
					$db_result=TRUE;
					
					//log to database
					$this->db_logger->write_log('new',$options['title'],'citations');
				}	
			}
			else
			{				
				//update record
				$db_result=$this->Citation_model->update($id,$options);	
				
				//log to database
				$this->db_logger->write_log('change',$options['title'],'citations');
			}
		
			if (isset($options['sid']))
			{
				//update related surveys
				$this->_attach_related_surveys($id, $options['sid']);			
			}
		}
		catch(Exception $e)
		{
			//insert/update failed
			$this->form_validation->set_error($e->message_detailed());
			$db_result=FALSE;
		}
			
		if ($db_result!==FALSE)
		{
			//update successful
			$this->session->set_flashdata('message', t('form_update_success'));
			
			//redirect back to the list
			redirect("admin/citations","refresh");
		}
	}

	/**
	*
	* Add/update related surveys for a citation
	*
	*
	**/
	function _attach_related_surveys($citationid,$surveys)
	{
		if (!is_array($surveys))
		{
			return FALSE;
		}

		//remove all related surveys 
		$this->Citation_model->delete_related_survey($citationid);

		//add related surveys
		$this->Citation_model->attach_related_surveys($citationid,$surveys);
		return TRUE;
	}

	function _process_authors($key='author')
	{
		$list=array();

		$keys=array();
		$keys['fname']=$key.'_fname';
		$keys['lname']=$key.'_lname';
		$keys['initial']=$key.'_initial';
		
		//arrays of postback data
		$fname_array=$this->input->post($keys['fname']);
		$lname_array=$this->input->post($keys['lname']);
		$initial_array=$this->input->post($keys['initial']);
		
		//var_dump($this->input->post($keys['fname']));exit;
		if ($fname_array==FALSE || !is_array($fname_array))
		{
			return FALSE;
		}
		
		//combine the values for individual fiels into one array
		$authors=array();
		
		//iterate rows
		for($i=0;$i<count($fname_array);$i++)
		{
			if ($fname_array[$i]!='' || $lname_array[$i]!='' )
			{
				$authors[]=array(
						'fname'=>$fname_array[$i],
						'lname'=>$lname_array[$i],
						'initial'=>$initial_array[$i]
				);
			}
		}
		
		return $authors;
	}

	
	/**
	* Delete one or more records
	* note: to use with ajax/json, pass the ajax as querystring
	* 
	* id 	int or comma seperate string
	*/
	function delete($id=NULL)
	{	
		if ($id==NULL)
		{
			return FALSE;
		}
				
		//array of id to be deleted
		$delete_arr=array();
	
		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id))
		{
			$tmp_arr=explode(",",$id);
		
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value))
				{
					$delete_arr[]=$value;
				}
			}
			
			if (count($delete_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!='')
				{
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}
				
				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/citations',"refresh");
			}	
		}		
		else
		{
			$delete_arr[]=$id;
		}
		
		if ($this->input->post('cancel')!='')
		{
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/citations');
			}	
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//get citation info
				$citation=$this->Citation_model->select_single($item);
				
				//delete if exists
				if ($citation)
				{
					//log to database
					$this->db_logger->write_log('delete',$citation['title'],'citations');
					
					//confirm delete	
					$this->Citation_model->delete($item);	
				}								
			}

			//for ajax calls, return output as JSON						
			if ($ajax!='')
			{
				echo json_encode(array('success'=>"true") );
				exit;
			}
						
			//redirect page url
			$destination=$this->input->get_post('destination');
			
			if ($destination!="")
			{
				redirect($destination);
			}
			else
			{
				redirect('admin/citations');
			}	
		}
		else
		{
			//ask for confirmation
			$content=$this->load->view('resources/delete', NULL,true);
			
			$this->template->write('content', $content,true);
	  		$this->template->render();
		}		
	}
	
	

	/**
	*
	* Imports a citation from BibTex format
	*
	**/
	function import()
	{	
		$this->template->add_css('javascript/jquery/themes/base/minified/jquery-ui.min.css');
		$this->template->add_js('javascript/jquery/ui/minified/jquery-ui.custom.min.js');		
		
		$this->form_validation->set_rules('citation_string', t('citation_string'), 'xss_clean|trim|required');
		$string=$this->input->post("citation_string");

		if ($string) 
		{				
			//$string=$this->input->post("citation_string");
			$format=$this->input->post("citation_format");
			$bib_array=NULL;
			
			if ($string)
			{
				switch($format)
				{
					case 'bibtex':
						$this->load->library('bibtex');
						$bib_array=$this->bibtex->parse_string($string);
					break;
					
					case 'endnote_bibix':
						$this->load->library('endnote');
						$bib_array=$this->endnote->parse($string);					
					break;
					
					case 'endnote_ris':
						$this->load->library('endnote_ris');
						$bib_array=$this->endnote_ris->parse($string);										
					break;
					
					case 'nada_serialized':
						$bib_array=(array)json_decode($string);
					break;
				}
				
				$format='bibtex';				
				$published=(int)$this->input->post("published");
				$flag=$this->input->post("flag");
				$surveys=$this->input->post("sid");
				
				if (count($bib_array)>0)
				{	
					$success=0;
					$failed=0;
					
					foreach($bib_array as $entry)
					{	
						$entry=(array)$entry;
						if (!isset($entry['title']))
						{
							$failed++;
							continue;
						}
						
						$entry['published']=$published;
						$entry['flag']=$flag;
						
						$new_id=$this->Citation_model->insert($entry);
					
						if (is_numeric($new_id))
						{
							//attach surveys to the newly imported citation
							$this->_attach_related_surveys($citationid=$new_id,$surveys);
							
							$success++;
							
							//log to database
							$this->db_logger->write_log('import',$entry['title'],'citations');

							//redirect('admin/citations/edit/'.$new_id);
							//return;
						}
						else
						{
							$failed++;
						}
					}
					
					//set message
					$this->session->set_flashdata('message', sprintf(t('citation_import_status'),$success,$failed));
					//redirect
					redirect('admin/citations/');
				}
				else				
				{	
					$this->form_validation->set_error(t('citation_string_invalid'));
				}
			}
		}
		
		$data=array();
		
		//list of all surveys
		$survey_list['surveys']=$this->Citation_model->get_all_surveys(NULL);
		
		//surveys attached to the citations
		$selected_surveys=array();
		
		//attached survey from the postback data
		if ($this->input->post("sid"))
		{
			//get the selected surveys from sid
			$survey_id_arr=$this->input->post("sid");
		
			//get survey info from db
			$selected_surveys=$this->Citation_model->get_surveys($survey_id_arr);
		}
		else
		{	
			//see if the edited citation has surveys attached, otherwise assign empty array
			$selected_surveys=isset($data['related_surveys']) ? $data['related_surveys'] : array();
		}
		
		//IDs of selected surveys
		$data['selected_surveys_id_arr']=$this->_get_related_surveys_array($selected_surveys);
		
		//load list of formatted surveys list for choosing related surveys			
		$data['survey_list']=$this->load->view('citations/selected_surveys', array('selected_surveys'=>$selected_surveys),TRUE);	
			
		
		//load the contents of the page into a variable
		$content=$this->load->view('citations/import_citation', $data,true);
		
		//page title
		$this->template->write('title', t('title_citations'),true);	
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}

	/**
	*
	* Publish or unpublish a citation using ajax
	*
	*/
	function publish($id=NULL,$publish=NULL)
	{
		if (!is_numeric($id) || !is_numeric($publish))
		{
			show_404();
		}
		
		$result=$this->Citation_model->update($id,array('published'=>$publish));
		
		echo json_encode(array('result'=>(int)$result) );
	}
	
	function export()
	{
		show_error("NOT_IMPLEMENTED");
	}
	
	
}
/* End of file citations.php */
/* Location: ./controllers/admin/citations.php */