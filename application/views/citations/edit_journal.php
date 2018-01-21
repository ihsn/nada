<div class="form-group">
	<label for="title"><?php echo t('article_title');?></label>
	<input name="title" type="text" id="title" class="form-control" value="<?php echo get_form_value('title',isset($title) ? $title : ''); ?>"/>
</div>
<div class="form-group">
    <label for="subtitle"><?php echo t('journal_title');?></label>
    <input name="subtitle" type="text" id="subtitle" size="50" class="form-control"  value="<?php echo get_form_value('subtitle',isset($subtitle) ? $subtitle: ''); ?>"/>
</div>

<?php echo form_author_field("author",'Author(s)'); ?>

 
<div class="form-group">
    <label for="volume"><?php echo t('volume');?></label>
    <input name="volume" type="text" id="volume" size="50" class="form-control" value="<?php echo get_form_value('volume',isset($volume) ? $volume : ''); ?>"/>
</div>
    
<div class="form-group">
    <label for="issue"><?php echo t('issue');?></label>
    <input name="issue" type="text" id="issue" size="50" class="form-control" value="<?php echo get_form_value('issue',isset($issue) ? $issue: ''); ?>"/>
</div>

<div class="form-group">
    <label><?php echo t('publication_day_month_year');?></label>
    <div class="form-inline">
    <input name="pub_day" class="form-control" style="width:20px" type="text" maxlength="2" size="2" value="<?php echo get_form_value('pub_day',isset($pub_day) ? $pub_day: ''); ?>"/>&nbsp;-&nbsp;
    <input name="pub_month" type="text" class="form-control" style="width:70px" maxlength="20"  size="10" value="<?php echo get_form_value('pub_month',isset($pub_month) ? $pub_month: ''); ?>"/>&nbsp;-&nbsp;
    <input name="pub_year" type="text" class="form-control"  size="2" maxlength="4"  value="<?php echo get_form_value('pub_year',isset($pub_year) ? $pub_year: ''); ?>"/>
    </div>
</div>  
    

<div class="row">
<div class="col-md-2">
<div class="form-group" style="padding-right:10px;">
    <label for="page_from"><?php echo t('page_from');?></label>
    <input name="page_from" type="text" id="page_from" size="10" maxlength="10" class="form-control" value="<?php echo get_form_value('page_from',isset($page_from) ? $page_from : ''); ?>"/>
</div>
</div>

<div class="col-md-2">
<div class="form-group">
    <label for="page_to"><?php echo t('page_to');?></label>
    <input name="page_to" type="text" id="page_to" size="10" maxlength="10" class="form-control" value="<?php echo get_form_value('page_to',isset($page_to) ? $page_to: ''); ?>"/>
</div>
</div>
</div>

<div class="form-group">
    <label for="idnumber"><?php echo t('periodical_number');?></label>
    <input name="idnumber" type="text" id="idnumber" size="10" maxlength="45" class="form-control" value="<?php echo get_form_value('idnumber',isset($idnumber) ? $idnumber: ''); ?>"/>
</div>
    

<div class="form-group">
	<label for="url"><?php echo t('url');?></label>
	<input name="url" type="text" id="url" size="50" class="form-control"  value="<?php echo get_form_value('url',isset($url) ? $url : ''); ?>"/>
</div>
