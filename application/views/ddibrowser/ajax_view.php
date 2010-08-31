<?php
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $this->survey['titl'];?></title>
<base href="<?php echo base_url(); ?>"/>
<?php if ($this->input->get('css')):?>
	<link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi.css" />
<?php endif; ?>

<style>
body,p{font-size:12px;}
</style>
</head>

<body>
<div style="float:right">
    <a target="blank_" href="<?php echo $section_url.'?print=yes';?>"><img alt="<?php echo t('print');?>" src="images/print.gif" border="0"/></a>
    <a target="blank_" href="<?php echo $section_url.'?pdf=yes';?>"><img alt="<?php echo t('pdf');?>" src="images/pdf.gif" border="0"/></a>
</div>
<?php if ($this->input->get("title")):?>
    <div style="float:left">
        <?php if (isset($this->survey)):?>
			<?php echo '<div style="color:gray;height:35px;">'.$this->survey['nation'].' - '. $this->survey['titl'].'</div>';?>
        <?php endif;?>
    </div>
<?php endif;?>
<br style="clear:both;"/>
<?php echo $html;exit; ?>
</body>
</html>