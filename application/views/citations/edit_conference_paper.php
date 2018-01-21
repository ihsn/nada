<div class="form-group">
	<label for="title"><?php echo t('title');?></label>
	<input name="title" type="text" id="title" class="form-control" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>

<div class="form-group">
	<label for="title"><?php echo t('conference_title');?></label>
	<input name="subtitle" type="text" id="subtitle" class="form-control" value="<?php echo get_form_value('subtitle',isset($subtitle) ? $subtitle : ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<div class="content edit-corporate-author top-margin-10">
	<div class="row">
    <table border="0" class="padding-table-rt">
	<tr>    
    <td width="33%">
    
        <label for="place_publication"><?php echo t('publication_city');?></label>
        <input name="place_publication" type="text" id="place_publication" size="50" class="form-control" value="<?php echo get_form_value('place_publication',isset($place_publication) ? $place_publication : ''); ?>"/>
    
    </td>
    <td width="33%">
        <label for="place_state"><?php echo t('publication_state_country');?></label>
        <input name="place_state" type="text" id="place_state" size="50" class="form-control" value="<?php echo get_form_value('place_state',isset($place_state) ? $place_state : ''); ?>"/>
    
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

<div class="row top-margin-10 bottom-margin-10">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
