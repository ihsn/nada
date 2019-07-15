<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<?php
$active_tab=$this->input->get("tab");
$active_tab_class=' class= "ui-corner-top ui-tabs-selected ui-state-active"';
$current_url=current_url();

if($active_tab)
{
	$current_url.='?tab='.$active_tab;
}
?>
<style type="text/css">
#project_name {
width: 1000px;
font-size: 16.4px;
z-index: 100;
padding: 5px 0;
font-weight: bold;
}

.project-options
{
	float: right;
}

.project-options a{
	margin-left:10px;
}

.input-flex{
    display:block;
    width:100%;
}
.field{
    margin-bottom:15px;
}



</style>

<div class="page-links">
		<a href="<?php echo site_url(); ?>/admin/datadeposit" class="button"><img src="images/house.png"/>Home</a> 
</div>


<h1 class="page-title"><?php echo t('manage_projects');?></h1>

<div id="project_name"><?php echo $project->title; ?></div>
<script type="text/javascript">
	 $(function() {
    $( "#tabs" ).tabs({
      /*beforeLoad: function( event, ui ) {
        ui.jqXHR.error(function() {
          ui.panel.html(
            "Failed loading content" );
        });
      }*/
    });
  });
</script>
<div id="tabs">
	<ul>
		<li <?php echo ($active_tab=='info') ? $active_tab_class : ''; ?>><a href="<?php echo $current_url;?>#tabs-1">Project information</a></li>
		<li <?php echo ($active_tab=='process') ? $active_tab_class : ''; ?>><a href="<?php echo site_url('admin/datadeposit/tab_process/'.$project_id);?>#tab-process">Process</a></li>
		<li <?php echo ($active_tab=='files') ? $active_tab_class : ''; ?>><a href="<?php echo site_url('admin/datadeposit/tab_files/'.$project_id);?>#tabs-3">Files</a></li>
		<li <?php echo ($active_tab=='communicate') ? $active_tab_class : ''; ?>><a href="<?php echo site_url('admin/datadeposit/tab_communicate/'.$project_id);?>#tabs-4">Communicate</a></li>
        <li <?php echo ($active_tab=='history') ? $active_tab_class : ''; ?>><a href="<?php echo site_url('admin/datadeposit/tab_history/'.$project_id);?>#tab-history">History</a></li>
	</ul>
    <div id="tabs-1">
		
        <div class="project-options">
        	<a target="_blank" href="<?php echo site_url('admin/datadeposit/summary/'.$project_id);?>">Summary</a> |
            <a  target="_blank" href="<?php echo site_url('datadeposit/export/'.$project_id.'?format=ddi');?>">DDI</a> |
            <a  target="_blank" href="<?php echo site_url('datadeposit/export/'.$project_id.'?format=rdf');?>">RDF</a>
        </div>

		<?php echo $project_summary;?>	
	</div>
    <!--
    <div id="tabs-2">
        <?php //$this->load->view('datadeposit/process');?>
    </div>
    <div id="tabs-3">
        <?php //$this->load->view('datadeposit/files');?>
    </div>
    <div id="tabs-4">
        <?php //$this->load->view('datadeposit/communication');?>
    </div>
    <div id="tabs-5">
        <?php //$this->load->view('datadeposit/history');?>
     </div>
     -->
</div>