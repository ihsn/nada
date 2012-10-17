<style type="text/css">
.contents{
		width:100%;
		min-height: 500px;
	}
.contents .field{
	margin:15px 2px;	
}
.field input, .field textarea {
    margin-left: 20px;
    width:       45%;
}

div.width p, div.width em {
	margin:0;float:right;font-size:12pt
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
    
    <?php //echo $toolbar; ?>
    <h1 style="font-size:100%;font-style:italic"><?php echo $title;//t('edit_project');?></h1>

    <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
    
    <div class="contents">
	<?php echo form_open("projects/update/{$project[0]->id}");?>
	<div style="text-align:right;margin:5px 20px;">
    	<input type="hidden" name="project_id" value="<?php echo $project[0]->id; ?>"/>
		<input class="button" type="submit" name="update" value="Save" id="submit"/>
        <a class="btn_cancel" href="<?php echo site_url('projects');?>">Cancel</a>
	</div>
    <div class="field">
		<label for="title">Title:</label>
		<input type="text" name="title" id="title" class="input-flex" value="<?php echo get_form_value('title',$project[0]->title); ?>"/>
	</div>
	
	<div class="field">
		<label for="name">Shortname:</label>
		<input type="text" name="name" id="name" class="input-flex" value="<?php echo get_form_value('name',$project[0]->shortname); ?>"/>
	</div>	
    
    <div class="field">
        <label for="datatype">Type of Data:</label>
        <?php echo form_dropdown('datatype', $option_types, get_form_value('datatype',isset($project[0]->data_type) ? $project[0]->data_type : ''));?>
    </div>
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

    <div class="field">
		<span style="font-weight:bold;">Project ID:</span>
		<p><?php echo $project[0]->id; ?></p>
	</div>
    
    <div class="field">
		<span style="font-weight:bold;">Created by:</span>
		<p><?php echo $project[0]->created_by; ?></p>
	</div>
    
    <div class="field">
		<span style="font-weight:bold;">Date created on:</span>
		<p><?php echo $project[0]->created_on ?></p>
	</div>
     
    <div class="field">
		<span style="font-weight:bold;">Status:</span>
		<em><?php echo $project[0]->status; ?></em>
	</div>
    </div>
	<br/>
	<div style="text-align:right;margin:5px 20px;">
    	<input type="hidden" name="project_id" value="<?php echo $project[0]->id; ?>"/>
		<input class="button" type="submit" name="update" value="Save" id="submit"/>
        <a class="btn_cancel" href="<?php echo site_url('projects');?>">Cancel</a>
	</div>
	<?php echo form_close(); ?>
	</div>
<script>
    	$('textarea').ata();
</script>