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
<div style="margin-bottom:10px;font-weight:bold"><?php echo t('request_status');?>: <em><?php echo t($status); ?></em></div>
	<?php echo form_open("admin/datadeposit/id/{$project[0]->id}");?>
        <div class="field action">
            <div>
                <b style="padding-right:15px;"><?php echo t('select_action');?></b>
                <label class="inline"><input type="radio" name="status" value="Draft" <?php echo ($status=='draft') ? 'checked="checked"' : ''; ?>/><?php echo t('draft');?></label>
                <label class="inline"><input type="radio" name="status" value="Closed"	<?php echo ($status=='closed') ? 'checked="checked"' : ''; ?>/><?php echo t('closed');?></label>                
                <label class="inline"><input type="radio" name="status" value="Accepted"	<?php echo ($status=='Accepted') ? 'checked="checked"' : ''; ?>/><?php echo t('accepted');?></label>

			</div>
        </div>    
        <div class="field">
            <label><b><?php echo t('comments');?></b> <!--<em><?php echo t('comments_visible_to_users');?></em>--></label>
            <textarea name="comments" rows="4" class="input-flex"><?php echo isset($project[0]->admin_comments) ? $project[0]->admin_comments : ''; ?></textarea>
        </div>
        
        <div class="field">
               <!--  <label for="notify"><input type="checkbox" name="notify" id="notify" value="1"/> <?php echo t('notify_user_by_email');?></label>           -->      
         </div>
                       
        <div id="status-text" style="margin-top:10px;margin-bottom:10px;"></div>
		<input type="submit" name="update" id="update" value="<?php echo t('update');?>" onclick="process_request(<?php echo $this->uri->segment(4); ?>);"/>        
		<?php echo form_close(); ?>
