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
	
	function __construct()
	{
        $this->ci =& get_instance();
        $this->ci->load->helper("array");
        //$this->ci->load->model("Editor_template_model");
    }

    function initialize($metadata,$template)
    {
        $this->metadata=$metadata;
        $this->template=$template;
        $this->load_translations($metadata['type']);
    }

    function load_translations($type)
    {
        // Load the template config file and setup our master template and regions
        include(APPPATH.'config/metadata_template.php');
        
        if (!isset($config)){		
            throw new Exception("config/metadata_template not loaded");
        }
            
        if (!isset($config[$type]) || !isset($config[$type]['template'])){
            throw new Exception("METADATA_VIEW_NOT_DEFINED: ".$type);
        }
        
        //language file
        $lang_file=$config[$type]['language_translations'];
        
        //language e.g. english,french...
        $lang_language=$this->ci->config->item("language");

        $lang_file_path=APPPATH.'/language/'.$lang_language.'/'.$lang_file.'_lang.php';

        //load language file
        if (file_exists($lang_file_path)){
            $this->ci->lang->load($lang_file);
        }
        else{
            log_message('error', "missing language file {$lang_file_path}");
        }
    }
    

    function render_html()
    {
        $this->populate_sidebar($this->template['items']);
        return $this->render_element($this->template['items']);
    }

    function get_sidebar_items()
    {
        return array_intersect($this->sidebar_items,$this->sidebar_items_all);
    }

    function populate_sidebar($items, $parent=null)
    {        
        foreach($items as $item)
        {
            if ($item['type']=='section_container'){
                $this->populate_sidebar($item['items'],$item['key']);
            }

            if ($item['type']=='section'){
                $this->sidebar_items_all[$item['key']]=$item['title'];
            }
        }
    }

    function render_element($items)
    {
        $output=array();

        foreach($items as $idx=>$item){       
            
            if (isset($item['display_options']) && isset($item['display_options']['field_template'])){
                $output[]=$this->render_custom($item,$item['display_options']['field_template']);
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
                        $this->sidebar_items[$item['key']]=$item['title'];
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
                case 'text':
                case 'string':
                case 'boolean':
                case 'integer':
                    $output[]= $this->render_text($item);
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
        $output=array();
        $output[]='<div class="field-section-container pb-3">';
        $output[]='<h2 class="field-section" id="'.$item['key'].'">'.tt(strtolower($item['title']),$item['title']).'</h2>';

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
        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));
        
        if (!$value){
            return false;
        }

        $resources=array_data_get($this->metadata,'resources');
        return $this->ci->load->view('display_templates/fields/field_array_accordion',array('resources'=>$resources,'data'=>$value,'template'=>$item),true);
    }


    private function render_custom($item,$field_template)
    {
        $template_field_path='application/views/display_templates/fields/'.$field_template.'.php';

        if (!file_exists($template_field_path)){
            throw new Exception("template not found: ".$template_field_path);
        }

        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));
        
        if (!$value){
            return false;
        }

        $resources=array_data_get($this->metadata,'resources');
        $resources=$this->ci->Survey_resource_model->generate_download_link($resources);

        return $this->ci->load->view('display_templates/fields/'.$field_template,array('resources'=>$resources, 'data'=>$value,'template'=>$item),true);
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
    
    private function render_text($item)
    {
        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($item['key']));

        if (!$value){
            return false;
        }

        return $this->ci->load->view('display_templates/fields/field_text_markdown',array('data'=>$value,'template'=>$item),true);
    }


    function get_metadata_dot_key($key)
    {
        return 'metadata.'.str_replace("/",".",$key);
    }


    function get_template_project_type($type)
	{
        $template=null;
        $template_folders=array(
            'application/templates/display/custom/',
            'application/templates/display/'
        );

        //load custom template for the project type
        foreach($template_folders as $template_folder){
            $template_file_name=$template_folder.$type.'_display_template.json';
            if (file_exists($template_file_name)){
                $template['template']=json_decode(file_get_contents($template_file_name),true);
                break;
            }
        }

        if ($template && isset($template['template'])){
            return $template['template'];
        }

        throw new Exception("display template not found: ".$template_file_name);
        /*

        //load default template for the project type from db
        $default_template=$this->ci->Editor_template_model->get_default_template($type);

        if (isset($default_template['template_uid'])){
            $template=$this->ci->Editor_template_model->get_template_by_uid($default_template['template_uid']);
        }

        if ($template){
            return $template;
        }

        $template_file_name='application/templates/display/'.$type.'_display_template.json';

		if (!file_exists($template_file_name)){
			throw new Exception("display template not found: ".$template_file_name);
		}

        $template['template']=json_decode(file_get_contents($template_file_name),true);
		return $template;
        */
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
        $widget_options=$item['widget_options'];
        $value=array_data_get($this->metadata, $this->get_metadata_dot_key($widget_options['data_key']));
        return $this->ci->load->view('display_templates/fields/field_'.$widget_options['widget_field'],
            array(
                'widgets'=>array_data_get($this->metadata,'metadata.iframe_embeds'),
                'data'=>$value,
                'metadata'=>$this->metadata,
                'template'=>$item
                )
        ,true);
    }

    function get_metadata($key)
    {
        return array_data_get($this->metadata, $this->get_metadata_dot_key($key));
    }
    
}