<?php
/**
 * Metadata editor
 *
 *
 */
class Template_editor extends MY_Controller {

	var $active_repo=NULL; //active repo object

  	public function __construct()
	{
      	parent::__construct();
		$this->lang->load("users");
		$this->lang->load("general");
        $this->load->model('Editor_template_model');
	}

	


	/***
	 * 
	 * Get project template or the default template for the project type
	 * 
	 */
	function get_project_template($project)
	{
		$template=NULL;
		
		//load template set for the project
		if (isset($project['template_uid']) && !empty($project['template_uid'])){
			$template=$this->Editor_template_model->get_template_by_uid($project['template_uid']);
		}

		if (!$template){		
			//load default template for the project type
			$default_template=$this->Editor_template_model->get_default_template($project['type']);

			if (isset($default_template['template_uid'])){
				$template=$this->Editor_template_model->get_template_by_uid($default_template['template_uid']);
			}
		}
		
		//load core template for the project type
		if (empty($template)){
			$core_templates_by_type=$this->Editor_template_model->get_core_templates_by_type($project['type']);

			if (!$core_templates_by_type){
				throw new Exception("Template not found for type", $project['type']);
			}

			//load default core template by type
			$template=$this->Editor_template_model->get_template_by_uid($core_templates_by_type[0]["uid"]);
		}
		
		return $template;
	}



	function get_template_custom_parts($items,&$output)
	{		
		foreach($items as $item){
			if (isset($item['items'])){
				$this->get_template_custom_parts($item['items'],$output);
			}
			if (isset($item['type']) 
				&& $item['type']=='section_container' 
				&& (isset($item['is_editable']) && $item['is_editable']==false)){				
				$output[$item['key']]=$item;
			}
		}        
	}

	function get_template_editable_parts(&$items)
	{
		foreach($items as $key=>$item){
			if (isset($item['items'])){
				if (isset($item['is_editable']) && $item['is_editable']==false){
					//delete
					unset($items[$key]);
				}else{
					$this->get_template_editable_parts($item['items']);
				}
			}
			
		}
	}


	function index($uid=null)
	{
		if ($uid){
			return $this->template_edit($uid);
		}

		$options['translations']=$this->lang->language;
		echo $this->load->view('templates/index',$options,true);
	}

	function edit($uid)
	{
		$this->template->set_template('blank');		
		$user_template=$this->Editor_template_model->get_template_by_uid($uid);

		if(!$user_template){
			show_error("Template not found");
		}

		$core_templates=$this->Editor_template_model->get_core_template_by_data_type($user_template['data_type']);

		if (!$core_templates){
			throw new Exception("No system templates found for type: " . $user_template['data_type']);
		}

		$core_template=$this->Editor_template_model->get_template_by_uid($core_templates[0]["uid"]);

		$options=array(
			'user_template_info'=>$user_template,
			'core_template'=>$core_template,
			'user_template'=>$user_template,
			'translations'=>$this->lang->language
		);

		unset($options['user_template_info']['template']);
		echo $this->load->view('template_manager/index',$options,true);
	}

	
}
/* End of file template_editor.php */
/* Location: ./controllers/admin/template_editor.php */
