<style>
#menu {
	border-bottom : 1px solid #ccc;
	margin : 0;
	xpadding-bottom : 19px;
	padding-left : 10px;
	margin-top:20px;
}
 
#menu ul, #menu li	{
	display : inline;
	list-style-type : none;
	margin : 0;
	padding : 0;
	margin-bottom:10px;
}
 
	
#menu a:link, #menu a:visited	{
	background:gainsboro;
	border : 1px solid #ccc;
	color:black;
	xfloat : left;
	font-size : 14px;
	font-weight : normal;
	xline-height : 18px;
	margin-right : 8px;
	padding : 2px 10px 2px 10px;
	text-decoration : none;
	display:inline-block;
	margin-bottom:-1px;
}

 
#menu .active a, #menu a:link.active, #menu a:visited.active	{
	background : #fff;
	border-bottom : 1px solid #fff;
	color : #000;
	font-weight:bold;
}

#menu .active a:hover{
	background:none;
	color:black;
}
 
#menu a:hover	{
	color:white;
	background:black;
}

 
</style>
<h1><?php echo t('resource_manager');?></h1>
<?php $selected_page=$this->uri->segment(4); ?>
<?php $edit_page=$this->uri->segment(2).'/'.$this->uri->segment(3);?>
<ul id="menu"> 
  <li <?php echo ($selected_page=='') ? 'class="active"' : ''; ?>><a href="<?php echo site_url();?>/admin/managefiles/<?php echo $this->uri->segment(3);?>"><?php echo t('manage_files');?></a></li> 
  <li <?php echo ($selected_page=='resources') ? 'class="active"' : ''; ?>><a href="<?php echo site_url();?>/admin/catalog/<?php echo $this->uri->segment(3);?>/resources"><?php echo t('external_resources');?></a></li>  
  <li <?php echo ($selected_page=='access') ? 'class="active"' : ''; ?>><a href="<?php echo site_url();?>/admin/managefiles/<?php echo $this->uri->segment(3);?>/access"><?php echo t('select_data_access_type');?></a></li>
  <li <?php echo ($selected_page=='edit') ? 'class="active"' : ''; ?>><a href="<?php echo site_url();?>/admin/catalog/<?php echo $this->uri->segment(3);?>/edit/"><?php echo t('links');?></a></li>  
</ul>