<div class="body-container" style="padding:10px;">

<h1 class="page-title"><?php echo t('translate');?></h1>

Template language set to: BASE
<table class="table table-striped" width="100%" cellspacing="0" cellpadding="0">
	<tr class="header">
        <th><?php echo t('language');?></th>
        <th><?php echo t('actions');?></th>
    </tr>
	<?php $tr_class=""; ?>
	<?php foreach($languages as $lang):?>
            <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <tr class="<?php echo $tr_class; ?>" valign="top">
            <td><a href="<?php echo site_url('admin/translate/edit/'.$lang);?>"><?php echo $lang;?></a></td>
            <td><?php echo anchor('admin/translate/edit/'.$lang,t('edit'));?> | <?php echo anchor('admin/translate/download/'.$lang,t('download'));?></td>    
        </tr>
    <?php endforeach;?>
</table>

</div>