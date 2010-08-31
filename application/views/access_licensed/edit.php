<style>
.fieldset{padding:10px;width:99%;}
h2{font-weight:bold; font-size:14px;}
.email-fieldset{padding:10px;}
x.email-fieldset .field{margin-bottom:10px;font-size:11px;}
.action{background-color:#EAEAEA;padding:10px; border:1px solid gainsboro;padding-bottom:10px;}
.action label.inline{margin-right:10px;font-weight:bold;display:inline}


/* styles for expand/collapse */
.expand a {
  display:block;
  padding:3px 10px;
  background-color:gainsboro;
  text-decoration:none;
  color:black;
}
.expand a:link, .expand a:visited {
  border:1px solid gainsboro;
  background-image:url(images/down.gif);
  background-repeat:no-repeat;
  background-position:99.5% 50%;
}
.expand a:hover, .expand a:active, .expand a:focus {
  text-decoration:underline
}
.expand a.open:link, .expand a.open:visited {
  border-style:solid;
  background:#eee url(images/up.gif) no-repeat 99.5% 50%
}
.collapse{border:1px solid gainsboro;margin-bottom:10px;padding:5px;}
h3{font-size:1em;font-weight:bold;}
.box-wrapper{margin-bottom:10px;margin-top:10px;}
/* end styles for expand/collapse */
</style>

<script type="text/javascript">
$(function() {
    $("h3.expand").toggler({speed: "fast"});
	$('.collapsed').toggle();
});
</script>


<?php
/**
* Licensed request edit form
*/
?>
<div class="content-container">
<div style="text-align:right;margin-top:10px;">
<?php          
    echo anchor('admin/licensed_requests',t('return_request_home'),array('class'=>'button') );	
?>
</div>

<?php if (validation_errors() ) : ?>
	<div class="error">
		<?php echo validation_errors(); ?>
	</div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('edit_licensed_request');?></h1> 
<script type="text/javascript">
	jQuery(document).ready(function(){
		$("#tabs").tabs();
	});
</script>
<div id="tabs">
	<ul>
		<li><a href="#tabs-1"><?php echo t('request_information');?></a></li>
		<li><a href="#tabs-2"><?php echo t('tab_process');?></a></li>
		<li><a href="#tabs-3"><?php echo t('tab_communicate');?></a></li>
        <li><a href="#tabs-4"><?php echo t('tab_monitor');?></a></li>
	</ul>
	<div id="tabs-1">
		<?php $this->load->view('access_licensed/edit_request_view');?>	
	</div>
    
	<div id="tabs-2">
    	<div style="margin-bottom:10px;font-weight:bold"><?php echo t('request_status');?>: <em><?php echo t($status); ?></em></div>
		<form id="form_request_review" name="form_request_review" method="post" autocomplete="off" class="form">        
        <div class="field action">
            <div>
                <b style="padding-right:15px;"><?php echo t('select_action');?></b>
                <label class="inline"><input type="radio" name="status" value="APPROVED" <?php echo ($status=='APPROVED') ? 'checked="checked"' : ''; ?>/><?php echo t('approve');?></label>
                <label class="inline"><input type="radio" name="status" value="DENIED"	<?php echo ($status=='DENIED') ? 'checked="checked"' : ''; ?>/><?php echo t('deny');?></label>
                <label class="inline"><input type="radio"  name="status" value="MOREINFO" <?php echo ($status=='MOREINFO') ? 'checked="checked"' : ''; ?>/><?php echo t('request_more_info');?></label>
                <label class="inline"><input type="radio"  name="status" value="CANCELLED" <?php echo ($status=='CANCELLED') ? 'checked="checked"' : ''; ?>/><?php echo t('cancel_authorization');?></label>
			</div>
        </div>    
        <div class="field">
            <label><b><?php echo t('comments');?></b> <em><?php echo t('comments_visible_to_users');?></em></label>
            <textarea name="comments" rows="4" class="input-flex"><?php echo isset($comments) ? $comments : ''; ?></textarea>
        </div>

        <div class="box-wrapper">
                <h3 class="expand"><?php echo t('grant_access_to_files');?></h3>
                <div class="collapse">
			        <?php $this->load->view('access_licensed/edit_request_files',array('files'=>$files));?>
            	</div>
        </div>
        
        <div class="field">
                 <label for="notify"><input type="checkbox" name="notify" id="notify" value="1"/> <?php echo t('notify_user_by_email');?></label>                
         </div>
                       
        <div id="status-text" style="margin-top:10px;margin-bottom:10px;"></div>
		<input type="button" name="update" id="update" value="<?php echo t('update');?>" onclick="process_request(<?php echo $this->uri->segment(4); ?>);"/>        
		</form>
    </div>
    
	<div id="tabs-3">
		<form class="form" name="form_compose_email" id="form_compose_email">
            <div class="field">
                 <label><?php echo t('compose_email');?></label>
            </div>
        
            <div class="field">        
                <label for="to"><?php echo t('to');?></label>
                <input name="to" type="text" class="input-flex" value="<?php echo $email; ?>"/>
            </div>
            <div class="field">        
                <label><?php echo t('cc');?> <?php echo t('use_comma_to_seperate_email');?></label>
                <input name="cc" type="text" class="input-flex"/>
            </div>
            <div class="field">        
                <label><?php echo t('subject');?></label>
                <input name="subject" type="text" class="input-flex" value="RE: [#<?php echo $id; ?>] - Application for request to a licensed dataset"/>
            </div>
    
            <div class="field">        
                <label><?php echo t('body');?></label>
                <textarea name="body" rows="5" class="input-flex">your email message to the user...</textarea>
            </div>
			
            <span id="form_compose_email_status"></span>
            
            <div class="field">        
            	<input type="button" name="send" id="send" value="<?php echo t('send');?>" onclick="send_mail(<?php echo $this->uri->segment(4); ?>);"/>
            </div>            
        </form>
	</div>

	<div id="tabs-4">
		<?php echo $download_log;?>
	</div>
</div>