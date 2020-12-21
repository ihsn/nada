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
    <div class="col-md-6">
	<div class="form-group">
    
        <label for="place_publication"><?php echo t('publication_city');?></label>
        <input name="place_publication" type="text" id="place_publication" size="50" class="form-control" value="<?php echo get_form_value('place_publication',isset($place_publication) ? $place_publication : ''); ?>"/>
    
    </div>
    </div>
    <div class="col-md-6">
	<div class="form-group">
        <label for="place_state"><?php echo t('publication_state_country');?></label>
        <input name="place_state" type="text" id="place_state" size="50" class="form-control" value="<?php echo get_form_value('place_state',isset($place_state) ? $place_state : ''); ?>"/>
    
  </div>
    </div>
    <div class="col-md-12 mt-3">
        <div class="form-inline">
        <div class="form-group">
            <label><?php echo t('publication_day_month_year');?></label>
            <div class="publications">
                <input name="pub_day" class="form-control" type="text" size="1" maxlength="2" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/>/
                <input name="pub_month" type="text" class="form-control" size="10" maxlength="20"  value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/>/
                <input name="pub_year" type="text" class="form-control" size="4" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
            </div>
        </div>
        </div>
        </div>
</div>
</div>

<div class="row">
    <div class="col-md-12 mt-3">
    <div class="form-group">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
</div>
</div>
