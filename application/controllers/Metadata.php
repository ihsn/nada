<?php
class Metadata extends MY_Controller {

	private $user=FALSE;

    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
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
            $metadata=$this->Dataset_model->get_metadata($sid);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($metadata));
            return;
        }
        else if($format=='ddi' && $dataset['type']=='survey'){
            $ddi_path=$this->Dataset_model->get_metadata_file_path($sid);
            if(file_exists($ddi_path)){
                force_download2($ddi_path);
            }
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