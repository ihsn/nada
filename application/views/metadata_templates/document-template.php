<?php
/*
 * DDI template - display metadata for DDI fields
 *
 * @metadata - array containing all metadata
 *
 * @id - survey id
 * @surveyid - IDNO
 * @ all survey table fields
 *
 *
 **/
?>


<?php 
    //rendered html for all sections
    $output=array();
?>

<?php /* ?>
<pre>
<?php
 var_dump($metadata);
?>
</pre>
<?php */?>

<!-- identification section -->
<?php $output['identification']= render_group('identification',
    $fields=array(
            "title"=>'text',
            "metadata.document_description.title_statement.sub_title"=>'text',
            "metadata.document_description.title_statement.alternate_title"=>'text',
            "metadata.document_description.title_statement.translated_title"=>'text',
            "idno"=>'text',
            "metadata.document_description.description"=>'text',
            "metadata.document_description.toc"=>'text',
            "metadata.document_description.abstract"=>'text',
            "metadata.document_description.note"=>'text',
            "metadata.document_description.coverage"=>'text',
            "metadata.document_description.spatial_coverage"=>'text',
            "metadata.document_description.temporal_coverage"=>'text',
            
            "metadata.document_description.date_created"=>'text',
            "metadata.document_description.date_available"=>'text',
            "metadata.document_description.date_modified"=>'text',
            "metadata.document_description.id_numbers"=>'array',
            "metadata.document_description.publication_frequency"=>'text',
            "metadata.document_description.format"=>'text',
            "metadata.document_description.language"=>'array',

            "metadata.document_description.bibliographic_citation"=>'text',
            "metadata.document_description.chapter"=>'text',
            "metadata.document_description.edition"=>'text',
            "metadata.document_description.institution"=>'text',
            "metadata.document_description.journal"=>'text',
            "metadata.document_description.volume"=>'text',
            "metadata.document_description.issue"=>'text',
            "metadata.document_description.pages"=>'text',
            "metadata.document_description.series"=>'text',
            "metadata.document_description.creator"=>'text',
            "metadata.document_description.authors"=>'array',
            
            "metadata.document_description.editors"=>'array',
            "metadata.document_description.translators"=>'array',
            "metadata.document_description.contributors"=>'array',
            "metadata.document_description.publisher"=>'text',
            "metadata.document_description.publisher_address"=>'text',
            "metadata.document_description.rights"=>'text',
            "metadata.document_description.copyright"=>'text',
            "metadata.document_description.usage_terms"=>'text',
            "metadata.document_description.security_classification"=>'text',

            "metadata.document_description.access_restrictions"=>'text',
            
            "metadata.document_description.sources.data_source"=>'array',
            "metadata.document_description.sources.source_origin"=>'text',
            "metadata.document_description.sources.source_char"=>'text',
            "metadata.document_description.sources.source_doc"=>'text',

            "metadata.document_description.keywords"=>'text',
            "metadata.document_description.topics"=>'text',
            "metadata.document_description.audience"=>'text',
            "metadata.document_description.location"=>'text',
            "metadata.document_description.mandate"=>'text',
            "metadata.document_description.pricing"=>'text',
            "metadata.document_description.relations"=>'array',
            

    ),
    $metadata);
?>


<!-- version -->
<?php $output['version']= render_group('version',
    $fields=array(
            "metadata.document_description.version_statement.version"=>'text',
            "metadata.document_description.version_statement.version_date"=>'text',
            "metadata.document_description.version_statement.version_notes"=>'text'
            ),
    $metadata);
?>


<!-- scope -->
<?php $output['scope']= render_group('scope',
    $fields=array(
            "metadata.document_description.study_notes"=>'text',
            "metadata.document_description.study_info.topics"=>'array',
            "metadata.document_description.study_info.keywords"=>'array'
            ),
    $metadata);
?>


<!-- coverage -->
<?php $output['coverage']= render_group('coverage',
    $fields=array(
            "metadata.document_description.study_info.geog_coverage"=>'text',
            "metadata.document_description.study_info.geog_coverage_notes"=>'text',
            "metadata.document_description.study_info.geog_unit"=>'text',
            "metadata.document_description.study_info.analysis_unit"=>'text',
            "metadata.document_description.study_info.universe"=>'text'
            ),
    $metadata);
?>


<!-- producers_sponsors -->
<?php $output['producers_sponsors']= render_group('producers_sponsors',
    $fields=array(
            "authoring_entity"=>'array',
            "metadata.document_description.production_statement.producers"=>'array',
            "metadata.document_description.production_statement.funding_agencies"=>'array',
            "metadata.document_description.oth_id"=>'array'
            ),
    $metadata);
?>


<!-- sampling -->
<?php $output['sampling']= render_group('sampling',
    $fields=array(
            "metadata.document_description.method.data_collection.sampling_procedure"=>'text',
            "metadata.document_description.method.data_collection.sampling_deviation"=>'text',
            "metadata.document_description.method.analysis_info.response_rate"=>'text',
            'metadata.document_description.method.data_collection.weight'=>'text'
            ),
    $metadata);
?>


<!-- data_collection -->
<?php $output['data_collection']= render_group('data_collection',
    $fields=array(
            "metadata.document_description.study_info.coll_dates"=>'array',
            "metadata.document_description.method.data_collection.frequency"=>'text',
            "metadata.document_description.study_info.time_periods"=>'array',
            "metadata.document_description.method.data_collection.sources.data_source"=>'text',
            "metadata.document_description.method.data_collection.coll_mode"=>'text',
            "metadata.document_description.method.data_collection.act_min"=>'text',
            "metadata.document_description.method.data_collection.research_instrument"=>'text',
            "metadata.document_description.method.data_collection.data_collectors"=>'array',
            ),
    $metadata);
?>


<!-- data_processing -->
<?php $output['data_processing']= render_group('data_processing',
    $fields=array(
            "metadata.document_description.method.data_collection.cleaning_operations"=>'text',
            "metadata.document_description.method.method_notes"=>'text'
            ),
    $metadata);
?>


<!-- data_appraisal -->
<?php $output['data_appraisal']= render_group('data_appraisal',
    $fields=array(
            "metadata.document_description.method.analysis_info.data_appraisal"=>'text'
            ),
    $metadata);
?>


<!-- data_access -->
<?php $output['data_access']= render_group('data_access',
    $fields=array(
            "metadata.document_description.data_access.dataset_use.contact"=>'array',
            "metadata.document_description.data_access.dataset_use.conf_dec.txt"=>'text',
            "metadata.document_description.data_access.dataset_use.conf_dec.form_url"=>'text',
            "metadata.document_description.data_access.dataset_use.conditions"=>'text',
            "metadata.document_description.data_access.dataset_use.cit_req"=>'text',
            "metadata.document_description.data_access.dataset_use.deposit_req">'text',
            "metadata.document_description.data_access.dataset_availability.access_place"=>'text', 
            "metadata.document_description.data_access.dataset_availability.original_archive"=>'text', 
            "metadata.document_description.data_access.dataset_availability.availability_status"=>'text'
            ),
    $metadata);
?>


<!-- disclaimer_copyright -->
<?php $output['disclaimer_copyright']= render_group('disclaimer_copyright',
    $fields=array(
            "metadata.document_description.data_access.dataset_use.disclaimer"=>'text',
            "metadata.document_description.production_statement.copyright"=>'text'
            ),
    $metadata);
?>


<!-- contacts -->
<?php $output['contacts']= render_group('contacts',
    $fields=array(
            "metadata.document_description.distribution_statement.contact"=>'array'
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
