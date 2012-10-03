<?php //if (!$is_home):?>
                               <!--breadcrumbs -->
                                <?php $breadcrumbs_str= $this->breadcrumb->to_string();?>
                                <?php if ($breadcrumbs_str!=''):?>
                                    <div id="breadcrumb" class="notabs">
                                    <?php echo $breadcrumbs_str;?>
                                    </div>
                                <?php endif;?>
<div class="sidebar" <?php if ($this->uri->segment(2) != 'projects') echo 'style="margin-top:120px;width:220px;"'; else echo 'style="width:220px;"' ?>>
<!--side-bar -->
 
<!--end-sidebar-->
<?php //endif;?>

<!--sidebar-reference-owner-->
<div class="grey-module" id="">
    <div class="m-head"> 
        <h2>Pending Tasks</h2>
    </div>
    <div class="m-body">
	<?php switch($this->uri->segment(2)):
	case 'summary':
	case 'study': ?>
 		<li style="list-style-type:none">Mandatory Fields:</li>
		<?php  foreach($merged['merged'] as $title): ?>
        <li style="margin-left: 5px"><?php echo $title; ?></li>
        <?php endforeach; ?>
 		<li style="list-style-type:none">Recommended Fields:</li>
		<?php  foreach($merged['recommended'] as $title): ?>
        <li style="margin-left: 5px"><?php echo $title; ?></li>
        <?php endforeach; ?>
		<?php break; ?>
   <?php case 'datafiles': ?>
   	<?php if (empty($files) && empty($records)): ?>
   		<li>Upload Files</li>
    <?php endif; ?>
    	<?php break; ?>
  <?php case 'citations': ?>
  	<?php if (!isset($study[0]->citations)): ?>
    	<li>Add Citations</li>
    <?php endif; ?>
    <?php break; ?>
    <?php endswitch; ?>
   </div>
</div>
<!--end-sidebar-reference-owner-->

<div class="grey-module" id="">
    <div class="m-head"> 
        <h2>Projects</h2>
    </div>
    <div class="m-body">
    	<?php foreach($projects as $project): ?>
        <li><a href="<?php echo site_url('datadeposit/update/'), '/', $project->id; ?>"><?php echo $project->title; ?></a></li>
        <?php endforeach; ?>
   </div>
</div>
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
