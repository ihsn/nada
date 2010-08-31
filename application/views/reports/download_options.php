<?php
	$from=$this->input->get("from");
	$to=$this->input->get("to");
	$report=$this->input->get("report");
	$format=$this->input->get("format");
	
	$querystring=sprintf("?format=excel&from=%s&to=%s&report=%s",$from,$to,$report);
?>
<?php if (!$format) :?>
<?php echo t('download_as');?>: <a href="<?php echo site_url(); ?>/admin/reports/<?php echo $querystring; ?>"><?php echo t('excel');?></a>
<?php endif;?>
