<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

class Facets extends MY_REST_Controller
{
	public function __construct()
	{
        parent::__construct();
        $this->load->model("Facet_model");
		$this->is_admin_or_die();
    }


    // GET /api/facets — list all user facets with term counts
    public function index_get()
    {
        try {
            $rows   = $this->Facet_model->select_terms_counts_detailed();
            $facets = array();
            foreach ((array) $rows as $row) {
                if ($row['facet_type'] === 'core') { continue; }
                $facets[] = $row;
            }
            $this->set_response(array('status' => 'success', 'facets' => $facets), REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    // GET /api/facets/get/{name} — single facet by name (with decoded mappings)
    public function get_get($name = null)
    {
        try {
            $facet = $this->Facet_model->get_facet_by_name($name);
            if (!$facet) {
                $this->set_response(array('status' => 'error', 'message' => 'Facet not found'), REST_Controller::HTTP_NOT_FOUND);
                return;
            }
            if (isset($facet['mappings'])) {
                $facet['mappings'] = json_decode($facet['mappings'], true) ?: array();
            }
            $this->set_response(array('status' => 'success', 'facet' => $facet), REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    // GET /api/facets/terms/{id} — terms for a given facet
    public function terms_get($id = null)
    {
        try {
            $facet = $this->Facet_model->select_single($id);
            $terms = $this->Facet_model->get_facet_terms($id);
            $this->set_response(array(
                'status' => 'success',
                'facet'  => $facet,
                'terms'  => array_values((array) $terms),
            ), REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    // GET /api/facets/ordering — current per-type ordering + full facets map
    public function ordering_get()
    {
        try {
            $this->load->model('Configurations_model');
            $data_types = array('all', 'microdata', 'geospatial', 'document', 'table', 'image', 'video', 'timeseries', 'script');
            $ordering   = array();
            foreach ($data_types as $type) {
                $ordering[$type] = (array) json_decode($this->Configurations_model->get_config_item('facets_' . $type));
            }
            $facets = $this->Facet_model->select_all();
            $this->set_response(array('status' => 'success', 'ordering' => $ordering, 'facets' => $facets), REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    // GET /api/facets/stats — indexer stats (term value counts + studies total)
    public function stats_get()
    {
        try {
            $this->load->model('Dataset_model');
            $rows   = $this->Facet_model->select_term_value_counts('user');
            $count  = $this->Dataset_model->get_total_count();
            $this->set_response(array(
                'status'        => 'success',
                'stats'         => array_values((array) $rows),
                'studies_count' => $count,
            ), REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    // POST /api/facets/delete/{id} — delete a user facet
    public function delete_post($id = null)
    {
        try {
            $facet = $this->Facet_model->select_single($id);
            if ($facet && $facet['facet_type'] === 'core') {
                $this->set_response(array('status' => 'error', 'message' => 'Core facets cannot be deleted'), REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
            $this->Facet_model->delete_facet($id);
            $this->set_response(array('status' => 'success'), REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->set_response(array('status' => 'error', 'message' => $e->getMessage()), REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    


    /**
	 *
	 * recursive function to reindex all facets
	 *
	 * @start_row start importing from a row number or NULL to start from first id
	 * @limit number of records to read at a time
	 * @loop whether to recursively call the function till the end of rows
	 *
	 * */
	public function reindex_get($start_row=NULL, $limit=10)
	{		  
        $output=$this->Facet_model->reindex($start_row, $limit, $loop=false);      
        try{
			//$output=$this->Facet_model->reindex($start_row, $limit, $loop=false);
			$output=array(
                'status'=>'success',
                'result'=>$output
			);
			$this->set_response($output, REST_Controller::HTTP_OK);			
		}
		catch(Exception $e){
            $error_output=array(
				'status'=>'failed',
				'message'=>$e->getMessage()
			);
			$this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
		}
    }
    



    public function clear_index_get()
    {        
        try{
            $output=$this->Facet_model->clear_index();
            $output=array(
                'status'=>'success'
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
        }
        catch(Exception $e){
            $error_output=array(
                'status'=>'failed',
                'message'=>$e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);			
        }
    }
    

    function reorder_post()
	{
		try{
            $this->load->model("Configurations_model");
            $options=$this->input->post(null,true);

            $data_types=array(
                'all',
                'microdata',
                'geospatial',
                'document',
                'table',
                'image',
                'video',
                'timeseries',
                'script'
            );

            $data=array();

            foreach($data_types as $type){
                if (isset($options[$type])){
                    $data=array_keys($options[$type]);
                    $result=$this->Configurations_model->upsert($name='facets_'.$type, json_encode($data));
                }
            }

            $output=array(
                'status'=>'success',
                'options'=>$options
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
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
     * 
     * Create/update facet
     * 
     * 
     */
    function index_post()
	{
		try{
            $this->load->model("Configurations_model");
            $options=$this->raw_json_input();

            $result=$this->Facet_model->create_facet($options);

            $output=array(
                'status'=>'success',
                'options'=>$options,
                'result'=>$result
            );
            $this->set_response($output, REST_Controller::HTTP_OK);			
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