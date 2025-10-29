<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Upgrade_resources_table extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Upgrade_resources_table::up() called');
        
        $sql_file = $this->get_sql_file_path('nada55-upgrade');
        
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found: " . $sql_file);
        }
        
        log_message('info', 'Starting resources table upgrade migration...');
        $this->execute_sql_file($sql_file);
        log_message('info', 'Resources table upgrade migration completed successfully');
        
        // Update resource_type column using PHP (SQL Server string manipulation doesn't work reliably)
        log_message('info', 'Starting resource_type extraction from dctype...');
        $this->update_resource_types_from_dctype();
        log_message('info', 'Resource_type extraction completed successfully');
    }
    
    /**
     * Extract resource_type codes from dctype strings using PHP
     * 
     * This method queries all unique dctype values, extracts the code using PHP regex,
     * then updates each dctype group with the extracted code.
     */
    private function update_resource_types_from_dctype()
    {
        // Get all unique dctype values
        $query = $this->db->query("
            SELECT DISTINCT dctype 
            FROM resources 
            WHERE dctype IS NOT NULL");
        
        $dctypes = $query->result_array();
        $total = count($dctypes);
        $updated = 0;
        
        log_message('info', "Found {$total} unique dctype values to process");
        
        foreach ($dctypes as $row) {
            $dctype = $row['dctype'];
            
            // Extract code from dctype
            // e.g. "Document, Administrative [doc/adm]" -> "doc/adm"
            // or if no brackets: "Table" -> "table"
            preg_match_all("/\[([^\]]*)\]/", $dctype, $matches);
            
            if (isset($matches[1][0])) {
                // Has brackets, extract code
                $resource_type = trim($matches[1][0]);
            } else {
                // No brackets, use dctype value as-is
                $resource_type = trim($dctype);
            }
            
            // Always lowercase
            $resource_type = strtolower($resource_type);
            
            if (!empty($resource_type)) {
                // Update all resources with this dctype value
                $this->db->where('dctype', $dctype);
                $this->db->update('resources', array('resource_type' => $resource_type));
                
                $affected = $this->db->affected_rows();
                $updated += $affected;
                
                log_message('info', "Updated {$affected} resources: dctype='{$dctype}' -> resource_type='{$resource_type}'");
            } else {
                log_message('warning', "Could not extract resource_type from dctype: '{$dctype}'");
            }
        }
        
        log_message('info', "Total resources updated with resource_type: {$updated}");
    }

    public function down()
    {
        throw new Exception("Rollback not supported - this is a one-way migration. Restore from database backup if needed.");
    }
}

