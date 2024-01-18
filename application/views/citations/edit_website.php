<div class="form-group">
    <label for="title"><?php echo t('title');?></label>
    <input name="title" type="text" id="title" size="50" class="form-control"  value="<?php echo get_form_value('title',isset($title) ? $title: ''); ?>"/>
</div>

<div class="form-group">
    <label for="organization"><?php echo t('website_organization');?></label>
    <input name="organization" type="text" id="organization" size="50" class="form-control"  value="<?php echo get_form_value('organization',isset($organization) ? $organization: ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>


<div class="row mt-3 mb-3">
    <div class="col-md-6">
        <div><label><?php echo t('publication_day_month_year');?></label></div>
        <div class="form-inline">
            <div class="form-group">                
                <div class="publications">
                    <input name="pub_day" placeholder="DD" class="form-control" type="text" size="2" maxlength="2" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/> / 
                    <input name="pub_month" placeholder="MM" type="text" class="form-control" size="10" maxlength="20"  value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/> / 
                    <input name="pub_year" placeholder="YYYY" type="text" class="form-control" size="4" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="data_accessed"><?php echo t('website_access_date');?></label>
            <input name="data_accessed"  type="text" id="data_accessed" size="10" maxlength="10" class="form-control" value="<?php echo get_form_value('data_accessed',isset($data_accessed) ? $data_accessed : ''); ?>"/>
        </div>
    </div>

</div>



<div class="form-group">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
