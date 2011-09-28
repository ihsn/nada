<?php $missing=array();?>
<?php foreach($this->master_lang as $key=>$value): ?>
<?php
	$slave_key_found=array_key_exists($key, $this->slave_lang);
	$slave_value='';
	if ($slave_key_found)
	{
		$slave_value=($this->slave_lang[$key]);
	}
	else
	{
		$missing[$key]=$value;
	}
?>
<?php if ($slave_key_found) :?>
$lang['<?php echo $key;?>']="<?php echo str_replace('"','&quot;',$slave_value);?>";<?php echo "\n";?>
<?php endif;?>
<?php endforeach;?>

<?php if ($this->fill_missing):?>
<?php foreach($missing as $key=>$value):?>
$lang['<?php echo $key;?>']="<?php echo str_replace('"','&quot;',$value);?>";<?php echo "\n";?>
<?php endforeach;?>
<?php endif;?>

/* End of file <?php echo $this->file; ?> */
/* Location: ./application/language/<?php echo $this->slave; ?>/<?php echo $this->file; ?> */