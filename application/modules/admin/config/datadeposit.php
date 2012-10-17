<?php
$config['datadeposit']['mandatory_fields'] = array(
	'ident_subtitle',
	'ver_desc',
	'overview_abstract',
	'coverage_geo',
	'prod_s_acknowledgements',
	'coverage_country',
	'ident_study_type',
	'coll_mode',
	'disclaimer_disclaimer',
	'contacts_contacts'
);

$config['datadeposit']['mandatory_fields2'] = array(
	'Subtitle',
	'Description',
	'Abstract',
	'Geographic Coverage',
	'Other Acknowledgements',
	'Country',
	'Study Type',
	'Mode of Data Collection',
	'Disclaimer',
	'Contact Persons'
);

$config['datadeposit']['merged'] = array_combine($config['datadeposit']['mandatory_fields'], $config['datadeposit']['mandatory_fields2']);
