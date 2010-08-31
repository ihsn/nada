<?php
class Reports extends MY_Controller {
 
    public function __construct()
    {
        parent::__construct();
		
		//set template to admin 
		$this->template->set_template('admin');
      	$this->load->model('Reports_model');
		
		$this->lang->load("reports");
		
		//$this->output->enable_profiler(TRUE);
    }
 
	function index()
	{	
		$this->template->set_template('admin');		
		//javascript/css needed for showing the date picker
		$this->template->add_css('themes/admin/generic.css');
		$this->template->add_css('javascript/jquery/themes/base/ui.all.css');
		$this->template->add_js('javascript/jquery/ui/ui.core.js');
		$this->template->add_js('javascript/jquery/ui/ui.datepicker.js');	
		
		//show the view with report options		
		$this->template->write_view('content', 'reports/index', NULL, TRUE);			
		$this->template->write('title', 'Reports',true);
		$this->template->render();
	}


	//reports/?from=x&to=X&report=top_keywords		
	function _remap()
	{		
	   //required
	   $report=$this->input->get("report");
	   
	   //report format
	   $format=$this->input->get("format"); //e.g. excel
	   
	   $from=$this->input->get("from");//MUST be in MM/DD/YYYY format
	   $to=$this->input->get("to");//MUST be in MM/DD/YYYY format

	   $from=$this->_unix_date($from);
	   $to=$this->_unix_date($to);	   	   	   
	   	   
	   switch($report)
	   {
	   		case 'top-keywords':
				$data['rows']=$this->Reports_model->get_top_keywords($from,$to);
				
				if ($format=='excel')
				{
					$this->_export_to_excel($data['rows'],'download-detailed-'.date("m-d-y").'.xls');
					return;
				}	
				
				$this->load->view("reports/top_keywords",$data);
			break;
			
			case 'survey-summary':
				$data['rows']=$this->Reports_model->get_survey_summary($from,$to);
				
				if ($format=='excel')
				{
					$this->_export_to_excel($data['rows'],'download-detailed-'.date("m-d-y").'.xls');
					return;
				}	
				
				$this->load->view("reports/survey_summary",$data);
			break;

			case 'survey-detailed':
				$data['rows']=$this->Reports_model->get_survey_detailed($from,$to);
				
				if ($format=='excel')
				{
					$this->_export_to_excel($data['rows'],'download-detailed-'.date("m-d-y").'.xls');
					return;
				}
					
				$this->load->view("reports/survey_detailed",$data);
			break;
			
			case 'downloads-detailed':
				$data['rows']=$this->Reports_model->downloads_detailed($from,$to);
				
				if ($format=='excel')
				{
					$this->_export_to_excel($data['rows'],'download-detailed-'.date("m-d-y").'.xls');
					return;
				}	
				
				$this->load->view("reports/downloads_detailed",$data);
			break;

			case 'licensed-requests':
				$data['rows']=$this->Reports_model->licensed_requests($from,$to);
				
				if ($format=='excel')
				{
					$this->_export_to_excel($data['rows'],'licensed-requests-'.date("m-d-y").'.xls');
					return;
				}
					
				$this->load->view("reports/licensed_requests",$data);
			break;

			case 'public-requests':
				$data['rows']=$this->Reports_model->public_requests($from,$to);
				
				if ($format=='excel')
				{
					$this->_export_to_excel($data['rows'],'public-requests-'.date("m-d-y").'.xls');
					return;
				}
					
				$this->load->view("reports/public_requests",$data);
			break;
			
			case 'study-statistics':
				$data=$this->Reports_model->survey_summary_statistics();
				
				if ($format=='excel')
				{
					$output=$this->load->view("reports/study_statistics",$data,TRUE);
					$this->_export_to_excel($output,'study-statistics-'.date("m-d-y").'.xls');
					return;
				}					
				$this->load->view("reports/study_statistics",$data);
			break;
			
			case 'users-statistics':
				$data['rows']=$this->Reports_model->user_stats($from,$to);

				if ($format=='excel')
				{
					$output=$this->load->view("reports/users_statistics",$data,TRUE);
					$this->_export_to_excel($output,'users-statistics-'.date("m-d-y").'.xls');
					return;
				}					
				$this->load->view("reports/users_statistics",$data);
			break;

			case 'user-activity':
				$data['rows']=$this->Reports_model->user_activity($from,$to);

				if ($format=='excel')
				{
					$output=$this->load->view("reports/user_activity",$data,TRUE);
					$this->_export_to_excel($output,'user-activity-'.date("m-d-y").'.xls');
					return;
				}					
				$this->load->view("reports/user_activity",$data);
			break;

			default:
				$this->index();
			break;
	   }
	   
/*	   	   //validate {from,to,report}
	   $from=$this->input->get("from");//MUST be in MM/DD/YYYY format
	   $to=$this->input->get("to");//MUST be in MM/DD/YYYY format

	   $from=$this->_unix_date($from);
	   $to=$this->_unix_date($to);
*/	   
	}
	
	
	function _export_to_excel($rows,$filename)
	{
		//set header for outputing to Excel
		header("Expires: Sat, 01 Jan 1980 00:00:00 GMT");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public"); 
		header("Content-Description: File Transfer");
		
		session_cache_limiter("must-revalidate");
		header("Content-Type: application/vnd.ms-excel");
		header('Content-Disposition: attachment; filename="'.$filename.'"'); 

		if (!is_array($rows))
		{
			echo $rows;
		}
		else
		{	
			$this->load->view('reports/excel',array('rows'=>$rows));
		}	
	}
	
	
	/**
	*
	* Converts the M/D/Y formatted date to Unix timestamp or return false
	*/
	function _unix_date($date)
	{
		$tmp=str_replace("/","",$date);
		
		if (!is_numeric($tmp))
		{
			return FALSE;
		}
		
		list($month, $day,$year) = explode("/", $date);
		if (is_numeric($year) && is_numeric($month) && is_numeric($day) ){
			return date('U', mktime(0, 0, 0, $month, $day, $year));
		}
		return FALSE;
	}
	
}
/* End of file reports.php */
/* Location: ./controllers/reports.php */