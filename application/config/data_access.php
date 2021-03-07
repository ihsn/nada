<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Configurations for Licensed data access form fields
|--------------------------------------------------------------------------
|
| 
|
*/

/*
|   Enable or disable data_access_field [Identification of data files and variables needed]
|
*/

$config['licensed_access']['dataset_access'] = false;



/**
 *
 *  Data classifications and Licenses
 *
 */

/*
|--------------------------------------------------------------------------
| Set licenses types allowed per data classification
|--------------------------------------------------------------------------
|
*/
$config['data_access_options'] = array(
    'public'=> array(
        'cc40', 'open', 'direct','public','licensed','remote', 'data_na'
    ),
    'official'=> array(
        'research_license'
    ),
    'confidential' => array(
        'data_na'
    )
);

