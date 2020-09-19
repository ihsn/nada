<?php
/*
 * Table data type template
 *
 * @metadata - array containing all metadata
 *
 *
 *
 **/
?>


<?php 
    //rendered html for all sections
    $output=array();
?>

<?php
if(isset($metadata['resources'])){
    //replace files->file_uri with resource download link 
    foreach($metadata['metadata']['files'] as $file_idx => $file){
        if (array_key_exists($file['file_uri'], $metadata['resources'])){
            $resource=$metadata['resources'][$file['file_uri']];
            $metadata['metadata']['files'][$file_idx]['file_uri']=site_url("catalog/{$resource['survey_id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
        }
    }
}
?>

<?php
    //render files field
    $download_buttons=render_field(
        "download_buttons_array",
        "metadata.files",
        get_field_value('metadata.files',$metadata), 
        $options=array(
            'url_column'=>'file_uri',
            'title_column'=>'note'
        )
    );
?>    

<?php    
    $download_buttons_html=render_field(
        "literal",
        "",
        $download_buttons, 
        $options=array(
            'css_class'=>'float-md-right',
            'css_style'=>''
        )
    );
    $output['download_links']=$download_buttons_html;
?>

<!-- identification section -->
<?php $output['description']= render_group('description',
    $fields=array(            
            "metadata.table_description.title_statement.title"=>'text',
            "metadata.table_description.title_statement.idno"=>'text',
            "metadata.table_description.id_numbers"=>'array',            
            "metadata.table_description.title_statement.sub_title"=>'text',
            "metadata.table_description.title_statement.alternate_title"=>'text',
            "metadata.table_description.title_statement.translated_title"=>'text',

            "metadata.table_description.links"=>'array',
            
            "metadata.table_description.description"=>'text',
            "metadata.table_description.date_produced"=>'text',
            "metadata.table_description.date_released"=>'text',
            "metadata.table_description.date_changed"=>'text',

            "metadata.table_description.version"=>'text',
            "metadata.table_description.data_sources"=>'array',
            "metadata.table_description.time_periods"=>'array',
            "metadata.table_description.table_series"=>'array',
            "metadata.table_description.ref_country"=>'array',            
            
            "metadata.table_description.file"=>'array',

            
            "metadata.table_description.authoring_entity"=>'array',            
            "metadata.table_description.contributor"=>'array',
            "metadata.table_description.publisher"=>'array',

            "metadata.table_description.languages"=>'array',
            "metadata.table_description.geographic_granularity"=>'text',              
            "metadata.table_description.statistics"=>'text',
            "metadata.table_description.unit_observation"=>'array_comma',
            "metadata.table_description.universe"=>'text',

            "metadata.table_description.table_columns"=>'array',
            "metadata.table_description.table_rows"=>'array',

            "metadata.table_description.keywords"=>'array',
            "metadata.table_description.themes"=>'array',
            "metadata.table_description.topics"=>'array',
            "metadata.tags"=>'array_comma',
            
            "metadata.table_description.data_years_range"=>'array',
            "metadata.table_description.data_years_list"=>'array_comma',

            "metadata.table_description.geographic_units"=>'array',
            
            
            "metadata.table_description.publications"=>'array',
            
            "metadata.table_description.rights"=>'text',
            "metadata.table_description.license"=>'array',
            
            "metadata.table_description.confidentiality"=>'text',
            "metadata.table_description.notes"=>'text',
            "metadata.table_description.relations"=>'array',
            "metadata.table_description.citation"=>'text'
    ),
    $metadata);
?>


<!-- additional items -->
<?php if (isset($metadata['metadata']['additional'])):?>
<?php   
    $additional_fields=array();
    foreach ($metadata['metadata']['additional'] as $item_key=>$value){
        $additional_fields['metadata.additional.'.$item_key]=is_array($value) ? 'array' : 'text';
    }
    $output['additional']= render_group('additional',$fields=$additional_fields,$metadata);
?>
<?php endif;?>



<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "metadata.table_description.data_access.dataset_use.disclaimer"=>'text',
            "metadata.table_description.production_statement.copyright"=>'text'
            ),
    $metadata);
?>


<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.metadata_information.producers"=>'array',    
        "metadata.metadata_information.production_date"=>'text',
        "metadata.metadata_information.version"=>'text',
        "metadata.metadata_information.idno"=>'text',
            ),
    $metadata);
?>

<?php 
    //items not to be included in the left side bar
    $exclude_sidebar_items=array('download_links');
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output, 'exclude_sidebar_items'=>$exclude_sidebar_items));
?>    
