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
<?php $output['identification']= render_group('identification',
    $fields=array(
        "metadata.idno"=>"text",                
        "metadata.metadata_creation.title"=>"text",
        "metadata.metadata_creation.idno"=>"text",
        "metadata.metadata_creation.producers"=>"array",
        "metadata.metadata_creation.prod_date"=>"text",
        "metadata.metadata_creation.version"=>"text",
        
        //"metadata.series_description"=>"array",
        "metadata.series_description.idno"=>"text",
        "metadata.series_description.name"=>"text",
        "metadata.series_description.db_idno"=>"text",
        "metadata.series_description.aliases"=>"array",
        "metadata.series_description.aliases.alias"=>"text",
        "metadata.series_description.measurement_unit"=>"text",
        "metadata.series_description.periodicity"=>"text",
        "metadata.series_description.base_period"=>"text",
        "metadata.series_description.definition_short"=>"text",
        "metadata.series_description.definition_long"=>"text",
        "metadata.series_description.definition_references"=>"array",
        "metadata.series_description.definition_references.source"=>"text",
        "metadata.series_description.definition_references.uri"=>"text",
        "metadata.series_description.definition_references.note"=>"text",
        "metadata.series_description.related_concepts"=>"array",
        "metadata.series_description.related_concepts.name"=>"text",
        "metadata.series_description.related_concepts.definition"=>"text",
        "metadata.series_description.methodology"=>"text",
        "metadata.series_description.imputation"=>"text",
        "metadata.series_description.quality_checks"=>"text",
        "metadata.series_description.quality_note"=>"text",
        "metadata.series_description.series_break"=>"text",
        "metadata.series_description.statistical_concept"=>"text",
        "metadata.series_description.limitation"=>"text",
        "metadata.series_description.topics"=>"array",
        "metadata.series_description.topics.topic"=>"text",
        "metadata.series_description.topics.vocabulary"=>"text",
        "metadata.series_description.topics.uri"=>"text",
        "metadata.series_description.relevance"=>"text",
        "metadata.series_description.series_dates"=>"array",
        "metadata.series_description.series_dates.start"=>"text",
        "metadata.series_description.series_dates.end"=>"text",
        "metadata.series_description.geographic_units"=>"array",
        "metadata.series_description.geographic_units.name"=>"text",
        "metadata.series_description.geographic_units.code"=>"text",
        "metadata.series_description.geographic_units.type"=>"text",
        "metadata.series_description.aggregation_method"=>"text",
        "metadata.series_description.ser_access_license"=>"object",
        "metadata.series_description.ser_access_license.type"=>"text",
        "metadata.series_description.ser_access_license.uri"=>"text",
        "metadata.series_description.confidentiality"=>"text",
        "metadata.series_description.confidentiality_status"=>"text",
        "metadata.series_description.confidentiality_note"=>"text",
        "metadata.series_description.series_links"=>"array",
        "metadata.series_description.series_links.type"=>"text",
        "metadata.series_description.series_links.description"=>"text",
        "metadata.series_description.series_links.uri"=>"text",
        "metadata.series_description.api_documentation"=>"object",
        "metadata.series_description.api_documentation.description"=>"text",
        "metadata.series_description.api_documentation.uri"=>"text",
        "metadata.series_description.source"=>"text",
        "metadata.series_description.source_note"=>"text",
        "metadata.series_description.keywords"=>"array",
        "metadata.series_description.keywords.name"=>"text",
        "metadata.series_description.keywords.vocabulary"=>"text",
        "metadata.series_description.keywords.uri"=>"text",
        "metadata.series_description.notes"=>"array",
        "metadata.series_description.notes.note"=>"text",
        "metadata.series_description.related_indicators"=>"array",
        "metadata.series_description.related_indicators.code"=>"text",
        "metadata.series_description.related_indicators.label"=>"text",
        "metadata.series_description.related_indicators.uri"=>"text",
        "metadata.series_description.compliance"=>"array",
        "metadata.series_description.compliance.standard"=>"text",
        "metadata.series_description.compliance.organization"=>"text",
        "metadata.series_description.compliance.uri"=>"text",
        "metadata.series_description.series_groups"=>"array",
        "metadata.series_description.series_groups.name"=>"text",
        "metadata.series_description.series_groups.version"=>"text",
        "metadata.series_description.series_groups.uri"=>"text",
        "metadata.additional"=>"object",

    ),
    $metadata);
?>



<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
            "metadata.metadata_creation.idno"=>'text',
            "metadata.metadata_creation.producers"=>'array',
            "metadata.metadata_creation.prod_date"=>'text',
            "metadata.metadata_creation.version"=>'text'
            ),
    $metadata);
?>

<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>