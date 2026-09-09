<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * DDI Study Export Class
 *
 * Class for exporting study data and generating DDI via a template.
 *
 */

class DDI_Study_Export
{	
	private $ci;
	private $_DDI_template = '';
	private $_DDI;

	private $grid_keys = array(
		'{prod_s_investigator}',
		'{prod_s_acknowledgements}',
		'{prod_s_other_prod}',
		'{prod_s_funding}',
		'{contacts_contacts}',
		'{scope_keywords}',
		'{scope_class}',
		'{coll_dates}',
		'{coll_periods}',
		'{coverage_country}',
		'{coll_collectors}',
		'{impact_wb_lead}',
		'{impact_wb_members}',
		'{access_authority}',
	);

	private $cdata_keys = array(
		'{ident_ser_info}',
		'{ver_desc}',
		'{ver_notes}',
		'{overview_abstract}',
		'{coverage_geo}',
		'{overview_analysis}',
		'{coverage_universe}',
		'{scope_definition}',
		'{sampling_procedure}',
		'{sampling_dev}',
		'{coll_questionnaire}',
		'{coll_notes}',
		'{coll_supervision}',
		'{sampling_weight}',
		'{process_editing}',
		'{process_other}',
		'{sampling_rates}',
		'{appraisal_error}',
		'{appraisal_other}',
		'{access_confidentiality}',
		'{access_cite_require}',
		'{access_conditions}',
		'{disclaimer_disclaimer}',
		'{operational_wb_summary}',
		'{operational_wb_objectives}',
		'{impact_wb_description}',
	);

	private $_variables = array();

	/**
	 * Constructor 
	 *
	 */
	 
	public function __construct() {
		$this->ci =& get_instance();
		$this->_variables = $this->default_variables();
		log_message('debug', "DDI Study Export Class Initialized.");
	}
	
	/**
	 * Private Methods 
	 *
	 */
	 
	private function default_variables()
	{
		return array(
			'{ident_id}'                => ' ',
			'{ident_title}'             => ' ',
			'{ident_subtitle}'          => ' ',
			'{ident_abbr}'              => ' ',
			'{ident_trans_title}'       => ' ',
			'{prod_s_investigator}'     => ' ',
			'{prod_s_acknowledgements}' => ' ',
			'{prod_s_other_prod}'       => ' ',
			'{disclaimer_copyright}'    => ' ',
			'{prod_s_funding}'          => ' ',
			'{contacts_contacts}'       => ' ',
			'{ident_ddp_id}'            => ' ',
			'{ident_study_type}'        => ' ',
			'{ident_ser_info}'          => ' ',
			'{ver_prod_date}'           => ' ',
			'{ver_desc}'                => ' ',
			'{overview_methods}'        => ' ',
			'{overview_analysis}'       => ' ',
			'{ver_notes}'               => ' ',
			'{scope_keywords}'          => ' ',
			'{scope_class}'             => ' ',
			'{overview_abstract}'       => ' ',
			'{coll_dates}'              => ' ',
			'{coll_periods}'            => ' ',
			'{coverage_country}'        => ' ',
			'{coverage_geo}'            => ' ',
			'{coverage_universe}'       => ' ',
			'{overview_kind_of_data}'   => ' ',
			'{scope_definition}'        => ' ',
			'{coll_collectors}'         => ' ',
			'{sampling_procedure}'      => ' ',
			'{sampling_dev}'            => ' ',
			'{coll_mode}'               => ' ',
			'{coll_questionnaire}'      => ' ',
			'{coll_notes}'              => ' ',
			'{operational_wb_name}'     => ' ',
			'{operational_wb_id}'       => ' ',
			'{operational_wb_net}'      => ' ',
			'{operational_wb_sector}'   => ' ',
			'{operational_wb_summary}'  => ' ',
			'{operational_wb_objectives}' => ' ',
			'{impact_wb_name}'          => ' ',
			'{impact_wb_id}'            => ' ',
			'{impact_wb_area}'          => ' ',
			'{impact_wb_lead}'          => ' ',
			'{impact_wb_members}'       => ' ',
			'{impact_wb_description}'   => ' ',
			'{coll_supervision}'        => ' ',
			'{sampling_weight}'         => ' ',
			'{process_editing}'         => ' ',
			'{process_other}'           => ' ',
			'{sampling_rates}'          => ' ',
			'{appraisal_error}'         => ' ',
			'{appraisal_other}'         => ' ',
			'{access_confidentiality}'  => ' ',
			'{access_authority}'        => ' ',
			'{access_cite_require}'     => ' ',
			'{access_conditions}'       => ' ',
			'{disclaimer_disclaimer}'   => ' ',
		);
	}

