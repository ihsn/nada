<div class="message" style="padding:10px;">
	<h1><?php echo t('thank_you');?></h1>
	<p><?php echo t('success_request_submitted');?></p>	
    <p><?php echo t('track_status_request');?>: <b><?php echo anchor('access_licensed/track/'.$this->uri->segment(3).'?print=yes', site_url().'/access_licensed/track/'.$this->uri->segment(3));?></b>.</p>
</div>
