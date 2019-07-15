<div class="contents dd-form">

    <?php $message = isset($message) ? $message : $this->session->flashdata('message'); ?>
    <?php echo ($message != "") ? '<div class="success">' . $message . '</div>' : ''; ?> 

    <?php if (validation_errors()) : ?>
        <div class="error"><?php echo validation_errors(); ?></div>
    <?php endif; ?>

    <?php $error = $this->session->flashdata('error'); ?>
    <?php echo ($error != "") ? '<div class="error">' . $error . '</div>' : ''; ?>

    <form method="post">

    <div class="field">
        <label for="title"><span class="required">*</span>Title:</label>
        <p class="text_help"><?php echo t('create_title'); ?></p>
        <input type="text" name="title"  class="input-flex" value="<?php echo set_value('title', @$project->title); ?>"/>
    </div>

    <div class="field">
        <label for="name"><span class="required">*</span>Short name:</label>
        <p class="text_help"><?php echo t('create_short'); ?></p>
        <input type="text" name="name" style="width:30%" class="input-flex" value="<?php echo set_value('name', @$project->shortname); ?>"/>
    </div>	

    <div class="field">
        <label for="description">Description:</label>
        <p class="text_help"><?php echo t('create_desc'); ?></p>                        
        <textarea name="description" id="description" cols="30" rows="5" class="input-flex"><?php echo set_value('description', @$project->description); ?></textarea>
    </div>

    <div class="field">
        <label for="collaboration">Collaboration:</label>
        <p class="text_help"><?php echo t('create_collab'); ?></p>

        <div class="collaborators">
        <?php for($k=0;$k<4;$k++):?>        
            <input type="text" name="collaborator[]" value="<?php echo isset($_POST['collaborator'][$k]) ? form_prep($this->security->xss_clean($_POST['collaborator'][$k])) : @$project->collaborators[$k]; ?>" class="collaborator" />
        <?php endfor;?>
        </div>    
        
    </div>

    <input type="submit" name="submit" value="Save" class="submit-button"/>
    <input class="button" type="hidden" name="create" value="Save" />
    <a href="<?php echo site_url('datadeposit/projects'); ?>">Cancel</a>


    <div style="text-align:left">
        <input type="hidden" name="project_id" value="<?php echo set_value('project_id',@$project->id); ?>"/>
        <input class="button" type="hidden" name="update" value="Save" />
    </div>

</div>
</form>
</div>
