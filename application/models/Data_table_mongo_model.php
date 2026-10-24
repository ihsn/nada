<?php

use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\Constraint;
use League\Csv\Reader;

require_once 'modules/mongodb/vendor/autoload.php';

class Data_table_mongo_model extends CI_Model {

    //table type object holds table definition[features, codelists, etc]
    private $table_type_obj=null;

    private $mongo_client;

    private $db_name;

    public function __construct() 
    {
        parent::__construct();
        $this->config->load('mongo');        
        $this->db_name=$this->config->item("mongodb_database");
        $this->mongo_client=$this->get_db_connection();
    }


    function get_db_connection()
    {
        $username=$this->config->item("mongodb_username");
        $password=$this->config->item("mongodb_password");
        $host=$this->config->item("mongodb_host");
        $port=$this->config->item("mongodb_port");


	    if (!empty($username) && !empty($password)){
            return new MongoDB\Client(
                "mongodb://${host}:${port}",
                    array(
                        "username" => $username, 
                        "password" => $password, 
                        "db"=> $this->get_db_name(), 
                        'authSource' => $this->get_db_name() 
            ));
        }
        
        return new MongoDB\Client(
            "mongodb://${host}:${port}",
                array(
                    "db"=> $this->get_db_name(), 
                )
        );

    }




    private function get_db_name()
    {
        if(empty($this->db_name)){
            throw new Exception("MongoDB Database not set, check application config for mongo.");
        }
        
        return $this->db_name;
    }

    
    private function get_table_name($db_id,$table_id)
    {
        return strtolower('table_'.$db_id.'_'.$table_id);
    }
    


    /**
     * Coerce API insert rows using the data dictionary (canonical field names).
     *
     * @param string $db_id
     * @param string $table_id
     * @param array $rows List of row objects
     * @return array
     * @throws Exception
     */
    public function coerce_insert_rows($db_id, $table_id, array $rows)
    {
        $metadata_map = $this->get_field_metadata_map($db_id, $table_id);
        if (empty($metadata_map)) {
            throw new Exception('Data dictionary is required before inserting rows');
        }

        $coerced_rows = array();
        foreach ($rows as $row_index => $row) {
            if (!is_array($row)) {
                throw new Exception('Row ' . $row_index . ' must be an object');
            }
            $coerced = array();
            foreach ($row as $column => $value) {
                $field_info = $this->get_field_info_from_metadata($column, $metadata_map);
                if ($field_info === null) {
                    $coerced[$column] = $value;
                    continue;
                }
                try {
                    $coerced[$field_info['name']] = $this->coerce_import_value(
                        $value,
                        $field_info['data_type']
                    );
                } catch (Exception $e) {
                    throw new Exception(
                        'Row ' . $row_index . ', field "' . $column . '": ' . $e->getMessage()
                    );
                }
            }
            $coerced_rows[] = $coerced;
        }

        return $coerced_rows;
    }

    public function table_batch_insert($db_id,$table_id,$rows)
    {
        $result = $this->table_batch_insert_with_errors($db_id, $table_id, $rows);
        if (!empty($result['failed_indexes'])) {
            $first = $result['failed_indexes'][0];
            throw new Exception("ERROR::".$first['message']);
        }
        if (!$result['inserted']) {
            throw new Exception("ERROR::insertMany inserted 0 documents");
        }
        return $result['inserted'];
    }

    /**
     * insertMany with ordered:false. Partial write errors are returned per document index
     * instead of aborting the whole batch.
     *
     * @return array{inserted:int,failed_indexes:array<int,array{index:int,code:int,message:string}>}
     */
    public function table_batch_insert_with_errors($db_id, $table_id, $rows)
    {
        if (empty($rows)) {
            return array('inserted' => 0, 'failed_indexes' => array());
        }

        $collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};

