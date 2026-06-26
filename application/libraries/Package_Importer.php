<?php

/**
 * Import a study package ZIP produced by Package_Exporter.
 */
class Package_Importer
{
    private $ci;
    private $cleanup_dirs = array();

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Survey_resource_model');
        $this->ci->load->model('Catalog_model');
        $this->ci->load->library('JSON_Reader');
        $this->ci->load->library('Catalog_dataset_import');
    }


    /**
     * @param string $zip_path absolute path to uploaded ZIP
     * @param array  $params   same keys as Catalog_dataset_import::create_from_options
     * @return array import result with sid, idno, dataset, package_stats
     */
    public function import($zip_path, array $params)
    {
        if (! class_exists('ZipArchive')) {
            throw new Exception('ZIP_EXTENSION_NOT_AVAILABLE');
        }

        $extract_dir = get_catalog_root() . '/tmp/pkg-import-' . md5(uniqid('', true));
        $this->cleanup_dirs[] = $extract_dir;

        $this->_extract_zip_safe($zip_path, $extract_dir);

        $manifest   = $this->_read_manifest($extract_dir);
        $jsonl_path = $this->_resolve_jsonl_path($extract_dir, $manifest);

        $options = $this->ci->json_reader->parse_jsonl_file($jsonl_path);

        $type_hint = isset($manifest['type']) ? $manifest['type'] : null;
        $created   = $this->ci->catalog_dataset_import->create_from_options($options, array_merge($params, array(
            'type_hint' => $type_hint,
        )));

        $sid = $created['sid'];
        $stats = array(
            'documentation_files' => 0,
            'resources'           => 0,
            'thumbnail'           => false,
            'xml_copied'          => false,
            'rdf_imported'        => false,
        );

        $study_path = $this->ci->Dataset_model->get_storage_fullpath($sid);
        if (! $study_path) {
            throw new Exception('STUDY_FOLDER_NOT_SET');
        }

        if (! empty($manifest['xml_file'])) {
            $stats['xml_copied'] = $this->_copy_study_xml($extract_dir, $study_path, $manifest['xml_file'], $sid);
        }

        $doc_dir = $extract_dir . '/documentation';
        if (is_dir($doc_dir)) {
            $stats['documentation_files'] = $this->_copy_documentation_dir($doc_dir, $study_path);
        }

        $resources_path = $extract_dir . '/external_resources.json';
        if (is_file($resources_path)) {
            $stats['resources'] = $this->_import_external_resources($sid, $resources_path);
        }

        if (! empty($manifest['thumbnail'])) {
            $stats['thumbnail'] = $this->_import_thumbnail($extract_dir, $manifest['thumbnail'], $sid);
        }

        if (! empty($manifest['rdf_xml_file'])) {
            $rdf_path = $extract_dir . '/' . basename($manifest['rdf_xml_file']);
            if (is_file($rdf_path)) {
                $rdf_result = $this->ci->Survey_resource_model->import_rdf($sid, $rdf_path);
                $stats['rdf_imported'] = ($rdf_result !== false);
            }
        }

        return array_merge($created, array(
            'package_stats' => $stats,
        ));
    }


    public function extract_zip_to($zip_path, $extract_dir)
    {
        if (! class_exists('ZipArchive')) {
            throw new Exception('ZIP_EXTENSION_NOT_AVAILABLE');
        }
        $this->cleanup_dirs[] = $extract_dir;
        $this->_extract_zip_safe($zip_path, $extract_dir);
    }


    public function read_manifest($extract_dir)
    {
        return $this->_read_manifest($extract_dir);
    }


    public function resolve_jsonl_path($extract_dir, array $manifest)
    {
        return $this->_resolve_jsonl_path($extract_dir, $manifest);
    }


    /**
     * Copy package assets (XML, docs, resources, thumbnail, RDF) into the study.
     *
     * @param int    $sid
     * @param string $extract_dir
     * @param array  $manifest
     * @return array stats
     */
    public function import_package_assets($sid, $extract_dir, array $manifest)
    {
        $stats = array(
            'documentation_files' => 0,
            'resources'           => 0,
            'thumbnail'           => false,
            'xml_copied'          => false,
            'rdf_imported'        => false,
        );

        $study_path = $this->ci->Dataset_model->get_storage_fullpath($sid);
        if (! $study_path) {
            throw new Exception('STUDY_FOLDER_NOT_SET');
        }

        if (! empty($manifest['xml_file'])) {
            $stats['xml_copied'] = $this->_copy_study_xml($extract_dir, $study_path, $manifest['xml_file'], $sid);
        }

        $doc_dir = $extract_dir . '/documentation';
        if (is_dir($doc_dir)) {
            $stats['documentation_files'] = $this->_copy_documentation_dir($doc_dir, $study_path);
        }

        $resources_path = $extract_dir . '/external_resources.json';
        if (is_file($resources_path)) {
            $stats['resources'] = $this->_import_external_resources($sid, $resources_path);
        }

        if (! empty($manifest['thumbnail'])) {
            $stats['thumbnail'] = $this->_import_thumbnail($extract_dir, $manifest['thumbnail'], $sid);
        }

        if (! empty($manifest['rdf_xml_file'])) {
            $rdf_path = $extract_dir . '/' . basename($manifest['rdf_xml_file']);
            if (is_file($rdf_path)) {
                $rdf_result = $this->ci->Survey_resource_model->import_rdf($sid, $rdf_path);
                $stats['rdf_imported'] = ($rdf_result !== false);
            }
        }

        return $stats;
    }


    public function cleanup_dir($dir)
    {
        if (! is_string($dir) || $dir === '' || ! is_dir($dir)) {
            return;
        }
        $this->ci->load->helper('file');
        delete_files($dir, true, false, true);
        @rmdir($dir);
        $this->cleanup_dirs = array_values(array_filter(
            $this->cleanup_dirs,
            function ($d) use ($dir) {
                return $d !== $dir;
            }
        ));
    }


    /**
     * Remove temporary extract directories.
     */
    public function cleanup()
    {
        $this->ci->load->helper('file');
        foreach ($this->cleanup_dirs as $dir) {
            if (is_dir($dir)) {
                delete_files($dir, true, false, true);
                @rmdir($dir);
            }
        }
        $this->cleanup_dirs = array();
    }


    /**
     * @param string $zip_path
     * @param string $extract_dir
     * @return void
     */
    private function _extract_zip_safe($zip_path, $extract_dir)
    {
        if (! @mkdir($extract_dir, 0777, true) && ! is_dir($extract_dir)) {
            throw new Exception('EXTRACT_DIR_FAILED');
        }

        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            throw new Exception('INVALID_ZIP');
        }

        $extract_root = realpath($extract_dir);
        if ($extract_root === false) {
            $zip->close();
            throw new Exception('EXTRACT_DIR_FAILED');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || $name === '') {
                continue;
            }
            if (strpos($name, '..') !== false) {
                $zip->close();
                throw new Exception('ZIP_SLIP_DETECTED');
            }

            $target = $extract_root . '/' . str_replace('\\', '/', $name);
            $target_dir = rtrim(dirname($target), '/');
            if ($target_dir !== $extract_root && strpos($target_dir, $extract_root . '/') !== 0) {
                $zip->close();
                throw new Exception('ZIP_SLIP_DETECTED');
            }
        }

        if (! $zip->extractTo($extract_dir)) {
            $zip->close();
            throw new Exception('ZIP_EXTRACT_FAILED');
        }
        $zip->close();
    }


    /**
     * @param string $extract_dir
     * @return array
     */
    private function _read_manifest($extract_dir)
    {
        $info_path = $extract_dir . '/info.json';
        if (! is_file($info_path)) {
            return array();
        }
        $decoded = json_decode(file_get_contents($info_path), true);
        if (! is_array($decoded)) {
            throw new Exception('INVALID_INFO_JSON');
        }
        return $decoded;
    }


    /**
     * @param string $extract_dir
     * @param array  $manifest
     * @return string absolute path to JSONL
     */
    private function _resolve_jsonl_path($extract_dir, array $manifest)
    {
        if (! empty($manifest['json_file'])) {
            $candidate = $extract_dir . '/' . basename($manifest['json_file']);
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $glob = glob($extract_dir . '/*.jsonl');
        if (is_array($glob)) {
            foreach ($glob as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        throw new Exception('PACKAGE_JSONL_NOT_FOUND');
    }


    /**
     * @param string $extract_dir
     * @param string $study_path
     * @param string $xml_name
     * @param int    $sid
     * @return bool
     */
    private function _copy_study_xml($extract_dir, $study_path, $xml_name, $sid)
    {
        $src = $extract_dir . '/' . basename($xml_name);
        if (! is_file($src)) {
            return false;
        }
        if (! is_dir($study_path)) {
            @mkdir($study_path, 0777, true);
        }
        $dest_name = basename($xml_name);
        $dest      = $study_path . '/' . $dest_name;
        if (! @copy($src, $dest)) {
            return false;
        }
        $this->ci->Dataset_model->update_options($sid, array('metafile' => $dest_name));
        return true;
    }


    /**
     * @param string $doc_dir
     * @param string $study_path
     * @return int files copied
     */
    private function _copy_documentation_dir($doc_dir, $study_path)
    {
        if (! is_dir($study_path)) {
            @mkdir($study_path, 0777, true);
        }
        $count = 0;
        $items = scandir($doc_dir);
        if (! is_array($items)) {
            return 0;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $src = $doc_dir . '/' . $item;
            if (! is_file($src)) {
                continue;
            }
            $dest = $study_path . '/' . basename($item);
            if (@copy($src, $dest)) {
                $count++;
            }
        }
        return $count;
    }


    /**
     * @param int    $sid
     * @param string $resources_path
     * @return int count imported
     */
    private function _import_external_resources($sid, $resources_path)
    {
        $raw = json_decode(file_get_contents($resources_path), true);
        if (! is_array($raw)) {
            return 0;
        }
        $resources = array();
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            unset($row['resource_id'], $row['survey_id']);
            $resources[] = $row;
        }
        if ($resources === array()) {
            return 0;
        }
        $this->ci->Dataset_model->update_resources($sid, $resources);
        return count($resources);
    }


    /**
     * @param string $extract_dir
     * @param string $thumbnail_name
     * @param int    $sid
     * @return bool
     */
    private function _import_thumbnail($extract_dir, $thumbnail_name, $sid)
    {
        $src = $extract_dir . '/' . basename($thumbnail_name);
        if (! is_file($src)) {
            return false;
        }
        $thumb_dir = FCPATH . 'files/thumbnails/';
        if (! is_dir($thumb_dir)) {
            @mkdir($thumb_dir, 0777, true);
        }
        $dest_name = basename($thumbnail_name);
        if (! @copy($src, $thumb_dir . $dest_name)) {
            return false;
        }
        $this->ci->Dataset_model->update_options($sid, array('thumbnail' => $dest_name));
        return true;
    }
}
