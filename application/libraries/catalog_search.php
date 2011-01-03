<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//decides which database search driver to load 
$ci=& get_instance();
$driver=$ci->db->dbdriver;

switch($driver)
{
	case 'postgre';
		include dirname(__FILE__).'/catalog_search_postgre.php';
		break;
	case 'sqlsrv';
		include dirname(__FILE__).'/catalog_search_sqlsrv.php';
		return;
		break;
	case 'mysql';
		include dirname(__FILE__).'/catalog_search_mysql.php';
		return;
		break;
}

/* End of file Catalog_search.php */
/* Location: ./application/libraries/Catalog_search.php */