<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * 
 * 
 * Generate html output using templates
 *
 *
 */ 
class Display_template{
    
    private $ci;
    private $metadata;
    private $template;
    private $sidebar_items=array();
    private $sidebar_items_all=array();
    /** @var array<string, mixed>|null Last resolved template record (uid, name, source, …) */
    private $template_resolution=null;
    /** @var string 'web' | 'pdf' */
    private $render_context='web';
	
	function __construct()
	{
        $this->ci =& get_instance();
        $this->ci->load->helper("array");
        $this->ci->load->helper('display_template');
        $this->ci->load->helper('display_renderer');
        $this->ci->load->config('metadata_field_languages');
    }

    function initialize($metadata,$template)
    {
        $this->metadata=$metadata;
        $this->template=$template;
        $this->load_translations($metadata['type']);
        $this->load_title_overlay();
        $this->apply_preprocess_callback();
    }

    function apply_preprocess_callback()
    {
        //check if preprocess callback is defined
        $process_callback=$this->ci->config->item('metadata_template_preprocess_metadata');

        if ($process_callback){
            if (isset($process_callback['file']) && file_exists($process_callback['file'])){
                include($process_callback['file']);
                $this->metadata=call_user_func($process_callback['function'],$this->metadata);
                //$this->metadata=$process_callback['function']($this->metadata);
            }
            else{
                //log
                log_message('error', "missing preprocess file: ".$process_callback['file']);
            }            
        }        
    }

    function load_translations($type)
    {
        $map = $this->ci->config->item('metadata_field_languages');
        if (!is_array($map) || $map === array()) {
            $config = array();
            $path = APPPATH . 'config/metadata_field_languages.php';
            if (is_file($path)) {
                include $path;
            }
            $map = (isset($config['metadata_field_languages']) && is_array($config['metadata_field_languages']))
                ? $config['metadata_field_languages']
                : array();
        }

        $lookup = (string) $type;
        if ($lookup === 'timeseries-db' && empty($map[$lookup]) && !empty($map['timeseriesdb'])) {
            $lookup = 'timeseriesdb';
        } elseif ($lookup === 'timeseriesdb' && empty($map[$lookup]) && !empty($map['timeseries-db'])) {
            $lookup = 'timeseries-db';
        }

        if (!isset($map[$lookup]) || (string) $map[$lookup] === '') {
            log_message('error', 'METADATA_FIELD_LANGUAGE_NOT_DEFINED: ' . $type);
            return;
        }

        $lang_file = (string) $map[$lookup];
        $lang_language = $this->ci->config->item('language');
        $lang_file_path = APPPATH . '/language/' . $lang_language . '/' . $lang_file . '_lang.php';

        if (file_exists($lang_file_path)) {
            $this->ci->lang->load($lang_file);
        } else {
            log_message('error', 'missing language file ' . $lang_file_path);
        }
    }

    private function load_title_overlay()
    {
        display_template_set_title_overlay(array());
        $meta = $this->template_resolution;
        if (!is_array($meta) || empty($meta['id'])) {
            return;
        }
        $iso = function_exists('ci_lang_to_iso') ? ci_lang_to_iso() : 'en';
        $iso = display_template_normalize_lang($iso, false);
        $primary = display_template_normalize_lang(isset($meta['lang']) ? $meta['lang'] : 'en', false);
        if ($iso === '' || $primary === '' || $iso === $primary) {
            return;
        }
        $this->ci->load->model('Display_template_model');
        $map = $this->ci->Display_template_model->get_translation_map((int) $meta['id'], $iso);
        display_template_set_title_overlay($map);
    }
    

    /**
     * Resolve the catalog display core, initialize, and return body HTML (no sidebar chrome).
     *
     * @param array<string, mixed> $project Study row with metadata and resources
     * @param string $context 'web' | 'pdf'
     * @return string
     */
    function render_body_html($project, $context = 'web')
    {
        $this->render_context = ($context === 'pdf') ? 'pdf' : 'web';

        $template = $this->get_template_project_type($project['type']);
        if (isset($template['template'])) {
            $template = $template['template'];
        }
        $this->initialize($project, $template);

        return $this->render_html();
    }

    function render_html()
    {
        $this->populate_sidebar($this->template['items']);
        return $this->render_element($this->template['items']);
    }

    function is_pdf_context()
    {
        return $this->render_context === 'pdf';
    }

    /**
     * Swap interactive renderers for print. Accordion stays collapsed without JS.
     *
     * @param string $renderer_key
     * @return string
     */
    private function pdf_safe_renderer_key($renderer_key)
    {
        return display_template_pdf_safe_renderer_key($renderer_key);
    }

    function get_sidebar_items()
    {
        return array_intersect($this->sidebar_items,$this->sidebar_items_all);
    }

