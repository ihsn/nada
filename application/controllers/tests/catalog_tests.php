<?php
class Catalog_tests extends MY_Controller
{
    public function __construct()
    {
        parent::__construct($skip_auth = TRUE);

        $this->load->library('unit_test');
        $this->load->model('Catalog_model');
        //$this->output->enable_profiler(TRUE);
    }

    function example_test()
    {
        $test = 1 + 1;

        $expected_result = 2;

        $test_name = 'Adds one plus one';

        $this->unit->run($test, $expected_result, $test_name);
    }

    function test_catalog_search()
    {
        $params=array(
            //'study_keywords'=>'education',
            'variable_keywords'=>'health',
            /*'variable_fields'=>$this->input->get_post('vf'),
            'countries'=>$this->input->get_post('country'),
            'topics'=>$this->input->get_post('topic'),
            'from'=>$this->input->get_post('from'),
            'to'=>$this->input->get_post('to'),
            'sort_by'=>$this->input->get_post('sort_by'),
            'sort_order'=>$this->input->get_post('sort_order'),
            'repo'=>$this->input->get_post('repo')*/
        );

        $surveyid=456;

        $this->load->library('catalog_search',$params);

        //search result
        $test=$this->catalog_search->v_quick_search($surveyid);

        //result should be an array
        $expected_result='is_array';

        $test_name='catalog study and variable keyword search';

        $this->unit->run($test, $expected_result,$test_name);
    }


    //does not work
    function test_catalog_search_variable()
    {
        //variable search
        $params=array(
            'variable_keywords'=>'health',
            'view'=>'v'
        );

        $this->load->library('catalog_search',$params);
        $test=$this->catalog_search->vsearch($limit=10,$offset=0);

        var_dump($test);

        //result should be an array
        $expected_result='is_array';

        $test_name='catalog variable search';

        $this->unit->run($test, $expected_result,$test_name);

    }

    function index()
    {
        echo "unit tests for catalog";

        $this->example_test();
        $this->test_catalog_search();
        $this->test_catalog_search_variable();

        echo $this->unit->report();


    }
}