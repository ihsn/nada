<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.13.0/Sortable.min.js" integrity="sha512-5x7t0fTAVo9dpfbp3WtE2N6bfipUwk7siViWncdDoSz2KwOqVC1N9fDxEOzk0vTThOua/mglfF8NO7uVDLRC8Q==" crossorigin="anonymous"></script>

<style>
h3, 
.tablist .nav-link,
.facet-name {
    text-transform:Capitalize;
}
.text-size-sm{
    font-size:12px;
    text-transform: lowercase;
}
</style>

<div class="container-fluid">


<?php require_once 'links.php';?>
<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h3 class="page-title mt-3"><?php echo t('Facets');?></h3>



<ul class="nav nav-tabs tablist" id="myTab" role="tablist">
    <?php foreach($data_types as $type):?>
    <li class="nav-item">
        <a class="nav-link <?php echo $type=='microdata' ? ' active' : '';?>" id="<?php echo $type;?>-tab" data-toggle="tab" href="#<?php echo $type;?>" role="tab" aria-controls="<?php echo $type;?>" aria-selected="true"><?php echo $type;?></a>
    </li>
    <?php endforeach;?>  
</ul>

<form id="form" class="mb-5">
<div class="tab-content" id="myTabContent">
    
    <?php foreach($data_types as $type):?>
    <div class="tab-pane fade <?php echo $type=='microdata' ? ' show active' : '';?>" id="<?php echo $type;?>" role="tabpanel" aria-labelledby="<?php echo $type;?>-tab">
  
        <div id="row-<?php echo $type;?>" class="row p-3 pl-4">
            <div class="col-12">
                <h3><?php echo $type;?></h3>
                <p>Drag and drop to re-arrange the display order</p>
            </div>

            <div id="list_<?php echo $type;?>" class="list-group col-md-4">
                
                <!--
                <?php foreach($facets as $facet_name=>$facet):?>
                <div class="list-group-item facet-name">
                    <i class="fas fa-bars "></i>
                        <?php echo $facet['title'];?> <span class="text-secondary text-size-sm">[<?php echo $facet_name;?>]</span>
                    <div class="float-right">
                        <input type="checkbox" class="chk-facet" name="<?php echo $type;?>[<?php echo $facet_name;?>]" checked data-toggle="toggle" data-size="sm">
                    </div>
                </div>
                <?php endforeach;?>
                -->

                <?php foreach($facets_selection[$type] as $facet_name=>$facet):?>
                <div class="list-group-item facet-name">
                    <i class="fas fa-bars "></i>
                        <?php echo $facet['title'];?> <span class="text-secondary text-size-sm">[<?php echo $facet_name;?>]</span>
                    <div class="float-right">
                        <input type="checkbox" class="chk-facet" name="<?php echo $type;?>[<?php echo $facet_name;?>]" 
                            <?php echo ($facet['enabled']) ? 'checked' : '';?> data-toggle="toggle" data-size="sm">
                    </div>
                </div>
                <?php endforeach;?>
                
            </div>			
        </div>

    </div>
    <?php endforeach;?>
    

</div>
<div class="col-md-4 mb-4">
<button type="button" class="btn btn-primary btn-block btn-update">Update</button>
</div>
</form>



<script>
function update_order()
{
    var form_data=$("#form").serialize();

    $.post("<?php echo site_url('api/facets/reorder/');?>", form_data, function(data) {		
			alert("<?php echo t('updated'); ?>");
            console.log(data);		
	})
    .fail(function(data) {
        console.log(data);
        //$(".index-status").text("ERROR: "+data.responseText);
        alert(data.responseText);
    })
}

$( ".btn-update" ).on( "click", function() {
  update_order();
});

<?php foreach($data_types as $type):?>
var sortable_<?php echo $type;?>=new Sortable(list_<?php echo $type;?>, {
    animation: 150,
    ghostClass: 'blue-background-class',
	onUpdate: function (evt) {
	},
});
<?php endforeach;?>
</script>



