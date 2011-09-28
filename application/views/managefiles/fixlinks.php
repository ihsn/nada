<?php include dirname(__FILE__).'/../managefiles/tabs.php';?>
<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/catalog/<?php echo $this->uri->segment(3);?>/resources" class="button"><img src="images/house.png"/><?php echo t('link_resource_home');?></a> 
</div>

<h1><?php echo t('title_fix_broken');?></h1>
<?php if (!$this->input->post("submit") ):?>
<form method="post">
	<div><?php echo t('instruction_fix_broken');?></div>
	<input type="submit" value="<?php echo t('fix_it');?>" name="submit" style="width:100px;"/>
</form>
<?php endif;?>

<?php if ($links):?>
	<?php //var_dump($links);?>
	<p><?php echo sprintf (t('n_resources_fixed'),$this->fixed_count); ?>: (<img src="images/close.gif"/>=<?php echo t('legend_not_fixed');?>, <img src="images/tick.png"/>=<?php echo t('legend_fixed');?>) </p>
    <table class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('title');?></th>
            <th><?php echo t('filename');?></th>
            <th><?php echo t('status');?></th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($links as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><?php echo $row->title; ?></a></td>
            <td><?php echo $row->filename; ?></td>
            <?php if ($row->match===FALSE):?>
            <td><img src="images/close.gif" alt="NOT-FOUND"/></td>
			<?php else:?>            
            <td><img src="images/tick.png" alt="NOT-FOUND"/></td>
            <?php endif;?>
        </tr>
    <?php endforeach;?>
    </table>        
<?php else:?>
	<?php if ($this->input->post("submit") ):?>
		<?php echo t('no_broken_links_found');?>
	<?php endif;?>    
<?php endif;?>