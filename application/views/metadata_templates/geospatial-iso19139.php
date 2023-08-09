<?php
/*
 * GEOSPATIAL metadata template
 *
 * @metadata - array containing all metadata
 *
 *
 **/
?>

<?php
 
 $resources=get_field_value('metadata.description.distributionInfo.transferOptions.onLine',$metadata);

if($resources){
    foreach($resources as $idx => $resource){
        if (!isset($resource['filename'])){
            unset($resources[$idx]);
            continue;
        }
        /*if($this->form_validation->valid_url($resource['filename'])){
            $resources[$idx]['download_link']=$resource['filename'];
            $resources[$idx]['extension']=pathinfo($resource['filename'],PATHINFO_EXTENSION);
        }else{
            $resources[$idx]['download_link']=site_url("catalog/{$metadata['id']}/download/{$resource['resource_id']}/".rawurlencode($resource['filename']) );
            $resources[$idx]['extension']=pathinfo($resource['filename'],PATHINFO_EXTENSION);
        }*/  
        $resources[$idx]['download_link']=$resource['filename'];
        $resources[$idx]['extension']=pathinfo($resource['filename'],PATHINFO_EXTENSION);
    }
$metadata['metadata']['description']['distributionInfo']['transferOptions']['onLine']=$resources;
} 
?>


<!--<h1>Geospatial metadata</h1>-->



<!-- identification section -->
<?php 
$identification_info=current((array)get_field_value('metadata.description.identificationInfo',$metadata));

$identification_info=get_field_value('metadata.description.identificationInfo',$metadata);

if (isset($identification[0])){
    $identification_info=$identification_info[0];
}


$identification=array(
    render_field('text','citation.title', get_field_value('citation.title',$identification_info)),
    render_field('text','citation.alternateTitle', get_field_value('citation.alternateTitle',$identification_info)),
    render_field('text','citation.identifier', get_field_value('citation.identifier',$identification_info)),    
    
    render_columns_array('',
        $fields=array(            
            render_field('text','metadata.description.hierarchyLevel', get_field_value('metadata.description.hierarchyLevel',$metadata)),
            render_field('text','citation.edition', get_field_value('citation.edition',$identification_info)),
            render_field('text','citation.editionDate', get_field_value('citation.editionDate',$identification_info)),
            render_field('text','status', get_field_value('status',$identification_info)),
        )
    ),

    render_columns_array('',
        $fields=array(            
            render_field('text','language', get_field_value('language',$identification_info)),
            render_field('text','characterSet', get_field_value('characterSet',$identification_info)),
        )
    ),

    render_field('array','citation.date', get_field_value('citation.date',$identification_info)),

    //graphic Overview
    render_field('photo_gallery','graphicOverview',get_field_value('metadata.description.distributionInfo.transferOptions.onLine',$metadata)),

    //visualizations
    render_field('iframe_embed','metadata.iframe_embeds',get_field_value("metadata.iframe_embeds",$metadata))
);


$ident_fields=array(
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
    "pointOfContact"=>'geog_contact',
    "resourceMaintenance"=>'array',
   //"graphicOverview"=>'array',
    "resourceFormats"=>'array',
    "descriptiveKeywords"=>'array',
    "spatialRepresentationType"=>"text",
    "spatialResolution"=>'object',
    "topicCategory"=>"text",
);

foreach($ident_fields as $field_path=>$field_type){
    $identification[]=render_field($field_type,$field_path, get_field_value($field_path,$identification_info));
}

$output["identificationInfo"]=render_group_text ("identificationInfo",implode("",$identification));
/*
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

        "pointOfContact"=>'geog_contact',
        "resourceMaintenance"=>'array',
        "graphicOverview"=>'array',
        "resourceFormats"=>'array',
        "descriptiveKeywords"=>'array',
        "spatialRepresentationType"=>"text",
        "language"=>"text",
        "characterSet"=>"text",
        "topicCategory"=>"text",
    ),
    $identification_info);
*/
    ?>


<!-- spatial extent -->
<?php 

$geographic_element=(array)get_field_value('extent.geographicElement',$identification_info);
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

$geographic=array(
    render_field('bounding_box','bbox', get_field_value('bbox',$bbox)),
    render_field('array','metadata.description.referenceSystemInfo', get_field_value('metadata.description.referenceSystemInfo',$metadata)),    
);

$output["spatial_extent"]=render_group_text ("spatial_extent",implode("",$geographic));
/*
$output['spatial_extent']= render_group('spatial_extent',
    $fields=array(
            "bbox"=>'array',
            "bbox"=>'bounding_box',
            ),
    $bbox);    
*/    
?>


<!-- resource constraints section -->
<?php 
$output['constraints']= render_group('constraints',
    $fields=array(
            "legalConstraints.accessConstraints"=>'array',
            "legalConstraints.useConstraints"=>'array',
            "legalConstraints.useLimitation"=>'array',
            ),
        current( (array)get_field_value('resourceConstraints',$identification_info)));
?>


<!-- distributionInfo section -->
<?php /* 
$output['distributionInfo']= render_group('distributionInfo',
    $fields=array(
        "distributionFormat"=>'array',
        "distributor"=>"geog_contact",
        "transferOptions.onLine"=>"resources",        
        ),
        get_field_value('metadata.description.distributionInfo',$metadata));
*/?>

<?php
$distributionInfo=array(
    render_field('array','distributionFormat', get_field_value('metadata.description.distributionInfo.distributionFormat',$metadata)),
    render_field('geog_contact','distributor', get_field_value('metadata.description.distributionInfo.distributor',$metadata)),
    render_field('resources','transferOptions.onLine', get_field_value('metadata.description.distributionInfo.transferOptions.onLine',$metadata), array('exclude'=>'pic')),
);

$output["distributionInfo"]=render_group_text ("distributionInfo",implode("",$distributionInfo));
?>

<?php  /*
$output['transferOptions']= render_group('transferOptions',
    $fields=array(
            "onLine"=>"resources"
            ),
        get_field_value('metadata.description.distributionInfo.transferOptions',$metadata)); 
*/
?>


<?php  /*
$output['Visualzations']= render_group('Visualizations',
    $fields=array(
            "metadata.iframe_embeds"=>'iframe_embed'
            ),
        $metadata); 
*/ ?>



<?php 
$output['dataQualityInfo']= render_group('dataQualityInfo',
    $fields=array(
            "scope"=>'text',
            "lineage.statement"=>"text",
            "lineage.processStep"=>"geo_lineage"
            ),
        current((array)get_field_value('metadata.description.dataQualityInfo',$metadata)));
?>



<!-- metadata -->
<?php $output['feature_catalogue']= render_group('feature_catalogue',
    $fields=array(
        "metadata.description.feature_catalogue"=>'feature_catalog'
    ),
    $metadata);
?>


<!-- metadata -->
<?php $output['metadata']= render_group('metadata',
    $fields=array(
        "metadata.description.fileIdentifier"=>'text',
        "metadata.description.metadataStandardName"=>'text',
        "metadata.description.dateStamp"=>'text',
        "metadata.description.language"=>'text',
        "metadata.description.characterset"=>'text',
        "metadata.description.contact"=>'geog_contact'        
    ),
    $metadata);
?>

<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>