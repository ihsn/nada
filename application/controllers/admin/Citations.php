<?php
class Citations extends MY_Controller {

    var  $allowed_attachment_file_types='pdf|doc|docx|txt|zip';
    var  $citations_storage_path;

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
        
        //set storage path
        $this->citations_storage_path=$this->config->item("citations_storage_path");
    }

    function index()
    {
        //get records
        $data['rows']=$this->_search();
        //list of users who created citations
        $data['citation_creators']=$this->Citation_model->get_citations_user_list();

        //flags assigned to citations
        $data['citation_flags']=$this->Citation_model->get_citations_flag_list();

        //citation url stats
        $data['citation_url_stats']=$this->Citation_model->get_citations_url_stats();

        $data['citation_publish_stats']=$this->Citation_model->get_citations_publish_stats();

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

        $search_options=array(
            'keywords'=>$this->input->get("keywords"),
            'flag'=>$this->input->get("flag"),
            'user'=>$this->input->get("user"),
            'published'=>$this->input->get("published"),
            'has_notes'=>$this->input->get("has_notes"),
            'no_survey_attached'=>$this->input->get("no_survey_attached"),
            'url_status'=>$this->input->get("url_status"),
        );

        //records
        $rows=$this->Citation_model->search($this->per_page, $this->offset,$search_options, $this->sort_by, $this->sort_order);

		//total records in the db
		$this->total = $this->Citation_model->search_count();

		if ($this->offset>$this->total)
		{
			$this->offset=$this->total-$this->per_page;

			//search again
			$rows=$this->Citation_model->search($this->per_page, $this->offset,$filter, $this->sort_by, $this->sort_order);
		}

        $citation_id_arr=array();

        //get survey_count by citations
        foreach($rows as $row) {
            $citation_id_arr[] = $row['id'];
        }
        $survey_counts_by_cit=$this->Citation_model->get_survey_counts_by_citation($citation_id_arr);

        foreach($rows as $row_key=>$row) {
            $rows[$row_key]['survey_count']=@$survey_counts_by_cit[$row['id']];
            //var_dump($rows[$row_key]);
        }

        //set pagination options
        $base_url = site_url('admin/citations');
        $config['base_url'] = $base_url;
        $config['total_rows'] = $this->total;
        $config['per_page'] = $this->per_page;
        $config['query_string_segment']="offset";
        $config['page_query_string'] = TRUE;
        //$config['additional_querystring']=get_querystring( array('keywords', 'field','ps'));//pass any additional querystrings
        $config['additional_querystring']=get_querystring( array('keywords', 'user','published','no_survey_attached','flag','has_notes','sort_by','sort_order','collection'));//pass any additional querystrings
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
        $this->template->add_js('javascript/underscore-min.js');
        $this->template->add_js('javascript/jquery.highlight.js');

		$this->citation_id=$id;//needed for the citation edit/add view

		//form action url
		$this->html_form_url=site_url().'/admin/citations';

		if ($id!=NULL && !is_numeric($id) ){
			show_404();
		}

        if ($id==NULL){
            $data['form_title']=t('add_new_citation');
            $this->html_form_url.='/add';
        }
        else{
            $data['form_title']=t('edit_citation');
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
        if ($this->input->post("select")==FALSE){
            //add/update record
            if ($this->form_validation->run() == TRUE){
                $this->_update($id);
            }
            else{
                //loading the form for the first time
                if ($id!=NULL ){
                    //load data from database
                    $db_row=$this->Citation_model->select_single($id);
                    
                    if (!$db_row){
                        show_error('INVALID ID');
                    }

					if ($db_row['authors']){
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
     * TODO:remove
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
        if (isset($options['pub_year']) && !is_numeric($options['pub_year']))
        {
            $options['pub_year']=NULL;
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

        //get current user object
        $user=$this->ion_auth->current_user();
        $user_id=NULL;

        if($user)
        {
            $user_id=$user->id;
        }


        try
        {
            $uploaded_file=NULL;

            if(!empty($_FILES['attachment']['name'])) {
                //process attachment
                $uploaded_file = $this->upload_attachment($id);

                //upload failed
                if (!$uploaded_file){
                    throw new Exception ($this->upload->display_errors());
                }

                if (isset($uploaded_file['file_name'])){
                    $options['attachment']=$uploaded_file['file_name'];         
                }
            }    

            $options['changed_by']=$user_id;

            //insert new citation
            if ($id==NULL)
            {
                $options['created_by']=$user_id;                

                //insert record, returns the new id
                $id=$this->Citation_model->insert($options);

                $db_result=FALSE;

                if($id!==FALSE)
                {
                    $db_result=TRUE;
                    $this->db_logger->write_log('new',$options['title'],'citations');
                }
            }
            else //update citation
            {
                //remove existing citation file
                if ($uploaded_file){                    
                    $this->Citation_model->delete_attachment($id);
                }
                
                $db_result=$this->Citation_model->update($id,$options);
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
            $this->form_validation->set_error($e->getMessage());
            $db_result=FALSE;
        }

        if ($db_result!==FALSE)
        {
            //update successful
            $this->session->set_flashdata('message', t('form_update_success'). ' '.anchor('admin/citations/edit/'.$id,'click here to edit record #'.$id));

            //redirect back to the list
            redirect("admin/citations","refresh");
        }
    }
    

    // upload citation attachment
    function upload_attachment()
    {
        $this->load->library('upload');
        
        if(!is_dir($this->citations_storage_path)){
            mkdir($this->citations_storage_path,0777);
        }
        
        $this->upload->initialize(array(
            'overwrite' => TRUE,
            'upload_path' => $this->citations_storage_path,
            'allowed_types' => $this->allowed_attachment_file_types,
            'encrypt_name' => FALSE,
            'file_ext_tolower'=>TRUE,
            'remove_spaces'=>TRUE
        ));

        if ($this->upload->do_upload('attachment')){
            $upload_data=$this->upload->data();

            $filename=$this->security->sanitize_filename(strtolower($upload_data['file_name']));
            rename($upload_data['full_path'],$this->citations_storage_path.'/'.$filename);

            return array(
                'file_name'=>$filename,
                'file_path'=>$this->citations_storage_path.'/'.$filename
            );
        }

        return false;
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
		$this->form_validation->set_rules('citation_string', t('citation_string'), 'xss_clean|trim|required');
		$string=$this->input->post("citation_string");

        //get current user object
        $user=$this->ion_auth->current_user();
        $user_id=NULL;

        if($user)
        {
            $user_id=$user->id;
        }

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
                        $options['created_by']=$user_id;

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

    /**
     * 
     * Export all citations
     */
	function export($format=null)
	{
        if($format=='json'){            
            $filename='citations-'.date("m-d-y-his").'.json';
            header( 'Content-Type: application/json');
            header('Content-Encoding: UTF-8');		
            header( 'Content-Disposition: attachment;filename='.$filename);
            $fp = fopen('php://output', 'w');
            $this->Citation_model->export_to_json_file($fp);
            fclose($fp);
            return;
        }
        else if($format=='csv'){
            $this->Citation_model->export('csv');
            return;
        }
        
        $content= $this->load->view('citations/export_options',null,true);        
        $this->template->write('title', t('export_citations'),true);
        $this->template->write('content', $content,true);
        $this->template->render();
	}


    /**
     * Find duplicate or similar citations
     */
    function find_duplicates()
    {
        $keywords=array();

        //key variables to search on
        $search_keys=explode(",","title,author_fname,author_lname,doi");

        foreach($search_keys as $key)
        {
            if (isset($_POST[$key]))
            {
                $value=$_POST[$key];

                if (is_array($value))
                {
                    $keywords[]=implode(" ",$value);
                }
                else
                {
                    $keywords[]=$value;
                }

            }
        }

        $keywords= implode(" ",$keywords);

        $citations_found=$this->Citation_model->search_duplicates($keywords);

        if (!$citations_found)
        {
            echo "No matching citations were found";
            exit;
        }

        echo $this->load->view('citations/duplicates',array('citations'=>$citations_found),TRUE);
    }



    /**
     *
     * Search survey to be attached to a citation
     *
     * TODO://move to citation model
     **/
    public function find_surveys()
    {
        $keywords=$this->input->post("q",true);

        $exclude_surveys=$this->input->post('exclude',true);

        if (!$keywords){
            show_error('NO-KEYWORDS-PROVIDED');
            return FALSE;
        }

        $study_fulltext_index='keywords';//'idno,title,nation,abbreviation,authoring_entity,keywords';
        $where=array();

        $tmp_surveys=explode(",",$exclude_surveys);
        $exclude_surveys=array();

        foreach($tmp_surveys as $sid)
        {
            if (is_numeric($sid))
            {
                $exclude_surveys[]=$sid;
            }
        }

        if (count($exclude_surveys)>0)
        {
            $where[]=sprintf('id NOT in (%s)',implode(",",$exclude_surveys));
        }

        if ($this->db->dbdriver=='mysql' || $this->db->dbdriver=='mysqli')
        {
            $score=sprintf("match(%s) against(%s in boolean mode) ",$study_fulltext_index, $this->db->escape($keywords));
            $where[]=$score;
            $sql=sprintf("SELECT *,%s as score FROM surveys ",$score, $study_fulltext_index, $this->db->escape($keywords));
            $sql.=" WHERE ". implode(" AND ",$where);
            $sql.=" ORDER BY score DESC LIMIT 100";
        }
        else if($this->db->dbdriver=='sqlsrv')
        {
            $sql=sprintf("SELECT
					surveys.id,title,nation,year_start,year_end,
					KEY_TBL.*
					FROM surveys
					INNER JOIN
						freetexttable (surveys,(ft_keywords),%s,100) as KEY_TBL
					ON surveys.id=KEY_TBL.[KEY]
					WHERE KEY_TBL.RANK >=10
					ORDER BY KEY_TBL.RANK DESC;",$this->db->escape($keywords));
        }
        //echo $sql;

        $result=$this->db->query($sql);

        if(!$result)
        {
            $error=$this->db->error();
            show_error('DB:Exception: '.$error['message']);
        }

        $result=$result->result_array();
        echo $this->load->view('citations/find_surveys',array('surveys'=>$result),TRUE);
    }


    /**
     * returns a formatted list of surveys
     * input - sid[] - list of survey id
     **/
    function get_formatted_surveys()
    {
        $sid_arr=$this->input->get_post("sid");
        $sid_arr=explode(",",$sid_arr);

        $surveys=FALSE;

        foreach($sid_arr as $sid)
        {
            if (is_numeric($sid))
            {
                $surveys[]=$sid;
            }
        }

        $survey_rows=$this->Citation_model->get_surveys($surveys);
        echo $this->load->view('citations/selected_surveys', array('selected_surveys'=>$survey_rows),TRUE);
    }

    //validate a citation URL field
    function validate_url_single($id)
    {
        echo "<pre>";

        $status=$this->Citation_model->update_url_status_single($id);

        var_dump($status);
        exit;
        $url=$this->input->get("url");

        $this->Citation_model->get_url_header($url);exit;

        echo $url;
        echo "<HR>";
        $url=str_replace(" ","%20",$url);
        $url="http://www-wds.worldbank.org/external/default/main?pagePK=64193027&piPK=64187937&theSitePK=523679&menuPK=64187510&searchMenuPK=64187283&siteName=WDS&entityID=000009265_3980420172958";
        echo $url;
        echo '<pre>';
        print_r(get_headers($url,1));
        exit;
    }



    //validate a url
    function validate_url()
    {
        $url=$this->input->get("url");

        if (!$url)
        {
            echo json_encode(array(
                'status'=>0,
                'message'=>"URL_NOT_SET"
            ));
            exit;
        }

        $status_code=$this->Citation_model->validate_url($url);

        echo json_encode(array(
            'status'=>$status_code,
            'message'=>"URL_NOT_SET"
        ));
        exit;


        echo $url;
        echo "<HR>";
        $url=str_replace(" ","%20",$url);
        echo $url;
        /*
                $url_parts=explode("//",$url);

                $url_parts[1]=rawurlencode($url_parts[1]);
                $url=implode("//",$url_parts);

                print_r($url_parts);
        */
        //$url=rawurlencode($url);
        //$url="http://www.sctimst.ac.in/About SCTIMST/Organisation/AMCHSS../Publications/Working Paper Series/resources/wp_8.pdf";


        echo $url;
        echo '<pre>';
        print_r(get_headers($url,1));
        exit;
        $headers=$this->Citation_model->curl_headers($url);
        var_dump($headers);
    }




    /*
     * Validate URL links for citations
     * */
    function batch_validate_url()
    {
        $id_str=$this->input->get("id");
        $confirmation=$this->input->get("confirm");

        if (!$id_str){
            show_error('INVALID_ID');
        }

        $id_array=explode(",",$id_str);

        foreach($id_array as $id)
        {
            if (!is_numeric($id))
            {
                var_dump($id);
                show_error("INVALID_VALUE");
            }
        }

        if (!$confirmation)
        {
            echo 'Please wait: URL Validation is running and may take a while to finish.';
            echo js_redirect('admin/citations/batch_validate_url/?confirm=true&id='.$id_str);
            exit;
        }

        //fetch each citation and process the URL link
        foreach($id_array as $id)
        {
            $status=$this->Citation_model->update_url_status_single($id);
            echo 'ID: '.$id.'<BR>';
            echo 'URL: '.$status['url'].'<BR>';
            echo 'Status code: '.$status['status'].'<BR>';
            echo "<HR>";
        }
    }

    //batch validation of publication links - to update the whole database
    function validate_citation_links()
    {
        $data['citation_url_stats']=$this->Citation_model->get_citations_url_stats();

        $content=$this->load->view('citations/batch_validate_links', $data,true);

        //page title
        $this->template->write('title', t('Validate citation links'),true);

        //pass data to the site's template
        $this->template->write('content', $content,true);

        //render final output
        $this->template->render();
    }

    function validate_next_link()
    {
        /*
         * fetch n items from database
         * update them
         * return status on whole db as json
         * */

        //validate and return number of records processed
        $result=$this->Citation_model->batch_update_citation_links($verbose=false,$limit=10,$loop=FALSE,$status_filter=NULL);

        //get stats group by url status
        $stats=$this->Citation_model->get_citations_url_stats();
        /*
        foreach($stats as $row)
        {
            $data['stats'][]=array(
                $row['url_status']=>$row['total']
            );
        }*/

        $data['stats']=$stats;
        $data['records_processed']=$result;

        echo json_encode($data);
    }

    // Delete citation attachment
    function delete_attachment($citationid=NULL)
    {
        if(!is_numeric($citationid)){
            show_404();
        }

        $result=$this->Citation_model->delete_attachment($citationid);
        header("Content-type: application/json; charset=utf-8");
        echo json_encode($result);
    }

    //download attachment for citation by citation id
    function download_attachment($citationid=NULL)
    {
        if(!is_numeric($citationid)){
            show_404();
        }

        $citation=$this->Citation_model->get_attachment($citationid);

        if (!$citation){
            show_404();
        }

        $attachment_filename=$citation['attachment'];
        $attachment_path=$this->config->item("citations_storage_path");
        $file_path=unix_path($attachment_path.'/'.$attachment_filename);

        if (file_exists($file_path)){
            $this->load->helper('download');
            return force_download($attachment_filename, $file_path);
        }
        else{
            show_error('FILE_NOT_FOUND: '. $file_path);
        }
    }


    /**
     * 
     * for NADA 4.5 and later, run this to update authors, editors, translators
     * fields in citations table to use JSON instead of plain text
     * 
     * refresh authors, editor, translators info 
     */
    function refresh_citations_authors($offset=0,$limit=0,$verbose=0)
    {
        echo "<pre>";
        $count=$this->Citation_model->refresh_db_author_fields($offset,$limit,$verbose);
        echo $count. " updated";

    }

    function to_json()
    {
        $citations=$this->Citation_model->get_all_citations();

        echo json_encode($citations);
    }


}
/* End of file citations.php */
/* Location: ./controllers/admin/citations.php */
