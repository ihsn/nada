<style type="text/css">
.contents{
		width:50%;
		min-height: 500px;
	}
label{margin:5px 10px;}
fieldset{margin:10px;}
</style>
<div style="text-align:right;">
<!--<a class="btn_cancel" href="<?php echo site_url("datadeposit/study_description/$id");?>">Go Back</a>-->
<a class="btn_cancel" href="<?php echo site_url("datadeposit");?>">Go Back</a>
</div>
<div id="feedback_header">
<h1 class="page-title"><?php echo  $title, ' - Feedback';?></h1> 
</div>
<div class="contents">
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 

<!--
<?php echo form_open(site_url("feedback/submit/$id"), array('class'=>'form') ); ?>
    
    <div class="field">
    <label for="title"><?php echo t('title');?>:</label>
    <input name="title" type="text" id="title" class="input-flex" value=""/>
    </div>
    
    <div class="field">
        <label for="message"><?php echo t('message');?>:</label>
        <textarea name="message" cols="70" rows="100" id="message" class="input-flex" ></textarea>
    </div>

    <div class="field" style="text-align:left;margin:5px 20px;">
        <input class="button" type="submit" name="submit_feedback" value="Submit" id="submit"/>
        <a class="btn_cancel" href="<?php echo current_url();?>">Cancel</a>
    </div>
<?php echo form_close();?>
-->  

     <?php if(!empty($feed)):?>
	 <?php $i=1; foreach($feed as $feedback):?>
     
     <fieldset>
     <legend><?php echo "#". $i; ?></legend>
     <div style="text-align:left;margin-bottom:25px">
     <?php echo "Posted by ".$feedback->created_by." , <em>".$feedback->created_on."</em>"; ?>
     </div>
     <p style="margin: 0 0 15px 20px"><?php echo $feedback->message; ?></p>
     </fieldset>

     <?php $i++; endforeach;?>
     
     <?php else: ?>
     <p>There is no feedback for this project.</p>
     <?php endif; ?>
 	
</div>
<div style="text-align:right;">
<a class="btn_cancel" href="<?php echo site_url("datadeposit/home");?>">Go Back</a>
</div>