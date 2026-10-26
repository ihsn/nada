<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data deposit is installed with NADA; this flag turns the feature on.
 */
function datadeposit_is_enabled()
{
	$ci =& get_instance();
	$ci->config->load('datadeposit');
	return $ci->config->item('enable_datadeposit', 'datadeposit') === true;
}

function datadeposit_require_enabled()
{
	if (!datadeposit_is_enabled()) {
		show_404();
	}
}

/**
 * Operator cap from Site settings (MB). Default 2048 when unset or invalid.
 */
function datadeposit_max_upload_mb()
{
	$ci =& get_instance();
	$mb = (int) $ci->config->item('deposit_max_upload_size');
	if ($mb < 1) {
		$mb = 2048;
	}
	if ($mb > 16384) {
		$mb = 16384;
	}
	return $mb;
}

function datadeposit_max_upload_bytes()
{
	return datadeposit_max_upload_mb() * 1024 * 1024;
}

/**
 * Allowed extensions for deposit files (from config.php; not editable in Site settings).
 */
/**
 * Character limits for data-deposit forms (must match dd_projects column capacity).
 *
 * @return array{title: int, shortname: int, description: int, submission_text: int, submission_disclosure_text: int, submission_cc: int}
 */
function datadeposit_field_limits()
{
	$ci =& get_instance();
	$ci->config->load('datadeposit');
	$limits = $ci->config->item('field_limits', 'datadeposit');
	if (!is_array($limits)) {
		$limits = array();
	}
	$defaults = array(
		'title'           => 500,
		'shortname'       => 255,
		'description'                => 5000,
		'submission_text'            => 2000,
		'submission_disclosure_text' => 2000,
		'submission_cc'              => 500,
	);
	return array_merge($defaults, $limits);
}

/**
 * Limits for the Vue SPA (camelCase keys).
 */
function datadeposit_field_limits_for_vue()
{
	$limits = datadeposit_field_limits();
	return array(
		'title'          => (int) $limits['title'],
		'shortname'      => (int) $limits['shortname'],
		'description'    => (int) $limits['description'],
		'submissionText'           => (int) $limits['submission_text'],
		'submissionDisclosureText' => (int) $limits['submission_disclosure_text'],
		'submissionCc'             => (int) $limits['submission_cc'],
	);
}

function datadeposit_allowed_resource_types()
{
	$ci =& get_instance();
	$raw = (string) $ci->config->item('allowed_resource_types');
	$list = array();
	foreach (explode(',', $raw) as $item) {
		$item = strtolower(trim($item));
		if ($item !== '') {
			$list[] = $item;
		}
	}
	return $list;
}
