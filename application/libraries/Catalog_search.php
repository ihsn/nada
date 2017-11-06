<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
class Catalog_search
{

    private $search_obj=null;

    function __construct()
    {
        //decides which database search driver to load
        $ci =& get_instance();
        $driver = $ci->db->dbdriver;

        switch ($driver) {
            case 'postgre';
                include dirname(__FILE__) . '/catalog_search_postgre.php';
                break;
            case 'sqlsrv';
                include dirname(__FILE__) . '/catalog_search_sqlsrv.php';
                return;
                break;
            case 'mysql';
                require_once dirname(__FILE__) . '/catalog_search_mysql.php';
                $this->search_obj= new Catalog_search_mysql;
                return;
                break;
        }
    }
}
*/


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

    function v_quick_search($surveyid=NULL,$limit=50,$offset=0){
        return $this->search_obj->v_quick_search($surveyid,$limit,$offset);
    }

}

/* End of file Catalog_search.php */
/* Location: ./application/libraries/Catalog_search.php */
