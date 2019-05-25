<?php
/*
 * Visualization data type template
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

<?php
    //render location field
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



<!-- description section -->
<?php $output['description']= render_group('description',
    $fields=array(
        "metadata.metadata_information.idno"=>"text",
        "metadata.metadata_information.producers"=>"array",
        "metadata.metadata_information.production_date"=>"text",
        "metadata.metadata_information.version"=>"text",
                
        "metadata.visualization_description.title_statement.idno"=>"text",
        "metadata.visualization_description.title_statement.visualization_number"=>"text",
        "metadata.visualization_description.title_statement.title"=>"text",
        "metadata.visualization_description.title_statement.sub_title"=>"text",
        "metadata.visualization_description.title_statement.alternate_title"=>"text",
        "metadata.visualization_description.title_statement.abbreviated_title"=>"text",
        "metadata.visualization_description.title_statement.legend"=>"text",
        "metadata.visualization_description.sub_chart_titles"=>"array",
        "metadata.visualization_description.sub_chart_titles.title"=>"text",
        "metadata.visualization_description.chart_footnotes"=>"array",
        "metadata.visualization_description.chart_footnotes.number"=>"text",
        "metadata.visualization_description.chart_footnotes.text"=>"text",
        
        "metadata.visualization_description.id_numbers"=>"array",
        //"metadata.visualization_description.id_numbers.type"=>"text",
        //"metadata.visualization_description.id_numbers.value"=>"text",
        "metadata.visualization_description.visualization_types"=>"array_comma",
        "metadata.visualization_description.visualization_types.type"=>"text",
        "metadata.visualization_description.visualization_types.note"=>"text",
        
        "metadata.visualization_description.animated"=>"text",
        "metadata.visualization_description.description"=>"text",
        "metadata.visualization_description.narrative"=>"array",
        "metadata.visualization_description.authoring_entity"=>"array",
        "metadata.visualization_description.contributors"=>"array",
        "metadata.visualization_description.publisher"=>"array",
        
        "metadata.visualization_description.acknowledgements"=>"array",
        //"metadata.visualization_description.acknowledgements.name"=>"text",
        //"metadata.visualization_description.acknowledgements.role"=>"text",

        "metadata.visualization_description.version"=>"text",
        "metadata.visualization_description.visualization_series"=>"array",
        "metadata.visualization_description.visualization_series.name"=>"text",
        "metadata.visualization_description.visualization_series.maintainer"=>"text",
        "metadata.visualization_description.visualization_series.uri"=>"text",
        "metadata.visualization_description.visualization_series.description"=>"text",
        "metadata.visualization_description.data_sources"=>"array",
        //"metadata.visualization_description.data_sources.source"=>"text",
        "metadata.visualization_description.time_periods"=>"array",
        "metadata.visualization_description.time_periods.from"=>"text",
        "metadata.visualization_description.time_periods.to"=>"text",
        "metadata.visualization_description.universe"=>"array",
        "metadata.visualization_description.universe.value"=>"text",
        "metadata.visualization_description.ref_country"=>"array",
        "metadata.visualization_description.ref_country.name"=>"text",
        "metadata.visualization_description.ref_country.code"=>"text",
        "metadata.visualization_description.geographic_units"=>"array",
        "metadata.visualization_description.geographic_units.name"=>"text",
        "metadata.visualization_description.geographic_units.code"=>"text",
        "metadata.visualization_description.geographic_units.type"=>"text",
        "metadata.visualization_description.geographic_granularity"=>"text",
        "metadata.visualization_description.languages"=>"array",
        "metadata.visualization_description.languages.name"=>"text",
        "metadata.visualization_description.languages.code"=>"text",
        "metadata.visualization_description.data_accessibility"=>"text",
        "metadata.visualization_description.data"=>"array",
        "metadata.visualization_description.data.dataset"=>"text",
        "metadata.visualization_description.data.access_type"=>"text",
        "metadata.visualization_description.data.uri"=>"text",
        "metadata.visualization_description.data.note"=>"text",
        "metadata.visualization_description.scripts"=>"array",
        "metadata.visualization_description.scripts.software_name"=>"text",
        "metadata.visualization_description.scripts.software_version"=>"text",
        "metadata.visualization_description.scripts.software_library"=>"text",
        "metadata.visualization_description.scripts.script_access"=>"text",
        "metadata.visualization_description.scripts.script_uri"=>"text",
        "metadata.visualization_description.embed_uri"=>"text",
        "metadata.visualization_description.publications"=>"array",
        "metadata.visualization_description.publications.title"=>"text",
        "metadata.visualization_description.publications.uri"=>"text",
        "metadata.visualization_description.keywords"=>"",
        "metadata.visualization_description.keywords.name"=>"text",
        "metadata.visualization_description.keywords.vocabulary"=>"text",
        "metadata.visualization_description.keywords.uri"=>"text",
        "metadata.visualization_description.themes"=>"",
        "metadata.visualization_description.themes.name"=>"text",
        "metadata.visualization_description.themes.vocabulary"=>"text",
        "metadata.visualization_description.themes.uri"=>"text",
        "metadata.visualization_description.topics"=>"array",
        "metadata.visualization_description.topics.id"=>"text",
        "metadata.visualization_description.topics.name"=>"text",
        "metadata.visualization_description.topics.parent_id"=>"text",
        "metadata.visualization_description.topics.vocabulary"=>"text",
        "metadata.visualization_description.topics.uri"=>"text",
        "metadata.visualization_description.disciplines"=>"array",
        "metadata.visualization_description.disciplines.name"=>"text",
        "metadata.visualization_description.disciplines.vocabulary"=>"text",
        "metadata.visualization_description.disciplines.uri"=>"text",
        "metadata.visualization_description.definitions"=>"array",
        "metadata.visualization_description.definitions.name"=>"text",
        "metadata.visualization_description.definitions.definition"=>"text",
        "metadata.visualization_description.definitions.uri"=>"text",
        "metadata.visualization_description.classifications"=>"array",
        "metadata.visualization_description.classifications.name"=>"text",
        "metadata.visualization_description.classifications.version"=>"text",
        "metadata.visualization_description.classifications.organization"=>"text",
        "metadata.visualization_description.classifications.uri"=>"text",
        "metadata.visualization_description.rights"=>"text",
        "metadata.visualization_description.license"=>"array",
        "metadata.visualization_description.license.name"=>"text",
        "metadata.visualization_description.license.uri"=>"text",
        "metadata.visualization_description.citation"=>"text",
        "metadata.visualization_description.disclaimer"=>"text",
        "metadata.visualization_description.contacts"=>"array",
        "metadata.visualization_description.contacts.name"=>"text",
        "metadata.visualization_description.contacts.role"=>"text",
        "metadata.visualization_description.contacts.affiliation"=>"text",
        "metadata.visualization_description.contacts.email"=>"text",
        "metadata.visualization_description.contacts.telephone"=>"text",
        "metadata.visualization_description.contacts.uri"=>"text",
        "metadata.visualization_description.notes"=>"array",
        "metadata.visualization_description.notes.note"=>"text",
        "metadata.visualization_description.links"=>"array",
        "metadata.visualization_description.links.uri"=>"text",
        "metadata.visualization_description.links.description"=>"text",
        "metadata.visualization_description.relations"=>"array",
        "metadata.visualization_description.relations.name"=>"text",
        "metadata.visualization_description.relations.type"=>"text",
        "metadata.visualization_description.tags"=>"array",
        "metadata.visualization_description.tags.tag"=>"text",
        
        "metadata.visualization_description.date_created"=>"text",
        "metadata.visualization_description.date_published"=>"text",
        "metadata.visualization_description.date_modified"=>"text",

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
            "metadata.visualization_description.data_access.dataset_use.disclaimer"=>'text',
            "metadata.visualization_description.production_statement.copyright"=>'text'
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


<!-- metadata_production -->
<?php $output['files']= render_group('files',
    $fields=array(
        "metadata.files"=>'array'
            ),
    $metadata);
?>


<?php 
    //items not to be included in the left side bar
    $exclude_sidebar_items=array('download_links');
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output, 'exclude_sidebar_items'=>$exclude_sidebar_items));
?>    
