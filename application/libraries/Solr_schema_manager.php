<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Solr Schema Manager
 * 
 * Manages Solr schema via the Schema API instead of manually editing schema files
 * This provides programmatic control over field definitions, field types, and schema updates
 */
class Solr_schema_manager {
    
    private $ci;
    private $solr_host;
    private $solr_port;
    private $solr_collection;
    private $schema_api_url;
    
    public function __construct() {
        $this->ci =& get_instance();
        
        // Load Solr configuration
        $this->ci->config->load('solr');
        
        // Try multiple ways to get configuration values
        $this->solr_host = $this->get_config_value('solr_host', 'localhost');
        $this->solr_port = $this->get_config_value('solr_port', '8983');
        $this->solr_collection = $this->get_config_value('solr_collection', 'nada');
        
        // Debug: Check if config is loaded
        $this->debug_config_loading();
        
        // Validate configuration values
        if (empty($this->solr_host) || empty($this->solr_port) || empty($this->solr_collection)) {
            log_message('error', 'Solr Schema Manager - Invalid configuration: host=' . $this->solr_host . ', port=' . $this->solr_port . ', collection=' . $this->solr_collection);
        }
        
        $this->schema_api_url = "http://{$this->solr_host}:{$this->solr_port}/solr/{$this->solr_collection}/schema";
        
        log_message('debug', 'Solr Schema Manager - Schema API URL: ' . $this->schema_api_url);
    }
    
    /**
     * Get configuration value with fallback
     * @param string $key Configuration key
     * @param string $default Default value
     * @return string Configuration value or default
     */
    private function get_config_value($key, $default) {
        $value = $this->ci->config->item($key);
        
        if (empty($value)) {
            log_message('error', 'Solr Schema Manager - Configuration key "' . $key . '" not found in solr config file');
            return $default;
        }
        
        return $value;
    }
    
    /**
     * Debug configuration loading
     */
    private function debug_config_loading() {
        $config_fields = $this->ci->config->item('solr_schema_fields');
        $solr_host = $this->ci->config->item('solr_host');
        $solr_port = $this->ci->config->item('solr_port');
        $solr_collection = $this->ci->config->item('solr_collection');
        
        log_message('debug', 'Solr Schema Manager - Config loading debug:');
        log_message('debug', 'solr_host: ' . ($solr_host ?: 'NULL'));
        log_message('debug', 'solr_port: ' . ($solr_port ?: 'NULL'));
        log_message('debug', 'solr_collection: ' . ($solr_collection ?: 'NULL'));
        log_message('debug', 'solr_schema_fields: ' . print_r($config_fields, true));
        
        // Check if config file exists
        $config_file_path = APPPATH . 'config/solr.php';
        log_message('debug', 'Config file path: ' . $config_file_path);
        log_message('debug', 'Config file exists: ' . (file_exists($config_file_path) ? 'YES' : 'NO'));
    }
    
    /**
     * Test Solr connection
     * @return array Connection test results
     */
    public function test_connection() {
        $results = array();
        
        // Test basic connectivity
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->schema_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);
        
        $results['connection'] = array(
            'url' => $this->schema_api_url,
            'http_code' => $http_code,
            'curl_error' => $curl_error,
            'response' => $response,
            'curl_info' => $curl_info
        );
        
        // Test if we can get schema
        if ($http_code === 200) {
            $schema = json_decode($response, true);
            $results['schema_accessible'] = !empty($schema);
            $results['schema_fields_count'] = isset($schema['schema']['fields']) ? count($schema['schema']['fields']) : 0;
        } else {
            $results['schema_accessible'] = false;
            $results['schema_fields_count'] = 0;
        }
        
