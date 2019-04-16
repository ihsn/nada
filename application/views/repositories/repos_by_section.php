<?php
/*
*
* Show repos by section
*/
if (!isset($show_unpublished)){
	$show_unpublished=FALSE;
}
?>
<?php if ($rows):?>
    <div class="container collection-container">
	<?php $tr_class=""; ?>
	<?php foreach($rows as $key=>$row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=" "; } ?>
        <?php if ($row->section!=$section){continue;}?>
        <?php if (!$row->ispublished && $show_unpublished==FALSE){continue;}?>
        <div class="row <?php echo $tr_class; ?>" >
            <div class="col-sm-2">
                <div class="thumb"><a href="<?php echo site_url('catalog/'.$row->repositoryid.'/about');?>"><img class="img-fluid img-thumbnail repo-thumbnail" src="<?php echo base_url();?><?php echo $row->thumbnail; ?>"/></a></div>
            </div>
            <div class="col-sm-10">
                <div class="text">
                    <h4><a href="<?php echo site_url('catalog/'.$row->repositoryid);?>/about"><?php echo $row->title; ?></a></h4>
                    <p><?php echo $row->short_text; ?></p>
                </div>
            </div>
        </div>
    <?php endforeach;?>
    </div>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>