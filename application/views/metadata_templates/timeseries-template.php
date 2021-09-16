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
<?php $output['overview']= render_group('overview',
    $fields=array(
        "metadata.series_description.idno"=>"text",
        "metadata.series_description.name"=>"text",
        "metadata.series_description.database_id"=>"text",
        "metadata.series_description.aliases"=>"array",        
        "metadata.series_description.measurement_unit"=>"text",
        "metadata.series_description.periodicity"=>"text",
        "metadata.series_description.base_period"=>"text",
        "metadata.iframe_embeds"=>'iframe_embed',        
        "metadata.series_description.definition_short"=>"text",
        "metadata.series_description.definition_long"=>"text",
        "metadata.series_description.definition_references"=>"array",
        "metadata.series_description.concepts"=>"array",
        "metadata.series_description.concepts.name"=>"text",
        "metadata.series_description.concepts.definition"=>"text",
        "metadata.series_description.concepts.uri"=>"text",
        "metadata.series_description.methodology"=>"text",
        "metadata.series_description.imputation"=>"text",
        "metadata.series_description.quality_checks"=>"text",
        "metadata.series_description.quality_note"=>"text",
        "metadata.series_description.series_break"=>"text",
        "metadata.series_description.limitation"=>"text",
        "metadata.series_description.themes"=>"array",
        "metadata.series_description.themes.name"=>"text",
        "metadata.series_description.themes.vocabulary"=>"text",
        "metadata.series_description.themes.uri"=>"text",
        "metadata.series_description.topics"=>"array",
        "metadata.series_description.topics.id"=>"text",
        "metadata.series_description.topics.name"=>"text",
        "metadata.series_description.topics.parent_id"=>"text",
        "metadata.series_description.topics.vocabulary"=>"text",
        "metadata.series_description.topics.uri"=>"text",
        "metadata.series_description.disciplines"=>"array",
        "metadata.series_description.disciplines.name"=>"text",
        "metadata.series_description.disciplines.vocabulary"=>"text",
        "metadata.series_description.disciplines.uri"=>"text",
        "metadata.series_description.relevance"=>"text",
        "metadata.series_description.time_periods"=>"array",
        "metadata.series_description.time_periods.start"=>"text",
        "metadata.series_description.time_periods.end"=>"text",

        "metadata.series_description.aggregation_method"=>"text",
        "metadata.series_description.sources"=>"array",
        "metadata.series_description.source_notes"=>"text",
        "metadata.series_description.keywords"=>"array",
        "metadata.series_description.notes"=>"array",
        "metadata.series_description.related_indicators"=>"array",
        "metadata.series_description.related_indicators.code"=>"text",
        "metadata.series_description.related_indicators.label"=>"text",
        "metadata.series_description.related_indicators.uri"=>"text",
        "metadata.series_description.compliance"=>"array",
        "metadata.series_description.series_groups"=>"array",
        "metadata.additional"=>"object"        
    ),
    $metadata);
?>


<?php $output['api_documentation']= render_group('api_documentation',
    $fields=array(
        "metadata.series_description.api_documentation.description"=>"text",
        "metadata.series_description.api_documentation.url"=>"text",
    ),
    $metadata);
?>


<?php $output['links']= render_group('links',
    $fields=array(
        "metadata.series_description.series_links"=>"array",
        "metadata.series_description.links"=>"array"
    ),
    $metadata);
?>


<?php $output['geographic_units']= render_group('geographic_units',
    $fields=array(
        "metadata.series_description.geographic_units"=>"array",
        "metadata.series_description.geographic_units.name"=>"text",
        "metadata.series_description.geographic_units.code"=>"text",
        "metadata.series_description.geographic_units.type"=>"text",
    ),
    $metadata);
?>


<?php $output['license_rights']= render_group('license_rights',
    $fields=array(
        "metadata.series_description.license.name"=>"text",
        "metadata.series_description.license.uri"=>"text",
        "metadata.series_description.confidentiality"=>"text",
        "metadata.series_description.confidentiality_status"=>"text",
        "metadata.series_description.confidentiality_note"=>"text",
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
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>