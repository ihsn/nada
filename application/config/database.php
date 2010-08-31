<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the "Database Connection"
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the "default" group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = "default";
$active_record = TRUE;

$db['default']['hostname'] = "localhost";
$db['default']['username'] = "ihsn";
$db['default']['password'] = "";
$db['default']['database'] = "nada_upgrade_test";
$db['default']['dbdriver'] = "mysql";
$db['default']['dbprefix'] = "";
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = FALSE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = "system/cache";
$db['default']['char_set'] = "utf8";
$db['default']['dbcollat'] = "utf8_general_ci";

$db['nada2']['hostname'] = "localhost";
$db['nada2']['username'] = "ihsn";
$db['nada2']['password'] = "";
$db['nada2']['database'] = "datafirst20";
$db['nada2']['dbdriver'] = "mysql";
$db['nada2']['dbprefix'] = "nada_";
$db['nada2']['pconnect'] = FALSE;
$db['nada2']['db_debug'] = FALSE;
$db['nada2']['cache_on'] = FALSE;
$db['nada2']['cachedir'] = "system/cache";
$db['nada2']['char_set'] = "utf8";
$db['nada2']['dbcollat'] = "utf8_general_ci";

$db['mssql']['hostname'] = "localhost\sqlexpress";
$db['mssql']['username'] = "sa";
$db['mssql']['password'] = "M-2002";
$db['mssql']['database'] = "nadaSQL";
$db['mssql']['dbdriver'] = "mssql";
$db['mssql']['dbprefix'] = "";
$db['mssql']['pconnect'] = TRUE;
$db['mssql']['db_debug'] = TRUE;
$db['mssql']['cache_on'] = FALSE;
$db['mssql']['cachedir'] = "";
$db['mssql']['char_set'] = "utf8";
$db['mssql']['dbcollat'] = "utf8_general_ci";

$db['pgsql']['hostname'] = "localhost";
$db['pgsql']['username'] = "postgres";
$db['pgsql']['password'] = "M-2002";
$db['pgsql']['database'] = "dummy";
$db['pgsql']['dbdriver'] = "postgre";
$db['pgsql']['dbprefix'] = "";
$db['pgsql']['pconnect'] = TRUE;
$db['pgsql']['db_debug'] = TRUE;
$db['pgsql']['cache_on'] = FALSE;
$db['pgsql']['cachedir'] = "system/cache";
$db['pgsql']['char_set'] = "utf8";
$db['pgsql']['dbcollat'] = "utf8_general_ci";

//MSSQL ODBC
//$db['odbc']['hostname'] = "Driver={SQL Server Native Client 10.0};Server=localhost\sqlexpress;Database=nada21;";
$db['odbc']['hostname'] = "Driver={SQL Server Native Client 10.0};Server=10.185.230.10;Database=ddpsecurity;";
$db['odbc']['username'] = "ddpuser";
$db['odbc']['password'] = "m-3";
$db['odbc']['database'] = "ddpsecurity";
$db['odbc']['dbdriver'] = "odbc";
$db['odbc']['dbprefix'] = "";
$db['odbc']['pconnect'] = FALSE;
$db['odbc']['db_debug'] = TRUE;
$db['odbc']['cache_on'] = FALSE;
$db['odbc']['cachedir'] = "";
$db['odbc']['char_set'] = "utf8";
$db['odbc']['dbcollat'] = "utf8_general_ci";

//SQLITE
$db['sqlite']['hostname'] = "";
$db['sqlite']['username'] = "";
$db['sqlite']['password'] = "";
$db['sqlite']['database'] = "nada.db";
$db['sqlite']['dbdriver'] = "sqlite";
$db['sqlite']['dbprefix'] = "";
$db['sqlite']['pconnect'] = TRUE;
$db['sqlite']['db_debug'] = TRUE;
$db['sqlite']['cache_on'] = FALSE;
$db['sqlite']['cachedir'] = "";
$db['sqlite']['char_set'] = "utf8";
$db['sqlite']['dbcollat'] = "utf8_general_ci";

/* End of file database.php */
/* Location: ./system/application/config/database.php */