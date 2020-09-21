<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
        'cc40', 'open', 'direct','public','licensed'
    ),
    'official'=> array(
        'research_license'
    ),
    'confidential' => array(
        'data_na'
    )
);

