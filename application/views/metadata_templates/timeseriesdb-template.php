<?php
/*
 * Timeseries display template
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


<!-- identification section -->
<?php $output['overview']= render_group('',
    $fields=array(
        "metadata.database_description.title_statement.title"=>"text",        
        "metadata.database_description.title_statement.sub_title"=>"text",
        "metadata.database_description.title_statement.alternate_title"=>"text",
        "metadata.database_description.title_statement.translated_title"=>"text",

        "metadata.database_description.title_statement.idno"=>"text",

        "metadata.database_description.authoring_entity"=>"array",
        "metadata.database_description.abstract"=>"text",
        "metadata.database_description.url"=>"text",

        "metadata.database_description.type"=>"text",
        "metadata.database_description.doi"=>"text",
        "metadata.database_description.date_created"=>"text",
        "metadata.database_description.date_published"=>"text",
        "metadata.database_description.version"=>"array",
        "metadata.database_description.update_frequency"=>"text",
        "metadata.database_description.update_schedule"=>"array",

        "metadata.database_description.time_coverage"=>"text",
        "metadata.database_description.time_coverage_note"=>"text",
        "metadata.database_description.periodicity"=>"array",
        "metadata.database_description.themes"=>"array",
        "metadata.database_description.topics"=>"array",
        "metadata.database_description.keywords"=>"array",
        "metadata.database_description.geographic_units"=>"array",

        "metadata.database_description.geographic_coverage_note"=>"text",
        "metadata.database_description.bbox"=>"array",
        "metadata.database_description.geographic_granularity"=>"text",
        "metadata.database_description.geographic_area_count"=>"text",
        "metadata.database_description.sponsors"=>"array",
        "metadata.database_description.acknowledgments"=>"array",
        "metadata.database_description.contacts"=>"array",

        "metadata.database_description.links"=>"array",
        "metadata.database_description.languages"=>"array",
        "metadata.database_description.access_options"=>"array",
        "metadata.database_description.license"=>"array",
        "metadata.database_description.citation"=>"text",

        "metadata.database_description.notes"=>"array",
        "metadata.database_description.disclaimer"=>"text",
        "metadata.database_description.copyright"=>"text",

        "metadata.additional"=>"object",
    ),
    $metadata);
?>



<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.metadata_creation.producers"=>"array",
        "metadata.metadata_creation.prod_date"=>"text",
        "metadata.metadata_creation.version"=>"text",
    ),
    $metadata);
?>


<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output, 'hide_sidebar'=>true));
?>