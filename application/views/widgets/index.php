<style>
    .no-thumbnail{
        font-size: 100px;
        width: 100%;
        padding: 20px;
        border: 1px solid gainsboro;
        text-align: center;
        color: #117a8b;
    }
    .thumbnail{
        padding: 10px;
        width: 100%;
        border: 1px solid gainsboro;
    }
</style>

<div class="container widgets-container">
    <h1>Widgets</h1>

    <h5 class="border-bottom mt-3 pb-2"><?php echo count($widgets);?> results</h5>

    <?php foreach($widgets as $row):?>
    <div class="row border-bottom pb-5 pt-4">
        <div class="col-md-3 ">
            <?php $thumbnail=$widget_storage_root.$row['storage_path'].'/'.$row['thumbnail'];?>
            <?php $thumbnail_url=base_url().'/'.$widget_storage_root.$row['storage_path'].'/'.$row['thumbnail'];?>
            <?php if (file_exists($thumbnail)):?>
                <img class="thumbnail" src="<?php echo $thumbnail_url;?>">
            <?php else:?>
                <i class="fa fa-bar-chart no-thumbnail" aria-hidden="true"></i>
            <?php endif;?>
        </div>
        <div class="col-md-9">
            <h3><a target="_blank" href="<?php echo $row['link'];?>"><?php echo $row['title'];?></a></h3>
            <p><?php echo $row['description'];?></p>
        </div>
    </div>
    <?php endforeach;?>
</div>    