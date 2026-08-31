<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Data-deposit form templates (owned by deposit, not catalog cores)
|--------------------------------------------------------------------------
|
| JSON lives under application/templates/deposit/. Same ME form shape as
| catalog editor templates; different files, UIDs, and override directory.
|
| Registry keys are NADA data types (survey, …) plus submit.
| The loader tags each row with context=deposit so catalog listings of
| type "survey" do not pick these up.
|
| Do not point these rows at application/templates/editor/. When adding a
| type, copy a subset into templates/deposit/ and register it here.
| Types with no deposit default still fall back to the catalog core at
| runtime until a deposit-owned file exists.
|
| Site overrides (same basename wins over the shipped file):
|   {userdata_path}/templates/deposit/{filename}
|
| Switching a type's form is done by changing deposit_template_defaults
| (no admin UI).
|
*/

/**
 * Base directory for deposit form templates (relative to APPPATH).
 */
$config['deposit_template_path'] = 'templates/deposit';

$config['survey'][] = array(
	'uid'  => 'deposit-survey-en',
	'name' => 'Data deposit — microdata',
	'lang' => 'en',
	'file' => 'templates/deposit/deposit_survey_en.json',
);

$config['submit'][] = array(
	'uid'  => 'deposit-submit-en',
	'name' => 'Data deposit — submit',
	'lang' => 'en',
	'file' => 'templates/deposit/deposit_submit_en.json',
);

/*
|--------------------------------------------------------------------------
| Default template UID per deposit data type
|--------------------------------------------------------------------------
|
| Keys match the registry buckets above.
| Each UID must exist in this file.
|
*/
$config['deposit_template_defaults'] = array(
	'survey' => 'deposit-survey-en',
	'submit' => 'deposit-submit-en',
);

/**
 * Config keys that are not data-type template lists.
 */
$config['deposit_template_meta_keys'] = array(
	'deposit_template_path',
	'deposit_template_defaults',
	'deposit_template_meta_keys',
);
