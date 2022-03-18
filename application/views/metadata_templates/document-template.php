<?php
/*
 * Document template
 *
 * @metadata - array containing all metadata
 *
 * @id - survey id
 * @surveyid - IDNO
 * 
 *
 *
 **/
?>

<style>
    .field-label{
        font-weight:bold;
    }
</style>



<?php 
    //rendered html for all sections
    $output=array();
?>


<!-- identification section -->
<?php $output['description']= render_group('description',
    $fields=array(
        "metadata.document_description.title_statement.title"=>"text",
        "metadata.document_description.title_statement.sub_title"=>"text",
        "metadata.document_description.title_statement.alternate_title"=>"text",
        "metadata.document_description.title_statement.translated_title"=>"text",


        "metadata.document_description.date_created"=>"text",
        "metadata.document_description.date_available"=>"text",
        "metadata.document_description.date_published"=>"text",
        "metadata.document_description.date_modified"=>"text",


        "metadata.document_description.authors"=>"array",
        "metadata.document_description.editors"=>"array",
        "metadata.document_description.type"=>"text",
        "metadata.document_description.publication_frequency"=>"text",
        "metadata.document_description.series"=>"text",
        "metadata.document_description.status"=>"text",
        "metadata.document_description.abstract"=>"text",
        "metadata.document_description.description"=>"text",

        "metadata.document_description.audience"=>"text",
        "metadata.document_description.mandate"=>"text",

        "metadata.document_description.title_statement.idno"=>"text",
        "metadata.document_description.identifiers"=>"array",

        "metadata.document_description.languages"=>"array",
        
        "metadata.resources"=>'resources',
    ),
    $metadata);
    ?>

 

<?php $output['scope and coverage']= render_group('scope and coverage',
    $fields=array(
        "metadata.document_description.scope"=>"text",
        "metadata.document_description.ref_country"=>"array",
        "metadata.document_description.geographic_units"=>"array",
        "metadata.document_description.bbox"=>"bounding_box",
        "metadata.document_description.spatial_coverage"=>"text",
        "metadata.document_description.temporal_coverage"=>"text",
        "metadata.document_description.toc"=>"text",
        "metadata.document_description.toc_structured"=>"array",

        "metadata.document_description.keywords"=>"array",
        "metadata.tags"=>"array_comma",
        "metadata.document_description.topics"=>"array",
        "metadata.document_description.themes"=>"array",
        "metadata.document_description.disciplines"=>"array",
          
    ),
    $metadata);
    ?>

<?php $output['lda_topics']= render_group('lda_topics',
        $fields=array(  
        "metadata.lda_topics"=>"lda_topics",
        "metadata.lda_topics.model_info"=>"array",
        "metadata.lda_topics.topic_description"=>"array",
        "metadata.lda_topics.topic_description.topic_words.word"=>"text",
        "metadata.embeddings"=>"array",
        "metadata.additional"=>"dump",
    ),
    $metadata);
?>

    <?php $output['contributors']= render_group('contributors',
        $fields=array(            
            "metadata.document_description.creator"=>"text",
            "metadata.document_description.translators"=>"array",
            "metadata.document_description.contributors"=>"array",
            "metadata.document_description.contacts"=>"array"            
        ),
        $metadata);
        ?>


<?php $output['bibliographic information']= render_group('bibliographic information',
    $fields=array(
        "metadata.document_description.bibliographic_citation"=>"text",
        "metadata.document_description.chapter"=>"text",
        "metadata.document_description.edition"=>"text",
        "metadata.document_description.institution"=>"text",
        "metadata.document_description.journal"=>"text",
        "metadata.document_description.volume"=>"text",
        "metadata.document_description.number"=>"text",
        "metadata.document_description.pages"=>"text",
        "metadata.document_description.publisher"=>"text",
        "metadata.document_description.publisher_address"=>"text",
        "metadata.document_description.annote"=>"text",
        "metadata.document_description.booktitle"=>"text",
        "metadata.document_description.crossref"=>"text",
        "metadata.document_description.howpublished"=>"text",
        "metadata.document_description.key"=>"text",
        "metadata.document_description.organization"=>"text",
        "metadata.document_description.url"=>"text"
    ),
    $metadata);
?>

<!--  reproducibility section -->
<?php $output['reproducibility']= render_group('reproducibility',
    $fields=array(
        "metadata.document_description.reproducibility.statement"=>"text",        
        "metadata.document_description.sources"=>"array",
        "metadata.document_description.data_sources"=>"array",
        "metadata.document_description.relations"=>"array",
    ),
    $metadata);
?>   
    
    <?php $output['copyrights']= render_group('copyrights',
        $fields=array(
            "metadata.document_description.license"=>"array",
            "metadata.document_description.disclaimer"=>"text",
            "metadata.document_description.rights"=>"text",
            "metadata.document_description.copyright"=>"text",
            "metadata.document_description.usage_terms"=>"text",
        
            "metadata.document_description.security_classification"=>"text",
            "metadata.document_description.access_restrictions"=>"text",
            "metadata.document_description.pricing"=>"text",
            "metadata.document_description.reproducibility.links"=>"array",
        ),
        $metadata);
    ?>
    
    <?php $output['notes']= render_group('notes',
        $fields=array(         
            "metadata.document_description.notes"=>"array",
        ),
        $metadata);
    ?>    





<!-- metadata_production -->
<?php $output['metadata_production']= render_group('metadata_production',
    $fields=array(
        "metadata.metadata_information.title"=>"text",
        "metadata.metadata_information.idno"=>"text",
        "metadata.metadata_information.producers"=>"array",
        "metadata.metadata_information.production_date"=>"text",
        "metadata.metadata_information.version"=>"text",        
    ),
    $metadata);
?>

<?php 
    //items not to be included in the left side bar
    $exclude_sidebar_items=array('download_links');
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output, 'exclude_sidebar_items'=>$exclude_sidebar_items));
?>