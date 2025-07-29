<?php
class Metadata extends MY_Controller {

	private $user=FALSE;

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->load->library("Dataset_manager");
        $this->load->model("Dataset_model");
        $this->load->helper("download");
	}
    
    /**
     * 
     * 
     * Export metadata
     * 
     * @format - JSON, DDI
     * 
     */	
    function export($sid=null,$format='json')
	{
        $dataset=$this->Dataset_model->get_row($sid); 

        if(!$dataset){
            show_404();
        }

        if($format=='json'){
            if (!$this->input->get("detailed")){
                $metadata=$this->dataset_manager->get_metadata($sid,$dataset['type']);
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($metadata));
                return;
            }
            //download JSON file
            $this->Dataset_model->download_metadata_json($sid);
        }
        else if($format=='ddi' && $dataset['type']=='survey'){
            $this->Dataset_model->download_metadata_ddi($sid);
        }
        else if($format=='croissant' && $dataset['type']=='survey'){
            $this->load->library('Croissant_Writer');

            $extended_metadata=$this->input->get('extended');

			if ($extended_metadata=='true'){
				$this->croissant_writer->enable_extended_metadata(true);
			}

            $metadata=$this->croissant_writer->write_croissant($sid);
            $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($metadata));            
            return;
        }
    }
    
    
    /**
     * 
     * 
     * Export variable metadata
     * 
     * @format - JSON, CSV
     * 
     */
	function export_variable($sid=null,$vid=null,$format='json')
	{
        $dataset=$this->Dataset_model->get_row($sid);

        if(!$dataset){
            show_404();
        }

        $this->load->model('Variable_model');
        $variable=$this->Variable_model->get_var_by_vid($sid,$vid);

        if(!$variable){
            show_404();
        }

        $variable=$variable['metadata'];
        $variable['sid']=$sid;
        $variable['survey_idno']=$dataset['idno'];


        if($format=='csv'){
            $this->Variable_model->export($list=array($variable),'csv');
            return;
        }

        
        //JSON output - default
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($variable));
        return;
	}	

}