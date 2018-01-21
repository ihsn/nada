<style type="text/css"> 
	#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
	#sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; cursor:move;background:white;}
	#sortable li span { position: absolute; margin-left: -1.3em; }
</style> 

<script type="text/javascript"> 
	$(function() {
		$("#sortable").sortable();
		$("#sortable").disableSelection();
	});
</script> 

<div class="container-fluid">
<?php 
	//menu breadcrumbs
	include 'menu_breadcrumb.php'; 
?>

    <h1 class="page-title"><?php echo t('menu_reorder');?></h1>
    <form name="menu_order_form" method="post">
        <div class="bottom-margin-10"><?php echo t('menu_reorder_instructions');?></div>
        <ul id="sortable"> 
            <?php foreach($rows as $row): ?>
            <li class="ui-state-default" id="<?php echo $row['id'] ?>"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><input type="hidden" name="id[]" value="<?php echo $row['id'];?>"/><?php echo $row['title'];?></li> 
            <?php endforeach;?>
        </ul> 
        <div class="top-margin-10">
        <span class="custom-fields"><?php echo form_submit('submit',t('update'),array('class'=>'btn btn-primary','id'=>'btnupdate')); ?></span>
        <?php echo anchor('admin/menu',t('cancel'),array('class'=>'btn btn-default') );	
        ?>
        </div>
    </form>
</div>
