<style>
.file{border:1px solid gainsboro;}
.repo-thumbnail{float:left;width:82px;height:82px;overflow:hidden;border:1px solid gainsboro;margin-right:20px;}
.repo-box-1{border:1px solid gainsboro;overflow:auto;padding:10px;margin-right:8px;
background-color: #F8F8F8;
border: 1px solid gainsboro;
margin-top: 5px;
margin-bottom: 10px;
margin-right: 8px;
}
.repo-box-1 legend{font-weight:bold;}
.repo-box-1 .repo-file-upload{float:left;width:450px}
.repo-about-photo{float:left;width:120px;height:82px;overflow:hidden;border:1px solid gainsboro;margin-right:20px;}
/*.repo-box-1 .repo-file-upload input{width:60%}
.repo-box-1 .repo-file-upload label{display:inline;}*/
.fixed-100 input{width:100px;}
.fixed-200 input{width:200px;}
</style>
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

$sections=$this->data['section_options'];
$options_section=array();

foreach($sections as $sec)
{
	$options_section[$sec['id']]=$sec['title'];
}

?>

<?php $this->load->view('repositories/page_links'); ?>
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
<?php echo form_open_multipart($form_action_url, array('class'=>'form') ); ?>
	<input type="hidden" name="id" value="<?php echo get_form_value('id',isset($id) ? $id : ''); ?>"/>

	<table style="width:99%;">
    <tr>
    <td>
    <div class="field fixed-200">
        <label for="repositoryid"><?php echo t('repositoryid');?><span class="required">*</span></label>
        <?php echo form_input($this->data['repositoryid']);?>        
    </div>
	</td>
    <td style="width:100%;">
    <div class="field">
        <label for="title"><?php echo t('title');?><span class="required">*</span></label>
        <?php echo form_input($this->data['title']);?>        
    </div>
    </td>
    </tr>
    </table>
    <?php /*
    <div class="field">
        <label for="thumbnail"><?php echo t('thumbnail');?><span class="required">*</span></label>
        <?php echo form_input($this->data['thumbnail']);?>        
    </div>
	*/ ?>

     <div class="field">
        <label for="short_text"><?php echo t('short_description');?><span class="required">*</span></label>
        <?php echo form_textarea('short_text', get_form_value('short_text',isset($this->data['short_text']) ? $this->data['short_text'] : ''),'style="height:50px" class="input-flex"');?>
    </div>
    
    <div class="field">
        <label for="long_text"><?php echo t('long_description');?><span class="required">*</span></label>
        <?php echo form_textarea('long_text', get_form_value('long_text',isset($this->data['long_text']) ? $this->data['long_text'] : ''),'style="height:150px" class="input-flex"');?>
    </div>

    <fieldset class="repo-box-1">
        <legend for="thumbnail-file"><?php echo t('thumbnail');?><span class="required">*</span></legend>
        
            <div class="repo-thumbnail">
                <img alt="THUMBNAIL" title="Thumbnail" src="<?php echo $this->data['thumbnail']['value'];?>"/>
            </div>
            
            <div class="repo-file-upload">
            
                <div class="field">
                    <label for="thumbnail"><?php echo t('Provide thumbnail path');?></label>
                    <?php echo form_input($this->data['thumbnail']);?>        
                </div>
            
                <div class="field file-upload">
                    <label for="thumbnail-file"><?php echo t('OR upload a thumbnail file (gif,png,jpg)');?></label>
                    <input type="file" name="thumbnail_file" class="file" />
                </div>
            </div>    
    </fieldset>


	<table cellpadding="10">
    <tr>
    	<td>
            <div class="field">
                <label for="weight"><?php echo t('weight');?><span class="required">*</span></label>
                <span class="fixed-100"><?php echo form_input($this->data['weight']);?></span>
            </div>
        </td>
        
    	<td>
            <div class="field">
                <label for="pid"><?php echo t('select_repo_type');?></label>
                <?php echo form_dropdown('type', $repo_types,get_form_value('type',isset($this->data['type']) ? $this->data['type'] : ''));?>
            </div>  
        </td>        
        <td>        
            <div class="field">
                <label for="section"><?php echo t('section');?><span class="required">*</span></label>
                <?php echo form_dropdown('section', $options_section,get_form_value('section',isset($this->data['section']) ? $this->data['section'] : ''));?>
            </div>
        </td>
        <td>
            <div class="field">
                <label for="ispublished"><?php echo t('published');?></label>
                <?php echo form_dropdown('ispublished', $options_published,get_form_value('ispublished',isset($this->data['ispublished']) ? $this->data['ispublished'] : ''));?>
            </div>  
        </td>
    </tr>
    
    </table>



    <div>
		<?php echo form_submit('submit', 'Submit');?>
     	<?php echo anchor('admin/repositories',t('cancel') );?>
    </div>
<?php echo form_close();?>