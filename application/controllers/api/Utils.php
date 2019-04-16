<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Utils extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
	}
	

    /**
     * 
     * Convert RDF to JSON
     * 
     */
    public function parse_rdf_post()
    {
        $this->load->library('RDF_Parser');

        try {
            $result=$this->upload_file('file');
            $uploaded_path=$result['full_path'];            
            $rdf_contents=file_get_contents($uploaded_path);
            $rdf_array=$this->rdf_parser->parse($rdf_contents);

            if ($rdf_array===FALSE || $rdf_array==NULL){
                throw new Exception("NO_ENTRIES_FOUND");
            }

            $rdf_fields=$this->rdf_parser->fields;
            $rdf_fields['additional']='99';

            $result=array();
            foreach($rdf_array as $row){
                $result[]=array_combine(array_keys($rdf_fields), $row);
            }
        
            @unlink($uploaded_path);

            $output=array(
                'status'=>'success',
                'entries'=>$result
            );

            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $output=array(
                'status'=>'error',
                'message'=>$e->getMessage()
            );
            $this->set_response($output, REST_Controller::HTTP_BAD_REQUEST);
        }

        
    }


    /**
	 * 	
	 *
	 * upload file to temp
	 *
	 * @file_field_name 	- name of POST file variable
	 *  
	 **/ 
	private function upload_file($file_field_name='file', $allowed_types=array('rdf','xml'))
	{
		$temp_upload_folder=get_catalog_root().'/tmp';
		
		if (!file_exists($temp_upload_folder)){
			@mkdir($temp_upload_folder);
		}
		
		if (!file_exists($temp_upload_folder)){
			throw new Exception('DATAFILES-TEMP-FOLDER-NOT-SET');
		}
						
		//upload class configurations for RDF
		$config['upload_path'] = $temp_upload_folder;
		$config['overwrite'] = false;
		$config['encrypt_name']=true;
		$config['allowed_types'] = implode("|", $allowed_types);
		
		$this->load->library('upload', $config);

		//process uploaded rdf file
		$upload_result=$this->upload->do_upload($file_field_name);

		if (!$upload_result){
			$error = $this->upload->display_errors();
			throw new Exception("RDF_UPLOAD::".$error);
		}

		return $this->upload->data();		
	}
		
	
}
