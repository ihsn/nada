<?php
/**
 * Study page tab bar (shared by layout.php and layout_scripts.php).
 *
 * @var array  $page_tabs
 * @var string $active_tab
 * @var string $active_tab_class
 * @var string $data_access_type
 */
?>
<!-- Nav tabs: page links (each tab is a separate page), not ARIA tabs -->
<?php $da_label=(isset($data_access_type) && $data_access_type!=='') ? t('legend_data_'.$data_access_type) : ''; ?>
<nav class="study-tabs-nav wb-nav-tab-space" aria-label="<?php echo html_escape(t('study_sections'));?>">
<ul class="nav nav-tabs study-tabs">
	<?php foreach($page_tabs as $tab_name=>$tab):?>
		<?php if ($tab['show_tab']==0){continue;};?>
		<?php $is_active=($tab_name==$active_tab); ?>
		<li class="nav-item tab-<?php echo html_escape($tab_name);?><?php echo $tab_name=='get_microdata' ? ' nav-item-get-microdata' : '';?><?php echo $is_active ? ' '.$active_tab_class : '';?>">
			<a href="<?php echo html_escape($tab['url']);?>" class="nav-link<?php echo $is_active ? ' '.$active_tab_class : '';?>"<?php echo $is_active ? ' aria-current="page"' : '';?>>
				<?php if($tab_name=='get_microdata'):?>
					<span class="get-microdata icon-da-<?php echo html_escape($data_access_type);?>"<?php echo $da_label!=='' ? ' title="'.html_escape($da_label).'"' : '';?> aria-hidden="true"></span>
					<?php echo $tab['label'];?>
					<?php if ($da_label!==''):?><span class="sr-only">(<?php echo html_escape($da_label);?>)</span><?php endif;?>
				<?php else:?>
					<?php echo $tab['label'];?>
				<?php endif;?>
			</a>
		</li>
	<?php endforeach;?>
</ul>
</nav>
<script>
	//phone: keep the active tab in view and fade the edge only while more tabs are hidden
	(function () {
		var nav = document.querySelector('.study-tabs-nav');
		var list = nav ? nav.querySelector('.study-tabs') : null;
		if (!list) { return; }
		var active = list.querySelector('.nav-link.active');
		if (active && list.scrollWidth > list.clientWidth) {
			list.scrollLeft = Math.max(0, active.offsetLeft - 16);
		}
		var update = function () {
			nav.classList.toggle('has-more', list.scrollLeft + list.clientWidth < list.scrollWidth - 2);
		};
		list.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update);
		update();
	})();
</script>
<!-- end nav tabs -->
