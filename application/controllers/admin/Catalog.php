<?php
/**
 * Catalog Maintenance Controller
 *
 * handles all Catalog Maintenance pages
 *
 */
class Catalog extends MY_Controller {

  	public function __construct()
	{
      	parent::__construct();
     	$this->load->model('Catalog_model');
		$this->load->model('Licensed_model');
		$this->load->model('Form_model');
		$this->load->model('Data_classification_model');
		$this->load->model('Repository_model');
		$this->load->model('Citation_model');
		$this->load->model('Search_helper_model');
		$this->load->library('pagination');
		$this->load->helper('querystring_helper','url');
		$this->load->helper('form');
		//$this->load->helper("catalog");
		$this->template->set_template('admin');
		$this->load->library("Dataset_manager");

		//load language file
		$this->lang->load('general');
		$this->lang->load('catalog_search');
		$this->lang->load('catalog_admin');
		$this->lang->load('permissions');
		$this->lang->load('resource_manager');

		//$this->output->enable_profiler(TRUE);
		//$this->acl->clear_active_repo();
	}

	/**
	 * Whether repositoryid is allowed for this user in admin catalog (scope check only).
	 */
	private function _repositoryid_allowed_by_catalog_scope($repositoryid)
	{
		$rid = strtolower(trim((string) $repositoryid));
		$scope = $this->acl_manager->get_admin_catalog_repository_scope();
		if ($scope === false) {
			return false;
		}
		if ($scope === null) {
			return true;
		}
		return in_array($rid, array_map('strtolower', $scope), true);
	}


