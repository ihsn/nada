<h1 class="page-title"><?php echo t('datadeposit');?></h1>
<script type="text/javascript">
	jQuery(document).ready(function(){
		$("#tabs").tabs();
	});
</script>
<div id="tabs">
	<ul>
		<li><a href="#tabs-1"><?php echo t('project_information_tab');?></a></li>
		<li><a href="#tabs-2"><?php echo t('process_tab');?></a></li>
	<?php /*	<li><a href="#tabs-3"><?php echo t('files_tab');?></a></li> */ ?>
        <li><a href="#tabs-4"><?php echo t('history');?></a></li>
	</ul>
    <div id="tabs-1">
		<?php $this->load->view('datadeposit/project_review');?>	
	</div>
    <div id="tabs-2">
        <?php $this->load->view('datadeposit/process');?>
    </div>
 <?php /*   <div id="tabs-3">
        <?php $this->load->view('datadeposit/files');?>
     </div> */ ?>
    <div id="tabs-4">
        <?php $this->load->view('datadeposit/history');?>
     </div>
</div>