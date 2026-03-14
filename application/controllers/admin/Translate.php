<?php
class Translate extends MY_Controller {
 
 	//base/template language
	var $base_lang='english';
	
 
    public function __construct()
    {
        parent::__construct();
		$this->template->set_template('admin5');

		$this->lang->load("general");
		$this->lang->load("translate");
		$this->load->library('translator');
		$this->acl_manager->has_access_or_die('translate', 'edit');
		//$this->output->enable_profiler(TRUE);
    }
 
 	function index()
	{
		$data['title']=t('translate');
		$data['error']=$this->session->flashdata('translate_error');
		$data['success']=$this->session->flashdata('translate_success');
		$data['languages']=$this->translator->get_languages_array();

		// Build completeness stats and source flags for the language list
		$data['completeness']=array();
		$data['lang_info']=array();
		foreach($data['languages'] as $lang)
		{
			$data['completeness'][$lang]=$this->translator->get_language_completeness($lang);
			$data['lang_info'][$lang]=$this->translator->get_language_info($lang);
		}

		$content=$this->load->view("translator/index_admin",$data,true);
		
		$this->template->write('title', $data['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();	
	}
 
	function edit($language=NULL,$translation_file=NULL)
	{	
		if (!$language)
		{
			show_error("NO_LANGUAGE_SELECTED");
		}

		//check if language exists
		if (!$this->translator->language_exists($language))
		{
			show_error('INVALID_LANGUAGE');
		}

		$data['save_status']=NULL;
		
		if ($this->input->post('save'))
		{
			//save form
			$data['save_status']=$this->_save($language,$translation_file);
		}

		// Determine whether a userdata copy of this file already exists
		$user_data=$this->config->item('userdata_path');
		$user_file=(!empty($user_data) && $translation_file)
			? $user_data.'/language/'.$language.'/'.$translation_file.'_lang.php'
			: '';
		$data['has_userdata_copy'] = !empty($user_file) && file_exists($user_file);
		
		$data['edit_file_fullpath']=$this->translator->translation_file_path($language,$translation_file, true);		
		$data['title']=t('translate');
		$data['active_lang_file']=$translation_file;
		$data['languages']=$this->translator->get_languages_array();
		$data['language']=$language;
		$data['rtl_languages']=array('arabic');
		$data['files']=$this->translator->get_language_files_array(APPPATH.'/language/english');

		// Build per-file completeness status for the sidebar
		$data['file_status']=array();
		if (is_array($data['files']))
		{
			$user_data_cfg=$this->config->item('userdata_path');
			foreach ($data['files'] as $f)
			{
				$base=str_replace('_lang.php','',$f);
				$en_keys=$this->translator->get_translations_array(APPPATH.'language/english/'.$f);
				if (!is_array($en_keys) || empty($en_keys))
				{
					$data['file_status'][$base]=true; // nothing to translate
					continue;
				}
				// Try userdata first, then application/language
				$lang_keys=null;
				if (!empty($user_data_cfg))
				{
					$lang_keys=$this->translator->get_translations_array($user_data_cfg.'/language/'.$language.'/'.$f);
				}
				if (!is_array($lang_keys))
				{
					$lang_keys=$this->translator->get_translations_array(APPPATH.'language/'.$language.'/'.$f);
				}
				if (!is_array($lang_keys))
				{
					$data['file_status'][$base]=false; // file missing entirely
					continue;
				}
				$missing=array_diff_key($en_keys,$lang_keys);
				$data['file_status'][$base]=empty($missing);
			}
		}
		
		//check if base translation file exists
		$data['template_file']=$this->translator->load($this->base_lang.'/'.$translation_file);				
		$data['edit_file']=$this->lang->load($translation_file, $language, true);
		
		if (!is_array($data['edit_file']))
		{
			$data['edit_file']=array();
		}

		$content=$this->load->view("translator/edit",$data,true);
		
		//set page options and render output
		$this->template->write('title', $data['title'],true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}
	
	
	function _save($language,$translation_file)
	{
		if (!$this->input->post('save'))
		{
			return FALSE;
		}

		//save the file
		$output_file=$this->translator->translation_file_path($language,$translation_file,true);

		$data['language']=$language;
		$data['template_file']=$this->translator->load($this->base_lang.'/'.$translation_file);
		$data['fill_missing']=false;
		$data['language_file']=$translation_file;
		
		$post_data=array();

		//reload data from POST for language
		foreach($data['template_file'] as $key=>$value)
		{
			if ($this->input->post(nada_hash($key)))
			{
				$post_data[$key]=$this->input->post(nada_hash($key));
			}
		}				
		
		$data['edit_file']=$post_data;
		$output =$this->load->view('translator/preview',$data,TRUE);
		
		//make a backup copy of the existing file
		@copy($output_file,$output_file.'.bak');
		
		//save file
		$file_contents="<?php \n"; 
		$file_contents.=$output;

		//create language folder if not exists
		if(!file_exists(dirname($output_file))){
			mkdir(dirname($output_file), 0755, true);
		}
		
		$result=@file_put_contents($output_file, $file_contents);
		
		if (!$result)
		{
			return array(
					'type'=>'error',
					'msg'=>'Could not save file. '.$output_file,
					);
		}
		else
		{
			return array(
				'type'=>'success',
				'msg'=>'File has been saved. '.$output_file,
				);

		}	
	
	}
	
	function change_lang()
	{
		$lang=$this->input->get_post("lang");
		$file=$this->input->get_post("file");
		
		if ($lang)
		{
			if ($file)
			{
				redirect("admin/translate/edit/".$lang."/".$file);exit;
			}
			redirect("admin/translate/edit/".$lang);exit;
		}
	}
	
	function download($language)
	{
		if (!$this->translator->language_exists($language))
		{
			show_error('INVALID_LANGUAGE');
		}

		$this->translator->export($language);
	}


	/**
	 * POST handler — create a new language folder in userdata/language/
	 */
	function create_lang()
	{
		$lang=strtolower(trim($this->input->post('language_name')));
		$result=$this->translator->create_language($lang);

		if ($result['type']==='success')
		{
			$this->session->set_flashdata('translate_success', $result['msg']);
			redirect('admin/translate/edit/'.$lang); exit;
		}
		else
		{
			$this->session->set_flashdata('translate_error', $result['msg']);
			redirect('admin/translate'); exit;
		}
	}


	/**
	 * GET  – show import form
	 * POST – handle zip upload and import
	 */
	function import_lang()
	{
		$data['title']=t('translate');
		$data['import_error']=null;
		$data['import_skipped']=array();

		if ($this->input->post('import_submit'))
		{
			$upload_error = isset($_FILES['lang_zip']['error']) ? $_FILES['lang_zip']['error'] : UPLOAD_ERR_NO_FILE;
			$tmp_name     = isset($_FILES['lang_zip']['tmp_name']) ? $_FILES['lang_zip']['tmp_name'] : '';

			if ($upload_error !== UPLOAD_ERR_OK || empty($tmp_name))
			{
				$data['import_error']='Upload failed. Please select a valid zip file.';
			}
			else
			{
				$ext=strtolower(pathinfo($_FILES['lang_zip']['name'], PATHINFO_EXTENSION));
				if ($ext !== 'zip')
				{
					$data['import_error']='Only .zip files are accepted.';
				}
				else
				{
					$result=$this->translator->import_zip($tmp_name);

					if ($result['type']==='success')
					{
						$flash=$result['msg'];
						if (!empty($result['skipped']))
						{
							$flash.=' Skipped ('.count($result['skipped']).'): '.implode('; ', $result['skipped']);
						}
						$this->session->set_flashdata('translate_success', $flash);
						redirect('admin/translate/edit/'.$result['language']); exit;
					}
					else
					{
						$data['import_error']=$result['msg'];
						$data['import_skipped']=$result['skipped'];
					}
				}
			}
		}

		$content=$this->load->view('translator/import', $data, true);
		$this->template->write('title', $data['title'], true);
		$this->template->write('content', $content, true);
		$this->template->render();
	}

}
/* End of file translate.php */
/* Location: ./controllers/admin/translate.php */