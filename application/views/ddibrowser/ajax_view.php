<?php
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
?>
<?php if ($this->input->get('css')):?>
	<link type="text/css" rel="stylesheet" href="themes/ddibrowser/ddi.css" />
<?php endif; ?>

<div style="overflow:hidden;">
<?php /* 
<div style="float:right">    
    <a target="blank_" href="<?php echo $section_url.'?print=yes';?>"><img alt="<?php echo t('print');?>" src="images/print.gif" border="0"/></a>
    <a target="blank_" href="<?php echo $section_url.'?pdf=yes';?>"><img alt="<?php echo t('pdf');?>" src="images/pdf.gif" border="0"/></a>
    <a target="blank_" href="<?php echo $section_url;?>" title="<?php echo t('new_window');?>"><img alt="<?php echo t('new_window');?>" src="images/new_window.png" border="0"/></a>
</div>
*/ ?>
<?php if ($this->input->get("title")):?>
    <div style="float:left">
        <?php if (isset($this->survey)):?>
			<?php echo '<div style="color:gray;height:35px;">'.$this->survey['nation'].' - '. $this->survey['titl'].'</div>';?>
        <?php endif;?>
    </div>
<?php endif;?>
<br style="clear:both;"/>
</div>
<?php echo $html;?>
<?php return;?>