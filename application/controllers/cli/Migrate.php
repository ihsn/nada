<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->input->is_cli_request()) {
            show_error('This script can only be accessed via the command line');
        }
        
        // Prevent timeouts for long-running migrations
        $this->configure_for_long_migrations();
        
        $this->load->database();
        $this->load->config('migration');
        
        if ($this->config->item('migration_enabled') !== TRUE) {
            echo "Error: Migrations are disabled.\n";
            echo "Enable migrations in application/config/migration.php\n";
            echo "Set: \$config['migration_enabled'] = TRUE;\n";
            exit(1);
        }
        
        // Check and disable database debug mode for security
        $this->ensure_db_debug_disabled();
        
        $this->load->library('migration');
    }
    
    /**
     * Configure PHP and database settings for long-running migrations
     * 
     * This prevents timeouts during:
     * - Large data imports
     * - Complex schema changes
     * - Index creation on large tables
     * - Batch updates
     */
    private function configure_for_long_migrations()
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "Configuring for Long-Running Migrations\n";
        echo str_repeat('=', 80) . "\n";
        
        // Remove PHP execution time limit
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        echo "✓ PHP execution time limit: unlimited\n";
        
        // Increase memory limit if needed
        $current_memory = ini_get('memory_limit');
        $current_bytes = $this->parse_memory_limit($current_memory);
        $recommended_bytes = 512 * 1024 * 1024; // 512MB
        
        if ($current_memory == '-1') {
            echo "✓ Memory limit: unlimited\n";
        } elseif ($current_bytes < $recommended_bytes) {
            ini_set('memory_limit', '512M');
            echo "✓ Memory limit: increased to 512M (was {$current_memory})\n";
        } else {
            echo "✓ Memory limit: {$current_memory}\n";
        }
        
        // Display current PHP settings
        $max_input_time = ini_get('max_input_time');
        echo "✓ Max input time: " . ($max_input_time == '-1' ? 'unlimited' : $max_input_time . 's') . "\n";
        
        echo str_repeat('=', 80) . "\n\n";
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parse_memory_limit($limit)
    {
        if ($limit == -1 || $limit == '-1') {
            return PHP_INT_MAX;
        }
        
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int)$limit;
        
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    public function index()
    {
        echo "NADA Migration Tool\n";
        echo "===================\n\n";
        echo "Available commands:\n";
        echo "  php index.php cli/migrate current          - Show current migration version\n";
        echo "  php index.php cli/migrate latest           - Migrate to latest version\n";
        echo "  php index.php cli/migrate version <ver>    - Migrate to specific version\n";
        echo "  php index.php cli/migrate list             - List available migrations\n";
        echo "\n";
    }

    public function current()
    {
        $version = $this->get_current_version();
        echo "Current migration version: " . ($version ? $version : 'None') . "\n";
    }
    
    public function list_migrations()
    {
        echo "Available Migrations:\n";
        echo "=====================\n\n";
        
        $migrations = $this->get_available_migrations();
        $current = $this->get_current_version();
        
        if (empty($migrations)) {
            echo "No migration files found.\n";
            return;
        }
        
        foreach ($migrations as $migration) {
            $status = '';
            if ($migration['version'] == $current) {
                $status = ' [CURRENT]';
            } elseif ($migration['version'] < $current) {
                $status = ' [APPLIED]';
            } else {
                $status = ' [PENDING]';
            }
            
            echo $migration['version'] . ' - ' . $migration['name'] . $status . "\n";
        }
        echo "\n";
    }

    public function latest()
    {
        $this->ensure_migrations_initialized();
        
        if ($this->migration->latest() === FALSE) {
            echo "Error: " . $this->migration->error_string() . "\n";
            exit(1);
        }
        
        echo "Migration to latest version completed successfully!\n";
    }

    public function version($target_version = null)
    {
        if (!$target_version) {
            echo "Error: Version number required\n";
            echo "Usage: php index.php cli/migrate version 20251022000001\n";
            exit(1);
        }
        
        $this->ensure_migrations_initialized();
        
        echo "Migrating to version {$target_version}...\n";
        
        if ($this->migration->version($target_version) === FALSE) {
            echo "Error: " . $this->migration->error_string() . "\n";
            exit(1);
        }
        
        echo "✓ Migration to version {$target_version} completed successfully!\n";
    }
    
    public function set_version($version = null)
    {
        if (!$version) {
            echo "Error: Version number required\n";
            echo "Usage: php index.php cli/migrate set_version <version>\n";
            echo "Example: php index.php cli/migrate set_version 20251022000001\n";
            exit(1);
        }
        
        // Validate version format (timestamp: 14 digits)
        if (!preg_match('/^\d{14}$/', $version)) {
            echo "Error: Invalid version format\n";
            echo "Expected 14-digit timestamp (e.g., 20251022000001)\n";
            exit(1);
        }
        
        echo "Setting migration version to {$version}...\n";
        
        try {
            // Ensure migrations table exists
            if (!$this->db->table_exists('migrations')) {
                $this->load->dbforge();
                $this->dbforge->add_field(array(
                    'version' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE),
                ));
                $this->dbforge->add_key('version', TRUE);
                $this->dbforge->create_table('migrations', TRUE);
                echo "✓ Migrations table created\n";
            }
            
            // Clear and set new version
            $this->db->truncate('migrations');
            $result = $this->db->insert('migrations', array('version' => $version));
            
            if ($result) {
                echo "✓ Migration version set to: {$version}\n";
                
                // Verify
                $current = $this->get_current_version();
                echo "✓ Verified current version: {$current}\n";
            } else {
                echo "✗ Failed to set migration version\n";
                exit(1);
            }
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function ensure_migrations_initialized()
    {
        // Create migrations table if it doesn't exist
        if (!$this->db->table_exists('migrations')) {
            $this->load->dbforge();
            $this->dbforge->add_field(array(
                'version' => array('type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE),
            ));
            $this->dbforge->add_key('version', TRUE);
            $this->dbforge->create_table('migrations', TRUE);
            echo "✓ Migrations table created\n";
        }
        
        // CodeIgniter migrations require the table to have at least one row
        // If empty, initialize with version 0
        $query = $this->db->get('migrations');
        if ($query->num_rows() == 0) {
            $this->db->insert('migrations', array('version' => 0));
            echo "✓ Migrations table initialized with version 0\n";
        }
    }
    
    private function get_current_version()
    {
        if (!$this->db->table_exists('migrations')) {
            return '0';
        }
        
        // Get the latest version (in case there are multiple rows)
        $this->db->select('version');
        $this->db->order_by('version', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('migrations');
        $row = $query->row();
        
        return $row ? (string)$row->version : '0';
    }
    
    private function get_available_migrations()
    {
        $migrations = array();
        $migration_path = APPPATH . 'migrations/';
        
        if (!is_dir($migration_path)) {
            return $migrations;
        }
        
        $files = scandir($migration_path);
        
        foreach ($files as $file) {
            if (preg_match('/^(\d{14})_(.+)\.php$/', $file, $matches)) {
                $migrations[] = array(
                    'version' => $matches[1],
                    'name' => ucwords(str_replace('_', ' ', $matches[2])),
                    'file' => $file
                );
            }
        }
        
        return $migrations;
    }
    
    /**
     * Ensure database debug mode is disabled for security during migrations
     * 
     * Database debug mode can expose sensitive information in error messages.
     * This method checks if it's enabled and disables it for the migration session.
     */
    private function ensure_db_debug_disabled()
    {
        if ($this->db->db_debug === TRUE) {
            echo "⚠ Warning: Database debug mode is currently ENABLED\n";
            echo "  Disabling db_debug for this migration session for security...\n";
            
            // Disable for this session only
            $this->db->db_debug = FALSE;
            
            echo "✓ Database debug mode disabled\n";
            echo "  Note: Update application/config/database.php to permanently disable:\n";
            echo "  \$db['default']['db_debug'] = FALSE;\n\n";
        }
    }
}

