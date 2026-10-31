<div class="content" >
<form method="post">
<input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />

<?php if ($template_file!=''):?>
	<div class="form-inline mb-3 translation-filter">
		<input type="text" id="filter-text" class="form-control form-control-sm mr-3" style="min-width:300px;"
			value="<?php echo htmlspecialchars(mb_substr((string)$this->input->get('q'),0,100)); ?>"
			placeholder="<?php echo t('filter_this_file'); ?>">
		<div class="form-check mr-3">
			<input type="checkbox" class="form-check-input" id="filter-missing">
			<label class="form-check-label" for="filter-missing"><?php echo t('show_only_missing'); ?></label>
		</div>
		<span class="text-muted" id="filter-count"></span>
	</div>
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
    	<tr class="translation-row <?php echo $td_css; ?>" data-missing="<?php echo ($edit_key_found && (string)$edit_value!=='') ? '0' : '1'; ?>" valign="top">
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

<script type="text/javascript">
//Filter rows by hiding them only; hidden rows stay in the form so Save keeps every translation.
$(function() {
	var $rows = $('.translation-row');
	if (!$rows.length) { return; }

	function applyFilter() {
		var q = $.trim($('#filter-text').val()).toLowerCase();
		var onlyMissing = $('#filter-missing').is(':checked');
		var shown = 0;

		$rows.each(function() {
			var $row = $(this);
			var text = ($row.find('.translation-key').text() + ' ' +
				$row.find('.master-translation').text() + ' ' +
				$row.find('textarea').val()).toLowerCase();

			var visible = (q === '' || text.indexOf(q) !== -1) &&
				(!onlyMissing || $row.data('missing') == 1);

			$row.toggle(visible);
			if (visible) { shown++; }
		});

		$('#filter-count').text(shown + ' / ' + $rows.length);
	}

	$('#filter-text').on('input', applyFilter);
	$('#filter-missing').on('change', applyFilter);
	applyFilter();
});
</script>
