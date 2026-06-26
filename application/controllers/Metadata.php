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
     * @format - json, jsonl, ddi
     * 
     */	
    function export($sid=null,$format='json')
	{
        $dataset=$this->Dataset_model->get_row($sid); 

        if(!$dataset){
            show_404();
        }

        if($format=='json' || $format=='jsonl'){
            try{
                $this->load->library('JSON_Writer');
                
                $pretty = $this->input->get('pretty') === 'true' || $this->input->get('pretty') === '1';
                $dsd_export = strtolower(trim((string) $this->input->get('dsd_export'))) === JSON_Writer::DSD_EXPORT_INLINE
                    ? JSON_Writer::DSD_EXPORT_INLINE
                    : JSON_Writer::DSD_EXPORT_REFERENCE;

                if ($format == 'jsonl') {
                    $this->json_writer->download($sid, 'jsonl', false, false, $dsd_export);
                } else {
                    $this->json_writer->download($sid, 'json', $pretty, false, $dsd_export);
                }
            }		
            catch(Exception $e){
                show_error($e->getMessage());
            }	
        }
        else if($format=='ddi' && $dataset['type']=='survey'){
            $this->Dataset_model->download_metadata_ddi($sid);
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