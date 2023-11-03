<?php /* ?>
<style>
    .item{
        padding:5x;
        margin-left:10px;
    }
    .item .item{
        padding-left:10px;
        margin-left:10px;
    }
    .folder{
        font-weight:bold;
    }
</style>
<?php */?>

<div class="item">
<?php foreach($data as $key=>$value):?>
    <?php if (isset($value['name'])):?>
        <div class="file">
            <i class="far fa-file-alt"></i> <?php echo $key;?>
        </div>
    <?php else:?>
        <?php 
            if ((!isset($parent))){
                $target=('resource_'.$resource_id);
            }else{
                $target=urlencode('res_'.$key.$resource_id);
            }            
            ?>
        <div class="folder mouse-pointer" type="button" data-toggle="collapse" data-target="#<?php echo $target;?>" aria-expanded="false"><i class="fas fa-folder" ></i> <?php echo $key;?></div>
        <div class="collapse" id="<?php echo $target;?>" >
            <?php echo $this->load->view('survey_info/zip_preview',array('data'=>$value, 'parent'=>$key, 'resource_id'=>$resource_id),true);?>
        </div>
    <?php endif;?>
<?php endforeach;?>
</div>