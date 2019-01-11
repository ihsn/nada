<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EndNote tagged format Import/export class [alpha]
 * 
 * 
 *
 *
 * @subpackage	Libraries
 * @category	EndNote import/export
 * @format		Refer/BibIX
 *
 * @resouces
 * Refer/BibIX file format	http://the-database.org/bibex.htm, EndNote 9 Manual PDF [pages around 173]
 */ 
class EndNote{
    					
		var $entry_types=array(
			'generic'=>'book',
			'government document'=>'report',
			'journal article'=>'journal',
			'book'=>'book', 
			'magazine article'=>'magazine',
			'book section'=>'book-section', 
			'online database'=>'website',
			'conference paper'=>'conference-paper', 
			'conference proceedings'=>'conference-paper',
			'edited book'=>'book',
			'report'=>'report',
			'electronic article'=>'journal', 
			'electronic book'=>'book',
			'thesis'=>'thesis',
			'electronic source'=>'website', 
			'unpublished work'=>'journal'
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
			if (trim($line)!="")
			{
				//$tmp.=$line.PHP_EOL;
				if (isset($entries[$entry]))
				{				
					$entries[$entry].=trim($line).PHP_EOL;
				}
				else
				{
					$entries[$entry]=trim($line).PHP_EOL;
				}	
			}
			else //blank line found
			{
				//if (trim($tmp)!='')
				//{
					//$entries[$entry]=trim($tmp);
					//$tmp='';
					$entry++;
				//}
			}
		}
		$citations_array=array();
		
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
			
			//find position of first space
			$pos=strpos($line," ");
			
			//skip parsing if no space found
			if (!$pos)
			{
				continue;
			}
			
			$tag=trim(substr($line,0,$pos));
			$value=trim(substr($line,$pos));
			
			switch ($tag)
			{
					case ('%A'):// Author
						$author_array=$this->parse_author($value);
						if ($author_array)
						{
							$entry['authors'][]=$author_array;
						}	
						break;
					
					//case ('%B'): //Secondary Title (of a Book or Conference Name)
					case ('%C'): // Place Published
						$entry['place_publication']=$value;
						break;
					
					case ('%D'): // Year
						$entry['pub_year']=$value;
						break;
					
					case ('%E'): // Editor /Secondary Author
						$entry['editors'][]=$value;
						break;
					
					//case ('%F'): // Label
					//case ('%G'): // Language
					//case ('%H'): // Translated Author
					case ('%I'): // Publisher
						$entry['publisher']=$value;
						break;
					
					case ('%J'): // Secondary Title (Journal Name)
						$entry['subtitle']=$value;
						break;
		
					case ('%K'): // Keywords
						$entry['keywords']=$value;
						break;
					//case ('%L'): // Call Number
					//case ('%M'): // Accession Number
					case ('%N'): // Number (Issue)
						$entry['issue']=$value;
						break;
		
					case ('%P'): // Pages
						$pages_array=$this->parse_pages($value);
						$entry['page_from']=$pages_array['page_from'];
						$entry['page_to']=$pages_array['page_to'];
						break;
					
					//case ('%Q'): // Translated Title
					//case ('%R'): // Electronic Resource Number
					//case ('%S'): // Tertiary Title
					case ('%T'): // Title
						$entry['title']=$value;
						break;
		
					case ('%U'): // URL
						$entry['url']=$value;
						break;
					
					case ('%V'): // Volume
						$entry['volume']=$value;
						break;
		
					//case ('%W'): // Database Provider
					case ('%X'): // Abstract
						$entry['abstract']=$value;
						break;
					
					//case ('%Y'): // Tertiary Author
					case ('%Z'): // Notes
						$entry['notes']=$value;
						break;					
					case ('%0'): // Reference Type
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
					//case ('%1'): // Custom 1
					//case ('%2'): // Custom 2
					//case ('%3'): // Custom 3
					//case ('%4'): // Custom 4
					//case ('%6'): // Number of Volumes
					case ('%7'): // Edition
						$entry['edition']=$value;
						break;
					
					//case ('%8'): // Date
					//case ('%9'): // Type of Work
					//case ('%?'): // Subsidiary Author
					case ('%@'): // ISBN/ISSN
						$entry['idnumber']=$value;
						break;
					
					//case ('%!'): // Short Title
					//case ('%#'): // Custom 5
					//case ('%$'): // Custom 6
					//case ('%]'): // Custom 7
					//case ('%&'): // Section
					//case ('%('): // Original Publication
					//case ('%)'): // Reprint Edition
					//case ('%*'): // Reviewed Item
					//case ('%+'): // Author Address
					//case ('%^'): // Caption
						
					case ('%>'): // Link to PDF
						$entry['url']=$value;
						break;
					
					//case ('%<'): // Research Notes
					case ('%['): // Access Date
						$entry['data_accessed']=$value;
						break;
					
					case ('%='): // Last Modified Date
						$entry['changed']=$value;
						break;
					
					//case ('%~'): // Name of Database					
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