<?php 	header('Content-Type: text/html; charset=utf-8');	
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>
<?php
$menu_horizontal=FALSE;

//side menu
$data['menus']= $this->Menu_model->select_all();		
$sidebar=$this->load->view('default_menu', $data,true);
?>

dfdf
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" /> 
    <title><?php if ($title) echo $title; ?></title>
   	<base href="<?php echo base_url(); ?>" />

    <link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css" />    
    <link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/styles.css" />

    <script type="text/javascript" src="javascript/jquery.js"></script>
    <?php if (isset($_styles) ){ echo $_styles;} ?>
    <?php if (isset($_scripts) ){ echo $_scripts;} ?>

	<script type="text/javascript"> 
       var CI = {'base_url': '<?php echo site_url(); ?>'}; 
    </script> 
    
		<style>
		.user-box{text-align:right;font-size:11px;padding-right:5px;}
		.user-box ul{list-style:none;text-align:left;display:inline;}
		.user-box li{display:inline;border-left:1px solid gray;padding-left:5px;}
		.user-box .username{border:0px;font-size:12px;letter-spacing:1px;}
	</style>
</head>
<body>
<!--document layout-->
<table border="0" style="width:880px;border-collapse:collapse" cellspacing="0"  cellpadding="0" align="center">

	<!--login-->
    <tr>
        <td colspan="3" >
		<?php $user=$this->session->userdata('username'); ?>
		<?php if ($user!=''):?>
			<div class="user-box">
				<ul>                
	                <li class="username"><?php echo $user; ?></li>
    	            <li><a href="<?php echo site_url(); ?>/auth/profile">Profile</a></li>
					<li><a href="<?php echo site_url(); ?>/auth/change_password">Password</a></li>                                    
                    <li><a href="<?php echo site_url(); ?>/auth/logout">Logout</a></li>                
                </ul>
            </div>
		<?php else:?>
        <div class="user-box">
            <a href="<?php echo site_url(); ?>/auth/login">Login</a> | 
            <a href="<?php echo site_url(); ?>/auth/register">Register</a>
        </div>
        <?php endif;?>
        
        </td>        
    </tr>

	<!--header-->
    <tr>
        <td colspan="3" >
            <div class="site-banner">
            &nbsp;
            </div>
        </td>        
    </tr>
    <!--content/menu-->
    <tr style="height:400px;" valign="top">
    	<!--left menu -->
        <td style="width:200px" class="menu-container">
	        <div class="sidebar">
	        <?php 
	        echo isset($sidebar) ? $sidebar : '';?>  
	        </div>
        </td>
        <td style="width:5px;"></td>
        <!--content -->
        <td class="content-container" style="width:675px" valign="top">
        	<div style="margin:5px;margin-bottom:20px;" id="page-content">
                <div><?php echo isset($content) ? $content : '';?></div>
          </div>
        </td>
    </tr>
    <!--footer-->
    <tr class="footer-container" >
        <td colspan="3" >
            <div class="footer" id="footer" style="border:1px solid silver;padding:5px;margin-top:5px;background-color:white;">
                <div style="text-align:center">Demo site developed by the International Household Survey Network</div>
            </div>
        </td>        
    </tr>
</table>
</body>
</html>