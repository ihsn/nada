<?php
/**
* Menu Add/Edit form
*/
?>
<div class="content-container">
<div class="page-links">
	<a href="<?php echo site_url(); ?>/admin/menu/add" class="button"><img src="images/icon_plus.gif"/>Add new</a> 
    <a href="<?php echo site_url(); ?>/admin/menu/add/external" class="button"><img src="images/icon_plus.gif"/>Add external page</a> 
    <span class="button">Home</span>
</div>

<h1 class="page-title">Add files</h1>
<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=isset($this->error) ? $this->error : '';?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php echo form_open(current_url(), array('class'=>'form') ); ?>
    <div class="field">
        <label for="filepath">Provide path/url to the licensed files. The file paths will not be shown to the user.</label>
        <?php for($i=0;$i<10;$i++):?>
        <input class="input-flex" name="filepath[]" type="text" id="filepath<?php echo $i; ?>"  value="<?php echo isset($filepath[$i]) ? $filepath[$i]: ''; ?>"/>
        <?php endfor;?>
    </div>


<?php
 //edit user
 if (isset($id) )
 {
	echo form_submit('submit','Update'); 
 }
 else
 {
	echo form_submit('submit','Add'); 
 }
 	echo anchor('admin/menu','Cancel',array('class'=>'button') );	
?>

<?php echo form_close(); ?>    
</div>