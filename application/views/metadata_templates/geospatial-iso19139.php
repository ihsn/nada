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
$identification_info=current((array)get_field_value('metadata.identificationInfo',$metadata));
$output['identificationInfo']= render_group('identificationInfo',
    $fields=array(
        "citation.title"=>'text',
        "citation.alternateTitle"=>'text',
        "citation.date"=>'array',
        "citation.edition"=>'text',
        "citation.editionDate"=>'text',
        "citation.identifier"=>'text',
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


<!-- spatial extent -->
<?php 

$geographic_element=get_field_value('extent.geographicElement',$identification_info);
$bbox=array();
foreach($geographic_element as $element){
    $bbox['bbox'][]=array(
        'place'=>get_field_value('geographicDescription',$element),
        'east'=>get_field_value('geographicBoundingBox.eastBoundLongitude',$element),
        'west'=>get_field_value('geographicBoundingBox.westBoundLongitude',$element),
        'north'=>get_field_value('geographicBoundingBox.northBoundLatitude',$element),
        'south'=>get_field_value('geographicBoundingBox.southBoundLatitude',$element)        
    );
}

$output['spatial_extent']= render_group('spatial_extent',
    $fields=array(
            "bbox"=>'array',
            "bbox"=>'bounding_box',
            ),
    $bbox);
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


<!-- distributionInfo section -->
<?php 
$output['distributionInfo']= render_group('distributionInfo',
    $fields=array(
            "distributionFormat"=>'array',
            "distributor"=>"geog_contact",
            ),
        get_field_value('metadata.distributionInfo',$metadata));
?>

<?php  /*
$output['transferOptions']= render_group('transferOptions',
    $fields=array(
            "onLine"=>"array"
            ),
        get_field_value('metadata..distributionInfo.transferOptions',$metadata));
*/ ?>

<?php 
    $output['distributionInfo']= $output['distributionInfo'] . render_field('resources','resources', $metadata['resources']);
?>


<?php 
$output['dataQualityInfo']= render_group('dataQualityInfo',
    $fields=array(
            "scope"=>'text',
            "lineage.statement"=>"text",
            "lineage.processStep"=>"geo_lineage"
            ),
        current((array)get_field_value('metadata..dataQualityInfo',$metadata)));
?>



<!-- metadata -->
<?php $output['feature_catalogue']= render_group('feature_catalogue',
    $fields=array(
        "metadata.feature_catalogue"=>'feature_catalog'
    ),
    $metadata);
?>


<!-- metadata -->
<?php $output['metadata']= render_group('metadata',
    $fields=array(
        "metadata..fileIdentifier"=>'text',
        "metadata..language"=>'text',
        "metadata..characterset"=>'text',
        "metadata..hierarchyLevel"=>'text',
        "metadata..contact"=>'text',
        "metadata..dateStamp"=>'text',
        "metadata..contact"=>'geog_contact',
        "metadata..metadataStandardName"=>'text',
        "metadata..referenceSystemInfo"=>'array',        
    ),
    $metadata);
?>

<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>