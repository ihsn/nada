<?php
$sort_by = $this->input->get('sort_by');
$sort_order = $this->input->get('sort_order');
$sid = isset($sid) ? (int) $sid : (int) $this->uri->segment(2);
$page_url = site_url('catalog/'.$sid.'/related-publications');
$persist = array('keywords', 'field', 'collection');
$citation_count = is_array($citations) ? count($citations) : 0;
?>

<div class="related-citations">
	<?php if (isset($survey)): ?>
		<h1 class="related-citations-study-title"><?php echo html_escape($survey['nation'].' - '.$survey['title']); ?></h1>
	<?php endif; ?>

	<header class="related-citations-header">
		<h2 class="related-citations-title"><?php echo t('citations_of_publications'); ?></h2>
		<p class="related-citations-intro"><?php echo t('related_publications_text'); ?></p>
	</header>

	<?php if ($citation_count > 0): ?>
		<div class="related-citations-toolbar">
			<div class="related-citations-count">
				<?php echo t('Found: '); ?><strong><?php echo (int) $citation_count; ?></strong>
			</div>
			<div class="related-citations-sort nada-sort-links" role="group" aria-label="<?php echo html_escape(t('sort_results_by')); ?>">
				<span class="related-citations-sort-label"><?php echo t('sort_results_by'); ?>:</span>
				<span><?php echo create_sort_link($sort_by, $sort_order, 'authors', t('authors'), $page_url, $persist); ?></span>
				<span><?php echo create_sort_link($sort_by, $sort_order, 'pub_year', t('date'), $page_url, $persist); ?></span>
				<span><?php echo create_sort_link($sort_by, $sort_order, 'title', t('title'), $page_url, $persist); ?></span>
			</div>
		</div>

		<ol class="citation-rows related-citations-list">
			<?php $k = 0; foreach ($citations as $row): $k++; ?>
				<?php
					$cite_url = site_url('citations/'.$row['id']);
					$ctype = isset($row['ctype']) ? (string) $row['ctype'] : '';
					$type_key = !empty($row['csl_type']) ? (string) $row['csl_type'] : $ctype;
					$type_label = $type_key !== '' ? t($type_key) : '';
					if ($type_label === $type_key) {
						$type_label = ucwords(str_replace(array('-', '_'), ' ', $type_key));
					}
				?>
				<li class="citation-row related-citation-row<?php echo ($k % 2 === 0) ? ' alternate' : ''; ?>"
					data-url="<?php echo html_escape($cite_url); ?>"
					title="<?php echo html_escape(t('View citation')); ?>">
					<span class="citation-index" aria-hidden="true"><?php echo $k; ?></span>
					<div class="row-body">
						<?php if ($type_label !== ''): ?>
							<span class="related-citation-type"><?php echo html_escape($type_label); ?></span>
						<?php endif; ?>
						<div class="related-citation-text">
							<?php echo $this->chicago_citation->format_escaped($row, $ctype); ?>
						</div>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else: ?>
		<p class="related-citations-empty text-muted"><?php echo t('no_records_found'); ?></p>
	<?php endif; ?>
</div>

<?php if ($this->input->get('ajax') && $citation_count > 0): ?>
<script type="text/javascript">
$(function () {
	$('.related-citations .citation-row a').each(function () {
		var href = $(this).attr('href');
		if (href && href.indexOf('ajax=true') === -1) {
			$(this).attr('href', href + (href.indexOf('?') === -1 ? '?' : '&') + 'ajax=true');
		}
	});
});
</script>
<?php endif; ?>

<?php if ($citation_count > 0): ?>
<script type="text/javascript">
$(function () {
	$('.related-citations .related-citation-row').on('click', function (e) {
		if ($(e.target).closest('a').length) {
			return;
		}
		var url = $(this).attr('data-url');
		if (url) {
			window.location = url;
		}
	});
	$('.related-citations .related-citation-row').on('keydown', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).trigger('click');
		}
	}).attr('tabindex', '0').attr('role', 'link');
});
</script>
<?php endif; ?>
