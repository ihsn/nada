<?php //if (!$is_home):?>
<div class="sidebar" style="width:220px;">
<!--side-bar -->
<div class="left-nav-gray" >
	<div class="left-nav-h-gray_blank">
		<h4>&nbsp;</h4>
	</div>
	<div class="left-nav-g"><?php echo $sidebar;?></div>
	<div class="left-gray-f"><span>&nbsp;</span></div>
</div>
<!--end-sidebar-->
<?php //endif;?>

<!--sidebar-reference-owner-->
<div class="grey-module" id="stpModule">
    <div class="m-head"> 
        <h2>Data Catalogs</h2>
    </div>
    <div class="m-body">
        <?php echo $repositories_sidebar;?>	
    </div><div class="m-footer"><span>&nbsp;</span></div>
</div>
<!--end-sidebar-reference-owner-->

<!--sidebar-reference-owner-->
<!--
<div class="grey-module" id="stpModule">
    <div class="m-head"> 
        <h2>For Service Owner</h2>
    </div>
    <div class="m-body">
        <?php //echo $sidebar;?>	
    </div><div class="m-footer"><span>&nbsp;</span></div>
</div>
-->
<!--end-sidebar-reference-owner-->

</div>
