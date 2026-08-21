<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;


/**
 * 
 * Timeseries
 * 
 */
class Dataset_timeseries_model extends Dataset_model {
 
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 
     * Update dataset
     * 
     * @merge_metadata - boolean
     *  true  - merge/update individual values
     *  false - replace all metadata with new values (no merge)
     * 
     */
    function update_dataset($sid,$type,$options, $merge_metadata=false, $validate_schema=true)
	{
		//need this to validate IDNO for uniqueness
        $options['sid']=$sid;
        
        //merge/replace metadata
        if ($merge_metadata==true){
            $metadata=$this->get_metadata($sid);
            if(is_array($metadata)){
                unset($metadata['idno']);                
                $options=$this->array_merge_replace_metadata($metadata,$options);
                $options=array_remove_nulls($options);
            }
        }

        return $this->create_dataset($type,$options,$sid, $validate_schema);
    }

    function create_dataset($type,$options, $sid=null, $validate_schema=true)
	{
        $this->_normalize_data_structure_reference_in_options($options, $sid);

		//validate schema
        if ($validate_schema){
            $this->validate_schema($type,$options);
        }

        //get core fields for listing datasets in the catalog
        $core_fields=$this->get_core_fields($options);
        $options=array_merge($options,$core_fields);
		
		if(!isset($core_fields['idno']) || empty($core_fields['idno'])){
			throw new exception("IDNO-NOT-SET");
		}

		//validate IDNO field
        $dataset_id=$this->find_by_idno($core_fields['idno']); 


		if(!empty($sid)){//for updating a study
            //if IDNO is changed, it should not be an existing IDNO
            if(is_numeric($dataset_id) && $sid!=$dataset_id ){
                throw new ValidationException("VALIDATION_ERROR", "IDNO matches an existing dataset: ".$dataset_id.':'.$core_fields['idno']);
            }

            $dataset_id=$sid;
        }
        else{//for creating new study or overwritting existing one
            if($dataset_id>0 && isset($options['overwrite']) && $options['overwrite']!=='yes'){
                throw new ValidationException("VALIDATION_ERROR", "IDNO already exists. ".$dataset_id);
            }
        }

        $options['changed']=date("U");

        // Partial API payloads often send { "metadata": { "data_structure_reference": "…" } }. Merged state can
        // still carry a stale root-level data_structure_reference; prefer the nested value so surveys.data_structure_id updates.
        if (is_array($options['metadata'] ?? null)) {
            $nested_ref = $options['metadata']['data_structure_reference'] ?? null;
            if (is_array($nested_ref) && !empty($nested_ref)) {
                $options['data_structure_reference'] = $nested_ref;
            } elseif ($nested_ref !== null && $nested_ref !== '' && trim((string) $nested_ref) !== '') {
                $options['data_structure_reference'] = trim((string) $nested_ref);
            }
        }

        $study_metadata_sections=array('metadata_information','metadata_creation','series_description','provenance','embeddings','lda_topics','tags','additional','data_structure_reference','data_notes');

        foreach($study_metadata_sections as $section){		
			if(array_key_exists($section,$options)){
                $options['metadata'][$section]=$options[$section];
                unset($options[$section]);
            }
        }

        // Normalize legacy database_id/database_name -> databases[] array
        if (is_array($options['metadata'] ?? null)) {
            $this->normalize_databases_metadata($options['metadata']);
        }

        // Canonical reference precedence for legacy or mixed payloads:
        // - when data_structure_reference is set, drop any inline metadata.data_structure (not part of the public schema)
        // - within an inline DSD snapshot, codelist_reference wins over codelist per component
        if (is_array($options['metadata'] ?? null)) {
            $this->_apply_reference_precedence_rules_to_metadata($options['metadata']);
        }

        // Resolve data_structure_reference (DSD idno) -> surveys.data_structure_id when the DSD exists
        // in the catalogue. Unresolved references are kept in metadata only (informational).
        if (array_key_exists('data_structure_reference', (array) ($options['metadata'] ?? []))) {
            $reference = $options['metadata']['data_structure_reference'];
            $options['data_structure_id'] = $this->_resolve_data_structure_id($options['metadata']['data_structure_reference']);
        }

		$existingSurveyId = null;
		if (!empty($sid) && is_numeric($sid)) {
			$existingSurveyId = (int) $sid;
		} elseif (!empty($dataset_id) && is_numeric($dataset_id) && (int) $dataset_id > 0) {
			$existingSurveyId = (int) $dataset_id;
		}
		$this->_apply_indicator_ts_columns_on_data_structure_change($options, $existingSurveyId);

		//start transaction
		$this->db->trans_start();
        
        if($dataset_id>0){
            //update
            $this->update($dataset_id,$type,$options);
        }
        else{
		    //insert record
            $dataset_id=$this->insert($type,$options);
        }

        $this->update_filters($dataset_id,$options['metadata']);

		//complete transaction
		$this->db->trans_complete();

		$this->sync_db_links($dataset_id, $options['metadata']);

		return $dataset_id;
    }


