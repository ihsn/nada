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
		$this->ci->load->config('omniture');
		
		//parent::__construct();
	}

	//track page
	function track($url='',$page_title='')
	{
		//Instantiate instance
		$s = new OmnitureMeasurement();
		
		if (defined('ENVIRONMENT') && ENVIRONMENT=='production')
		{		
			//set s_account
			$s->account = $this->ci->config->item("omniture_s_account_prod");
		}
		else
		{
			//dev account
			$s->account = $this->ci->config->item("omniture_s_account_dev");
		}	
		
		//$s->mobile = false;
		
		$s->pageName=$this->ci->config->item("omniture_pagename");
		
		//set URL
		if ($url=='')
		{
			$uri=strip_tags($_SERVER["REQUEST_URI"]);
		}
		else
		{
			$uri=$url;
		}
		
		$q_pos=strpos($uri,"?");
		
		if ($q_pos!=FALSE)
		{
			$uri=substr($uri,0,$q_pos);
			$uri=str_replace("?","",$uri);
		}
		
		$uri=explode('/',$uri);
		
		//var_dump($uri);exit;
		
		if ($url!='' && $page_title!='')
		{
			$s->pageName.=$page_title;
			
			/*$pos = array_search('variable', $uri);
			$k=0;
			foreach($uri as $str)
			{
				if ($k>$pos)
				{
					$s->pageName.=' > '.$str;
				}
				$k++;
			}*/
			
			$s->prop1 = '';//reset prop1
		}
		//catalog search
		else if (in_array('catalog',$uri) && in_array('search',$uri))
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
		$s->channel = 'DEC Microdata Catalog EXT';//clears any previously set value for channel
		$s->track();
	}
	
	function track_download($url,$title)
	{
	//Instantiate instance
		$s = new OmnitureMeasurement();
				
		if (defined('ENVIRONMENT') && ENVIRONMENT=='production')
		{		
			//set s_account
			$s->account = $this->ci->config->item("omniture_s_account_prod");
		}
		else
		{
			//dev account
			$s->account = $this->ci->config->item("omniture_s_account_dev");
		}	
		
		$s->pageName=$this->ci->config->item("omniture_pagename");
		//Set Variables
		$s->channel = 'DEC Microdata Catalog EXT';
		$s->prop1 = 'download-prop1 '.$title;
		$s->debugTracking=1;
		//$s->trackLink('http://www.somedownloadURL.com', 'd', 'Some Action Name');
		$s->sendFromServer = true; 
		$s->trackLink($linkURL=$url, $linkType="d", $linkName=basename($url));
			//  $s->sendFromServer = true; 
	}
		
}
