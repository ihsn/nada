<?php
	$from=form_prep($this->input->get("from"));
	$to=form_prep($this->input->get("to"));
	$report=form_prep($this->input->get("report"));
	$format=form_prep($this->input->get("format"));
	
	$querystring=sprintf("?format=excel&from=%s&to=%s&report=%s",$from,$to,$report);
?>
<?php if (!$format) :?>
<?php echo t('download_as');?>: <a href="<?php echo site_url(); ?>/admin/reports/<?php echo $querystring; ?>"><?php echo t('excel');?></a>
<?php endif;?>
