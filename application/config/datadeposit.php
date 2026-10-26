<?php
// Enable data deposit (file-only; Site settings shows status, does not toggle this)
$config['datadeposit']['enable_datadeposit'] = true;

// Folder for storing project files (relative to FCPATH or absolute)
$config['datadeposit']['resources'] = 'datafiles';

// Depositor field length limits (characters; enforced in API and UI).
$config['datadeposit']['field_limits'] = array(
	'title'           => 500,
	'shortname'       => 255,
	'description'              => 5000,
	'submission_text'          => 2000,
	'submission_disclosure_text' => 2000,
	'submission_cc'            => 500,
);
