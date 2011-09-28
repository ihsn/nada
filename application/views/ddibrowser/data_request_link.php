<?php if ($this->harvested):?>
		<?php $obj_harvest=(object)$this->harvested;?>
		<!--data access -->
		<span>
		<?php if($obj_harvest->accesspolicy=='direct'): ?>
			<span><img src="images/form_direct.gif" /> <?php echo t('link_data_direct_hover');?></span>
		<?php elseif($obj_harvest->accesspolicy=='public'): ?>                    
			<span><img src="images/form_public.gif" /> <?php echo t('link_data_public_hover');?></span>
		<?php elseif($obj_harvest->accesspolicy=='licensed'): ?>
			<span><img src="images/form_licensed.gif" /> <?php echo t('link_data_licensed_hover');?></span>
		<?php elseif($obj_harvest->accesspolicy=='data_enclave'): ?>
			<span><img src="images/form_enclave.gif" /> <?php echo t('link_data_enclave_hover');?></span>
		<?php elseif($obj_harvest->accesspolicy=='remote'): ?>
				<span><img src="images/form_remote.gif" /> <?php echo t('link_data_remote_hover');?></span>
		<?php endif; ?>
		</span>            
<?php else:?>
		<!--data access -->
		<?php if ($this->survey['model']!=''):?>
		<span>
		<?php if($this->survey['model']=='direct'): ?>
			<a href="<?php echo site_url().'/access_direct/'.$this->survey['id'];?>" class="accessform" title="<?php echo t('link_data_direct_hover');?>">
			<span><img src="images/form_direct.gif" /> <?php echo t('request_microdata');?></span>
			</a>                    
		<?php elseif($this->survey['model']=='public'): ?>                    
			<a href="<?php echo site_url().'/access_public/'.$this->survey['id'];?>" class="accessform"  title="<?php echo t('link_data_public_hover');?>">
			<span><img src="images/form_public.gif" /> <?php echo t('request_microdata');?></span>
			</a>                    
		<?php elseif($this->survey['model']=='licensed'): ?>
			<a href="<?php echo site_url().'/access_licensed/'.$this->survey['id'];?>" class="accessform"  title="<?php echo t('link_data_licensed_hover');?>">
			<span><img src="images/form_licensed.gif" /> <?php echo t('request_microdata');?></span>
			</a>                    
		<?php elseif($this->survey['model']=='data_enclave'): ?>
			<a href="<?php echo site_url().'/access_enclave/'.$this->survey['id'];?>" class="accessform"  title="<?php echo t('link_data_enclave_hover');?>">
			<span><img src="images/form_enclave.gif" /> <?php echo t('request_microdata');?></span>
			</a>                    
		<?php elseif($this->survey['model']=='remote'): ?>
			<?php if (isset($this->survey['link_da']) && strlen($this->survey['link_da'])>1):?>
				<a target="_blank" href="<?php echo $this->survey['link_da'];?>"  title="<?php echo t('link_data_remote_hover');?>">
				<span><img src="images/form_remote.gif" /> <?php echo t('request_microdata');?></span>
				</a>                    
			<?php endif; ?>
		<?php endif; ?>
		</span>
		<?php endif;?>
<?php endif;?>