    function populate_sidebar($items, $parent=null)
    {        
        foreach($items as $item)
        {
            if (!is_array($item)) {
                continue;
            }
            if (display_template_node_is_custom_section_container($item)) {
                continue;
            }
            if ($item['type']=='section_container'){
                $this->populate_sidebar($item['items'],$item['key']);
            }

            if ($item['type']=='section'){
                $key = isset($item['key']) ? $item['key'] : '';
                $over = display_template_overlay_text($key);
                $this->sidebar_items_all[$key] = $over !== null ? $over : $item['title'];
            }
        }
    }

    function render_element($items)
    {
        $output=array();

        foreach($items as $idx=>$item){

            if (display_template_node_hidden($item)) {
                continue;
            }
            if (display_template_node_is_custom_section_container($item)) {
                continue;
            }

            if (isset($item['type']) && $item['type'] === 'widget') {
                if ($this->is_pdf_context()) {
                    continue;
                }
                $output[] = $this->render_widget($item);
                continue;
            }
            
            $renderer_key = display_template_field_composite_renderer_key($item);
            if ($renderer_key !== null) {
                if ($renderer_key === 'field_text_inline') {
                    $output[] = $this->render_text($item);
                    continue;
                }
                $output[] = $this->render_composite($item, $renderer_key);
                continue;
            }
            
            switch($item['type'])
            {
                case 'section_container':
                    $output[]= $this->render_section_container($item);
                    break;
                case 'section':
                    $html_=$this->render_section($item);
                    if (!empty($html_)){
                        $output[]=$html_;
                        $over = display_template_overlay_text($item['key']);
                        $this->sidebar_items[$item['key']] = $over !== null ? $over : $item['title'];
                    }
                    break;
                case 'nested_array':
                    $output[]= $this->render_nested_array($item);
                    break;
                case 'array':
                    $output[]= $this->render_array($item);
                    break;
                case 'simple_array':
                    $output[]= $this->render_simple_array($item);
                    break;
                case 'object':
                    $output[]= $this->render_object($item);
                    break;
                case 'text':
                case 'string':
                case 'boolean':
                case 'integer':
                case 'number':
                    $output[]= $this->render_text($item);
                    break;
                case 'date':
                    $output[]= $this->render_date($item);
                    break;
                case 'widget':
                    $output[]= $this->render_widget($item);
                    break;

                default:
                    throw new Exception("not supported: ". $item['type']);
            }
        }

        return implode("", $output);
    }

    private function render_section_container($item)
    {
        if (display_template_node_hidden($item)) {
            return false;
        }
        $output=array();
        $output[]='<div>';
        //$output[]='<h1 class="field-section-container mt-3">'.$item['title'].'</h1>';

        if (isset($item['items'])){
            $el_html=$this->render_element($item['items']);
            if(empty($el_html)){
                return false;
            }
            $output[]=$el_html;            
        }
        
        $output[]='</div>';        
        return implode("",$output);
    }
    
    private function render_section($item)
    {
        if (display_template_node_hidden($item)) {
            return false;
        }
        $output=array();
        $output[]='<div class="field-section-container pb-3">';
        $section_key = isset($item['key']) ? $item['key'] : '';
        $section_title = display_template_overlay_text($section_key);
        if ($section_title === null) {
            $section_title = tt(strtolower($item['title']), $item['title']);
        }
        $output[]='<h2 class="field-section" id="'.$item['key'].'">'.$section_title.'</h2>';

        if (isset($item['items'])){
            $el_html=$this->render_element($item['items']);
            if(empty($el_html)){
                return false;
            }
            $output[]=$el_html;
        }
        
        $output[]='</div>';        
        return implode("",$output);
    }
    
    private function render_nested_array($item)
    {
        return $this->render_composite($item, 'field_array_accordion');
    }


