<?php

defined('BASEPATH') OR exit('No direct script access allowed');


/*
|--------------------------------------------------------------------------
| Fields for user registration and profile
|--------------------------------------------------------------------------
|
| List of optional/additional fields to be used for user registration and profile
|
|
|
*/
$config['auth_fields'] = array(
    'company' => array(
        'enabled' => true,
        'required' => false,
        'validation' => 'required|trim|disable_html_tags|xss_clean|max_length[100]',
        'enum' => array(
                'Institution 1' => 'Institution 1',
                'Institution 2' => 'Institution 2',
                'Institution 3' => 'Institution 3',
        ),
        'display' => 'dropdown',
        'help_text' => 'Institution name',
    ),
    'country' => array(
        'enabled' => false,
        'required' => true,
        'validation' => 'trim|disable_html_tags|xss_clean|max_length[150]|check_user_country_valid',
        'display' => 'dropdown',
    ),
);


