<div class="form-group">
	<label for="title"><?php echo t('title');?></label>
	<input name="title" type="text" id="title" class="form-control" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

<div class="content edit-book">
	<div class="row">
	<div class="col-md-2">
	<div class="form-group">
        <label for="edition"><?php echo t('edition');?></label>
        <input name="edition" type="text" id="editon" class="form-control"  value="<?php echo get_form_value('edition',isset($edition) ? $edition: ''); ?>"/>
    </div>
    </div>
    <div class="col-md-3">   
    <div class="field">
        <label for="volume"><?php echo t('volume');?></label>
        <input name="volume" type="text" id="volume" class="form-control" value="<?php echo get_form_value('volume',isset($volume) ? $volume : ''); ?>"/>
    </div>
    </div>

	<div class="col-md-4">
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

    <div class="col-md-3">
    <div class="form-group">
        <label for="idnumber"><?php echo t('periodical_number');?></label>
        <input name="idnumber" type="text" id="idnumber" size="10" maxlength="44" class="form-control" value="<?php echo get_form_value('idnumber',isset($idnumber) ? $idnumber: ''); ?>"/>
    </div>
    </div>

</div>
</div>


<div class="content">
	<div class="row">
	<div class="col-md-4">
	<div class="form-group" style="padding-right:10px;">
        <label for="publisher"><?php echo t('publisher');?></label>
        <input name="publisher" type="text" id="publisher" size="50" class="form-control" value="<?php echo get_form_value('publisher',isset($publisher) ? $publisher : ''); ?>"/>        
    </div>
    </div>
    <div class="col-md-4">
	<div class="form-group" style="padding-right:10px;">
        <label for="place_publication"><?php echo t('publication_city');?></label>
        <input name="place_publication" type="text" id="place_publication" size="50" class="form-control" value="<?php echo get_form_value('place_publication',isset($place_publication) ? $place_publication : ''); ?>"/>
    </div>
    </div>
    <div class="col-md-4">
	<div class="form-group">
        <label for="place_state"><?php echo t('publication_state_country');?></label>
        <input name="place_state" type="text" id="place_state" size="50" class="form-control" value="<?php echo get_form_value('place_state',isset($place_state) ? $place_state : ''); ?>"/>
    </div>
    </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="url"><?php echo t('url');?></label>
                <input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
            </div>
        </div>
    </div>
    </div>



