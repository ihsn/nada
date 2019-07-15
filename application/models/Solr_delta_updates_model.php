<?php
class Solr_delta_updates_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
		//$this->output->enable_profiler(TRUE);
    }

    function apply_updates($table, $delta_op, $obj_id, $is_processed)
    {
      $options=array(
          'obj_id'=>$obj_id,
          'obj_type'=>$table,
          'obj_delta'=>$delta_op,
          'is_processed'=>$is_processed
      );

      $this->insert($options);
    }





  	function insert($options)
  	{
    		//allowed fields
    		$valid_fields=array(
    						'obj_id',
    						'obj_type',
    						'obj_delta',
                'is_processed'
    						);

    		$data=array();

    		foreach($options as $key=>$value)
    		{
    			if (in_array($key,$valid_fields) )
    			{
    				$data[$key]=$value;
    			}
    		}

    		$result=$this->db->insert('solr_delta_updates', $data);

    		if ($result===false)
    		{
    			throw new MY_Exception($this->db->_error_message());
    		}

    		return TRUE;
  	}
}
?>