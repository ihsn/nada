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

<style>
.field-metadata__series_description__geographic_units  .field-value{
    max-height:300px;
    overflow-y:auto;
}
</style>

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
        "metadata.series_description.alternate_identifiers"=>"array",        
        "metadata.series_description.languages"=>"array",
        "metadata.series_description.dimensions"=>"array",
        "metadata.series_description.measurement_unit"=>"text",
        "metadata.series_description.periodicity"=>"text",
        "metadata.series_description.base_period"=>"text",
        "metadata.iframe_embeds"=>'iframe_embed',
        "metadata.series_description.definition_short"=>"text",
        "metadata.series_description.definition_long"=>"text",
        "metadata.series_description.definition_references"=>"array",
        "metadata.series_description.statistical_concept"=>"text",
        "metadata.series_description.concepts"=>"array",
        "metadata.series_description.methodology"=>"text",
        "metadata.series_description.derivation"=>"text",
        "metadata.series_description.imputation"=>"text",
        "metadata.series_description.missing"=>"text",
        "metadata.series_description.quality_checks"=>"text",
        "metadata.series_description.quality_note"=>"text",
        "metadata.series_description.sources_discrepancies"=>"text",        
        "metadata.series_description.series_break"=>"text",
        "metadata.series_description.limitation"=>"text",
        "metadata.series_description.themes"=>"array",        
        "metadata.series_description.topics"=>"array",
        "metadata.series_description.disciplines"=>"array",
        "metadata.series_description.relevance"=>"text",
        "metadata.series_description.time_periods"=>"array",
        "metadata.series_description.aggregation_method"=>"text",
        "metadata.series_description.disaggregation"=>"text",


        "metadata.series_description.authoring_entity"=>"array",

        "metadata.series_description.sources"=>"array",
        "metadata.series_description.sources_note"=>"text",
        "metadata.series_description.keywords"=>"array",
        "metadata.series_description.acronyms"=>"array",
        "metadata.series_description.errata"=>"array",
        "metadata.series_description.notes"=>"array",
        
        "metadata.series_description.related_indicators"=>"array",        
        "metadata.series_description.compliance"=>"array",
        "metadata.series_description.framework"=>"array_vertical",
        "metadata.series_description.series_groups"=>"array",
        "metadata.tags"=>"array",
        "metadata.additional"=>"object"        
    ),
    $metadata);
?>


<?php $output['geographic_units']= render_group('geographic_units',
    $fields=array(
        "metadata.series_description.geographic_units"=>"array",
        "metadata.series_description.bbox"=>"bounding_box",
        "metadata.series_description.ref_country"=>"array"
    ),
    $metadata);
?>


<?php $output['api_documentation']= render_group('api_documentation',
    $fields=array(
        "metadata.series_description.api_documentation"=>"array"
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



<?php $output['license_rights']= render_group('license_rights',
    $fields=array(
        "metadata.series_description.license"=>"array",
        "metadata.series_description.confidentiality"=>"text",
        "metadata.series_description.confidentiality_status"=>"text",
        "metadata.series_description.confidentiality_note"=>"text",
    ),
    $metadata);
?>

<?php $output['lda_topics']= render_group('lda_topics',
        $fields=array(  
        "metadata.lda_topics"=>"lda_topics",
        "metadata.lda_topics.model_info"=>"array",
        "metadata.lda_topics.topic_description"=>"array",
        "metadata.lda_topics.topic_description.topic_words.word"=>"text",
        "metadata.embeddings"=>"array",
        "metadata.additional"=>"dump",
    ),
    $metadata);
?>
        



<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.series_description.title"=>"text",
        "metadata.series_description.idno"=>"text",
        "metadata.series_description.producers"=>"array",
        "metadata.series_description.prod_date"=>"text",
        "metadata.series_description.version"=>"text",
    ),
    $metadata);
?>


<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>