	/**
	 * True if the user may import a DDI into at least one collection (scope + study.create on that repo).
	 * Used so the upload page can load when the "active" repo is not a valid target but another collection is.
	 */
	private function _user_has_any_ddi_upload_target($user = null)
	{
		if (empty($user)) {
			$user = $this->ion_auth->current_user();
		}
		if (!$user) {
			return false;
		}
		$scope = $this->acl_manager->get_admin_catalog_repository_scope($user);
		if ($scope === false) {
			return false;
		}

		if ($this->_repositoryid_allowed_by_catalog_scope('central') && $this->_user_may_create_study_in_repo_for_user($user, 'central')) {
			return true;
		}

		$repos = $this->Repository_model->select_all(null, false);
		foreach ($repos as $row) {
			$rid = isset($row['repositoryid']) ? $row['repositoryid'] : '';
			if ($rid === '') {
				continue;
			}
			if (!$this->_repositoryid_allowed_by_catalog_scope($rid)) {
				continue;
			}
			if ($this->_user_may_create_study_in_repo_for_user($user, $rid)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param object $user
	 * @param string $repositoryid
	 * @return bool
	 */
	private function _user_may_create_study_in_repo_for_user($user, $repositoryid)
	{
		try {
			$this->acl_manager->has_access('study', 'create', $user, $repositoryid);

			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Default collection hint for DDI upload Vue (optional cookie). Does not use $this->active_repo.
	 *
	 * @return string repositoryid or ''
	 */
	private function _default_repositoryid_for_ddi_upload_ui()
	{
		$uid = $this->Repository_model->user_active_repo();
		if ($uid === false || $uid === null) {
			return '';
		}
		$rid = $this->Repository_model->get_repositoryid_by_uid((int) $uid);
		if ($rid === false || $rid === null || $rid === '') {
			return '';
		}
		if (! $this->_repositoryid_allowed_by_catalog_scope($rid)) {
			return '';
		}
		return $rid;
	}

	/**
	 * Numeric repositories.id for featured_surveys.repoid from surveys.repositoryid.
	 */
	private function _owner_repo_numeric_id_for_survey(array $survey_row)
	{
		if (empty($survey_row['repositoryid'])) {
			return 0;
		}
		if (strtolower((string) $survey_row['repositoryid']) === 'central') {
			return 0;
		}
		$r = $this->Repository_model->get_repository_by_repositoryid($survey_row['repositoryid']);
		return !empty($r['id']) ? (int) $r['id'] : 0;
	}


	/**
	 * Default page
	 *
	 */
	function index()
	{
		$this->acl_manager->require_catalog_access();

		$this->_render_admin_catalog_vue_shell();
	}


	/**
	 * Shared data for admin catalog Vue (list + batch tools).
	 *
	 * @return array
	 */
	private function _admin_catalog_vue_view_data()
	{
		$this->load->helper('vite_helper');
		$pu    = parse_url(site_url('admin/catalog'));
		$rpath = isset($pu['path']) ? $pu['path'] : '/admin/catalog';

		return array(
			'api_base_url'      => site_url('api/admin/catalog/'),
			'datasets_api_url'  => site_url('api/datasets/'),
			'site_url'          => site_url(),
			'base_url'          => base_url(),
			'csrf_token'        => $this->security->get_csrf_hash(),
			'csrf_token_name'   => $this->security->get_csrf_token_name(),
			'assets_base'       => base_url('frontend/dist/'),
			'translations'      => $this->lang->language,
			'router_path_base'  => $rpath,
		);
	}


	/**
	 * Render Vue shell for admin catalog maintenance.
	 *
	 * @return void
	 */
	private function _render_admin_catalog_vue_shell()
	{
		$catalog_view_data = $this->_admin_catalog_vue_view_data();

		$page = array(
			'title'           => t('catalog_maintenance'),
			'content'         => $this->load->view('admin/catalog/index', $catalog_view_data, true),
			'hide_breadcrumb' => true,
			'theme_folder'    => 'adminvue',
		);

		$this->load->view('layouts/admin_vue', $page);
	}


	/**
	 * Study-edit shell CSS for layouts/admin_vue (tabs + Bootstrap-compat utilities).
	 *
	 * @return array With keys _styles and _scripts for admin_vue layout.
	 */
	private function _admin_catalog_edit_study_shell_assets()
	{
		$b = base_url();
		$css_path = FCPATH . 'themes/adminvue/catalog-study-edit.css';
		$v = file_exists($css_path) ? ('?v=' . (string) filemtime($css_path)) : '';

		$styles = '<link rel="stylesheet" href="' . $b . 'themes/adminvue/catalog-study-edit.css' . $v . '">' . "\n";

		// jQuery / Bootstrap / Font Awesome removed: study tabs use Vue; 401 redirect is handled in layouts/admin_vue.php (fetch).

		return array(
			'_styles'  => $styles,
			'_scripts' => '',
		);
	}


	/**
	 * Vue Router page: bulk DDI import from server folder.
	 *
	 * @return void
	 */
	function batch_import_page()
	{
		$this->acl_manager->require_catalog_access();
		$this->_render_admin_catalog_vue_shell();
	}


	/**
	 * Vue Router page: batch refresh DDI.
	 *
	 * @return void
	 */
	function batch_refresh_page()
	{
		$this->acl_manager->require_catalog_access();
		$this->_render_admin_catalog_vue_shell();
	}


	/**
	 * Vue Router page: batch generate DDI files.
	 *
	 * @return void
	 */
	function batch_generate_page()
	{
		$this->acl_manager->require_catalog_access();
		$this->_render_admin_catalog_vue_shell();
	}


	//return temp upload folder path
	private function get_temp_upload_folder()
	{
		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");

		//if not fixed path, use a relative path
		if (!file_exists($catalog_root) )
		{
			$catalog_root=FCPATH.$catalog_root;
		}

		//create .htaccess if not already exists
		//@file_put_contents($catalog_root.'/.htaccess','deny from all');
		//@chmod($catalog_root.'/.htaccess',0444);

		$temp_upload_folder=$catalog_root.'/tmp';

		if (!file_exists($temp_upload_folder))
		{
			@mkdir($temp_upload_folder);
		}

		if (!file_exists($temp_upload_folder))
		{
			show_error('DATAFILES-TEMP-FOLDER-NOT-SET');
		}

		return $temp_upload_folder;
	}


	function upload()
	{
		$this->add_study();
	}

	/**
	 * Upload form for DDI (xml) file
	 *
	 * @return void
	 **/
	function add_study()
	{
		$this->acl_manager->require_catalog_access();

		//user has permissions on the repo
		//$this->acl->user_has_repository_access($this->active_repo->id);
		$this->template->set_template('admin');

		// Show Vue 3 upload UI (GET); POST is unchanged multipart handling below.
		if (!$this->input->post('submit')) {
			if (!$this->_user_has_any_ddi_upload_target()) {
				$this->acl_manager->show_access_denied('feature');
			}
			$this->load->helper('vite_helper');
			$flash_error = $this->session->flashdata('error');

			$ddi_upload_view_data = array(
				'api_base_url' => site_url('api/admin/catalog/'),
				'site_url' => site_url(),
				'base_url' => base_url(),
				'csrf_token' => $this->security->get_csrf_hash(),
				'csrf_token_name' => $this->security->get_csrf_token_name(),
				'assets_base' => base_url('frontend/dist/'),
				'translations' => $this->lang->language,
				'default_repositoryid' => $this->_default_repositoryid_for_ddi_upload_ui(),
				'upload_action_url' => site_url('admin/catalog/add_study'),
				'create_study_url' => site_url('admin/catalog/create'),
				'catalog_admin_url' => site_url('admin/catalog'),
				'catalog_edit_base' => site_url('admin/catalog/edit/'),
				'collections_admin_url' => site_url('admin/collections'),
				'max_upload_mb' => min((int) ini_get('upload_max_filesize'), (int) ini_get('post_max_size')),
				'flash_error' => $flash_error ? $flash_error : null,
			);

			$page = array(
				'title' => t('admin_add_study_title'),
				'content' => $this->load->view('admin/catalog/ddi_upload', $ddi_upload_view_data, true),
				'hide_breadcrumb' => true,
				'theme_folder' => 'adminvue',
			);

			$this->load->view('layouts/admin_vue', $page);
			return;
		}

		$overwrite=$this->input->post("overwrite");
		$repositoryid=$this->input->post("repositoryid");

		$this->acl_manager->has_access_or_die('study', 'create', null, $repositoryid);

		if (!$this->_repositoryid_allowed_by_catalog_scope($repositoryid)) {
			$this->session->set_flashdata('error', 'Access denied');
			redirect('admin/catalog/add_study','refresh');
			return;
		}

		if($overwrite=='yes'){
			$overwrite=TRUE;
		}
		else{
			$overwrite=FALSE;
		}

		//process form

		$temp_upload_folder=$this->get_temp_upload_folder();

		//upload class configurations for DDI
		$config['upload_path'] 	 = $temp_upload_folder;
		$config['overwrite'] 	 = FALSE;
		$config['encrypt_name']	 = TRUE;
		$config['allowed_types'] = 'xml';

		$this->load->library('upload', $config);

		//process uploaded ddi file
		$ddi_upload_result=$this->upload->do_upload();

		$uploaded_ddi_path=NULL;

		//ddi upload failed
		if (!$ddi_upload_result){
			$error = $this->upload->display_errors();
			$this->db_logger->write_log('ddi-upload',$error,'catalog');
			$this->session->set_flashdata('error', $error);
			redirect('admin/catalog/add_study','refresh');
		}
		else //successful upload
		{
			//get uploaded file information
			$uploaded_ddi_path = $this->upload->data();
			$uploaded_ddi_path=$uploaded_ddi_path['full_path'];
			$this->db_logger->write_log('ddi-upload','success','catalog');
		}

		$this->load->model("Data_file_model");
		$this->load->library('DDI2_import');

		$user=$this->ion_auth->current_user();

		$ddi_path=$uploaded_ddi_path;
		$params=array(
			'file_type'=>'survey',
			'file_path'=>$ddi_path,
			'user_id'=>$user->id,
			'repositoryid'=>$repositoryid,
			'overwrite'=>$overwrite
		);

		try{
			//import ddi
			$result=$this->ddi2_import->import($params);

			//import rdf
			$rdf_result=$this->upload_rdf_file($result['sid']);

			$this->events->emit('db.after.update', 'surveys', $result['sid'],'refresh');
			$this->session->set_flashdata('success', $result);
			redirect('admin/catalog/edit/'.$result['sid'],'refresh');return;
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);

			$error_str='Validation Error<br/><pre class="error-pre">'.print_r($e->GetValidationErrors(),true).'</pre>';			
			$this->session->set_flashdata('error', $error_str);
			redirect('admin/catalog/add_study','refresh');return;
		}
		catch(Exception $e){
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('admin/catalog/add_study','refresh');return;
		}
	}


	private function upload_rdf_file($sid)
	{
		$this->load->library('catalog_admin');

		//upload class configurations for RDF
		$config['upload_path'] = $this->get_temp_upload_folder();
		$config['overwrite'] = FALSE;
		$config['encrypt_name']=TRUE;
		$config['allowed_types'] = 'rdf';

		$this->upload->initialize($config);

		//process uploaded rdf file
		$rdf_upload_result=$this->upload->do_upload('rdf');

		$uploaded_rdf_path='';

		if ($rdf_upload_result)
		{
			$uploaded_rdf_path = $this->upload->data();
			$uploaded_rdf_path=$uploaded_rdf_path['full_path'];
		}

		if ($uploaded_rdf_path!="")
		{
			//import rdf
			$this->catalog_admin->import_rdf($sid,$uploaded_rdf_path);

			//delete rdf
			@unlink($uploaded_rdf_path);
		}

		return true;
	}


	/**
	 *
	 * Sanitize file name
	 */
	private function sanitize_filename($name)
	{
		return preg_replace('/[^a-zA-Z0-9-_\.]/','-',$name);
	}

	/**
	* Imports an uploaded DDI file or batch import
	*
	*/
	private function __replace_ddi($sid,$new_ddi_file)
	{
		$this->load->model("Survey_alias_model");
		$this->load->model("Dataset_model");
		$this->load->model("Data_file_model");
		$this->load->library('Dataset_manager');

		//get survey info
		$survey=$this->Dataset_model->get_row($sid);
		$user=$this->ion_auth->current_user();

		if (!$survey){
			show_error("SURVEY_NOT_FOUND");
		}

		//get ddi path
		$survey_ddi_path=$this->Catalog_model->get_survey_ddi_path($sid);

		$parser_params=array(
            'file_type'=>'survey',
            'file_path'=>$new_ddi_file
        );
        
		$this->load->library('Metadata_parser', $parser_params);
		
		 //parser to read metadata
		 $parser=$this->metadata_parser->get_reader();

		 $new_idno=$parser->get_id();

		 //sanitize ID to remove anything except a-Z1-9 characters
		 if ($new_idno!==$this->sanitize_filename($new_idno)){
			 throw new Exception(t('IDNO_INVALID_FORMAT').': '.$new_idno);
		 }
 
		 //check if the study already exists, find the sid		
		$new_ddi_sid=$this->dataset_manager->find_by_idno($new_idno);

		//check if uploaded study ID is used by another study in the catalog
		if(!empty($new_ddi_sid) && $new_ddi_sid!=$sid){			
			$error=t('replace_ddi_failed_duplicate_study_found'). ': '.anchor(site_url('admin/catalog/edit/'.$new_ddi_sid));
			$this->db_logger->write_log('ddi-replace-error',$error,'catalog');
			throw new Exception($error);
		}

		//copy
		$survey_folder_path=$this->Dataset_model->get_storage_fullpath($sid);
		$survey_target_ddi=unix_path($survey_folder_path.'/'.$new_idno.'.xml');

		if (!@rename($new_ddi_file,$survey_target_ddi)){
			throw new Exception("COPY_FAILED: ".$survey_target_ddi);
		}

		//update survey metadata to point to new file
		$survey_options=array(
			'metafile'=>$new_idno.'.xml'
		);
		
		$this->Dataset_model->update_options($sid,$survey_options);

		//if Survey ID has changed then add the OLD ID as alias
		if (!$this->Survey_alias_model->id_exists($new_idno)){
			$alias_options = array(
				'sid'  => $sid,
				'alternate_id' => $new_idno,
			);
			$this->Survey_alias_model->insert($alias_options);
		}
	
		//refresh metadata
		return redirect('admin/catalog/refresh/'.$sid,'refresh');
	}


	/**
	*
	* Refresh DDI Information in the database
	*
	* Note: Useful for updating study information in the database for existing DDIs
	**/
	function refresh($id=NULL)
	{
		$this->acl_manager->require_catalog_access();

		if (!is_numeric($id)){
			show_404();
		}

		$this->load->model("Dataset_model");
		$this->load->model("Data_file_model");
		$this->load->library('DDI2_import');


		$is_ajax=$this->input->get("ajax");

		//get survey ddi file path by id
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);

		if ($ddi_file===FALSE){
			if($is_ajax==FALSE){
				show_error('DDI_NOT_FOUND');
			}
			else{
				die (json_encode(array('error'=>'DDI_NOT_FOUND' )));
			}
		}
		
		$user=$this->ion_auth->current_user();
		$dataset=$this->Dataset_model->get_row($id);

		if (!$dataset) {
			if ($is_ajax) {
				die(json_encode(array('error' => 'NOT_FOUND')));
			}
			show_error('Survey was not found');
		}

		$this->acl_manager->has_access_or_die('study', 'edit', null, $dataset['repositoryid']);

		$params=array(
			'file_type'=>'survey',
			'file_path'=>$ddi_file,
			'user_id'=>$user->id,
			'repositoryid'=>$dataset['repositoryid'],
			'overwrite'=>'yes'
		);

		try{			
			$result=$this->ddi2_import->import($params,$id);

			//reset changed and created dates
			$update_options=array(
				'changed'=>$dataset['changed'],
				'created'=>$dataset['created'],
				'repositoryid'=>$dataset['repositoryid']
			);

			$this->Dataset_model->update_options($id,$update_options);
			$this->events->emit('db.after.update', 'surveys', $id,'refresh');

			if ($is_ajax){
				die (json_encode(array('success'=>'UPDATED: '.$id) ));
			}
	
			$this->session->set_flashdata('success', $result);			
			redirect('admin/catalog/edit/'.$id,'refresh');return;
		}
		catch(ValidationException $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage(),
				'errors'=>$e->GetValidationErrors()
			);

			$error_str='Validation Error<br/><pre class="error-pre">'.print_r($e->GetValidationErrors(),true).'</pre>';
			
			if ($is_ajax){
				die (json_encode(array('error'=>$error_str) ));
			}

			$this->session->set_flashdata('error', $error_str);
			redirect('admin/catalog/edit/'.$id,'refresh');return;
		}
		catch(Exception $e){
			if ($is_ajax){
				die (json_encode(array('error'=>$e->getMessage()) ));
			}
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('admin/catalog/edit/'.$id,'refresh');return;
		}
	}

	function delete($id)
	{
		$this->acl_manager->require_catalog_access();
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
				redirect('admin/catalog');
			}
		}
		else
		{
			$delete_arr[]=$id;
		}

		//test user has permission to delete study or not
		//$this->acl->user_has_study_access($id);		

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
				redirect('admin/catalog');
			}
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($delete_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);

