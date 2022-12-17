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
        font-size:1em;
        color:#0275d8;
        font-weight:normal;
    }
    .accordion-title:hover{
        color:#000;
    }

    #accordion-script-files .card{
        margin-top:5px;
        border-radius:0px;
    }

    #accordion-script-files .card-header {
        padding: 0.25rem 1rem;
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

<?php /* ?>
    <div class="col">
        <h4><?php echo get_array_nested_value ($metadata, 'metadata.project_desc.title_statement.title','.');?></h4>
        <h6><?php echo get_array_nested_value ($metadata, 'metadata.project_desc.title_statement.sub_title','.');?></h6>

        <?php if ($translated_title=get_array_nested_value ($metadata, 'metadata.project_desc.title_statement.translated_title','.')):?>
        <div>(<?php echo $translated_title?>)</div>
        <?php endif;?>

        
        <?php 
            $abbr=array();
            $abbr[]=get_array_nested_value ($metadata, 'metadata.project_desc.title_statement.alternate_title','.');        
            $prod_dates=get_array_nested_value ($metadata, 'metadata.project_desc.production_date','.');
            $abbr[]= implode(" - ", $prod_dates);
            $abbr= implode(", ", array_filter($abbr));
        ?>

        <?php if (!empty($abbr)):?>
        <div>
            <?php echo $abbr;?>
        </div>
        <?php endif;?>

        <?php if($idno=get_array_nested_value ($metadata, 'metadata.project_desc.title_statement.idno','.')):?>
            <div><?php echo $idno;?></div>
        <?php endif;?>    
        
        <?php if($abstract=get_array_nested_value ($metadata, 'metadata.project_desc.abstract','.')):?>
            <p class="mt-2"><?php echo $abstract;?></p>
        <?php endif;?>

    </div>
<?php */ ?>    
<?php 

    //rendered html for all sections
    $output=array();

    $template=array(
        'Overview'=>array(
            "metadata.project_desc.abstract"=>'text',
            "metadata.project_desc.keywords" =>'array_badge',
            "metadata.project_desc.output" =>'script_output_type',

            "metadata.project_desc.authoring_entity"=>'script_authoring_entity',
            "metadata.project_desc.contributors"=>'array',
            "metadata.project_desc.curators" =>'array',
            "metadata.project_desc.sponsors"=>'array',
            "metadata.project_desc.acknowledgements"=>'array',
            "metadata.project_desc.topics" =>'array',

            //"metadata.project_desc.title_statement.title"=>'text',
            //"metadata.project_desc.title_statement.sub_title" =>'text',
            //"metadata.project_desc.title_statement.alternate_title"=>'text',
            //"metadata.project_desc.title_statement.translated_title"=>'text',

            //"metadata.project_desc.production_date"=>'array',            
            
            "metadata.project_desc.language" =>'array_badge',
            "metadata.project_desc.process"=>'array'
            
        ),
        
        /*'process'=>array(
            
            "metadata.project_desc.project_website" =>'text',            
        ),*/

        'Methods, software and scripts'=>array(
            "metadata.project_desc.methods"=>'array_badge',
            "metadata.project_desc.software"=>'array',
            "metadata.project_desc.license"=>'array',
            "metadata.project_desc.repository_url" =>'array',


            "metadata.project_desc.scripts"=>'script_file',
            "metadata.project_desc.technology_environment"=>'text',
            "metadata.project_desc.technology_requirements"=>'text',
            "metadata.project_desc.reproduction_instructions"=>'text'
            
        ),        


        'scripts'=> array(
            
        ),
        'methods'=>array(
            
        ),
        
        'datasets' => array(
            "metadata.project_desc.datasets"=>'script_datasets',
            "metadata.project_desc.geographic_units"=>'array',
            
            "metadata.project_desc.themes" =>'array_badge',
            
            "metadata.project_desc.tags" =>'array_badge',
            "metadata.project_desc.disciplines" =>'array',   
            "metadata.project_desc.review_process"=>'array',
            "metadata.project_desc.disclaimer"=>'text',
            "metadata.project_desc.confidentiality"=>'text',
            "metadata.project_desc.citation_requirement"=>'text',

            
        ),  

        /*'version'=>array(
            "metadata.project_desc.version_statement.version"=>'text',
            "metadata.project_desc.version_statement.version_date"=>'text',
            "metadata.project_desc.version_statement.version_resp"=>'text',
            "metadata.project_desc.version_statement.version_notes"=>'text'
        ),*/

        /*'language'=> array(
            "metadata.project_desc.language" =>'array_badge'
        ),*/
        
              
        'related_projects' => array(
            "metadata.project_desc.related_projects"=>'array',
        ),
        'contacts' => array(
            "metadata.project_desc.contacts"=>'array'
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
foreach($template as $section=>$fields){
    $output[$section]=render_group($section,$fields,$metadata, array('resources'=>$metadata['resources']));
}
?>

<?php 
    //renders html
    $this->load->view('metadata_templates/metadata_output', array('output'=>$output, 'hide_sidebar'=>FALSE));
?>

