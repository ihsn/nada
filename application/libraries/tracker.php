<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 


/**
 * Site visits tracking class
 *
 * tracks site visits
 *
 * @category	Libraries
 * @author		Mehmood Asghar
 * @link		
 * @license		GPL
 */

require_once(APPPATH."../modules/omniture/OmnitureMeasurement.php");

class Tracker //extends OmnitureMeasurement
{
	private $ci;

	/**
	 * Constructor - Initializes and references CI
	 */
	function __construct()
	{
		log_message('debug', "Tracker Class Initialized.");
		$this->ci =& get_instance();
		
		//parent::__construct();
	}

	//track page
	function track()
	{
		//Instantiate instance
		$s = new OmnitureMeasurement();
		//Setup application config variables
		$s->account = 'DEVWBTSTSAMP3';
		//$s->mobile = false;
		
		$s->pageName='DDP Microdata > ';
		
		$uri=strip_tags($_SERVER["REQUEST_URI"]);
		$q_pos=strpos($uri,"?");
		
		if ($q_pos!=FALSE)
		{
			$uri=substr($uri,0,$q_pos);
			$uri=str_replace("?","",$uri);
		}
		
		$uri=explode('/',$uri);
		if (in_array('ddibrowser',$uri))
		{
			$s->pageName.='DDI Browser';
			
			$pos = array_search('ddibrowser', $uri);
			$k=0;
			foreach($uri as $str)
			{
				if ($k>$pos)
				{
					$s->pageName.=' > '.$str;
				}
				$k++;
			}
			
			$s->prop1 = 'DDI Browser';//clears any previously set value for prop1	
		}
		else if (in_array('search',$uri))
		{
			$allowed=array('sk','vk','view','dtype','from','to','page','nation');
			
			$output='';
			foreach($allowed as $key)
			{
				$data=$this->ci->input->get($key);
				if ($data)
				{	
					if (is_array($data))
					{
						$output.=$key.'/'.implode(",",$this->ci->input->get($key)).'/';
					}
					else
					{
						$output.=$key.'/'.$this->ci->input->get($key).'/';
					}	
				}
			}
					
			$s->pageName.='Search '.$output;
			$s->prop1 = 'Search/'.$output;
		}
		
		//echo $s->pageName;exit;
		$s->channel = 'DEC DDP Microdata Catalog';//clears any previously set value for channel
		
		$s->track();

	}
		
}
