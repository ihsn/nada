<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * DDI Parser Class
 * 
 * uses the XML READER class for memory efficient parsing of the DDI Files.
 * The variables can be read in Chunks for avoiding higher memory 
 * consumption. 
 *
 * For backward compatibility, DOM based method is provided which can be
 * very slow for larger DDI files with more than 1000 variables. To use the 
 * DOM based method, simply set the use_xml_reader=FALSE.
 *
 * Usage:
 * 
 * $parserObj->ddi_file='some file.xml';//set the file
 * $parserObj->parse(); //returns an array of study and variables
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @category	DDI Parser
 * @author		Mehmood
 * @link		-
 *
 */
class DDI_Parser{
    
	var $ddi_file;
	var $use_xml_reader=TRUE;
	
	//List of study fields in the order returned by the XSLT
	var $fields_study=array('id', 
							'titl', 
							'titlstmt',
							'authenty',
							'geogcover',
							'nation',
							'topic',
							'scope',
							'sername',
							'producer',
							'refno',
							'proddate',
							'sponsor'
							);
	
	
    //constructor
	function DDI_Parser()
	{
		$CI =& get_instance();
		$CI->load->helper('xslt_helper');
    }

	/**
	* validate DDI file
	*
	* @return boolean
	*/
	function validate()
	{
		//read the study and document description to validate the DDI
		$study=$this->get_study_array();

		if ($study===NULL)
		{
			return false;
		}
		
		//basic validation: checks for @ID,TITL and REFNO values
		if ($study['id']!='' && $study['titl']!='')
		{
			return true;
		}
		else
		{
			return false;
		}	
	}

	/**
	 * Parse DDI file into an array
	 *
	 * @return array
	 **/		
	function parse(){
		
		if ($this->use_xml_reader===TRUE)
		{
			//use xml reader
			$output['study']=$this->get_study_array();//study description
			$output['variables']=$this->get_variables_array(); //variables array
			return $output;			
		}
		else
		{
			//DOM based XSLT transform - consumes lots of memory
			return $this->parse_using_dom();			
		}
		
	}
	
