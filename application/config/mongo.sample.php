<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| MongoDB Configurations
|--------------------------------------------------------------------------
|
| Copy this file to mongo.php and configure for your environment.
| mongo.php is not tracked in git and is not replaced when upgrading NADA.
|
| MongoDB is optional; it is needed only for indicator data and data API
| tables. Leave username and password empty only for a local development
| server without authentication. NADA authenticates against
| mongodb_database, so the user must be created in that database (not in
| admin).
|
*/

$config['mongodb_database'] = 'nada_db';
$config['mongodb_username'] = '';
$config['mongodb_password'] = '';
$config['mongodb_host'] = 'localhost';
$config['mongodb_port'] = '27017';
