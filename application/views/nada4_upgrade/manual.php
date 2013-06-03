<h1>Manual steps to upgrade to NADA 4</h1>
<p>The manual process requires you to connect to your database using a tool such as PHPMYADMIN or MySQL Workbench. Please make sure you have made backup copy of your database.</p>
<ul>
<li>Connect to your database with your choice of client database tool</li>
<li>Select the nada database that you want to upgrade</li>
<li>Copy and paste the SQL Statements listed below and execute them on your NADA database</li>
</ul>

<textarea style="width:100%;height:400px;">
<?php echo $sql;?>
<?php echo $updates;?>
</textarea>