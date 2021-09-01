<?php
if (isset($repo) && isset($repo['repositoryid'])){
}
else{
	$repo=array(
        'repositoryid'	=>'central',
        'title'			=>t('central_data_catalog')
    );
}

?>

<div class="container citations-container mt-3"> 
    <div class="row">
        <div class="col-sm-12">
            <?php if(isset($repo['ispublished']) && intval($repo['ispublished'])===0):?>
                <div class="content-unpublished"><?php echo t('content_is_not_published');?></div>
            <?php endif;?>            
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <?php echo $facets_html;?>
        </div>
        <div class="col">
            <div class="tab-content"><?php echo $content;?></div>
        </div>

    </div>
</div>    
