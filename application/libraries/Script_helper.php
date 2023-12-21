<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Script_helper{
	
	
	var $ci=NULL;	

	function __construct()
	{
		$this->ci=& get_instance();
        $this->ci->load->model('Survey_resource_model');
	}

    /**
     * 
     * Return only the first resource
     * 
     */
    function get_reproducibility_package_resource($sid)
    {
		$resources=$this->ci->Survey_resource_model->get_resources_by_survey($sid);

        $prg_resources=[];
		foreach($resources as $resource)
		{
			if (stristr($resource['dctype'],'[prg]')!==false){
				return $this->generate_download_link($resource);
			}
		}

        return false;
    }


    function generate_download_link($resource)
	{
        if($this->ci->form_validation->valid_url($resource['filename'])){
            $resource['_links']=array(
                'download'=>$resource['filename'],
                'type'=>'link'
            );				
        }else{
            if(!empty($resource['filename'])){
                $resource['_links']=array(
                    'download'=> site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename'])),
                    'type'=>'download'
                );
            }
        }  

		return $resource;
	}

}