        return $results;
    }
    
    /**
     * Test a simple schema command to verify format
     * @return array Test results
     */
    public function test_schema_command() {
        // Test with a simple, known-working command
        $test_command = array(
            'add-field' => array(
                'name' => 'test_field_' . time(),
                'type' => 'string',
                'indexed' => true,
                'stored' => true,
                'multiValued' => false
            )
        );
        
        log_message('debug', 'Solr Schema Manager - Testing schema command: ' . print_r($test_command, true));
        
        $result = $this->make_schema_request($test_command);
        
        // If successful, delete the test field
        if (!isset($result['error'])) {
            $delete_command = array(
                'delete-field' => array(
                    'name' => $test_command['add-field']['name']
                )
            );
            $this->make_schema_request($delete_command);
        }
        
        return array(
            'test_command' => $test_command,
            'result' => $result,
            'format_correct' => !isset($result['error'])
        );
    }
    
    /**
     * Add a new field to the schema
     * @param string $field_name Field name
     * @param string $field_type Field type (e.g., 'text_en', 'pint', 'string')
     * @param array $options Additional field options
     * @param bool $replace_existing Whether to replace existing fields
     * @return array Response from Solr API
     */
    public function add_field($field_name, $field_type, $options = array(), $replace_existing = false) {
        // Check if field already exists
        if ($this->field_exists($field_name)) {
            if ($replace_existing) {
                // Return special status for replacement handling
                return array(
                    'status' => 'exists',
                    'field_name' => $field_name,
                    'message' => "Field '$field_name' exists and will be replaced"
                );
            } else {
                return array(
                    'warning' => "Field '$field_name' already exists",
                    'field_name' => $field_name,
                    'status' => 'exists'
                );
            }
        }
        
        // Check if field type exists in Solr schema
        if (!$this->field_type_exists($field_type)) {
            $available_types = $this->get_available_field_types();
            return array(
                'error' => "Field type '$field_type' does not exist in Solr schema",
                'field_name' => $field_name,
                'field_type' => $field_type,
                'available_types' => $available_types,
                'suggestion' => 'Use one of the available field types'
            );
        }
        
        $default_options = array(
            'indexed' => true,
            'stored' => true,
            'multiValued' => false
        );
        
        $field_options = array_merge($default_options, $options);
        
        $field_definition = array(
            'add-field' => array(
                'name' => $field_name,
                'type' => $field_type,
                'indexed' => $field_options['indexed'],
                'stored' => $field_options['stored'],
                'multiValued' => $field_options['multiValued']
            )
        );
        
        log_message('debug', 'Solr Schema Manager - Adding field: ' . $field_name . ' with type: ' . $field_type);
        
        return $this->make_schema_request($field_definition);
    }
    
    /**
     * Add multiple fields at once
     * @param array $fields Array of field definitions
     * @param bool $replace_existing Whether to replace existing fields
     * @return array Response from Solr API
     */
    public function add_fields($fields, $replace_existing = false) {
        // For multiple fields, we need to send them as separate requests
        // Solr Schema API doesn't support batch operations in the way we were trying
        $results = array();
        $success_count = 0;
        $error_count = 0;
        $replaced_count = 0;
        $skipped_count = 0;
        
        foreach ($fields as $field) {
            $result = $this->add_field($field['name'], $field['type'], array(
                'indexed' => isset($field['indexed']) ? $field['indexed'] : true,
                'stored' => isset($field['stored']) ? $field['stored'] : true,
                'multiValued' => isset($field['multiValued']) ? $field['multiValued'] : false
            ), $replace_existing);
            
            if (isset($result['error'])) {
                $error_count++;
                $results[] = array(
                    'field' => $field['name'],
                    'status' => 'error',
                    'error' => $result['error']
                );
            } elseif (isset($result['status']) && $result['status'] === 'exists') {
                if ($replace_existing) {
                    // Replace the existing field
                    $replace_result = $this->replace_field($field['name'], $field);
                    if (isset($replace_result['error'])) {
                        $error_count++;
                        $results[] = array(
                            'field' => $field['name'],
                            'status' => 'replace_error',
                            'error' => $replace_result['error']
                        );
                    } else {
                        $replaced_count++;
                        $results[] = array(
                            'field' => $field['name'],
                            'status' => 'replaced',
                            'old_type' => 'existing',
                            'new_type' => $field['type']
                        );
                    }
                } else {
                    // Skip existing field
                    $skipped_count++;
                    $results[] = array(
                        'field' => $field['name'],
                        'status' => 'skipped',
                        'reason' => 'Field already exists'
                    );
                }
            } else {
                $success_count++;
                $results[] = array(
                    'field' => $field['name'],
                    'status' => 'success'
                );
            }
        }
        
        return array(
            'total_fields' => count($fields),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'replaced_count' => $replaced_count,
            'skipped_count' => $skipped_count,
            'results' => $results,
            'copy_field_operations' => $replace_existing ? 'Copy fields handled automatically' : 'No copy field operations needed'
        );
    }
    
    /**
     * Delete a field from the schema
     * @param string $field_name Field name to delete
     * @return array Response from Solr API
     */
    public function delete_field($field_name) {
        $field_definition = array(
            'delete-field' => array(
                'name' => $field_name
            )
        );
        
        return $this->make_schema_request($field_definition);
    }
    
    /**
     * Replace a field definition
     * @param string $field_name Field name
     * @param array $new_definition New field definition
     * @return array Response from Solr API
     */
    public function replace_field($field_name, $new_definition) {
        // Check if field exists
        if (!$this->field_exists($field_name)) {
            return array(
                'error' => "Field '$field_name' does not exist, cannot replace"
            );
        }
        
        // First delete the old field
        $delete_response = $this->delete_field($field_name);
        
        if (isset($delete_response['error'])) {
            return array(
                'error' => "Failed to delete old field: " . $delete_response['error']
            );
        }
        
        // Then add the new field
        $add_response = $this->add_field($field_name, $new_definition['type'], $new_definition);
        
        if (isset($add_response['error'])) {
            return array(
                'error' => "Failed to add new field: " . $add_response['error'],
                'field_deleted' => true,
                'warning' => "Field '$field_name' was deleted but new field could not be added"
            );
        }
        
        return array(
            'success' => "Field '$field_name' replaced successfully",
            'old_field_deleted' => true,
            'new_field_added' => true,
            'new_definition' => $new_definition
        );
    }
    
    /**
     * Get current schema information
     * @return array Schema information from Solr
     */
    public function get_schema() {
        $url = $this->schema_api_url;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        } else {
            return array('error' => "HTTP $http_code: Failed to get schema");
        }
    }
    
    /**
     * Get list of all fields
     * @return array List of field names
     */
    public function get_fields() {
        $schema = $this->get_schema();
        
        if (isset($schema['error'])) {
            return $schema;
        }
        
        $fields = array();
        if (isset($schema['schema']['fields'])) {
            foreach ($schema['schema']['fields'] as $field) {
                $fields[] = $field['name'];
            }
        }
        
        return $fields;
    }
    
    /**
     * Check if a field exists
     * @param string $field_name Field name to check
     * @return bool True if field exists
     */
    public function field_exists($field_name) {
        $fields = $this->get_fields();
        
        if (is_array($fields) && !isset($fields['error'])) {
            return in_array($field_name, $fields);
        }
        
        return false;
    }
    
    /**
     * Setup variable fields with var_ prefix
     * @param bool $replace_existing Whether to replace existing fields
     * @return array Response from Solr API
     */
    public function setup_variable_fields($replace_existing = false) {
        $config_fields = $this->ci->config->item('solr_schema_fields');
        
        // Debug: Log what we're getting from config
        log_message('debug', 'Solr Schema Manager - Config fields: ' . print_r($config_fields, true));
        
        if (empty($config_fields) || !isset($config_fields['variable_fields'])) {
            return array('error' => 'Variable fields not configured in solr config file');
        }
        
        $variable_fields = $config_fields['variable_fields'];
        
        // Debug: Log what fields we're going to process
        log_message('debug', 'Solr Schema Manager - Processing variable fields: ' . print_r($variable_fields, true));
        
        if (empty($variable_fields)) {
            return array('error' => 'Variable fields array is empty in solr config');
        }
        
        // If replacing fields, handle copy field dependencies first
        if ($replace_existing) {
            $copy_field_results = $this->handle_copy_field_dependencies($variable_fields);
            if (isset($copy_field_results['error'])) {
                return $copy_field_results;
            }
        }
        
        return $this->add_fields($variable_fields, $replace_existing);
    }
    

    
    /**
     * Setup survey fields for denormalized variables (no prefix)
     * @param bool $replace_existing Whether to replace existing fields
     * @return array Response from Solr API
     */
    public function setup_survey_fields($replace_existing = false) {
        // Use survey_document_fields (consolidated schema)
        // This method is kept for backward compatibility with existing API endpoints
        return $this->setup_survey_document_fields($replace_existing);
    }
    
    /**
     * Setup survey document fields (doctype=1) - comprehensive schema for actual survey documents
     * @param bool $replace_existing Whether to replace existing fields
     * @return array Response from Solr API
     */
    public function setup_survey_document_fields($replace_existing = false) {
        $config_fields = $this->ci->config->item('solr_schema_fields');
        
        if (empty($config_fields) || !isset($config_fields['survey_document_fields'])) {
            return array('error' => 'Survey document fields not configured in solr config file');
        }
        
        $survey_document_fields = $config_fields['survey_document_fields'];
        
        if (empty($survey_document_fields)) {
            return array('error' => 'Survey document fields array is empty in solr config');
        }
        
        // If replacing fields, handle copy field dependencies first
        if ($replace_existing) {
            $copy_field_results = $this->handle_copy_field_dependencies($survey_document_fields);
            if (isset($copy_field_results['error'])) {
                return $copy_field_results;
            }
        }
        
        return $this->add_fields($survey_document_fields, $replace_existing);
    }
    

    
    /**
     * Setup complete schema for denormalized variables
     * @param bool $replace_existing Whether to replace existing fields
     * @return array Response from Solr API
     */
    public function setup_complete_schema($replace_existing = false) {
        $results = array();
        
        // Setup variable fields
        $var_result = $this->setup_variable_fields($replace_existing);
        $results['variable_fields'] = $var_result;
        
        // Setup survey fields
        $survey_result = $this->setup_survey_fields($replace_existing);
        $results['survey_fields'] = $survey_result;
        
        return $results;
    }
    
    /**
     * Make a request to the Solr Schema API
     * @param array $data Data to send to the API
     * @return array Response from Solr API
     */
    private function make_schema_request($data) {
        // Log the data being sent for debugging
        log_message('debug', 'Solr Schema Manager - Sending data: ' . print_r($data, true));
        
        $json_data = json_encode($data);
        
        // Log the JSON being sent
        log_message('debug', 'Solr Schema Manager - JSON data: ' . $json_data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->schema_api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data)
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the response for debugging
        log_message('debug', 'Solr Schema Manager - Response: ' . $response);
        log_message('debug', 'Solr Schema Manager - HTTP Code: ' . $http_code);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        } else {
            return array(
                'error' => "HTTP $http_code: Failed to update schema",
                'response' => $response,
                'http_code' => $http_code,
                'sent_data' => $data
            );
        }
    }
    
    /**
     * Validate schema setup
     * @return array Validation results
     */
    public function validate_schema() {
        $config_fields = $this->ci->config->item('solr_schema_fields');
        
        if (empty($config_fields)) {
            return array('error' => 'Schema fields not configured in solr config');
        }
        
        $required_fields = array();
        $field_groups = array();
        
        // Add variable fields
        if (isset($config_fields['variable_fields'])) {
            $variable_fields = array();
            foreach ($config_fields['variable_fields'] as $field) {
                $field_name = $field['name'];
                $required_fields[] = $field_name;
                $variable_fields[] = $field_name;
            }
            $field_groups['variable_fields'] = $variable_fields;
        }
        
        // Add survey document fields (for actual survey documents and embedding in variable documents)
        if (isset($config_fields['survey_document_fields'])) {
            $survey_doc_fields = array();
            foreach ($config_fields['survey_document_fields'] as $field) {
                $field_name = $field['name'];
                $required_fields[] = $field_name;
                $survey_doc_fields[] = $field_name;
            }
            $field_groups['survey_document_fields'] = $survey_doc_fields;
        }
        
        $existing_fields = $this->get_fields();
        $missing_fields = array();
        $present_fields = array();
        $group_status = array();
        
        foreach ($required_fields as $field) {
            if (in_array($field, $existing_fields)) {
                $present_fields[] = $field;
            } else {
                $missing_fields[] = $field;
            }
        }
        
        // Check status for each field group
        foreach ($field_groups as $group_name => $group_fields) {
            $group_missing = array();
            $group_present = array();
            foreach ($group_fields as $field) {
                if (in_array($field, $existing_fields)) {
                    $group_present[] = $field;
                } else {
                    $group_missing[] = $field;
                }
            }
            $group_status[$group_name] = array(
                'total' => count($group_fields),
                'present' => count($group_present),
                'missing' => count($group_missing),
                'complete' => empty($group_missing),
                'missing_fields' => $group_missing
            );
        }
        
        return array(
            'total_required' => count($required_fields),
            'present' => count($present_fields),
            'missing' => count($missing_fields),
            'present_fields' => $present_fields,
            'missing_fields' => $missing_fields,
            'status' => empty($missing_fields) ? 'complete' : 'incomplete',
            'field_groups' => $group_status,
            'config_fields' => $config_fields
        );
    }
    
    /**
     * Check schema setup status - simplified version for quick checks
     * @return array Status information
     */
    public function check_schema_setup_status() {
        try {
            $validation = $this->validate_schema();
            
            if (isset($validation['error'])) {
                return array(
                    'setup_complete' => false,
                    'status' => 'error',
                    'message' => $validation['error'],
                    'can_index' => false
                );
            }
            
            $setup_complete = ($validation['status'] === 'complete');
            $critical_fields_exist = true;
            
            // Check for critical fields that are absolutely required
            $critical_fields = array('doctype');
            $existing_fields = $this->get_fields();
            
            foreach ($critical_fields as $critical_field) {
                if (!in_array($critical_field, $existing_fields)) {
                    $critical_fields_exist = false;
                    break;
                }
            }
            
            $can_index = $setup_complete && $critical_fields_exist;
            
            return array(
                'setup_complete' => $setup_complete,
                'status' => $validation['status'],
                'total_required' => $validation['total_required'],
                'present' => $validation['present'],
                'missing' => $validation['missing'],
                'missing_fields' => $validation['missing_fields'],
                'field_groups' => $validation['field_groups'],
                'critical_fields_exist' => $critical_fields_exist,
                'can_index' => $can_index,
                'message' => $setup_complete 
                    ? 'Schema setup is complete' 
                    : 'Schema setup is incomplete. ' . count($validation['missing_fields']) . ' field(s) missing.'
            );
        } catch (Exception $e) {
            return array(
                'setup_complete' => false,
                'status' => 'error',
                'message' => 'Failed to check schema status: ' . $e->getMessage(),
                'can_index' => false
            );
        }
    }
    
    /**
     * Get schema statistics
     * @return array Schema statistics
     */
    public function get_schema_stats() {
        $schema = $this->get_schema();
        
        if (isset($schema['error'])) {
            return $schema;
        }
        
        $stats = array(
            'total_fields' => 0,
            'indexed_fields' => 0,
            'stored_fields' => 0,
            'multi_valued_fields' => 0,
            'field_types' => array()
        );
        
        // Get field group counts from validation
        $validation = $this->validate_schema();
        if (isset($validation['field_groups'])) {
            foreach ($validation['field_groups'] as $group_name => $group_data) {
                if ($group_name === 'variable_fields') {
                    $stats['variable_fields'] = isset($group_data['present']) ? $group_data['present'] : 0;
                    $stats['variable_fields_count'] = isset($group_data['total']) ? $group_data['total'] : 0;
                } elseif ($group_name === 'survey_document_fields') {
                    $stats['survey_fields'] = isset($group_data['present']) ? $group_data['present'] : 0;
                    $stats['survey_fields_count'] = isset($group_data['total']) ? $group_data['total'] : 0;
                    // Also set survey_document_fields for consistency
                    $stats['survey_document_fields'] = isset($group_data['present']) ? $group_data['present'] : 0;
                    $stats['survey_document_fields_count'] = isset($group_data['total']) ? $group_data['total'] : 0;
                }
            }
        }
        
        if (isset($schema['schema']['fields'])) {
            foreach ($schema['schema']['fields'] as $field) {
                $stats['total_fields']++;
                
                if (isset($field['indexed']) && $field['indexed']) {
                    $stats['indexed_fields']++;
                }
                
                if (isset($field['stored']) && $field['stored']) {
                    $stats['stored_fields']++;
                }
                
                if (isset($field['multiValued']) && $field['multiValued']) {
                    $stats['multi_valued_fields']++;
                }
                
                $field_type = isset($field['type']) ? $field['type'] : 'unknown';
                if (!isset($stats['field_types'][$field_type])) {
                    $stats['field_types'][$field_type] = 0;
                }
                $stats['field_types'][$field_type]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get configured variable fields
     * @return array List of variable field definitions
     */
    public function get_configured_variable_fields() {
        $config_fields = $this->ci->config->item('solr_schema_fields');
        return isset($config_fields['variable_fields']) ? $config_fields['variable_fields'] : array();
    }
    
    /**
     * Get configured survey fields
     * @return array List of survey field definitions
     */
    public function get_configured_survey_fields() {
        $config_fields = $this->ci->config->item('solr_schema_fields');
        // Return survey_document_fields (consolidated schema)
        return isset($config_fields['survey_document_fields']) ? $config_fields['survey_document_fields'] : array();
    }
    
    /**
     * Get all configured fields
     * @return array All field definitions from config
     */
    public function get_all_configured_fields() {
        return $this->ci->config->item('solr_schema_fields');
    }
    
    /**
     * Get available field types from Solr schema
     * @return array Available field types
     */
    public function get_available_field_types() {
        $schema = $this->get_schema();
        
        if (isset($schema['error'])) {
            return $schema;
        }
        
        $field_types = array();
        if (isset($schema['schema']['fieldTypes'])) {
            foreach ($schema['schema']['fieldTypes'] as $field_type) {
                $field_types[] = $field_type['name'];
            }
        }
        
        return $field_types;
    }
    
    /**
     * Check if a field type exists in Solr schema
     * @param string $field_type Field type name
     * @return bool True if field type exists
     */
    public function field_type_exists($field_type) {
        $available_types = $this->get_available_field_types();
        
        if (is_array($available_types) && !isset($available_types['error'])) {
            return in_array($field_type, $available_types);
        }
        
        return false;
    }
    
    /**
     * Update field properties (only some properties can be updated)
     * @param string $field_name Field name
     * @param array $properties Properties to update
     * @return array Response from Solr API
     */
    public function update_field_properties($field_name, $properties) {
        // Check if field exists
        if (!$this->field_exists($field_name)) {
            return array(
                'error' => 'Field \'' . $field_name . '\' does not exist, cannot update'
            );
        }
        
        // Only certain properties can be updated
        $updatable_properties = array('defaultValue', 'required');
        $update_data = array();
        
        foreach ($properties as $key => $value) {
            if (in_array($key, $updatable_properties)) {
                $update_data[$key] = $value;
            } else {
                log_message('warning', 'Property \'' . $key . '\' cannot be updated for field \'' . $field_name . '\'');
            }
        }
        
        if (empty($update_data)) {
            return array(
                'error' => 'No updatable properties found for field \'' . $field_name . '\''
            );
        }
        
        $field_definition = array(
            'replace-field' => array_merge(
                array('name' => $field_name),
                $update_data
            )
        );
        
        return $this->make_schema_request($field_definition);
    }
    
    /**
     * Add a field type to Solr schema
     * @param string $field_type_name Name of the field type
     * @param string $class Solr field class (e.g., "solr.TextField")
     * @param array $analyzer_config Analyzer configuration
     * @param array $options Additional options (positionIncrementGap, etc.)
     * @param bool $replace_existing Whether to replace existing field type
     * @return array Response from Solr API
     */
    public function add_field_type($field_type_name, $class, $analyzer_config, $options = array(), $replace_existing = false) {
        // Check if field type already exists
        if ($this->field_type_exists($field_type_name)) {
            if ($replace_existing) {
                // Need to delete first, then add
                $delete_result = $this->delete_field_type($field_type_name);
                if (isset($delete_result['error'])) {
                    return array(
                        'error' => "Failed to delete existing field type '$field_type_name': " . $delete_result['error']
                    );
                }
            } else {
                return array(
                    'warning' => "Field type '$field_type_name' already exists",
                    'field_type_name' => $field_type_name,
                    'status' => 'exists'
                );
            }
        }
        
        $field_type_definition = array(
            'name' => $field_type_name,
            'class' => $class
        );
        
        // Add positionIncrementGap if provided
        if (isset($options['positionIncrementGap'])) {
            $field_type_definition['positionIncrementGap'] = $options['positionIncrementGap'];
        }
        
        // Add analyzer configuration
        if (!empty($analyzer_config)) {
            $field_type_definition['analyzer'] = $analyzer_config;
        }
        
        $request_data = array(
            'add-field-type' => $field_type_definition
        );
        
        log_message('debug', 'Solr Schema Manager - Adding field type: ' . $field_type_name . ' with class: ' . $class);
        
        return $this->make_schema_request($request_data);
    }
    
    /**
     * Delete a field type from Solr schema
     * @param string $field_type_name Name of the field type to delete
     * @return array Response from Solr API
     */
    public function delete_field_type($field_type_name) {
        if (!$this->field_type_exists($field_type_name)) {
            return array(
                'warning' => "Field type '$field_type_name' does not exist",
                'field_type_name' => $field_type_name,
                'status' => 'not_found'
            );
        }
        
        $request_data = array(
            'delete-field-type' => array(
                'name' => $field_type_name
            )
        );
        
        log_message('debug', 'Solr Schema Manager - Deleting field type: ' . $field_type_name);
        
        return $this->make_schema_request($request_data);
    }
    
    /**
     * Copy field data from one field to another
     * @param string $source_field Source field name
     * @param string $dest_field Destination field name
     * @return array Response from Solr API
     */
    public function copy_field($source_field, $dest_field) {
        // Check if source field exists
        if (!$this->field_exists($source_field)) {
            return array(
                'error' => "Source field '$source_field' does not exist"
            );
        }
        
        // Check if destination field exists
        if ($this->field_exists($dest_field)) {
            return array(
                'error' => "Destination field '$dest_field' already exists"
            );
        }
        
        $copy_definition = array(
            'add-copy-field' => array(
                'source' => $source_field,
                'dest' => $dest_field
            )
        );
        
        return $this->make_schema_request($copy_definition);
    }
    
    /**
     * Get field usage information
     * @param string $field_name Field name to check
     * @return array Field usage details
     */
    public function get_field_usage($field_name) {
        $schema = $this->get_schema();
        
        if (isset($schema['error'])) {
            return $schema;
        }
        
        $usage = array(
            'field_name' => $field_name,
            'field_exists' => false,
            'copy_fields_source' => array(),
            'copy_fields_dest' => array(),
            'field_definition' => null,
            'can_delete' => true,
            'delete_reason' => null
        );
        
        // Check if field exists
        if (isset($schema['schema']['fields'])) {
            foreach ($schema['schema']['fields'] as $field) {
                if ($field['name'] === $field_name) {
                    $usage['field_exists'] = true;
                    $usage['field_definition'] = $field;
                    break;
                }
            }
        }
        
        // Check copy fields
        if (isset($schema['schema']['copyFields'])) {
            foreach ($schema['schema']['copyFields'] as $copy_field) {
                if ($copy_field['source'] === $field_name) {
                    $usage['copy_fields_source'][] = $copy_field;
                }
                if ($copy_field['dest'] === $field_name) {
                    $usage['copy_fields_dest'][] = $copy_field;
                }
            }
        }
        
        // Determine if field can be deleted
        if (!empty($usage['copy_fields_source'])) {
            $usage['can_delete'] = false;
            $usage['delete_reason'] = 'Field is used as source in copy fields';
        }
        
        if (!empty($usage['copy_fields_dest'])) {
            $usage['can_delete'] = false;
            $usage['delete_reason'] = 'Field is used as destination in copy fields';
        }
        
        return $usage;
    }
    
    /**
     * Get all copy fields
     * @return array Copy field definitions
     */
    public function get_copy_fields() {
        $schema = $this->get_schema();
        
        if (isset($schema['error'])) {
            return $schema;
        }
        
        return isset($schema['schema']['copyFields']) ? $schema['schema']['copyFields'] : array();
    }
    
    /**
     * Delete a copy field
     * @param string $source Source field name
     * @param string $dest Destination field name
     * @return array Response from Solr API
     */
    public function delete_copy_field($source, $dest) {
        $copy_definition = array(
            'delete-copy-field' => array(
                'source' => $source,
                'dest' => $dest
            )
        );
        
        return $this->make_schema_request($copy_definition);
    }
    
    /**
     * Handle copy field dependencies before replacing fields
     * @param array $fields Array of field definitions
     * @return array Results of copy field operations
     */
    private function handle_copy_field_dependencies($fields) {
        $results = array(
            'copy_fields_removed' => array(),
            'copy_fields_errors' => array(),
            'fields_checked' => array()
        );
        
        // Get current copy fields
        $copy_fields = $this->get_copy_fields();
        if (isset($copy_fields['error'])) {
            return array('error' => 'Failed to get copy fields: ' . $copy_fields['error']);
        }
        
        // Extract field names that will be replaced
        $field_names = array();
        foreach ($fields as $field) {
            $field_names[] = $field['name'];
        }
        
        // Check each copy field for dependencies
        foreach ($copy_fields as $copy_field) {
            $source = $copy_field['source'];
            $dest = $copy_field['dest'];
            
            // Check if source field is being replaced
            if (in_array($source, $field_names)) {
                log_message('info', 'Removing copy field: ' . $source . ' -> ' . $dest . ' (source field will be replaced)');
                
                $delete_result = $this->delete_copy_field($source, $dest);
                if (isset($delete_result['error'])) {
                    $results['copy_fields_errors'][] = array(
                        'source' => $source,
                        'dest' => $dest,
                        'error' => $delete_result['error']
                    );
                } else {
                    $results['copy_fields_removed'][] = array(
                        'source' => $source,
                        'dest' => $dest,
                        'status' => 'removed'
                    );
                }
            }
            
            // Check if destination field is being replaced
            if (in_array($dest, $field_names)) {
                log_message('info', 'Removing copy field: ' . $source . ' -> ' . $dest . ' (destination field will be replaced)');
                
                $delete_result = $this->delete_copy_field($source, $dest);
                if (isset($delete_result['error'])) {
                    $results['copy_fields_errors'][] = array(
                        'source' => $source,
                        'dest' => $dest,
                        'error' => $delete_result['error']
                    );
                } else {
                    $results['copy_fields_removed'][] = array(
                        'source' => $source,
                        'dest' => $dest,
                        'status' => 'removed'
                    );
                }
            }
        }
        
        $results['fields_checked'] = $field_names;
        
        // If there were errors removing copy fields, return error
        if (!empty($results['copy_fields_errors'])) {
            return array(
                'error' => 'Failed to remove some copy fields',
                'details' => $results
            );
        }
        
        return $results;
    }
}
