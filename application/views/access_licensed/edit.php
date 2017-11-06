<style>
.error-msg{color:red;}
.process-request .form .field{margin-bottom:20px;}
.fieldset{padding:10px;width:99%;}
h2{font-weight:bold; font-size:14px;}
.email-fieldset{padding:10px;}
.action{padding: 10px;
border: 1px solid #C1DAD7;
padding-bottom: 10px;
background: #ECF5F4;}
.action label.inline{margin-right:10px;font-weight:normal;display:inline}


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
.collapse{border:1px solid gainsboro;margin-bottom:10px;padding:5px;overflow:auto;height:auto;}
h3{font-size:1em;font-weight:bold;}
.box-wrapper{margin-bottom:10px;margin-top:10px;}
/* end styles for expand/collapse */

.comments_history table {font-size:smaller;}

.comments_history legend{font-weight:bold;}
.collapse_div{display:none;}
a.view_comments{color:#08C;font-size:10px;margin-right:5px;cursor:pointer;}

.message-body{display:none;font-size:smaller;}
.open .subject{display:none;}
.email-field {display:inline-block;width:100px;}
.view-email, .view-email-forward{cursor:pointer;}
.view-email .email_body, .view-email-forward .email_body{margin-top:15px;}
.view-email a.subject, .view-email-forward a.subject{color:#08C;}
.ui-widget{font-size:14px;font-family:"Helvetica Neue", Helvetica, Arial, sans-serif}
.microdata-files .grid-table .header th{font-weight:normal;}
.microdata-files .file-settings{
	font-size: smaller;
	background-color: #F5F5F2;
	color: #575252;
}


.field-expanded,.always-visible{background-color:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;margin-right:8px;}
.always-visible{padding:10px;}
.field-expanded .field, .always-visible .field {padding:5px;}
.field-expanded legend, .field-collapsed legend, .always-visible legend{background:white;padding-left:5px;padding-right:5px;font-weight:bold; cursor:pointer}
.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
.field-collapsed legend {background-position:left top; background-repeat:no-repeat;}
.field-collapsed .field{display:none;}
.field-expanded .field label, .always-visible label{font-weight:normal;}
.study-notes {font-size:small;}
</style>

<script type="text/javascript">
$(function() {
	$('.view_comments').click(function(){
		$(".comments_history").toggleClass("collapse_div");
	});

	$('.view-email').click(function(e){
		$(this).toggleClass("open");
		$(this).parent().find(".message-body").toggle();
	});
	
	$('.view-email-forward').click(function(e){
		$(this).toggleClass("open");
		$(this).parent().find(".message-body").toggle();
	});

	$('.field-expanded > legend').click(function(e) {
			e.preventDefault();
			$(this).parent('fieldset').toggleClass("field-collapsed");
			return false;
	});
	

	$('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');
		
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
		<li><a href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-1"><?php echo t('request_information');?></a></li>
		<li><a href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-2"><?php echo t('tab_process');?></a></li>
		<li><a href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-3"><?php echo t('tab_communicate');?></a></li>
        <li><a href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-4"><?php echo t('tab_monitor');?></a></li>
        <li><a href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-5"><?php echo t('forward_lic_request');?></a></li>
	</ul>
	<div id="tabs-1">
		<?php $this->load->view('access_licensed/edit_request_view');?>	
	</div>
    
	<div id="tabs-2">
    	<div class="process-request">
    	<div style="margin-bottom:10px;font-weight:bold"><?php echo t('request_status');?>: <em><?php echo t($status); ?></em></div>
		<form id="form_request_review" name="form_request_review" method="post" autocomplete="off" class="form">
        
        <div class="field action">
            <div>
           		<span style="font-weight:bold;"><?php echo t('select_action');?></span>
                <label class="inline"><input type="radio" name="status" value="APPROVED" <?php echo ($status=='APPROVED') ? 'checked="checked"' : ''; ?>/><?php echo t('approve');?></label>
                <label class="inline"><input type="radio" name="status" value="DENIED"	<?php echo ($status=='DENIED') ? 'checked="checked"' : ''; ?>/><?php echo t('deny');?></label>
                <label class="inline"><input type="radio"  name="status" value="MOREINFO" <?php echo ($status=='MOREINFO') ? 'checked="checked"' : ''; ?>/><?php echo t('request_more_info');?></label>
                <label class="inline"><input type="radio"  name="status" value="CANCELLED" <?php echo ($status=='CANCELLED') ? 'checked="checked"' : ''; ?>/><?php echo t('cancel_authorization');?></label>
			</div>
        </div>    
        
        <div class="box-wrapper microdata-files field">
                <h3 class="expand"><?php echo t('grant_access_to_files');?></h3>
                <div class="collapse">
			        <?php $this->load->view('access_licensed/edit_request_files',array('surveys'=>$surveys,'files'=>$files));?>
            	</div>
        </div>

        <div class="field">
            <label><b><?php echo t('comments');?></b> <em style="font-weight:normal"><?php echo t('comments_visible_to_users');?></em></label>
            <textarea name="comments" rows="9" class="input-flex"><?php //echo isset($comments) ? $comments : ''; ?></textarea>
        </div>

        <?php if (!isset($comments_history) || count($comments_history)>0): ?>
        <fieldset class="comments_history field-expanded">
            <legend><?php echo t('request_history');?></legend>
            <div class="field" ><?php $this->load->view('access_licensed/comments_history',array('files'=>$files));?></div>
        </fieldset>   
		<?php endif;?>
		
		<?php if (isset($study_notes) && count($study_notes)>0):?>
        <fieldset class="study_notes field-expanded">
            <legend><?php echo t('study_notes');?></legend>
            <div class="field" ><?php $this->load->view('access_licensed/study_notes',array('study_notes'=>$study_notes));?></div>
        </fieldset>
        <?php endif;?>   

		<div id="status-text" style="margin-bottom:10px;"></div>

        <div class="field" style="margin-top:20px;background:#F5F2F2;padding:5px;">
                 <label for="notify" style="display:inline;margin-right:20px;"><input type="checkbox" name="notify" id="notify" value="1" checked="checked"/> <?php echo t('notify_user_by_email');?></label>
                 <input type="button" name="update" id="update" value="<?php echo t('update');?>" onclick="process_request(<?php echo $this->uri->segment(4); ?>);"/>
                 <a href="<?php echo site_url('admin/licensed_requests');?>"><?php echo t('cancel');?></a>
         </div>

        
		</form>
        
        
        </div>
    </div>
    
	<div id="tabs-3">
		<form class="form" name="form_compose_email" id="form_compose_email">
            <div class="field">
                 <label><?php echo t('compose_email');?></label>
            </div>
        
            <div class="field">        
                <label for="to"><?php echo t('to');?></label>
                <input name="to" type="text" class="input-flex" value="<?php echo isset($email) ? $email : ''; ?>"/>
            </div>
            <div class="field">        
                <label><?php echo t('cc');?> <?php echo t('use_comma_to_seperate_email');?></label>
                <input name="cc" type="text" class="input-flex"/>
            </div>
            <div class="field">        
                <label><?php echo t('subject');?></label>
                <input name="subject" type="text" class="input-flex" value="RE: [#<?php echo $id; ?>] - <?php echo form_prep($request_title);?>"/>
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
        
        <div>
        	<h3><?php echo t('communicate_history');?></h3>
        	<div><?php $this->load->view('access_licensed/email_history');?></div>
        </div>

        
	</div>

	<div id="tabs-4">
		<?php echo $download_log;?>
	</div>
    
	<div id="tabs-5">
		<?php $this->load->view("access_licensed/forward_request");?>
        
        <div>
        	<h3><?php echo t('forward_history');?></h3>
        	<div><?php $this->load->view('access_licensed/forward_history');?></div>
        </div>
	</div>
    
</div>