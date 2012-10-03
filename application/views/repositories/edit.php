<?php
$repo_types=array(
	'0'=>'Internal',
	'1'=>'External',
	'2'=>'System'
);
$options_published=array(
	'0'=>'Unpublish',
	'1'=>'Publish'
);

$options_section=$this->data['section_options'];

?>
<h1><?php echo $this->page_title;?></h1>
	
<?php if (validation_errors() ) : ?>
    <div class="error">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

<?php
	//form action url
	$uri_arr=$this->uri->segment_array();

	$form_action_url=site_url().'/admin/repositories/'.$this->uri->segment(3).'/';
	if ($this->uri->segment(3)=='add')
	{
		$form_action_url.='/add';
	}
	else
	{
		$form_action_url.=$this->uri->segment(4);
	}
?>
<?php echo form_open($form_action_url, array('class'=>'form') ); ?>
	<input type="hidden" name="id" value="<?php echo get_form_value('id',isset($id) ? $id : ''); ?>"/>

    <div class="field">
        <label for="pid"><?php echo t('select_repo_type');?></label>
        <?php echo form_dropdown('type', $repo_types,get_form_value('type',isset($this->data['type']) ? $this->data['type'] : ''));?>
    </div>  

    <div class="field">
        <label for="repositoryid"><?php echo t('repositoryid');?><span class="required">*</span></label>
        <?php echo form_input($this->data['repositoryid']);?>        
    </div>

    <div class="field">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <?php echo form_input($this->data['title']);?>        
    </div>
    
    <div class="field">
        <label for="url"><?php echo t('url');?><span class="required">*</span></label>
        <?php echo form_input($this->data['url']);?>        
    </div>

    <div class="field">
        <label for="organization"><?php echo t('organization');?><span class="required">*</span></label>
        <?php echo form_input($this->data['organization']);?>        
    </div>

    <div class="field">
        <label for="country"><?php echo t('country');?><span class="required">*</span></label>
        <?php echo form_input($this->data['country']);?>        
    </div>
    
    <div class="field">
        <label for="thumbnail"><?php echo t('thumbnail');?><span class="required">*</span></label>
        <?php echo form_input($this->data['thumbnail']);?>        
    </div>
    
     <div class="field">
        <label for="short_text"><?php echo t('short_description');?><span class="required">*</span></label>
        <?php echo form_textarea('short_text', get_form_value('short_text',isset($this->data['short_text']) ? $this->data['short_text'] : ''),'style="height:50px" class="input-flex"');?>
    </div>
    
    <div class="field">
        <label for="long_text"><?php echo t('long_description');?><span class="required">*</span></label>
        <?php echo form_textarea('long_text', get_form_value('long_text',isset($this->data['long_text']) ? $this->data['long_text'] : ''),'style="height:150px" class="input-flex"');?>
    </div>

    <div class="field">
        <label for="weight"><?php echo t('weight');?><span class="required">*</span></label>
        <?php echo form_input($this->data['weight']);?>        
    </div>

   <!--
    <div class="field">
        <label for="scan_interval"><?php echo t('scan_interval_in_days');?><span class="required">*</span></label>
        <?php echo form_input($this->data['scan_interval']);?>        
    </div>
	-->    

    <div class="field">
        <label for="section"><?php echo t('section');?><span class="required">*</span></label>
        <?php //form_dropdown('section', $options_section,get_form_value('section',isset($this->data['section']) ? $this->data['section'] : ''));?>
        <select name="section">
		<?php foreach($options_section as $section) : ?>
        	<option value="<?php echo $section['id'] ?>" ><?php echo $section['title']; ?></option>
        <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label for="ispublished"><?php echo t('published');?></label>
        <?php echo form_dropdown('ispublished', $options_published,get_form_value('ispublished',isset($this->data['ispublished']) ? $this->data['ispublished'] : ''));?>
    </div>  

    <p>
		<?php echo form_submit('submit', 'Submit');?>
     	<?php echo anchor('admin/repositories',t('cancel'),array('class'=>'button') );?>
    </p>
<?php echo form_close();?>