<?php

class Croissant_Writer
{
    private $data;
    private $writer;
    private $ci;
    private $extended_metadata=false;
    private $context_config;


    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Dataset_model');
        $this->ci->load->model('Survey_resource_model');
        $this->ci->load->model('Data_file_model');
        $this->ci->load->model('Variable_model');
    }


    /**
     * 
     * Enable extended metadata
     * 
     * Add additional metadata to the croissant file
     * 
     * @param bool $enable - Enable extended metadata
     */
    function enable_extended_metadata($enable=true)
    {
        $this->extended_metadata=$enable;
    }


    /**
     * 
     * Write croissant file
     * 
     * @param string $sid - Survey ID
     * @param string $output - Output file
     */
    function write_croissant($sid, $output='php://output')
    {
        $dataset=$this->ci->Dataset_model->get_row_detailed($sid);

        if ($dataset['type']!='survey'){
            throw new Exception('Only `microdata` datasets are supported:: '. $sid . ' - ' . $dataset['type']);
        }

        //dataset information (study level metadata)
        $dataset_info=$this->map_dataset_info($dataset);

        //distribution information (external resources/files)
        $resources=$this->ci->Survey_resource_model->get_resources_by_survey($sid);

        $distribution_info=$this->map_distribution_info($sid,$resources);

        if (empty($distribution_info)){
            return $dataset_info;
        }

        $dataset_info['distribution']=$distribution_info;

        //recordset information (data file level metadata)
        $data_files=$this->ci->Data_file_model->get_all_by_survey($sid);

        $data_files_by_name=[];
        foreach($data_files as $data_file){
            $file_name=pathinfo($data_file['file_name'], PATHINFO_FILENAME);
            $file_name=strtoupper($file_name);
            $data_files_by_name[$file_name]=$data_file;
        }
        
        $microdata_files=[];
        foreach($resources as $resource){
            //microdata files only
            if (strpos($resource['dctype'],'dat/micro')==false){
                continue;
            }

            $file_name=basename($resource['filename']);
            $file_name=pathinfo($file_name, PATHINFO_FILENAME);
            $file_name=strtoupper($file_name);

            if (isset($data_files_by_name[$file_name])){
                $data_files_by_name[$file_name]['resource_file_name']=$resource['filename'];
                $data_files_by_name[$file_name]['resource_id']=$resource['resource_id'];
                $microdata_files[]=$data_files_by_name[$file_name];
            }
        }

        //add variable data dictionary
        foreach($microdata_files as $data_file){
            $variables=$this->ci->Variable_model->list_by_dataset($sid,$data_file['file_id'],$detailed=true);
            $recordset_info=$this->get_recordset_info($data_file,$variables);

            if (!empty($recordset_info)){
                $dataset_info['recordSet'][]=$recordset_info;
            }
        }
        
        return $dataset_info;
    }


    /**
     * 
     * Map dataset information (study level metadata)
     * 
     * @param array $metadata - Dataset metadata [microdata]
     * @return array - Mapped dataset information
     */
    function map_dataset_info($projectObj)
    {
        $metadata = new \Adbar\Dot($projectObj['metadata']);
        $dataset_info=array();
        $dataset_info['@context'] = $this->get_current_context();

        $dataset_info['@type'] = 'sc:Dataset';
        $dataset_info['conformsTo'] = 'http://mlcommons.org/croissant/1.0';
        $dataset_info['@id'] = $projectObj['idno'];
        $dataset_info['name'] = $metadata->get('study_desc.title_statement.title');
        $dataset_info['alternateName'] = $metadata->get('study_desc.title_statement.alternate_title');
        $dataset_info['identifier'] = $metadata->get('study_desc.title_statement.idno');
        $dataset_info['description'] = $metadata->get('study_desc.study_info.abstract');

        $dataset_info['creator'] = [];
        foreach ($metadata->get('study_desc.authoring_entity') as $author) {
            $dataset_info['creator'][] = [
                '@type' => 'Organization',
                'name' => $author['name']
                //'affiliation' => $author['affiliation']
            ];
        }

        $dataset_info['publisher'] = [];
        $producers=(array)$metadata->get('study_desc.production_statement.producers');

        if (count($producers)>0){
            foreach ($producers as $producer) {
                $dataset_info['publisher'][] = [
                    '@type' => 'Organization',
                    'name' => $producer['name']
                ];
            }
        }

        $dataset_info['dateCreated'] = date('Y-m-d',$projectObj['created']);//$metadata->get('study_desc.production_statement.prod_date');
        $dataset_info['datePublished'] = $metadata->get('study_desc.version_statement.version_date');
        $dataset_info['version'] = $metadata->get('study_desc.version_statement.version');

        $dataset_info['spatialCoverage'] = [];
        foreach ((array)$metadata->get('study_desc.study_info.nation') as $nation) {
            $dataset_info['spatialCoverage'][] = [
                '@type' => 'Place',
                'name' => $nation['name']
            ];
        }

        $dataset_info['temporalCoverage'] = $metadata->get('study_desc.study_info.coll_dates.0.start');

        $dataset_info['license'] = $metadata->get('data_access.use_conditions');
        $dataset_info['url'] = site_url('catalog/study/'.$projectObj['idno']);
        //$dataset_info['isAccessibleForFree'] = true;

        $dataset_info['keywords'] = [];
        foreach ((array)$metadata->get('study_desc.study_info.keywords') as $keyword) {
            $dataset_info['keywords'][] = $keyword['keyword'];
        }

        if ($this->extended_metadata){
            //add study_desc.series_statement using schema.org properties
            $series_name = $metadata->get('study_desc.series_statement.series_name');
            $series_info = $metadata->get('study_desc.series_statement.series_info');
            
            if ($series_name) {
                $dataset_info['isPartOf'] = [
                    '@type' => 'CreativeWork',
                    'name' => $series_name,
                    'description' => $series_info
                ];
            }
        }

        // Handle method data only when extended metadata is enabled
        if ($this->extended_metadata) {
            $method_data = $metadata->get('study_desc.method');
            
            if ($method_data) {
                // Add method context definitions only when extended metadata is enabled
                $this->add_context_item('method', 'nada:method');
                $this->add_context_item('dataCollection', 'nada:dataCollection');
                $this->add_context_item('timeMethod', 'nada:timeMethod');
                $this->add_context_item('researchInstrument', 'nada:researchInstrument');
                $this->add_context_item('weight', 'nada:weight');
                $this->add_context_item('cleaningOperations', 'nada:cleaningOperations');
                $this->add_context_item('methodNotes', 'nada:methodNotes');
                
                // Use the custom fields with proper structure
                $dataset_info['method'] = [];
                
                // Add data collection information
                if (isset($method_data['data_collection'])) {
                    $data_collection = $method_data['data_collection'];
                    $dataset_info['method']['dataCollection'] = [];
                    
                    if (isset($data_collection['time_method'])) {
                        $dataset_info['method']['dataCollection']['timeMethod'] = $data_collection['time_method'];
                    }
                    
                    if (isset($data_collection['research_instrument'])) {
                        $dataset_info['method']['dataCollection']['researchInstrument'] = $data_collection['research_instrument'];
                    }
                    
                    if (isset($data_collection['weight'])) {
                        $dataset_info['method']['dataCollection']['weight'] = $data_collection['weight'];
                    }
                    
                    if (isset($data_collection['cleaning_operations'])) {
                        $dataset_info['method']['dataCollection']['cleaningOperations'] = $data_collection['cleaning_operations'];
                    }
                }
                
                // Add method notes
                if (isset($method_data['method_notes'])) {
                    $dataset_info['method']['methodNotes'] = $method_data['method_notes'];
                }
            }
        }

        


        return $dataset_info;
    }



    /**
     * 
     * Map distribution information (file level metadata)
     * 
     * @param array $resources - Dataset resources [microdata]
     * @return array - Mapped distribution information
     */
    function map_distribution_info($sid,$resources)
    {
        $distribution_info=array();
        $resourcesArr= new \Adbar\Dot($resources);

        $survey_folder=$this->ci->Catalog_model->get_survey_path_full($sid);								

        foreach ($resourcesArr as $resource) {

            $file_name=$resource['filename'];

            if (is_url($file_name)){
                //$file_size=0;
                //$file_encoding_format='application/octet-stream';
                continue;
            }else{
                //build complete filepath to be downloaded
		        $file_path=unix_path($survey_folder.'/'.$resource['filename']);
                if (file_exists($file_path)){
                    $file_size=$this->get_file_size($file_path);
                    $file_encoding_format=$this->get_file_encoding_format($file_path);
                    $file_md5=md5_file($file_path);
                }else{
                    continue;
                }
            }

            $distribution_info[] = [
                '@type' => 'cr:FileObject',
                '@id' => $resource['resource_id'],
                'name' => basename($file_path),
                //'path' => $file_path,
                'encodingFormat' => $file_encoding_format,
                'md5' => $file_md5,
                'contentSize' => $file_size . ' B',
                'description' => $resource['description'],
                'contentUrl' => site_url('catalog/'.$sid.'/download/'.$resource['resource_id'])
            ];
        }

        return $distribution_info;
    }


    /**
     * 
     * Get recordset information
     * 
     * @param array $dataFileObj - Data file object
     * @param array $variables - Variables
     * @return array - Recordset information
     */
    function get_recordset_info($dataFileObj, $variables)
    {
        $recordset_info=array();
        $recordset_info['@id'] = $dataFileObj['id'];
        $recordset_info['@type'] = 'cr:RecordSet';
        $recordset_info['field'] = [];

        $data_types_map=[
            'character' => 'sc:Text',
            'numeric' => 'sc:Number',
            'date' => 'sc:Date',
            'datetime' => 'sc:DateTime',
            'time' => 'sc:Time',
            'boolean' => 'sc:Boolean',
            'integer' => 'sc:Integer',
            'float' => 'sc:Float',
            'double' => 'sc:Double',
            'long' => 'sc:Long',
        ];

        foreach ($variables as $variable) {

            if (isset($variable['field_dtype']) && isset($data_types_map[$variable['field_dtype']])){
                $data_type=$data_types_map[$variable['field_dtype']];
            }else{
                $data_type='sc:Text';
            }

            $recordset_info['field'][] = [
                '@type' => 'cr:Field',
                'name' => $variable['name'],
                'description' => $variable['labl'],
                'dataType' => $data_type,
                'source' => [
                    /*'@id' => $dataFileObj['id'],*/
                    'fileObject' => [
                        '@id' => $dataFileObj['resource_id']
                    ]
                    /*'fileSet' => [
                        '@id' => $dataFileObj['id']
                    ]*/
                ]
            ];
        }

        return $recordset_info;
    }


    
    
    /**
     * 
     * Get file size from the file name
     * 
     * @param string $file_name - File name
     * @return int - File size in bytes
     */
    function get_file_size($file_name){

        if (file_exists($file_name)){
            return filesize($file_name);
        }
        return 0;
    }


    /**
     * 
     * Get file encoding format from the file name
     * 
     */
    function get_file_encoding_format($file_name){
        
        $mime_types=[
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',            
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            'bz2' => 'application/x-bzip2',
            'dta' => 'application/x-stata-data',
            'do' => 'text/plain',
            'sav' => 'application/x-spss-sav',
            'por' => 'application/x-spss-por',
            'r' => 'text/x-r-source'
        ];

        //check if file_name is a url
        if (filter_var($file_name, FILTER_VALIDATE_URL)){
            return 'application/octet-stream';
        }
        
        //get file extension
        $extension=pathinfo($file_name, PATHINFO_EXTENSION);

        if (isset($mime_types[$extension])){
            return $mime_types[$extension];
        }        

        return 'application/octet-stream';

    }

    /**
     * 
     * Get the @context configuration
     * 
     * @return array - Context configuration
     */
    private function get_context_config()
    {
        return [
            '@language' => 'en',
            '@vocab' => 'https://schema.org/',
            'citeAs' => 'cr:citeAs',
            'column' => 'cr:column',
            'conformsTo' => 'dct:conformsTo',
            'cr' => 'http://mlcommons.org/croissant/',
            'rai' => 'http://mlcommons.org/croissant/RAI/',
            'dct' => 'http://purl.org/dc/terms/',
            'data' => [
                '@id' => 'cr:data',
                '@type' => '@id'
            ],
            'dataType' => [
                '@id' => 'cr:dataType',
                '@type' => '@id'
            ],
            'examples' => [
                '@id' => 'cr:examples',
                '@type' => '@id'
            ],
            'extract' => 'cr:extract',
            'field' => 'cr:field',
            'fileProperty' => 'cr:fileProperty',
            'fileObject' => 'cr:fileObject',
            'fileSet' => 'cr:fileSet',
            'format' => 'cr:format',
            'includes' => 'cr:includes',
            'isLiveDataset' => 'cr:isLiveDataset',
            'jsonPath' => 'cr:jsonPath',
            'key' => 'cr:key',
            'md5' => 'cr:md5',
            'parentField' => 'cr:parentField',
            'path' => 'cr:path',
            'recordSet' => 'cr:recordSet',
            'references' => 'cr:references',
            'regex' => 'cr:regex',
            'repeated' => 'cr:repeated',
            'replace' => 'cr:replace',
            'separator' => 'cr:separator',
            'source' => 'cr:source',
            'subField' => 'cr:subField',
            'transform' => 'cr:transform',
            'sc' => 'https://schema.org/',
            'nada' => 'http://nada.org/terms/'
        ];
    }

    /**
     * 
     * Add or update context configuration
     * 
     * @param string $key - Context key
     * @param mixed $value - Context value
     */
    public function add_context_item($key, $value)
    {
        if (!isset($this->context_config)) {
            $this->context_config = $this->get_context_config();
        }
        $this->context_config[$key] = $value;
    }

    /**
     * 
     * Remove context configuration item
     * 
     * @param string $key - Context key to remove
     */
    public function remove_context_item($key)
    {
        if (isset($this->context_config) && isset($this->context_config[$key])) {
            unset($this->context_config[$key]);
        }
    }

    /**
     * 
     * Get current context configuration
     * 
     * @return array - Current context configuration
     */
    public function get_current_context()
    {
        if (!isset($this->context_config)) {
            $this->context_config = $this->get_context_config();
        }
        return $this->context_config;
    }

    /**
     * 
     * Set the entire context configuration
     * 
     * @param array $context - Complete context configuration
     */
    public function set_context($context)
    {
        $this->context_config = $context;
    }

    /**
     * 
     * Reset context to default configuration
     * 
     */
    public function reset_context()
    {
        $this->context_config = null;
    }

    /**
     * 
     * Add multiple context items at once
     * 
     * @param array $items - Array of key-value pairs to add
     */
    public function add_context_items($items)
    {
        if (!isset($this->context_config)) {
            $this->context_config = $this->get_context_config();
        }
        
        foreach ($items as $key => $value) {
            $this->context_config[$key] = $value;
        }
    }

}  