<div class="field">
	<label for="title"><?php echo t('title');?></label>
	<input name="title" type="text" id="title" class="input-flex" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>

<div class="field">
	<label for="title"><?php echo t('conference_title');?></label>
	<input name="subtitle" type="text" id="subtitle" class="input-flex" value="<?php echo get_form_value('subtitle',isset($subtitle) ? $subtitle : ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<table border="0" class="inline-fields">
	<tr>    
    <td>
    <div class="field">
        <label for="place_publication"><?php echo t('publication_city');?></label>
        <input name="place_publication" type="text" id="place_publication" size="50" class="input-flex" value="<?php echo get_form_value('place_publication',isset($place_publication) ? $place_publication : ''); ?>"/>
    </div>
    </td>
    <td>
    <div class="field">
        <label for="place_state"><?php echo t('publication_state_country');?></label>
        <input name="place_state" type="text" id="place_state" size="50" class="input-flex" value="<?php echo get_form_value('place_state',isset($place_state) ? $place_state : ''); ?>"/>
    </div>
    </td>
    <td>
    <div class="field">
        <label><?php echo t('publication_day_month_year');?></label>
        <input name="pub_day" class="input-flex" style="width:40px" type="text" maxlength="10" size="10" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/>&nbsp;-&nbsp;
		<input name="pub_month" type="text" class="input-flex" style="width:70px" maxlength="20"  size="10" value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/>&nbsp;-&nbsp;
        <input name="pub_year" type="text" class="input-flex" style="width:40px" size="2" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
    </div>    
    </td>
    </tr>
</table>

<div class="field">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="input-flex"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>