<?php
//enable datadeposit
$config['datadeposit']['enable_datadeposit']=true;

//Show metadata sections expanded or collapsed?
$config['datadeposit']['sections_collapsed']=true;

//Show help text for form fields?
$config['datadeposit']['show_help']=false;

$config['datadeposit']['mandatory_fields'] = array(
	'ident_title',
	'coll_dates',
	'coverage_country'
);

$config['datadeposit']['mandatory_fields2'] = array(
	'Title',
	'Dates of Data Collection',
	'Country',
);

$config['datadeposit']['merged'] = array_combine($config['datadeposit']['mandatory_fields'], $config['datadeposit']['mandatory_fields2']);

$config['datadeposit']['recommended_fields'] = array(
	'overview_abstract',
	'coverage_geo',
	'prim_investigator',
	'funding',
	'impact_wb_name',
	'impact_wb_id'
);

$config['datadeposit']['recommended_fields2'] = array(
	'Abstract',
	'Geographical Coverage',
	'Primary Investigator',
	'Funding',
	'IE Project Name',
	'IE Project ID'
);


/**
 * 
 * Data access options
 * 
 * 
 */
$config['datadeposit']['access_policy_options']=array(
	''					=>	'--',
	'Direct Access'		=>	'Direct Access',
	'Public Use Files'	=>	'Public Use Files',
	'Licensed Access'	=>	'Licensed Access',
	'Data Enclave'		=>	'Data Enclave',
	'Not Defined'		=>	'Not Defined'
);



/**
 * 
 * Catalog publishing options
 * 
 */
$config['datadeposit']['to_catalog_options']=array(
	''					=>	'--',
	'Internal'			=>	'Internal',
	'External'			=>	'External',
);

/**
 * 
 *  To disable catalog publishing options, set the options to false
 * 
 */
//$config['datadeposit']['to_catalog_options']=false;


//folder for storing project files
$config['datadeposit']['resources'] = 'datafiles';

$config['datadeposit']['recommended'] = array_combine($config['datadeposit']['recommended_fields'], $config['datadeposit']['recommended_fields2']);

//enable/disable operational_information and impact evaluation fields
$config['datadeposit']['additional_fields']=false;

