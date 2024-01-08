<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Datacite_citation Class
 *
 * Download citations via Datacite API
 *
 */

class Datacite_citation
{
	private $ci;

	/**
	 * Constructor - Initializes and references CI
	 */
	function __construct()
	{
		log_message('debug', "Datacite_citation Class Initialized.");
		$this->ci =& get_instance();
	}

	function export($sid=null,$format='ris')
	{
		try{
			if (!$sid){
				throw new Exception("MISSING_PARAMETERS");
			}

			$this->ci->load->model("Dataset_model");

			$formats=array(
				'ris'=>'application/x-research-info-systems',
				'bib'=>'application/x-bibtex',
				'xml'=>'application/xml',
				'json'=>'application/json',
				'rdf'=>'application/rdf+xml',
				'rdf_turtle'=>'text/turtle',
				'txt'=>'text/x-bibliography',
			);

			$survey=$this->ci->Dataset_model->get_row($sid);
			$doi=$survey['doi'];

			if (!$doi){
				throw new Exception("DOI_NOT_FOUND");
			}

			if (!isset($formats[$format])){
				throw new Exception("FORMAT_NOT_SUPPORTED. Valid formats are: ".implode(", ",array_keys($formats)).".");
			}

			//get citation from crossref
			$stream_options = [
				"http" => [
					"method" => "GET",
					"header" => "Accept: " . $formats[$format] . "\r\n"
				]
			];

			$context = stream_context_create($stream_options);
			$response = file_get_contents($doi, false, $context);
			$filename=$survey['idno'].'-citation.'.$format;

			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			//header("Content-Length: ". filesize("$filename").";");
			header("Content-Disposition: attachment; filename=$filename");
			header("Content-Type: application/octet-stream; "); 
			header("Content-Transfer-Encoding: binary");
			echo $response;
		}
		catch(Exception $e){
			header("HTTP/1.0 400");
			header("Content-Type: application/json");
			echo json_encode(
				array(
					'status'=>'error',
					'message'=>$e->getMessage()
				)
			);
			die();
		}
	}
	
}

