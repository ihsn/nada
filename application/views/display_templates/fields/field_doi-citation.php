<?php 
if (!isset($data) || !is_array($data)){
    return false;
}

$doi='';
foreach($data as $row){
    if (isset($row['type']) && strtolower($row['type'])=='doi'){
        if (isset($row['identifier'])){
            $doi=$row['identifier'];
            break;
        }
    }
}

if (empty($doi)){
    return false;
}
?>

<div class="mb-2 field field-<?php echo str_replace(".'","-",$template['key']);?>">
    <div class="font-weight-bold field-title"><?php echo t($template['title']);?></div>

    <div class="doi-citation" ><a target="_blank" href="https://citation.crosscite.org/?doi=<?php echo $doi;?>"><i class="fas fa-spinner fa-spin"></i> loading, please wait...</a></div>

    <div class="pt-2" style="font-size:small;display:none">
        <span class="btn btn-sm btn-outline-secondary collapsed" data-toggle="collapse" data-target="#citation_options" aria-expanded="false" aria-controls="citation_options">Citation format <i class="fas fa-cog"></i></span>
    </div>
    
    <div class="collapse clearfix bg-light p-3" id="citation_options">
        <form class="form-inline pt-2">
            <div class="form-group">
                <label for="doi_format" class="mr-2">Format</label>
                <select class="form-control form-control-sm" id="doi_format">
                    <option value="apa" selected="selected">APA</option>
                    <option value="university-of-york-mla">MLA</option>
                    <option value="harvard-cite-them-right">Harvard</option>
                    <option value="chicago-fullnote-bibliography-16th-edition">Chicago</option>
                </select>
            </div>
        </form>
    </div>

    <div class="mt-3 text-secondary" style="font-size:small;">
        <?php echo t('Export citation');?>:
        <?php
            $formats=array(
                'ris'=>'RIS',
                'bib'=>'BibTeX',
                'json'=>'JSON',
                'rdf'=>'RDF',
                'txt'=>'Plain text',
                //'endnote'=>'EndNote',
                //'refworks'=>'RefWorks'
            );

            $export_links=[];
        ?>
        <?php foreach($formats as $format_key=>$format):?>
            <?php $export_links[]='<a href="'.site_url('study/export_citation/'.$metadata['id'].'/'.$format_key).'">'.$format.'</a>'; ?>
        <?php endforeach;?>

        <?php echo implode(' | ',$export_links);?>
    </div>

</div>

<script>

    function get_citation_by_doi()
    {        
        var doi='<?php echo $doi;?>';
        var url='https://doi.org/'+doi;
        var doi_citation=$('.doi-citation');

        doi_citation.html('<i class="fas fa-spinner fa-spin"></i> loading, please wait...');

        $.ajax({
            url: url,
            dataType: 'html',
            headers:{
                Accept: 'text/x-bibliography; style='+$('#doi_format').val()
            },
            success: function(data){
                console.log("success",data);
                var citation=data;
                doi_citation.html(citation);
            },
            error: function(data){
                console.log("error",data);
                doi_citation.html('<i class="fas fa-exclamation-triangle"></i> Citation is not available.');
                doi_citation.toggle();
            }
        });
    }

    $(document).ready(function(){
        get_citation_by_doi();
        
        $("#doi_format").change(function(){
            get_citation_by_doi();
        });        
    });

</script>
