<style type="text/css">
.contents{
		width:100%;
		min-height: 500px;
	}
.contents .field{
	margin:15px 2px;	
}

div.width p, div.width em {
	margin:0;float:right;font-size:11pt!important;font-style:normal;
}

.field label {
			font-weight: bold;
			font-size: 10pt;
}

textarea{min-height:90px;}
</style>
    
    <div class="contents">
    
		<?php $message=isset($message)?$message:$this->session->flashdata('message');?>
        <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
        
        <?php if (validation_errors() ) : ?>
            <div class="error"><?php echo validation_errors(); ?></div>
        <?php endif; ?>
        
        <?php $error=$this->session->flashdata('error');?>
        <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>
        
		<?php echo form_open("datadeposit/update/{$project[0]->id}");?>
        
        <div class="field">
            <label for="title"><span class="required">*</span>Title:</label>
            <p class="text_help"><?php echo t('create_title'); ?></p>
            <input type="text" name="title"  class="input-flex" value="<?php echo get_form_value('title',$project[0]->title); ?>"/>
        </div>
        
        <div class="field">
            <label for="name"><span class="required">*</span>Short name:</label>
            <p class="text_help"><?php echo t('create_short'); ?></p>            
            <input type="text" name="name" style="width:30%" class="input-flex" value="<?php echo get_form_value('name',$project[0]->shortname); ?>"/>
        </div>	

        <div class="field">
            <label for="description">Description:</label>
            <p class="text_help"><?php echo t('create_desc'); ?></p>                        
            <textarea name="description" id="description" cols="30" rows="5" class="input-flex"><?php echo get_form_value('description',$project[0]->description); ?></textarea>
        </div>
        
        <div class="field">
            <label for="collaboration">Collaboration:</label>
            <p class="text_help"><?php echo t('create_collab'); ?></p>
            <?php 
				$count  = 4;
				$count -= sizeof($collaborators);
				if (!empty($collaborators)) {
					$x = 0;
					foreach($collaborators as $key => $value) {
					?>
            <input type="text" <?php if ($project[0]->access != 'owner') echo 'disabled="disabled"'; ?> name="collaborators[<?php echo $key ?>]" value="<?php echo get_form_value('collaborators['.$key.']',isset($collaborators[$key]) ? $collaborators[$key] : '' );?>" class="input-flex" style="width:25%" /> 
			<?php
						$x++;
						if (!($x % 2)) echo '<br />';
					}
				} if ($count > 0) {
					while ($count--) {
					?>
         	<input type="text" <?php if ($project[0]->access != 'owner') echo 'disabled="disabled"'; ?> value="<?php echo get_form_value('new_collab[]', '')?>" name="new_collab[]" class="input-flex" style="width:25%" /> 
            <?php
						if (!($count % 2)) echo '<br />';
					}
				}
				?>
        </div>

	<input type="submit" name="submit" value="Save" class="submit-button"/>
	<input class="button" type="hidden" name="create" value="Save" />
        <a href="<?php echo site_url('datadeposit/projects');?>">Cancel</a>
        

	<div style="text-align:left">
    	<input type="hidden" name="project_id" value="<?php echo $project[0]->id; ?>"/>
		<input class="button" type="hidden" name="update" value="Save" />
	</div>
    
    </div>
	<?php echo form_close(); ?>
	</div>
