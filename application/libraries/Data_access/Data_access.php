<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Data_access extends CI_Driver_Library {

	protected $valid_drivers 	= array(
		'public',
		'direct',
		'licensed',
		'remote',
		'enclave',
		'open'
	);
	
	protected $_adapter			= 'enclave';


	/**
	 * Constructor
	 *
	 * @param array
	 */
	public function __construct($config = array())
	{
		if ( ! empty($config))
		{
			$this->_initialize($config);
		}
	}

	
	/**
	 * Initialize
	 *
	 * Initialize class properties based on the configuration array.
	 *
	 * @param	array 	
	 * @return 	void
	 */
	private function _initialize($config)
	{        
		$default_config = array(
				'adapter'
			);

		foreach ($default_config as $key)
		{
			if (isset($config[$key]))
			{
				$param = '_'.$key;

				$this->{$param} = $config[$key];
			}
		}
	}
	
	/**
	 * Is the requested driver supported in this environment?
	 *
	 * @param 	string	The driver to test.
	 * @return 	array
	 */
	public function is_supported($driver)
	{
		if ( in_array($driver,$this->valid_drivers))
		{
            return TRUE;
		}

		return FALSE;
	}
	
	
	function process_form($sid,$user=FALSE)
	{
        return $this->{$this->_adapter}->process_form($sid,$user);
	}
	
}