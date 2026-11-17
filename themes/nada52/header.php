<?php
/**
 * Public site header entry point.
 *
 * Priority:
 *  1. header.local.php — site-specific manual header (never shipped; survives upgrades)
 *  2. Theme file (header_theme.php) — when Admin > Site header source is "Theme file",
 *     or when $config['site_header_force_default'] is TRUE, or when nothing is published
 *  3. Site header builder / custom HTML — Admin > Settings > Site header
 */
$_ci =& get_instance();
$_ci->load->library('site_header_builder');

$_sh_dir = __DIR__;
if (is_file($_sh_dir . '/header.local.php')) {
	include $_sh_dir . '/header.local.php';
} elseif ($_ci->site_header_builder->use_theme_header()) {
	include $_sh_dir . '/header_theme.php';
} else {
	echo $_ci->site_header_builder->render(isset($data['menus']) ? $data['menus'] : null);
}
