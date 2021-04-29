<style>
.field_select select{
    max-width:200px;
}
</style>

<div class="container-fluid">


<div class="text-right page-links">
	<a href="<?php echo site_url('admin/facets'); ?>" class="btn btn-outline-primary btn-sm">
        <span class="fas fa-home ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('home');?>
    </a>

    <a href="<?php echo site_url('admin/facets/indexer'); ?>" class="btn btn-outline-primary btn-sm">
        <span class="fas fa-coins ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('Indexer');?>
    </a>

    <a href="<?php echo site_url('admin/facets/create'); ?>" class="btn btn-outline-primary btn-sm">
        <span class="fas fa-plus-circle ico-add-color right-margin-5" aria-hidden="true"></span> 
        <?php echo t('Create new facet');?>
    </a>

</div>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h3 class="page-title mt-3"><?php echo t('Facets');?></h3>



<?php

$data_types=array(
    'survey',
    'geospatial',
    'document',
    'table',
    'image'
);

?>


<?php echo form_open('', array('class'=>'form form-custom-width'));?>

<div class="form-group">
    <label for="title"><?php echo t('Name');?><span class="required">*</span></label>
    <input class="form-control" name="title" type="text" id="title"  value="<?php echo get_form_value('title',isset($row['title']) ? $row['title'] : ''); ?>"/>
</div>

<div class="form-group">
    <label for="weight"><?php echo t('Title');?><span class="required">*</span></label>
    <input class="form-control" name="weight" type="text" id="weight"  value="<?php echo get_form_value('weight',isset($row['weight']) ? $row['weight'] : ''); ?>"/>
</div>

<table class="table table-striped">
    <tr>
        <td>Type</td>
        <td>Field</td>
        <td>Subfield (for composite types)</td>
        <td>Filter subfield</td>
        <td>Filter value</td>
    </tr>
<?php foreach($data_types as $type):?>

    <?php 
    $fields_= array_keys($fields[$type]);

    $fields_ = array_filter($fields_, 
        function($item) { 
            return strpos($item, '/'); 
    });

    $fields_=array_merge(array("--SELECT--"),$fields_);

    ?>

    <tr>    
        <td>
            <div class="form-group-x">
                <?php echo t($type);?>
            </div>
        </td>    

        <td>
            <div class="form-group field_select">
            <?php echo form_dropdown('field', $fields_, '');?>
            </div>
        </td>    

        <td>
            <div class="form-group">                
                <input class="form-control" name="weight" type="text" id="weight"  value="<?php echo get_form_value('weight',isset($row['weight']) ? $row['weight'] : ''); ?>"/>
            </div>
        </td>  

        <td>
            <div class="form-group">                
                <input class="form-control" name="weight" type="text" id="weight"  value="<?php echo get_form_value('weight',isset($row['weight']) ? $row['weight'] : ''); ?>"/>
            </div>
        </td>
        
        <td>
            <div class="form-group">                
                <input class="form-control" name="weight" type="text" id="weight"  value="<?php echo get_form_value('weight',isset($row['weight']) ? $row['weight'] : ''); ?>"/>
            </div>
        </td>  
        

    </tr>

<?php endforeach;?>
</table>

<div style="margin-top:10px;">
    <?php echo form_submit('submit', t('update'),array('class'=>'btn btn-primary btn-sm'));?>
    <?php echo anchor('admin/regions/', t('cancel'),array('class'=>'btn btn-secondary btn-sm'));?>
</div>
  
<?php echo form_close();?>

</div>
