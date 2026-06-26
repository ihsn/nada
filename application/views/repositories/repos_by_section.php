<?php
/*
 * Show repos by section
 */
if (!isset($show_unpublished)) {
	$show_unpublished = FALSE;
}
?>
<?php if ($rows): ?>
	<div class="row home-featured-cards">
		<?php foreach ($rows as $key => $row): ?>
			<?php if ($row['section'] != $section) { continue; } ?>
			<?php if (!$row['ispublished'] && $show_unpublished == FALSE) { continue; } ?>
			<?php render_collection_card($row); ?>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<?php echo t('no_records_found'); ?>
<?php endif; ?>
