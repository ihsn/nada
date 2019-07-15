<?php

/*
 study
 --------------------------
 CREATE TABLE IF NOT EXISTS `study` (
 `id`                       int(11)       NOT NULL,
 `ident_title`              tinytext      NOT NULL,
 `ident_abbr`               tinytext      NOT NULL,
 `ident_study_type`         tinytext      NOT NULL,
 `ident_ser_info`           text          NOT NULL,
 `ident_trans_title`        tinytext      NOT NULL,
 `ident_id`                 smallint      NOT NULL,
 `ver_desc`                 tinytext      NOT NULL,
 `ver_prod_date`            date          NOT NULL,
 `ver_notes`                text          NOT NULL,
 `overview_abstract`        text          NOT NULL,
 `overview_kind_of_data`    tinytext      NOT NULL,
 `overview_analysis`        text          NOT NULL,
 `overview_methods`         text          NOT NULL,
 `scope_definition`         text          NOT NULL,
 `scope_class`              text          NOT NULL,
 `coverage_country`         text          NOT NULL,
 `coverage_geo`             tinytext      NOT NULL,
 `coverage_universe`        text          NOT NULL,
 `prod_s_investigator`      text          NOT NULL,
 `prod_s_other_prod`        text          NOT NULL,
 `prod_s_funding`           text          NOT NULL,
 `prod_s_acknowledgements`  text          NOT NULL,
 `sampling_procedure`       text          NOT NULL,
 `sampling_dev`             text,
 `sampling_rates`           text          NOT NULL,
 `sampling_weight`          text,
 `coll_dates`               text          NOT NULL,
 `coll_periods`             text          NOT NULL,
 `coll_mode`                tinytext      NOT NULL,
 `coll_notes`               text          NOT NULL,
 `coll_questionnaire`       longtext      NOT NULL,
 `coll_collectors`          text          NOT NULL,
 `coll_supervision`         text          NOT NULL,
 `process_editing`          text          NOT NULL,
 `process_other`            text          NOT NULL,
 `appraisal_error`          text          NOT NULL,
 `appraisal_other`          text          NOT NULL,
 `access_authority`         text          NOT NULL,
 `access_confidentiality`   text          NOT NULL,
 `access_conditions`        text          NOT NULL,
 `access_cite_require`      text          NOT NULL,
 `disclaimer_disclaimer`    text          NOT NULL,
 `disclaimer_copyright`     tinytext      NOT NULL,
 `contacts_contacts`        text          NOT NULL,
  INDEX (`id`),
  FOREIGN KEY (`id`)
  	REFERENCES projects(`id`)
	ON UPDATE CASCADE ON DELETE CASCADE  
 ) ENGINE=InnoDB;
 --------------------------
*/

class DD_study_model extends CI_Model {

	public function __construct() {
    	parent::__construct();
  	}

	public function record_id() {}
	public function insert_record() {}
	public function delete_record() {}
	public function get_study($id) {
		$q = $this->db->select('*')
	  		->from('dd_study')
			->where('id', $id);
		return $q->get()->result();
	}
	public function get_study_array($id) {
		$q = $this->db->select('*')
	  		->from('dd_study')
			->where('id', $id);
		return $q->get()->result_array();
	}
  
  
  public function insert_study($data) 
  {
	return $this->db->insert('dd_study', $data);
  }
  
  
  public function update_study($id, $data) 
  {
	 $d = $this->db->where('id', $id)
	  	->update('dd_study', $data);
	 return $d;
  }
    
}