        try {
            $insertManyResult = $collection->insertMany($rows, array('ordered' => false));
            return array(
                'inserted' => (int) $insertManyResult->getInsertedCount(),
                'failed_indexes' => array()
            );
        } catch (\Exception $e) {
            if (method_exists($e, 'getWriteResult')) {
                $writeResult = $e->getWriteResult();
                if ($writeResult) {
                    $failed = array();
                    foreach ($writeResult->getWriteErrors() as $writeError) {
                        $failed[] = array(
                            'index' => (int) $writeError->getIndex(),
                            'code' => (int) $writeError->getCode(),
                            'message' => (string) $writeError->getMessage()
                        );
                    }
                    return array(
                        'inserted' => (int) $writeResult->getInsertedCount(),
                        'failed_indexes' => $failed
                    );
                }
            }
            throw new Exception("ERROR::". utf8_encode($e->getMessage()));
        }
    }



   //return storage info for all tables (collections) in db
   function get_tables_list()
   {
       $database=$this->mongo_client->{$this->get_db_name()};
       $cursor = $database->command(['listCollections' => 1, 'nameOnly'=> true ]);

       $output=array();
       foreach ($cursor as $collection) {
            $coll_stats=$this->get_collection_info($this->get_db_name(),$collection['name']);
            $output[$collection['name']]=array(
                'name'=>$collection['name'],
                'storageUnit'=>'M',
                'size'=>$coll_stats['size'],
                'count'=>$coll_stats['count'],
                'storageSize'=>$coll_stats['storageSize'],
                'nindexes'=>$coll_stats['nindexes'],
                'indexNames'=>array_keys((array)$coll_stats['indexDetails'])
            );
       }

       return $output;
   } 
   

   /**
    * 
    * Return collection info
    * 
    * 
    */
    function get_collection_info($db_name,$table_name)
    {
        $database = $this->mongo_client->{$db_name};
        $collection_info = $database->command(['collStats' => $table_name, 'scale'=> 1024*1024 ]);
        return $collection_info->toArray()[0];
    }


    /**
    * 
    * Rename collectinon
    * 
    * 
    */
    function rename_collection($old_name, $new_name)
    {
        $db_name=$this->get_db_name();
     
        //enable admin priviledges
        $admin = $this->mongo_client->admin;
     
        $result= $admin->command(array(
            'renameCollection'=> $db_name.'.'.$old_name,
            'to'=> $db_name.'.'.$new_name)
        );
        return $result->toArray()[0];
    }



   /**
    * 
    * Return table type information
    * 
    * 
    */
   function get_table_info($db_id,$table_id)
   {
       $table_id=strtolower($table_id);
       $result= $this->get_collection_info($this->get_db_name(),$this->get_table_name($db_id,$table_id));
       $result['table_type']=$this->get_table_type($db_id,$table_id);       
       return $result;
   }

   /**
    * 
    * Return the table definition
    *
    */
   function get_table_type($db_id,$table_id)
   {
       $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
       $result = $collection->findOne(
           [
               '_id'=>$this->get_table_name($db_id,$table_id)
           ]
       );

       if ($result === null) {
           return null;
       }

       return $this->mongo_document_to_array($result);
   }

   /**
    * MongoDB find/findOne return BSONDocument for nested objects.
    * Convert to a plain array so typed helpers and array access stay consistent.
    */
   private function mongo_document_to_array($document)
   {
       if (is_array($document)) {
           return $document;
       }
       if ($document === null) {
           return array();
       }
       $decoded = json_decode(json_encode($document), true);
       return is_array($decoded) ? $decoded : array();
   }


   /**
    * 
    * Check if a table definition exists
    *
    */
   function table_type_exists($db_id,$table_id)
   {
       $type=$this->get_table_type($db_id,$table_id);

       if(empty($type) ){
           return false;
       };

       return true;
   }


   /**
    * Validate and normalize db_id and table_id
    * - Only allows alphanumeric characters and underscores
    * - Converts to lowercase
    * 
    * @param string $id The id to validate and normalize
    * @param string $type Either 'db_id' or 'table_id' for error messages
    * @return string Normalized id
    * @throws Exception If validation fails
    */
   function validate_and_normalize_id($id, $type = 'id')
   {
       if (empty($id)) {
           throw new Exception("Missing Param:: {$type}");
       }

       // Convert to string and trim
       $id = trim((string)$id);
       
       // Check if it contains only alphanumeric characters, underscores and hyphens
       if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
           throw new Exception("Invalid {$type}: Only alphanumeric characters, underscores and hyphens are allowed");
       }

       return strtolower($id);
   }

   function get_table_types_list($db_id)
   {
      $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
       $projection_options=[
            '_id'=>1,
            'db_id'=>1,
            'table_id'=>1,
            'title'=>1, 
            'description'=>1,
            'created_at'=>1,
            'updated_at'=>1
       ];

       $filter_options=array();

       if (!empty($db_id)){
           $filter_options=array(
               'db_id' => $db_id
           );
       }

       $result = $collection->find(
           $filter_options,
           [    
               'projection'=>$projection_options
           ]           
       );
       
       $output=array();
       foreach($result as $item){
            $output[$item['_id']]=$item;
       }

       return $output;
   }

	/**
	 * Paginated table definitions from table_types (skip/limit in Mongo).
	 *
	 * @param string|null $db_id Optional database filter
	 * @param int $limit
	 * @param int $offset
	 * @return array{total:int,tables:array}
	 */
	function get_table_types_paginated($db_id, $limit, $offset)
	{
		$collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
		$projection_options = array(
			'_id' => 1,
			'db_id' => 1,
			'table_id' => 1,
			'title' => 1,
			'description' => 1,
			'created_at' => 1,
			'updated_at' => 1
		);

		$filter_options = array(
			'db_id' => array('$exists' => true, '$nin' => array(null, '')),
			'table_id' => array('$exists' => true, '$nin' => array(null, ''))
		);
		if (!empty($db_id)) {
			$filter_options['db_id'] = $db_id;
		}

		$limit = max(1, (int) $limit);
		$offset = max(0, (int) $offset);

		$total = (int) $collection->countDocuments($filter_options);
		$cursor = $collection->find(
			$filter_options,
			array(
				'projection' => $projection_options,
				'skip' => $offset,
				'limit' => $limit,
				'sort' => array('updated_at' => -1, '_id' => 1)
			)
		);

		$output = array();
		$db_name = $this->get_db_name();
		foreach ($cursor as $item) {
			$key = (string) $item['_id'];
			$row = json_decode(json_encode($item), true);
			if (!is_array($row)) {
				$row = array('_id' => $key);
			}
			try {
				$stats = $this->get_collection_info($db_name, $key);
				$row['rows_count'] = isset($stats['count']) ? $stats['count'] : 0;
				$row['storage_size'] = (isset($stats['storageSize']) ? $stats['storageSize'] : 0) . 'M';
				$row['nindexes'] = isset($stats['nindexes']) ? $stats['nindexes'] : 0;
				$row['indexNames'] = isset($stats['indexDetails']) ? array_keys((array) $stats['indexDetails']) : array();
			} catch (Exception $e) {
				$row['rows_count'] = 0;
				$row['storage_size'] = '0M';
				$row['nindexes'] = 0;
				$row['indexNames'] = array();
			}
			$output[$key] = $row;
		}

		return array(
			'total' => $total,
			'tables' => $output
		);
	}

   function get_database_info()
   {
       $database=$this->mongo_client->{$this->get_db_name()};
       $db_info = $database->command(['dbStats' => 1, 'scale'=> 1024*1024 ]);
       return $db_info->toArray()[0];
   }


   function get_collection_indexes($db_id,$table_id)
   {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};

        $indexes=array();
        foreach ($collection->listIndexes() as $index) {
            $indexes[$index->getName()]=$index->getKey();
        }

        return $indexes;
   }


   /**
    * 
    * Create index on a collection
    *
    * @index_options - comma seperated list of field names
    *
    */
   function create_collection_index($db_id,$table_id,$index_options)
   {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};

        $index_options=array_filter(explode(",",$index_options));
        
        if(empty($index_options)){
            return false;
        }

        // Trim whitespace from field names
        $index_options = array_map('trim', $index_options);
        
        // Validate that all field names exist in the data or dictionary (case-sensitive)
        $actual_fields = $this->get_indexable_field_names($db_id,$table_id);
        $invalid_fields = array();
        foreach($index_options as $field){
            if(!empty($actual_fields) && !isset($actual_fields[$field])){
                $invalid_fields[] = $field;
            }
        }
        
        if(!empty($invalid_fields)){
            throw new Exception("Field(s) not found in data: " . implode(", ", $invalid_fields) . ". Field names are case-sensitive (e.g., 'ISO3' is different from 'iso3').");
        }
        
        // Build index definition
        $indexes=array();
        foreach($index_options as $index){
            $indexes[$index]=1;
        }

        // Generate a clean index name (no spaces or special characters)
        // Format: field1_field2_field3_1
        $index_name_parts = array();
        foreach($index_options as $field){
            // Remove spaces and special characters, keep only alphanumeric and underscores
            $clean_field = preg_replace('/[^a-zA-Z0-9_]/', '_', $field);
            // Remove multiple consecutive underscores
            $clean_field = preg_replace('/_+/', '_', $clean_field);
            // Remove leading/trailing underscores
            $clean_field = trim($clean_field, '_');
            if (!empty($clean_field)) {
                $index_name_parts[] = $clean_field;
            }
        }
        
        $index_name = implode('_', $index_name_parts) . '_1';
        
        // If index name is empty or too long, use a hash
        if (empty($index_name) || strlen($index_name) > 120) {
            $index_name = 'idx_' . substr(md5(implode(',', $index_options)), 0, 16) . '_1';
        }

        $result= $collection->createIndex($indexes, array('name' => $index_name));
        $this->upsert_table_index_definition_entry($db_id, $table_id, $index_name, $indexes);
        return $result;
   }


   /**
    * 
    * Create text index on a collection
    *
    * @index_options - comma seperated list of field names
    *
    */
    function create_collection_text_index($db_id,$table_id,$index_options)
    {
         $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
 
         $index_options=array_filter(explode(",",$index_options));
         
         if(empty($index_options)){
             return false;
         }
 
         // Trim whitespace from field names
         $index_options = array_map('trim', $index_options);
         
         // Validate that all field names exist in the data or dictionary (case-sensitive)
         $actual_fields = $this->get_indexable_field_names($db_id,$table_id);
         $invalid_fields = array();
         foreach($index_options as $field){
             if(!empty($actual_fields) && !isset($actual_fields[$field])){
                 $invalid_fields[] = $field;
             }
         }
         
         if(!empty($invalid_fields)){
             throw new Exception("Field(s) not found in data: " . implode(", ", $invalid_fields) . ". Field names are case-sensitive (e.g., 'ISO3' is different from 'iso3').");
         }
         
         // Build index definition
         $indexes=array();
         foreach($index_options as $index){
             $indexes[$index]='text';
         }
 
         // Generate a clean index name for text index
         // Format: text_field1_field2_field3
         $index_name_parts = array('text');
         foreach($index_options as $field){
             // Remove spaces and special characters, keep only alphanumeric and underscores
             $clean_field = preg_replace('/[^a-zA-Z0-9_]/', '_', $field);
             // Remove multiple consecutive underscores
             $clean_field = preg_replace('/_+/', '_', $clean_field);
             // Remove leading/trailing underscores
             $clean_field = trim($clean_field, '_');
             if (!empty($clean_field)) {
                 $index_name_parts[] = $clean_field;
             }
         }
         
         $index_name = implode('_', $index_name_parts);
         
         // If index name is empty or too long, use a hash
         if (empty($index_name) || strlen($index_name) > 120) {
             $index_name = 'text_idx_' . substr(md5(implode(',', $index_options)), 0, 16);
         }

         $result= $collection->createIndex($indexes, array('name' => $index_name));
         $this->upsert_table_index_definition_entry($db_id, $table_id, $index_name, $indexes);
         return $result;
    }


   /**
    * 
    * Delete index on a collection
    *
    * @index_name - name of index
    *
    */
   function delete_collection_index($db_id,$table_id,$index_name)
   {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};          
        $result= $collection->dropIndex($index_name);
        $this->remove_table_index_definition_entry($db_id, $table_id, $index_name);
        return $result;
   }

   /**
    * 
    * Delete all indexes in a collection (except _id_)
    *
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @return array Result with number of indexes dropped
    */
   function delete_all_collection_indexes($db_id,$table_id)
   {
        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        
        // Get count of indexes before deletion (excluding _id_)
        $indexes_before = $this->get_collection_indexes($db_id, $table_id);
        $count_before = count($indexes_before);
        if (isset($indexes_before['_id_'])) {
            $count_before--; // Exclude _id_ from count
        }
        
        // Drop all indexes (this will drop all except _id_)
        $collection->dropIndexes();
        
        // Get count after deletion
        $indexes_after = $this->get_collection_indexes($db_id, $table_id);
        $count_after = count($indexes_after);
        
        $this->clear_table_index_definitions($db_id, $table_id);

        return array(
            'indexes_dropped' => $count_before,
            'indexes_remaining' => $count_after // Should be 1 (_id_)
        );
   }


   /**
    * Query parameters reserved for table data API (not field filters).
    *
    * @return array
    */
   function get_reserved_table_query_params()
   {
        return array(
            'limit',
            'offset',
            'fields',
            'ft_query',
            'debug',
            'format',
            'disposition',
            'indicator',
            'c',
        );
   }

   /**
    * @return array Allowed dictionary data_type values (normalized lowercase).
    */
   function get_allowed_field_data_types()
   {
        return array(
            'string',
            'integer',
            'int',
            'float',
            'decimal',
            'boolean',
            'bool',
            'date',
            'datetime',
            'array',
            'object',
        );
   }

   /**
    * Normalize dictionary data_type; empty/null becomes string.
    *
    * @param mixed $data_type
    * @return string
    */
   function normalize_field_data_type($data_type)
   {
        if ($data_type === null || $data_type === '') {
            return 'string';
        }
        if (!is_string($data_type)) {
            return 'string';
        }
        $normalized = strtolower(trim($data_type));
        if ($normalized === '' || $normalized === 'null') {
            return 'string';
        }
        if ($normalized === 'int') {
            return 'integer';
        }
        if ($normalized === 'bool') {
            return 'boolean';
        }
        if ($normalized === 'decimal' || $normalized === 'double') {
            return 'float';
        }
        if (in_array($normalized, $this->get_allowed_field_data_types(), true)) {
            return $normalized;
        }
        return 'string';
   }

   /**
    * Whether a name is a reserved table data query parameter.
    *
    * @param string $name
    * @return bool
    */
   function is_reserved_table_query_param($name)
   {
        return in_array(strtolower((string) $name), $this->get_reserved_table_query_params(), true);
   }

   /**
    * Validate a table column / dictionary field name.
    *
    * @param string $name
    * @param bool $throw When false, returns false instead of throwing.
    * @return bool
    */
	/**
	 * Trim CSV header cells and strip UTF-8 BOM (common in Excel-exported files).
	 *
	 * @param mixed $name
	 * @return string
	 */
	public function normalize_csv_column_name($name)
	{
		if (!is_string($name)) {
			return trim((string) $name);
		}
		$name = trim($name);
		if (strncmp($name, "\xEF\xBB\xBF", 3) === 0) {
			$name = substr($name, 3);
		}
		$name = preg_replace('/^\x{FEFF}/u', '', $name);
		return trim($name);
	}

   function validate_field_name($name, $throw = true)
   {
        if ($name === null || $name === '') {
            if ($throw) {
                throw new Exception('Field name is required');
            }
            return false;
        }
        if (!is_string($name)) {
            if ($throw) {
                throw new Exception('Field name must be a string');
            }
            return false;
        }
        $name = trim($name);
        if ($name === '') {
            if ($throw) {
                throw new Exception('Field name is required');
            }
            return false;
        }
        if (strlen($name) > 100) {
            if ($throw) {
                throw new Exception('Field name must be at most 100 characters');
            }
            return false;
        }
        if (!preg_match('/^(?!_)[a-zA-Z0-9_]+$/', $name)) {
            if ($throw) {
                throw new Exception(
                    'Invalid field name "' . $name . '": use only letters, numbers, and underscores; must not start with underscore'
                );
            }
            return false;
        }
        return true;
   }

   /**
    * Find case-insensitive duplicate names in a list (e.g. CSV headers).
    *
    * @param array $names
    * @return array[] Groups of names that collide case-insensitively (each group has 2+ entries).
    */
   function find_duplicate_field_names_case_insensitive(array $names)
   {
        $groups = array();
        $fold_map = array();
        foreach ($names as $name) {
            if ($name === null || $name === '') {
                continue;
            }
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $fold = strtolower($name);
            if (!isset($fold_map[$fold])) {
                $fold_map[$fold] = array();
            }
            if (!in_array($name, $fold_map[$fold], true)) {
                $fold_map[$fold][] = $name;
            }
        }
        foreach ($fold_map as $variants) {
            if (count($variants) > 1) {
                $groups[] = $variants;
            }
        }
        return $groups;
   }

   /**
    * @param array $names
    * @throws Exception
    */
   function assert_unique_field_names(array $names)
   {
        $duplicates = $this->find_duplicate_field_names_case_insensitive($names);
        if (empty($duplicates)) {
            return;
        }
        $parts = array();
        foreach ($duplicates as $group) {
            $parts[] = implode(' / ', $group);
        }
        throw new Exception(
            'Duplicate field names (case-insensitive): ' . implode('; ', $parts)
        );
   }

   /**
    * Find dictionary field whose name matches case-insensitively.
    *
    * @return array|null
    */
   function find_dictionary_field_by_case_insensitive_name($db_id, $table_id, $name)
   {
        $fields = $this->get_field_metadata($db_id, $table_id);
        if (!is_array($fields)) {
            return null;
        }
        foreach ($fields as $field) {
            $field = (array) $field;
            if (!isset($field['name'])) {
                continue;
            }
            if (strcasecmp((string) $field['name'], (string) $name) === 0) {
                return $field;
            }
        }
        return null;
   }

   /**
    * Ensure a new or renamed dictionary field does not collide case-insensitively or with reserved params.
    *
    * @throws Exception
    */
   function assert_dictionary_field_name_available($db_id, $table_id, $name)
   {
        $name = trim((string) $name);
        $this->validate_field_name($name);

        if ($this->field_name_conflicts_with_reserved_query_param($name)) {
            throw new Exception(
                'Field name "' . $name . '" is reserved and cannot be used as a column name'
            );
        }

        $existing = $this->find_dictionary_field_by_case_insensitive_name($db_id, $table_id, $name);
        if ($existing !== null && (string) $existing['name'] !== $name) {
            throw new Exception(
                'Duplicate field names (case-insensitive): ' . $existing['name'] . ' / ' . $name
            );
        }
   }

   /**
    * Validate a list of field names (pattern + uniqueness).
    *
    * @param array $names
    * @throws Exception
    */
   function validate_field_name_list(array $names)
   {
        foreach ($names as $name) {
            $this->validate_field_name($name);
        }
        $this->assert_unique_field_names($names);
   }

   /**
    * Dictionary field name matches a reserved query param (not filterable via ?name=).
    *
    * @param string $name
    * @return bool
    */
   function field_name_conflicts_with_reserved_query_param($name)
   {
        return $this->is_reserved_table_query_param($name);
   }

   /**
    * Field names for index validation (data columns, or dictionary when collection is empty).
    */
   function get_indexable_field_names($db_id, $table_id)
   {
        $from_data = $this->get_table_field_names($db_id, $table_id);
        if (!empty($from_data)) {
            return $from_data;
        }

        $fields = $this->get_table_fields($db_id, $table_id, true, array('name' => 1));
        $output = array();
        foreach ($fields as $field) {
            if (!empty($field['name'])) {
                $output[$field['name']] = $field['name'];
            }
        }
        return $output;
   }

   /**
    * Convert Mongo index key document to stored definition entry.
    */
   function mongo_index_key_to_definition_entry($name, $key)
   {
        $keys = array();
        foreach ((array) $key as $field => $direction) {
            if ($field === '_id') {
                continue;
            }
            $keys[] = array(
                'field' => $field,
                'direction' => ($direction === 'text') ? 'text' : (int) $direction
            );
        }
        return array(
            'name' => $name,
            'keys' => $keys
        );
   }

   /**
    * Build Mongo createIndex key array from a stored definition entry.
    */
   function definition_entry_to_mongo_index_keys($entry)
   {
        $indexes = array();
        if (empty($entry['keys']) || !is_array($entry['keys'])) {
            return $indexes;
        }
        foreach ($entry['keys'] as $key_part) {
            if (empty($key_part['field'])) {
                continue;
            }
            $field = $key_part['field'];
            $direction = isset($key_part['direction']) ? $key_part['direction'] : 1;
            if ($direction === 'text') {
                $indexes[$field] = 'text';
            } else {
                $indexes[$field] = (int) $direction;
            }
        }
        return $indexes;
   }

   function get_table_index_definitions($db_id, $table_id)
   {
        $table_type = $this->get_table_type($db_id, $table_id);
        if (!$table_type || empty($table_type['indexes']) || !is_array($table_type['indexes'])) {
            return array();
        }
        return $table_type['indexes'];
   }

   function set_table_index_definitions($db_id, $table_id, array $indexes)
   {
        return $this->update_table_type($db_id, $table_id, array(
            'indexes' => array_values($indexes),
            'indexes_updated_at' => date('Y-m-d H:i:s')
        ));
   }

   /**
    * Persist live collection indexes onto table_types (excludes _id_).
    */
   function sync_table_index_definitions_from_collection($db_id, $table_id)
   {
        $live = $this->get_collection_indexes($db_id, $table_id);
        $definitions = array();
        foreach ($live as $name => $key) {
            if ($name === '_id_') {
                continue;
            }
            $definitions[] = $this->mongo_index_key_to_definition_entry($name, $key);
        }
        $this->set_table_index_definitions($db_id, $table_id, $definitions);
        return $definitions;
   }

   function upsert_table_index_definition_entry($db_id, $table_id, $name, $mongo_keys)
   {
        $definitions = $this->get_table_index_definitions($db_id, $table_id);
        $entry = $this->mongo_index_key_to_definition_entry($name, $mongo_keys);
        $found = false;
        foreach ($definitions as $i => $def) {
            if (isset($def['name']) && $def['name'] === $name) {
                $definitions[$i] = $entry;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $definitions[] = $entry;
        }
        $this->set_table_index_definitions($db_id, $table_id, $definitions);
   }

   function remove_table_index_definition_entry($db_id, $table_id, $name)
   {
        $definitions = $this->get_table_index_definitions($db_id, $table_id);
        $filtered = array();
        foreach ($definitions as $def) {
            if (!isset($def['name']) || $def['name'] !== $name) {
                $filtered[] = $def;
            }
        }
        $this->set_table_index_definitions($db_id, $table_id, $filtered);
   }

   function clear_table_index_definitions($db_id, $table_id)
   {
        $this->set_table_index_definitions($db_id, $table_id, array());
   }

   /**
    * Create indexes on the data collection from table_types.indexes.
    *
    * @param bool $missing_only Skip indexes that already exist by name
    * @return array applied, skipped, errors
    */
   function apply_table_index_definitions($db_id, $table_id, $missing_only = true)
   {
        $db_id = strtolower($db_id);
        $table_id = strtolower($table_id);
        $definitions = $this->get_table_index_definitions($db_id, $table_id);
        $live = array();
        try {
            $live = $this->get_collection_indexes($db_id, $table_id);
        } catch (Exception $e) {
            $live = array();
        }

        $collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
        $field_names = $this->get_indexable_field_names($db_id, $table_id);

        $applied = array();
        $skipped = array();
        $errors = array();

        foreach ($definitions as $entry) {
            if (empty($entry['name'])) {
                continue;
            }
            $name = $entry['name'];
            if ($missing_only && isset($live[$name])) {
                $skipped[] = $name;
                continue;
            }

            $mongo_keys = $this->definition_entry_to_mongo_index_keys($entry);
            if (empty($mongo_keys)) {
                $errors[] = array('name' => $name, 'message' => 'Empty index keys');
                continue;
            }

            $invalid = array();
            foreach (array_keys($mongo_keys) as $field) {
                if (empty($field_names) || isset($field_names[$field])) {
                    continue;
                }
                $invalid[] = $field;
            }
            if (!empty($invalid) && !empty($field_names)) {
                $errors[] = array(
                    'name' => $name,
                    'message' => 'Field(s) not found: ' . implode(', ', $invalid)
                );
                continue;
            }

            try {
                $collection->createIndex($mongo_keys, array('name' => $name));
                $applied[] = $name;
            } catch (Exception $e) {
                $errors[] = array('name' => $name, 'message' => $e->getMessage());
            }
        }

        return array(
            'applied' => $applied,
            'skipped' => $skipped,
            'errors' => $errors,
            'definition_count' => count($definitions)
        );
   }


   /**
	 * 
	 * 
	 * Get table data
	 * 
	 * Filters
	 * - table id
	 * - region_type
	 * - state_code
	 * - district_code
	 * - subdistrict_code
	 * - town_code
	 * - ward_code
	 * 
	 * - features
	 * 
	 * 
	 * 
	 * 
	 * 
	 */
   function get_table_data($db_id,$table_id,$limit=100,$offset=0,$options)
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>10000){
            $limit=100;
        }        

        $table_id=strtolower($table_id);

        //get table type info
        $this->table_type_obj= $this->get_table_type($db_id,$table_id);

        $fields=$this->get_table_field_names($db_id,$table_id);
        $field_metadata_map = $this->get_field_metadata_map($db_id, $table_id);

        $feature_filters=array();
        $filter_options = $this->build_filter_options_from_request($options, $field_metadata_map);

        $tmp_feature_filters=array();

        foreach($filter_options as $feature_key=>$value){
            $tmp_feature_filters[$feature_key]=$this->apply_feature_filter($feature_key,$value,$field_metadata_map);
        }

        if(isset($options['ft_query']) && !empty($options['ft_query'])){
            $tmp_feature_filters['ft_query'][]['$text']=$this->text_search($options['ft_query']);
        }
        
        $feature_filters=array();

        if (!empty($tmp_feature_filters)){

            $feature_filters=array(
                '$and'=> array()
            );

            foreach($tmp_feature_filters as $feature_key=>$filter){
                $feature_filters['$and'][]['$or']=$filter;
            }
        }
        

        /*
        $feature_filters_example=array(

            '$and'=> array(
                array(
                '$or'=> array(
                    array( 
                        'sex' => array( '$in' => array(1) ), 
                        'age'=> array(
                            '$gte' => 10,
                            '$lte' => 15                    
                        )
                    ) 
                )
                )
            )
        );
        */

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};        

        if (!isset($options['fields'])){
            $options['fields']="";
        }

        //which fields to display
        $output_fields=$this->get_projection_fields($options['fields']);

        $cursor = $collection->find(
            $feature_filters,
            [
                'projection'=>$output_fields,
                'limit' => $limit,
                'skip'  => $offset
            ]
        );

        $output=array();

        if (isset($options['debug'])){
            $output['options']=$options;
            $output['features']=$features;        
            $output['feature_filters']=$feature_filters;
        }

        $output['rows_count']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;
        $output['found']=$collection->count($feature_filters);
        $output['total']=$collection->count();        
        $output['data']=array();
        
        foreach ($cursor as $document) {
            //convert to array from mongodb object
            $output['data'][]= iterator_to_array($document);
        }

        $output['rows_count']=count($output['data']);
        return $output;
   } 


   /**
    * 
    *
    *  Export data from a table
    *  
    *  - output_format - json, csv
    * 
    */
    function export_data($db_id, $table_id, $output_format = 'json', $options = array())
    {
        $table_id = strtolower($table_id);
    
        $output_file_name = $db_id . '_' . $table_id . '_' . base64_encode(json_encode($options)) . '.' . $output_format;
        $output_file_name = 'datafiles/tmp/' . $output_file_name;
        $download_file_name = $db_id . '_' . $table_id . '_' . date('Y-m-d') . '.' . $output_format;
    
        if (file_exists($output_file_name)) {
            // check if the file is older than 5 hours
            if (filemtime($output_file_name) < (time() - 5 * 3600)) {
                unlink($output_file_name);
            } else {
                $this->download_file($output_file_name, $output_format, $download_file_name);
                return;
            }
        }
    
        $this->table_type_obj = $this->get_table_type($db_id, $table_id);    
        $fields = $this->get_table_field_names($db_id, $table_id);
        $field_metadata_map = $this->get_field_metadata_map($db_id, $table_id);
    
        $feature_filters = array();
        $filter_options = $this->build_filter_options_from_request($options, $field_metadata_map);

        $tmp_feature_filters = array();

        foreach ($filter_options as $feature_key => $value) {
            $tmp_feature_filters[$feature_key] = $this->apply_feature_filter($feature_key, $value, $field_metadata_map);
        }

        if (isset($options['ft_query']) && !empty($options['ft_query'])) {
            $tmp_feature_filters['ft_query'][]['$text'] = $this->text_search($options['ft_query']);
        }

        $feature_filters = array();

        if (!empty($tmp_feature_filters)) {
            $feature_filters = array('$and' => array());

            foreach ($tmp_feature_filters as $feature_key => $filter) {
                $feature_filters['$and'][]['$or'] = $filter;
            }
        }

        $collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
    
        if (!isset($options['fields'])) {
            $options['fields'] = "";
        }
    
        // Which fields to display
        $output_fields = $this->get_projection_fields($options['fields']);
    
        $cursor = $collection->find(
            $feature_filters,
            [
                'projection' => $output_fields
            ]
        );

        $file_handle = fopen($output_file_name, 'w');

        $rows_found= $collection->count($feature_filters);

        if ($output_format === 'json') {
            // Start JSON array for output
            fwrite($file_handle, '[');
    
            $first_row = true;
            foreach ($cursor as $document) {
                // Convert MongoDB object to array
                $data = iterator_to_array($document);
    
                // Write JSON for each document
                if (!$first_row) {
                    fwrite($file_handle, ',');
                }
                fwrite($file_handle, json_encode($data));
    
                $first_row = false;
            }
    
            // End JSON array
            fwrite($file_handle, ']');

        } elseif ($output_format === 'csv') {
            // Prepare CSV format
            $headers_written = false;
            $batch_size = 1000; // Set batch size for flushing data
            $batch_data = [];
    
            foreach ($cursor as $document) {
                // Convert MongoDB object to array
                $data = iterator_to_array($document);
    
                // Write headers if not already written
                if (!$headers_written) {
                    fputcsv($file_handle, array_keys($data));
                    $headers_written = true;
                }
    
                // Store data in batch
                $batch_data[] = $data;
    
                // Write batch to CSV after reaching batch size
                if (count($batch_data) >= $batch_size) {
                    foreach ($batch_data as $row) {
                        fputcsv($file_handle, $row);
                    }
                    // Clear the batch data
                    $batch_data = [];
                }
            }
    
            // Write any remaining data in the batch
            if (count($batch_data) > 0) {
                foreach ($batch_data as $row) {
                    fputcsv($file_handle, $row);
                }
            }
        }    
        fclose($file_handle);        
        $this->download_file($output_file_name, $output_format);
    }
    

    /**
	 * 
     * Download CSV file associated with a table
     * 
	 */
	public function download_csv_file($db_id, $table_id)
	{
		$table_definition = $this->get_table_type($db_id, $table_id);
		
		if (!$table_definition) {
			throw new Exception("Table definition not found - please upload a file first");
		}

		if (!isset($table_definition['csv_file_path']) || empty($table_definition['csv_file_path'])) {
			throw new Exception("No CSV file path found in table definition - please upload a file first");
		}

		$validated_file_path = validate_file_path($table_definition['csv_file_path'], $db_id, $table_id);
		$full_file_path = 'datafiles/' . $validated_file_path;

		if (!file_exists($full_file_path)) {
			throw new Exception("CSV file not found: " . $validated_file_path);
		}

        $this->download_file($full_file_path, 'csv', $db_id . '_' . $table_id . '.csv');		
	}


    function download_file($file_path, $output_format = 'json', $download_file_name=null)
    {
        if (!file_exists($file_path)) {
            throw new Exception("File not found: " . $file_path);
        }

        if ($download_file_name === null) {
            $download_file_name = basename($file_path);
        }

        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/' . $output_format);
        header('Content-Disposition: attachment; filename=' . $download_file_name);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));

        readfile($file_path);
        exit;
    }
   

   function get_projection_fields($fields)
   {
        $fields=explode(",",$fields);
        $fields=array_filter($fields);

        $output=array(
            '_id'=>0
        );

        foreach($fields as $field){            
            $output[$field]=1;
        }

        return $output;
   }




    /**
     * 
     * parse value formats
     * 
     * - age=1-12
     * - age=1,2,3,4,5,6
     * - age=1-12,24-18,7,9
     * 
     */
   function parse_filter_value($value)
   {
       $output=array();

       $values=explode(",",$value);

       foreach($values as $val){

            //check for values enclosed in quotes e.g. "0-4"
            if (substr($val,0,1)=='"'){
                $output[]=array(
                    'type'=>'value',
                    'value'=>str_replace('"','',$val)
                );
                continue;
            }
			
			//gte or lte 
			if (substr($val,0,1)=='>' && is_numeric(substr($val,1))){
                $output[]=array(
                    'type'=>'gte',
                    'value'=>substr($val,1)
                );
                continue;
            }
			
			if (substr($val,0,1)=='<' && is_numeric(substr($val,1))){
                $output[]=array(
                    'type'=>'lte',
                    'value'=>substr($val,1)
                );
                continue;
            }
            if (substr($val,0,4)=='&lt;' && is_numeric(substr($val,4))){
                $output[]=array(
                    'type'=>'lte',
                    'value'=>substr($val,4)
                );
                continue;
            }
            
            $range=explode("-",$val);

            if(count($range)==2){
                $output[]=array(
                    'type'=>'range',
                    'start'=>$range[0],
                    'end'=>$range[1]
                );
            }else{
                $output[]=array(
                    'type'=>'value',
                    'value'=>$val
                );
            }
       }
       return $output;
   }


   function text_search($keywords)
   {        
        /* format for text search
        return ['$text' => ['$search' => "jammu"]];
        */
        
        $keywords=explode(" ", $keywords);
        $output=array();
        foreach($keywords as $keyword){
            $output[]='"'.str_replace('"','',trim($keyword)).'"'; 
        }

        return array('$search' => implode(" ", $output));
   }


   function apply_feature_filter($feature_name,$value,$field_metadata_map=null)
   {
        $parsed_val=$this->parse_filter_value($value);

        $output=array();
        $values=array();

        $field_type = null;
        if ($field_metadata_map && isset($field_metadata_map[$feature_name])) {
            $data_type = $field_metadata_map[$feature_name]['data_type'];
            $data_type_lower = strtolower($data_type);
            if ($data_type_lower === 'string') {
                $field_type = 'string';
            } elseif (in_array($data_type_lower, array('integer', 'int'))) {
                $field_type = 'int';
            } elseif (in_array($data_type_lower, array('float', 'decimal', 'double'))) {
                $field_type = 'float';
            }
        }

        foreach($parsed_val as $val){
            if($val['type']=='range')
            {
                $start=(int)$val['start'];
                $end=(int)$val['end'];
            
                $output[][$feature_name]= array(
                        '$gte' => $start,
                        '$lte' => $end
                );
            }else if($val['type']=='value'){
                $val_value = $val['value'];
                if (is_numeric($val_value)) {
                    if ($field_type === 'string') {
                        $values[] = (string)$val_value;
                    } elseif ($field_type === 'int') {
                        $values[] = (int)$val_value;
                    } elseif ($field_type === 'float') {
                        $values[] = (float)$val_value;
                    } else {
                        $values[] = (int)$val_value;
                    }
                } else {
                    $values[] = $val_value;
                }
            }
			else if($val['type']=='gte'){
                $val_value = $val['value'];
                $gte_value = $val_value;
                if (is_numeric($val_value)) {
                    if ($field_type === 'string') {
                        $gte_value = (string)$val_value;
                    } elseif ($field_type === 'int') {
                        $gte_value = (int)$val_value;
                    } elseif ($field_type === 'float') {
                        $gte_value = (float)$val_value;
                    } else {
                        $gte_value = (int)$val_value;
                    }
                }
				$output[][$feature_name]= array(
                        '$gte' => $gte_value                        
                );
            }
			else if($val['type']=='lte'){
                $val_value = $val['value'];
                $lte_value = $val_value;
                if (is_numeric($val_value)) {
                    if ($field_type === 'string') {
                        $lte_value = (string)$val_value;
                    } elseif ($field_type === 'int') {
                        $lte_value = (int)$val_value;
                    } elseif ($field_type === 'float') {
                        $lte_value = (float)$val_value;
                    } else {
                        $lte_value = (int)$val_value;
                    }
                }
				$output[][$feature_name]= array(
                        '$lte' => $lte_value                        
                );
            }
        }

        if (count($values)>0){
            $values_in=array();
            $values_nin=array();

            foreach($values as $value){
                if (trim($value)==''){continue;}

                if (substr($value,0,1)=='!'){
                    $nin_=substr($value,1,strlen($value));
                    if (is_numeric($nin_)) {
                        if ($field_type === 'string') {
                            $nin_ = (string)$nin_;
                        } elseif ($field_type === 'int') {
                            $nin_ = (int)$nin_;
                        } elseif ($field_type === 'float') {
                            $nin_ = (float)$nin_;
                        } else {
                            $nin_ = $nin_ + 0;
                        }
                    }
                    $values_nin[]=$nin_;

                }
                else{
                    $values_in[]=$value;
                }
            }

            if (count($values_in)>0){
                $output[][$feature_name]= 
                    array(
                        '$in'=>$values_in
                );
            }
            if (count($values_nin)>0){
                $output[][$feature_name]= 
                    array(
                        '$nin'=>$values_nin
                );
            }
        }

        return $output;

        
        if (count($output)==1){
            return $output[0];
        }
        else if (count($output)>1){
            return $output;
            return array(
                '$and' => 
                    array('$or'=>$output)
            );
        }
   }

    
   
   function create_table($db_id,$table_id,$options)
   {
        $table_id=strtolower($table_id);
        $db_id=strtolower($db_id);

        //schema file name
        $schema_name='census-table_type';

        //validate schema
        $this->validate_schema($schema_name,$options);
    
        //remove table definition if already exists
        $this->delete_table_type($db_id,$table_id);

        $options['db_id']=(string)$db_id;
        $options['table_id']=(string)$table_id;

        $options['_id']=$this->get_table_name($db_id,$table_id);
        $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
        $result = $collection->insertOne($options);
        $inserted_count=$result->getInsertedCount();
        
        if (!$inserted_count){
			throw new Exception($result);
        }
        
        return $inserted_count;
   }

   function update_table_type($db_id,$table_id,$update_data)
   {
        $table_id=strtolower($table_id);
        $db_id=strtolower($db_id);

        $collection=$this->mongo_client->{$this->get_db_name()}->{'table_types'};
        $result = $collection->updateOne(
            ['_id' => $this->get_table_name($db_id,$table_id)],
            ['$set' => $update_data]
        );
        
        return $result->getModifiedCount();
   }

   
   /**
    * 
    * Update import progress
    * 
    * @db_id - database id
    * @table_id - table id
    * @progress_data - progress data
    * 
    */
   function update_import_progress($db_id, $table_id, $progress_data)
   {
        $table_id = strtolower($table_id);
        $db_id = strtolower($db_id);

        $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
                
        $update_data = array();
        foreach ($progress_data as $key => $value) {
            if ($key === 'last_batch') {
                $update_data['import_progress.last_batch'] = $value;
            } else {
                $update_data['import_progress.' . $key] = $value;
            }
        }
        
        $result = $collection->updateOne(
            ['_id' => $this->get_table_name($db_id, $table_id)],
            ['$set' => $update_data]
        );
        
        return $result->getModifiedCount();
   }


   /**
    * 
    * Upsert table type
    * 
    * @db_id - database id
    * @table_id - table id
    * @csv_file_path - csv file path
    * @form_data - form data
    * 
    */
   function upsert_table_type($db_id, $table_id, $csv_file_path, $form_data = array())
   {
        $table_id = strtolower($table_id);
        $db_id = strtolower($db_id);
        
        $existing_table = $this->get_table_type($db_id, $table_id);
        $this->reset_import_errors_file($db_id, $table_id);

        $title = isset($form_data['title']) ? $form_data['title'] : null;
        $description = isset($form_data['description']) ? $form_data['description'] : null;
        
        if ($existing_table) {
            // Update existing table definition
            $update_data = array(
                'csv_file_path' => $csv_file_path,  // Always update file path
                'updated_at' => date('Y-m-d H:i:s'),
                'csv_uploaded_at' => date('Y-m-d H:i:s'),
                'import_progress' => $this->default_import_progress(array(
                    'errors_file' => $this->get_import_errors_relative_path($db_id, $table_id)
                ))
            );
            
            if ($title) {
                $update_data['title'] = $title;
            }
            
            if ($description) {
                $update_data['description'] = $description;
            }
            
            $result = $this->update_table_type($db_id, $table_id, $update_data);

            return array(
                'action' => 'updated',
                'result' => $result,
                'was_existing' => true
            );
        } else {
            // Create new table definition
            $table_metadata = array(
                'title' => $title ?: $db_id . ' - ' . $table_id,
                'description' => $description ?: 'N/A',
                'table_id' => $table_id,
                'csv_file_path' => $csv_file_path,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'csv_uploaded_at' => date('Y-m-d H:i:s'),
                'import_progress' => $this->default_import_progress(array(
                    'errors_file' => $this->get_import_errors_relative_path($db_id, $table_id)
                ))
            );
            
            $result = $this->create_table($db_id, $table_id, $table_metadata);
            return array(
                'action' => 'created',
                'result' => $result,
                'was_existing' => false
            );
        }
   }


   function validate_schema($type,$data)
	{
		$schema_file="application/schemas/$type-schema.json";

		if(!file_exists($schema_file)){
			throw new Exception("INVALID-DATASET-TYPE-NO-SCHEMA-DEFINED");
		}

		// Validate
		$validator = new JsonSchema\Validator;
		$validator->validate($data, 
				(object)['$ref' => 'file://' . unix_path(realpath($schema_file))],
				Constraint::CHECK_MODE_TYPE_CAST 
				+ Constraint::CHECK_MODE_COERCE_TYPES 
				+ Constraint::CHECK_MODE_APPLY_DEFAULTS
			);

		if ($validator->isValid()) {
			return true;
		} else {			
			/*foreach ($validator->getErrors() as $error) {
				echo sprintf("[%s] %s\n", $error['property'], $error['message']);
			}*/
			throw new ValidationException("SCHEMA_VALIDATION_FAILED [{$type}]: ", $validator->getErrors());
		}
	}


   

    /**
     * 
     * 
     * Get features array - id, name 
     * 
     */
    /**
     * 
     * 
     * Get features array - id, name 
     * 
     */
    function get_table_field_names($db_id,$table_id)
    {
        $collection = $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        $result = $collection->findOne();

        $output=array();

        if($result){

            foreach (array_keys((array)$result) as $key){
                $output[$key]=$key;
            }
        }

        return $output;
    }

    function get_correct_field_name($user_field_name, $available_fields)
    {
        if (empty($user_field_name) || empty($available_fields)) {
            return null;
        }

        if (array_key_exists($user_field_name, $available_fields)) {
            return $user_field_name;
        }

        foreach ($available_fields as $field_name => $value) {
            if (strcasecmp($user_field_name, $field_name) === 0) {
                return $field_name;
            }
        }

        return null;
    }

    function get_field_metadata_map($db_id, $table_id)
    {
        $projection = array('name' => 1, 'data_type' => 1);
        $fields_metadata = $this->get_table_fields($db_id, $table_id, true, $projection);
        $metadata_map = array();
        
        foreach ($fields_metadata as $field) {
            $field_name = isset($field['name']) ? $field['name'] : null;
            if ($field_name) {
                $metadata_map[$field_name] = array(
                    'name' => $field_name,
                    'data_type' => $this->normalize_field_data_type(
                        array_key_exists('data_type', $field) ? $field['data_type'] : null
                    )
                );
            }
        }
        
        return $metadata_map;
    }

    function get_field_info_from_metadata($user_field_name, $metadata_map)
    {
        if (empty($user_field_name) || empty($metadata_map)) {
            return null;
        }

        if (isset($metadata_map[$user_field_name])) {
            return $metadata_map[$user_field_name];
        }

        foreach ($metadata_map as $field_name => $field_info) {
            if (strcasecmp($user_field_name, $field_name) === 0) {
                return $field_info;
            }
        }

        return null;
    }

    /**
     * Build filter query options from request params; unknown fields raise an exception.
     *
     * @param array $options Request options / query parameters
     * @param array $field_metadata_map From get_field_metadata_map()
     * @return array Canonical field name => filter value
     * @throws Exception
     */
    function build_filter_options_from_request($options, $field_metadata_map)
    {
        $filter_options = array();
        $unknown_fields = array();
        $reserved_params = $this->get_reserved_table_query_params();

        if (isset($options['c']) && is_array($options['c'])) {
            foreach ($options['c'] as $key => $value) {
                $field_info = $this->get_field_info_from_metadata($key, $field_metadata_map);
                if ($field_info !== null) {
                    $filter_options[$field_info['name']] = $value;
                } else {
                    $unknown_fields[] = (string) $key;
                }
            }
        }

        foreach ($options as $key => $value) {
            if ($this->is_reserved_table_query_param($key)) {
                continue;
            }
            if (isset($options['c']) && is_array($options['c']) && isset($options['c'][$key])) {
                continue;
            }
            $field_info = $this->get_field_info_from_metadata($key, $field_metadata_map);
            if ($field_info !== null) {
                $filter_options[$field_info['name']] = $value;
            } else {
                $unknown_fields[] = (string) $key;
            }
        }

        if (!empty($unknown_fields)) {
            $unknown_fields = array_values(array_unique($unknown_fields));
            sort($unknown_fields);
            throw new Exception(
                'UNKNOWN_FILTER_FIELD: ' . implode(', ', $unknown_fields)
            );
        }

        return $filter_options;
    }

    function delete_table_data($db_id,$table_id)
    {
        try {
            $this->sync_table_index_definitions_from_collection($db_id, $table_id);
        } catch (Exception $e) {
            log_message('error', 'delete_table_data: could not snapshot indexes before drop: ' . $e->getMessage());
        }

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};
        $result = $collection->drop();
        return $result;
    }


    function delete_table_type($db_id,$table_id)
    {
        // Delete field metadata from table_dictionary
        $this->delete_table_fields($db_id, $table_id);
        
        // Delete table definition from table_types
        $collection = $this->mongo_client->{$this->get_db_name()}->{'table_types'};
        $result = $collection->deleteOne(['_id' => $this->get_table_name($db_id, $table_id)]);
        return $result->getDeletedCount();
    }



   /**
    * Fast CSV import using byte offset tracking - O(1) seek time
    * Processes maximum rows within the time limit
    *
    * Resume never drops an in-flight row: the time limit is checked before
    * fgetcsv, and byte_offset_end is the start of the next unread record.
    *
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param string $csv_path Path to CSV file
    * @param string $delimiter CSV delimiter
    * @param int $byte_offset Byte position to start from (0 for beginning)
    * @param int $max_time_seconds Maximum execution time (default: 30 seconds)
    * @param array $resume Running totals from prior chunks
    * @return array Import result with next byte offset and row accounting
    */
   function import_csv_chunked($db_id, $table_id, $csv_path, $delimiter = ',', $byte_offset = 0, $max_time_seconds = 30, array $resume = array())
   {
       $start_time = microtime(true);
       $chunk_size = 1000;
       $chunked_rows = array();
       $chunked_meta = array();

       $csv_records_read = isset($resume['csv_records_read']) ? (int) $resume['csv_records_read'] : 0;
       $rows_inserted = isset($resume['rows_inserted']) ? (int) $resume['rows_inserted'] : 0;
       $rows_blank = isset($resume['rows_blank']) ? (int) $resume['rows_blank'] : 0;
       $rows_failed = isset($resume['rows_failed']) ? (int) $resume['rows_failed'] : 0;
       $rows_warned = isset($resume['rows_warned']) ? (int) $resume['rows_warned'] : 0;
       $csv_row_num = isset($resume['last_csv_row']) ? (int) $resume['last_csv_row'] : 0;

       $batch_inserted = 0;
       $batch_blank = 0;
       $batch_failed = 0;
       $batch_warned = 0;
       $batch_read = 0;

       set_time_limit(0);

       $handle = fopen($csv_path, 'r');
       if (!$handle) {
           throw new Exception("Failed to open CSV file: {$csv_path}");
       }

       $delimiters = array(
           'comma' => ',',
           'tab' => "\t",
           'semicolon' => ';',
           ',' => ',',
           ';' => ';',
       );
       $delimiter_char = isset($delimiters[$delimiter]) ? $delimiters[$delimiter] : ',';

       $header = fgetcsv($handle, 0, $delimiter_char);
       if (!$header) {
           fclose($handle);
           throw new Exception("Failed to read CSV header");
       }

       $header = array_map(array($this, 'normalize_csv_column_name'), $header);
       $header_count = count($header);
       $header_byte_length = ftell($handle);

       if ($byte_offset == 0) {
           $this->prepare_import_dictionary_from_csv_header($db_id, $table_id, $header);
       }
       $import_type_map = $this->get_import_coercion_map_for_header($db_id, $table_id, $header);

       if ($byte_offset == 0) {
           $byte_offset = $header_byte_length;
       }

       fseek($handle, $byte_offset);

       $record_start = $byte_offset;
       $has_more = false;
       $file_size = filesize($csv_path);
       $handled_any = false;
       $timed_out = false;
       $next_byte_offset = $byte_offset;

       try {
       while (true) {
           $record_start = ftell($handle);
           if ($handled_any && (microtime(true) - $start_time) >= $max_time_seconds) {
               $timed_out = true;
               $has_more = true;
               break;
           }

           $row_data = fgetcsv($handle, 0, $delimiter_char);
           if ($row_data === false) {
               $has_more = false;
               break;
           }

           $handled_any = true;
           $csv_row_num++;
           $csv_records_read++;
           $batch_read++;

           if ($this->csv_row_is_blank($row_data)) {
               $rows_blank++;
               $batch_blank++;
               $this->append_import_error($db_id, $table_id, array(
                   'csv_row' => $csv_row_num,
                   'byte_offset' => $record_start,
                   'reason' => 'blank_row',
                   'detail' => 'CSV record is empty',
                   'preview' => $this->csv_row_preview($row_data)
               ));
               log_message('error', sprintf(
                   'CSV import skipped blank row db=%s table=%s csv_row=%d',
                   $db_id,
                   $table_id,
                   $csv_row_num
               ));
               continue;
           }

           $actual_count = count($row_data);
           if ($actual_count > $header_count) {
               $rows_warned++;
               $batch_warned++;
               $this->append_import_error($db_id, $table_id, array(
                   'csv_row' => $csv_row_num,
                   'byte_offset' => $record_start,
                   'reason' => 'extra_columns',
                   'expected_cols' => $header_count,
                   'actual_cols' => $actual_count,
                   'detail' => 'Row has more columns than the header; extra fields were truncated and the row was imported',
                   'preview' => $this->csv_row_preview($row_data)
               ));
               $row_data = array_slice($row_data, 0, $header_count);
           } elseif ($actual_count < $header_count) {
               $row_data = array_pad($row_data, $header_count, '');
           }

           $row = array_combine($header, $row_data);
           if ($row === false) {
               $rows_failed++;
               $batch_failed++;
               $this->append_import_error($db_id, $table_id, array(
                   'csv_row' => $csv_row_num,
                   'byte_offset' => $record_start,
                   'reason' => 'malformed_row',
                   'expected_cols' => $header_count,
                   'actual_cols' => count($row_data),
                   'detail' => 'Could not combine CSV columns with header',
                   'preview' => $this->csv_row_preview($row_data)
               ));
               log_message('error', sprintf(
                   'CSV import malformed row db=%s table=%s csv_row=%d',
                   $db_id,
                   $table_id,
                   $csv_row_num
               ));
               continue;
           }

           try {
               foreach ($row as $column => $cell) {
                   $data_type = isset($import_type_map[$column])
                       ? $import_type_map[$column]
                       : 'string';
                   $row[$column] = $this->coerce_import_value($cell, $data_type);
               }
           } catch (Exception $e) {
               $rows_failed++;
               $batch_failed++;
               $this->append_import_error($db_id, $table_id, array(
                   'csv_row' => $csv_row_num,
                   'byte_offset' => $record_start,
                   'reason' => 'invalid_coercion',
                   'detail' => $e->getMessage(),
                   'preview' => $this->csv_row_preview($row_data)
               ));
               log_message('error', sprintf(
                   'CSV import coercion failed db=%s table=%s csv_row=%d: %s',
                   $db_id,
                   $table_id,
                   $csv_row_num,
                   $e->getMessage()
               ));
               continue;
           }
           $chunked_rows[] = $row;
           $chunked_meta[] = array(
               'csv_row' => $csv_row_num,
               'byte_offset' => $record_start,
               'preview' => $this->csv_row_preview($row_data)
           );

           if (count($chunked_rows) >= $chunk_size) {
               $this->flush_import_chunk($db_id, $table_id, $chunked_rows, $chunked_meta, $rows_inserted, $rows_failed, $batch_inserted, $batch_failed);
               $this->persist_import_chunk_progress($db_id, $table_id, array(
                   'byte_offset_end' => ftell($handle),
                   'rows_inserted' => $rows_inserted,
                   'csv_records_read' => $csv_records_read,
                   'rows_blank' => $rows_blank,
                   'rows_failed' => $rows_failed,
                   'rows_warned' => $rows_warned,
                   'last_csv_row' => $csv_row_num,
                   'file_size' => $file_size
               ));
           }
       }

       $this->flush_import_chunk($db_id, $table_id, $chunked_rows, $chunked_meta, $rows_inserted, $rows_failed, $batch_inserted, $batch_failed);

       $next_byte_offset = $timed_out ? $record_start : ftell($handle);
       if (!$timed_out && ($next_byte_offset >= $file_size || feof($handle))) {
           $has_more = false;
       }
       } finally {
           fclose($handle);
       }

       $this->persist_import_chunk_progress($db_id, $table_id, array(
           'byte_offset_end' => $next_byte_offset,
           'rows_inserted' => $rows_inserted,
           'csv_records_read' => $csv_records_read,
           'rows_blank' => $rows_blank,
           'rows_failed' => $rows_failed,
           'rows_warned' => $rows_warned,
           'last_csv_row' => $csv_row_num,
           'file_size' => $file_size
       ));

       $execution_time = microtime(true) - $start_time;
       $denom = $file_size > 0 ? $file_size : 1;

       return array(
           'rows_processed' => $batch_inserted,
           'csv_records_read' => $batch_read,
           'rows_blank' => $batch_blank,
           'rows_failed' => $batch_failed,
           'rows_warned' => $batch_warned,
           'rows_inserted_total' => $rows_inserted,
           'csv_records_read_total' => $csv_records_read,
           'rows_blank_total' => $rows_blank,
           'rows_failed_total' => $rows_failed,
           'rows_warned_total' => $rows_warned,
           'last_csv_row' => $csv_row_num,
           'byte_offset_start' => $byte_offset,
           'byte_offset_end' => $next_byte_offset,
           'file_size' => $file_size,
           'progress_percent' => round(($next_byte_offset / $denom) * 100, 2),
           'has_more' => $has_more,
           'execution_time_seconds' => round($execution_time, 2),
           'execution_time_formatted' => $this->format_execution_time($execution_time)
       );
   }