	/**
	 * Import DDI Study/Document Description into an Array
	 * using the xml reader
	 *
	 * @return array
	 **/
	function get_study_array()
	{
		//initialize the reader	
		$reader = new XMLReader();

		//read the xml file
	    if(!$reader->open($this->ddi_file))
		{ 
			print "can't open file";
			return false;
		}
		
		$output=array();
		
		$codebook_name='codeBook';
		$doc_dscr='docDscr';
		$stdy_dscr='stdyDscr';
		
		//read only the DDI docDscr and stdyDscr sections 
		while ($reader->read() ) 
		{
			if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == "codeBook") 
			{
				$codebook_name=$reader->name;
				
				//read the codeBook attributes		
				$output['ID']= $reader->getAttribute ('ID');
				$output['xmlns']= $reader->getAttribute ('xmlns');
				$output['version']=$reader->getAttribute ('version');				
			}
			else if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == "docDscr") 
			{
				$doc_dscr=$reader->name;
				$output[$doc_dscr]=$reader->readOuterXML();
			}
			else if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == "stdyDscr") 
			{
				$stdy_dscr=$reader->name;
				$output[$stdy_dscr]=$reader->readOuterXML();
				break;
			}
		}				
		$reader->close();
		
		//basic DDI validation
		if (!isset($output['ID']) || !isset($output['version']))
		{
			return NULL;
		}
		
		//build an xml file with the study/doc elements only
		$xml='<'.$codebook_name.' 	xmlns="http://www.icpsr.umich.edu/DDI" 
									xmlns:ddi="http://www.icpsr.umich.edu/DDI"
									xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
						ID="'.$output['ID'].'" version="'.$output['version'].'" >';
		$xml.=$output[$doc_dscr];
		$xml.=$output[$stdy_dscr];
		$xml.='</'.$codebook_name.'>';
		
		//transform the xml to flattened xml format
		$xslt=APPPATH.'../xslt/study_parser.xslt';
		$output=xsl_transform($xml,$xslt,$parameters=NULL, $format="xml");

		//var_dump($output);exit;
		//cleanup the output for whitespaces,xml header
		//$output=trim(str_replace('<?xml version="1.0" encoding="UTF-8"? >','',$output));
		//$output=str_replace("\n",' ',$output);
		
		//read into SIMPLE XML
		$xmlObj = simplexml_load_string($output);
		
		//convert to array
		$result = $this->objects_to_array($xmlObj);
		
		$output=array();
		foreach($result as $key=>$value)
		{
			if (is_array($value) && count($value)==0)
			{
				$output[$key]='';
			}
			else
			{
				$output[$key]=$value;
			}
		}
		return $output;
	}
	
	
	/**
	 * Import DDI Study/Document/Variables Description into an Array
	 * using the XSLT. 
	 *
	 * NOTE: disabled because the import should return simple xml instead of plain text. need tobe updated
	 * 
	 * @return array
	 **/
	function parse_using_dom()
	{
		echo 'parse_using_dom::FUNCTION DISABLED';exit;
		$xslt=FCPATH.'xslt/ddi2sql.xslt';
		$output=xsl_transform($this->ddi_file,$xslt,$parameters=NULL, $format="xml");		
		$output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$output);
		
		if ($output=='' || strlen($output) <300)
		{
			return NULL;
		}
		
		//explode variables and study description
		$section=explode('{SECTION-BREAK}',$output);

		//get study description		
		$result['study']=explode('{TAB}',$section[0]);
		
		//get variables
		$result['variables']=explode('{LN}',$section[1]);
		
		//convert variables into an array
		foreach($result['variables'] as $key=>$value)
		{
			$result['variables'][$key]=explode('{TAB}',$value);
		}
		
		//not sure if this saves any memory
		//TODO: remove unset
		unset($section);
		unset($output);
		
		return $result;
	}
	
	/**
	* get variables array
	* offset - starting point
	* limit - number of var to read
	*/	
	
	/**
	 * Import DDI variables into an Array
	 * using the xml reader
	 *
	 * @param	integer
	 * @param	integer
	 * @return 	array
	 **/
	function get_variables_array($offset=0,$limit=0)
	{	
		//initialize the reader	
		$reader = new XMLReader();

		//read the xml file
	    if(!$reader->open($this->ddi_file))
		{ 
			print "can't open file";
			return false;
		}
		
		$k=0;
		$aggregate_count=50;//combine variables xml to create a mini-xml file for transformation
		$variables=array(); //final result of the transform into an array of variables
		$var_xml=array(); //temp. holder for variable xml
				
		while ($reader->read() ) 
		{
			if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == "var") 
			{										
				//start from N variable
				if ($offset>0)
				{
					if ($k>=$offset)
					{
						//load into an array
						$var_xml[]=$reader->readOuterXML();
					}
				}
				else
				{
						//load into an array
						$var_xml[]=$reader->readOuterXML();					
				}
																
				//Transform aggregated variables
				if (count($var_xml) >=$aggregate_count)
				{
					//transform to CSV format
					$tmp_var_array=$this->_transform_var( '<codeBook>'.implode('',$var_xml).'</codeBook>' );
					
					//merge arrays					
					$variables=array_merge($variables,$tmp_var_array);
					
					//unset var xml count
					$var_xml=array();
				}												
				$k++;				
			}
			
			if ($limit>0 && $offset===0)
			{
				if ($k>=$limit)
				{
					break;
				}
			}
			else if ($limit>0 && $offset>0)
			{
				if ($k>=$limit+$offset)
				{
					break;
				}				
			}
		}
		
		$reader->close();
		
		//Transform aggregated variables
		if (count($var_xml) >0)
		{
				$tmp_var_array=$this->_transform_var( '<codeBook>'.implode('',$var_xml).'</codeBook>' );
				
				//merge arrays					
				$variables=array_merge($variables,$tmp_var_array);
		}

		return $variables;
    }//end-function

		
	//transforms a single/multiple variable xml to string array
	function _transform_var($xml)
	{
		$xslt=APPPATH.'../xslt/var_to_array.xslt';		
		$output=xsl_transform($xml,$xslt,$parameters=NULL, $format="xml");
		
		//remove xml/utf header
		$output=trim(str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$output));

		//get rows of variables	
		$variables=explode('{LN-BR}',$output);

		$result=array();
		
		foreach($variables as $variable)
		{
			if ( trim($variable)!='')
			{
				$result[]=explode('{CL-BR}',$variable);
			}	
		}		
		return $result;		
	}
	
	
	/*	
	//array of variable xml data
	function _put_parsed_variables($var_xml,$file_path)
	{
		//get an array of variables
		$variables=$this->_transform_var( '<codeBook>'.implode('',$var_xml).'</codeBook>' );
		
		$csv_data='';
		
		//iterate variables
		foreach($variables as $variable)
		{
			//process variable row
			foreach($variable as $value)		
			{
				$csv_data .= '"'.$value.'",';
			}
			$csv_data .= "\015\012";
		}
		
		//write to a file for import
		file_put_contents($file_path,$csv_data,FILE_APPEND);		
	}
	*/

	/**
	* Converts Objects into Array
	*
	* @link: http://php.net/manual/en/book.simplexml.php
	*/
	function objects_to_array($arrObjData, $arrSkipIndices = array())
	{
		$arrData = array();
		
		// if input is object, convert into array
		if (is_object($arrObjData)) {
			$arrObjData = get_object_vars($arrObjData);
		}
		
		if (is_array($arrObjData)) {
			foreach ($arrObjData as $index => $value) {
				if (is_object($value) || is_array($value)) {
					$value = $this->objects_to_array($value, $arrSkipIndices); // recursive call
				}
				if (in_array($index, $arrSkipIndices)) {
					continue;
				}
				$arrData[$index] = $value;
			}
		}
		return $arrData;
	}

}
// END DDI Parser Class

/* End of file DDI_Parser.php */
/* Location: ./application/libraries/DDI_Parser.php */