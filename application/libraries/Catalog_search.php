<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Adaptor class for catalog search
 *
 * default/base search class is catalog_search_mysql. For other databases, they all must extend the mysql class and
 * override class methods as needed.
 *
 *  class hierarchy:
 *
 *  catalog_search [adaptor] - all methods in the search classes must be defined in the adaptor
 *      -> catalog_search_mysql
 *          --> catalog_search_sqlsrv extends catalog_search_mysql
 */
class Catalog_search{

    private $search_obj;

    function __construct($params=array()){

        $ci =& get_instance();
        $ci->load->library('search_engine_resolver');
        $valid_engines = array('database', 'solr', 'opensearch', 'nada_ai_opensearch', 'nada_ai_qdrant');

        // an explicit engine in $params (the admin search test, the variable view) wins over the site setting
        $search_engine = (isset($params['search_engine']) && in_array($params['search_engine'], $valid_engines, true))
            ? $params['search_engine']
            : $ci->search_engine_resolver->engine();

        $driver = $ci->db->dbdriver;
        if ($search_engine === 'solr') {
            $driver = 'solr';
        } elseif ($search_engine === 'opensearch') {
            $driver = 'opensearch';
        } elseif ($search_engine === 'nada_ai_opensearch' || $search_engine === 'nada_ai_qdrant') {
            $driver = 'semantic';
        }

        require_once dirname(__FILE__) . '/Catalog_search_mysql.php';

        switch ($driver) {
            case 'sqlsrv';
                //extended sqlsrv class
                require_once dirname(__FILE__) . '/Catalog_search_sqlsrv.php';
                $this->search_obj= new catalog_search_sqlsrv($params);
                break;
            case 'mysql';
            case 'mysqli';
                $this->search_obj= new catalog_search_mysql($params);
                break;
            case 'solr';
                require_once dirname(__FILE__) . '/Catalog_search_solr.php';
                $this->search_obj= new catalog_search_solr($params);
                break;
            case 'opensearch';
                require_once dirname(__FILE__) . '/OpenSearch/Catalog_search_opensearch.php';
                $this->search_obj= new catalog_search_opensearch($params);
                break;
            case 'semantic';
                // nada-ai runs the engine the setting names, and says which it runs: a difference is an error, because the
                // driver and its index belong to one engine. OpenSearch uses nada-ai's standard study search; Qdrant uses
                // the driver that combines it with the database keyword search.
                $ci->search_engine_resolver->assert_backend($search_engine);
                if ($search_engine === 'nada_ai_opensearch') {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic_studies.php';
                    $this->search_obj= new catalog_search_semantic_studies($params);
                } else {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic_fused.php';
                    $this->search_obj= new catalog_search_semantic_fused($params);
                }
                break;
            default:
                throw new exception(sprintf("DRIVER [%s] NOT SUPPORTED",$driver));
        }
    }

    function search($limit=15, $offset=0)
    {
        return $this->search_obj->search($limit, $offset);
    }

    function vsearch($limit = 15, $offset = 0)
    {
        return $this->search_obj->vsearch($limit, $offset);
    }

    function v_quick_search($sid=NULL,$limit=50,$offset=0)
    {
        return $this->search_obj->v_quick_search($sid,$limit,$offset);
    }

}

/* End of file Catalog_search.php */
/* Location: ./application/libraries/Catalog_search.php */