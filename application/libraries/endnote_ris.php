<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EndNote RIS format Import/export class
 * 
 * 
 *
 *
 * @package		NADA 3.0
 * @subpackage	Libraries
 * @category	EndNote import/export
 * @format		RIS
 * @author		Mehmood Asghar
 * @link		http://en.wikipedia.org/wiki/RIS_(file_format)
 *
 */ 
class EndNote_RIS{
    					
		var $entry_types=array(
			'gen'=>'book',
			'rprt'=>'report',
			'jfull'=>'journal',
			'jour'=>'journal',
			'book'=>'book', 
			'mgzn'=>'magazine',
			'chap'=>'book-section', 
			'ctlg'=>'website',
			'conf'=>'conference-paper', 
			'thes'=>'thesis',
			'elec'=>'website', 
			'unpb'=>'journal',
			'ser'=>'anthology',
			);
			
	function __construct($params=NULL)
	{
	}
	
	/**
	*
	* Parse a EndNote String or File to Array
	*
	*	@string		string or filename
	*	@isfile		it is a file
	**/	
	function parse($string,$isfile=FALSE)
	{		
		if ($isfile===TRUE)
		{
			if (!file_exists($string))
			{
				$this->errors[]=t('file_not_found');
				return FALSE;
			}
			
			//load from file
			$string=file_get_contents($string);			
		}
	
		//standardize new line char across OS
		$string=str_replace(array("\n","\r","\r\n"),"\r",$string);
	
		$entries=array();
		$lines=explode("\r",$string);
		$entry=0;
		$tmp='';
		
		//read line by line and create any array of citation entries
		foreach($lines as $line)
		{
			$line=trim($line);

			if ($line=='' || strtolower(substr($line,0,2))=='er')
			{
				//echo "founD".strtolower(substr($line,0,2)).'<BR>';
				$entry++;
			}
			else// ($line!="" || !strtolower(substr($line,0,2))=='er')
			{
				if (isset($entries[$entry]))
				{				
					$entries[$entry].=$line.PHP_EOL;
				}
				else
				{
					$entries[$entry]=$line.PHP_EOL;
				}	
			}
		}
		$citations_array=array();
//echo '<pre>';
//var_dump($entries);exit;

		//process individual records and convert to array
		foreach($entries as $entry)
		{			
			$arr=$this->_parse_single($entry);
			
			if ($arr)
			{
				$citations_array[]=$arr;
			}
		}
		
		if (count($citations_array)>0)
		{
			return $citations_array;
		}
		
		return FALSE;
	}
	
	
	/**
	*
	* Parse a single EndNote string to Array
	**/
	function _parse_single($string)
	{
		$lines=explode(PHP_EOL,$string);
		$entry=array();

		foreach($lines as $line)
		{
			$line=trim($line);
			
			//find position of first dash
			$pos=strpos($line,"-");
			
			//skip parsing if no dash found
			if (!$pos)
			{
				continue;
			}
			
			$tag=trim(substr($line,0,$pos));
			$value=trim(substr($line,$pos+1));
		
			switch ($tag)
			{
				case ('TY'):  //  - Type of reference (must be the first tag)
					if (array_key_exists(strtolower($value),$this->entry_types))
					{
						$entry['ctype']=$this->entry_types[strtolower($value)];
					}
					else
					{
						//default
						$entry['ctype']='book';
					}	
					break;				
				//case ('ID'):  //  - Reference ID (not imported to reference software)
				case ('T1'):  //  - Primary title
					$entry['title']=$value;
					break;
				case ('CT'):  //  - Title of unpublished reference
					$entry['title']=$value;
					break;
				case ('A1'):  //  - Primary author
				case ('A2'):  //  - Secondary author (each name on separate line)
				case ('AU'):  //  - Author (syntax. Last name, First name, Suffix)
					$author_array=$this->parse_author($value);
					if ($author_array)
					{
						$entry['authors'][]=$author_array;
					}	
					break;
				
				case ('Y1'):  //  - Primary date
				case ('PY'):  //  - Publication year (YYYY/MM/DD)
					$date=explode("/",$value);
					if (isset($date[0]))
					{
						$entry['pub_year']=(int)$date[0];
					}
					if (isset($date[1]))
					{
						$entry['pub_month']=(int)$date[1];
					}
					if (isset($date[2]))
					{
						$entry['pub_day']=(int)$date[2];
					}
					break;				
				case ('N1'):  //  - Notes 
					$entry['notes']=$value;
					break;
				case ('KW'):  //  - Keywords (each keyword must be on separate line preceded KW -)
					$entry['keywords']=$value;
					break;				
				//case ('RP'):  //  - Reprint status (IN FILE, NOT IN FILE, ON REQUEST (MM/DD/YY))
				
				case ('SP'):  //  - Start page number
					$entry['page_from']=$value;
					break;
				case ('EP'):  //  - Ending page number
					$entry['page_to']=$value;
					break;				
				case ('JF'):  //  - Periodical full name
				case ('JO'):  //  - Periodical standard abbreviation
				case ('JA'):  //  - Periodical in which article was published
				case ('J1'):  //  - Periodical name - User abbreviation 1
				case ('J2'):  //  - Periodical name - User abbreviation 2
					$entry['subtitle']=$value;
					break;								
				case ('VL'):  //  - Volume number
					$entry['volume']=$value;
					break;								
				case ('IS'):  //  - Issue number
					$entry['issue']=$value;
					break;				
				//case ('T2'):  //  - Title secondary
				case ('CY'):  //  - City of Publication
					$entry['place_publication']=$value;
					break;				
				case ('PB'):  //  - Publisher
					$entry['publisher']=$value;
					break;								
				//case ('U1'):  //  - User definable 1
				//case ('U5'):  //  - User definable 5
				//case ('T3'):  //  - Title series
				case ('N2'):  //  - Abstract
					$entry['abstract']=$value;
					break;								
				case ('SN'):  //  - ISSN/ISBN (e.g. ISSN XXXX-XXXX)
					$entry['idnumber']=$value;
					break;								
				//case ('AV'):  //  - Availability
				//case ('M1'):  //  - Misc. 1
				case ('M3'):  //  - Misc. 3
					$entry['doi']=$value;
					break;
				//case ('AD'):  //  - Address
				case ('UR'):  //  - Web/URL
					$entry['url']=$value;
					break;								
				case ('L1'):  //  - Link to PDF
					$entry['url']=$value;
					break;								
				//case ('L2'):  //  - Link to Full-text
				//case ('L3'):  //  - Related records
				//case ('L4'):  //  - Images
				case ('ER'):  //  - End of Reference (must be the last tag)
					break;
								
			}//end-switch
		}//end-lines
		
		return $entry;		
	}
	
	/**
	*
	* Split author name into array
	*
	**/
	function parse_author($author_name)
	{
		//check if author name uses lastname,firstname format
		$comma=strpos($author_name,",");
		
		//use last,first format
		if ($comma>0)
		{
			$output['lname']=substr($author_name,0,$comma);
			$output['fname']=trim(substr($author_name,$comma+1));
			$output['initial']='';
			return $output;
		}
		
		$space=strpos($author_name," ");
		
		//use last,first format
		if ($space>0)
		{
			$output['fname']=substr($author_name,0,$space);
			$output['lname']=trim(substr($author_name,$space));
			$output['initial']='';
			return $output;
		}

		return FALSE;
	}


	/**
	*
	* Split pages string into from and to array
	*
	*	@pages string 	e.g. 100-200
	**/
	function parse_pages($pages_str)
	{
		$pages_array=explode("-",$pages_str);
		
		if (count($pages_array)>1)
		{
			$pages['page_from']=trim($pages_array[0]);
			$pages['page_to']=trim($pages_array[1]);
		}
		else
		{
			$pages['page_from']=$pages_str;
			$pages['page_to']='';			
		}
		return $pages;
	}
}
// END endnote Class

/* End of file endnote.php */
/* Location: ./application/libraries/endnote.php */