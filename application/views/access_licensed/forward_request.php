<form class="form-group" name="form_fw_lic_request" id="form_fw_lic_request">
    <div class="form-group">
         <label><?php echo t('forward_lic_request');?></label>
    </div>

    <div class="form-group">        
        <label for="to"><?php echo t('to');?></label>
        <input name="to" type="text" class="form-control" value=""/>
    </div>
    <div class="form-group">        
        <label><?php echo t('cc');?> <?php echo t('use_comma_to_seperate_email');?></label>
        <input name="cc" type="text" class="form-control"/>
    </div>
    
    <div class="form-group">        
        <label><?php echo t('subject');?></label>
        <input name="subject" type="text" class="form-control" value="FWD: [#<?php echo $id; ?>] - <?php echo form_prep($request_title);?>"/>        
    </div>

    <div class="form-group">        
        <label><?php echo t('body');?></label>
        <textarea name="body" rows="5" class="form-control">your email message to the user...</textarea>
    </div>
    
    <span id="form_fw_lic_request_status"></span>
    
    <div class="form-group">        
        <input class="btn btn-primary" type="button" name="fwd_request" id="fwd_request" value="<?php echo t('send');?>" onclick="forward_mail(<?php echo $this->uri->segment(4); ?>);"/>
    </div>            
</form>
