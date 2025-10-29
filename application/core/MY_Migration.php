<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Migration extends CI_Migration {

    protected function execute_sql_file($filename)
    {
        $templine = '';
        $lines = file($filename);
        $skipped = 0;
        
        foreach ($lines as $line_num => $line) {
            if (substr(trim($line), 0, 2) == '--' || substr(trim($line), 0, 1) == '#' || trim($line) == '') {
                continue;
            }
            
            $templine .= $line;
            
            if (substr(trim($line), -1, 1) == ';' || strtoupper(substr(trim($line), -2)) == 'GO') {
                $templine = str_replace(';', '', $templine);
                $templine = str_replace('GO', '', $templine);
                $templine = trim($templine);
                
                if (!empty($templine)) {
                    // Execute with error suppression
                    $result = @$this->db->query($templine);
                    
                    if ($result === FALSE) {
                        $error = $this->db->error();
                        $error_code = $error['code'];
                        
                        if ($this->is_safe_to_skip_error($error_code, $this->db->dbdriver)) {
                            $skipped++;
                            log_message('info', "Skipped (error {$error_code}): " . substr($templine, 0, 100) . '...');
                        } else {
                            $error_msg = "Migration failed at line {$line_num}\n";
                            $error_msg .= "Error {$error_code}: {$error['message']}\n";
                            $error_msg .= "SQL: {$templine}";
                            log_message('error', $error_msg);
                            throw new Exception($error_msg);
                        }
                    } else {
                        log_message('info', 'Migration statement succeeded: ' . substr($templine, 0, 100) . '...');
                    }
                }
                
                $templine = '';
            }
        }
        
        if ($skipped > 0) {
            log_message('info', "Migration completed with {$skipped} statements skipped (already applied)");
        }
        
        log_message('info', "SQL file execution completed: {$filename}");
    }
    
    protected function is_safe_to_skip_error($error_code, $db_driver)
    {
        if (in_array($db_driver, array('mysql', 'mysqli'))) {
            $safe_errors = array(
                1060,
                1061,
                1091,
                1050,
                1068,
                1146,
            );
        } elseif ($db_driver == 'sqlsrv') {
            $safe_errors = array(
                1712,
                1913,
                2714,
                3728,
            );
        } else {
            $safe_errors = array();
        }
        
        return in_array($error_code, $safe_errors);
    }
    
    protected function get_sql_file_path($filename)
    {
        $db_driver = $this->db->dbdriver;
        
        if (in_array($db_driver, array('mysql', 'mysqli'))) {
            $db_driver = 'mysql';
        }
        
        return APPPATH . '../install/' . $filename . '-' . $db_driver . '.sql';
    }
}

