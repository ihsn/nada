<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<html>
<head>
<title>NADA Installation</title>

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

.error{background-color:#FFFFCC;color:red;border:1px solid #999999;padding:10px;}
.success{background-color:#FFFFCC;color:red;border:1px solid #999999;padding:10px;}
</style>
</head>
<body>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<form method="post" action="<?php echo site_url();?>/install/installing">
<h1>Welcome to National Data Archive 3.0</h1>

<p>Click on the install button to setup the database.</p>
<div style="border-top:1px solid #D0D0D0;padding-top:5px;">
<input type="submit" name="install" value="Install database"/>
</div>
</form>

</body>
</html>