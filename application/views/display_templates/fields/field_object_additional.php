<?php
/**
 * Open additional-metadata object (any JSON shape).
 */
$this->load->helper('display_template');

$html = display_template_render_additional_field(
	isset($data) ? $data : null,
	isset($template) && is_array($template) ? $template : array(),
	isset($layout_items) && is_array($layout_items) ? $layout_items : array()
);
if ($html === '') {
	return;
}
echo $html;