	private function xml_escape($value)
	{
		if ($value === null) {
			return '';
		}

		return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}

	private function cdata_safe($value)
	{
		if ($value === null) {
			return '';
		}

		return str_replace(']]>', ']]]]><![CDATA[>', (string) $value);
	}

	private function xml_cell($row, $index)
	{
		if (! is_array($row) || ! array_key_exists($index, $row)) {
			return '';
		}

		return $this->xml_escape($row[$index]);
	}

	private function decode_grid($json)
	{
		if (is_array($json)) {
			$decoded = $json;
		} elseif (is_string($json) && $this->_is($json)) {
			$decoded = json_decode($json, true);
		} else {
			return array();
		}

		if (! is_array($decoded)) {
			return array();
		}

		$rows = array();
		foreach ($decoded as $row) {
			if (is_array($row)) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	private function _replace_vars(array $variables) {
		$this->_prepare_rows($variables);
		$this->_escape_template_values($variables);
		$search     = array_keys($variables);
		$replace    = array_values($variables); 	
		$this->_DDI = str_replace($search, $replace, $this->_DDI_template);
				
		return $this->_DDI;
	}
	
	private function _is($json) {
		return isset($json) && !empty($json) && $json !== ' ';
	}

	private function _escape_template_values(array &$variables)
	{
		foreach ($variables as $key => $value) {
			if (in_array($key, $this->grid_keys, true) && is_string($value) && isset($value[0]) && $value[0] === '<') {
				continue;
			}
			if (in_array($key, $this->cdata_keys, true)) {
				$variables[$key] = $this->cdata_safe($value);
				continue;
			}
			$variables[$key] = $this->xml_escape($value);
		}
	}
	
	private function _prepare_rows(array &$variables) {
		$this->ci->load->library('Grid');
		// prod_s_investigator
		$i                       = &$variables['{prod_s_investigator}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$prod_s_investigator     = '';
			foreach ($rows as $row) {
				$prod_s_investigator .= '<AuthEnty affiliation="'.$this->xml_cell($row, 1).'">'.$this->xml_cell($row, 0).'</AuthEnty>'.PHP_EOL;
			}
			$i                       = $prod_s_investigator;
		}
		// prod_s_acknowledgements
		$i                       = &$variables['{prod_s_acknowledgements}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$prod_s_acknowledgements = '';
			foreach($rows as $row) {
				$prod_s_acknowledgements .= '<othId affiliation="'.$this->xml_cell($row, 1).'" role="'.$this->xml_cell($row, 2).'">'."\n".'<p>'.$this->xml_cell($row, 0).'</p>'."\n".'</othId>'.PHP_EOL;
			}
			$i                       = $prod_s_acknowledgements;
		}
		// prod_s_other_prod
		$i                       = &$variables['{prod_s_other_prod}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$prod_s_other_prod       = '';
			foreach($rows as $row) {
				$prod_s_other_prod .= '<producer abbr="'.$this->xml_cell($row, 1).'" affiliation="'.$this->xml_cell($row, 2).'" role="'.$this->xml_cell($row, 3).'">'.$this->xml_cell($row, 0).'</producer>'.PHP_EOL;
			}
			$i                       = $prod_s_other_prod;
		}
		// prod_s_funding
		$i                       = &$variables['{prod_s_funding}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$prod_s_funding          = '';
			foreach($rows as $row) {
				$prod_s_funding .= '<fundAg abbr="'.$this->xml_cell($row, 1).'" role="'.$this->xml_cell($row, 3).'">'.$this->xml_cell($row, 0).'</fundAg>'."\n".'<grantNo agency="'.$this->xml_cell($row, 0).'" role="'.$this->xml_cell($row, 3).'">'.$this->xml_cell($row, 2).'</grantNo>'.PHP_EOL;
			}
			$i                       = $prod_s_funding;
		}
		// contacts_contacts
		$i                       = &$variables['{contacts_contacts}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$contacts_contacts       = '';
			foreach($rows as $row) {
				$contacts_contacts .= $this->contact_xml($row);
			}
			$i                       = $contacts_contacts;
		}
		// scope_keywords
		$i                       = &$variables['{scope_keywords}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$scope_keywords          = '';
			foreach ($rows as $row) {
				$scope_keywords .= '<keyword vocab="'.$this->xml_cell($row, 1).'" vocabURI="'.$this->xml_cell($row, 2).'">'.$this->xml_cell($row, 0).'</keyword>'.PHP_EOL;
			}
			$i                       = $scope_keywords;
		}
		// scope_class
		$i                       = &$variables['{scope_class}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$scope_class             = ''; 
			foreach($rows as $row) {
				$scope_class .= '<topcClas vocab="'.$this->xml_cell($row, 1).'" vocabURI="'.$this->xml_cell($row, 2).'">'.$this->xml_cell($row, 0).'</topcClas>'.PHP_EOL;
			}
			$i                       = $scope_class;
		}
		// coll_dates
		$i                       = &$variables['{coll_dates}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$coll_periods = '';
			foreach($rows as $row) {
				$coll_periods .= '<collDate date="'.$this->xml_cell($row, 0).'" event="start" cycle="'.$this->xml_cell($row, 2).'" />'."\n".'<collDate date="'.$this->xml_cell($row, 1).'" event="end" cycle="'.$this->xml_cell($row, 2).'" />'.PHP_EOL;
			}	
			$i                       = $coll_periods;
		}


		// coll_periods
		$i                       = &$variables['{coll_periods}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$coll_dates              = '';
			foreach($rows as $row) {
				$coll_dates .= '<timePrd date="'.$this->xml_cell($row, 0).'" event="start" cycle="'.$this->xml_cell($row, 2).'" />'."\n".'<timePrd date="'.$this->xml_cell($row, 1).'" event="end" cycle="'.$this->xml_cell($row, 2).'" />'.PHP_EOL;
			}
			$i                       = $coll_dates;
		}



		// coverage_country
		$i                       = &$variables['{coverage_country}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$coverage_country        = '';
			foreach ($rows as $row) {
				$coverage_country .= '<nation abbr="'.$this->xml_cell($row, 1).'">'.$this->xml_cell($row, 0).'</nation>'.PHP_EOL;
			}
			$i                       = $coverage_country;
		}
		// coll_collectors
		$i                       = &$variables['{coll_collectors}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$coll_collectors         = '';
			foreach ($rows as $row) {
				$coll_collectors .= '<dataCollector abbr="'.$this->xml_cell($row, 1).'" affiliation="'.$this->xml_cell($row, 2).'">'.$this->xml_cell($row, 0).'</dataCollector>'.PHP_EOL;
			}
			$i                       = $coll_collectors;
		}
		// impact_wb_lead
		$i                       = &$variables['{impact_wb_lead}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$impact_wb_lead        = '';
			foreach ($rows as $row) {
				$impact_wb_lead .= $this->contact_xml($row);
			}
			$i                     = $impact_wb_lead;
		}
		// impact_wb_members
		$i                       = &$variables['{impact_wb_members}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$impact_wb_members       = '';
			foreach ($rows as $row) {
				$impact_wb_members .= $this->contact_xml($row);
			}
			$i                       = $impact_wb_members;
		}

		// access_authority
		$i                       = &$variables['{access_authority}'];
		$rows                    = $this->decode_grid($i);
		if ($rows) {
			$access_authority       = '';
			foreach ($rows as $row) {
				$access_authority .= $this->contact_xml($row);
			}
			$i                       = $access_authority;
		}
		
	}

	private function contact_xml(array $row)
	{
		return '<contact affiliation="'.$this->xml_cell($row, 1).'" email="'.$this->xml_cell($row, 2).'" URI="'.$this->xml_cell($row, 3).'">'.$this->xml_cell($row, 0).'</contact>'.PHP_EOL;
	}
	
	/**
	 * Public Methods 
	 *
	 */
	 
	public function load_template($template) {
		if (!file_exists($template)) {
			throw new Exception("DDI Template '$template' does not exist");
		}
		
		$this->_DDI_template = $template;
	}
	
	public function to_ddi($data) 
	{
		if (!isset($this->_DDI_template)) {
			throw new Exception('DDI Template NOT set');
		}

		$data= $data[0];
		$this->_variables = $this->default_variables();
		$this->_DDI_template = file_get_contents($this->_DDI_template);

		foreach ($this->_variables as $keys => $values) {
			$keys = str_replace(array('{', '}'), '', $keys);

			//apply date format
			if ($keys=="ver_prod_date" && isset($data[$keys]) && !empty($data[$keys]) ){
				$data[$keys] = date("Y-m-d", $data[$keys]);
			}

			if (isset($data[$keys])) {
				$this->_variables['{' . $keys . '}'] = $data[$keys];
			}
		}

		return $this->_replace_vars($this->_variables);
	}
}
