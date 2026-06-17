<?php
if (empty($menus) || !is_array($menus)) {
	return;
}

$current_page = isset($current_page) ? $current_page : current_url();

foreach ($menus as $item):
	$children = (isset($item['children']) && is_array($item['children'])) ? $item['children'] : array();
	$has_children = count($children) > 0;
	$url = menu_item_url($item);
	$target = menu_item_target($item);
	$is_active = menu_item_is_active($item, $current_page);
	$item_id = (int) $item['id'];
?>
<?php if ($has_children): ?>
<li class="nav-item dropdown">
	<a class="nav-link<?php echo $is_active ? ' active' : ''; ?>"
		href="<?php echo html_escape($url); ?>"
		id="nav-menu-<?php echo $item_id; ?>"
		data-toggle="dropdown"
		aria-haspopup="true"
		aria-expanded="false"<?php echo $target; ?>>
		<?php echo html_escape($item['title']); ?>
		<i class="fas fa-chevron-down nav-menu-chevron ml-1" aria-hidden="true"></i>
	</a>
	<div class="dropdown-menu" aria-labelledby="nav-menu-<?php echo $item_id; ?>">
		<?php foreach ($children as $child):
			$child_url = menu_item_url($child);
			$child_target = menu_item_target($child);
			$child_active = menu_item_is_active($child, $current_page);
		?>
		<a class="dropdown-item nav-menu-child<?php echo $child_active ? ' active' : ''; ?>"
			href="<?php echo html_escape($child_url); ?>"<?php echo $child_target; ?>>
			<?php echo html_escape($child['title']); ?>
		</a>
		<?php endforeach; ?>
	</div>
</li>
<?php else: ?>
<li class="nav-item">
	<a class="nav-link<?php echo $is_active ? ' active' : ''; ?>"
		href="<?php echo html_escape($url); ?>"<?php echo $target; ?>>
		<?php echo html_escape($item['title']); ?>
	</a>
</li>
<?php endif; ?>
<?php endforeach; ?>
