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
        <div class="folder"><i class="fas fa-folder"></i> <?php echo $key;?></div>
        <?php echo $this->load->view('survey_info/zip_preview',array('data'=>$value),true);?>        
    <?php endif;?>
<?php endforeach;?>
</div>