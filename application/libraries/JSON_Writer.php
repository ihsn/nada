<?php

class JSON_Writer
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /**
     * Write study/survey to JSON format (single document)
     * 
     * @param int $sid - Study ID
     * @param string $output_path - Output file path or 'php://output'
     * @param bool $overwrite - Whether to overwrite existing file
     * @param bool $pretty - Whether to pretty print JSON
     * @return string|false - Path to written file or false on failure
     */
    public function write_json($sid, $output_path = 'php://output', $overwrite = false, $pretty = false)
    {
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Data_file_model');
        $this->ci->load->model('Variable_model');
        $this->ci->load->model('Variable_group_model');

        if (!$sid) {
            throw new Exception('STUDY NOT FOUND');
        }

        $dataset = $this->ci->Dataset_model->get_row($sid);
        if (!$dataset) {
            throw new Exception('STUDY NOT FOUND: ' . $sid);
        }

        if ($output_path !== 'php://output' && file_exists($output_path) && !$overwrite) {
            throw new Exception("JSON_FILE_EXISTS");
        }

        $fp = fopen($output_path, 'w');
        if (!$fp) {
            throw new Exception("FAILED_TO_OPEN_OUTPUT: " . $output_path);
        }

        $metadata = $this->ci->Dataset_model->get_metadata($sid);
        $basic_info = array(
            'type' => $dataset['type']
        );

        $output = array_merge($basic_info, $metadata);

        if ($dataset['type'] == 'survey') {
            $output['data_files'] = function () use ($sid) {
                $files = $this->ci->Data_file_model->get_all_by_survey($sid);
                if ($files) {
                    foreach ($files as $file) {
                        unset($file['id']);
                        unset($file['sid']);
                        yield $file;
                    }
                }
            };

            $output['variables'] = function () use ($sid) {
                foreach ($this->ci->Variable_model->chunk_reader_generator($sid) as $variable) {
                    yield $variable['metadata'];
                }
            };

            $output['variable_groups'] = function () use ($sid) {
                $var_groups = $this->ci->Variable_group_model->select_all($sid);
                foreach ($var_groups as $var_group) {
                    yield $var_group;
                }
            };
        }

        $encoder = new \Violet\StreamingJsonEncoder\StreamJsonEncoder(
            $output,
            function ($json) use ($fp) {
                fwrite($fp, $json);
            }
        );

        if ($pretty) {
            $encoder->setOptions(JSON_PRETTY_PRINT);
        }

        $encoder->encode();
        fclose($fp);

        return $output_path;
    }

    /**
     * Write study/survey to JSON Lines format (one JSON object per line)
     * First line contains study metadata, subsequent lines contain individual variables
     * 
     * @param int $sid - Study ID
     * @param string $output_path - Output file path or 'php://output'
     * @param bool $overwrite - Whether to overwrite existing file
     * @return string|false - Path to written file or false on failure
     */
    public function write_jsonl($sid, $output_path = 'php://output', $overwrite = false)
    {
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Data_file_model');
        $this->ci->load->model('Variable_model');
        $this->ci->load->model('Variable_group_model');

        if (!$sid) {
            throw new Exception('STUDY NOT FOUND');
        }

        $dataset = $this->ci->Dataset_model->get_row($sid);
        if (!$dataset) {
            throw new Exception('STUDY NOT FOUND: ' . $sid);
        }

        if ($output_path !== 'php://output' && file_exists($output_path) && !$overwrite) {
            throw new Exception("JSONL_FILE_EXISTS");
        }

        $fp = fopen($output_path, 'w');
        if (!$fp) {
            throw new Exception("FAILED_TO_OPEN_OUTPUT: " . $output_path);
        }

        $metadata = $this->ci->Dataset_model->get_metadata($sid);
        $basic_info = array(
            'schema_type' => $dataset['type'],
            'id' => $dataset['id'],
            'idno' => $dataset['idno']
        );

        $study_doc = array_merge($basic_info, $metadata);

        if ($dataset['type'] == 'survey') {
            $files = $this->ci->Data_file_model->get_all_by_survey($sid);
            if ($files) {
                foreach ($files as $file) {
                    unset($file['id']);
                    unset($file['sid']);
                    $study_doc['data_files'][] = $file;
                }
            }

            $var_groups = $this->ci->Variable_group_model->select_all($sid);
            if ($var_groups) {
                $study_doc['variable_groups'] = $var_groups;
            }
        }

        $study_encoder = new \Violet\StreamingJsonEncoder\StreamJsonEncoder(
            $study_doc,
            function ($json) use ($fp) {
                fwrite($fp, $json);
            }
        );

        $study_encoder->encode();
        fwrite($fp, "\n");

        if ($dataset['type'] == 'survey') {
            foreach ($this->ci->Variable_model->chunk_reader_generator($sid) as $variable) {
                $variable['metadata']['schema_type'] = 'variable';
                $var_record = $variable['metadata'];                

                $var_encoder = new \Violet\StreamingJsonEncoder\StreamJsonEncoder(
                    $var_record,
                    function ($json) use ($fp) {
                        fwrite($fp, $json);
                    }
                );

                $var_encoder->encode();
                fwrite($fp, "\n");
            }
        }

        fclose($fp);

        return $output_path;
    }

    /**
     * Download JSON/JSONL with caching support
     * Checks if cached file exists and is current, generates if needed, then streams to output
     * 
     * @param int $sid - Study ID
     * @param string $format - 'json' or 'jsonl'
     * @param bool $pretty - Whether to pretty print (only for JSON format)
     * @param bool $force_regenerate - Force regeneration even if cache is valid
     * @return void - Streams directly to output
     */
    public function download($sid, $format = 'json', $pretty = false, $force_regenerate = false)
    {
        $this->ci->load->model('Dataset_model');

        if (!$sid) {
            throw new Exception('STUDY NOT FOUND');
        }

        $dataset = $this->ci->Dataset_model->get_row($sid);
        if (!$dataset) {
            throw new Exception('STUDY NOT FOUND: ' . $sid);
        }

        $study_path = $this->ci->Dataset_model->get_storage_fullpath($sid);
        if (!$study_path) {
            throw new Exception("STUDY_FOLDER_NOT_SET");
        }

        $extension = ($format == 'jsonl') ? 'jsonl' : 'json';
        $json_path = $study_path . '/' . $dataset['idno'] . '.' . $extension;

        $generate_file = $force_regenerate;
        if (!$generate_file) {
            if (!file_exists($json_path)) {
                $generate_file = true;
            } elseif (filemtime($json_path) < $dataset['changed']) {
                $generate_file = true;
            }
        }

        if ($generate_file) {
            if (!file_exists($study_path)) {
                mkdir($study_path, 0755, true);
            }

            if ($format == 'jsonl') {
                $this->write_jsonl($sid, $json_path, true);
            } else {
                $this->write_json($sid, $json_path, true, $pretty);
            }
        }

        if (file_exists($json_path)) {
            $content_type = ($format == 'jsonl') ? 'application/x-ndjson' : 'application/json';
            header("Content-Type: {$content_type}; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"" . $dataset['idno'] . ".{$extension}\"");
            header("Cache-Control: public, max-age=3600");
            header("Last-Modified: " . gmdate('D, d M Y H:i:s', filemtime($json_path)) . ' GMT');
            header("ETag: \"" . md5_file($json_path) . "\"");

            $stdout = fopen('php://output', 'w');
            $fh = fopen($json_path, 'r');
            stream_copy_to_stream($fh, $stdout);
            fclose($fh);
            fclose($stdout);
        } else {
            throw new Exception("FAILED_TO_GENERATE_JSON");
        }
    }

    /**
     * Stream JSON/JSONL directly to output without caching
     * 
     * @param int $sid - Study ID
     * @param string $format - 'json' or 'jsonl'
     * @param bool $pretty - Whether to pretty print (only for JSON format)
     * @return void - Streams directly to output
     */
    public function stream($sid, $format = 'json', $pretty = false)
    {
        $this->ci->load->model('Dataset_model');

        if (!$sid) {
            throw new Exception('STUDY NOT FOUND');
        }

        $dataset = $this->ci->Dataset_model->get_row($sid);
        if (!$dataset) {
            throw new Exception('STUDY NOT FOUND: ' . $sid);
        }

        $content_type = ($format == 'jsonl') ? 'application/x-ndjson' : 'application/json';
        header("Content-Type: {$content_type}; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"" . $dataset['idno'] . ".{$format}\"");

        if ($format == 'jsonl') {
            $this->write_jsonl($sid, 'php://output', false);
        } else {
            $this->write_json($sid, 'php://output', false, $pretty);
        }
    }
}
