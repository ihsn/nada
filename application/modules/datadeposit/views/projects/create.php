<style type="text/css">
.contents{
		width:100%;
		min-height: 500px;
	}
.contents .field{
	margin:      15px 2px;	
}
.field input, .field textarea {
    margin-left: 20px;
    width:       45%;
}

.field label {
			font-weight: bold;
			font-size: 10pt;
}


.contents label{
		background:#CCC;
		display:block;
		margin:5px 0px;
		padding:3px;
		font-weight:bold;
	}
textarea{min-height:90px;}
</style>
     
     <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
     
    <h1><?php echo t('add_new_project');?></h1>
    
    <div class="contents">
		<?php echo form_open('projects/create');?>
        
        <div style="text-align:right;margin:5px 20px;">
            <input class="button" type="submit" name="create" value="Save" id="submit"/>
            <a class="btn_cancel" href="<?php echo site_url('projects');?>">Cancel</a>
        </div>

        <div class="field">
            <label for="title">Title:</label>
            <input type="text" name="title" id="title" class="input-flex" value="<?php echo get_form_value('title','');?>"/>
        </div>
        
        <div class="field">
            <label for="name">Shortname:</label>
            <input type="text" name="name" id="name" class="input-flex" value="<?php echo get_form_value('name','');?>"/>
        </div>	
        
        <div class="field">
            <label for="datatype">Type of Data:</label>
            <?php echo form_dropdown('datatype', $option_types, get_form_value('data_type','')); ?>
        </div>

        <div class="field">
            <label for="description">Description:</label>
            <textarea name="description" id="description" cols="30" rows="5" class="input-flex"><?php echo get_form_value('description','');?></textarea>
        </div>
        
        <div class="field">
            <label for="collaboration">Collaboration:</label>
            <input type="text" name="collaborators" id="collaboration" cols="30" rows="5" class="input-flex" value="<?php echo get_form_value('acesss','');?>"/>
        </div>
        <br/>
        <div style="text-align:right;margin:5px 20px;">
            <input class="button" type="submit" name="create" value="Save" id="submit"/>
            <a class="btn_cancel" href="<?php echo site_url('projects');?>">Cancel</a>
        </div>
        <?php echo form_close(); ?>
	</div>