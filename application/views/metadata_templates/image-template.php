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


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "metadata.image_description.version_statement.version"=>'text',
            "metadata.image_description.version_statement.version_date"=>'text',
            "metadata.image_description.version_statement.version_notes"=>'text'
            ),
    $metadata);
?>


<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "metadata.image_description.distribution_statement.contact"=>'array'
            ),
    $metadata);
?>

<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
            "metadata.metadata_information.production_date"=>'text',
            "metadata.metadata_information.version"=>'text',
            "metadata.metadata_information.idno"=>'text',
            "metadata.metadata_information.idno"=>'text',
            ),
    $metadata);
?>


<!-- metadata_production -->
<?php $output['metadata_dump']= render_field('dump',$field_name='dump',$metadata,true);
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
