<?php
/*
 * Reproducable research template
 *
 * @metadata - array containing all metadata
 *
 *
 **/
?>


<style>
    .accordion-title{
        cursor:pointer;
        font-size:1.2em;
        color:#0275d8;
        font-weight:normal;
    }
    .accordion-title:hover{
        color:#000;
    }

    #accordion-script-files .card{
        margin-top:5px;
    }

    [data-toggle="collapse"] i:before{
        content: "\f139";
    }
    
    [data-toggle="collapse"].collapsed i:before{
        content: "\f13a";
    }
    
    .metadata-content-body{
        border-left:1px solid gainsboro;
    }

    .metadata-container .badge{
        border-radius: .25rem !important;
        font-size:100%;
        font-weight:normal;
    }
    
</style>




<?php 

    //rendered html for all sections
    $output=array();

    $template=array(
        'identification'=>array(
            "metadata.project_desc.title_statement.title"=>'text',
            "metadata.project_desc.title_statement.sub_title" =>'text',
            "metadata.project_desc.title_statement.alternate_title"=>'text',
            "metadata.project_desc.title_statement.translated_title"=>'text',

            "metadata.project_desc.production_date"=>'array',
            "metadata.project_desc.geographic_units"=>'array_badge',
            "metadata.project_desc.authoring_entity"=>'array',
            "metadata.project_desc.contributors"=>'array',
            "metadata.project_desc.curators" =>'array',
            "metadata.project_desc.abstract" =>'text',
            "metadata.project_desc.process"=>'array',
            "metadata.project_desc.keywords" =>'array_badge',
            "metadata.project_desc.themes" =>'array_badge',
            "metadata.project_desc.topics" =>'array',
            "metadata.project_desc.tags" =>'array_badge',
            "metadata.project_desc.disciplines" =>'array',        
            "metadata.project_desc.output_types" =>'script_output_type',
            "metadata.project_desc.repository_url" =>'array',
            "metadata.project_desc.project_website" =>'text'            
        ),
        'version'=>array(
            "metadata.project_desc.version_statement.version"=>'text',
            "metadata.project_desc.version_statement.version_date"=>'text',
            "metadata.project_desc.version_statement.version_resp"=>'text',
        "metadata.project_desc.version_statement.version_notes"=>'text'
        ),
        'language'=> array(
            "metadata.project_desc.language" =>'array_badge'
        ),
        'methods'=>array(
            "metadata.project_desc.methods"=>'array_badge',
        ),
        'software'=>array(
            "metadata.project_desc.software"=>'array',
            "metadata.project_desc.technology_environment"=>'text',
            "metadata.project_desc.technology_requirements"=>'text',
            "metadata.project_desc.reproduction_instructions"=>'text',
            "metadata.project_desc.license"=>'array',
            "metadata.project_desc.review_process"=>'array',
            "metadata.project_desc.disclaimer"=>'text',
            "metadata.project_desc.confidentiality"=>'text',
            "metadata.project_desc.citation_requirement"=>'text',

            "metadata.project_desc.sponsors"=>'array',
            "metadata.project_desc.acknowledgements"=>'array',
        ),        
        'datasets' => array(
            "metadata.project_desc.datasets"=>'script_datasets',
        ),        
        'related_projects' => array(
            "metadata.project_desc.related_projects"=>'array',
        ),
        'contacts' => array(
            "metadata.project_desc.contacts"=>'array'
        ),
        'scripts'=>array(
            "metadata.project_desc.scripts"=>'script_file',
        ),        
        
        'metadata_production'=>array(
            "metadata.doc_desc.idno"=>'text',
            "metadata.doc_desc.producers"=>'array',
            "metadata.doc_desc.prod_date"=>'text',
            "metadata.doc_desc.version"=>'text'
        )
    );
?>

<?php

$output=array(); 
foreach($template as $section=>$fields){
    $output[$section]=render_group($section,$fields,$metadata, array('resources'=>$metadata['resources']));
}
?>

<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output));
?>

