<?php if (!empty($thumbnail)):?>

    <div>
        <div>
            <img src="<?php echo base_url();?>files/thumbnails/<?php echo basename($thumbnail);?>?v=<?php echo time();?>" alt="" class="rounded shadow-sm study-thumbnail center-block"/>
        </div>

        <div>

            <div class="center-block" style="text-align:center;padding:4px;">
                <button type="button" class="btn btn-sm btn-link">
                    <span data-toggle="modal" data-target="#exampleModal" class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo t('upload');?> 
                </button>
                <button type="button" class="btn btn-sm btn-link btn-delete-thumbnail">
                    <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span> <?php echo t('remove');?>
                </button>
            </div>
        </div>
    </div>


<?php else:?>        
    <button type="button" class="btn btn-link center-block" data-toggle="modal" data-target="#exampleModal"><span data-toggle="modal" data-target="#exampleModal" class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo t('Upload thumbnail');?></button>
<?php endif;?>
        

<!-- Modal -->
<div class="modal fade modal-thumbnail-upload" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel"><?php echo t('Upload thumnail');?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
        <?php echo form_open_multipart('api/datasets/thumbnail/'.$idno, 'style="margin-left:10px;" id="form-thumbnail"');?>

        <div class="form-group">
            <input type="file" class="form-control" name="file" id="thumbnail-file" xstyle="width:100px;">
            <p class="text-muted"><?php echo t('select_study_thumbnail');?></p>
        </div>

        <?php echo form_close();?>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo t('cancel');?></button>
            <button type="button" class="btn btn-primary btn-upload-thumbnail"><?php echo t('upload');?></button>
        </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $(document.body).on("click",".btn-upload-thumbnail", function(){
        $.ajax({
            url: '<?php echo site_url('api/datasets/thumbnail/'.$idno);?>', 
            type: 'POST',
            data: new FormData($('#form-thumbnail')[0]),
            processData: false,
            contentType: false
        }).done(function(){
            window.location.href = "<?php echo site_url('admin/catalog/edit/'.$id);?>";
        }).fail(function(xhr, status, error) {
            if(xhr.hasOwnProperty("responseJSON")){
                alert(xhr.responseJSON.message);
            }else{
                alert(error);
            }
        });
    });

    $(document.body).on("click",".btn-delete-thumbnail", function(){
        /*if(!confirm("delete thumbnail?")){
            return false;
        }*/

        $.ajax({
            url: '<?php echo site_url('api/datasets/thumbnail_delete/'.$idno);?>', 
            type: 'POST',
            processData: false,
            contentType: false 
        }).done(function(){           
            window.location.href = "<?php echo site_url('admin/catalog/edit/'.$id);?>";
        }).fail(function(xhr, status, error) {
            if(xhr.hasOwnProperty("responseJSON")){
                alert(xhr.responseJSON.message);
            }else{
                alert(error);
            }
        });
    });

});
</script>