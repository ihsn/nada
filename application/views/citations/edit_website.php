<div class="field">
    <label for="title"><?php echo t('title');?></label>
    <input name="title" type="text" id="title" size="50" class="input-flex"  value="<?php echo get_form_value('title',isset($title) ? $title: ''); ?>"/>
</div>

<div class="field">
    <label for="organization"><?php echo t('website_organization');?></label>
    <input name="organization" type="text" id="organization" size="50" class="input-flex"  value="<?php echo get_form_value('organization',isset($organization) ? $organization: ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<div class="field">
    <label for="data_accessed"><?php echo t('website_access_date');?></label>
    <input name="data_accessed" type="text" id="data_accessed" size="10" maxlength="10" class="input-flex" value="<?php echo get_form_value('data_accessed',isset($data_accessed) ? $data_accessed : ''); ?>"/>
</div>

<div class="field">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="input-flex"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>