/**
 * Helper: Format seconds as H:M:S
 */
function format_execution_time($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = floor($seconds % 60);
    return sprintf("%02dh:%02dm:%02ds", $hours, $minutes, $seconds);
}

	private function csv_row_is_blank($row_data)
	{
		if (!is_array($row_data) || $row_data === array()) {
			return true;
		}
		foreach ($row_data as $cell) {
			if ($cell === null || $cell === false) {
				continue;
			}
			if (trim((string) $cell) !== '') {
				return false;
			}
		}
		return true;
	}

	private function csv_row_preview($row_data, $max = 200)
	{
		if (!is_array($row_data)) {
			return '';
		}
		$line = implode(',', array_map(function ($cell) {
			return (string) $cell;
		}, $row_data));
		if (function_exists('mb_substr')) {
			return mb_substr($line, 0, $max);
		}
		return substr($line, 0, $max);
	}

	private function flush_import_chunk($db_id, $table_id, array &$chunked_rows, array &$chunked_meta, &$rows_inserted, &$rows_failed, &$batch_inserted, &$batch_failed)
	{
		if (empty($chunked_rows)) {
			return;
		}

		$result = $this->table_batch_insert_with_errors($db_id, $table_id, $chunked_rows);
		$failed_by_index = array();
		foreach ($result['failed_indexes'] as $err) {
			$failed_by_index[(int) $err['index']] = $err;
		}

		foreach ($chunked_meta as $i => $meta) {
			if (isset($failed_by_index[$i])) {
				$err = $failed_by_index[$i];
				$this->append_import_error($db_id, $table_id, array(
					'csv_row' => $meta['csv_row'],
					'byte_offset' => $meta['byte_offset'],
					'reason' => 'insert_failed',
					'code' => $err['code'],
					'detail' => $err['message'],
					'preview' => $meta['preview']
				));
				log_message('error', sprintf(
					'CSV import insert failed db=%s table=%s csv_row=%d: %s',
					$db_id,
					$table_id,
					$meta['csv_row'],
					$err['message']
				));
				$rows_failed++;
				$batch_failed++;
			} else {
				$rows_inserted++;
				$batch_inserted++;
			}
		}

		$chunked_rows = array();
		$chunked_meta = array();
	}

	private function persist_import_chunk_progress($db_id, $table_id, array $stats)
	{
		$file_size = isset($stats['file_size']) ? (int) $stats['file_size'] : 0;
		$offset = isset($stats['byte_offset_end']) ? (int) $stats['byte_offset_end'] : 0;
		$denom = $file_size > 0 ? $file_size : 1;

		$this->update_import_progress($db_id, $table_id, array(
			'byte_offset_end' => $offset,
			'total_rows_processed' => (int) $stats['rows_inserted'],
			'csv_records_read' => (int) $stats['csv_records_read'],
			'rows_blank' => (int) $stats['rows_blank'],
			'rows_failed' => (int) $stats['rows_failed'],
			'rows_warned' => (int) $stats['rows_warned'],
			'last_csv_row' => (int) $stats['last_csv_row'],
			'file_size' => $file_size,
			'progress_percent' => round(($offset / $denom) * 100, 2),
			'import_status' => 'in_progress',
			'errors_file' => $this->get_import_errors_relative_path($db_id, $table_id)
		));
	}

	public function get_import_errors_relative_path($db_id, $table_id)
	{
		return strtolower($db_id) . '/' . strtolower($table_id) . '/import_errors.ndjson';
	}

	public function get_import_errors_full_path($db_id, $table_id)
	{
		return 'datafiles/' . $this->get_import_errors_relative_path($db_id, $table_id);
	}

	public function reset_import_errors_file($db_id, $table_id)
	{
		$path = $this->get_import_errors_full_path($db_id, $table_id);
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($path, '');
	}

	public function append_import_error($db_id, $table_id, array $error)
	{
		$path = $this->get_import_errors_full_path($db_id, $table_id);
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		$error['logged_at'] = date('c');
		$line = json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
		$fp = fopen($path, 'ab');
		if ($fp === false) {
			log_message('error', 'Failed to open import errors file: ' . $path);
			return;
		}
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $line);
			fflush($fp);
			flock($fp, LOCK_UN);
		}
		fclose($fp);
	}

	public function download_import_errors_file($db_id, $table_id)
	{
		$path = $this->get_import_errors_full_path($db_id, $table_id);
		if (!file_exists($path) || filesize($path) === 0) {
			throw new Exception("No import errors file found");
		}
		$this->download_file($path, 'ndjson', $db_id . '_' . $table_id . '_import_errors.ndjson');
	}

	private function default_import_progress($overrides = array())
	{
		return array_merge(array(
			'byte_offset_end' => 0,
			'total_rows_processed' => 0,
			'csv_records_read' => 0,
			'rows_blank' => 0,
			'rows_failed' => 0,
			'rows_warned' => 0,
			'last_csv_row' => 0,
			'import_status' => 'ready',
			'import_started_at' => null,
			'import_completed_at' => null,
			'last_import_at' => null,
			'progress_percent' => 0,
			'errors_file' => null,
			'balanced' => true
		), $overrides);
	}

	private function is_import_terminal($status)
	{
		return in_array($status, array('completed', 'completed_with_errors', 'failed'), true);
	}

	private function import_errors_download_url($db_id, $table_id)
	{
		return site_url('api/tables/import_errors/' . $db_id . '/' . $table_id);
	}

	private function import_progress_payload($import_progress, $table_definition, $has_more_override = null)
	{
		$import_progress = $this->mongo_document_to_array($import_progress);
		$status = isset($import_progress['import_status']) ? $import_progress['import_status'] : 'ready';
		$has_more = ($has_more_override !== null) ? (bool) $has_more_override : !$this->is_import_terminal($status);
		$inserted = isset($import_progress['total_rows_processed']) ? (int) $import_progress['total_rows_processed'] : 0;
		$read = isset($import_progress['csv_records_read']) ? (int) $import_progress['csv_records_read'] : 0;
		$blank = isset($import_progress['rows_blank']) ? (int) $import_progress['rows_blank'] : 0;
		$failed = isset($import_progress['rows_failed']) ? (int) $import_progress['rows_failed'] : 0;
		$warned = isset($import_progress['rows_warned']) ? (int) $import_progress['rows_warned'] : 0;
		if (isset($import_progress['balanced'])) {
			$balanced = (bool) $import_progress['balanced'];
		} else {
			$balanced = ($read === ($inserted + $blank + $failed));
		}
		$db_id = isset($table_definition['db_id']) ? $table_definition['db_id'] : '';
		$table_id = isset($table_definition['table_id']) ? $table_definition['table_id'] : '';

		return array(
			'total_rows_processed' => $inserted,
			'csv_records_read' => $read,
			'rows_inserted' => $inserted,
			'rows_blank' => $blank,
			'rows_failed' => $failed,
			'rows_warned' => $warned,
			'last_csv_row' => isset($import_progress['last_csv_row']) ? (int) $import_progress['last_csv_row'] : 0,
			'balanced' => $balanced,
			'byte_offset_end' => isset($import_progress['byte_offset_end']) ? $import_progress['byte_offset_end'] : 0,
			'progress_percent' => isset($import_progress['progress_percent']) ? $import_progress['progress_percent'] : 0,
			'import_status' => $status,
			'has_more' => $has_more,
			'errors_file' => isset($import_progress['errors_file']) ? $import_progress['errors_file'] : $this->get_import_errors_relative_path($db_id, $table_id),
			'errors_count' => $failed + $blank + $warned,
			'errors_download_url' => $this->import_errors_download_url($db_id, $table_id)
		);
	}



   function get_table_aggregate($db_id,$table_id,$limit=100,$offset=0,$options)
   {    
        $limit=intval($limit);
        $offset=intval($offset);
        
        if ($limit<=0 || $limit>10000){
            $limit=100;
        }        

        $table_id=strtolower($table_id);

        if (!isset($options['fields'])){
            throw new Exception("fields parameter is required");
        }

        $group_fields=explode(",",$options['fields']);

        //get table type info
        $this->table_type_obj= $this->get_table_type($db_id,$table_id);

        //get table fields list
        $fields=$this->get_table_field_names($db_id,$table_id);

        if (!empty($fields) && isset($fields['_id'])){
            unset($fields['_id']);
        }

        $group_fields=array_intersect($group_fields,$fields);

        if (empty($group_fields)){
            throw new Exception("No valid field values provided. Valid values are: ". implode(", ",$fields));
        }

        $collection=$this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id,$table_id)};        

        /*
        $cond = array(
            //array('$match' => array('page_id' =>123456)),
            array(
                '$group' => array(
                    '_id' => array("ctry_code" => '$ctry_code', 'country'=>'$ctry_name'),
                'total' => array('$sum' => '$ctry_code'),
                //'count' => "total_count"
                'count' => array('$sum' => 1),
                )
            ),
        );
        */

        $groups_arr=array();
        foreach($group_fields as $f){
            $groups_arr[$f]='$'.$f;
        }

        $cond=array(
            //array('$match'=>null),
            array('$group'=>array('_id'=>$groups_arr, 'count'=>array('$sum'=>1))),
            array('$sort'=>array('_id'=>1)),
            array('$skip'=>$offset),
            array('$limit'=>$limit)
        );

        $filters=$this->get_filters($db_id,$table_id,$options);

        if ($filters){
            //must be added at the beginning of the conditions to work
            array_unshift($cond,array('$match'=>$filters));
        }

        $cursor = $collection->aggregate($cond);

        $output['rows']=0;
        $output['limit']=$limit;
        $output['offset']=$offset;        
        $output['data']=array();

        if (isset($options['debug'])){
            $output['debug']=$cond;
        }

        $k=0;
        foreach ($cursor as $document) {
            $k++;
            $row=iterator_to_array($document);
            $result=array();
            if(isset($row['_id'])){
                $result=$row['_id'];
            }
            $result['count']=$row['count'];
            $output['data'][]= $result;
        }

        $output['rows']=$k;
        return $output;
   } 


   function get_filters($db_id, $table_id,$options)
   {
        $field_metadata_map = $this->get_field_metadata_map($db_id, $table_id);

        $feature_filters=array();
        $filter_options = $this->build_filter_options_from_request($options, $field_metadata_map);

        $tmp_feature_filters=array();

        foreach($filter_options as $feature_key=>$value){
            $tmp_feature_filters[$feature_key]=$this->apply_feature_filter($feature_key,$value,$field_metadata_map);
        }

        if(isset($options['ft_query']) && !empty($options['ft_query'])){
            $tmp_feature_filters['ft_query'][]['$text']=$this->text_search($options['ft_query']);
        }
        
        $feature_filters=array();

        if (!empty($tmp_feature_filters)){

            $feature_filters=array(
                '$and'=> array()
            );

            foreach($tmp_feature_filters as $feature_key=>$filter){
                $feature_filters['$and'][]['$or']=$filter;
            }
        }

        return $feature_filters;
   }
	
   /**
    * Get field metadata for a table (from table_dictionary collection)
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param string|null $field_name Field name (optional - if provided, returns single field)
    * @return array|null Field metadata or array of fields
    */
   function get_field_metadata($db_id, $table_id, $field_name = null, $projection = null)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
       
       $filter = [
           'db_id' => strtolower($db_id),
           'table_id' => strtolower($table_id)
       ];
       
       if ($field_name) {
           // Get single field
           $field_id = $this->get_field_dictionary_id($db_id, $table_id, $field_name);
           $options = array();
           if ($projection) {
               $options['projection'] = $projection;
           }
           $result = $collection->findOne(['_id' => $field_id], $options);
           return $result ? (array)$result : null;
       }
       
       // Get all fields, sorted by field_order
       $options = array('sort' => ['field_order' => 1]);
       if ($projection) {
           $options['projection'] = $projection;
       }
       $cursor = $collection->find($filter, $options);
       
       $fields = [];
       foreach ($cursor as $doc) {
           $fields[] = (array)$doc;
       }
       
       return $fields;
   }

   /**
    * Create field metadata in table_dictionary collection
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param array $field_metadata Field metadata array
    * @return int Number of documents inserted
    */
   function create_field_metadata($db_id, $table_id, $field_metadata)
   {
       // Validate required parameters
       if (empty($db_id)) {
           throw new Exception("Database ID is required");
       }
       
       if (empty($table_id)) {
           throw new Exception("Table ID is required");
       }
       
       // Validate required fields
       if (!isset($field_metadata['name']) || empty($field_metadata['name'])) {
           throw new Exception("Field name is required");
       }

       $field_metadata['name'] = trim((string) $field_metadata['name']);
       $this->assert_dictionary_field_name_available($db_id, $table_id, $field_metadata['name']);
       $field_metadata['data_type'] = $this->normalize_field_data_type(
           array_key_exists('data_type', $field_metadata) ? $field_metadata['data_type'] : null
       );
       
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
       
       // Set required fields
       $field_metadata['db_id'] = strtolower($db_id);
       $field_metadata['table_id'] = strtolower($table_id);
       $field_metadata['_id'] = $this->get_field_dictionary_id($db_id, $table_id, $field_metadata['name']);
       
       // Set timestamps
       $now = date('Y-m-d H:i:s');
       $field_metadata['created_at'] = $now;
       $field_metadata['updated_at'] = $now;
       
       // Set default field_order if not provided
       if (!isset($field_metadata['field_order'])) {
           $max_order = $this->get_max_field_order($db_id, $table_id);
           $field_metadata['field_order'] = $max_order + 1;
       }
       
       // Set defaults for optional fields
       $field_metadata['time_period_format'] = $field_metadata['time_period_format'] ?? null;
       $field_metadata['unit_of_measurement'] = $field_metadata['unit_of_measurement'] ?? null;
       $field_metadata['code_list'] = $field_metadata['code_list'] ?? [];
       $field_metadata['code_list_reference'] = $field_metadata['code_list_reference'] ?? null;
       unset($field_metadata['format']);
       
       // Use updateOne with upsert to handle duplicates gracefully
       $result = $collection->updateOne(
           ['_id' => $field_metadata['_id']],
           [
               '$set' => $field_metadata,
               '$unset' => array('format' => ''),
           ],
           ['upsert' => true]
       );
       
       return $result->getUpsertedCount() > 0 ? 1 : $result->getModifiedCount();
   }

   /**
    * Update field metadata in table_dictionary collection
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param string $field_name Field name
    * @param array $update_data Fields to update
    * @return int Number of documents modified
    */
   function update_field_metadata($db_id, $table_id, $field_name, $update_data)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
       
       $field_id = $this->get_field_dictionary_id($db_id, $table_id, $field_name);
       
       // Don't allow updating name (would require changing _id)
       if (isset($update_data['name'])) {
           unset($update_data['name']);
       }

       if (array_key_exists('data_type', $update_data)) {
           $update_data['data_type'] = $this->normalize_field_data_type($update_data['data_type']);
       }
       unset($update_data['format']);
       
       // Always update updated_at
       $update_data['updated_at'] = date('Y-m-d H:i:s');
       
       $result = $collection->updateOne(
           ['_id' => $field_id],
           [
               '$set' => $update_data,
               '$unset' => array('format' => ''),
           ]
       );
       
       return $result->getModifiedCount();
   }

   /**
    * Delete field metadata from table_dictionary collection
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param string $field_name Field name
    * @return int Number of documents deleted
    */
   function delete_field_metadata($db_id, $table_id, $field_name)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
       
       $field_id = $this->get_field_dictionary_id($db_id, $table_id, $field_name);
       $result = $collection->deleteOne(['_id' => $field_id]);
       
       return $result->getDeletedCount();
   }

   /**
    * Get all fields for a table (alias for get_field_metadata without field_name)
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param bool $sort_by_order Sort by field_order (default: true)
    * @return array Array of field metadata
    */
   function get_table_fields($db_id, $table_id, $sort_by_order = true, $projection = null)
   {
       return $this->get_field_metadata($db_id, $table_id, null, $projection);
   }

   /**
    * Delete all fields for a table (cascade delete)
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @return int Number of documents deleted
    */
   function delete_table_fields($db_id, $table_id)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
       
       $result = $collection->deleteMany([
           'db_id' => strtolower($db_id),
           'table_id' => strtolower($table_id)
       ]);
       
       return $result->getDeletedCount();
   }
	
   /**
    * Reorder fields for a table
    * 
    * @param string $db_id Database ID
    * @param string $table_id Table ID
    * @param array $field_orders Associative array: ['field_name' => order_number]
    * @return int Number of documents updated
    */
   function reorder_fields($db_id, $table_id, $field_orders)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
       $updated = 0;
       $now = date('Y-m-d H:i:s');
       
       foreach ($field_orders as $field_name => $order) {
           $field_id = $this->get_field_dictionary_id($db_id, $table_id, $field_name);
       $result = $collection->updateOne(
               ['_id' => $field_id],
               [
                   '$set' => [
                       'field_order' => (int)$order,
                       'updated_at' => $now
                   ]
               ]
       );
           $updated += $result->getModifiedCount();
       }
       
       return $updated;
   }
	
   /**
    * 
    * Get field names from actual data collection
    * 
    */
   function get_data_field_names($db_id, $table_id)
   {
       $collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
       
       // Get a sample document to extract field names
       $sample = $collection->findOne();
       
       if (!$sample) {
           return array();
       }
       
       $field_names = array();
       foreach (array_keys((array)$sample) as $field_name) {
           if ($field_name !== '_id') { // Exclude MongoDB _id field
               $field_names[] = $field_name;
           }
       }
       
       return $field_names;
   }

	/**
	 * Populate table schema from actual data collection
	 * Creates field metadata in table_dictionary for each field found in data
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @return int Number of fields created
	 */
	function populate_table_schema($db_id, $table_id)
	{
		$field_names = $this->get_data_field_names($db_id, $table_id);
		
		if (empty($field_names)) {
			throw new Exception("No data found in the collection to extract schema from");
		}
		
		$collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
		$fields_created = 0;
		
		foreach ($field_names as $index => $field_name) {
			// Generate field ID
			$field_id = $this->get_field_dictionary_id($db_id, $table_id, $field_name);
			
			// Check if field already exists
			$existing = $collection->findOne(['_id' => $field_id]);
			
			if (!$existing) {
				// Create default field metadata
				$field_metadata = $this->get_default_field_metadata($field_name, $db_id, $table_id);
				$field_metadata['_id'] = $field_id;
				$field_metadata['field_order'] = $index + 1;
				
				// Use upsert to prevent race condition (only insert if doesn't exist)
				// $setOnInsert ensures we only set these fields on insert, not on update
			$result = $collection->updateOne(
					['_id' => $field_id],
					['$setOnInsert' => $field_metadata],
					['upsert' => true]
			);
			
				// Only count as created if it was actually inserted (not updated)
				if ($result->getUpsertedCount() > 0) {
					$fields_created++;
				}
			} else {
				// Field exists - check if it's missing db_id or table_id and fix it
				$existing_array = (array)$existing;
				$needs_update = false;
				$update_data = [];
				
				if (!isset($existing_array['db_id']) || $existing_array['db_id'] !== strtolower($db_id)) {
					$update_data['db_id'] = strtolower($db_id);
					$needs_update = true;
				}
				
				if (!isset($existing_array['table_id']) || $existing_array['table_id'] !== strtolower($table_id)) {
					$update_data['table_id'] = strtolower($table_id);
					$needs_update = true;
				}
				
				// Update field_order if it's missing or incorrect
				if (!isset($existing_array['field_order']) || $existing_array['field_order'] != ($index + 1)) {
					$update_data['field_order'] = $index + 1;
					$needs_update = true;
				}
				
				if ($needs_update) {
					$update_data['updated_at'] = date('Y-m-d H:i:s');
					$collection->updateOne(
						['_id' => $field_id],
						['$set' => $update_data]
					);
					$fields_created++; // Count as "fixed"
				}
			}
		}
		
		return $fields_created;
	}

	/**
	 * Sync field metadata with actual data fields
	 * Deletes fields that don't exist in data and adds new fields from data
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @return array Array with fields_removed and fields_added counts
	 */
	function sync_table_fields($db_id, $table_id)
	{
		// Get actual fields from data
		$actual_fields = $this->get_data_field_names($db_id, $table_id);
		
		if (empty($actual_fields)) {
			throw new Exception("No data found in the collection to extract fields from");
		}
		
		// Get all field definitions from dictionary
		$dict_fields = $this->get_table_fields($db_id, $table_id);
		$dict_field_names = array();
		foreach ($dict_fields as $field) {
			$dict_field_names[$field['name']] = $field;
		}
		
		$fields_removed = 0;
		$fields_added = 0;
		
		// Delete fields that don't exist in actual data
		foreach ($dict_field_names as $field_name => $field_data) {
			if (!in_array($field_name, $actual_fields)) {
				$this->delete_field_metadata($db_id, $table_id, $field_name);
				$fields_removed++;
			}
		}
		
		// Populate schema for new fields (only creates missing ones)
		$fields_added = $this->populate_table_schema($db_id, $table_id);
		
		return array(
			'fields_removed' => $fields_removed,
			'fields_added' => $fields_added,
			'total_fields' => count($actual_fields)
		);
	}

	/**
	 * Get the number of rows in a table (optimized)
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @param bool $exact_count Whether to get exact count (slower) or estimated count (faster)
	 * @return int Number of rows in the table
	 */
	function get_table_row_count($db_id, $table_id, $exact_count = false)
	{
		try {
			$collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
			
			if ($exact_count) {
				// Use countDocuments() for exact count (slower but accurate)
				return $collection->countDocuments();
			} else {
				// Use estimatedDocumentCount() for fast approximate count
				return $collection->estimatedDocumentCount();
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to get table row count: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Get fast row count using _id index filter (optimized for large collections)
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @return int Number of rows in the table
	 */
	function get_table_row_count_fast($db_id, $table_id)
	{
		try {
			$collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
			// Use _id index filter for faster counting
			return $collection->countDocuments(['_id' => ['$ne' => null]]);
		} catch (Exception $e) {
			log_message('error', 'Failed to get fast table row count: ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Ensure dictionary exists / matches CSV header before import (byte_offset 0).
	 *
	 * @param string $db_id
	 * @param string $table_id
	 * @param array $header
	 * @throws Exception
	 */
	public function prepare_import_dictionary_from_csv_header($db_id, $table_id, array $header)
	{
		$this->validate_field_name_list($header);

		$existing_fields = $this->get_field_metadata($db_id, $table_id);
		$metadata_map = $this->get_field_metadata_map($db_id, $table_id);

		if (empty($existing_fields)) {
			foreach ($header as $index => $column_name) {
				$field_metadata = $this->get_default_field_metadata($column_name, $db_id, $table_id);
				$field_metadata['field_order'] = $index + 1;
				$this->create_field_metadata($db_id, $table_id, $field_metadata);
			}
			return;
		}

		$max_order = $this->get_max_field_order($db_id, $table_id);
		foreach ($header as $column_name) {
			if ($this->get_field_info_from_metadata($column_name, $metadata_map) !== null) {
				continue;
			}
			$max_order++;
			$field_metadata = $this->get_default_field_metadata($column_name, $db_id, $table_id);
			$field_metadata['field_order'] = $max_order;
			$this->create_field_metadata($db_id, $table_id, $field_metadata);
			$metadata_map[$column_name] = array(
				'name' => $column_name,
				'data_type' => 'string',
			);
		}
	}

	/**
	 * Map CSV header columns to dictionary data types for coercion.
	 *
	 * @param string $db_id
	 * @param string $table_id
	 * @param array $header
	 * @return array column name => normalized data_type
	 * @throws Exception
	 */
	public function get_import_coercion_map_for_header($db_id, $table_id, array $header)
	{
		$metadata_map = $this->get_field_metadata_map($db_id, $table_id);
		$map = array();
		foreach ($header as $column_name) {
			$field_info = $this->get_field_info_from_metadata($column_name, $metadata_map);
			if ($field_info === null) {
				throw new Exception(
					'Dictionary missing field for CSV column "' . $column_name . '"'
				);
			}
			$map[$column_name] = $field_info['data_type'];
		}
		return $map;
	}

	/**
	 * Normalize a raw CSV cell to UTF-8 trimmed string (empty string if blank).
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function normalize_csv_cell_raw($value)
	{
		if ($value === '' || $value === null) {
			return '';
		}
		if (!is_string($value)) {
			return trim((string) $value);
		}
		$encoded = mb_convert_encoding($value, 'UTF-8', 'auto');
		if ($encoded === false) {
			return trim($value);
		}
		return trim($encoded);
	}

	/**
	 * Coerce a CSV cell using dictionary data_type.
	 *
	 * @param mixed $value
	 * @param string $data_type
	 * @return mixed
	 * @throws Exception
	 */
	public function coerce_import_value($value, $data_type)
	{
		$data_type = $this->normalize_field_data_type($data_type);
		$raw = $this->normalize_csv_cell_raw($value);
		if ($raw === '') {
			return '';
		}

		switch ($data_type) {
			case 'string':
				return $raw;
			case 'integer':
				if (!preg_match('/^-?\d+$/', $raw)) {
					throw new Exception(
						'Value "' . $raw . '" is not a valid integer'
					);
				}
				return (int) $raw;
			case 'float':
				if (!is_numeric($raw)) {
					throw new Exception(
						'Value "' . $raw . '" is not a valid number'
					);
				}
				return (float) $raw;
			case 'boolean':
				$lower = strtolower($raw);
				if (in_array($lower, array('1', 'true', 'yes', 'y'), true)) {
					return true;
				}
				if (in_array($lower, array('0', 'false', 'no', 'n'), true)) {
					return false;
				}
				throw new Exception(
					'Value "' . $raw . '" is not a valid boolean'
				);
			case 'date':
			case 'datetime':
			case 'array':
			case 'object':
				return $raw;
			default:
				return $raw;
		}
	}

	/**
	 * Run validation checks for table definition, dictionary, stored CSV, data, and indexes.
	 *
	 * @param string $db_id
	 * @param string $table_id
	 * @return array
	 */
	public function validate_table($db_id, $table_id)
	{
		$db_id = strtolower($db_id);
		$table_id = strtolower($table_id);

		$issues = array();
		$context = array(
			'db_id' => $db_id,
			'table_id' => $table_id,
			'row_count' => 0,
			'csv_file_path' => null,
			'csv_file_exists' => false,
			'dictionary_field_count' => 0,
			'import_status' => null,
		);

		$table_type = $this->get_table_type($db_id, $table_id);
		if (!$table_type) {
			$this->append_table_validation_issue(
				$issues,
				'definition',
				'error',
				'table_not_found',
				'Table definition was not found'
			);
			return $this->build_table_validation_report($issues, $context);
		}

		if (empty($table_type['table_id']) && empty($table_type['_id'])) {
			$this->append_table_validation_issue(
				$issues,
				'definition',
				'warning',
				'table_id_missing',
				'Table definition is missing a table identifier'
			);
		}

		$import_progress = isset($table_type['import_progress']) && is_array($table_type['import_progress'])
			? $table_type['import_progress']
			: array();
		$context['import_status'] = isset($import_progress['import_status']) ? $import_progress['import_status'] : null;

		if ($context['import_status'] === 'in_progress') {
			$this->append_table_validation_issue(
				$issues,
				'import',
				'warning',
				'import_in_progress',
				'CSV import is still in progress'
			);
		} elseif ($context['import_status'] === 'failed') {
			$this->append_table_validation_issue(
				$issues,
				'import',
				'error',
				'import_failed',
				'Last import finished with an accounting mismatch (see import progress)'
			);
		} elseif ($context['import_status'] === 'completed_with_errors') {
			$this->append_table_validation_issue(
				$issues,
				'import',
				'warning',
				'import_completed_with_errors',
				'Last import completed with skipped or failed rows'
			);
		}

		if (isset($import_progress['balanced']) && $import_progress['balanced'] === false) {
			$this->append_table_validation_issue(
				$issues,
				'import',
				'error',
				'import_not_balanced',
				'Import row counts do not balance with MongoDB document count'
			);
		}

		$dict_fields = $this->get_field_metadata($db_id, $table_id);
		$dict_fields = is_array($dict_fields) ? $dict_fields : array();
		$context['dictionary_field_count'] = count($dict_fields);

		if (empty($dict_fields)) {
			$this->append_table_validation_issue(
				$issues,
				'dictionary',
				'warning',
				'dictionary_empty',
				'Data dictionary has no fields (import will create fields from the CSV header as strings)'
			);
		} else {
			$dict_names = array();
			foreach ($dict_fields as $field) {
				$field = (array) $field;
				$name = isset($field['name']) ? trim((string) $field['name']) : '';
				if ($name === '') {
					$this->append_table_validation_issue(
						$issues,
						'dictionary',
						'error',
						'dictionary_name_missing',
						'A dictionary entry is missing a field name'
					);
					continue;
				}
				$dict_names[] = $name;
				try {
					$this->validate_field_name($name);
				} catch (Exception $e) {
					$this->append_table_validation_issue(
						$issues,
						'dictionary',
						'error',
						'invalid_field_name',
						$e->getMessage(),
						array('field' => $name)
					);
				}
				if ($this->field_name_conflicts_with_reserved_query_param($name)) {
					$this->append_table_validation_issue(
						$issues,
						'dictionary',
						'error',
						'reserved_query_param_name',
						'Field "' . $name . '" is reserved and cannot be used as a column name',
						array('field' => $name)
					);
				}
				if (array_key_exists('format', $field) && $field['format'] !== null && $field['format'] !== '') {
					$this->append_table_validation_issue(
						$issues,
						'dictionary',
						'warning',
						'deprecated_format_field',
						'Dictionary field "' . $name . '" still has deprecated "format" metadata; re-save the field to remove it',
						array('field' => $name)
					);
				}
			}

			$duplicate_groups = $this->find_duplicate_field_names_case_insensitive($dict_names);
			foreach ($duplicate_groups as $group) {
				$this->append_table_validation_issue(
					$issues,
					'dictionary',
					'error',
					'duplicate_field_names',
					'Duplicate dictionary field names (case-insensitive): ' . implode(' / ', $group),
					array('fields' => $group)
				);
			}
		}

		$csv_header = array();
		if (empty($table_type['csv_file_path'])) {
			$this->append_table_validation_issue(
				$issues,
				'csv',
				'error',
				'csv_path_missing',
				'No stored CSV path on the table definition; upload a file first'
			);
		} else {
			$context['csv_file_path'] = $table_type['csv_file_path'];
			try {
				$validated_file_path = validate_file_path($table_type['csv_file_path'], $db_id, $table_id);
				$full_file_path = 'datafiles/' . $validated_file_path;
				$context['csv_file_path'] = $validated_file_path;
				if (!file_exists($full_file_path)) {
					$this->append_table_validation_issue(
						$issues,
						'csv',
						'error',
						'csv_file_missing',
						'Stored CSV file was not found on disk: ' . $validated_file_path
					);
				} elseif (!is_readable($full_file_path)) {
					$this->append_table_validation_issue(
						$issues,
						'csv',
						'error',
						'csv_file_not_readable',
						'Stored CSV file is not readable: ' . $validated_file_path
					);
				} else {
					$context['csv_file_exists'] = true;
					try {
						$csv_header = $this->read_csv_header_columns($full_file_path, 'comma');
					} catch (Exception $e) {
						$this->append_table_validation_issue(
							$issues,
							'csv',
							'error',
							'csv_header_invalid',
							$e->getMessage()
						);
					}
				}
			} catch (Exception $e) {
				$this->append_table_validation_issue(
					$issues,
					'csv',
					'error',
					'csv_path_invalid',
					$e->getMessage()
				);
			}
		}

		if (!empty($csv_header)) {
			try {
				$this->validate_field_name_list($csv_header);
			} catch (Exception $e) {
				$this->append_table_validation_issue(
					$issues,
					'csv',
					'error',
					'csv_header_field_names',
					$e->getMessage()
				);
			}

			$dict_name_map = array();
			foreach ($dict_fields as $field) {
				$field = (array) $field;
				if (!empty($field['name'])) {
					$dict_name_map[strtolower($field['name'])] = $field['name'];
				}
			}
			foreach ($csv_header as $column) {
				$fold = strtolower($column);
				if (!isset($dict_name_map[$fold])) {
					$this->append_table_validation_issue(
						$issues,
						'csv',
						'warning',
						'csv_column_not_in_dictionary',
						'CSV column "' . $column . '" is not in the dictionary (it will be added on import as type string)',
						array('field' => $column)
					);
				}
			}
			$csv_fold_set = array();
			foreach ($csv_header as $column) {
				$csv_fold_set[strtolower($column)] = true;
			}
			foreach ($dict_fields as $field) {
				$field = (array) $field;
				$name = isset($field['name']) ? $field['name'] : '';
				if ($name === '') {
					continue;
				}
				if (!isset($csv_fold_set[strtolower($name)])) {
					$this->append_table_validation_issue(
						$issues,
						'dictionary',
						'warning',
						'dictionary_field_not_in_csv',
						'Dictionary field "' . $name . '" is not present in the stored CSV header',
						array('field' => $name)
					);
				}
			}
		}

		try {
			$context['row_count'] = (int) $this->get_table_row_count_fast($db_id, $table_id);
		} catch (Exception $e) {
			$context['row_count'] = 0;
		}

		if ($context['row_count'] > 0) {
			try {
				$data_fields = $this->get_data_field_names($db_id, $table_id);
			} catch (Exception $e) {
				$data_fields = array();
			}
			$data_fold = array();
			foreach ($data_fields as $name) {
				$data_fold[strtolower($name)] = $name;
			}
			foreach ($dict_fields as $field) {
				$field = (array) $field;
				$name = isset($field['name']) ? $field['name'] : '';
				if ($name === '') {
					continue;
				}
				if (!isset($data_fold[strtolower($name)])) {
					$this->append_table_validation_issue(
						$issues,
						'data',
						'warning',
						'dictionary_field_not_in_data',
						'Dictionary field "' . $name . '" has no matching column in imported data',
						array('field' => $name)
					);
				}
			}
			foreach ($data_fields as $name) {
				$info = $this->get_field_info_from_metadata($name, $this->get_field_metadata_map($db_id, $table_id));
				if ($info === null) {
					$this->append_table_validation_issue(
						$issues,
						'data',
						'warning',
						'data_field_not_in_dictionary',
						'Data column "' . $name . '" is not defined in the dictionary',
						array('field' => $name)
					);
				}
			}

			$duplicate_data_groups = $this->find_duplicate_field_names_case_insensitive($data_fields);
			foreach ($duplicate_data_groups as $group) {
				$this->append_table_validation_issue(
					$issues,
					'data',
					'error',
					'data_duplicate_columns',
					'Data contains multiple column names that differ only by case: ' . implode(' / ', $group),
					array('fields' => $group)
				);
			}

			$metadata_map = $this->get_field_metadata_map($db_id, $table_id);
			$collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
			$sample = $collection->findOne(array(), array('limit' => 1));
			if ($sample) {
				$sample = (array) $sample;
				foreach ($dict_fields as $field) {
					$field = (array) $field;
					$name = isset($field['name']) ? $field['name'] : '';
					if ($name === '') {
						continue;
					}
					$fold = strtolower($name);
					if (!isset($data_fold[$fold])) {
						continue;
					}
					$actual_key = $data_fold[$fold];
					if (!array_key_exists($actual_key, $sample)) {
						continue;
					}
					$data_type = $this->normalize_field_data_type(
						array_key_exists('data_type', $field) ? $field['data_type'] : null
					);
					if (!$this->sample_value_matches_data_type($sample[$actual_key], $data_type)) {
						$this->append_table_validation_issue(
							$issues,
							'data',
							'warning',
							'data_type_mismatch',
							'Sample value for "' . $name . '" does not match dictionary data_type "' . $data_type . '" (re-import or convert types)',
							array('field' => $name, 'data_type' => $data_type)
						);
					}
				}
			}
		}

		$saved_indexes = $this->get_table_index_definitions($db_id, $table_id);
		$live_indexes = array();
		try {
			$live_indexes = $this->get_collection_indexes($db_id, $table_id);
		} catch (Exception $e) {
			$live_indexes = array();
		}

		if (!empty($saved_indexes)) {
			foreach ($saved_indexes as $entry) {
				$entry = (array) $entry;
				$index_name = isset($entry['name']) ? $entry['name'] : '';
				if ($index_name === '') {
					$this->append_table_validation_issue(
						$issues,
						'indexes',
						'error',
						'index_name_missing',
						'A saved index definition is missing a name'
					);
					continue;
				}
				if (!isset($live_indexes[$index_name])) {
					$this->append_table_validation_issue(
						$issues,
						'indexes',
						'warning',
						'index_not_applied',
						'Saved index "' . $index_name . '" is not present on the data collection (use Apply saved indexes)',
						array('index' => $index_name)
					);
				}
				$keys = $this->definition_entry_to_mongo_index_keys($entry);
				$indexable = $this->get_indexable_field_names($db_id, $table_id);
				foreach (array_keys($keys) as $field_name) {
					if ($field_name === '_fts' || $field_name === '$**') {
						continue;
					}
					if (!empty($indexable) && !isset($indexable[$field_name])) {
						$this->append_table_validation_issue(
							$issues,
							'indexes',
							'error',
							'index_unknown_field',
							'Index "' . $index_name . '" references unknown field "' . $field_name . '"',
							array('index' => $index_name, 'field' => $field_name)
						);
					}
				}
			}
		}

		return $this->build_table_validation_report($issues, $context);
	}

	/**
	 * Whether a single BSON value is consistent with a dictionary data_type (sample check).
	 *
	 * @param mixed $value
	 * @param string $data_type
	 * @return bool
	 */
	private function sample_value_matches_data_type($value, $data_type)
	{
		$data_type = $this->normalize_field_data_type($data_type);
		if ($value === null || $value === '') {
			return true;
		}

		switch ($data_type) {
			case 'string':
				return is_string($value);
			case 'integer':
				if (is_int($value)) {
					return true;
				}
				if (is_float($value)) {
					return floor($value) == $value
						&& $value <= PHP_INT_MAX
						&& $value >= PHP_INT_MIN;
				}
				return false;
			case 'float':
				return is_int($value) || is_float($value);
			case 'boolean':
				return is_bool($value);
			case 'date':
			case 'datetime':
				return is_string($value);
			case 'array':
				return is_array($value);
			case 'object':
				return is_array($value) || is_object($value);
			default:
				return true;
		}
	}

	private function append_table_validation_issue(array &$issues, $section, $severity, $code, $message, array $context = array())
	{
		$issues[] = array(
			'section' => $section,
			'severity' => $severity,
			'code' => $code,
			'message' => $message,
			'context' => $context,
		);
	}

	private function build_table_validation_report(array $issues, array $context)
	{
		$errors = 0;
		$warnings = 0;
		foreach ($issues as $issue) {
			if ($issue['severity'] === 'error') {
				$errors++;
			} elseif ($issue['severity'] === 'warning') {
				$warnings++;
			}
		}

		return array(
			'valid' => ($errors === 0),
			'summary' => array(
				'errors' => $errors,
				'warnings' => $warnings,
				'issues' => count($issues),
			),
			'issues' => $issues,
			'context' => $context,
		);
	}

	/**
	 * Read the first row of a CSV file as trimmed column names.
	 *
	 * @param string $full_file_path Path under datafiles/
	 * @param string $delimiter Import delimiter keyword
	 * @return array
	 * @throws Exception
	 */
	public function read_csv_header_columns($full_file_path, $delimiter = 'comma')
	{
		if (!file_exists($full_file_path)) {
			throw new Exception('CSV file not found');
		}
		$handle = fopen($full_file_path, 'r');
		if (!$handle) {
			throw new Exception('Failed to open CSV file');
		}
		$delimiters = array(
			'comma' => ',',
			'tab' => "\t",
			'semicolon' => ';',
			',' => ',',
			';' => ';',
		);
		$delimiter_char = isset($delimiters[$delimiter]) ? $delimiters[$delimiter] : ',';
		$header = fgetcsv($handle, 0, $delimiter_char);
		fclose($handle);
		if (!$header || !is_array($header)) {
			throw new Exception('Failed to read CSV header row');
		}
		$header = array_map(array($this, 'normalize_csv_column_name'), $header);
		if (empty($header) || (count($header) === 1 && $header[0] === '')) {
			throw new Exception('CSV header row is empty');
		}
		return $header;
	}

	/**
	 * Delete table data, reset import progress, and import from stored CSV.
	 *
	 * @param string $db_id
	 * @param string $table_id
	 * @param array $options Import options (delimiter, max_time, etc.)
	 * @return array
	 * @throws Exception
	 */
	public function process_import_reload_request($db_id, $table_id, $options)
	{
		$table_definition = $this->validate_table_and_file($db_id, $table_id);
		if (empty($table_definition['csv_file_path'])) {
			throw new Exception('No stored CSV file path on table; upload a CSV before reload');
		}

		$this->delete_table_data($db_id, $table_id);
		$this->reset_import_progress($db_id, $table_id);

		return $this->process_import_request($db_id, $table_id, $options);
	}

	public function process_import_request($db_id, $table_id, $options)
	{
		$validated_options = $this->validate_import_parameters($options);
		$table_definition = $this->validate_table_and_file($db_id, $table_id);

		if ($validated_options['status_only']) {
			return $this->get_import_status_response($table_definition);
		}

		$status = isset($table_definition['import_progress']['import_status'])
			? $table_definition['import_progress']['import_status']
			: '';
		if ($this->is_import_terminal($status)) {
			return $this->get_completed_import_response($table_definition, $validated_options);
		}

		$result = $this->execute_import_process($db_id, $table_id, $validated_options, $table_definition);

		return $this->build_import_response($table_definition, $result, $validated_options);
	}

	/**
	 * Validate import parameters
	 */
	private function validate_import_parameters($options)
	{
		$status_only = isset($options['status']) ? (bool)$options['status'] : false;
		$delimiter = isset($options['delimiter']) ? $options['delimiter'] : 'comma';
		$max_time = isset($options['max_time']) ? (int)$options['max_time'] : 30;
		
		// Validate and normalize max_time (between 10 and 120 seconds)
		if ($max_time < 10) {
			$max_time = 10;
		}
		if ($max_time > 120) {
			$max_time = 120;
		}
		
		return array(
			'status_only' => $status_only,
			'delimiter' => $delimiter,
			'max_time' => $max_time
		);
	}

	/**
	 * Validate table and file existence
	 */
	private function validate_table_and_file($db_id, $table_id)
	{
		$table_definition = $this->get_table_type($db_id, $table_id);
		
		if (!$table_definition) {
			throw new Exception("Table definition not found - please upload a file first");
		}

		if (!isset($table_definition['csv_file_path']) || empty($table_definition['csv_file_path'])) {
			throw new Exception("No CSV file path found in table definition - please upload a file first");
		}

		$validated_file_path = validate_file_path($table_definition['csv_file_path'], $db_id, $table_id);
		$full_file_path = 'datafiles/' . $validated_file_path;

		if (!file_exists($full_file_path)) {
			throw new Exception("CSV file not found: " . $validated_file_path);
		}

		return $table_definition;
	}

	/**
	 * Get import status response (status-only request)
	 */
	private function get_import_status_response($table_definition)
	{
		$import_progress = isset($table_definition['import_progress']) ? $table_definition['import_progress'] : array();

		return array(
			'status' => 'success',
			'csv_info' => array(
				'csv_file_path' => $table_definition['csv_file_path'],
				'csv_uploaded_at' => $table_definition['csv_uploaded_at'],
				'file_size' => isset($import_progress['file_size']) ? $import_progress['file_size'] : 0
			),
			'progress' => $this->import_progress_payload($import_progress, $table_definition)
		);
	}

	/**
	 * Get completed import response
	 */
	private function get_completed_import_response($table_definition, $options)
	{
		$import_progress = isset($table_definition['import_progress']) ? $table_definition['import_progress'] : array();
		$progress = $this->import_progress_payload($import_progress, $table_definition, false);
		if (!isset($progress['progress_percent']) || $progress['progress_percent'] < 100) {
			$progress['progress_percent'] = 100;
		}

		return array(
			'status' => 'success',
			'csv_info' => array(
				'csv_file_path' => $table_definition['csv_file_path'],
				'csv_uploaded_at' => $table_definition['csv_uploaded_at'],
				'file_size' => isset($import_progress['file_size']) ? $import_progress['file_size'] : 0
			),
			'batch' => array(
				'rows_processed' => 0,
				'byte_offset_start' => isset($import_progress['byte_offset_end']) ? $import_progress['byte_offset_end'] : 0,
				'byte_offset_end' => isset($import_progress['byte_offset_end']) ? $import_progress['byte_offset_end'] : 0,
				'execution_time_seconds' => 0,
				'execution_time_formatted' => '00h:00m:00s'
			),
			'progress' => $progress,
			'next' => null,
			'message' => $this->import_completion_message($progress)
		);
	}

	private function import_completion_message(array $progress)
	{
		$status = isset($progress['import_status']) ? $progress['import_status'] : 'completed';
		$inserted = isset($progress['rows_inserted']) ? (int) $progress['rows_inserted'] : 0;
		$read = isset($progress['csv_records_read']) ? (int) $progress['csv_records_read'] : 0;
		$blank = isset($progress['rows_blank']) ? (int) $progress['rows_blank'] : 0;
		$failed = isset($progress['rows_failed']) ? (int) $progress['rows_failed'] : 0;
		$warned = isset($progress['rows_warned']) ? (int) $progress['rows_warned'] : 0;
		$balanced = !empty($progress['balanced']);

		if ($status === 'failed' || !$balanced) {
			return sprintf(
				'Import finished with accounting mismatch: read %d CSV row(s), inserted %d, blank %d, failed %d. Use DELETE to reset and re-import.',
				$read,
				$inserted,
				$blank,
				$failed
			);
		}

		$msg = sprintf('Imported %d of %d CSV row(s).', $inserted, $read);
		if ($blank > 0) {
			$msg .= sprintf(' %d blank row(s) skipped.', $blank);
		}
		if ($failed > 0) {
			$msg .= sprintf(' %d row(s) failed.', $failed);
		}
		if ($warned > 0) {
			$msg .= sprintf(' %d row(s) imported with warnings (extra columns).', $warned);
		}
		if ($failed > 0 || $blank > 0) {
			$msg .= ' See import_errors.ndjson for details.';
		}
		return $msg;
	}

	/**
	 * Execute import process
	 */
	private function execute_import_process($db_id, $table_id, $options, $table_definition)
	{
		$progress = isset($table_definition['import_progress']) ? $table_definition['import_progress'] : array();
		$byte_offset = isset($progress['byte_offset_end']) ? $progress['byte_offset_end'] : 0;
		$existing_row_count = isset($progress['total_rows_processed']) ? $progress['total_rows_processed'] : 0;

		$this->validate_import_consistency($db_id, $table_id, $byte_offset, $existing_row_count, $table_definition);

		if ($byte_offset == 0) {
			$this->apply_table_index_definitions($db_id, $table_id, true);
			$this->reset_import_errors_file($db_id, $table_id);
			$this->update_import_progress($db_id, $table_id, $this->default_import_progress(array(
				'import_status' => 'in_progress',
				'import_started_at' => date('Y-m-d H:i:s'),
				'errors_file' => $this->get_import_errors_relative_path($db_id, $table_id)
			)));
			$resume = array(
				'csv_records_read' => 0,
				'rows_inserted' => 0,
				'rows_blank' => 0,
				'rows_failed' => 0,
				'rows_warned' => 0,
				'last_csv_row' => 0
			);
		} else {
			$resume = array(
				'csv_records_read' => isset($progress['csv_records_read']) ? (int) $progress['csv_records_read'] : 0,
				'rows_inserted' => (int) $existing_row_count,
				'rows_blank' => isset($progress['rows_blank']) ? (int) $progress['rows_blank'] : 0,
				'rows_failed' => isset($progress['rows_failed']) ? (int) $progress['rows_failed'] : 0,
				'rows_warned' => isset($progress['rows_warned']) ? (int) $progress['rows_warned'] : 0,
				'last_csv_row' => isset($progress['last_csv_row']) ? (int) $progress['last_csv_row'] : 0
			);
		}

		$validated_file_path = validate_file_path($table_definition['csv_file_path'], $db_id, $table_id);
		$full_file_path = 'datafiles/' . $validated_file_path;

		$result = $this->import_csv_chunked(
			$db_id,
			$table_id,
			$full_file_path,
			$options['delimiter'],
			$byte_offset,
			$options['max_time'],
			$resume
		);

		$inserted = (int) $result['rows_inserted_total'];
		$read = (int) $result['csv_records_read_total'];
		$blank = (int) $result['rows_blank_total'];
		$failed = (int) $result['rows_failed_total'];
		$warned = (int) $result['rows_warned_total'];
		$accounts_match = ($read === ($inserted + $blank + $failed));

		$progress_data = array(
			'total_rows_processed' => $inserted,
			'csv_records_read' => $read,
			'rows_blank' => $blank,
			'rows_failed' => $failed,
			'rows_warned' => $warned,
			'last_csv_row' => (int) $result['last_csv_row'],
			'byte_offset_end' => $result['byte_offset_end'],
			'file_size' => $result['file_size'],
			'progress_percent' => $result['progress_percent'],
			'errors_file' => $this->get_import_errors_relative_path($db_id, $table_id),
			'last_batch' => array(
				'rows_processed' => $result['rows_processed'],
				'csv_records_read' => $result['csv_records_read'],
				'rows_blank' => $result['rows_blank'],
				'rows_failed' => $result['rows_failed'],
				'rows_warned' => $result['rows_warned'],
				'byte_offset_start' => $result['byte_offset_start'],
				'byte_offset_end' => $result['byte_offset_end'],
				'execution_time' => $result['execution_time_seconds']
			)
		);

		if (!$result['has_more']) {
			$mongo_count = (int) $this->get_table_row_count_fast($db_id, $table_id);
			$balanced = $accounts_match && ($mongo_count === $inserted);
			$progress_data['balanced'] = $balanced;
			$progress_data['mongo_count'] = $mongo_count;
			$progress_data['import_completed_at'] = date('Y-m-d H:i:s');
			$progress_data['progress_percent'] = 100;

			if (!$balanced) {
				$progress_data['import_status'] = 'failed';
				log_message('error', sprintf(
					'CSV import accounting mismatch db=%s table=%s read=%d inserted=%d blank=%d failed=%d mongo=%d',
					$db_id,
					$table_id,
					$read,
					$inserted,
					$blank,
					$failed,
					$mongo_count
				));
			} elseif ($failed > 0) {
				$progress_data['import_status'] = 'completed_with_errors';
			} else {
				$progress_data['import_status'] = 'completed';
			}
		} else {
			$progress_data['import_status'] = 'in_progress';
			$progress_data['balanced'] = $accounts_match;
		}

		$this->update_import_progress($db_id, $table_id, $progress_data);

		$result['balanced'] = !empty($progress_data['balanced']);
		$result['import_status'] = $progress_data['import_status'];
		return $result;
	}

	/**
	 * Build import response
	 */
	private function build_import_response($table_definition, $result, $options)
	{
		$updated_definition = $this->get_table_type($table_definition['db_id'], $table_definition['table_id']);
		$import_progress = isset($updated_definition['import_progress']) ? $updated_definition['import_progress'] : array();
		$progress = $this->import_progress_payload($import_progress, $table_definition, $result['has_more']);
		if (isset($result['progress_percent'])) {
			$progress['progress_percent'] = $result['progress_percent'];
		}
		if (!$result['has_more']) {
			$progress['progress_percent'] = 100;
			$progress['has_more'] = false;
		}

		$response = array(
			'status' => 'success',
			'csv_info' => array(
				'csv_file_path' => $table_definition['csv_file_path'],
				'csv_uploaded_at' => $table_definition['csv_uploaded_at'],
				'file_size' => $result['file_size'],
				'file_size_mb' => round($result['file_size'] / (1024 * 1024), 2)
			),
			'batch' => array(
				'rows_processed' => $result['rows_processed'],
				'csv_records_read' => $result['csv_records_read'],
				'rows_blank' => $result['rows_blank'],
				'rows_failed' => $result['rows_failed'],
				'rows_warned' => $result['rows_warned'],
				'byte_offset_start' => $result['byte_offset_start'],
				'byte_offset_end' => $result['byte_offset_end'],
				'execution_time_seconds' => $result['execution_time_seconds'],
				'execution_time_formatted' => $result['execution_time_formatted']
			),
			'progress' => $progress,
			'next' => $result['has_more'] ? array(
				'byte_offset' => $result['byte_offset_end'],
				'endpoint' => base_url() . 'api/tables/import/' . $table_definition['db_id'] . '/' . $table_definition['table_id'],
				'message' => 'Call this endpoint again to continue import'
			) : null
		);

		if (!$result['has_more']) {
			$response['message'] = $this->import_completion_message($progress);
		}

		return $response;
	}

	public function reset_import_progress($db_id, $table_id)
	{
		$this->reset_import_errors_file($db_id, $table_id);
		return $this->update_import_progress($db_id, $table_id, $this->default_import_progress(array(
			'errors_file' => $this->get_import_errors_relative_path($db_id, $table_id)
		)));
	}

	/**
	 * Validate import consistency
	 */
	private function validate_import_consistency($db_id, $table_id, $byte_offset, $expected_row_count, $table_definition)
	{
		$existing_rows = $this->get_table_row_count_fast($db_id, $table_id);
		
		if ($byte_offset == 0) {
			// New import - table must be empty
			if ($existing_rows > 0) {
				throw new Exception("Table already contains {$existing_rows} rows. Use DELETE /api/tables/{$db_id}/{$table_id} to clear data first.");
			}
		} else {
			// Resume import - check consistency
			if ($existing_rows !== $expected_row_count) {
				throw new Exception("Data inconsistency: expected {$expected_row_count} rows, found {$existing_rows} rows. Use DELETE endpoint to reset.");
			}
		}
	}

	/**
	 * Generate field dictionary document ID
	 */
	private function get_field_dictionary_id($db_id, $table_id, $field_name)
	{
		return 'dict_' . strtolower($db_id) . '_' . strtolower($table_id) . '_' . strtolower($field_name);
	}

	/**
	 * Get maximum field_order for a table
	 */
	private function get_max_field_order($db_id, $table_id)
	{
		$collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
		
		$result = $collection->findOne(
			[
				'db_id' => strtolower($db_id),
				'table_id' => strtolower($table_id)
			],
			[
				'sort' => ['field_order' => -1],
				'projection' => ['field_order' => 1]
			]
		);
		
		return $result && isset($result['field_order']) ? (int)$result['field_order'] : 0;
	}

	/**
	 * Get default field metadata structure
	 */
	private function get_default_field_metadata($field_name, $db_id, $table_id)
	{
		$max_order = $this->get_max_field_order($db_id, $table_id);
		$now = date('Y-m-d H:i:s');
		
		return [
			'name' => $field_name,
			'label' => $field_name,
			'description' => '',
			'data_type' => $this->normalize_field_data_type(null),
			'column_type' => null,
			'time_period_format' => null,
			'unit_of_measurement' => null,
			'field_order' => $max_order + 1,
			'code_list' => [],
			'code_list_reference' => null,
			'db_id' => strtolower($db_id),
			'table_id' => strtolower($table_id),
			'created_at' => $now,
			'updated_at' => $now
		];
	}

	/**
	 * Convert field data types in MongoDB collection based on field metadata
	 * Uses MongoDB's $convert operator to convert values to their target types
	 * 
	 * @param string $db_id Database ID
	 * @param string $table_id Table ID
	 * @return array Conversion statistics
	 */
	public function convert_table_field_types($db_id, $table_id)
	{
		$db_id = strtolower($db_id);
		$table_id = strtolower($table_id);
		
		// Get all field metadata for this table
		$fields = $this->get_field_metadata($db_id, $table_id);
		
		if (empty($fields)) {
			throw new Exception("No field metadata found for table");
		}
		
		// Get the data collection
		$collection = $this->mongo_client->{$this->get_db_name()}->{$this->get_table_name($db_id, $table_id)};
		
		// Get total document count
		$total_documents = $collection->countDocuments([]);
		
		$field_results = [];
		$fields_converted = 0;
		$fields_skipped = 0;
		$total_modified = 0;
		
		// Types to exclude from conversion (keep as strings)
		$excluded_types = ['date', 'datetime'];
		
		foreach ($fields as $field) {
			$field_name = $field['name'];
			$data_type = isset($field['data_type']) ? $field['data_type'] : null;
			
			// Skip fields without data_type or excluded types
			if (empty($data_type) || in_array($data_type, $excluded_types)) {
				$fields_skipped++;
				$field_results[] = [
					'field_name' => $field_name,
					'data_type' => $data_type,
					'status' => 'skipped',
					'reason' => empty($data_type) ? 'no_data_type' : 'excluded_type'
				];
				continue;
			}
			
			// Map metadata data_type to MongoDB BSON type
			$mongo_type = $this->map_data_type_to_mongo_type($data_type);
			
			if (!$mongo_type) {
				$fields_skipped++;
				$field_results[] = [
					'field_name' => $field_name,
					'data_type' => $data_type,
					'status' => 'skipped',
					'reason' => 'unsupported_type'
				];
				continue;
			}
			
			try {
				// Build update pipeline with $convert
				$pipeline = [
					[
						'$set' => [
							$field_name => [
								'$convert' => [
									'input' => '$' . $field_name,
									'to' => $mongo_type,
									'onError' => '$' . $field_name,  // Keep original value on error
									'onNull' => '$' . $field_name    // Keep original value if null
								]
							]
						]
					]
				];
				
				// Execute updateMany with pipeline
				$result = $collection->updateMany([], $pipeline);
				$modified_count = $result->getModifiedCount();
				
				$fields_converted++;
				$total_modified += $modified_count;
				
				$field_results[] = [
					'field_name' => $field_name,
					'data_type' => $data_type,
					'mongo_type' => $mongo_type,
					'status' => 'success',
					'documents_modified' => $modified_count
				];
			} catch (Exception $e) {
				$field_results[] = [
					'field_name' => $field_name,
					'data_type' => $data_type,
					'status' => 'error',
					'error' => $e->getMessage()
				];
			}
		}
		
		return [
			'total_fields' => count($fields),
			'fields_converted' => $fields_converted,
			'fields_skipped' => $fields_skipped,
			'total_documents' => $total_documents,
			'documents_modified' => $total_modified,
			'field_results' => $field_results
		];
	}
	
	/**
	 * Map metadata data_type to MongoDB BSON type for $convert operator
	 * 
	 * @param string $data_type Metadata data type
	 * @return string|null MongoDB BSON type or null if unsupported
	 */
	private function map_data_type_to_mongo_type($data_type)
	{
		$type_map = [
			'string' => 'string',
			'integer' => 'int',
			'int' => 'int',
			'float' => 'double',
			'boolean' => 'bool',
			'bool' => 'bool',
			'array' => 'array',
			'object' => 'object',
		];
		
		$normalized_type = strtolower($data_type);
		return isset($type_map[$normalized_type]) ? $type_map[$normalized_type] : null;
	}

	/**
	 * Create indexes on table_dictionary collection
	 * Call this method once to set up indexes for optimal query performance
	 * 
	 * @return array Array of index creation results
	 */
	function create_dictionary_indexes()
	{
		$collection = $this->mongo_client->{$this->get_db_name()}->{'table_dictionary'};
		$results = [];
		
		try {
			// Primary lookup: Get all fields for a table (sorted by field_order)
			$results['idx_table_fields'] = $collection->createIndex(
				[
					'db_id' => 1,
					'table_id' => 1,
					'field_order' => 1
				],
				['name' => 'idx_table_fields']
			);
		} catch (Exception $e) {
			$results['idx_table_fields'] = ['error' => $e->getMessage()];
		}
		
		try {
			// Field lookup: Get specific field (unique constraint)
			$results['idx_table_field_name'] = $collection->createIndex(
				[
					'db_id' => 1,
					'table_id' => 1,
					'name' => 1
				],
				['name' => 'idx_table_field_name', 'unique' => true]
			);
		} catch (Exception $e) {
			$results['idx_table_field_name'] = ['error' => $e->getMessage()];
		}
		
		try {
			// Cross-table queries: Find fields by name across tables
			$results['idx_field_name'] = $collection->createIndex(
				['name' => 1],
				['name' => 'idx_field_name']
			);
		} catch (Exception $e) {
			$results['idx_field_name'] = ['error' => $e->getMessage()];
		}
		
		try {
			// Column type queries: Find all measures, dimensions, etc.
			$results['idx_column_type'] = $collection->createIndex(
				['column_type' => 1],
				['name' => 'idx_column_type']
			);
		} catch (Exception $e) {
			$results['idx_column_type'] = ['error' => $e->getMessage()];
		}
		
		try {
			// Text search on labels and descriptions
			$results['idx_text_search'] = $collection->createIndex(
				[
					'label' => 'text',
					'description' => 'text'
				],
				['name' => 'idx_text_search']
			);
		} catch (Exception $e) {
			$results['idx_text_search'] = ['error' => $e->getMessage()];
		}
		
		return $results;
	}
	
}    
