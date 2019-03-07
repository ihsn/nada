<?php
/*
 * Visualization data type template
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


<?php
/*
//convert file links into hyperlnks
if(isset($metadata['metadata']['visualization_description']['file'])){
    foreach($metadata['metadata']['visualization_description']['file']  as $idx=>$value){
        if(isset($metadata['metadata']['visualization_description']['file'][$idx]['filename'])){
            $metadata['metadata']['visualization_description']['file'][$idx]['filename']='<a href="'.site_url('filestore/file/'.$value['filename']).'">'.$value['filename'].'</a>';
        }
    }
}*/
?>


<!-- description section -->
<?php $output['description']= render_group('description',
    $fields=array(
            "metadata.visualization_description.title_statement.idno"=>'text',
            "metadata.visualization_description.id_numbers"=>'array_comma',
            "metadata.visualization_description.title_statement.title"=>'text',
            "metadata.visualization_description.title_statement.sub_title"=>'text',
            "metadata.visualization_description.title_statement.alternate_title"=>'text',
            "metadata.visualization_description.title_statement.translated_title"=>'text',
                        
            "metadata.visualization_description.description"=>'text',
            "metadata.visualization_description.date_produced"=>'text',
            "metadata.visualization_description.date_released"=>'text',
            "metadata.visualization_description.date_changed"=>'text',

            "metadata.visualization_description.version"=>'text',
            "metadata.visualization_description.ref_country"=>'array',            

            "metadata.visualization_description.table_series"=>'array',
            "metadata.visualization_description.authoring_entity"=>'array',            
            "metadata.visualization_description.contributor"=>'array',
            "metadata.visualization_description.publisher"=>'array',
            
            "metadata.visualization_description.table_columns"=>'array',
            "metadata.visualization_description.table_rows"=>'array',
            "metadata.visualization_description.statistics"=>'text',
            "metadata.visualization_description.unit_observation"=>'array_comma',

            "metadata.visualization_description.universe"=>'text',
            "metadata.visualization_description.data_sources"=>'array',
            "metadata.visualization_description.time_period"=>'array',            

            "metadata.visualization_description.geographic_granularity"=>'text',
            "metadata.visualization_description.geographic_units"=>'array',
            "metadata.visualization_description.languages"=>'array',
            "metadata.visualization_description.links"=>'array',
            "metadata.visualization_description.publications"=>'array',
            "metadata.visualization_description.keywords"=>'array',
            "metadata.visualization_description.themes"=>'array',
            "metadata.visualization_description.topics"=>'array',
            "metadata.visualization_description.rights"=>'text',
            "metadata.visualization_description.license"=>'array',
            "metadata.visualization_description.citation"=>'text',
            "metadata.visualization_description.confidentiality"=>'text',
            "metadata.visualization_description.notes"=>'text',
            "metadata.visualization_description.relations"=>'array',
    ),
    $metadata);
?>


<!-- additional items -->
<?php if (isset($metadata['metadata']['additional'])):?>
<?php   
    $additional_fields=array();
    foreach ($metadata['metadata']['additional'] as $item_key=>$value){
        $additional_fields['metadata.additional.'.$item_key]=is_array($value) ? 'array' : 'text';
    }
    $output['additional']= render_group('additional',$fields=$additional_fields,$metadata);
?>
<?php endif;?>



<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "metadata.visualization_description.data_access.dataset_use.disclaimer"=>'text',
            "metadata.visualization_description.production_statement.copyright"=>'text'
            ),
    $metadata);
?>


<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.metadata_information.producers"=>'array',    
        "metadata.metadata_information.production_date"=>'text',
        "metadata.metadata_information.version"=>'text',
        "metadata.metadata_information.idno"=>'text',
            ),
    $metadata);
?>


<!-- metadata_production -->
<?php $output['files']= render_group('files',
    $fields=array(
        "metadata.files"=>'array'
            ),
    $metadata);
?>



<!-- sidebar with section links -->
<div class="col-sm-2 col-lg-2  d-none d-sm-block">
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
