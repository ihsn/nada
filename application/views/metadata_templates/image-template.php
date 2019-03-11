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
        "metadata.image_description.mediafragment.uri"=>"text",
        "metadata.image_description.mediafragment.delimitertype"=>"text",
        "metadata.image_description.mediafragment.description"=>"text",
        
        //"metadata.image_description.photoVideoMetadataIPTC"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.aboutCvTerms"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.aboutCvTerms.cvId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.aboutCvTerms.cvTermName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.aboutCvTerms.cvTermId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.aboutCvTerms.cvTermRefinedAbout"=>"text",
        
        "metadata.image_description.photoVideoMetadataIPTC.additionalModelInfo"=>"text",
        
        //"metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.circaDateCreated"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.contentDescription"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.contributionDescription"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.copyrightNotice"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.creatorIdentifiers"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.creatorNames"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.currentCopyrightOwnerIdentifier"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.currentCopyrightOwnerName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.currentLicensorIdentifier"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.currentLicensorName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.dateCreated"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.physicalDescription"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.source"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.sourceInventoryNr"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.sourceInventoryUrl"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.stylePeriod"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.artworkOrObjects.title"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.captionWriter"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.cityName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.copyrightNotice"=>"text",

        //"metadata.image_description.photoVideoMetadataIPTC.copyrightOwners"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.copyrightOwners.name"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.copyrightOwners.role"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.copyrightOwners.identifiers"=>"array",

        "metadata.image_description.photoVideoMetadataIPTC.countryCode"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.countryName"=>"text",
        
        //"metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.country"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.emailwork"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.region"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.phonework"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.weburlwork"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.address"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.city"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.creatorContactInfo.postalCode"=>"text",

        "metadata.image_description.photoVideoMetadataIPTC.creatorNames"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.creditLine"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.dateCreated"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.description"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.digitalImageGuid"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.digitalSourceType"=>"text",
        
        "metadata.image_description.photoVideoMetadataIPTC.embdEncRightsExpr"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.embdEncRightsExpr.encRightsExpr"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.embdEncRightsExpr.rightsExprEncType"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.embdEncRightsExpr.rightsExprLangId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.eventName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.genres"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.genres.cvId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.genres.cvTermName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.genres.cvTermId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.genres.cvTermRefinedAbout"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.headline"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.imageRating"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.imageSupplierImageId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.instructions"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.intellectualGenre"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.jobid"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.jobtitle"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.keywords"=>"array",
        
        "metadata.image_description.photoVideoMetadataIPTC.linkedEncRightsExpr"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.linkedEncRightsExpr.linkedRightsExpr"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.linkedEncRightsExpr.rightsExprEncType"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.linkedEncRightsExpr.rightsExprLangId"=>"text",

        "metadata.image_description.photoVideoMetadataIPTC.locationsShown"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.city"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.countryCode"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.countryName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.gpsAltitude"=>"number",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.gpsLatitude"=>"number",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.gpsLongitude"=>"number",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.identifiers"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.name"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.provinceState"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.sublocation"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.locationsShown.worldRegion"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.maxAvailHeight"=>"number",
        "metadata.image_description.photoVideoMetadataIPTC.maxAvailWidth"=>"number",
        "metadata.image_description.photoVideoMetadataIPTC.minorModelAgeDisclosure"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.modelAges"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.modelReleaseDocuments"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.modelReleaseStatus"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.modelReleaseStatus.cvId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.modelReleaseStatus.cvTermName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.modelReleaseStatus.cvTermId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.modelReleaseStatus.cvTermRefinedAbout"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.organisationInImageCodes"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.organisationInImageNames"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.personInImageNames"=>"array",
        
        "metadata.image_description.photoVideoMetadataIPTC.personsShown"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.name"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.description"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.identifiers"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.characteristics"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.characteristics.cvId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.characteristics.cvTermName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.characteristics.cvTermId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.personsShown.characteristics.cvTermRefinedAbout"=>"text",
        
        "metadata.image_description.photoVideoMetadataIPTC.productsShown"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.productsShown.description"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.productsShown.gtin"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.productsShown.name"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.propertyReleaseDocuments"=>"array",
        
        "metadata.image_description.photoVideoMetadataIPTC.propertyReleaseStatus"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.propertyReleaseStatus.cvId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.propertyReleaseStatus.cvTermName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.propertyReleaseStatus.cvTermId"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.propertyReleaseStatus.cvTermRefinedAbout"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.provinceStatePhoto"=>"text",
        
        "metadata.image_description.photoVideoMetadataIPTC.registryEntries"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.registryEntries.role"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.registryEntries.assetIdentifier"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.registryEntries.registryIdentifier"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.sceneCodes"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.source"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.subjectCodes"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.sublocationName"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.supplier"=>"object",
        "metadata.image_description.photoVideoMetadataIPTC.supplier.name"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.supplier.identifiers"=>"array",
        "metadata.image_description.photoVideoMetadataIPTC.title"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.usageTerms"=>"text",
        "metadata.image_description.photoVideoMetadataIPTC.webstatementRights"=>"text"
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
