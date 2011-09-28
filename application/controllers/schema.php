<?php
class Schema extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct($skip_auth=TRUE);
    	$this->load->database();
	}
 
	function index()
	{	
		echo 'schema import/export';
	}
	
	function import()
	{
		$table_cols=$this->db->query("desc surveys")->result_array();
		//echo '<pre>';		
		//var_dump($table_cols);
		
		echo '<schema>';
		echo '<table name="surveys">';
		//individual column
		foreach($table_cols as $col)
		{
			printf('<column name="%s" type="%s" isnull="%s"/>'."\r\n",$col['Field'],$col['Type'],$col['Null']);
		}
		echo '<table>';
		echo '</schema>';		
	}
	
}
/* End of file test.php */
/* Location: ./controllers/test.php */