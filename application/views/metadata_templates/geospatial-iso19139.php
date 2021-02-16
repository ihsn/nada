<?php
/*
 * GEOSPATIAL metadata template
 *
 * @metadata - array containing all metadata
 *
 *
 **/
?>

<!--<h1>Geospatial metadata</h1>-->

<!-- identification section -->
<?php $output['overview']= render_group('overview',
    $fields=array(
            "metadata.dataset_description.identification_info.title"=>'text',
            "metadata.dataset_description.identification_info.alternate_title"=>'text',
            "metadata.dataset_description.identification_info.abstract"=>'text',
            "metadata.dataset_description.identification_info.identifier"=>'array',            
            "metadata.dataset_description.identification_info.edition"=>'text',
            "metadata.dataset_description.identification_info.presentation_form"=>'text',
            "metadata.dataset_description.identification_info.purpose"=>'text',
            "metadata.dataset_description.identification_info.credit"=>'text',
            "metadata.dataset_description.identification_info.status"=>'text',            
            "metadata.dataset_description.identification_info.resource_maintenance.maintenance_frequency"=>'text',
            "metadata.dataset_description.identification_info.spatial_representation_type"=>'text',
            "metadata.dataset_description.identification_info.supplemental_information"=>'text',
            ),
    $metadata);
?>



<!-- spatial extent -->
<?php $output['spatial_extent']= render_group('spatial_extent',
    $fields=array(
            "metadata.dataset_description.identification_info.extent.geographic_bounding_box"=>'array',
            "metadata.dataset_description.identification_info.extent.geographic_bounding_box"=>'bounding_box',
            ),
    $metadata);
?>

<!-- spatial extent -->
<?php $output['reference_system_info']= render_columns('reference_system_info',
    $fields=array(
            "metadata.dataset_description.reference_system_info.code"=>'text',
            "metadata.dataset_description.reference_system_info.code_space"=>'text',
            ),
    $metadata);
?>


<!-- spatial extent -->
<?php $output['keywords']= render_group('keywords',
    $fields=array(
        "metadata.dataset_description.identification_info.keywords"=>'array',
        "metadata.dataset_description.identification_info.topics"=>'array',
            ),
    $metadata);
?>

<?php $output['contact']= render_group('contact',
    $fields=array(
            "metadata.dataset_description.contact"=>'array',
            "metadata.dataset_description.identification_info.point_of_contact"=>'array',
            ),
    $metadata);
?>




<!-- graphic_overview -->
<?php $output['graphic_overview']= render_group('graphic_overview',
    $fields=array(
            "metadata.dataset_description.identification_info.graphic_overview"=>'array',
            ),
    $metadata);
?>


<!-- constraints -->
<?php $output['resource_constraints']= render_group('resource_constraints',
    $fields=array(
            "metadata.dataset_description.identification_info.resource_constraints.access_constraints"=>'array',
            "metadata.dataset_description.identification_info.resource_constraints.use_constraints"=>'array',
            "metadata.dataset_description.identification_info.resource_constraints.other_constraints"=>'text',
            "metadata.dataset_description.identification_info.resource_constraints.use_limitations"=>'text',
            ),
    $metadata);
?>




<!-- distribution -->
<?php $output['distribution']= render_group('distribution',
    $fields=array(
        "metadata.dataset_description.distribution_info.distributors"=>'array',
        "metadata.dataset_description.distribution_info.online_resource"=>'array',
        "metadata.dataset_description.identification_info.date"=>'array'
    ),
    $metadata);
?>


<!-- metadata -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.dataset_description.language"=>'text',
        "metadata.dataset_description.charset_code"=>'text',
        "metadata.dataset_description.time_stamp"=>'text',
        "metadata.metadata_maintenance.update_frequency"=>'text',
        "metadata.metadata_maintenance.note"=>'text',
        "metadata.metadata_maintenance.contact"=>'text',
        "metadata.metadata_maintenance.version"=>'text',        
    ),
    $metadata);
?>


<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>