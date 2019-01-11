<?php
/*
 * Table data type template
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
            "title"=>'text',
            "metadata.table_description.title_statement.sub_title"=>'text',
            "metadata.table_description.title_statement.alternate_title"=>'text',
            "metadata.table_description.title_statement.translated_title"=>'text',
            "idno"=>'text',
            "metadata.table_description.description"=>'text',
            "metadata.table_description.toc"=>'text',
            "metadata.table_description.abstract"=>'text',
            "metadata.table_description.note"=>'text',
            "metadata.table_description.coverage"=>'text',
            "metadata.table_description.spatial_coverage"=>'text',
            "metadata.table_description.temporal_coverage"=>'text',
            
            "metadata.table_description.date_created"=>'text',
            "metadata.table_description.date_available"=>'text',
            "metadata.table_description.date_modified"=>'text',
            "metadata.table_description.id_numbers"=>'array',
            "metadata.table_description.publication_frequency"=>'text',
            "metadata.table_description.format"=>'text',
            "metadata.table_description.language"=>'array',

            "metadata.table_description.bibliographic_citation"=>'text',
            "metadata.table_description.chapter"=>'text',
            "metadata.table_description.edition"=>'text',
            "metadata.table_description.institution"=>'text',
            "metadata.table_description.journal"=>'text',
            "metadata.table_description.volume"=>'text',
            "metadata.table_description.issue"=>'text',
            "metadata.table_description.pages"=>'text',
            "metadata.table_description.series"=>'text',
            "metadata.table_description.creator"=>'text',
            "metadata.table_description.authors"=>'array',
            
            "metadata.table_description.editors"=>'array',
            "metadata.table_description.translators"=>'array',
            "metadata.table_description.contributors"=>'array',
            "metadata.table_description.publisher"=>'text',
            "metadata.table_description.publisher_address"=>'text',
            "metadata.table_description.rights"=>'text',
            "metadata.table_description.copyright"=>'text',
            "metadata.table_description.usage_terms"=>'text',
            "metadata.table_description.security_classification"=>'text',

            "metadata.table_description.access_restrictions"=>'text',
            
            "metadata.table_description.sources.data_source"=>'array',
            "metadata.table_description.sources.source_origin"=>'text',
            "metadata.table_description.sources.source_char"=>'text',
            "metadata.table_description.sources.source_doc"=>'text',

            "metadata.table_description.keywords"=>'text',
            "metadata.table_description.topics"=>'text',
            "metadata.table_description.audience"=>'text',
            "metadata.table_description.location"=>'text',
            "metadata.table_description.mandate"=>'text',
            "metadata.table_description.pricing"=>'text',
            "metadata.table_description.relations"=>'array',
            

    ),
    $metadata);
?>


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "metadata.table_description.version_statement.version"=>'text',
            "metadata.table_description.version_statement.version_date"=>'text',
            "metadata.table_description.version_statement.version_notes"=>'text'
            ),
    $metadata);
?>


<!-- scope -->
<?php $output['notes']= render_group('notes',
    $fields=array(
            "metadata.table_description.notes"=>'array'
            ),
    $metadata);
?>


<!-- coverage -->
<?php $output['coverage']= render_group('coverage',
    $fields=array(
            "metadata.table_description.study_info.geog_coverage"=>'text',
            "metadata.table_description.study_info.geog_coverage_notes"=>'text',
            "metadata.table_description.study_info.geog_unit"=>'text',
            "metadata.table_description.study_info.analysis_unit"=>'text',
            "metadata.table_description.study_info.universe"=>'text'
            ),
    $metadata);
?>


<!-- producers_sponsors -->
<?php $output['producers_sponsors']= render_group('producers_sponsors',
    $fields=array(
            "authoring_entity"=>'array',
            "metadata.table_description.production_statement.producers"=>'array',
            "metadata.table_description.production_statement.funding_agencies"=>'array',
            "metadata.table_description.oth_id"=>'array'
            ),
    $metadata);
?>




<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "metadata.table_description.data_access.dataset_use.disclaimer"=>'text',
            "metadata.table_description.production_statement.copyright"=>'text'
            ),
    $metadata);
?>


<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "metadata.table_description.distribution_statement.contact"=>'array'
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
