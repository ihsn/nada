<?php

class Package_Exporter
{
    private $ci;
    private $temp_files = array();

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Survey_resource_model');
        $this->ci->load->model('Catalog_model');
        $this->ci->load->library('JSON_Writer');
    }

    /**
     * Export study package as ZIP file
     * 
     * @param int $sid - Study ID
     * @param string $output_path - Output ZIP file path (optional, defaults to temp file)
     * @param string $dsd_export 'reference'|'inline' — timeseries JSONL first line (same as JSON_Writer::DSD_EXPORT_*)
     * @return string - Path to created ZIP file
     */
    public function export($sid, $output_path = null, $dsd_export = 'reference')
    {
        if (!$sid) {
            throw new Exception('STUDY_NOT_FOUND');
        }

        $dataset = $this->ci->Dataset_model->get_row($sid);
        if (!$dataset) {
            throw new Exception('STUDY_NOT_FOUND: ' . $sid);
        }

        $study_path = $this->ci->Dataset_model->get_storage_fullpath($sid);
        if (!$study_path) {
            throw new Exception("STUDY_FOLDER_NOT_SET");
        }

        if ($output_path === null) {
            $temp_dir = sys_get_temp_dir();
            $output_path = $temp_dir . '/' . $dataset['idno'] . '-package-' . time() . '.zip';
        }

        $zip = new ZipArchive();
        if ($zip->open($output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception("FAILED_TO_CREATE_ZIP: " . $output_path);
        }

        try {
            // Add timeout for long exports, max 10 minutes
            set_time_limit(600);

            $resources = $this->ci->Survey_resource_model->get_resources_by_survey($sid);
            
            $this->add_study_json($zip, $sid, $dataset, $dsd_export);
            $this->add_study_xml($zip, $sid, $dataset, $study_path);
            $this->add_external_resources_json($zip, $sid, $resources);
            $this->add_documentation_files($zip, $study_path, $resources);
            $this->add_thumbnail($zip, $dataset);
            $this->add_info_json($zip, $sid, $dataset, $study_path);
        } finally {
            $zip->close();
            $this->cleanup_temp_files();
        }

        return $output_path;
    }

    /**
     * Add study JSON (JSON Lines format) to ZIP
     */
    private function add_study_json($zip, $sid, $dataset, $dsd_export = 'reference')
    {
        $jsonl_path = sys_get_temp_dir() . '/' . $dataset['idno'] . '-' . time() . '.jsonl';
        
        try {
            $this->ci->json_writer->write_jsonl($sid, $jsonl_path, true, $dsd_export);

            $zip_inner_name = $dataset['idno'] . '.jsonl';
            if ($dataset['type'] === 'timeseries'
                && strtolower((string) $dsd_export) === 'inline') {
                $zip_inner_name = $dataset['idno'] . '.inline.jsonl';
            }

            if (file_exists($jsonl_path)) {
                $zip->addFile($jsonl_path, $zip_inner_name);
                $this->temp_files[] = $jsonl_path;
            }
        } catch (Exception $e) {
            if (file_exists($jsonl_path)) {
                @unlink($jsonl_path);
            }
            throw $e;
        }
    }

    /**
     * Add study DDI/XML file to ZIP if it exists
     */
    private function add_study_xml($zip, $sid, $dataset, $study_path)
    {
        $xml_file = $this->ci->Catalog_model->get_survey_ddi_path($sid);

        if ($xml_file && file_exists($xml_file)) {
            $xml_filename = basename($xml_file);
            if (empty($xml_filename)) {
                $xml_filename = $dataset['idno'] . '.xml';
            }
            $zip->addFile($xml_file, $xml_filename);
        }
    }

    /**
     * Add external resources as JSON to ZIP
     */
    private function add_external_resources_json($zip, $sid, $resources = null)
    {
        if ($resources === null) {
            $resources = $this->ci->Survey_resource_model->get_resources_by_survey($sid);
        }
        
        if ($resources) {
            $resources_json = json_encode($resources, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $zip->addFromString('external_resources.json', $resources_json);
        } else {
            $zip->addFromString('external_resources.json', '[]');
        }
    }

    /**
     * Add documentation files from external resources to ZIP
     */
    private function add_documentation_files($zip, $study_path, $resources)
    {
        if (!$resources) {
            return;
        }
        
        foreach ($resources as $resource) {
            if (empty($resource['filename'])) {
                continue;
            }
            
            if (!empty($resource['is_url'])) {
                continue;
            }
            
            $file_path = $study_path . '/' . $resource['filename'];
            
            if (file_exists($file_path) && is_file($file_path)) {
                $zip_file_path = 'documentation/' . basename($resource['filename']);
                $zip->addFile($file_path, $zip_file_path);
            }
        }
    }

    /**
     * Add thumbnail to ZIP root if it exists
     */
    private function add_thumbnail($zip, $dataset)
    {
        if (!empty($dataset['thumbnail'])) {
            $thumbnail_path = FCPATH . 'files/thumbnails/' . $dataset['thumbnail'];
            
            if (file_exists($thumbnail_path)) {
                $zip->addFile($thumbnail_path, basename($dataset['thumbnail']));
            }
        }
    }

    /**
     * Add info.json file with study/survey info
     */
    private function add_info_json($zip, $sid, $dataset, $study_path)
    {
        $info = array(
            'idno' => $dataset['idno'],
            'created' => date('c', $dataset['created']),
            'type' => $dataset['type'],
            'thumbnail' => !empty($dataset['thumbnail']) ? basename($dataset['thumbnail']) : null,
        );

        $xml_file = $this->ci->Catalog_model->get_survey_ddi_path($sid);
        if ($xml_file && file_exists($xml_file)) {
            $info['xml_file'] = basename($xml_file);
        } else {
            $info['xml_file'] = null;
        }

        $info['json_file'] = $dataset['idno'] . '.jsonl';

        $rdf_xml_file = $study_path . '/' . $dataset['idno'] . '.rdf';
        if (file_exists($rdf_xml_file)) {
            $info['rdf_xml_file'] = basename($rdf_xml_file);
        } else {
            $info['rdf_xml_file'] = null;
        }

        //rdf json file = generated json file external_resources.json
        $info['rdf_json_file'] = 'external_resources.json';
        
        $collections = $this->get_collections($sid);
        $info['collections'] = $collections;

        $info_json = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $zip->addFromString('info.json', $info_json);
    }

    /**
     * Get collections (repositories) for the study
     */
    private function get_collections($sid)
    {
        try {
            if (!$this->ci->db->table_exists('survey_repos')) {
                return array();
            }
            
            $this->ci->load->model('Repository_model');
            $repository_ids = $this->ci->Repository_model->linked_repos_by_study($sid);
            
            if ($repository_ids) {
                return $repository_ids;
            }
        } catch (Exception $e) {
            return array();
        }
        
        return array();
    }

    /**
     * Recursively add directory contents to ZIP
     */
    private function add_directory_to_zip($zip, $dir, $zip_path = '')
    {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $file_path = $dir . '/' . $file;
            $zip_file_path = ($zip_path ? $zip_path . '/' : '') . $file;
            
            if (is_dir($file_path)) {
                $this->add_directory_to_zip($zip, $file_path, $zip_file_path);
            } else {
                $zip->addFile($file_path, $zip_file_path);
            }
        }
    }

    /**
     * Clean up temporary files after ZIP is created
     */
    private function cleanup_temp_files()
    {
        foreach ($this->temp_files as $temp_file) {
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }
        $this->temp_files = array();
    }
}