	/**
	 * Rebuild timeseries_db_links rows for this series from series_description.databases[].
	 */
	function sync_db_links($sid, $metadata)
	{
		$sid = (int)$sid;
		if ($sid <= 0 || !is_array($metadata)) {
			return;
		}

		$databases = isset($metadata['series_description']['databases'])
			? $metadata['series_description']['databases']
			: array();

		$this->db->where('series_id', $sid)->delete('timeseries_db_links');

		if (!is_array($databases)) {
			return;
		}

		foreach ($databases as $db) {
			$idno = isset($db['id']) ? trim((string)$db['id']) : '';
			if ($idno === '') {
				continue;
			}
			$is_primary = !empty($db['is_primary']) ? 1 : 0;
			$this->db->query(
				'INSERT IGNORE INTO timeseries_db_links (series_id, db_idno, is_primary) VALUES (?, ?, ?)',
				array($sid, $idno, $is_primary)
			);
		}
	}


	/**
	 * Delete pivot rows when a timeseries series is deleted.
	 */
	function delete($id)
	{
		$this->db->where('series_id', (int)$id)->delete('timeseries_db_links');
		return parent::delete($id);
	}


    /**
     * Expand legacy/partial data_structure_reference before schema validation.
     * Hydrates from surveys.data_structure_id when the payload omits the link (catalog metadata save).
     *
     * @param array $options
     * @param int|null $sid
     * @return void
     */
    private function _normalize_data_structure_reference_in_options(array &$options, $sid = null)
    {
        $refKey = 'data_structure_reference';

        if (array_key_exists($refKey, $options)) {
            $ref = $options[$refKey];
            if ($ref === null || $ref === '') {
                unset($options[$refKey]);
                return;
            }
            if ($this->_is_complete_data_structure_reference($ref)) {
                return;
            }
            $expanded = $this->_expand_data_structure_reference($ref, $sid);
            if ($expanded) {
                $options[$refKey] = $expanded;
            } else {
                unset($options[$refKey]);
            }
            return;
        }

        if ($sid) {
            $expanded = $this->_expand_data_structure_reference(null, $sid);
            if ($expanded) {
                $options[$refKey] = $expanded;
            }
        }
    }

