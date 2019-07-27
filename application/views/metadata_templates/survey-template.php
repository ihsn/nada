<?php
/*
 * DDI template - display metadata for DDI fields
 *
 * @metadata - array containing all metadata
 *
 * @id - survey id
 * @surveyid - IDNO
 * @ all survey table fields
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
            "idno"=>'text',
            "title"=>'text',
            "metadata.study_desc.title_statement.sub_title"=>'text',
            "metadata.study_desc.title_statement.alternate_title"=>'text',
            "metadata.study_desc.title_statement.translated_title"=>'text',
            "metadata.study_desc.study_info.nation"=>'nation',
            "metadata.study_desc.study_info.nation"=>'array',
            "metadata.study_desc.geog_units"=>'array',
            "metadata.study_desc.series_name"=>'text',
            "metadata.study_desc.series_info"=>'text',            
            "metadata.study_desc.study_info.abstract"=>'text',
            "metadata.study_desc.study_info.data_kind"=>'text'            
    ),
    $metadata);
?>


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "metadata.study_desc.version_statement.version"=>'text',
            "metadata.study_desc.version_statement.version_date"=>'text',
            "metadata.study_desc.version_statement.version_notes"=>'text'
            ),
    $metadata);
?>


<!-- scope -->
<?php $output['scope']= render_group('scope',
    $fields=array(
            "metadata.study_desc.study_notes"=>'text',
            "metadata.study_desc.study_info.topics"=>'array',
            "metadata.study_desc.study_info.keywords"=>'array'
            ),
    $metadata);
?>


<!-- coverage -->
<?php $output['coverage']= render_group('coverage',
    $fields=array(
            "metadata.study_desc.study_info.geog_coverage"=>'text',
            "metadata.study_desc.study_info.geog_coverage_notes"=>'text',
            "metadata.study_desc.study_info.geog_unit"=>'text',
            "metadata.study_desc.study_info.analysis_unit"=>'text',
            "metadata.study_desc.study_info.universe"=>'text'
            ),
    $metadata);
?>


<!-- producers_sponsors -->
<?php $output['producers_sponsors']= render_group('producers_sponsors',
    $fields=array(
            "metadata.study_desc.authoring_entity"=>'array',
            "metadata.study_desc.production_statement.producers"=>'array',
            "metadata.study_desc.production_statement.funding_agencies"=>'array',
            "metadata.study_desc.oth_id"=>'array'
            ),
    $metadata);
?>


<!-- sampling -->
<?php $output['sampling']= render_group('sampling',
    $fields=array(
            "metadata.study_desc.method.data_collection.sampling_procedure"=>'text',
            "metadata.study_desc.method.data_collection.sampling_deviation"=>'text',
            "metadata.study_desc.method.analysis_info.response_rate"=>'text',
            'metadata.study_desc.method.data_collection.weight'=>'text'
            ),
    $metadata);
?>


<!-- data_collection -->
<?php $output['data_collection']= render_group('data_collection',
    $fields=array(
            "metadata.study_desc.study_info.coll_dates"=>'array',
            "metadata.study_desc.method.data_collection.frequency"=>'text',
            "metadata.study_desc.study_info.time_periods"=>'array',
            "metadata.study_desc.method.data_collection.sources.data_source"=>'text',
            "metadata.study_desc.method.data_collection.coll_mode"=>'text',
            "metadata.study_desc.method.data_collection.act_min"=>'text',
            "metadata.study_desc.method.data_collection.research_instrument"=>'text',
            "metadata.study_desc.method.data_collection.data_collectors"=>'array',
            ),
    $metadata);
?>


<!-- data_processing -->
<?php $output['data_processing']= render_group('data_processing',
    $fields=array(
            "metadata.study_desc.method.data_collection.cleaning_operations"=>'text',
            "metadata.study_desc.method.data_collection.method_notes"=>'text'
            ),
    $metadata);
?>


<!-- data_appraisal -->
<?php $output['data_appraisal']= render_group('data_appraisal',
    $fields=array(
        "metadata.study_desc.method.analysis_info.sampling_error_estimates"=>'text',    
        "metadata.study_desc.method.analysis_info.data_appraisal"=>'text'
            ),
    $metadata);
?>


<!-- data_access -->
<?php $output['data_access']= render_group('data_access',
    $fields=array(
            "metadata.study_desc.data_access.dataset_use.contact"=>'array',
            "metadata.study_desc.data_access.dataset_use.conf_dec"=>'array_comma',
            "metadata.study_desc.data_access.dataset_use.conf_dec.form_url"=>'text',
            "metadata.study_desc.data_access.dataset_use.conditions"=>'text',
            "metadata.study_desc.data_access.dataset_use.cit_req"=>'text',
            "metadata.study_desc.data_access.dataset_use.deposit_req">'text',
            "metadata.study_desc.data_access.dataset_availability.access_place"=>'text', 
            "metadata.study_desc.data_access.dataset_availability.original_archive"=>'text', 
            "metadata.study_desc.data_access.dataset_availability.availability_status"=>'text'
            ),
    $metadata);
?>


<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "metadata.study_desc.data_access.dataset_use.disclaimer"=>'text',
            "metadata.study_desc.production_statement.copyright"=>'text'
            ),
    $metadata);
?>


<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "metadata.study_desc.distribution_statement.contact"=>'array'
            ),
    $metadata);
?>

<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
            "metadata.doc_desc.idno"=>'text',
            "metadata.doc_desc.producers"=>'array',
            "metadata.doc_desc.prod_date"=>'text',
            "metadata.doc_desc.version_statement.version"=>'text',
            ),
    $metadata);
?>


<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/2.1.8/linkify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/2.1.8/linkify-jquery.min.js"></script>

<script>
    $(function() {
        $(".metadata-container").linkify();
    });
</script>    