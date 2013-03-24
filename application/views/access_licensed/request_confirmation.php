<div class="message" style="padding:10px;">
	<h2><?php echo t('thank_you');?></h2>
	<p><?php echo t('success_request_submitted');?></p>	
    <?php if ($this->input->get_post("ajax")):?>
    	<p><?php echo t('track_status_request');?>: <b><?php echo anchor('access_licensed/track/'.$request_id.'?print=yes', site_url().'/access_licensed/track/'.$request_id);?></b>.</p>
    <?php else:?>
	    <p><?php echo t('track_status_request');?>: <b><?php echo anchor('access_licensed/track/'.$request_id, site_url().'/access_licensed/track/'.$request_id);?></b>.</p>
    <?php endif;?>
</div>
