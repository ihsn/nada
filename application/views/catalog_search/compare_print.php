<html>
<head>
	<title><?php echo t('title_compare_variables');?></title>
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
    <base href="<?php echo base_url(); ?>"/>
   	<link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi.css" />
    <style>
		body,html,td{font-family:Arial, Helvetica, sans-serif;font-size:12px;}
		.survey-info{margin-bottom:10px;}
		.compare-header{padding:5px;}
		.compare-header div {float:left;}
		.remove {color:white;}
		.item{padding-bottom:10px;margin-bottom:20px;}
		.varCatgry td{font-size:11px;padding:0px;margin:0px;}
		.varCatgry{margin:0px;padding:0px;}
	</style>
</head>
<body style="margin:0px;padding:0px;">
<div class="compare-header">
	<div class="xsl-title" style="margin:0px;"><?php echo t('title_compare_variables');?></div>
    <br style="clear:both" />
</div>    
<?php 
if ($list): ?>
<?php $tr_class=""; ?>
		<?php $k=0;?>
		<?php foreach($list as $item):?>
	        <?php $k++;?>
    		<div class="item">
            	<?php $survey_title=$item['dataset']['title'];?>
                <?php $variable_name=$item['variable']['name'];?>
                <?php if ($survey_title!==FALSE && $variable_name!==FALSE):?>
            	<div style="border:1px solid gray;margin:5px;">
	            	<div style="background-color:gray;color:white;padding:5px;">
						<div><?php echo $variable_name;?> - <?php echo $survey_title;?></div>
                     </div>
    	        	<div style="padding:5px;">
                            <?php echo $item['html'];?>
                    </div>
				</div>
				<?php endif;?>                
            </div>
        	<?php if ($k<count($list)):?>
                <!-- page break for pdf-->
                <pagebreak orientation="portrait" />    
            <?php endif; ?>
		<?php endforeach;?>
<?php else: ?>
	<?php echo t('no_records_found');?>
<?php endif; ?>
<script>
window.print();
</script>
</body>
</html>