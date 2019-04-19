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
//convert file links into hyperlnks
if(isset($metadata['metadata']['table_description']['file'])){
    foreach($metadata['metadata']['table_description']['file']  as $idx=>$value){
        if(isset($metadata['metadata']['table_description']['file'][$idx]['filename'])){
            $metadata['metadata']['table_description']['file'][$idx]['filename']='<a href="'.site_url('filestore/file/'.$value['filename']).'">'.$value['filename'].'</a>';
        }
    }
}
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
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>