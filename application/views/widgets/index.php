<div class="container">
    <?php foreach($widgets as $row):?>
    <div class="row border-bottom pb-5 pt-4">
        <div class="col-md-3 ">
            <?php $thumbnail=$widget_storage_root.$row['storage_path'].'/'.$row['thumbnail'];?>
            <?php $thumbnail_url=base_url().'/'.$widget_storage_root.$row['storage_path'].'/'.$row['thumbnail'];?>
            <?php if (file_exists($thumbnail)):?>
                <img class="" style="width:100%;" src="<?php echo $thumbnail_url;?>">
            <?php endif;?>
        </div>
        <div class="col-md-9">
            <h3><a target="_blank" href="<?php echo $row['link'];?>"><?php echo $row['title'];?></a></h3>
            <p><?php echo $row['description'];?></p>
        </div>
    </div>
    <?php endforeach;?>
</div>    