    /**
     * @param mixed $ref
     * @return bool
     */
    private function _is_complete_data_structure_reference($ref)
    {
        if (!is_array($ref)) {
            return false;
        }
        foreach (['idno', 'agency', 'name', 'version'] as $k) {
            if (trim((string) ($ref[$k] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $ref string idno, partial array, or null (resolve via $sid only)
     * @param int|null $sid
     * @return array<string,string>|null
     */
    private function _expand_data_structure_reference($ref, $sid = null)
    {
        $this->load->model('Data_structure_model');
        $row = null;
        $idno = '';
        if (is_string($ref)) {
            $idno = trim($ref);
        } elseif (is_array($ref)) {
            $idno = trim((string) ($ref['idno'] ?? ''));
        }

        if ($idno !== '') {
            $row = $this->Data_structure_model->get_structure_by_idno($idno);
        }

        if (!$row && $sid) {
            $survey = $this->db->select('data_structure_id')
                ->get_where('surveys', ['id' => (int) $sid])
                ->row_array();
            $dsdId = isset($survey['data_structure_id']) ? (int) $survey['data_structure_id'] : 0;
            if ($dsdId > 0) {
                $row = $this->Data_structure_model->get_structure_by_id($dsdId, false);
            }
        }

        if (!$row || !is_array($row)) {
            return null;
        }

        $normalized = [
            'idno'    => (string) ($row['idno'] ?? ''),
            'agency'  => (string) ($row['agency'] ?? ''),
            'name'    => (string) ($row['name'] ?? ''),
            'version' => (string) ($row['version'] ?? ''),
        ];

        if (is_array($ref)) {
            foreach (['uri', 'notes'] as $k) {
                if (!empty($ref[$k])) {
                    $normalized[$k] = trim((string) $ref[$k]);
                }
            }
        }

        return $this->_is_complete_data_structure_reference($normalized) ? $normalized : null;
    }

    /**
     * Resolve metadata.data_structure_reference (DSD idno) to data_structures.id.
     *
     * Returns:
     *   - int  — matching data_structures.id when idno is known in the catalogue.
     *   - null — when the reference is empty/null, or when the idno is informational
     *            only (not yet present in data_structures). The reference is still
     *            stored in metadata; only the operational FK is left unset.
     */
    private function _resolve_data_structure_id($reference)
    {
        if ($reference === null) {
            return null;
        }
        $idno = '';
        if (is_array($reference)) {
            $idno = trim((string) ($reference['idno'] ?? ''));
        } elseif (is_string($reference)) {
            $idno = trim($reference);
        }
        if ($idno === '') {
            return null;
        }
        $this->load->model('Data_structure_model');
        $row = $this->Data_structure_model->get_structure_by_idno($idno);
        if (!$row) {
            return null;
        }
        return (int) $row['id'];
    }


    /**
     * 
     * Update all related tables used for facets/filters
     * 
     * 
     */
    function update_filters($sid, $metadata=null)
    {
        if (!is_array($metadata)){            
            return false;
        }

        $core_fields=$this->get_core_fields($metadata);

		$this->update_years($sid,$core_fields['year_start'],$core_fields['year_end']);
        $this->Survey_country_model->update_countries($sid,$core_fields['nations']);
        $this->add_tags($sid,$this->get_array_nested_value($metadata,'tags'));
        return true;
    }


    /**
	 * 
	 * get core fields 
	 * 
	 * core fields are: idno, title, nation, year, authoring_entity
	 * 
	 * 
	 */
	function get_core_fields($options)
	{        
        $output=array();
        $output['title']=$this->get_array_nested_value($options,'series_description/name');
        $output['idno']=$this->get_array_nested_value($options,'series_description/idno');

        $nations=(array)$this->get_array_nested_value($options,'series_description/geographic_units');	

        if (count($nations)>0 && isset($nations[0]['name'])){
            //$nation_names=array_column($nations,"name");
            $nation_names=array();
            foreach($nations as $nrow){
                //if(isset($nrow['type']) && strtolower($nrow['type'])=='country'){
                    $nation_names[]=$nrow['name'];
                //}
            }
            
            $output['nation']=$this->get_country_names_string($nation_names);
            $output['nations']=$nation_names;
        }
        else{
            $output['nation']='';
            $output['nations']=array();
        }    

        $output['abbreviation']=$this->get_array_nested_value($options,'series_description/abbreviation');
        
        //$auth_entity=$this->get_array_nested_value($options,'series_description/authoring_entity');
        //$output['authoring_entity']=$this->array_column_to_string($auth_entity,$column_name='name', $max_length=300);
        $output['authoring_entity']='';

        $years=$this->get_years($options);
        $output['year_start']=$years['start'];
        $output['year_end']=$years['end'];
        return $output;
    }
    


    /**
     * 
     * Return an array of country names
     * 
     */
	function get_country_names($nations)
	{
        if(!is_array($nations)){
            return false;
        }

        $nation_names=array();
        foreach($nations as $nation){
            $nation_names[]=$nation['name'];
        }	
        return $nation_names;	
    }
    

    /**
     * 
     * get years
     * 
     **/
	function get_years($options)
	{
		$years=array();
        $data_coll=$this->get_array_nested_value($options,'series_description/time_periods');
			
        if (is_array($data_coll)){
            //get years from data collection dates				
            foreach($data_coll as $row){
                $year_=substr($row['start'],0,4);
                if((int)$year_>0){
                    $years[]=$year_;
                }					
                if(isset($row['end'])){
                    $year_=substr($row['end'],0,4);
                    if((int)$year_>0){
                        $years[]=$year_;
                    }
                }
            }
        }

		$start=0;
		$end=0;
		
		if (count($years)>0){
			$start=min($years);
			$end=max($years);
		}

		if ($start==0){
			$start=$end;
		}

		if($end==0){
			$start=$end;
		}

		return array(
			'start'=>$start,
			'end'=>$end
		);
    }
    

    function get_timeseries_db_id($sid)
    {
        $this->db->select('ts_db_id');
        $this->db->where('id', (int)$sid);
        $row=$this->db->get('surveys')->row_array();

        if(isset($row['ts_db_id']) && $row['ts_db_id']!==null && $row['ts_db_id']!==''){
            return (int)$row['ts_db_id'];
        }

        $metadata=$this->get_metadata($sid);

        if(isset($metadata['series_description']['database_id'])){
            return $metadata['series_description']['database_id'];
        }

        return false;
    }

	/**
	 * When metadata resolved data_structure_id, align ts_dimensions / ts_sync_required on the survey row.
	 *
	 * @param array    $options
	 * @param int|null $existingSurveyId surveys.id when updating or overwriting an existing row
	 */
	private function _apply_indicator_ts_columns_on_data_structure_change(array &$options, $existingSurveyId)
	{
		if (!array_key_exists('data_structure_id', $options)) {
			return;
		}
		$newRaw = $options['data_structure_id'];
		$newId   = ($newRaw !== null && $newRaw !== '' && is_numeric($newRaw)) ? (int) $newRaw : null;
		if ($existingSurveyId !== null && (int) $existingSurveyId > 0) {
			$oldRow = $this->db->select('data_structure_id')->get_where('surveys', ['id' => (int) $existingSurveyId])->row_array();
			$oldId  = (isset($oldRow['data_structure_id']) && $oldRow['data_structure_id'] !== null && $oldRow['data_structure_id'] !== '')
				? (int) $oldRow['data_structure_id']
				: null;
			if ($oldId === $newId) {
				return;
			}
		}
		if ($newId === null) {
			$options['ts_dimensions'] = null;
			$options['ts_frequency']  = null;
			$options['ts_sync_required'] = 0;
		} else {
			$options['ts_dimensions'] = $this->build_ts_dimensions_csv_for_structure_id($newId);
			$options['ts_frequency']  = $this->build_ts_frequency_for_structure_id($newId);
			$options['ts_sync_required'] = 1;
		}
	}

    /**
     * Apply canonical reference precedence in metadata (legacy rows may still carry inline snapshots).
     * When `data_structure_reference` is set, drop `data_structure`. Within nested components, when
     * `codelist_reference` is set, drop inline `codelist`.
     *
     * @param array $metadata
     * @return void
     */
    private function _apply_reference_precedence_rules_to_metadata(array &$metadata)
    {
        if (array_key_exists('data_structure_reference', $metadata)) {
            $ref = $metadata['data_structure_reference'];
            $hasRef = false;
            if (is_array($ref)) {
                $hasRef = trim((string) ($ref['idno'] ?? '')) !== '';
            } elseif ($ref !== null && trim((string) $ref) !== '') {
                $hasRef = true;
            }
            if ($hasRef && array_key_exists('data_structure', $metadata)) {
                unset($metadata['data_structure']);
            }
        }

        if (!isset($metadata['data_structure']) || !is_array($metadata['data_structure'])) {
            return;
        }
        if (!isset($metadata['data_structure']['components']) || !is_array($metadata['data_structure']['components'])) {
            return;
        }

        foreach ($metadata['data_structure']['components'] as $i => $component) {
            if (!is_array($component)) {
                continue;
            }
            $ref = isset($component['codelist_reference']) ? $component['codelist_reference'] : null;
            $hasRef = false;
            if (is_array($ref)) {
                $hasRef = trim((string) ($ref['idno'] ?? '')) !== '';
            } elseif ($ref !== null && trim((string) $ref) !== '') {
                $hasRef = true;
            }
            if ($hasRef && array_key_exists('codelist', $component)) {
                unset($component['codelist']);
                $metadata['data_structure']['components'][$i] = $component;
            }
        }
    }


    /**
     * Override: normalize legacy database_id/database_name -> databases[] on every read
     * so exports and display always receive the canonical format regardless of when the
     * record was last saved.
     */
    function get_metadata($sid)
    {
        $metadata = parent::get_metadata($sid);
        if (is_array($metadata)) {
            $this->normalize_databases_metadata($metadata);
        }
        return $metadata;
    }


    public function normalize_databases_metadata(array &$metadata)
    {
        $sd = isset($metadata['series_description']) && is_array($metadata['series_description'])
            ? $metadata['series_description']
            : null;

        if ($sd === null) {
            return;
        }

        $legacy_id   = isset($sd['database_id'])   ? trim((string)$sd['database_id'])   : '';
        $legacy_name = isset($sd['database_name'])  ? trim((string)$sd['database_name']) : '';

        // Remove legacy fields regardless — databases[] is canonical going forward
        unset($metadata['series_description']['database_id']);
        unset($metadata['series_description']['database_name']);

        if ($legacy_id === '') {
            return;
        }

        $databases = isset($sd['databases']) && is_array($sd['databases'])
            ? $sd['databases']
            : array();

        // Check if already present in databases[]
        foreach ($databases as $entry) {
            $existing_id = isset($entry['id']) ? trim((string)$entry['id']) : '';
            if ($existing_id === $legacy_id) {
                // Already represented — ensure name is filled if missing
                return;
            }
        }

        // Prepend entry so the legacy primary database appears first
        $new_entry = array(
            'id'         => $legacy_id,
            'name'       => $legacy_name !== '' ? $legacy_name : $legacy_id,
            'is_primary' => true,
        );

        array_unshift($databases, $new_entry);
        $metadata['series_description']['databases'] = $databases;
    }

}