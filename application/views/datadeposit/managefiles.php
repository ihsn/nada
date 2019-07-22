<style type="text/css">
input, textarea, select {
	margin-bottom: 15px;
}

.resource-file-name{
	background:#F2F2F2;
	padding:5px;
	font-weight:normal;
	font-size:smaller;
}
</style>

<?php 
		$dctypes_list=array(
			'Document, Questionnaire [doc/qst]'	=>'Questionnaire',
			'Document, Report [doc/rep]'		=> 'Report',
			'Document, Technical [doc/tec]'		=>	'Technical Document',
			'Audio [aud]'						=>	'Audio',
			'Map [map]'							=>	'Map',
			'Microdata File [dat/micro]'		=>	'Microdata File',
			'Photo [pic]'						=>	'Photo',
			'Program [prg]'						=>	'Program',
			'Table [tbl]'						=>	'Table',
			'Video [vid]'						=>	'Video',
			'Web Site [web]'					=>	'Web Site'
	);
	
?>

<h1 >Edit Resource <span class="resource-file-name"><?php echo $file[0]->filename;?></span></h1>
<form method="post">
	<input name="resource_id" type="hidden" id="resource_id" value=""/>
    
    <div class="field">
	<label for="dctype">Type</label>
    
    <select name="dctype">
        <option value="<?php echo $file[0]->dctype; ?>">--SELECT--</option>
        <?php foreach ($dctypes_list as $key=>$value):?>
                <option value='<?php echo $key;?>' <?php echo $file[0]->dctype==$key ? 'selected="selected"' : '';?>><?php echo $value;?></option>
            <?php endforeach;?>
    </select>
    </div>

    <div class="field">
        <label for="title">Title</label>
        <input name="title" type="text" id="title" class="input-flex" value="<?php echo set_value('title', @$file[0]->title); ?>"/>
    </div>

	<div class="field">
		<label for="description">Description</label>
		<textarea name="description" cols="30" rows="4" id="description" class="input-flex" ><?php echo set_value('description', @$file[0]->description); ?></textarea>
	</div>


	<div class="field" >
		<input type="submit" name="update" value="Submit" class="submit-button"/>
        <a class="btn_cancel" href="<?php echo site_url('datadeposit'); ?>/datafiles/<?php echo intval($file[0]->project_id); ?>">Cancel</a>
	</div>

</form>
