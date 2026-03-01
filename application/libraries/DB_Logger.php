<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Database logger
 * 
 * Log entries in database
 *
 *
 *
 *
 * @package		NADA 2.1
 * @subpackage	Libraries
 * @category	logging
 * @author		Mehmood
 * @link		-
 *
 */
class DB_Logger{    	
	
	var $ci;
	protected $event_logger;
	
    //constructor
    function __construct()
    {
        // Skip initialization in CLI mode
        if (php_sapi_name() === 'cli') {
            return;
        }
        
        $this->ci =& get_instance();
		$this->ci->config->load("bots");
		$this->ci->load->library('Event_Logger');
		$this->event_logger = $this->ci->event_logger;
    }

	/**
	* log
	*
	*	@type		search,survey,login,logout,register,forgot-pass,reset-pass,change-pass
	*	@section	ddibrowser sections (overview, sampling,datafile,download,public-form,direct-form)
	*
	* @return boolean
	* 
	* @deprecated Use Event_Logger methods directly:
	*   - Analytics: $this->event_logger->log_pageview() or log_download()
	*   - Search: $this->event_logger->log_search()
	*   - Audit: $this->event_logger->log_audit() or log()
	*/
	function write_log($type, $message=NULL, $section=NULL,$surveyid=0)
	{
		// Route to Event_Logger based on event type
		
		// Handle search events
		if ($type === 'search' || $type === 'api-search') {
			return $this->event_logger->log_search($message, $section, null, array('surveyid' => $surveyid));
		}
		
		// Handle analytics events (should use specific methods, but route for backward compat)
		if ($type === 'pageview' || $type === 'survey') {
			if ($surveyid) {
				return $this->event_logger->log_pageview($surveyid);
			}
			// If no surveyid, log as audit
			return $this->event_logger->log_audit($type, array(
				'keyword' => $message,
				'section' => $section,
				'surveyid' => $surveyid
			));
		}
		
		if ($type === 'download') {
			if ($surveyid && $message) {
				return $this->event_logger->log_download($surveyid, $message, array('file_type' => $section));
			}
			// If missing data, log as audit
			return $this->event_logger->log_audit($type, array(
				'keyword' => $message,
				'section' => $section,
				'surveyid' => $surveyid
			));
		}
		
		// Everything else is an audit event
		return $this->event_logger->log_audit($type, array(
			'keyword' => $message,
			'section' => $section,
			'surveyid' => $surveyid
		));
	}
	
	
	/**
	 * Check if user agent is a bot
	 * 
	 * @deprecated Use $this->event_logger->is_bot() instead
	 */
	function is_bot($agent=NULL)
	{
		return $this->event_logger->is_bot($agent);
	}
	
	
	/**
	 * Increment study view count
	 * 
	 * @deprecated Counters are now updated asynchronously from analytics aggregates.
	 * This method is a no-op. Counters are synced from analytics_monthly_studies table
	 * via background job/cron. Use Analytics_model::get_study_totals() for current counts.
	 */
	function increment_study_view_count($study_id)
	{
		// No-op: Counters updated asynchronously from analytics aggregates
		return true;
	}
	
	/**
	 * Increment study download count
	 * 
	 * @deprecated Counters are now updated asynchronously from analytics aggregates.
	 * This method is a no-op. Counters are synced from analytics_monthly_studies table
	 * via background job/cron. Use Analytics_model::get_study_totals() for current counts.
	 */
	function increment_study_download_count($study_id)
	{
		// No-op: Counters updated asynchronously from analytics aggregates
		return true;
	}
	
	
}// END DB_Logger

/* End of file DB_Logger.php */
/* Location: ./application/libraries/DB_Logger.php */