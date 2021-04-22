<?php


class Facets_test extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
        $this->load->model("Facet_model");
        $this->load->model("Dataset_model");
	}


    function index()
    {
        echo "<pre>";
        var_dump($this->Facet_model->get_facet_options());
    }


    function index_all()
    {
        echo '<pre>';
        $studies=$this->Dataset_model->get_all($sid=null);//,$type='document');
        foreach($studies as $study){
            echo $study['idno']."\r\n";
            echo $study['id']."\r\n";
            $this->test($study['id']);
        }
        
    }


    function test($sid)
    {
        echo '<pre>';

        $study=$this->Dataset_model->get_row($sid);

        //var_dump($study);
        //die();

        //get metadata for a study
        $metadata=$this->Dataset_model->get_metadata($sid);
        
        //print_r($study);
        
        //extract facets
        $facet_data=$this->Facet_model->extract_facet_values($type=$study['type'],$metadata);

       var_dump($facet_data);
       //return;
       //die();

        //remove all existing facet terms for study
        $this->Facet_model->clear_facet_values($sid);

        //upsert facets
        foreach($facet_data as $facet_key=>$facet_values)
        {

            $facet_id=$this->Facet_model->get_facet_id($facet_key);

            if(empty($facet_id)){
                //create facet
                $facet_id=$this->Facet_model->create_facet(array('name'=>$facet_key, 'title'=>$facet_key));
            }

            //var_dump($facet_id);

            foreach($facet_values as $facet_value){
                
                if(empty($facet_value)){
                    continue;
                }

                if(is_array($facet_value)){
                    var_dump($facet_data);
                    die("IS-ARR");
                }

                //create a term for facet if not already exists
                $term_id=$this->Facet_model->upsert_facet_term($facet_id,$facet_value);

                //var_dump($term_id);

                //upsert facet value
                $this->Facet_model->insert_facet_value($sid,$facet_id,$term_id);
            }

        }

    }

}
