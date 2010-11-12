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
		//records to show per page
		$per_page = $this->input->get("ps");
		
		if (!is_numeric($per_page))
		{
			$per_page=20;
		}
				
		//current page
		$offset=$this->input->get('offset');//$this->uri->segment(4);

		//sort order
		$sort_order=$this->input->get('sort_order') ? $this->input->get('sort_order') : 'desc';
		$sort_by=$this->input->get('sort_by') ? $this->input->get('sort_by') : 'changed';

		//filter
		$filter=NULL;

		//simple search
		if ($this->input->get_post("keywords") ){
			$filter[0]['field']=$this->input->get_post('field');
			$filter[0]['keywords']=$this->input->get_post('keywords');			
		}		
		
		//records
		$rows=$this->Citation_model->search($per_page, $offset,$filter, $sort_by, $sort_order);

		//total records in the db
		$total = $this->Citation_model->search_count();

		if ($offset>$total)
		{
			$offset=$total-$per_page;
			
			//search again
			$rows=$this->Citation_model->search($per_page, $offset,$filter, $sort_by, $sort_order);
		}
		
		//set pagination options
		$base_url = site_url('admin/citations');
		$config['base_url'] = $base_url;
		$config['total_rows'] = $total;
		$config['per_page'] = $per_page;
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
		$this->citation_id=$id;//needed for the citation edit/add view
		
		if ($id!=NULL && !is_numeric($id) )
		{
			show_404();
		}
		
		if ($id==NULL)
		{
			//form/page title
			$data['form_title']=t('add_new_citation');
		}
		else
		{
			$data['form_title']=t('edit_citation');
		}
		
       //validate form input
		$this->form_validation->set_rules('title', 'Title', 'xss_clean|trim|max_length[255]|required');
		$this->form_validation->set_rules('authors', 'Authors', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('url', 'URL', 'xss_clean|trim|max_length[255]');
		$this->form_validation->set_rules('volume', 'Volume', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('issue', 'Issue', 'xss_clean|trim|max_length[45]');
		$this->form_validation->set_rules('pub_year', 'Year', 'xss_clean|trim|max_length[4]|is_numeric');
		
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
		
		//attached survey from the postback data
		if ($this->input->post("sid"))
		{
			$survey_list['related_surveys']=$this->input->post("sid");
		}
		else
		{	//attached surveys from database		
			$survey_list['related_surveys']=isset($data['related_surveys']) ? $this->_get_related_surveys_array($data['related_surveys']) : array();
		}
		
		//load list of formatted surveys list for choosing related surveys			
		$data['survey_list']=$this->load->view('citations/surveys', $survey_list,TRUE);	
			
		//load form			
		$content=$this->load->view('citations/edit', $data,TRUE);
			
		//render page
		$this->template->write('content', $content,true);
		$this->template->write('title', $data['form_title'],true);
		$this->template->render();				
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
	
	
	function _update($id)
	{
		$options=array();
		$post_arr=$_POST;
					
		//read post values to pass to db
		foreach($post_arr as $key=>$value)
		{
			$options[$key]=$this->input->xss_clean($this->input->post($key));
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
				}	
			}
			else
			{				
				//update record
				$db_result=$this->Citation_model->update($id,$options);		
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
			$this->session->set_flashdata('message', 'Form was updated successfully!');
			
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
	function delete($id)
	{			
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
				//confirm delete	
				$this->Citation_model->delete($item);
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
	
	function import()
	{	
		$this->form_validation->set_rules('bibtex_string', 'Bibtex_String', 'xss_clean|trim|required');
		
		if ($this->form_validation->run() == TRUE) 
		{					
			$this->load->library('bibtex');		
			$string=$this->input->post("bibtex_string");
		
			if ($string)
			{
				//parsed entries to nada format
				$bib_array=$this->bibtex->parse_string($string);
				
				if (count($bib_array)>0)
				{	
					$success=0;
					$failed=0;
					
					foreach($bib_array as $entry)
					{	
						if (!isset($entry['title']))
						{
							$failed++;
							continue;
						}
						
						$new_id=$this->Citation_model->insert($entry);
					
						if (is_numeric($new_id))
						{
							$success++;
							//redirect('admin/citations/edit/'.$new_id);
							//return;
						}
						else
						{
							$failed++;
						}
					}
					
					//set message
					$this->session->set_flashdata('message', sprintf(t('bibtex_import_status'),$success,$failed));
					//redirect
					redirect('admin/citations/');
				}
				else				
				{	
					$this->form_validation->set_error('bibtex_string_invalid');
				}
			}
		}
		
		//load the contents of the page into a variable
		$content=$this->load->view('citations/import_bibtex', NULL,true);
		
		//page title
		$this->template->write('title', t('title_citations'),true);	
	
		//pass data to the site's template
		$this->template->write('content', $content,true);
		
		//render final output
	  	$this->template->render();
	}

	
}
/* End of file citations.php */
/* Location: ./controllers/admin/citations.php */