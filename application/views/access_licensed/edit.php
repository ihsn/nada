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
.collapsed{border:1px solid gainsboro;margin-bottom:10px;padding:5px;overflow:auto;height:auto;}
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
.tab-content{
    padding-top:15px;
}
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
<div class="container-fluid">
<div class="col-md-12">
<div class="top-margin-10 pull-right">
<?php          
    echo anchor('admin/licensed_requests',t('return_request_home'),array('class'=>'btn btn-default') );	
?>
</div>
</div>

<?php if (validation_errors() ) : ?>
	<div class="alert alert-danger">
		<?php echo validation_errors(); ?>
	</div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<h1 class="page-title"><?php echo t('edit_licensed_request');?></h1> 
<div id="tabs">
	<ul class="nav nav-tabs" id="myTabs">
		<li class="active"><a class="active" role="presentation" href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-1" aria-controls="tabs-1" role="tab" data-toggle="tab"><?php echo t('request_information');?></a></li>
		<li><a role="presentation" href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-2" aria-controls="tabs-2" role="tab" data-toggle="tab"><?php echo t('tab_process');?></a></li>
		<li><a role="presentation" href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-3" aria-controls="tabs-3" role="tab" data-toggle="tab"><?php echo t('tab_communicate');?></a></li>
        <li><a role="presentation" href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-4" aria-controls="tabs-4" role="tab" data-toggle="tab"><?php echo t('tab_monitor');?></a></li>
        <li><a role="presentation" href="<?php echo site_url('admin/licensed_requests/edit/'.$this->uri->segment(4) );?>#tabs-5" aria-controls="tabs-5" role="tab" data-toggle="tab"><?php echo t('forward_lic_request');?></a></li>
	</ul>
    <div class="tab-content">
	<div role="tabpanel" class="tab-pane active" id="tabs-1">
		<?php $this->load->view('access_licensed/edit_request_view');?>	
	</div>
    
	<div role="tabpanel" class="tab-pane" id="tabs-2">
    	<div class="process-request">
    	<div class="bottom-margin-10"><strong><?php echo t('request_status');?>: <em><?php echo t($status); ?></em></strong></div>
		<form id="form_request_review" name="form_request_review" method="post" autocomplete="off" class="form-group">
        
        <div class="field action">
            <div>
           		<strong><?php echo t('select_action');?></strong>
                <label class="inline"><input type="radio" name="status" value="APPROVED" <?php echo ($status=='APPROVED') ? 'checked="checked"' : ''; ?>/> <?php echo t('approve');?></label>
                <label class="inline"><input type="radio" name="status" value="DENIED"	<?php echo ($status=='DENIED') ? 'checked="checked"' : ''; ?>/> <?php echo t('deny');?></label>
                <label class="inline"><input type="radio"  name="status" value="MOREINFO" <?php echo ($status=='MOREINFO') ? 'checked="checked"' : ''; ?>/> <?php echo t('request_more_info');?></label>
                <label class="inline"><input type="radio"  name="status" value="CANCELLED" <?php echo ($status=='CANCELLED') ? 'checked="checked"' : ''; ?>/> <?php echo t('cancel_authorization');?></label>
			</div>
        </div>    
        
        <div class="box-wrapper microdata-files field">
                <h3 class="expand"><?php echo t('grant_access_to_files');?></h3>
                <div class="collapsed">
			        <?php $this->load->view('access_licensed/edit_request_files',array('surveys'=>$surveys,'files'=>$files));?>
            	</div>
        </div>

        <div class="form-group">
            <label><b><?php echo t('comments');?></b> <em><?php echo t('comments_visible_to_users');?></em></label>
            <textarea name="comments" rows="9" class="form-control"><?php //echo isset($comments) ? $comments : ''; ?></textarea>
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

		<div id="status-text" class="bottom-margin-10"></div>

        <div class="field gray-bg">
            <label for="notify">
                <input type="checkbox" name="notify" id="notify" value="1" checked="checked"/> <?php echo t('notify_user_by_email');?>
            </label>
            <div>
            <input type="button" name="update" class="btn btn-primary" id="update" value="<?php echo t('update');?>" onclick="process_request(<?php echo $this->uri->segment(4); ?>);"/>
                <a href="<?php echo site_url('admin/licensed_requests');?>"><?php echo t('cancel');?></a>
            </div>
         </div>

        
		</form>
        
        
        </div>
    </div>
    
	<div role="tabpanel" class="tab-pane" id="tabs-3">
		<form class="form-group" name="form_compose_email" id="form_compose_email">
            <div class="form-group">
                 <label><?php echo t('compose_email');?></label>
            </div>
        
            <div class="form-group">        
                <label for="to"><?php echo t('to');?></label>
                <input name="to" type="text" class="form-control" value="<?php echo isset($email) ? $email : ''; ?>"/>
            </div>
            <div class="form-group">        
                <label><?php echo t('cc');?> <?php echo t('use_comma_to_seperate_email');?></label>
                <input name="cc" type="text" class="form-control"/>
            </div>
            <div class="form-group">        
                <label><?php echo t('subject');?></label>
                <input name="subject" type="text" class="form-control" value="RE: [#<?php echo $id; ?>] - <?php echo form_prep($request_title);?>"/>
            </div>
    
            <div class="form-group">        
                <label><?php echo t('body');?></label>
                <textarea name="body" rows="5" class="form-control">your email message to the user...</textarea>
            </div>
			
            <span id="form_compose_email_status"></span>
            
            <div class="form-group">        
            	<input type="button" class="btn btn-primary" name="send" id="send" value="<?php echo t('send');?>" onclick="send_mail(<?php echo $this->uri->segment(4); ?>);"/>
            </div>            
        </form>
        
        <div>
        	<h3><?php echo t('communicate_history');?></h3>
        	<div><?php $this->load->view('access_licensed/email_history');?></div>
        </div>

        
	</div>

	<div role="tabpanel" class="tab-pane" id="tabs-4">
		<?php echo $download_log;?>
	</div>
    
	<div role="tabpanel" class="tab-pane"  id="tabs-5">
		<?php $this->load->view("access_licensed/forward_request");?>
        
        <div>
        	<h3><?php echo t('forward_history');?></h3>
        	<div><?php $this->load->view('access_licensed/forward_history');?></div>
        </div>
	</div>
    
</div>
