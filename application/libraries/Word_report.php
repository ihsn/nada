<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class mpdf
{
	var $output_file='';
	
	function mpdf($output_filename)
	{
		$this->output_file=$output_filename;
	}
	
	public function WriteHTML($content)
	{
		file_put_contents($this->output_file, $content, FILE_APPEND);
	}
	
	public function AddPage()
	{
		//$content='<div style="page-break-before: always;"></div>';
		//file_put_contents($this->output_file, $content, FILE_APPEND);
	}
	
	public function Bookmark(){}
	public function Output()
	{
		//include $this->output_file;
	}
}


/**
 * DDI to Word documentation
 * 
 *
 *
 * @package		NADA 3.0
 * @subpackage	Libraries
 * @author		Mehmood
 * @link		-
 *
 */
class Word_Report{
	
	var $ci;
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->helper('xslt_helper');
    }
	
	
	/**
	*
	* Generate PDF report from DDI
	*
	* @survey_options 	array	{header_title, titl, nation,proddate, refno, producer, sponsor}
	**/
	function generate($output_filename, $ddi_file, $survey_data)
	{		
		$mpdf=new mPDF($output_filename);

		//add stylesheet
		$stylesheet = @file_get_contents('themes/ddibrowser/ddi.css');
		$stylesheet.=' html,*{font-family:arial;}';
		$mpdf->WriteHTML('<html><head><style>'.$stylesheet.'</style></head>');
		
		//add cover page
		$coverpage=$this->ci->load->view('ddibrowser/coverpage',$survey_data,TRUE);		
		$mpdf->AddPage();
		$mpdf->Bookmark("cover",0);
		$mpdf->WriteHTML( $coverpage );
				
		$mpdf->AddPage();
		$mpdf->Bookmark("Overview",0);
		$mpdf->WriteHTML( $this->get_section($ddi_file,"overview") );
				
		$mpdf->AddPage();
		$mpdf->Bookmark("Sampling",1);
		$mpdf->WriteHTML( $this->get_section($ddi_file,"sampling") );
		
		$mpdf->AddPage();
		$mpdf->Bookmark("Questionnaires",1);
		$mpdf->WriteHTML( $this->get_section($ddi_file,"questionnaires") );
		
		$mpdf->AddPage();
		$mpdf->Bookmark("Data collection",1);
		$mpdf->WriteHTML( $this->get_section($ddi_file,"datacollection") );
		
		$mpdf->AddPage();
		$mpdf->Bookmark("Data processing",1);
		$mpdf->WriteHTML( $this->get_section($ddi_file,"dataprocessing") );
		
		$mpdf->AddPage();
		$mpdf->Bookmark("Data appraisal",1);
		$mpdf->WriteHTML( $this->get_section($ddi_file,"dataappraisal") );
		
		$mpdf->AddPage();
		$mpdf->Bookmark("File description",0);
		$mpdf->WriteHTML( "<h1>File Description</h1>");
		
		$datafiles_str=$this->get_section($ddi_file,"datafiles-list");
		$start_pos=strpos($datafiles_str,'{START}');
		$datafiles_str=substr($datafiles_str,$start_pos);
		$datafiles_str=str_replace('{START}','',$datafiles_str);
		$datafiles_arr=explode('{BR}',$datafiles_str);

		$mpdf->AddPage();
		$mpdf->Bookmark("Variable List",0);
		$mpdf->WriteHTML( "<h1>Variable List</h1>");
		
		foreach($datafiles_arr as $value)
		{
			$value=trim($value);
			if ($value!="")
			{
				//explode datafile id,name
				$value_arr=explode("=",$value);
				$value_arr[1]=str_replace('.NSDstat','',$value_arr[1]);
				$mpdf->AddPage();
				$mpdf->Bookmark($value_arr[1],1);
				//$mpdf->WriteHTML( "TODO");
				$mpdf->WriteHTML( $this->get_section($ddi_file,"variable-list", $value_arr[0]) );
			}	
			set_time_limit(0);
		}
		
		//print each variable details
		$mpdf->AddPage();
		$mpdf->Bookmark("Variable Description",0);
		
		foreach($datafiles_arr as $value)
		{
			$value=trim($value);
			if ($value!="")
			{
				//explode datafile id,name
				$value_arr=explode("=",$value);
				$value_arr[1]=str_replace('.NSDstat','',$value_arr[1]);
				$mpdf->AddPage();
				$mpdf->Bookmark($value_arr[1],1);
				//$mpdf->WriteHTML( "TODO");
				$mpdf->WriteHTML( $this->get_section($ddi_file,"datafile-vars-detail", $value_arr[0]) );
			}	
			set_time_limit(0);
		}
		
		$mpdf->Output($output_filename,"F");
	}		


	function get_section($xml, $section, $param1=NULL)
	{
		$params=array();
		
		switch($section)
		{
			case 'overview':
				$xslt='xslt/ddi_overview.xslt';
			break;
			
			case 'sampling':
				$xslt='xslt/ddi_sampling.xslt';		
			break;
	
			case 'questionnaires':
				$xslt='xslt/ddi_questionnaires.xslt';		
			break;
	
			case 'datacollection':
				$xslt='xslt/ddi_datacollection.xslt';		
			break;
			
			case 'dataprocessing':
				$xslt='xslt/ddi_dataprocessing.xslt';		
			break;
	
			case 'dataappraisal':
				$xslt='xslt/ddi_dataappraisal.xslt';		
			break;
		
			case 'datafiles-list':
				$xslt='xslt/ddi_datafile_list.xslt';		
			break;		
			
			case 'datafile':
				$xslt='xslt/ddi_datafile.xslt';
				$params=array('file'=>$param1);
			break;		
	
			case 'variable-list':
				$xslt='xslt/dataset_variables.xslt';
				$params=array('file'=>$param1);
				//return "skipped";
			break;		
	
			case 'datafile-vars-detail':
				$xslt='xslt/ddi_datafile_variables.xslt';
				$params=array('file'=>$param1);
			break;				
		}
		
		//language
		$language=array('lang'=>$this->ci->config->item("language"));
		
		if(!$language)
		{
			//default language
			$language=array('lang'=>"english");
		}	
		
		//get the xml translation file path
		$language_file=$this->ci->DDI_Browser->get_language_path($language['lang']);
		
		if ($language_file)
		{
			//change to the language file (without .xml) in cache
			$language['lang']=unix_path(FCPATH.$language_file);
		}		

		//add language to params
		$parameters=array_merge( array('lang'=> $language['lang']), $params);
		
		$output= xsl_transform($xml,$xslt,$parameters);
		
		//$output=str_replace('php-survey-id',$surveyid, $output);
		$output=str_replace('<table ','<table repeat_header="1" ', $output);
		return html_entity_decode(url_filter($output));
	}
	
}// END WORD_Report Class

/* End of file WORD_Report.php */
/* Location: ./application/libraries/WORD_Report.php *////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////