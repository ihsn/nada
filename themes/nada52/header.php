<?php
/**
 * Public site header.
 *
 * Built by application/libraries/Site_header_builder.php from the settings in Admin > Settings > Site header
 * (a preset layout or the admin's own HTML with {{tokens}}). With no settings it shows the default NADA header.
 * Set $config['site_header_force_default'] = TRUE in application/config/config.php to always show the default.
 */
$_ci =& get_instance();
$_ci->load->library('site_header_builder');
echo $_ci->site_header_builder->render(isset($data['menus']) ? $data['menus'] : null);
