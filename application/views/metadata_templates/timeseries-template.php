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
            "titl"=>'text',
            "abbreviation"=>'text',
            "metadata.database_description.title"=>"text",
            "metadata.database_description.abbreviation"=>"text",
            "metadata.database_description.authoring_entity"=>"array",
            "metadata.database_description.abstract"=>"text",
            "metadata.database_description.url"=>"text",
            "metadata.database_description.type"=>"text",

            "metadata.database_description.doi"=>"text",
            "metadata.database_description.date_created"=>"text",
            "metadata.database_description.revision_dates"=>"array",
            "metadata.database_description.date_published"=>"text",
            "metadata.database_description.update_frequency"=>"text",
            "metadata.database_description.update_schedule"=>"array",
            "metadata.database_description.time_coverage"=>"array",
            "metadata.database_description.periodicity"=>"array",
            "metadata.database_description.themes"=>"array",
            "metadata.database_description.topics"=>"array",
            "metadata.database_description.keywords"=>"array",

            "metadata.database_description.geographic_coverage_note"=>"text",
            "metadata.database_description.bbox"=>"bounding_box",
            "metadata.database_description.geographic_granularity"=>"text",
            "metadata.database_description.geographic_area_count"=>"text",
            "metadata.database_description.sponsors"=>"array",
            "metadata.database_description.acknowledgements"=>"text",
            "metadata.database_description.contacts"=>"array",
            "metadata.database_description.languages"=>"array",
            "metadata.database_description.access_options"=>"array",
            "metadata.database_description.license"=>"array",
            "metadata.database_description.citation"=>"text",
            "metadata.database_description.notes"=>"text",
            "metadata.database_description.disclaimer"=>"text",
            "metadata.database_description.copyright"=>"text",

    ),
    $metadata);
?>


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "metadata.databased_description.version"=>'text',
            "metadata.databased_description.date"=>'text',
            "metadata.databased_description.responsibility"=>'text',
            "metadata.databased_description.notes"=>'text'
            ),
    $metadata);
?>



<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "metadata.database_description.contact"=>'array'
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

<!-- sidebar with section links -->
<div class="col-sm-2 col-lg-2 d-none d-sm-block">
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