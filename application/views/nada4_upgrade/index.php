<form method="post" action="<?php echo current_url();?>/run_upgrade">
<h2>NADA 3 to 4 upgrade</h2>
<p>Please confirm you have done the following steps before you start the upgrade process.</p>
<ul>
<li>Make a backup of your nada 3 database. The upgrade script will modify your database and it will no longer work with the NADA 3.
<li>Make sure the nada database user account has access to create, update, delete, alter database tables, otherwise the script won't work.
</ul>
<input type="submit" name="submit" value="Upgrade database to NADA 4"/>
</form>