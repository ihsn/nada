<div class="content" >
<form method="post">
<input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />

<?php if ($template_file!=''):?>
	<table class="table table-striped" cellpadding="0" cellspacing="0">
        <tr class="table-header" valign="top" align="left">
            <th width="100px">Key</th>
            <th>Translation</th>
        </tr>	
	<?php $td_css='even';?>
	<?php foreach($template_file as $key=>$value):?>
    	<?php 
			if ($td_css!=='odd') {$td_css='odd';}
			else{$td_css='even';}
			
			$edit_key_found=array_key_exists($key, $edit_file);
			$edit_value='';
			if ($edit_key_found)
			{
				$edit_value=$edit_file[$key];
			}
			else
			{
				$td_css.=' not-found bg-danger';
			}
		?>
    	<tr class="<?php echo $td_css; ?>" valign="top">
        <td class="translation-key"><?php echo $key; ?></td>
        <td>
			<div class="master-translation">
                <?php echo nl2br(htmlspecialchars($value)); ?>
            </div>
            <?php 
				$lines = count(explode("\n", $edit_value));
				if ($lines<2) { $lines=2; }
			?>
			<textarea name="<?php echo nada_hash($key);?>" class="form-control flex-textarea flex" rows="<?php echo $lines;?>"><?php echo set_value(nada_hash($key), htmlspecialchars_decode($edit_value)); ?></textarea>
        </td>
        </tr>
    <?php endforeach;?>
    </table>
	<div><input class="btn btn-primary" type="submit" value="Save" name="save"/>  </div>
    <?php else:?>
        <h1><?php echo t('translator_help_title');?></h1>
        <p><?php echo t('translator_help_text');?></p>
    <?php endif;?>
</form>
</div>
