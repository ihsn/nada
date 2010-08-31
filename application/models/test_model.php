<?php
function __autoload($class_name) {
    require_once $class_name.EXT;
}
//OR use
//require_once dirname(__FILE__).'/advanced_search_model.php';
//require_once(BASEPATH.'libraries/Model'.EXT);
class Test_model extends Advanced_search_model {
 
    public function __construct()
    {
		echo 'loaded';
	   // parent::__construct();
		//$this->output->enable_profiler(TRUE);
		//$this->load->library('cache');
    }

    public function factory($type)
    {
		return new $type;
	}

}



class Nada_Search
{
    // The parameterized factory method
    public static function factory($type)
    {
      /*
	    if (include_once 'Drivers/' . $type . '.php') {
            $classname = 'Driver_' . $type;
            return new $classname;
        } else {
            throw new Exception('Driver not found');
        }
    */
	return new $type;
	}
}


//$mysql = Nada_Search::factory('MySQL');

// Load a SQLite Driver
//$sqlite = Nada_Search::factory('SQLite');



//abstract class
interface iNada_Search
{
	function search();
	function vsearch();
	function vsearch_quick();
}

//for each database type create a class
class mysql implements iNada_Search
{
	function search(){echo "searching with mysql";}
	function vsearch(){}
	function vsearch_quick(){}
}

class mssql implements iNada_Search
{
	function search(){echo "searching with mssql";}
	function vsearch(){}
	function vsearch_quick(){}
}


?>	