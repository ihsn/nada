<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Create_analytics_aggregation_status extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Create_analytics_aggregation_status::up() called');
        
        $db_driver = $this->db->dbdriver;
        
        // Check if table already exists
        $table_exists = false;
        if ($db_driver === 'mysqli') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'analytics_aggregation_status'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $table_exists = ($row->cnt > 0);
            }
        } elseif ($db_driver === 'sqlsrv') {
            $result = $this->db->query("
                SELECT COUNT(*) as cnt 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'analytics_aggregation_status'
            ");
            if ($result && $result->num_rows() > 0) {
                $row = $result->row();
                $table_exists = ($row->cnt > 0);
            }
        }
        
        if ($table_exists) {
            echo "Table analytics_aggregation_status already exists. Skipping creation.\n";
            log_message('info', 'Table analytics_aggregation_status already exists');
            return;
        }
        
        // Create table
        $this->load->dbforge();
        
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ),
            'status' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'idle'
            ),
            'current_step' => array(
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ),
            'current_item' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ),
            'total_items' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => true
            ),
            'processed_items' => array(
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => true
            ),
            'progress_percent' => array(
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
                'null' => true
            ),
            'message' => array(
                'type' => 'TEXT',
                'null' => true
            ),
            'started_at' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'completed_at' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'last_updated_at' => array(
                'type' => 'DATETIME',
                'null' => true
            ),
            'error_message' => array(
                'type' => 'TEXT',
                'null' => true
            ),
            'context' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'cli',
                'comment' => 'cli or web'
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'null' => true
            )
        );
        
        $this->dbforge->add_field($fields);
        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('status');
        $this->dbforge->add_key('last_updated_at');
        
        if ($this->dbforge->create_table('analytics_aggregation_status', true)) {
            echo "Table analytics_aggregation_status created successfully.\n";
            log_message('info', 'Table analytics_aggregation_status created successfully');
            
            // Insert initial idle row
            $this->db->insert('analytics_aggregation_status', array(
                'status' => 'idle',
                'last_updated_at' => date('Y-m-d H:i:s')
            ));
        } else {
            $error = $this->db->error();
            $error_msg = is_array($error) ? ($error['message'] ?? 'Unknown error') : 'Unknown error';
            echo "Error creating table analytics_aggregation_status: {$error_msg}\n";
            log_message('error', "Error creating table analytics_aggregation_status: {$error_msg}");
            throw new Exception("Failed to create table: {$error_msg}");
        }
    }

    public function down()
    {
        throw new Exception("Rollback not supported.");
    }
}
