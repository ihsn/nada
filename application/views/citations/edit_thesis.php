<div class="form-group">
	<label for="title"><?php echo t('title');?></label>
	<input name="title" type="text" id="title" class="form-control" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>

<div class="form-group">
	<label for="subtitle"><?php echo t('thesis_type');?></label>
	<input name="subtitle" type="text" id="subtitle" class="form-control" value="<?php echo get_form_value('subtitle',isset($subtitle) ? $subtitle : ''); ?>"/>
</div>

<div class="form-group">
	<label for="organization"><?php echo t('name_academic_institute');?></label>
	<input name="organization" type="text" id="organization" class="form-control" value="<?php echo get_form_value('organization',isset($organization) ? $organization : ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<div class="form-group">
    <label><?php echo t('thesis_prepare_year');?></label>
    <input name="pub_year" type="text" class="form-control" size="2" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
</div>

<div class="form-group">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
