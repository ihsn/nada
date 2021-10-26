<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Import Geospatial xml file
 *
 */
class Geospatial_import{

    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    function import($file_path, $options)
	{
		$parser_params=array(
            'file_type'=>'geospatial',
			'file_path'=>$file_path,
			'partial'=>false
		);

		$this->ci->load->library('Metadata_parser', $parser_params);
		
		//parser to read metadata
        $parser=$this->ci->metadata_parser->get_reader();

		$idno=$parser->get_id();

		$metadata=$parser->get_metadata_array();
		/*echo '<pre>';
		var_dump($metadata);
		echo '<HR>';*/
		$metadata=($this->transform_geospatial_fields($metadata));

        //echo json_encode($metadata,JSON_PRETTY_PRINT);
        

            /*$user_id=$this->get_api_user_id();
			
			$options['created_by']=$user_id;
			$options['changed_by']=$user_id;
			$options['created']=date("U");
			$options['changed']=date("U");
            */

            $options=array_merge($options, $metadata);
            
			//validate & create dataset
			$dataset_id=$this->ci->dataset_manager->create_dataset($type='geospatial',$options);

			if(!$dataset_id){
				throw new Exception("FAILED_TO_CREATE_DATASET");
			}

			$dataset=$this->ci->dataset_manager->get_row($dataset_id);

			//create dataset project folder
			$dataset['dirpath']=$this->ci->dataset_manager->setup_folder($repositoryid='central', $folder_name=md5($dataset['idno']));

			$update_options=array(
                'dirpath'=>$dataset['dirpath'],
                'metafile'=>basename($file_path)
            );

            $this->ci->dataset_manager->update_options($dataset_id,$update_options);

            $dataset_storage_path=unix_path(get_catalog_root().'/'.$dataset['dirpath'].'/'.basename($file_path));
            copy($file_path,$dataset_storage_path);
            
            //$dataset['path']=$dataset_storage_path;
            
			return $dataset;
	}


	//transform structure of ddi fields  to survey type fields
    private function transform_geospatial_fields($metadata)
    {
        //mappings from DDI to NADA SURVEY type
        $ddi_mappings=$this->ci->config->item('geospatial',"metadata_parser",TRUE);

        $mappings=array();
        $complex_fields=array();
        foreach($ddi_mappings as $key=>$value){
            $mappings[$value['xpath']][]=$key;

            if(isset($value['type']) && $value['type']=='array' ){
                $complex_fields[$value['xpath']]['type']='array';
            }
        }

        $output=array();
        //only importing what is mapped
        foreach($mappings as $xpath=>$values)
        {
            foreach($values as $value){            
                //metadata exists?
                if(isset($metadata[$xpath])){
                    $element_value=$metadata[$xpath];

                    //complex type?
                    if(isset($ddi_mappings[$value]['type']) &&  $ddi_mappings[$value]['type']=='array'){
                        $this->array_nested_path($output, $value, $element_value, $glue = '/');
                    }
                    else{
                        //non-complex types
                        //value in array format
                        if(is_array($element_value) ){
                            //echo $value."-----\r\n";
                            //var_dump($element_value);
                            $this->array_nested_path($output, $value, implode(" ",$element_value), $glue = '/');
                        }
                        else { //simple element
                            #$output[$mappings[$key]]=$value;
                            $this->array_nested_path($output, $value, $element_value, $glue = '/');
                        }
                    }
                                  
                }
            }    
        }

        //array of array fields        
        if (isset($output['description']['identificationInfo']['extent']['geographicElement']['geographicBoundingBox'])){
            $ident_extent_bbox=$output['description']['identificationInfo']['extent']['geographicElement']['geographicBoundingBox'];
            unset($output['description']['identificationInfo']['extent']['geographicElement']);
            $output['description']['identificationInfo']['extent']['geographicElement'][]['geographicBoundingBox']=$ident_extent_bbox[0];
        }

        $identification_info=$output['description']['identificationInfo'];        
        unset($output['description']['identificationInfo']);
        $output['description']['identificationInfo'][]=$identification_info;

        //echo '<pre>';
        //print_r($output);

        return $output;
    }

    
    //return an array with the nested path and value
    function array_nested_path(&$array, $parents, $value, $glue = '/')
    {
        $parents = explode($glue, (string) $parents);
        $reference = &$array;
        foreach ($parents as $key) {
            if (!array_key_exists($key, $reference)) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }
        $reference = $value;
        unset($reference);

        return $array;
    }

    function get_array_nested_value($data, $path, $glue = '/')
    {
        $paths = explode($glue, (string) $path);
        $reference = $data;
        foreach ($paths as $key) {
            if (!array_key_exists($key, $reference)) {
                return false;
            }
            $reference = $reference[$key];
        }
        return $reference;
    }
}