<script type="text/javascript"> 
	if (top.frames.length!=0) {
		top.location=self.document.location;
	}
</script>	
<style>
.fp-form{
    width: 100%;
    max-width: 420px;
    padding: 15px;
    margin: auto;
}
.privacy-info{
    font-size:smaller;
}
</style>

<div class="fp-form">
<h1 class="page-title"><?php echo t('forgot_password');?></h1>
<?php if ($message):?>
	<div class="error"><?php echo $message;?></div>
<?php endif;?>

<p><?php echo t('enter_email_to_reset_password');?></p>

<form  method="post" class="form" autocomplete="off">        
<div style="padding:5px;">
<div class="form-group">
        <!--<label for="email"><?php echo t('email');?>:</label>-->
        <input class="form-control"  name="email" type="text" id="email"  value="" placeholder="<?php echo t('email');?>" />
    </div>
    <div class="text-center">
    <input type="submit" name="submit" value="<?php echo t('submit');?>" class="btn btn-primary btn-block"/> 
      
      </div>
      </div>      

      </div>

<?php echo form_close();?>