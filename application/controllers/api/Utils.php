<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Utils extends MY_REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper("date");
		$this->is_admin_or_die();
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
		
    
    
    /**
	 * 
	 *  batch delete by type
	 * 
	 */
	public function batch_delete_by_type_delete($type=NULL)
	{
		try{
            
            $this->db->where('type',$type);
            $this->db->delete('surveys');

			$response=array(
				'status'=>'success',
				'message'=>'DELETED'
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}
	
	
	/**
	 * 
	 *  Get list of IDs by Type
	 * 
	 */
	public function datasets_list_by_type_get($type=NULL)
	{
		try{
            $this->db->select('id,idno,type');
            $this->db->where('type',$type);
            $result=$this->db->get('surveys')->result_array();

			$response=array(
				'status'=>'success',
				'items'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}
	
	

	public function datasets_publish_by_type_get($type=NULL)
	{
		try{
            $options=array(
				'published'=>1
			);
            $this->db->where('type',$type);
            $result=$this->db->update('surveys',$options);

			$response=array(
				'status'=>'success',
				'items'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}	

	public function datasets_unpublish_by_type_get($type=NULL)
	{
		try{
            $options=array(
				'published'=>0
			);
            $this->db->where('type',$type);
            $result=$this->db->update('surveys',$options);

			$response=array(
				'status'=>'success',
				'items'=>$result
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}



	/**
	 * 
	 * Repopulate variable fields for indexing
	 * 
	 *  fields added to index:
	 * 	- var_notes
	 *  - var_txt
	 * 
	 */
	public function variable_index_repopulate_get($start=0,$limit=10)
	{
		try{
			
			if (!is_numeric($limit) || $limit >1000){
				throw new exception("Invalid value for limit. Provide a numeric value for limit. Max value = 1000");
			}

			if (!is_numeric($start)){
				throw new exception("Invalid value for start");
			}

			$this->load->model("Dataset_model");
			$this->load->model("Variable_model");
			$this->db->select("uid,sid,metadata");
			$this->db->where("uid >",$start);
			$this->db->order_by('uid');
			$this->db->limit($limit);
			$variables=$this->db->get("variables")->result_array();

			$last_row_id=null;
			$updated=0;

			foreach($variables as $key=>$variable){
				$metadata=$this->Dataset_model->decode_metadata($variable['metadata']);

				$index_text='';

				if(isset($metadata['var_notes'])){
					$index_text=$metadata['var_notes'];
				}
				if(isset($metadata['var_txt'])){
					$index_text=$index_text. ' '. $metadata['var_txt'];
				}

				if (trim($index_text)!=''){
					$options=array(
						'keywords'=>$index_text
					);				

					$this->Variable_model->update($variable['sid'],$variable['uid'],$options);
					$updated++;
				}

				$last_row_id=$variable['uid'];
			}


			$response=array(
				'status'=>'success',
				'processed'=>count($variables),
				'start_row'=>$start,
				'last_row'=>$last_row_id,
				'updated'=>$updated
			);

			$this->set_response($response, REST_Controller::HTTP_OK);
		}	
		catch(Exception $e){
			$error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
		}	
	}
}
