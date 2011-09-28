<html>
<head>
<title>Migrate to NADA 3 from NADA 2</title>

<style type="text/css">

body {
 background-color: #fff;
 margin: 40px;
 font-family: Lucida Grande, Verdana, Sans-serif;
 font-size: 14px;
 color: #4F5155;
}

a {
 color: #003399;
 background-color: transparent;
 font-weight: normal;
}

h1 {
 color: #444;
 background-color: transparent;
 border-bottom: 1px solid #D0D0D0;
 font-size: 16px;
 font-weight: bold;
 margin: 24px 0 2px 0;
 padding: 5px 0 6px 0;
}

code {
 font-family: Monaco, Verdana, Sans-serif;
 font-size: 12px;
 background-color: #f9f9f9;
 border: 1px solid #D0D0D0;
 color: #002166;
 display: block;
 margin: 14px 0 14px 0;
 padding: 12px 10px 12px 10px;
}

.bg{font-weight:bold;}
.rem{color:#666666}
</style>
</head>
<body>

<h1>Upgrade NADA 2.0 to NADA 3.0</h1>

<p style="background-color:#CCCC99;padding:5px;">Note: Before running the migrate script please make sure you have made a backup of NADA 2.0 database and files. The script will remove data from the NADA 3.0 database, if you have added data to NADA3.0 that you want to keep, DO NOT run this script.</p>

<p>1) The script requires that you edit the following file and add information about NADA 2.0:</p>
<code>
<span class="bg">application/config/migrate.php</span>
<pre>
<span class="rem">//relative or absolute path to nada2 installation</span>
$config['nada2_folder_path'] = '../nada2';

<span class="rem">//repository identifier - see the inc_config.php file in nada2 and paste the value here</span>
$config['nada2_repository_id'] = 'www.ihsn.org';
</pre>
</code>

<p>2) To upgrade the database, we need the connection settings for the NADA 2.0 database. Find the below file and enter the database information:</p>
<code>
<span class="bg">application/config/database.php</span>

<pre>
<span class="rem">
// insert this at the bottom of the <b>database.php</b> file. 
// Change the hostname,username, password, database, dbprefix values to match your 
// nada 2 database settings that can be found in nada2 <b>inc_config.php</b> file.
</span>
$db['nada2']['hostname'] = "localhost";
$db['nada2']['username'] = "user";
$db['nada2']['password'] = "pass";
$db['nada2']['database'] = "nada2";
$db['nada2']['dbdriver'] = "mysql";
$db['nada2']['dbprefix'] = "nada_";
$db['nada2']['pconnect'] = FALSE;
$db['nada2']['db_debug'] = FALSE;
$db['nada2']['cache_on'] = FALSE;
$db['nada2']['cachedir'] = "system/cache";
$db['nada2']['char_set'] = "utf8";
$db['nada2']['dbcollat'] = "utf8_general_ci";
</pre>

</code>

<p>3) Copy files and folders under <b>stock/datasets</b> to nada 3 datafiles folder: <b><?php echo $this->config->item("catalog_root"); ?></b></p>
<p>Click on the upgrade button after you have made changes to the database.php and migrate.php.</p>
<form method="post">
<p><input type="submit" name="upgrade" value="Run migrate script"/></p>
</form>
</body>
</html>