				//delete if exists
				if ($survey)
				{
					$this->acl_manager->has_access_or_die('study', 'delete', null, $survey['repositoryid']);
					//delete survey and related data from other tables
					$this->Catalog_model->delete($item);
					$this->events->emit('db.after.delete', 'surveys', $item,'delete');

					//log deletion
					$survey_name=$survey['idno']. ' - '.$survey['title'].' - '. $survey['year_start'].' - '. $survey['nation'];
					$this->db_logger->write_log('study-deleted',$survey_name,'catalog',$item);
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
				//redirect($destination);
			}
			else
			{
				redirect('admin/catalog');
			}
		}
		else
		{
			$items=array(); //list of deleted items

			foreach($delete_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);

				//exists
				if ($survey)
				{
					$this->acl_manager->has_access_or_die('study', 'delete', null, $survey['repositoryid']);
					//log deletion
					$survey_name=$survey['idno']. ' - '.$survey['title'].' - '. $survey['year_start'].' - '. $survey['nation'];
					$items[]=$survey_name;
				}
			}

			//ask for confirmation
			$content=$this->load->view('resources/delete', array('deleted_items'=>$items),true);

			$this->template->write('content', $content,true);
	  		$this->template->render();
		}
	}

	/**
	*
	* Export External Resources as RDF
	**/
	function export_rdf($id=NULL)
	{
		$this->load->helper('download');
		$data=$this->Catalog_model->get_survey_rdf($id);
		force_download('rdf-'.$id.'.rdf', $data);
		//application/rdf+xml
	}


	



	/**
	* Returns survey DDI file
	* as .xml or .zip
	*
	*/
	function ddi($id=NULL)
	{
		if (!is_numeric($id))
		{
			show_404();
		}

		$format=$this->input->get("format");

		//required for getting ddi file path
		$this->load->model('Catalog_model');
		$this->load->helper('download');

		//get ddi file path from db
		$ddi_file=$this->Catalog_model->get_survey_ddi_path($id);

		if ($ddi_file===FALSE)
		{
			show_404();
		}

		if (file_exists($ddi_file))
		{
			if($format=='zip')
			{
				$this->load->library('zip');

				//zip file path
				$zip_file=$ddi_file.'.zip';

				//create zip if not created already
				if (!file_exists($zip_file))
				{
					$this->zip->read_file($ddi_file);
					$this->zip->archive($zip_file);
				}

				//download zip file
				if (file_exists($zip_file))
				{
					force_download2($zip_file);
					return;
				}
			}

			//download the xml file
			force_download2($ddi_file);
			return;
		}
		else
		{
			show_404();
		}
	}



	/**
	*
	* Replace a DDI
	*
	**/
	function replace_ddi($sid=NULL)
	{
		if (!is_numeric($sid)){
			show_error("ID_INVALID");
		}

		$this->acl_manager->require_catalog_access();

		$survey_gate = $this->Catalog_model->get_survey($sid);
		if (!$survey_gate) {
			show_error('Survey was not found');
		}
		$this->acl_manager->has_access_or_die('study', 'edit', null, $survey_gate['repositoryid']);


		if(!isset($_FILES['userfile'])){			
			$data['id']=$sid;
			$data['survey']=$this->Catalog_model->select_single($sid);

			$content=$this->load->view('catalog/replace_ddi',$data,TRUE);
			$this->template->write('content', $content,true);
			$this->template->render();
			return;
		}

		//no file uploaded?
		if ($_FILES['userfile']['size']==0){		
			$this->session->set_flashdata('error', "NO_FILE_UPLOADED");	
			redirect('admin/catalog/replace_ddi/'.$sid,'refresh');exit;
		}

		//catalog folder path
		$catalog_root=$this->config->item("catalog_root");

		if (!file_exists($catalog_root) ){
			show_error("CATALOG_ROOT_NOT_FOUND");
		}

		$tmp_path=unix_path($catalog_root.'/tmp');

		try
		{
			//upload the ddi
			$upload_result=$this->upload_ddi_file($key='userfile',$destination=$tmp_path);

			if(!$upload_result){				
				$error = $this->upload->display_errors();
				$this->db_logger->write_log('ddi-upload',$error,'catalog');
				throw new Exception($error);
			}

			$this->__replace_ddi($sid,$new_ddi_file=$upload_result['full_path']);
		}
		catch (Exception $e)
		{
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('admin/catalog/replace_ddi/'.$sid,'refresh');			
		}
	}



	/**
	*
	* Transfer Ownership of a study to another catalog
	*
	* @surveyid  number | string in case of multiple IDs seperated by comma
	**/
	function transfer($surveyid=NULL)
	{
		$this->acl_manager->require_catalog_access();

		if ($surveyid==NULL && !$this->input->post("sid")){
			show_error("PARAM_MISSING");
		}

		if ($this->input->post("sid")){
			$surveys_arr=$this->input->post("sid");
		}
		else{
			$surveys_arr=explode(",",$surveyid);
		}

		$surveys=array();

		//get survey info by id
		foreach($surveys_arr as $id)
		{
			if (is_numeric($id)){
				$survey_row=$this->Catalog_model->get_survey($id);
				if ($survey_row){
					$this->acl_manager->has_access_or_die('study', 'edit', null, $survey_row['repositoryid']);
					$surveys[$id]=$survey_row;
				}
			}
		}

		if (count($surveys) === 0) {
			show_error("PARAM_MISSING");
		}

		//postback?
		if ($this->input->post("submit"))
		{
			$repositoryid=$this->input->post("repositoryid");

			//validate repository
			if ($repositoryid=='central'){
				$exists=true;
			}
			else{
				$exists=$this->Catalog_model->repository_exists($repositoryid);
			}

			if (!$exists){
				$this->form_validation->set_error(t('error_no_collection_selected'));
			}
			else{
				foreach($surveys as $key=>$value){
					$this->acl_manager->has_access_or_die('study', 'edit', null, $value['repositoryid']);
					//transfer ownership
					$this->Catalog_model->transfer_ownership($repositoryid,$key);
				}
				$this->session->set_flashdata('message', t('msg_study_ownership_has_changed'));
				redirect('admin/catalog');
			}
		}

		$options=array(
			'repositories'=>$this->Repository_model->select_all(),
			'surveys'=>$surveys
		);

		$content=$this->load->view('catalog/transfer_ownership',$options,TRUE);
		$this->template->write('content', $content,true);
  		$this->template->render();
	}


	/**
	*
	* Unlink a study from a repository
	*
	**/
	function unlink($repositoryid,$surveyid)
	{
		if (!is_numeric($surveyid))
		{
			show_error("INVALID_ID");
		}

		$result=$this->Catalog_model->unlink_study($repositoryid,$surveyid);

		if ($result!==FALSE)
		{
			$content='Study link was removed successfully!';
		}
		else
		{
			$content='Error: Failed to remove study link';
		}

		$this->session->set_flashdata('message', $content);

		redirect('admin/catalog');
	}

	/**
	*
	* Attach admin/reviewer note to a study
	**/
	function attach_note($sid,$type)
	{
		//$this->output->enable_profiler(TRUE);
		if (!is_numeric($sid))
		{
			show_404();
		}

		$note=$this->input->post("note");

		$result=$this->Catalog_model->attach_note($sid,$note, $note_type=$type);

		if ($result)
		{
			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode(array('success'=>"updated")));
			return TRUE;
		}

			$this->output->set_content_type('application/json');
			$this->output->set_output(json_encode(array('error'=>"failed")));
	}


	/**
	*
	* Publish/Unpublish studies
	*
	* $id single numeric value or a comma seperated list of IDs
	* TODO: remove - has been replaced by udpate function
	**/
	function publish($id,$publish=1)
	{
		if (!in_array($publish,array(0,1))){
			$publish=1;
		}

		//array of id to be published
		$id_arr=array();

		//is ajax call
		$ajax=$this->input->get_post('ajax');

		if (!is_numeric($id)){
			$tmp_arr=explode(",",$id);
			foreach($tmp_arr as $key=>$value)
			{
				if (is_numeric($value)){
					$id_arr[]=$value;
				}
			}

			if (count($id_arr)==0)
			{
				//for ajax return JSON output
				if ($ajax!=''){
					echo json_encode(array('error'=>"invalid id was provided") );
					exit;
				}

				$this->session->set_flashdata('error', 'Invalid id was provided.');
				redirect('admin/catalog');
			}
		}
		else{
			$id_arr[]=$id;
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
				redirect('admin/catalog');
			}
		}
		else if ($this->input->post('submit')!='')
		{
			foreach($id_arr as $item)
			{
				//get survey info
				$survey=$this->Catalog_model->get_survey($item);
				$this->acl_manager->has_access_or_die('study', 'publish', null, $survey['repositoryid']);

				//if exists
				if ($survey){
					//publish/unpublish a study
					$result=$this->Catalog_model->publish_study($item,$publish);
					$this->events->emit('db.after.update', 'surveys', $item,'publish');
					//log
					$survey_name=$survey['idno']. ' - '.$survey['title'].' - '. $survey['year_start'].' - '. $survey['nation'];
					$this->db_logger->write_log('study-published',$survey_name,'catalog',$item);
				}
			}

			//raise db update event
			//$this->events->emit('db.after.update', 'surveys', $id_arr,'atomic');

			//for ajax calls, return output as JSON
			if ($ajax!=''){
				echo json_encode(array('success'=>"true") );
				exit;
			}

			//redirect page url
			$destination=$this->input->get_post('destination');
			redirect('admin/catalog');			
		}
		else
		{
			$items=array(); //list of items

			foreach($id_arr as $item){				
				$survey=$this->Catalog_model->get_survey($item);
				$this->acl_manager->has_access_or_die('study', 'publish', null, $survey['repositoryid']);

				if ($survey){
					$survey_name=$survey['idno']. ' - '.$survey['title'].' - '. $survey['year_start'].' - '. $survey['nation'];
					$items[]=$survey_name;
				}
			}

			//ask for confirmation
			$content=$this->load->view('catalog/publish_confirm', array('items'=>$items,'publish'=>$publish),true);

			$this->template->write('content', $content,true);
	  		$this->template->render();
		}
	}

	
	
	function create($type=null)
	{
		$this->acl_manager->require_catalog_access();

		$this->template->set_template('admin5');
		
		if(!$type){
			$type=$this->input->get("type");
		}

		if(!$type){
			show_error("Type not set");
		}

		$repositoryid = strtolower(trim((string) $this->input->get('repositoryid')));
		if ($repositoryid === '') {
			$repositoryid = 'central';
		}

		if (!$this->_repositoryid_allowed_by_catalog_scope($repositoryid)) {
			$this->acl_manager->show_access_denied('feature');
		}

		$this->acl_manager->has_access_or_die('study', 'create', null, $repositoryid);

		$user = $this->ion_auth->current_user();
		$created_by = ($user && isset($user->id)) ? (int) $user->id : 1;

		$sid=$this->Dataset_model->create_new(
			$idno=$this->Dataset_model->GUID(), 
			$type, 
			$repositoryid, 
			$title='untitled', 
			$created_by
		);

		redirect("admin/catalog/edit/".$sid);
		return;
		
		$template_path="application/metadata_editor_templates/{$type}_form_template.json";
		$schema_path="application/schemas/{$type}-schema.json";

		if(!file_exists($template_path)){
			show_error('Template not found::'. $template_path);
		}

		if(!file_exists($schema_path)){
			show_error('Schema not found::'. $schema_path);
		}
		
		$options['sid']=null;
		$options['survey']=array(
			'title'=>'New survey'
		);

		$options['type']=$type;
		$options['metadata_template']=file_get_contents($template_path);
		$options['metadata_schema']=file_get_contents($schema_path);
		$options['post_url']=site_url('api/datasets/create/'.$type);
		$options['metadata']=array();
		$options['metadata']['merge_options']='replace';
				
		//render
		$content=$this->load->view('metadata_editor/inline',$options, true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
		
	}

	/**
	 * Read a metadata editor template/schema file once per request.
	 *
	 * @param string $path
	 * @return string|false
	 */
	private function _read_study_metadata_file($path)
	{
		static $cache = array();
		$key = (string) $path;
		if (! isset($cache[$key])) {
			$cache[$key] = is_file($key) ? file_get_contents($key) : false;
		}
		return $cache[$key];
	}


	/**
	 * Build view data for the legacy Vue metadata editor (inline.php).
	 *
	 * @param int|string $id surveys.id
	 * @param bool       $lazy_metadata When true, metadata is fetched client-side (faster TTFB).
	 * @return array
	 */
	private function _prepare_metadata_editor_options($id, $lazy_metadata = false)
	{
		$survey = $this->dataset_manager->get_row($id);

		if (! $survey) {
			show_error('Survey was not found');
		}

		$this->acl_manager->has_access_or_die('study', 'edit', null, $survey['repositoryid']);

		if ($survey['type'] === 'geospatial') {
			show_error('GEOSPATIAL-TYPE-NOT-SUPPORTED');
		}

		$template_file = $survey['type'] . '_form_template.json';
		$template_path = null;
		$template_locations = array(
			'application/metadata_editor_templates/custom',
			'application/metadata_editor_templates',
		);

		foreach ($template_locations as $path) {
			if (file_exists($path . '/' . $template_file)) {
				$template_path = $path . '/' . $template_file;
				break;
			}
		}

		$schema_path = 'application/schemas/' . $survey['type'] . '-schema.json';

		if (! $template_path || ! file_exists($template_path)) {
			show_error('Template not found::' . $template_file);
		}

		if (! file_exists($schema_path)) {
			show_error('Schema not found::' . $schema_path);
		}

		$metadata = null;
		if (! $lazy_metadata) {
			$metadata = $this->dataset_manager->get_metadata($id);
			if (is_array($metadata)) {
				if ($survey['type'] === 'survey') {
					$coll_mode = array_data_get($metadata, 'study_desc.method.data_collection.coll_mode');
					if (! empty($coll_mode) && ! is_array($coll_mode)) {
						set_array_nested_value($metadata, 'study_desc.method.data_collection.coll_mode', (array) $coll_mode, '.');
					}
				}
				$metadata['merge_options'] = 'replace';
			}
		}

		$options = array(
			'sid'               => $id,
			'survey'            => $survey,
			'type'              => $survey['type'],
			'metadata'          => $metadata,
			'metadata_template' => $this->_read_study_metadata_file($template_path),
			'metadata_schema'   => $this->_read_study_metadata_file($schema_path),
			'post_url'          => site_url('api/datasets/update/' . $survey['type'] . '/' . $survey['idno']),
			'lazy_metadata_load'=> (bool) $lazy_metadata,
			'metadata_api_url'  => site_url('api/admin/catalog/' . (int) $id),
			'ajv_extra_schemas' => $this->_metadata_editor_ajv_schemas($survey['type']),
		);

		return $options;
	}


	/**
	 * Extra JSON schemas for AJV (read once per request, not in Vue mounted).
	 *
	 * @param string $study_type
	 * @return array<string, string>
	 */
	private function _metadata_editor_ajv_schemas($study_type)
	{
		$schemas = array(
			'provenance' => 'application/schemas/provenance-schema.json',
		);

		if ($study_type === 'survey') {
			$schemas['survey'] = 'application/schemas/survey-schema.json';
			$schemas['ddi'] = 'application/schemas/ddi-schema.json';
			$schemas['datafile'] = 'application/schemas/datafile-schema.json';
			$schemas['variable'] = 'application/schemas/variable-schema.json';
		} elseif ($study_type === 'image') {
			$schemas['image'] = 'application/schemas/image-schema.json';
			$schemas['iptc'] = 'application/schemas/iptc-pmd-schema.json';
			$schemas['iptc_shared'] = 'application/schemas/iptc-phovidmdshared-schema.json';
		}

		$out = array();
		foreach ($schemas as $key => $path) {
			$contents = $this->_read_study_metadata_file($path);
			if ($contents !== false) {
				$out[$key] = $contents;
			}
		}

		return $out;
	}


	/**
	 * Render metadata editor HTML (fragment or full document).
	 *
	 * @param array $options From _prepare_metadata_editor_options()
	 * @param bool  $embedded Embed in study edit tab (admin_vue shell)
	 * @return string
	 */
	private function _render_metadata_editor_html(array $options, $embedded = false)
	{
		$options['embedded'] = (bool) $embedded;
		return $this->load->view('metadata_editor/inline', $options, true);
	}


	function metadata_editor($id=null)
	{
		$options = $this->_prepare_metadata_editor_options($id, true);
		$this->template->set_template('admin5');
		$content = $this->_render_metadata_editor_html($options, false);
		$this->template->write('content', $content, true);
		$this->template->render();
	}

	function widgets($sid)
	{
		$this->load->model("Widget_model");
		$options['widget_storage_root']='files/embed/';
		$options['widgets']=$this->Widget_model->widgets_by_study($sid);
		
		$content= $this->load->view('widgets/related_widgets', $options,TRUE);
		$this->template->write('content', $content,true);
		$this->template->render();
	}

	/**
	 * Edit survey - by id
	 *
	 * @return void
	 *
	 **/
	function edit($id=NULL)
	{
		if ( !is_numeric($id)){
			show_error('Invalid parameters were passed');
		}

		$this->acl_manager->require_catalog_access();

		//test user study permissiosn
		//$this->acl->user_has_study_access($id);		

		if ($this->uri->segment(5)=='widgets'){
			return $this->widgets($id);
		}

		$tab = $this->_study_edit_tab_slug();

		$survey_row = $this->_load_study_edit_shell($id);

		if ($tab === 'metadata') {
			$meta_options = $this->_prepare_metadata_editor_options($id, true);
			$survey_row['metadata_editor'] = $this->_render_metadata_editor_html($meta_options, true);
		}

		if ($tab !== 'metadata') {
			$survey_row = $this->_append_study_edit_active_tab_config($id, $survey_row, $tab);
		} else {
			$survey_row['catalog_sidebar_app_config'] = $this->_study_edit_sidebar_app_config($id, $survey_row);
		}

		$this->_render_study_edit_page($id, $survey_row);
	}


	/**
	 * URI segment 5 for study edit tabs (empty => overview).
	 *
	 * @return string
	 */
	private function _study_edit_tab_slug()
	{
		$seg = $this->uri->segment(5);
		if ($seg === false || $seg === null || $seg === '') {
			return 'overview';
		}
		return (string) $seg;
	}


	/**
	 * Minimal study row for edit shell (header, tab nav, ACL).
	 *
	 * @param int|string $id surveys.id
	 * @return array
	 */
	private function _load_study_edit_shell($id)
	{
		$survey_row = $this->Catalog_model->select_single($id);

		if (! $survey_row) {
			show_error('Survey was not found');
		}

		$this->acl_manager->has_access_or_die('study', 'edit', null, $survey_row['repositoryid']);

		$survey_row['survey_id'] = $id;

		$this->config->load('analytics');
		$survey_row['analytics_enabled'] = (bool) $this->config->item('analytics_enabled');

		$survey_row['study_type_display'] = $this->_resolve_study_type_display(
			isset($survey_row['type']) ? $survey_row['type'] : 'survey'
		);

		return $survey_row;
	}


	/**
	 * Shared Vue bootstrap fields for study edit mini-apps.
	 *
	 * @param int|string $id
	 * @param array      $survey_row
	 * @return array
	 */
	private function _study_edit_vue_common($id, array $survey_row)
	{
		return array(
			'siteUrl'       => site_url(),
			'baseUrl'       => base_url(),
			'assetsBase'    => base_url('frontend/dist/'),
			'csrfToken'     => $this->security->get_csrf_hash(),
			'csrfTokenName' => $this->security->get_csrf_token_name(),
			'studySid'      => (int) $id,
			'studyIdno'     => isset($survey_row['idno']) ? $survey_row['idno'] : '',
		);
	}


	/**
	 * Sidebar + active tab Vue config only.
	 *
	 * @param int|string $id
	 * @param array      $survey_row
	 * @param string     $tab
	 * @return array
	 */
	private function _append_study_edit_active_tab_config($id, array $survey_row, $tab)
	{
		$this->load->helper('vite_helper');

		$survey_row['catalog_sidebar_app_config'] = $this->_study_edit_sidebar_app_config($id, $survey_row);

		switch ($tab) {
			case 'overview':
				$survey_row['catalog_overview_app_config'] = $this->_study_edit_overview_app_config($id, $survey_row);
				break;
			case 'files':
				$survey_row['catalog_files_app_config'] = $this->_study_edit_files_app_config($id, $survey_row);
				break;
			case 'resources':
				$survey_row['catalog_resources_app_config'] = $this->_study_edit_resources_app_config($id, $survey_row);
				break;
			case 'citations':
				$survey_row['catalog_citations_app_config'] = $this->_study_edit_citations_app_config($id, $survey_row);
				break;
			case 'notes':
				$survey_row['catalog_notes_app_config'] = $this->_study_edit_notes_app_config($id, $survey_row);
				break;
			case 'related-data':
				$survey_row['catalog_related_studies_app_config'] = $this->_study_edit_related_studies_app_config($id, $survey_row);
				break;
			case 'analytics':
				$survey_row['catalog_study_analytics_app_config'] = $this->_study_edit_analytics_app_config($id, $survey_row);
				break;
			default:
				break;
		}

		return $survey_row;
	}


	/**
	 * @param int|string $id
	 * @param array      $survey_row
	 * @return void
	 */
	private function _render_study_edit_page($id, array $survey_row)
	{
		$content = $this->load->view('catalog/edit_study', $survey_row, true);

		$page_title = t('catalog_maintenance');
		if (! empty($survey_row['title'])) {
			$page_title .= ' — ' . $survey_row['title'];
		}

		$page = array_merge(
			array(
				'title'           => $page_title,
				'content'         => $content,
				'hide_breadcrumb' => true,
				'theme_folder'    => 'adminvue',
			),
			$this->_admin_catalog_edit_study_shell_assets()
		);

		$this->load->view('layouts/admin_vue', $page);
	}


	private function _study_edit_analytics_app_config($id, array $survey_row)
	{
		$_sid_int = (int) $id;
		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'analyticsApiBase' => rtrim(site_url('api/admin/analytics'), '/') . '/',
				'totalViews'       => (int) (isset($survey_row['total_views']) ? $survey_row['total_views'] : 0),
				'totalDownloads'   => (int) (isset($survey_row['total_downloads']) ? $survey_row['total_downloads'] : 0),
				'exportMonthlyStudiesCsv' => site_url('api/admin/analytics/monthly/studies/export?study_id=' . $_sid_int . '&format=csv'),
				'exportMonthlyStudiesJson' => site_url('api/admin/analytics/monthly/studies/export?study_id=' . $_sid_int . '&format=json'),
				'exportFilesCsv'   => site_url('api/admin/analytics/monthly/files/export?study_id=' . $_sid_int . '&format=csv'),
				'exportFilesJson'  => site_url('api/admin/analytics/monthly/files/export?study_id=' . $_sid_int . '&format=json'),
				'labels'           => array(
					'title'               => t('Analytics'),
					'loading'             => t('loading'),
					'kpi_all_views'       => 'All-time views',
					'kpi_all_downloads'   => 'All-time downloads',
					'kpi_views_month'     => 'Views this month',
					'kpi_downloads_month' => 'Downloads this month',
					'section_trend'       => 'Monthly trend',
					'section_monthly'     => 'Monthly breakdown',
					'section_files'       => 'File downloads',
					'empty_monthly'       => 'No monthly data recorded yet for this study.',
					'empty_short'         => 'No data yet.',
					'empty_files'         => 'No file download data yet.',
					'col_period'          => 'Period',
					'col_views'           => 'Views',
					'col_unique'          => 'Unique visitors',
					'col_downloads'       => 'Downloads',
					'col_file'            => 'File',
					'total'               => 'Total',
					'chart_pageviews'     => 'Pageviews',
					'chart_downloads'     => 'Downloads',
				),
			)
		);
	}


	private function _study_edit_overview_app_config($id, array $survey_row)
	{
		$this->load->model('Configurations_model');
		$data_classifications_enabled = $this->Configurations_model->is_data_classifications_enabled();

		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'apiBaseUrl'                => site_url('api/admin/catalog/'),
				'dataClassificationsApiUrl' => site_url('api/admin/catalog/data-classifications'),
				'dataClassificationsEnabled' => (bool) $data_classifications_enabled,
				'pdfSetupUrl'               => site_url('admin/pdf_generator/setup/' . $id),
				'pdfDeleteUrl'              => site_url('admin/pdf_generator/delete/' . $id),
				'countriesMappingUrl'       => site_url('admin/countries/mappings'),
				'labels'                    => array(
					'ref_no'                       => t('ref_no'),
					'created'                      => t('created'),
					'last_changed'                 => t('last_changed'),
					'study_aliases'                => t('study_aliases'),
					'year'                         => t('year'),
					'country'                      => t('country'),
					'folder'                       => t('folder'),
					'study_folder_exists_disk'     => t('study_folder_exists_disk'),
					'study_folder_missing_disk'  => t('study_folder_missing_disk'),
					'study_folder_create'        => t('study_folder_create'),
					'repository'                   => t('repository'),
					'metadata_in_pdf'              => t('metadata_in_pdf'),
					'generate_pdf'                 => t('Generate PDF'),
					'delete'                       => t('delete'),
					'pdf_not_generated'            => t('pdf_not_generated'),
					'pdf_uptodate'                 => t('pdf_uptodate'),
					'pdf_outdated'                 => t('pdf_outdated'),
					'data_access'                  => t('data_access'),
					'data_classification'          => t('data_classification'),
					'remote_data_access_url'       => t('remote_data_access_url'),
					'indicator_database'           => t('indicator_database'),
					'study_website'                => t('study_website'),
					'featured_study'               => t('featured_study'),
					'mark_as_featured'             => t('mark_as_featured'),
					'tags'                         => t('Tags'),
					'add_tags_placeholder'         => 'Add tags — type and press Enter',
					'study_collections'            => t('study_collections'),
					'doi_label'                    => t('DOI'),
					'update'                       => t('update'),
					'saved'                        => t('form_update_success'),
					'fix_country'                  => t('Fix country code'),
					'loading'                      => t('loading'),
					'save'                         => t('save'),
					'cancel'                       => t('cancel'),
					'study_no_data_files_assigned' => sprintf(t('study_no_data_files_assigned'), ''),
					'data_selection_apply_to_files' => t('data_selection_apply_to_files'),
				),
			)
		);
	}


	private function _study_edit_files_app_config($id, array $survey_row)
	{
		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'apiBaseUrl'          => site_url('api/admin/catalog/'),
				'uploadsApiUrl'       => rtrim(site_url('api/uploads'), '/') . '/',
				'resourcesEditBase'   => rtrim(site_url('admin/resources/edit'), '/'),
				'managefilesEditBase' => rtrim(site_url('admin/managefiles/' . (int) $id . '/edit'), '/'),
				'labels'              => array(
					'name'                 => t('name'),
					'size'                 => t('size'),
					'permissions'          => t('permissions'),
					'modified'             => t('modified'),
					'actions'              => t('actions'),
					'upload'               => t('batch_upload_files'),
					'delete_selection'     => t('delete_selection'),
					'not_linked'           => t('not_linked'),
					'data_files'           => t('data_files'),
					'other_resources'      => t('other_resources'),
					'total_files'          => t('total_files_count'),
					'microdata'            => t('data_files'),
					'loading'              => t('loading'),
					'ddi_locked'           => 'DDI',
					'locked_delete'        => 'This file cannot be deleted (catalog metadata).',
					'confirm_delete'       => t('js_confirm_delete'),
					'confirm_batch_delete' => t('js_confirm_delete'),
					'no_selection'         => t('js_no_item_selected'),
					'upload_failed'        => t('form_update_fail'),
					'download'             => t('download'),
					'delete'               => t('delete'),
					'edit_resource'        => t('edit_resource'),
					'create_resource'      => t('link_add_new_resource'),
					'saved'                => t('form_update_success'),
					'resource_col'         => 'Link',
					'upload_queue_title'   => 'Files to upload',
					'drop_zone_hint'       => 'Drag and drop files here, or click to browse',
					'add_files'            => t('select_upload_files'),
					'start_upload'         => t('upload_files'),
					'clear_queue'          => 'Clear list',
					'remove_from_queue'    => t('delete'),
					'queue_empty'          => 'No files queued yet.',
					'files_queued_suffix'  => 'file(s) in queue — press Upload when ready.',
					'study_folder_title'   => 'Files in study folder',
				),
			)
		);
	}


	private function _study_edit_resources_app_config($id, array $survey_row)
	{
		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'resourcesApiBase' => rtrim(site_url('api/admin/resources'), '/') . '/',
				'legacyUrls'       => array(
					'addUrl'       => site_url('admin/resources/add/new/' . (int) $id),
					'importUrl'    => site_url('admin/resources/import/' . (int) $id),
					'fixLinksUrl'  => site_url('admin/resources/fixlinks/' . (int) $id),
					'exportRdfUrl' => site_url('admin/catalog/export_rdf/' . (int) $id),
					'editBase'     => rtrim(site_url('admin/resources/edit'), '/'),
				),
				'labels'           => array(
					'add_resource'         => t('link_add_new_resource'),
					'import_rdf'         => t('link_import_rdf'),
					'fix_links'          => t('link_fix_broken'),
					'export_rdf'         => t('rdf_export'),
					'total'              => t('total_files_count'),
					'sort_by'            => t('sort_by'),
					'sort_asc'           => 'Ascending',
					'sort_desc'          => 'Descending',
					'delete_selection'   => t('delete_selection'),
					'col_title'          => t('title'),
					'col_link'           => t('link'),
					'col_type'           => t('resource_type'),
					'col_modified'       => t('modified'),
					'col_created'        => t('created'),
					'col_id'             => 'ID',
					'col_file'           => t('filename'),
					'actions'            => t('actions'),
					'edit'               => t('edit'),
					'download'           => t('download'),
					'delete'             => t('delete'),
					'legend_ok'          => t('legend_file_exist'),
					'legend_missing'     => t('legend_file_no_exist'),
					'confirm_delete'     => t('js_confirm_delete'),
					'confirm_batch_delete' => t('js_confirm_delete'),
					'no_selection'       => t('js_no_item_selected'),
					'saved'              => t('form_update_success'),
				),
			)
		);
	}


	private function _study_edit_citations_app_config($id, array $survey_row)
	{
		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'apiBaseUrl' => rtrim(site_url('api/admin/catalog'), '/') . '/',
				'legacyUrls' => array(
					'editCitationBase' => rtrim(site_url('admin/citations/edit'), '/'),
				),
				'labels'     => array(
					'title'                => t('tab_citations'),
					'search'               => t('search'),
					'attach_citation'      => t('attach_citation'),
					'attach_citations_cta' => t('attach_citations'),
					'no_records'           => t('no_records_found'),
					'selected'             => t('showing'),
					'actions'              => t('actions'),
					'edit'                 => t('edit'),
					'remove'               => t('remove'),
					'add'                  => t('attach'),
					'confirm_remove'       => t('js_confirm_delete'),
					'confirm_batch_remove' => t('js_confirm_delete'),
					'col_attached'         => t('link'),
					'col_link'             => t('link'),
					'col_title'            => t('title'),
					'col_year'             => t('year'),
					'col_doi'              => t('DOI'),
					'col_modified'         => t('modified'),
					'col_actions'          => t('actions'),
					'sort_by'              => t('sort_by'),
					'sort_asc'             => 'Ascending',
					'sort_desc'            => 'Descending',
					'remove_selected'      => t('delete_selection'),
					'no_selection'         => t('js_no_item_selected'),
				),
			)
		);
	}


	private function _study_edit_notes_app_config($id, array $survey_row)
	{
		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'apiBaseUrl' => rtrim(site_url('api/admin/catalog'), '/') . '/',
				'labels'     => array(
					'title'            => t('tab_notes'),
					'compose_title'    => t('add_note'),
					'add_note'         => t('Submit'),
					'select_note_type' => t('select_note_type'),
					'admin_note'       => t('admin_note'),
					'reviewer_note'    => t('reviewer_note'),
					'public_note'      => t('public_note'),
					'placeholder'      => t('Type note...'),
					'remove'           => t('remove'),
					'confirm_remove'   => t('js_confirm_delete'),
					'no_records'       => t('no_records_found'),
					'saved'            => t('form_update_success'),
					'col_type'         => 'Type',
					'col_when'         => 'When',
					'col_note'         => t('notes'),
					'col_actions'      => t('actions'),
				),
			)
		);
	}


	private function _study_edit_related_studies_app_config($id, array $survey_row)
	{
		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'apiBaseUrl' => rtrim(site_url('api/admin/catalog'), '/') . '/',
				'legacyUrls' => array(
					'editStudyBase' => rtrim(site_url('admin/catalog/edit'), '/'),
				),
				'labels'     => array(
					'title'                  => t('tab_related_data'),
					'attached'               => t('attach_related_data'),
					'attach_related_cta'     => t('attach_related_data'),
					'manage_in_legacy'       => t('manage_citations_legacy_page'),
					'relationship_type'      => t('relationship_type'),
					'default_rel_help'       => t('relationship_type'),
					'search'                 => t('search'),
					'reset'                  => t('reset'),
					'add'                    => t('attach'),
					'remove'                 => t('remove'),
					'no_records'             => t('no_records_found'),
					'confirm_remove'         => t('js_confirm_delete'),
					'col_title'              => t('title'),
					'col_country_year'       => t('country'),
					'col_relationship'       => t('relationship_type'),
					'col_actions'            => t('actions'),
					'col_link'               => t('link'),
					'col_attached'           => t('link'),
					'legacy_full_page'       => t('attach_related_data'),
					'field_title'            => t('title'),
					'field_nation'           => t('country'),
					'field_idno'             => t('survey_id'),
					'field_year_start'       => t('year'),
					'field_authoring_entity' => t('producer'),
					'saved'                  => t('form_update_success'),
				),
			)
		);
	}


	private function _study_edit_sidebar_app_config($id, array $survey_row)
	{
		$_thumb_fn = '';
		if (! empty($survey_row['thumbnail'])) {
			$_thumb_fn = basename((string) $survey_row['thumbnail']);
		}

		return array_merge(
			$this->_study_edit_vue_common($id, $survey_row),
			array(
				'apiBaseUrl'           => rtrim(site_url('api/admin/catalog'), '/') . '/',
				'published'            => ! empty($survey_row['published']),
				'thumbnailFilename'    => $_thumb_fn,
				'thumbnailsPublicBase' => base_url('files/thumbnails/'),
				'studyType'            => isset($survey_row['type']) ? $survey_row['type'] : 'survey',
				'catalogListUrl'       => site_url('admin/catalog'),
				'legacyUrls'           => array(
					'browsePublic' => site_url('catalog/' . (int) $id),
					'importRdf'    => site_url('admin/resources/import/' . (int) $id),
					'fixLinks'     => site_url('admin/resources/fixlinks/' . (int) $id),
					'pdfSetup'     => site_url('admin/pdf_generator/setup/' . (int) $id),
					'replaceDdi'   => site_url('admin/catalog/replace_ddi/' . (int) $id),
					'exportDdi'    => site_url('admin/catalog/ddi/' . (int) $id),
					'refreshDdi'   => site_url('admin/catalog/refresh/' . (int) $id),
					'generateDdi'  => site_url('admin/catalog/generate_ddi/' . (int) $id),
					'transfer'     => site_url('admin/catalog/transfer/' . (int) $id),
					'exportRdf'    => site_url('admin/catalog/export_rdf/' . (int) $id),
					'deleteStudy'  => site_url('admin/catalog/delete/' . (int) $id),
				),
				'labels'               => array(
					'status'                 => t('Status'),
					'published'              => t('published'),
					'draft'                  => t('draft'),
					'delete_study'           => t('delete_study'),
					'confirm_delete'         => t('js_confirm_delete'),
					'study_warnings'         => t('study_warnings'),
					'thumbnail'              => t('Thumbnail'),
					'upload'                 => t('upload'),
					'remove'                 => t('remove'),
					'cancel'                 => t('cancel'),
					'upload_thumbnail_title' => t('Upload thumbnail'),
					'survey_options'         => t('Survey options'),
					'options'                => t('study_sidebar_options'),
					'thumbnail_empty_hint'   => t('study_sidebar_no_thumbnail'),
					'browse_metadata'        => t('browse_metadata'),
					'upload_rdf'             => t('upload_rdf'),
					'link_resources'         => t('link_resources'),
					'generate_pdf'           => t('generate_pdf'),
					'replace_ddi'            => t('replace_ddi'),
					'export_ddi'             => t('export_ddi'),
					'refresh_ddi'            => t('refresh_ddi'),
					'transfer_study'         => t('transfer_study_ownership'),
					'export_rdf'             => t('export_rdf'),
					'click_publish'          => t('click_to_publish_unpublish'),
					'saved'                  => t('form_update_success'),
					'loading'                => t('loading'),
					'confirm_generate_ddi'   => 'This will overwrite the existing DDI file. Are you sure?',
					'generate_ddi'           => t('Generate DDI'),
				),
			)
		);
	}


	/**
	 * 
	 * Get data access dropdown list by data classification
	 * 
	 * @return_output - if true, return the html
	 */
	function da_by_class($classification_code=null,$da_id=null,$format='html',$return_output=false)
	{
		if(is_numeric($classification_code)){
			$classification=$this->Data_classification_model->get_single($classification_code);
			if(isset($classification['code'])){
				$classification_code=$classification['code'];
			}
		}

		//load data classification + license options
		$this->config->load('data_access');
		$data_access_options=$this->config->item("data_access_options");

		$data_classfications = $this->Data_classification_model->get_all();

		//data access options by classification
		$da_options=isset($data_access_options[$classification_code]) ? $data_access_options[$classification_code] : array();

		$data_access_list=$this->Form_model->get_all();

		$output=array();
		foreach($da_options as $da){
			if(isset($data_access_list[$da])){
				$output[$da]=$data_access_list[$da];
			}
		}

		if($format=="html"){
			$html= $this->load->view('catalog/data_access_dropdown', array('da_list'=>$output,'selected'=>$da_id),TRUE);
			if ($return_output==true) {
				return $html;
			}
			echo $html;
			return;
		}
		
		return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($output));

	}



	/*
	*
	* Update various study options
	*/
	function update()
	{
		//study id
		$id=$this->input->post("sid");

		if (!is_numeric($id)){
			show_404();
		}
		
		$survey=$this->Catalog_model->get_survey($id);

		if(!$survey){
			show_404();
		}

		//test user study permissiosn
		//$this->acl->user_has_study_access($id);
		$this->acl_manager->has_access_or_die('study', 'edit', null, $survey['repositoryid']);

		//is ajax call
		$ajax=$this->input->get_post('ajax');

		//allowed fields
		$allowed_keys=array('published','formid','link_indicator','link_study','link_da','license_id','data_class_id');

		$options=array();
		foreach($_POST as $key=>$value){
			if (in_array($key,$allowed_keys)){
				$options[$key]=$this->input->post($key);
			}
		}
		
		$options['id']=$id;
		$result=$this->Catalog_model->update_survey_options($options);

		if ($result){
			$this->session->set_flashdata('message', t('form_update_success'));
		}
		else{
			$this->session->set_flashdata('error', t('form_update_failed'));
		}
		//$this->events->emit('db.after.update', 'surveys', $id,'atomic');
		redirect('admin/catalog/edit/'.$id);
	}


	/**
	 * Human-readable study type for the catalog edit header (reference table when available, else lang lines).
	 *
	 * @param string $type_code Value of surveys.type (e.g. survey, timeseries, document).
	 * @return string
	 */
	private function _resolve_study_type_display($type_code)
	{
		$code = strtolower(trim((string) $type_code));
		if ($code === '') {
			$code = 'survey';
		}

		if ($this->db->table_exists('survey_types')) {
			if ($this->db->field_exists('code', 'survey_types') && $this->db->field_exists('title', 'survey_types')) {
				$this->db->select('title');
				$this->db->from('survey_types');
				$this->db->where('code', $code);
				$this->db->limit(1);
				$row = $this->db->get()->row_array();
				if (! empty($row['title'])) {
					return (string) $row['title'];
				}
			}
		}

		$this->lang->load('catalog_admin');
		$lang_key = 'study_type_' . str_replace('-', '_', preg_replace('/[^a-z0-9_\-]/', '', $code));
		$label = t($lang_key);
		if ($label !== '' && $label !== $lang_key) {
			return $label;
		}

		return ucwords(str_replace(array('_', '-'), ' ', $code));
	}


	/**
	*
	* add/update related study
	*
	*	@sid_1			parent study id
	*	@sid_2			child studies comma separated list e.g 1,2,3,4
	*	@rel_id			relationship id
	**/
	function update_related_study($sid_1=null,$sid_2=null,$rel_id=null)
	{
		if(!is_numeric($sid_1) || !is_numeric($rel_id))
		{
			show_error("INVALID_PARAMS");
		}

		$sid_2_arr=explode(",",$sid_2);

		foreach($sid_2_arr as $value)
		{
			if(!is_numeric($value))
			{
				show_error("INVALID_PARAM");
			}
		}

		$this->load->model("Related_study_model");
		$this->Related_study_model->update_relationship($sid_1,$sid_2_arr,$rel_id);
	}


	/**
	*
	*	Remove a single study relationship
	**/
	function remove_related_study($sid_1=null,$sid_2=null,$rel_id=null)
	{
		if(!is_numeric($sid_1) || !is_numeric($rel_id) || !is_numeric($sid_2) )
		{
			show_error("INVALID_PARAMS");
		}

		$sid_2_arr=explode(",",$sid_2);

		$this->load->model("Related_study_model");
		$this->Related_study_model->delete_relationship($sid_1,$sid_2,$rel_id=null);
	}

	function set_featured_study($repositoryid,$sid,$status)
	{
		$result=$this->Repository_model->set_featured_study($repositoryid,$sid,$status);

		if ($this->input->get('destination')){
			redirect($this->input->get('destination'));
		}

		if ($this->input->get("ajax")){
			echo json_encode(array('status'=>$result));
		}
	}


	//list all featured studies
	function featured_studies()
	{
		$data['featured_studies']=$this->Repository_model->get_all_featured_studies();
		$content=$this->load->view('catalog/featured_studies', $data,TRUE);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}


	/**
	 * 
	 * Attach survey data files to external resources
	 * 
	 */
	function attach_data_file_resources($sid,$file_id)
	{
		$this->load->model("Dataset_model");
		$this->load->model("Survey_resource_model");
		$this->load->model("Data_file_model");
		$this->load->model("Data_file_resources_model");

		$survey=$this->Dataset_model->get_row($sid);

		if(!$survey){
			show_error("DATASET-NOT-FOUND");
		}

		//load data file and resources
		//TODO

		$data['sid']=$sid;
		$data['file_id']=$file_id;
		$data['survey']=$survey;

		//get file info
		$data['file']=$this->Data_file_model->get_file_by_id($sid,$file_id);

		//get all microdata type external resources
		$data['resources']=$this->Survey_resource_model->get_microdata_resources($sid);

		//get current data file and attached resources
		$data['attached_resources']=$this->Data_file_resources_model->get_file_resources($sid,$file_id);

		$content=$this->load->view('catalog/attach_data_file_resources',$data,true);
		
		$this->template->set_template('admin_blank');
		$this->template->write('content', $content,true);
		$this->template->render();
	}

	//process posted form
	function attach_data_file_resources_post($sid,$file_id)
	{
		$this->load->model("Data_file_resources_model");
		$resources=$this->input->post("resource_id");
		$formats=$this->input->post("format");

		$options=array();
		foreach($resources as $idx=>$value)
		{
			echo $value;
			echo "-";
			echo $formats[$idx];

			$options[]=array(
				'resource_id'=>$value,
				'sid'=>$sid,
				'fid'=>$file_id,
				'file_format'=>$formats[$idx]
			);
		}

		//update data file resources links
		$this->Data_file_resources_model->batch_update($sid, $file_id, $options);

		redirect('admin/catalog/attach_data_file_resources/'.$sid.'/'.$file_id);
	}


	/**
	 *
	 * Upload ddi file for ddi replace
	 * 
	 * @return array
	 */
	private function upload_ddi_file($key,$destination)
	{
		if ($_FILES[$key]['size']==0)
		{
			return false;
		}
		$config['encrypt_name']	 = TRUE;
		$config['upload_path'] = $destination;
		$config['allowed_types'] = 'xml';
		$config['overwrite'] = true;
		$this->load->library('upload', $config);
		if ( ! $this->upload->do_upload($key))
		{
			throw new Exception( $this->upload->display_errors() );
		}
		else
		{
			$data = $this->upload->data();
			return $data;
		}
	}


	function doi($sid=null) 
	{
		$this->template->set_template('admin_blank');
 
		$this->load->model("Dataset_model");
		$dataset=$this->Dataset_model->get_row($sid);

		$this->config->load('doi');
		$doi_options=$this->config->item("doi");
		
		$options=array();
		$options['dataset']=$dataset;
		$options['doi_options']=$doi_options;
		$content=$this->load->view('catalog/doi', $options,true);
		$this->template->write('content', $content,true);
	  	$this->template->render();
	}

	/*
	*
	* Set DOI
	*
	*/
	function update_doi()
	{
		//study id
		$id=$this->input->post("sid");

		if (!is_numeric($id)){
			show_404();
		}
		
		$survey=$this->Catalog_model->get_survey($id);

		if(!$survey){
			show_404();
		}

		//test user study permissiosn
		//$this->acl->user_has_study_access($id);
		$this->acl_manager->has_access_or_die('study', 'edit', null, $survey['repositoryid']);

		//is ajax call
		$ajax=$this->input->get_post('ajax');
		
		$doi=$this->input->post("doi");

		try{
			$result=$this->Dataset_model->assign_doi($id,$doi);
		}
		catch(Exception $e){
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('admin/catalog/edit/'.$id);
		}

		$this->session->set_flashdata('message', t('form_update_success'));		
		
		$this->events->emit('db.after.update', 'surveys', $id,'atomic');
		redirect('admin/catalog/edit/'.$id);
	}


	function generate_ddi($sid=null)
	{
		if (!$sid){
			show_404();
		}

		$this->load->model("Dataset_model");
		$survey=$this->Catalog_model->get_survey($sid);

		if(!$survey){
			show_404();
		}

		$this->acl_manager->has_access_or_die('study', 'edit', null, $survey['repositoryid']);
		
		try{
			$result=$this->Dataset_model->write_ddi($sid,$overwrite=true);
			$this->session->set_flashdata('message', t('form_update_success'));
			redirect('admin/catalog/edit/'.$sid);
		}
		catch(Exception $e){
			$this->session->set_flashdata('error', $e->getMessage());
			redirect('admin/catalog/edit/'.$sid);
		}
	}


	/**
	 * Export studies as CSV.
	 *
	 * Optional GET owner_repo or repositoryid: filter like legacy single-collection export (survey_repos).
	 * Omit: unrestricted users get full export; collection-scoped users get rows for allowed repositories only.
	 *
	 * @return void
	 */
	function export_csv()
	{
		$this->acl_manager->require_catalog_access();
		$scope = $this->acl_manager->get_admin_catalog_repository_scope();

		$req = trim((string) $this->input->get('owner_repo'));
		if ($req === '') {
			$req = trim((string) $this->input->get('repositoryid'));
		}
		if ($req === '') {
			$p = $this->input->get_post('repositoryid');
			$req = ($p !== null && $p !== '') ? trim((string) $p) : '';
		}

		if ($req !== '') {
			if ($scope !== null) {
				if (! $this->_repositoryid_allowed_by_catalog_scope($req)) {
					$this->acl_manager->show_access_denied('feature');
				}
			}
			$this->acl_manager->has_access_or_die('study', 'view', null, $req);
			$repo = (strtolower($req) === 'central') ? null : $req;
			$this->Catalog_model->download_csv($repo);
			return;
		}

		$this->acl_manager->has_access_or_die('study', 'view');

		if ($scope === null) {
			$this->Catalog_model->download_csv(null);
			return;
		}

		if ($scope === false || ! is_array($scope) || count($scope) === 0) {
			$this->acl_manager->show_access_denied('feature');
		}

		$this->Catalog_model->download_csv(array_values($scope));
	}
	
}
/* End of file catalog.php */
/* Location: ./controllers/admin/catalog.php */
