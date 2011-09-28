<style>
h4,p{margin:0px;padding:0px;}
p{margin-bottom:5px;}
h4{font-weight:bold;font-size:14px;margin-top:15px;}
</style>

<?php
//translate the form list
foreach($this->form_list as $key=>$value)
{
	$this->form_list[$key]=t($value);
}
?>

<?php include 'tabs.php';?>
<div class="body-container" style="padding:10px;background-color:gainsboro;margin-top:10px;margin-bottom:20px;">
<form autocomplete="off" method="post" >
    <div>
		<?php echo t('msg_select_data_access_type');?>: 
		<?php echo form_dropdown('formid', $this->form_list, get_form_value("formid",isset($formid) ? $formid : '')); ?>
        <input type="submit" value="<?php echo t('update');?>" name="submit"/>
    </div>
	<?php if (isset($this->form_message) ):?>
    	<div style="font-size:14px;font-weight:bold;padding:10px;background-color:#F2F2F2;border:1px solid gainsboro;margin-top:10px;margin-bottom:10px;"><?php echo $this->form_message;?></div>
    <?php endif;?>
</form>    
</div>
