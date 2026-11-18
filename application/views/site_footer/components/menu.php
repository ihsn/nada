<?php
/**
 * Footer menu: top-level published menu links.
 *
 * @var array $items menu tree from Menu_model
 */
$this->load->helper('menu');
if (empty($items) || !is_array($items)) {
	return;
}
?>
<ul class="sf-menu sf-c">
<?php foreach ($items as $item): ?>
	<?php
	$title = isset($item['title']) ? (string) $item['title'] : '';
	if ($title === '') {
		continue;
	}
	$url = menu_item_url($item);
	$target = menu_item_target($item);
	?>
	<li><a href="<?php echo html_escape($url); ?>"<?php echo $target; ?>><?php echo html_escape($title); ?></a></li>
<?php endforeach; ?>
</ul>
