<div class="field">
	<label for="title"><?php echo t('article_title');?></label>
	<input name="title" type="text" id="title" class="input-flex" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>
<div class="field">
    <label for="subtitle"><?php echo t('newspaper_title');?></label>
    <input name="subtitle" type="text" id="subtitle" size="50" class="input-flex"  value="<?php echo get_form_value('subtitle',isset($subtitle) ? $subtitle: ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<table border="0" class="inline-fields">
	<tr>
    <td>    
    <div class="field">
        <label for="periodical_month"><?php echo t('periodical_month');?></label>
        <input name="periodical_month" type="text" id="periodical_month" size="50" class="input-flex" value="<?php echo get_form_value('periodical_month',isset($periodical_month) ? $periodical_month : ''); ?>"/>
    </div>
    </td>
    <td>    
    <div class="field">
        <label for="periodical_day"><?php echo t('periodical_day');?></label>
        <input name="periodical_day" type="text" id="periodical_day" size="50" class="input-flex" value="<?php echo get_form_value('periodical_day',isset($periodical_day) ? $periodical_day: ''); ?>"/>
    </div>
    </td>
    <td>
     <div class="field">
        <label><?php echo t('publication_day_month_year');?></label>
        <input name="pub_day" class="input-flex" style="width:20px" type="text" maxlength="2" size="2" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/>&nbsp;-&nbsp;
		<input name="pub_month" type="text" class="input-flex" style="width:70px" maxlength="20"  size="10" value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/>&nbsp;-&nbsp;
        <input name="pub_year" type="text" class="input-flex" style="width:40px" size="2" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
    </div>  
    </td>    
</tr>
</table>

<table border="0" class="inline-fields">
	<tr>    
    <td>
    <div class="field">
        <label for="section"><?php echo t('section');?></label>
        <input name="volume" type="text" id="volume" size="10" maxlength="10" class="input-flex" value="<?php echo get_form_value('volume',isset($volume) ? $volume : ''); ?>"/>
    </div>
    </td>
    <td>
    <div class="field">
        <label for="page_from"><?php echo t('page');?></label>
        <input name="page_from" type="text" id="page_from" size="10" maxlength="10" class="input-flex" value="<?php echo get_form_value('page_from',isset($page_from) ? $page_from: ''); ?>"/>
    </div>
    </td>
    </tr>
</table>

<div class="field">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="input-flex"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>