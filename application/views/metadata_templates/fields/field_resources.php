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

<style>
.resource-icon{
    font-size:35px;
    padding:10px;
    color:#0071bc;
}
</style>

<?php 

$exclude=isset($options['exclude']) ? (array)$options['exclude'] : array();

?>
<div class="field resource-downloads field-<?php echo $name;?>">
    <div class="xsl-caption field-caption"><?php echo t($name);?></div>
    <div class="field-value p-2" >
    <?php foreach($data as $index=>$resource):?>
        <?php 
            $dctypecode=explode("[",$resource['dctype']);
            $dctypecode=str_replace("]","",$dctypecode[count($dctypecode)-1]);
            if(in_array($dctypecode,$exclude)){continue;}
        ?>
        <div class="row mb-3 pb-2 border-bottom ">
            <div class="col">            
                <a target="_blank" href="<?php echo $resource['filename'];?>" class="font-weight-bold">    
                    <?php echo $resource['title'];?></strong>
                </a>
                <?php if (isset($resource['dcformat'])):?>
                    <span class="badge badge-light"><?php echo $resource['dcformat'];?></span>
                <?php endif;?>
                <?php if(isset($resource['description'])):?>
                <div><?php echo nl2br($resource['description']);?></div>
                <?php endif;?>
            </div>
            <div class="col-md-2 col-sm-4">
            <?php //if (!empty($resource['filename'])):?>
                <button class="float-right btn btn-primary btn-sm"><i class="fa fa-download" aria-hidden="true"></i> 
                    <a target="_blank" href="<?php echo $resource['filename'];?>" class="text-white"><?php echo t('Download');?></a>
                </button>
            <?php //endif;?>                
            </div>
        </div>

    <?php endforeach;?>
    </div>
</div>
<?php endif;?>