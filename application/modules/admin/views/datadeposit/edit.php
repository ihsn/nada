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
    
    <?php //echo $toolbar; ?>
    <h1><?php echo $title;//t('edit_project');?></h1>

    <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
    
    <div class="contents">
	<?php echo form_open("datadeposit/update/{$project[0]->id}");?>
    <div class="field">
		<label for="title">Title:</label>
		<input type="text" name="title" id="title" class="input-flex" value="<?php echo get_form_value('title',$project[0]->title); ?>"/>
	</div>
	
	<div class="field">
		<label for="name">Short name:</label>
		<input type="text" name="name" id="name" class="input-flex" value="<?php echo get_form_value('name',$project[0]->shortname); ?>"/>
	</div>	
    <?php /*
    <div class="field">
        <label for="datatype">Type of Data:</label>
        <?php echo form_dropdown('datatype', $option_types, get_form_value('datatype',isset($project[0]->data_type) ? $project[0]->data_type : ''));?>
    </div>
    */ ?>
    <!--
    <div class="field">
        <label for="collection">Collection:</label>
        <?php echo form_dropdown('collection', $option_formats, isset($dcformat) ? $dcformat : ''); ?>
    </div>
    -->
    <div class="field">
		<label for="description">Description:</label>
		<textarea name="description" id="description" cols="30" rows="5" class="input-flex"><?php echo get_form_value('description',$project[0]->description); ?></textarea>
	</div>
    
    <div class="field">
         <label for="collaboration">Collaboration:</label>
         <input type="text" name="access" id="collaboration" cols="30" rows="5" class="input-flex" value="<?php echo isset($project[0]->collaborators) ? get_form_value('collaboration',$project[0]->collaborators) : '';?>" />
    </div>
        
    <div class="width" style="width:33%;">
    <style type="text/css">
    	.field p {
    		position: relative;
    		top:      -23px;
    	}
    </style>
    <div class="field">
		<label>Project ID:</label>
		<p><?php echo $project[0]->id; ?></p>
	</div>
    
    <div class="field">
		<label>Created by:</label>
		<p><?php echo $project[0]->created_by; ?></p>
	</div>
    
    <div class="field">
		<label>Date created on:</label>
		<p><?php echo $project[0]->created_on ?></p>
	</div>
     
    <div class="field">
		<label>Status:</label>
		<p><?php echo $project[0]->status; ?></p>
	</div>
    </div>
	<br/>
	<div style="text-align:left">
    	<input type="hidden" name="project_id" value="<?php echo $project[0]->id; ?>"/>
		<input class="button" type="submit" name="update" value="Save" id="submit"/>
        <a class="btn_cancel" style="font-size:14px" href="<?php echo site_url('datadeposit/projects');?>">Cancel</a>

	</div>
	<?php echo form_close(); ?>
	</div>
<script>
</script>