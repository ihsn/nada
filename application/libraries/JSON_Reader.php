<?php

/**
 * Parse study export JSON Lines (JSONL) for catalog import.
 *
 * Format matches JSON_Writer::write_jsonl(): line 1 = study document, further lines = variables.
 */
class JSON_Reader
{
    /**
     * Read a JSONL file into a single create_dataset() options array.
     *
     * @param string $path
     * @return array study payload including variables[] for surveys
     */
    public function parse_jsonl_file($path)
    {
        if (! is_file($path)) {
            throw new Exception('JSONL_FILE_NOT_FOUND');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new Exception('JSONL_OPEN_FAILED');
        }

        $study    = null;
        $variables = array();

        try {
            $line_no = 0;
            while (($line = fgets($handle)) !== false) {
                $line_no++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $decoded = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    throw new Exception('INVALID_JSONL_LINE:' . $line_no);
                }

                if ($study === null) {
                    $study = $decoded;
                    continue;
                }

                if ($this->_line_is_variable($decoded)) {
                    $variables[] = $this->_normalize_variable_line($decoded);
                }
            }
        } finally {
            fclose($handle);
        }

        if ($study === null) {
            throw new Exception('JSONL_EMPTY');
        }

        if ($variables !== array()) {
            $study['variables'] = $variables;
        }

        return $study;
    }


    /**
     * Read only the first (study) document from a JSONL export file.
     *
     * @param string $path
     * @return array
     */
    public function parse_study_line_from_jsonl($path)
    {
        if (! is_file($path)) {
            throw new Exception('JSONL_FILE_NOT_FOUND');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new Exception('JSONL_OPEN_FAILED');
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    throw new Exception('INVALID_JSONL_STUDY_LINE');
                }
                return $decoded;
            }
        } finally {
            fclose($handle);
        }

        throw new Exception('JSONL_EMPTY');
    }


    /**
     * Group variable lines by file_id and write one JSON array file per data file.
     *
     * @param string $jsonl_path
     * @param string $out_dir
     * @return array list of file_id strings
     */
    public function build_variable_sidecars_by_file($jsonl_path, $out_dir)
    {
        if (! is_file($jsonl_path)) {
            throw new Exception('JSONL_FILE_NOT_FOUND');
        }
        if (! is_dir($out_dir) && ! @mkdir($out_dir, 0777, true)) {
            throw new Exception('VAR_SIDECAR_DIR_FAILED');
        }

        $handle = fopen($jsonl_path, 'r');
        if ($handle === false) {
            throw new Exception('JSONL_OPEN_FAILED');
        }

        $by_file   = array();
        $seen_study = false;

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    throw new Exception('INVALID_JSONL_LINE');
                }

                if (! $seen_study) {
                    $seen_study = true;
                    continue;
                }

                if (! $this->_line_is_variable($decoded)) {
                    continue;
                }

                $var = $this->_normalize_variable_line($decoded);
                $fid = isset($var['file_id']) ? (string) $var['file_id'] : '';
                if ($fid === '') {
                    continue;
                }
                if (! isset($by_file[$fid])) {
                    $by_file[$fid] = array();
                }
                $by_file[$fid][] = $var;
            }
        } finally {
            fclose($handle);
        }

        $file_ids = array();
        foreach ($by_file as $fid => $variables) {
            $safe_fid  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fid);
            $out_path  = $out_dir . '/variables-' . $safe_fid . '.json';
            $encoded   = json_encode($variables, JSON_UNESCAPED_UNICODE);
            if ($encoded === false || file_put_contents($out_path, $encoded) === false) {
                throw new Exception('VAR_SIDECAR_WRITE_FAILED:' . $fid);
            }
            $file_ids[] = $fid;
        }

        sort($file_ids, SORT_NATURAL);
        return $file_ids;
    }


    /**
     * @param array $decoded
     * @return bool
     */
    private function _line_is_variable($decoded)
    {
        if (isset($decoded['schema_type']) && strtolower((string) $decoded['schema_type']) === 'variable') {
            return true;
        }
        if (isset($decoded['file_id'], $decoded['name']) && ! isset($decoded['study_desc'], $decoded['series_description'])) {
            return true;
        }
        return false;
    }


    /**
     * @param array $decoded
     * @return array
     */
    private function _normalize_variable_line($decoded)
    {
        unset($decoded['schema_type']);
        return $decoded;
    }
}
