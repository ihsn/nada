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
        $valid_search_providers=array('solr','db','opensearch','semantic');

        $search_provider = null;
        
        // Check if search provider is explicitly specified in params
        if (isset($params['search_provider'])) {
            if (in_array($params['search_provider'], $valid_search_providers)){
                $search_provider = $params['search_provider'];
            }
        } else {
            // Use configuration-based search provider
            $search_provider = $ci->config->item('search_provider');
        }

        $driver = null;
        
        if ($search_provider === 'db') {
            $driver = $ci->db->dbdriver;
        } elseif ($search_provider === 'solr') {
            $driver = 'solr';
        } elseif ($search_provider === 'opensearch') {
            $driver = 'opensearch';
        } elseif ($search_provider === 'semantic') {
            $driver = 'semantic';
        } else {
            $driver = $ci->db->dbdriver;
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
                //the engine behind nada-ai decides the driver: qdrant keeps the original semantic driver,
                //opensearch uses nada-ai's standard study search
                $ci->config->load('semantic_search');
                $engine = strtolower(trim((string) $ci->config->item('semantic_search_engine')));
                if ($engine === 'opensearch') {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic_studies.php';
                    $this->search_obj= new catalog_search_semantic_studies($params);
                } elseif ($engine === 'qdrant') {
                    require_once dirname(__FILE__) . '/Catalog_search_semantic.php';
                    $this->search_obj= new catalog_search_semantic($params);
                } else {
                    throw new exception(sprintf("SEMANTIC SEARCH ENGINE [%s] NOT SUPPORTED (use qdrant or opensearch)",$engine));
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