    private function render_composite($item, $renderer_key)
    {
        $renderer_key = $this->pdf_safe_renderer_key($renderer_key);
        if ($renderer_key === '') {
            return false;
        }
        $record = display_renderer_lookup($renderer_key);
        if (!$record || !display_renderer_is_active($record)) {
            log_message('error', 'Display template: unknown or inactive renderer "' . $renderer_key . '"');
            return false;
        }

        $layout_type = isset($item['type']) ? (string) $item['type'] : '';
        if ($layout_type !== '' && !display_renderer_supported_for_layout_type($record, $layout_type)) {
            log_message(
                'error',
                'Display template: renderer "' . $renderer_key . '" does not support layout type "' . $layout_type . '"'
            );
            return false;
        }

        $data_type = isset($this->metadata['type']) ? (string) $this->metadata['type'] : '';
        if ($data_type !== '' && !display_renderer_supported_for_data_type($record, $data_type)) {
            log_message(
                'error',
                'Display template: renderer "' . $renderer_key . '" does not support data type "' . $data_type . '"'
            );
            return false;
        }

        $view = display_renderer_view_basename($record);
        $template_field_path = APPPATH . 'views/display_templates/fields/' . $view . '.php';

        if (!file_exists($template_field_path)) {
            throw new Exception('template not found: application/views/display_templates/fields/' . $view . '.php');
        }

        $value = array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));

        $this->ci->load->model('Survey_resource_model');
        $resources = array_data_get($this->metadata, 'resources');
        if (!is_array($resources)) {
            $resources = array();
        }
        $resources = $this->ci->Survey_resource_model->generate_download_link($resources);

        if ($this->display_uses_catalog_resources($renderer_key, isset($item['key']) ? (string) $item['key'] : '')) {
            $value = $resources;
        }

        if (!$value) {
            return false;
        }

        $item = display_renderer_prepare_item($item, $record);

        $view_data = array(
                'resources' => $resources,
                'data' => $value,
                'template' => $item,
            );
        if ($renderer_key === 'field_object_additional' && isset($this->template['items']) && is_array($this->template['items'])) {
            $view_data['layout_items'] = $this->template['items'];
        }

        return $this->ci->load->view(
            'display_templates/fields/' . $view,
            $view_data,
            true
        );
    }

    /**
     * Catalog files live on the study row (`resources`), not in geospatial JSON.
     */
    private function display_uses_catalog_resources($renderer_key, $key)
    {
        if ($renderer_key !== 'field_photo_gallery' && $renderer_key !== 'field_resources') {
            return false;
        }
        if ($key === 'resources' || $key === 'transferOptions.onLine') {
            return true;
        }
        $suffix = 'transferOptions.onLine';
        $len = strlen($suffix);
        return strlen($key) >= $len && substr($key, -$len) === $suffix;
    }

    /** @deprecated Use render_composite() */
    private function render_custom($item, $field_template)
    {
        $key = display_renderer_key_for_legacy_field_template($field_template);
        if ($key === null) {
            log_message('error', 'Display template: unregistered field_template "' . $field_template . '"');
            return false;
        }
        return $this->render_composite($item, $key);
    }

    private function render_array($item)
    {
        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));
        
        if (!$value){
            return false;
        }

        return $this->ci->load->view('display_templates/fields/field_array',array('data'=>$value,'template'=>$item),true);
    }

    private function render_simple_array($item)
    {
        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));
        
        if (!$value){
            return false;
        }

        return $this->ci->load->view('display_templates/fields/field_simple_array',array('data'=>$value,'template'=>$item),true);
    }

    private function render_object($item)
    {
        return $this->render_composite($item, 'field_object_additional');
    }
    
    private function render_text($item)
    {
        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));

        if (!$value){
            return false;
        }

        $this->ci->load->helper('display_date');
        $this->ci->load->helper('display_template');

        $html = display_template_render_scalar_field($value, $item);
        return $html !== false ? $html : false;
    }

    private function render_date($item)
    {
        return $this->render_text($item);
    }


    function get_metadata_dot_key($key)
    {
        return 'metadata.'.str_replace("/",".",$key);
    }


    /**
     * Metadata for the template chosen by the last get_template_project_type() call.
     *
     * @return array<string, mixed>|null
     */
    function get_template_resolution()
    {
        return $this->template_resolution;
    }

    function get_template_project_type($type)
	{
        $this->template_resolution = null;
        $this->ci->load->model('Display_template_model');

        $lookup_types = array($type);
        if ($type === 'timeseries-db') {
            $lookup_types[] = 'timeseriesdb';
        } elseif ($type === 'timeseriesdb') {
            $lookup_types[] = 'timeseries-db';
        }

        $skipped_site_default = null;

        foreach ($lookup_types as $lookup) {
            $default_row = $this->ci->Display_template_model->get_default_template($lookup);
            if (!$default_row || empty($default_row['template_uid'])) {
                continue;
            }
            $stored = $this->ci->Display_template_model->get_template_by_uid($default_row['template_uid']);
            if (!$stored || empty($stored['template_json'])) {
                continue;
            }
            if (isset($stored['status']) && $stored['status'] !== 'published') {
                $skipped = $this->build_template_resolution_meta($stored, 'site_default_not_published', $lookup);
                $skipped['is_site_default'] = true;
                if ($skipped_site_default === null) {
                    $skipped_site_default = $skipped;
                }
                continue;
            }
            $json = $stored['template_json'];
            if (is_string($json)) {
                $json = json_decode($json, true);
            }
            if (is_array($json) && isset($json['items']) && is_array($json['items'])) {
                $this->template_resolution = $this->build_template_resolution_meta($stored, 'database_default', $lookup);
                return $json;
            }
        }

        foreach ($lookup_types as $lookup) {
            $core = $this->ci->Display_template_model->get_default_core_template($lookup);
            if (!$core || empty($core['template_json']) || !is_array($core['template_json'])) {
                continue;
            }
            $json = $core['template_json'];
            if (isset($json['items']) && is_array($json['items'])) {
                $this->template_resolution = $this->build_template_resolution_meta($core, 'system_core_fallback', $lookup);
                if ($skipped_site_default !== null) {
                    $this->template_resolution['skipped_site_default'] = $skipped_site_default;
                }
                return $json;
            }
        }

        throw new Exception("display template not found: ".$type);
	}

    /**
     * @param array<string, mixed> $record Template row from Display_template_model
     * @param string $resolution How the template was selected
     * @param string $lookup_type data_type key used for lookup
     * @return array<string, mixed>
     */
    private function build_template_resolution_meta($record, $resolution, $lookup_type)
    {
        $is_core = !empty($record['is_core']) || (isset($record['template_type']) && $record['template_type'] === 'system');
        return array(
            'id' => isset($record['id']) ? (int) $record['id'] : 0,
            'uid' => isset($record['uid']) ? (string) $record['uid'] : '',
            'name' => isset($record['name']) ? (string) $record['name'] : '',
            'lang' => isset($record['lang']) ? (string) $record['lang'] : 'en',
            'data_type' => isset($record['data_type']) ? (string) $record['data_type'] : (string) $lookup_type,
            'template_type' => isset($record['template_type']) ? (string) $record['template_type'] : '',
            'status' => isset($record['status']) ? (string) $record['status'] : '',
            'resolution' => $resolution,
            'is_system_core' => $is_core,
        );
    }

    function get_nested_section_data($section,$field,$data)
    {
        $field=str_replace($section.'.','',$field);
        if (isset($data[$field])){
            return $data[$field];
        }
    }

    private function render_widget($item)
    {
        if ($this->is_pdf_context()) {
            return false;
        }

        $renderer_key = display_template_widget_renderer_key($item);
        if ($renderer_key === null) {
            log_message('error', 'Display template: widget node is missing display_options.renderer');
            return false;
        }

        $record = display_renderer_lookup($renderer_key);
        if (!$record || !display_renderer_is_active($record) || !display_renderer_is_widget($record)) {
            log_message('error', 'Display template: unknown or inactive widget renderer "' . $renderer_key . '"');
            return false;
        }

        $data_type = isset($this->metadata['type']) ? (string) $this->metadata['type'] : '';
        if ($data_type !== '' && !display_renderer_supported_for_data_type($record, $data_type)) {
            log_message(
                'error',
                'Display template: widget renderer "' . $renderer_key . '" does not support data type "' . $data_type . '"'
            );
            return false;
        }

        $view = display_renderer_view_basename($record);
        $template_field_path = APPPATH . 'views/display_templates/fields/' . $view . '.php';
        if (!file_exists($template_field_path)) {
            throw new Exception('template not found: application/views/display_templates/fields/' . $view . '.php');
        }

        $item = display_renderer_prepare_item($item, $record);
        $data_source = isset($record['data_source']) ? (string) $record['data_source'] : '';
        $payload = array(
            'template' => $item,
            'metadata' => $this->metadata,
            'data' => null,
            'widgets' => array(),
            'doi' => '',
        );

        if ($data_source === 'iframe_embeds') {
            $widgets = array_data_get($this->metadata, 'metadata.iframe_embeds');
            if (!is_array($widgets) || count($widgets) === 0) {
                return false;
            }
            $payload['widgets'] = $widgets;
            $payload['data'] = $widgets;
        } elseif ($data_source === 'doi') {
            $data_key = display_template_widget_data_key($item);
            $doi = display_template_resolve_doi($this->metadata, $data_key);
            if ($doi === '') {
                return false;
            }
            $payload['doi'] = $doi;
            if ($data_key !== '') {
                $payload['data'] = array_data_get($this->metadata, $this->get_metadata_dot_key($data_key));
            }
        } else {
            $data_key = display_template_widget_data_key($item);
            if ($data_key === '' && isset($item['key'])) {
                $data_key = (string) $item['key'];
            }
            $value = $data_key !== ''
                ? array_data_get($this->metadata, $this->get_metadata_dot_key($data_key))
                : null;
            if (!$value) {
                return false;
            }
            $payload['data'] = $value;
        }

        return $this->ci->load->view('display_templates/fields/' . $view, $payload, true);
    }

    function get_metadata($key)
    {
        return array_data_get($this->metadata, $this->get_metadata_dot_key($key));
    }
    
}