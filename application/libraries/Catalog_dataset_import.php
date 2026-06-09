<?php

/**
 * Shared catalog study import from decoded JSON / JSONL payloads.
 */
class Catalog_dataset_import
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->library('Dataset_manager');
    }


    /**
     * @param array $options decoded study document
     * @param array $params  repositoryid, overwrite (bool), published (int|null), user_id,
     *                       formid (int|null), link_da (string|null), type_hint (string|null)
     * @return array{sid:int,idno:?string,type:string,dataset:array}
     */
    public function create_from_options(array $options, array $params)
    {
        $type_hint = isset($params['type_hint']) ? $params['type_hint'] : null;
        $type      = $this->resolve_dataset_type($options, $type_hint);

        if (! $this->ci->dataset_manager->is_valid_type($type)) {
            throw new Exception('INVALID_TYPE');
        }

        $this->strip_export_fields($options);

        $repositoryid = isset($params['repositoryid']) ? $params['repositoryid'] : 'central';
        $overwrite    = ! empty($params['overwrite']);

        $options['repositoryid'] = $repositoryid;
        $options['overwrite']    = $overwrite ? 'yes' : 'no';

        if (! empty($params['link_da'])) {
            $options['link_da'] = $params['link_da'];
        } elseif (isset($options['data_remote_url'])) {
            $options['link_da'] = $options['data_remote_url'];
        }

        if (! empty($params['formid'])) {
            $options['formid'] = (int) $params['formid'];
        }

        if (array_key_exists('published', $params) && $params['published'] !== null) {
            $options['published'] = (int) $params['published'];
        }

        $user_id = isset($params['user_id']) ? (int) $params['user_id'] : null;
        if ($user_id) {
            $options['created_by'] = $user_id;
            $options['changed_by'] = $user_id;
        }
        $options['created']  = date('U');
        $options['changed'] = date('U');

        $dataset_id = $this->ci->dataset_manager->create_dataset($type, $options);
        if (! $dataset_id) {
            throw new Exception('FAILED_TO_CREATE_DATASET');
        }

        $result  = $this->ci->dataset_manager->get_row($dataset_id);
        $dirpath = $this->ci->dataset_manager->setup_folder($repositoryid, md5($result['idno']));
        $this->ci->dataset_manager->update_options($dataset_id, array('dirpath' => $dirpath));

        if (! isset($this->ci->events)) {
            $this->ci->load->library('Events');
        }
        $this->ci->events->emit('db.after.update', 'surveys', $dataset_id, 'import');

        return array(
            'sid'     => (int) $dataset_id,
            'idno'    => isset($result['idno']) ? $result['idno'] : null,
            'type'    => $type,
            'dataset' => $result,
        );
    }


    /**
     * @param array       $options
     * @param string|null $type_hint manifest or explicit type override
     * @return string
     */
    public function resolve_dataset_type(array $options, $type_hint = null)
    {
        if ($type_hint !== null && $type_hint !== '') {
            $normalized = $this->_normalize_type($type_hint);
            if ($normalized !== null && $this->ci->dataset_manager->is_valid_type($normalized)) {
                return $normalized;
            }
        }

        $last_invalid = null;
        foreach (array('type', 'schema_type', 'schematype', 'datatype') as $key) {
            if (! isset($options[$key]) || ! is_string($options[$key]) || trim($options[$key]) === '') {
                continue;
            }
            $normalized = $this->_normalize_type($options[$key]);
            if ($normalized !== null && $this->ci->dataset_manager->is_valid_type($normalized)) {
                return $normalized;
            }
            if ($normalized !== null) {
                $last_invalid = $normalized;
            }
        }

        $inferred = $this->_infer_type_from_sections($options);
        if ($inferred !== null && $this->ci->dataset_manager->is_valid_type($inferred)) {
            return $inferred;
        }

        if ($last_invalid !== null) {
            throw new Exception('INVALID_TYPE: ' . $last_invalid);
        }

        return 'survey';
    }


    /**
     * @param array $options
     * @return void
     */
    public function strip_export_fields(array &$options)
    {
        foreach (array('type', 'schema_type', 'schematype', 'datatype', 'id') as $key) {
            unset($options[$key]);
        }
    }


    /**
     * @param mixed $raw
     * @return string|null
     */
    private function _normalize_type($raw)
    {
        if (! is_string($raw)) {
            return null;
        }
        $t = strtolower(trim($raw));
        if ($t === '') {
            return null;
        }
        $aliases = array(
            'microdata'     => 'survey',
            'timeseries-db' => 'timeseriesdb',
        );
        if (isset($aliases[$t])) {
            return $aliases[$t];
        }
        return $t;
    }


    /**
     * @param array $options
     * @return string|null
     */
    private function _infer_type_from_sections(array $options)
    {
        $section_map = array(
            'database_description'      => 'timeseriesdb',
            'series_description'        => 'timeseries',
            'study_desc'                => 'survey',
            'document_description'      => 'document',
            'project_desc'              => 'script',
            'table_description'         => 'table',
            'image_description'         => 'image',
            'video_description'         => 'video',
            'visualization_description' => 'visualization',
        );
        foreach ($section_map as $section => $type) {
            if (! empty($options[$section]) && is_array($options[$section])) {
                return $type;
            }
        }
        if (! empty($options['description']) && is_array($options['description'])) {
            return 'geospatial';
        }
        return null;
    }
}
