<style>
.search-results .not-found td{background:#f8d7da;}
.search-results mark{padding:0;}
</style>

<div class="container-fluid translation-container">

<div class="page-links text-right">
	<a href="<?php echo site_url('admin/translate/'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-home mr-1"></i><?php echo t('Home'); ?></a>
</div>

<h1 class="page-title"><?php echo t('translate'); ?> &mdash; <?php echo t('search_all_files'); ?></h1>

<form method="get" class="form-inline mb-3" id="form-search-all" action="<?php echo site_url('admin/translate/search/'.$language); ?>">
	<select class="form-control form-control-sm mr-2" id="search-lang">
		<?php foreach ($languages as $lang): ?>
		<option value="<?php echo htmlspecialchars($lang); ?>" <?php echo ($language == $lang) ? 'selected="selected"' : ''; ?>><?php echo ucfirst($lang); ?></option>
		<?php endforeach;?>
	</select>
	<input type="text" name="q" class="form-control form-control-sm mr-2" style="min-width:320px;" maxlength="100"
		value="<?php echo htmlspecialchars($q); ?>" placeholder="<?php echo t('translate_search_placeholder'); ?>" autofocus>
	<div class="form-check mr-2">
		<input type="checkbox" class="form-check-input" id="missing" name="missing" value="1" <?php echo $only_missing ? 'checked' : ''; ?>>
		<label class="form-check-label" for="missing"><?php echo t('show_only_missing'); ?></label>
	</div>
	<button type="submit" class="btn btn-sm btn-primary"><?php echo t('search'); ?></button>
</form>

<?php if (!$searched): ?>
	<p class="text-muted"><?php echo t('search_all_files_help'); ?></p>
<?php elseif (empty($results)): ?>
	<div class="alert alert-info"><?php echo t('search_no_results'); ?></div>
<?php else: ?>
	<p class="text-muted"><?php echo count($results); ?> <?php echo t('search_results_count'); ?></p>
	<?php if ($truncated): ?>
		<div class="alert alert-warning"><?php echo sprintf(t('search_results_limited'), $limit); ?></div>
	<?php endif; ?>

	<table class="table table-sm table-striped search-results">
		<tr>
			<th style="width:15%"><?php echo t('search_col_file_key'); ?></th>
			<th style="width:40%">English</th>
			<th style="width:45%"><?php echo ucfirst($language); ?></th>
		</tr>
		<?php foreach ($results as $row): ?>
		<tr class="<?php echo $row['missing'] ? 'not-found' : ''; ?>" valign="top">
			<td>
				<a href="<?php echo site_url('admin/translate/edit/'.$language.'/'.$row['file']).'?q='.urlencode($row['key']); ?>"><?php echo htmlspecialchars($row['file']); ?></a>
				<div class="text-muted" style="font-size:smaller;word-break:break-all;"><?php echo htmlspecialchars($row['key']); ?></div>
			</td>
			<td><?php echo nl2br(htmlspecialchars($row['english'])); ?></td>
			<td>
				<?php if ($row['missing']): ?>
					<em class="text-danger"><?php echo t('search_not_translated'); ?></em>
				<?php else: ?>
					<?php echo nl2br(htmlspecialchars($row['translation'])); ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

</div>

<script type="text/javascript">
//changing the language submits the search to that language's URL
$('#form-search-all').on('submit', function() {
	this.action = "<?php echo site_url('admin/translate/search'); ?>/" + encodeURIComponent($('#search-lang').val());
});
</script>
