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

        //which db driver to use
        $ci =& get_instance();
        $driver = $ci->db->dbdriver;

        //default/base search class
        require_once dirname(__FILE__) . '/Catalog_search_mysql.php';

 
        $search_provider=$ci->config->item('search_provider');

        //SOLR
        if ($search_provider=='solr'){
            $driver='solr';
        }

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
