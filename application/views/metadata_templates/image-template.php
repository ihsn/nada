<?php
/*
 * Image template 
 *
 * @metadata - array containing all metadata
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
            "title"=>'text',
            "metadata.image_description.description.description"=>'text',
            "metadata.image_description.description.albums"=>'array',
            "idno"=>'text',

    ),
    $metadata);
?>



<!-- metadata_production -->
<?php $output['image_description']= render_group('image_description',
    $fields=array(

        //"metadata.image_description.mediafragment"=>"object",
        "metadata.image_description.iptc.mediafragment.uri"=>"text",
        "metadata.image_description.iptc.mediafragment.delimitertype"=>"text",
        "metadata.image_description.iptc.mediafragment.description"=>"text",
        
        //"metadata.image_description.iptc.photoVideoMetadataIPTC"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.aboutCvTerms"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.aboutCvTerms.cvId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.aboutCvTerms.cvTermName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.aboutCvTerms.cvTermId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.aboutCvTerms.cvTermRefinedAbout"=>"text",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.additionalModelInfo"=>"text",
        
        //"metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.circaDateCreated"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.contentDescription"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.contributionDescription"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.copyrightNotice"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.creatorIdentifiers"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.creatorNames"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.currentCopyrightOwnerIdentifier"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.currentCopyrightOwnerName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.currentLicensorIdentifier"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.currentLicensorName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.dateCreated"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.physicalDescription"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.source"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.sourceInventoryNr"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.sourceInventoryUrl"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.stylePeriod"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.artworkOrObjects.title"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.captionWriter"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.cityName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.copyrightNotice"=>"text",

        //"metadata.image_description.iptc.photoVideoMetadataIPTC.copyrightOwners"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.copyrightOwners.name"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.copyrightOwners.role"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.copyrightOwners.identifiers"=>"array",

        "metadata.image_description.iptc.photoVideoMetadataIPTC.countryCode"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.countryName"=>"text",
        
        //"metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.country"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.emailwork"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.region"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.phonework"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.weburlwork"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.address"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.city"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorContactInfo.postalCode"=>"text",

        "metadata.image_description.iptc.photoVideoMetadataIPTC.creatorNames"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.creditLine"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.dateCreated"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.description"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.digitalImageGuid"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.digitalSourceType"=>"text",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.embdEncRightsExpr"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.embdEncRightsExpr.encRightsExpr"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.embdEncRightsExpr.rightsExprEncType"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.embdEncRightsExpr.rightsExprLangId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.eventName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.genres"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.genres.cvId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.genres.cvTermName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.genres.cvTermId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.genres.cvTermRefinedAbout"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.headline"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.imageRating"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.imageSupplierImageId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.instructions"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.intellectualGenre"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.jobid"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.jobtitle"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.keywords"=>"array",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.linkedEncRightsExpr"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.linkedEncRightsExpr.linkedRightsExpr"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.linkedEncRightsExpr.rightsExprEncType"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.linkedEncRightsExpr.rightsExprLangId"=>"text",

        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.city"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.countryCode"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.countryName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.gpsAltitude"=>"number",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.gpsLatitude"=>"number",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.gpsLongitude"=>"number",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.identifiers"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.name"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.provinceState"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.sublocation"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.locationsShown.worldRegion"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.maxAvailHeight"=>"number",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.maxAvailWidth"=>"number",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.minorModelAgeDisclosure"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelAges"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelReleaseDocuments"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelReleaseStatus"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelReleaseStatus.cvId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelReleaseStatus.cvTermName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelReleaseStatus.cvTermId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.modelReleaseStatus.cvTermRefinedAbout"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.organisationInImageCodes"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.organisationInImageNames"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personInImageNames"=>"array",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.name"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.description"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.identifiers"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.characteristics"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.characteristics.cvId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.characteristics.cvTermName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.characteristics.cvTermId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.personsShown.characteristics.cvTermRefinedAbout"=>"text",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.productsShown"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.productsShown.description"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.productsShown.gtin"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.productsShown.name"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.propertyReleaseDocuments"=>"array",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.propertyReleaseStatus"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.propertyReleaseStatus.cvId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.propertyReleaseStatus.cvTermName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.propertyReleaseStatus.cvTermId"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.propertyReleaseStatus.cvTermRefinedAbout"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.provinceStatePhoto"=>"text",
        
        "metadata.image_description.iptc.photoVideoMetadataIPTC.registryEntries"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.registryEntries.role"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.registryEntries.assetIdentifier"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.registryEntries.registryIdentifier"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.sceneCodes"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.source"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.subjectCodes"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.sublocationName"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.supplier"=>"object",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.supplier.name"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.supplier.identifiers"=>"array",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.title"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.usageTerms"=>"text",
        "metadata.image_description.iptc.photoVideoMetadataIPTC.webstatementRights"=>"text"
            ),
    $metadata);
?>


<?php $output['album']= render_group('album',
    $fields=array(
        "metadata.image_description.album"=>"array"
    ),
    $metadata);
?>        


<?php $output['files']= render_group('files',
    $fields=array(
        "metadata.image_description.files"=>"array",
        "metadata.image_description.files.filename"=>"text",
        "metadata.image_description.files.format"=>"text",
        "metadata.image_description.files.note"=>"text",
    ),
    $metadata);
?>        

        


<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.metadata_information.title"=>"text",
        "metadata.metadata_information.idno"=>"text",
        "metadata.metadata_information.producers"=>"array",
        "metadata.metadata_information.producers.name"=>"text",
        "metadata.metadata_information.producers.abbr"=>"text",
        "metadata.metadata_information.producers.affiliation"=>"text",
        "metadata.metadata_information.producers.role"=>"text",
        "metadata.metadata_information.production_date"=>"text",
        "metadata.metadata_information.version"=>"text"
    ),
    $metadata);
?>        


<!-- dump -->
<?php 
    //$output['metadata_dump']= render_field('dump',$field_name='dump',$metadata,true);
?>


<!-- sidebar with section links -->
<div class="col-sm-2 col-lg-2 hidden-sm-down">
<div class="navbar-collapse sticky-top">

    <ul class="navbar-nav flex-column wb--full-width">
    <?php foreach($output as $key=>$value):?>            
        <?php if(trim($value)!==""):?>    
        <li class="nav-item">
            <a href="<?php echo current_url();?>#metadata-<?php echo $key;?>"><?php echo t($key);?></a>
        </li>
        <?php endif;?>
    <?php endforeach;?>
    </ul>
</div>
</div>
<!--metadata content-->
<div class="col-12 col-sm-10 col-lg-10 wb-border-left">
    <?php echo implode('',$output);?>
</div>
