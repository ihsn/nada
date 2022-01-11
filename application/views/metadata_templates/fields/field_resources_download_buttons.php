<?php if (isset($data) && is_array($data) && count($data)>0 ):?>

<?php
    //resource fields
    $resource_fields=array(
    "title"=>'text',
    "file_name" =>'text',
    "description" =>'text',
    "resource_id" =>'text',
    "survey_id" =>'text'
);    
?>

<div class="field resource-downloads field-<?php echo $name;?> float-right">
    <?php $k=0;foreach($data as $index=>$resource):?>
        <?php if($k>=3){break;}?>
        <?php if (!empty($resource['filename'])):$k++;?>            
                <a class="float-right btn btn-outline-primary btn-sm ml-2" target="_blank" title="<?php echo $resource['title'];?>" href="<?php echo $resource['filename'];?>">
                <i class="fa fa-download" aria-hidden="true"></i>  <?php echo t('Download');?>
                </a>            
        <?php endif;?>  
    <?php endforeach;?>
</div>
<?php endif;?>