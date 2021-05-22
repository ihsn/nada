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



<?php 
$identification_info=current((array)get_field_value('metadata.dataset_metadata.identificationInfo',$metadata));
$output['IdentificationInfo']= render_group('identificationInfo',
    $fields=array(
        "citation.title"=>'text',
        "citation.alternateTitle"=>'text',
        "citation.date"=>'array',
        "citation.edition"=>'text',
        "citation.editionDate"=>'text',
        "citation.identifier"=>'array',
        "citation.citedResponsibleParty"=>'geog_contact',
        "citation.presentationForm"=>'array_badge',
        "citation.series.name"=>'text',
        "citation.series.issueIdentification"=>'text',
        "citation.otherCitationDetails"=>'text',
        "citation.collectiveTitle"=>'text',
        "citation.ISBN"=>'text',
        "citation.ISSN"=>'text',
        "abstract"=>'text',
        "purpose"=>'text',
        "credit"=>'text',
        "status"=>'text',

        "pointOfContact"=>'geog_contact',
        "resourceMaintenance"=>'array',
        "graphicOverview"=>'array',
        "resourceFormats"=>'array',
        "descriptiveKeywords"=>'array',
        "status"=>'text',
        "spatialRepresentationType"=>"text",
        "language"=>"text",
        "characterSet"=>"text",
        "topicCategory"=>"text",
    ),
    $identification_info);
?>

<!-- resource constraints section -->
<?php 
$output['constraints']= render_group('constraints',
    $fields=array(
            "legalConstraints.accessConstraints"=>'array',
            "legalConstraints.useConstraints"=>'array',
            "legalConstraints.uselimitation"=>'array',
            ),
        current( get_field_value('resourceConstraints',$identification_info)));
?>


<!-- spatial extent -->
<?php 

$geographic_element=get_field_value('extent.geographicElement',$identification_info);
$bbox=array();
foreach($geographic_element as $element){
    $bbox['bbox'][]=array(
        'place'=>get_field_value('geographicDescription',$element),
        'south'=>get_field_value('geographicBoundingBox.southBoundLongitude',$element),
        'west'=>get_field_value('geographicBoundingBox.westBoundLongitude',$element),
        'north'=>get_field_value('geographicBoundingBox.northBoundLongitude',$element),
        'east'=>get_field_value('geographicBoundingBox.eastBoundLongitude',$element)
    );
}

$output['spatial_extent']= render_group('spatial_extent',
    $fields=array(
            "bbox"=>'array',
            "bbox"=>'bounding_box',
            ),
    $bbox);
?>


<!-- distributionInfo section -->
<?php 
$output['distributionInfo']= render_group('distributionInfo',
    $fields=array(
            "distributionFormat"=>'array',
            "distributor"=>"geog_contact",
            ),
        get_field_value('metadata.dataset_metadata.distributionInfo',$metadata));
?>

<?php 
$output['transferOptions']= render_group('transferOptions',
    $fields=array(
            "onLine"=>"array"
            ),
        current((array)get_field_value('metadata.dataset_metadata.distributionInfo.transferOptions',$metadata)));
?>


<?php 
$output['dataQualityInfo']= render_group('dataQualityInfo',
    $fields=array(
            "scope"=>'text',
            "lineage.statement"=>"text",
            "lineage.processStep"=>"geo_lineage"
            ),
        current((array)get_field_value('metadata.dataset_metadata.dataQualityInfo',$metadata)));
?>




<!-- metadata -->
<?php $output['dataset_metadata']= render_group('dataset_metadata',
    $fields=array(
        "metadata.dataset_metadata.fileIdentifier"=>'text',
        "metadata.dataset_metadata.language"=>'text',
        "metadata.dataset_metadata.characterset"=>'text',
        "metadata.dataset_metadata.hierarchyLevel"=>'text',
        "metadata.dataset_metadata.contact"=>'text',
        "metadata.dataset_metadata.dateStamp"=>'text',
        "metadata.dataset_metadata.contact"=>'geog_contact',
        "metadata.dataset_metadata.metadataStandardName"=>'text',
        "metadata.dataset_metadata.referenceSystemInfo"=>'array'
    ),
    $metadata);
?>

<!-- metadata -->
<?php $output['feature_catalogue']= render_group('feature_catalogue',
    $fields=array(
        "metadata.feature_catalogue"=>'feature_catalog'
    ),
    $metadata);
?>


<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>