<?php
/**
 * Header catalog search box. Values arrive escaped from Site_header_builder.
 *
 * @var string $action
 * @var string $placeholder
 * @var string $width   compact | medium | wide
 * @var string $submit  icon | button
 * @var bool   $use_rank
 * @var string $query   current sk value (may be empty)
 */
$width = in_array($width, array('compact', 'medium', 'wide'), true) ? $width : 'medium';
$submit = $submit === 'button' ? 'button' : 'icon';
?>
<form class="sh-search sh-search--<?php echo $width; ?> sh-c" method="get" action="<?php echo $action; ?>" role="search">
	<?php if (!empty($use_rank)): ?>
	<input type="hidden" name="sort_by" value="rank">
	<input type="hidden" name="sort_order" value="desc">
	<?php endif; ?>
	<div class="sh-search-box">
		<input
			class="sh-search-input"
			type="search"
			name="sk"
			value="<?php echo $query; ?>"
			placeholder="<?php echo $placeholder; ?>"
			aria-label="<?php echo $placeholder; ?>"
			autocomplete="off"
		>
		<?php if ($submit === 'button'): ?>
		<button class="sh-search-submit sh-search-submit--button" type="submit"><?php echo html_escape(t('search')); ?></button>
		<?php else: ?>
		<button class="sh-search-submit" type="submit" title="<?php echo html_escape(t('search')); ?>" aria-label="<?php echo html_escape(t('search')); ?>">
			<i class="fas fa-search" aria-hidden="true"></i>
		</button>
		<?php endif; ?>
	</div>
</form>
