<?php

/**
 * Staged study package import keyed by resumable upload_id.
 *
 * Phases: upload (api/uploads) → unzip → create_study → datafile (per file) → finalize
 */
class Package_import_staged
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Survey_resource_model');
        $this->ci->load->model('Catalog_model');
        $this->ci->load->model('Dataset_microdata_model');
        $this->ci->load->library('Resumable_upload', null, 'resumable_upload');
        $this->ci->load->library('JSON_Reader');
        $this->ci->load->library('Catalog_dataset_import');
        $this->ci->load->library('Package_Importer');
    }


    /**
     * @param string $upload_id
     * @param int    $user_id
     * @return array public status payload
     */
    public function get_status($upload_id, $user_id)
    {
        $upload = $this->_require_upload($upload_id, $user_id);
        $progress = $this->_get_progress($upload);

        return $this->_status_response($upload, $progress);
    }


    /**
     * @param string $upload_id
     * @param int    $user_id
     * @return array
     */
    public function unzip($upload_id, $user_id)
    {
        $upload   = $this->_require_upload($upload_id, $user_id);
        $progress = $this->_get_progress($upload);

        if (! empty($progress['extract_dir']) && is_dir($progress['extract_dir'])) {
            return $this->_status_response($upload, $progress, 'already_unzipped');
        }

        $zip_path = $upload['file_path'];
        if (strtolower(pathinfo($zip_path, PATHINFO_EXTENSION)) !== 'zip') {
            throw new Exception('NOT_A_ZIP_FILE');
        }

        $extract_dir = get_catalog_root() . '/tmp/pkg-import-' . $upload_id;
        $this->ci->package_importer->extract_zip_to($zip_path, $extract_dir);

        $manifest   = $this->ci->package_importer->read_manifest($extract_dir);
        $jsonl_path = $this->ci->package_importer->resolve_jsonl_path($extract_dir, $manifest);

        $study_type = null;
        if (! empty($manifest['type'])) {
            $study_type = $manifest['type'];
        }

        $data_file_ids = array();
        $vars_dir      = $extract_dir . '/package-vars';
        if ($study_type === null || $study_type === 'survey') {
            $study_line = $this->ci->json_reader->parse_study_line_from_jsonl($jsonl_path);
            $resolved   = $this->ci->catalog_dataset_import->resolve_dataset_type($study_line, isset($manifest['type']) ? $manifest['type'] : null);
            $study_type = $resolved;
            if ($resolved === 'survey') {
                $from_sidecars = $this->ci->json_reader->build_variable_sidecars_by_file($jsonl_path, $vars_dir);
                $data_file_ids = array();
                if (! empty($study_line['data_files']) && is_array($study_line['data_files'])) {
                    foreach ($study_line['data_files'] as $df) {
                        if (! empty($df['file_id'])) {
                            $data_file_ids[] = (string) $df['file_id'];
                        }
                    }
                }
                foreach ($from_sidecars as $fid) {
                    if (! in_array($fid, $data_file_ids, true)) {
                        $data_file_ids[] = $fid;
                    }
                }
            }
        }

        $progress = array_merge($progress, array(
            'phase'           => 'unzipped',
            'extract_dir'     => $extract_dir,
            'jsonl_path'      => $jsonl_path,
            'manifest'        => $manifest,
            'type'            => $study_type,
            'data_file_ids'   => $data_file_ids,
            'data_files_done' => isset($progress['data_files_done']) ? $progress['data_files_done'] : array(),
        ));

        $this->_save_progress($upload_id, $upload, $progress);

        return $this->_status_response($upload, $progress, 'unzipped');
    }


    /**
     * @param string $upload_id
     * @param int    $user_id
     * @param array  $params repositoryid, overwrite, published, user_id, formid, link_da
     * @return array
     */
    public function create_study($upload_id, $user_id, array $params)
    {
        $upload   = $this->_require_upload($upload_id, $user_id);
        $progress = $this->_get_progress($upload);

        if (empty($progress['extract_dir']) || ! is_dir($progress['extract_dir'])) {
            throw new Exception('PACKAGE_NOT_UNZIPPED');
        }

        if (! empty($progress['sid'])) {
            return $this->_status_response($upload, $progress, 'study_already_created');
        }

        $jsonl_path = $progress['jsonl_path'];
        $manifest   = isset($progress['manifest']) ? $progress['manifest'] : array();
        $options    = $this->ci->json_reader->parse_study_line_from_jsonl($jsonl_path);

        unset($options['variables']);

        $type_hint = isset($manifest['type']) ? $manifest['type'] : null;
        $import_params = array_merge($params, array('type_hint' => $type_hint));

        $created = $this->ci->catalog_dataset_import->create_from_options($options, $import_params);

        $progress = array_merge($progress, array(
            'phase'           => 'study_created',
            'sid'             => (int) $created['sid'],
            'idno'            => $created['idno'],
            'type'            => $created['type'],
            'repositoryid'    => isset($params['repositoryid']) ? $params['repositoryid'] : 'central',
            'import_params'   => $params,
            'data_files_done' => array(),
        ));

        if ($created['type'] !== 'survey' || empty($progress['data_file_ids'])) {
            $progress['phase'] = 'datafiles_done';
        }

        $this->_save_progress($upload_id, $upload, $progress);

        return $this->_status_response($upload, $progress, 'study_created');
    }


    /**
     * Import the next pending data file and all its variables.
     *
     * @param string $upload_id
     * @param int    $user_id
     * @return array
     */
    public function import_datafile($upload_id, $user_id)
    {
        $upload   = $this->_require_upload($upload_id, $user_id);
        $progress = $this->_get_progress($upload);

        if (empty($progress['sid'])) {
            throw new Exception('STUDY_NOT_CREATED');
        }

        $sid = (int) $progress['sid'];
        $type = isset($progress['type']) ? $progress['type'] : '';

        if ($type !== 'survey') {
            $progress['phase'] = 'datafiles_done';
            $this->_save_progress($upload_id, $upload, $progress);
            return $this->_status_response($upload, $progress, 'no_datafiles');
        }

        $pending = $this->_pending_data_file_ids($progress);
        if ($pending === array()) {
            $progress['phase'] = 'datafiles_done';
            $this->_save_progress($upload_id, $upload, $progress);
            return $this->_status_response($upload, $progress, 'datafiles_complete');
        }

        $file_id     = $pending[0];
        $extract_dir = $progress['extract_dir'];
        $vars_dir    = $extract_dir . '/package-vars';
        $safe_fid    = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_id);
        $sidecar     = $vars_dir . '/variables-' . $safe_fid . '.json';

        $variables = array();
        if (is_file($sidecar)) {
            $decoded = json_decode(file_get_contents($sidecar), true);
            if (is_array($decoded)) {
                $variables = $decoded;
            }
        }

        $count = $this->ci->Dataset_microdata_model->import_variables_for_datafile($sid, $file_id, $variables);

        $done = isset($progress['data_files_done']) && is_array($progress['data_files_done'])
            ? $progress['data_files_done']
            : array();
        if (! in_array($file_id, $done, true)) {
            $done[] = $file_id;
        }

        $progress['data_files_done'] = $done;
        $progress['phase']           = 'importing_datafiles';
        $progress['last_datafile']   = array(
            'file_id'         => $file_id,
            'variables_count' => $count,
        );

        if ($this->_pending_data_file_ids($progress) === array()) {
            $progress['phase'] = 'datafiles_done';
        }

        $this->_save_progress($upload_id, $upload, $progress);

        return $this->_status_response($upload, $progress, 'datafile_imported');
    }


    /**
     * @param string $upload_id
     * @param int    $user_id
     * @return array
     */
    public function finalize($upload_id, $user_id)
    {
        $upload   = $this->_require_upload($upload_id, $user_id);
        $progress = $this->_get_progress($upload);

        if (empty($progress['sid'])) {
            throw new Exception('STUDY_NOT_CREATED');
        }

        if (! empty($progress['finalized'])) {
            return $this->_status_response($upload, $progress, 'already_finalized');
        }

        if ($this->_pending_data_file_ids($progress) !== array()) {
            throw new Exception('DATAFILES_NOT_COMPLETE');
        }

        $sid         = (int) $progress['sid'];
        $extract_dir = $progress['extract_dir'];
        $manifest    = isset($progress['manifest']) ? $progress['manifest'] : array();

        $stats = $this->ci->package_importer->import_package_assets($sid, $extract_dir, $manifest);

        $this->ci->Dataset_microdata_model->index_variable_data($sid);

        $this->ci->package_importer->cleanup_dir($extract_dir);

        $progress['phase']          = 'completed';
        $progress['finalized']      = true;
        $progress['finalize_stats'] = $stats;

        $this->_save_progress($upload_id, $upload, $progress);

        $response = $this->_status_response($upload, $progress, 'completed');
        $response['sid']            = $sid;
        $response['idno']           = isset($progress['idno']) ? $progress['idno'] : null;
        $response['package_stats']  = $stats;

        return $response;
    }


    /**
     * @param string $upload_id
     * @param int    $user_id
     * @return array upload row with file_path
     */
    private function _require_upload($upload_id, $user_id)
    {
        $upload_id = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $upload_id);
        if ($upload_id === '') {
            throw new Exception('INVALID_UPLOAD_ID');
        }

        $info = $this->ci->resumable_upload->get_completed_upload($upload_id);
        if (! is_array($info) || empty($info['file_path'])) {
            throw new Exception('UPLOAD_NOT_COMPLETE_OR_MISSING');
        }

        $meta  = isset($info['metadata']) && is_array($info['metadata']) ? $info['metadata'] : array();
        $owner = isset($meta['_upload_owner_user_id']) ? (int) $meta['_upload_owner_user_id'] : 0;
        if ($owner <= 0 || $owner !== (int) $user_id) {
            throw new Exception('ACCESS_DENIED');
        }

        return $info;
    }


    /**
     * @param array $upload
     * @return array
     */
    private function _get_progress(array $upload)
    {
        $meta = isset($upload['metadata']) && is_array($upload['metadata']) ? $upload['metadata'] : array();
        if (isset($meta['package_import']) && is_array($meta['package_import'])) {
            return $meta['package_import'];
        }
        return array(
            'phase'           => 'uploaded',
            'data_file_ids'   => array(),
            'data_files_done' => array(),
        );
    }


    /**
     * @param string $upload_id
     * @param array  $upload
     * @param array  $progress
     * @return void
     */
    private function _save_progress($upload_id, array $upload, array $progress)
    {
        $full = $this->ci->resumable_upload->get_upload_metadata($upload_id);
        if (! is_array($full)) {
            throw new Exception('UPLOAD_NOT_FOUND');
        }
        if (! isset($full['metadata']) || ! is_array($full['metadata'])) {
            $full['metadata'] = array();
        }
        $full['metadata']['package_import'] = $progress;
        $full['updated_at'] = time();
        $this->ci->resumable_upload->save_upload_metadata($upload_id, $full);
    }


    /**
     * @param array $progress
     * @return array
     */
    private function _pending_data_file_ids(array $progress)
    {
        $all  = isset($progress['data_file_ids']) && is_array($progress['data_file_ids']) ? $progress['data_file_ids'] : array();
        $done = isset($progress['data_files_done']) && is_array($progress['data_files_done']) ? $progress['data_files_done'] : array();
        $pending = array();
        foreach ($all as $fid) {
            if (! in_array($fid, $done, true)) {
                $pending[] = $fid;
            }
        }
        return $pending;
    }


    /**
     * @param array       $upload
     * @param array       $progress
     * @param string|null $action
     * @return array
     */
    private function _status_response(array $upload, array $progress, $action = null)
    {
        $pending = $this->_pending_data_file_ids($progress);
        $total   = isset($progress['data_file_ids']) && is_array($progress['data_file_ids'])
            ? count($progress['data_file_ids'])
            : 0;
        $done_count = isset($progress['data_files_done']) && is_array($progress['data_files_done'])
            ? count($progress['data_files_done'])
            : 0;

        $next_task = 'unzip';
        $phase     = isset($progress['phase']) ? $progress['phase'] : 'uploaded';

        if ($phase === 'completed') {
            $next_task = null;
        }
        elseif (! empty($progress['finalized'])) {
            $next_task = null;
        }
        elseif (empty($progress['extract_dir'])) {
            $next_task = 'unzip';
        }
        elseif (empty($progress['sid'])) {
            $next_task = 'create';
        }
        elseif ($pending !== array()) {
            $next_task = 'datafile';
        }
        elseif (empty($progress['finalized'])) {
            $next_task = 'finalize';
        }
        else {
            $next_task = null;
        }

        return array(
            'status'              => 'success',
            'upload_id'           => $upload['upload_id'],
            'phase'               => $phase,
            'next_task'           => $next_task,
            'action'              => $action,
            'sid'                 => isset($progress['sid']) ? (int) $progress['sid'] : null,
            'idno'                => isset($progress['idno']) ? $progress['idno'] : null,
            'type'                => isset($progress['type']) ? $progress['type'] : null,
            'data_files_total'    => $total,
            'data_files_done'     => $done_count,
            'data_files_pending'  => $pending,
            'last_datafile'       => isset($progress['last_datafile']) ? $progress['last_datafile'] : null,
            'finalize_stats'      => isset($progress['finalize_stats']) ? $progress['finalize_stats'] : null,
            'done'                => ($phase === 'completed'),
        );
    }
}
