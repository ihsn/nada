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
        $valid_engines = array('database', 'solr', 'opensearch_native', 'nada_ai');

        // an explicit engine in $params (the admin search test, the variable view) wins over the site setting
        $search_engine = (isset($params['search_engine']) && in_array($params['search_engine'], $valid_engines, true))
            ? $params['search_engine']
            : $ci->search_engine_resolver->engine();

        $driver = $ci->db->dbdriver;
        if ($search_engine === 'solr') {
            $driver = 'solr';
        } elseif ($search_engine === 'opensearch_native') {
            $driver = 'opensearch';
        } elseif ($search_engine === 'nada_ai') {
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
                // the engine behind nada-ai decides the driver, and nada-ai says which it runs: OpenSearch uses nada-ai's
                // standard study search, Qdrant keeps the original semantic driver, or the one that fuses it with the
                // database search when nada_ai_combine_with_database is on. When nada-ai has never been reachable the
                // engine is not known, and the database search serves the request.
                $engine = $ci->search_engine_resolver->nada_ai_study_driver();
                if ($engine === 'opensearch') {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic_studies.php';
                    $this->search_obj= new catalog_search_semantic_studies($params);
                } elseif ($engine === 'qdrant_db') {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic_fused.php';
                    $this->search_obj= new catalog_search_semantic_fused($params);
                } elseif ($engine === 'qdrant') {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic.php';
                    $this->search_obj= new catalog_search_semantic($params);
                } elseif ($ci->db->dbdriver === 'sqlsrv') {
                    require_once dirname(__FILE__) . '/Catalog_search_sqlsrv.php';
                    $this->search_obj= new catalog_search_sqlsrv($params);
                } else {
                    $this->search_obj= new catalog_search_mysql($params);
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