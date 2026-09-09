<?php
/**
 * Authors table (simplified): Name, affiliation, Type, ID.
 * If full_name is set it is used and first/initial/last are ignored;
 * otherwise those parts are concatenated. Repeatable author_id is
 * flattened into Type and ID columns on the main table.
 */
$this->load->helper('display_template');

$prepared = display_template_authors_normalize_for_table(
	isset($data) ? $data : array(),
	isset($template) ? $template : array()
);

$this->load->view('display_templates/fields/field_array', array(
	'data' => $prepared['data'],
	'template' => $prepared['template'],
	'resources' => isset($resources) ? $resources : array(),
));
