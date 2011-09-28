<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * DDI to PDF documentation
 * 
 *
 *
 * @package		NADA 3.0
 * @subpackage	Libraries
 * @author		Mehmood Asghar
 * @link		-
 *
 */
class PDF_Report{
	
	var $ci;
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
		$codepage=$this->ci->config->item("pdf_codepage");		
		$this->ci->load->library('my_mpdf',array('codepage'=>$codepage));
		$this->ci->load->helper('xslt_helper');
		$this->ci->lang->load("ddibrowser");
    }
	
	
	/**
	*
	* Generate PDF report from DDI
	*
	* @survey_options 	array	{header_title, titl, nation,proddate, refno, producer, sponsor}
	**/
	function generate($output_filename, $ddi_file, $survey_data)
	{
		$mpdf=$this->ci->my_mpdf;
		//$mpdf=new mPDF('win-1251');
		$mpdf->useOnlyCoreFonts = false;

		$stylesheet = @file_get_contents(APPPATH.'../themes/ddibrowser/ddi.css');
		$mpdf->WriteHTML($stylesheet,1);
		
		// Set a simple Footer including the page number
		$mpdf->defaultfooterfontsize = 8;	/* in pts */
		$mpdf->defaultfooterfontstyle = '';	/* blank, B, I, or BI */
		$mpdf->defaultfooterline = 0; 	/* 1 to include line below header/above footer */
		$mpdf->setFooter('{PAGENO}');
		
		//add cover page
		$coverpage=$this->ci->load->view('ddibrowser/coverpage',$survey_data,TRUE);		
		$mpdf->AddPage();
		$mpdf->Bookmark("cover",0);
		$mpdf->WriteHTML( $coverpage );

		$mpdf->defaultheaderfontsize = 8;	/* in pts */
		$mpdf->defaultheaderfontstyle = '';	/* blank, B, I, or BI */
		$mpdf->defaultheaderline = 0; 	/* 1 to include line below header/above footer */
		$mpdf->SetHeader($survey_data['report_title']);
		
		$mpdf->AddPage();
		$mpdf->Bookmark("Overview",0);
		$mpdf->WriteHTML( $this->get_section(FCPATH.$ddi_file,"overview") );

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
				$xslt=APPPATH.'../xslt/ddi_overview.xslt';
			break;
			
			case 'sampling':
				$xslt=APPPATH.'../xslt/ddi_sampling.xslt';		
			break;
	
			case 'questionnaires':
				$xslt=APPPATH.'../xslt/ddi_questionnaires.xslt';		
			break;
	
			case 'datacollection':
				$xslt=APPPATH.'../xslt/ddi_datacollection.xslt';		
			break;
			
			case 'dataprocessing':
				$xslt=APPPATH.'../xslt/ddi_dataprocessing.xslt';		
			break;
	
			case 'dataappraisal':
				$xslt=APPPATH.'../xslt/ddi_dataappraisal.xslt';		
			break;
		
			case 'datafiles-list':
				$xslt=APPPATH.'../xslt/ddi_datafile_list.xslt';		
			break;		
			
			case 'datafile':
				$xslt=APPPATH.'../xslt/ddi_datafile.xslt';
				$params=array('file'=>$param1);
			break;		
	
			case 'variable-list':
				$xslt=APPPATH.'../xslt/dataset_variables.xslt';
				$params=array('file'=>$param1);
				//return "skipped";
			break;		
	
			case 'datafile-vars-detail':
				$xslt=APPPATH.'../xslt/ddi_datafile_variables.xslt';
				$params=array('file'=>$param1);
			break;				
		}

		//needed for finding/creating the language translation file
		$this->ci->load->library("DDI_Browser");
						
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
	
}// END PDF_Report Class

/* End of file PDF_Report.php */
/* Location: ./application/libraries/PDF_Report.php *////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////