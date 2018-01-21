<div class="form-group">
	<label for="title"><?php echo t('article_title');?></label>
	<input name="title" type="text" id="title" class="form-control" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>
<div class="form-group">
    <label for="subtitle"><?php echo t('newspaper_title');?></label>
    <input name="subtitle" type="text" id="subtitle" size="50" class="form-control"  value="<?php echo get_form_value('subtitle',isset($subtitle) ? $subtitle: ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<div class="content edit-newspaper top-margin-10">
	<div class="row">
    <table border="0" class="padding-table-rt" width="100%">
	<tr>    
    <td width="33%">
        <label for="periodical_month"><?php echo t('periodical_month');?></label>
        <input name="periodical_month" type="text" id="periodical_month" size="50" class="form-control" value="<?php echo get_form_value('periodical_month',isset($periodical_month) ? $periodical_month : ''); ?>"/>
    
    </td>
    <td width="33%">
        <label for="periodical_day"><?php echo t('periodical_day');?></label>
        <input name="periodical_day" type="text" id="periodical_day" size="50" class="form-control" value="<?php echo get_form_value('periodical_day',isset($periodical_day) ? $periodical_day: ''); ?>"/>
    
    </td>
    <td width="33%">
        <label><?php echo t('publication_day_month_year');?></label>
        <div class="row publications">
                    <div class="col-md-2">
                        <input name="pub_day" class="form-control" type="text" maxlength="2" size="2" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/></div>
                            <div class="col-md-1">&nbsp;-&nbsp;</div>
                            <div class="col-md-5"><input name="pub_month" type="text" class="form-control" maxlength="20"  size="10" value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/></div>
                            <div class="col-md-1">&nbsp;-&nbsp;</div>
                            <div class="col-md-2">
                        <input name="pub_year" type="text" class="form-control" size="2" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/></div>
    	</div> 
    </td>    
</tr>
</table>
</div>
</div>

<div class="content edit-newspaper top-margin-10">
	<div class="row">
    <table border="0" class="padding-table-rt" width="100%">
	<tr>    
    <td width="50%">
        <label for="section"><?php echo t('section');?></label>
        <input name="volume" type="text" id="volume" size="10" maxlength="10" class="form-control" value="<?php echo get_form_value('volume',isset($volume) ? $volume : ''); ?>"/>
    
    </td>
    <td width="50%">
        <label for="page_from"><?php echo t('page');?></label>
        <input name="page_from" type="text" id="page_from" size="10" maxlength="10" class="form-control" value="<?php echo get_form_value('page_from',isset($page_from) ? $page_from: ''); ?>"/>
    
    </td>
    </tr>
</table>
</div>
</div>

<div class="row top-margin-10 bottom-margin-10">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
