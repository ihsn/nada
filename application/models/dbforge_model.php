<?php
class Dbforge_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }
	
	
	function get_meta($tablename){
		$fields = $this->db->field_data($tablename);
		return $fields;
	}
	
	function get_all_tables_meta()
	{
		$tables = $this->db->list_tables();
		$tables_meta=array();
		foreach ($tables as $table)
		{
		   $tables_meta[$table]= $this->get_meta($table);
		}
		return $tables_meta;
	}
	

}
?>