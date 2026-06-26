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
.field-metadata__database_description__geographic_units  .field-value{
    max-height:300px;
    overflow-y:auto;
}


</style>


<?php 
    //rendered html for all sections
    $output=array();
?>


<!-- overview -->
<?php $output['overview']= render_group('overview',
    $fields=array(
        "metadata.database_description.title_statement.title"=>"text",
        "metadata.database_description.title_statement.sub_title"=>"text",
        "metadata.database_description.title_statement.alternate_title"=>"text",
        "metadata.database_description.title_statement.translated_title"=>"text",
        "metadata.database_description.title_statement.idno"=>"text",
        "metadata.database_description.title_statement.identifiers"=>"array",
        "metadata.database_description.abstract"=>"text",
        "metadata.database_description.url"=>"text",
        "metadata.database_description.type"=>"text",
        "metadata.database_description.doi"=>"text",
    ),
    $metadata);
?>

<?php $output['coverage_and_frequency']= render_group('coverage_and_frequency',
    $fields=array(
        "metadata.database_description.update_frequency"=>"text",
        "metadata.database_description.update_schedule"=>"array",
        "metadata.database_description.time_coverage"=>"array",
        "metadata.database_description.time_coverage_note"=>"text",
        "metadata.database_description.periodicity"=>"array",
    ),
    $metadata);
?>

<?php $output['classifications_and_content']= render_group('classifications_and_content',
    $fields=array(
        "metadata.database_description.themes"=>"array",
        "metadata.database_description.topics"=>"array",
        "metadata.database_description.keywords"=>"array",
        "metadata.database_description.indicators"=>"array",
        "metadata.database_description.dimensions"=>"array",
        "metadata.database_description.tabulations"=>"array",
    ),
    $metadata);
?>

<?php $output['geographic_coverage']= render_group('geographic_coverage',
    $fields=array(
        "metadata.database_description.ref_country"=>"array",
        "metadata.database_description.geographic_units"=>"array",
        "metadata.database_description.geographic_coverage_note"=>"text",
        "metadata.database_description.bbox"=>"bounding_box",
        "metadata.database_description.geographic_granularity"=>"text",
        "metadata.database_description.geographic_area_count"=>"text",
    ),
    $metadata);
?>

<?php $output['contributors_and_contacts']= render_group('contributors_and_contacts',
    $fields=array(
        "metadata.database_description.authoring_entity"=>"array",
        "metadata.database_description.sponsors"=>"array",
        "metadata.database_description.acknowledgments"=>"array",
        "metadata.database_description.acknowledgment_statement"=>"text",
        "metadata.database_description.contacts"=>"array",
    ),
    $metadata);
?>

<?php $output['access_links_and_rights']= render_group('access_links_and_rights',
    $fields=array(
        "metadata.database_description.links"=>"array",
        "metadata.database_description.access_options"=>"array",
        "metadata.database_description.license"=>"array",
        "metadata.database_description.citation"=>"text",
        "metadata.database_description.disclaimer"=>"text",
        "metadata.database_description.copyright"=>"text",
    ),
    $metadata);
?>

<?php $output['quality_and_notes']= render_group('quality_and_notes',
    $fields=array(
        "metadata.database_description.errata"=>"array",
        "metadata.database_description.notes"=>"array",
    ),
    $metadata);
?>

<?php $output['versioning_and_metadata_admin']= render_group('versioning_and_metadata_admin',
    $fields=array(
        "metadata.database_description.version"=>"array",
        "metadata.database_description.date_created"=>"text",
        "metadata.database_description.date_published"=>"text",
        "metadata.database_description.languages"=>"array",
        "metadata.published"=>"text",
        "metadata.overwrite"=>"text",
        "metadata.tags"=>"array",
        "metadata.datacite"=>"object",
        "metadata.provenance"=>"array",
        "metadata.additional"=>"object",
        "metadata.metadata_information.title"=>"text",
        "metadata.metadata_information.idno"=>"text",
        "metadata.metadata_information.producers"=>"array",
        "metadata.metadata_information.prod_date"=>"text",
        "metadata.metadata_information.version"=>"text",
    ),
    $metadata);
?>


<?php 
    // Render metadata with section sidebar enabled.
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/2.1.8/linkify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/2.1.8/linkify-jquery.min.js"></script>

<script>
    $(function() {
        $(".study-metadata").linkify();
    });
</script>