<?php
$doi = isset($doi) ? trim((string) $doi) : '';
if ($doi === '' && isset($metadata['doi'])) {
    $doi = trim((string) $metadata['doi']);
}
if ($doi === '' && isset($data) && is_array($data)) {
    $doi = display_template_doi_from_identifiers($data);
}
//accept bare DOIs and doi.org / doi: forms; anything that is not a 10.xxxx/... DOI is skipped
$doi = preg_replace('~^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)~i', '', $doi);
if ($doi === '' || !preg_match('~^10\.\d{4,9}/\S+$~', $doi)) {
    return false;
}
$doi_js = json_encode($doi, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<div class="mb-2 field field-doi-citation-widget field-<?php echo html_escape(str_replace(".'","-",$template['key']));?>">
    <div class="font-weight-bold field-title"><?php echo display_template_resolve_title(isset($template['key']) ? $template['key'] : '', $template['title']);?></div>

    <div class="doi-citation" ><a target="_blank" href="https://citation.crosscite.org/?doi=<?php echo html_escape(rawurlencode($doi));?>"><i class="fas fa-spinner fa-spin"></i> loading, please wait...</a></div>

    <div class="pt-2" style="font-size:small;display:none">
        <span class="btn btn-sm btn-outline-secondary collapsed" data-toggle="collapse" data-target="#citation_options" aria-expanded="false" aria-controls="citation_options">Citation format <i class="fas fa-cog"></i></span>
    </div>
    
    <div class="collapse clearfix bg-light p-3" id="citation_options">
        
        <?php echo form_open(null, 'class="form-inline pt-2"'); ?>
            <div class="form-group">
                <label for="doi_format" class="mr-2">Format</label>
                <select class="form-control form-control-sm" id="doi_format">
                    <option value="apa" selected="selected">APA</option>
                    <option value="university-of-york-mla">MLA</option>
                    <option value="harvard-cite-them-right">Harvard</option>
                    <option value="chicago-fullnote-bibliography-16th-edition">Chicago</option>
                </select>
            </div>
        <?php echo form_close(); ?>
        
    </div>

    <div class="mt-3 text-secondary" style="font-size:small;">
        <?php echo t('Export citation');?>:
        <?php
            $formats=array(
                'ris'=>'RIS',
                'bib'=>'BibTeX',
                //'json'=>'JSON',
                //'rdf'=>'RDF',
                'txt'=>'Plain text',
                //'endnote'=>'EndNote',
                //'refworks'=>'RefWorks'
            );

            $export_links=[];
        ?>
        <?php foreach($formats as $format_key=>$format):?>
            <?php $export_links[]='<a onclick="citation_export('."'".$format_key."'".')" href="#'.$format_key.'">'.$format.'</a>'; ?>
        <?php endforeach;?>

        <?php echo implode(' | ',$export_links);?>

        <div id="export-citation-status"></div>
    </div>

</div>

<script>

    function get_citation_by_doi()
    {        
        var doi=<?php echo $doi_js;?>;
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
                var citation=data;
                doi_citation.html(citation);
            },
            error: function(data){
                hide_doi_citation();
            }
        });
    }

    function citation_export(format='txt')
    {
        var doi=<?php echo $doi_js;?>;
        var url='https://doi.org/'+doi;
        var citation_export_status=$('#export-citation-status');
        var formats={
            'ris':'application/x-research-info-systems',
            'bib':'application/x-bibtex',
            'xml':'application/xml',
            'json':'application/json',
            'rdf':'application/rdf+xml',
            'rdf_turtle':'text/turtle',
            'txt':'text/x-bibliography',
        };
        
        citation_export_status.html('<i class="fas fa-spinner fa-spin"></i> exporting, please wait...');

        $.ajax({
            url: url,
            dataType: 'html',
            headers:{
                Accept: formats[format]
            },
            success: function(data){
                var citation=data;
                js_download('citation.'+format,citation);
                citation_export_status.html('');
            },
            error: function(data){
                citation_export_status.html('<i class="fas fa-exclamation-triangle"></i> Citation is not available.');                                
            }
        });
    }

    //DOI not resolvable: remove the widget, and its section + menu entry when nothing else is left
    function hide_doi_citation()
    {
        var widget=$('.field-doi-citation-widget');
        var section=widget.closest('.field-section-container');
        widget.remove();
        if (section.length && section.find('.field').length===0){
            var heading_id=section.find('.field-section').first().attr('id');
            section.hide();
            if (heading_id){
                $('#dataset-metadata-sidebar a[href="#'+heading_id+'"]').hide();
                $('.metadata-section-select option[value="#'+heading_id+'"]').remove();
            }
        }
    }

    function js_download(filename, text) 
    {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);

        element.style.display = 'none';
        document.body.appendChild(element);

        element.click();
        document.body.removeChild(element);
    }

    $(document).ready(function()
    {
        get_citation_by_doi();
        
        $("#doi_format").change(function(){
            get_citation_by_doi();
        });
               
    });

</script>
