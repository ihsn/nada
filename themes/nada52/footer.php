<?php
/**
 * Public site footer entry point.
 *
 * Priority:
 *  1. footer.local.php — site-specific manual footer (never shipped; survives upgrades)
 *  2. Theme file (footer_theme.php) — when Admin > Site footer source is "Theme file",
 *     or when $config['site_footer_force_default'] is TRUE, or when nothing is published
 *  3. Custom HTML — Admin > Settings > Site footer
 */
$_ci =& get_instance();
$_ci->load->library('site_footer_builder');

$_sf_dir = __DIR__;
if (is_file($_sf_dir . '/footer.local.php')) {
	include $_sf_dir . '/footer.local.php';
} elseif ($_ci->site_footer_builder->use_theme_footer()) {
	include $_sf_dir . '/footer_theme.php';
} else {
	echo $_ci->site_footer_builder->render(isset($data['menus']) ? $data['menus'] : null);
}
