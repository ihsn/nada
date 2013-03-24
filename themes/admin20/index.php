<?php
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Pragma: no-cache");
?>
<?php //include_once APPPATH.'/config/site_menus.php'; ?>
<?php
//build a list of links for available languages
$languages=$this->config->item("supported_languages");

$lang_list='';
if ($languages!==FALSE)
{
	if (count($languages)>1)
	{
		foreach($languages as $language)
		{
			$lang_list.='| <span> '.anchor('switch_language/'.$language.'/?destination=admin', strtoupper($language)).' </span>';
		}
	}
}

$this->load->library('site_menu');
$site_navigation_menu=$this->site_menu->get_formatted_menu_tree();
?>
<!DOCTYPE html>
<html>
  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<base href="<?php echo js_base_url(); ?>">
	<title><?php echo $title; ?></title>
	
    <!-- style reset using YUI -->
    <!--[if lt IE 9]>
      <link rel="stylesheet" type="text/css" href="themes/admin/reset-fonts-grids.css">
    <![endif]-->
    
    <!-- Bootstrap -->
    <link href="themes/<?php echo $this->template->theme();?>/css/custom/bootstrap.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/forms.css">
	<link rel="stylesheet" type="text/css" href="themes/<?php echo $this->template->theme();?>/catalog_admin.css">
    
    <!--[if lt IE 8]>
      <style>
      .btn-group > .btn-mini + .dropdown-toggle{border:0px solid red;padding:4px;vertical-align:top}
      </style>
    <![endif]-->
    
    

    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
		text-align:left;
      }
      .sidebar-nav {
        padding: 9px 0;
      }
    </style>
    
    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

	<script src="themes/<?php echo $this->template->theme();?>/js/jquery-1.9.0.js"></script>
    <script src="themes/<?php echo $this->template->theme();?>/js/bootstrap.min.js"></script>
    <script src="javascript/jquery-migrate-1.0.0.min.js"></script>
    
    
    <script type="text/javascript"> 
   		var CI = {'base_url': '<?php echo site_url(); ?>'}; 
	</script> 

	<?php if (isset($_styles) ){ echo $_styles;} ?>
    <?php if (isset($_scripts) ){ echo $_scripts;} ?>

  </head>
  <body>
  
<div class="navbar navbar-inverse navbar-fixed-top">
<div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".subnav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="<?php echo site_url();?>/admin">NADA 4.0-alpha</a>
          <div class="nav-collapse subnav-collapse">
          <?php echo $site_navigation_menu;?>
          <?php /*
          <!--
            <ul class="nav">
              <li class="active"><a href="<?php echo site_url();?>/admin">Dashboard</a></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Catalog<b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="<?php echo site_url();?>/admin/catalog">All Surveys</a></li>
                  <li><a href="<?php echo site_url();?>/admin/catalog/upload_ddi">Add Survey (upload ddi)</a></li>
                  <li><a href="<?php echo site_url();?>/admin/catalog/import_ddi">Batch import DDIs</a></li>
                  <li class="divider"></li>
                  <li><a href="<?php echo site_url();?>/admin/licensed_requests">Licensed survey requests</a></li>
                  <li class="divider"></li>
                  <li><a href="<?php echo site_url();?>/admin/repositories">Repositories</a></li>
                  <li><a href="<?php echo site_url();?>/admin/repositories/add">Add new repository</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Vocabularies <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="#">Survey collections</a></li>
                  <li><a href="#">Countries</a></li>
                  <li><a href="#">Topics</a></li>
                </ul>
              </li>

              <li class="dropdown">
                <a href="<?php echo site_url();?>/admin/citations" class="dropdown-toggle" data-toggle="dropdown">Citations<b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="<?php echo site_url();?>/admin/citations">All Citations</a></li>
                  <li><a href="#">Import citations</a></li>
                  <li><a href="#">Export citations</a></li>
                </ul>
              </li>

			<li class="dropdown">
                <a href="<?php echo site_url();?>/admin/users" class="dropdown-toggle" data-toggle="dropdown">Users<b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="<?php echo site_url();?>/admin/users">All Users</a></li>
                  <li><a href="#">Add User</a></li>
                  <li class="divider"></li>
                  <li><a href="#">User Groups</a></li>
                  <li><a href="#">Add User Group</a></li>
                  <li class="divider"></li>
                  <li><a href="#">User Permissions</a></li>
                </ul>
              </li>
              
              <li><a href="#">Menu</a></li>
              <li><a href="#">Reports</a></li>
              <li><a href="#">Site Configurations</a></li>

            </ul>
           
            <ul class="nav pull-right">
              <li class="divider-vertical"></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">User <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="#">Profile</a></li>
                  <li><a href="#">Logout</a></li>
                  <li class="divider"></li>
                  <li><a href="#">Data Catalog</a></li>
                </ul>
              </li>
            </ul>
          </div><!-- /.nav-collapse -->
		  */?>
          
          <ul class="nav pull-right">
              <li class="divider-vertical"></li>
              <li class="dropdown">
              <?php $user=strtoupper($this->session->userdata('username'));?>
			  <?php if ($user):?>
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $user;?> <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><?php echo anchor('auth/change_password',t('change_password'));?></li>
                  <li><?php echo anchor('auth/logout',t('logout'));?></li>
                  <li class="divider"></li>
                  <li><a href="<?php echo site_url();?>"/>Home</a></li>
                  <li><a href="<?php echo site_url();?>/catalog"/>Data Catalog</a></li>
                  <li><a href="<?php echo site_url();?>/citations"/>Citations</a></li>
                </ul>
                <?php endif;?>
              </li>
            </ul>
        </div>
      </div>  
</div>
</div>
    
    <div class="container-fluid">
        <div class="row-fluid">
             
             <!--breadcrumbs -->
			<?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
            <?php if ($breadcrumbs_str!=''):?>
                <div id="breadcrumb" class="notabs">
                <?php echo $breadcrumbs_str;?>
                </div>
            <?php endif;?>
                
            <div id="content">
            <?php if (isset($content) ):?>
                <?php print $content; ?>
            <?php endif;?>
            </div> 
        
        </div>
    </div>    
    
  </body>
</html>