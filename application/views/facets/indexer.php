<div class="container-fluid">

<?php require_once 'links.php';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<h3 class="page-title mt-3"><?php echo t('Facet indexer');?></h3>



<div class="p-3 border">
<p class="text-secondary">Facets index is not updated automatically. After making any changes to the facets, use the re-index to update the index.</p>
<button class="btn btn-sm btn-primary btn-index">Reindex facets</button>
<button class="btn btn-sm btn-danger btn-clear">Clear index</button> 

<div class="index-status mt-3" style="display:none;"></div>

</div>


<div class="mt-5">

	<h3 class="page-title mt-3"><?php echo t('Index stats');?></h3>

	<?php if($rows):?>		
		<div><?php echo t('Found');?>: <?php echo count($rows);?></div>
		<!-- grid -->
		<table class="table table-striped table-sm" width="100%" cellspacing="0" cellpadding="0">
			<tr class="header">
				<th><?php echo t('ID');?></th>
				<th><?php echo t('Facet');?></th>
				<th><?php echo t('Rows');?></th>            
			</tr>
		<?php $tr_class=""; ?>
		<?php foreach($rows as $row): ?>
			<?php $row=(object)$row;?>        
			<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
			<tr class="<?php echo $tr_class; ?>" valign="top">            
				<td><?php echo $row->id;?></td>            
				<td><?php echo $row->name;?></td>
				<td><?php echo $row->total;?></td>
			</tr>
		<?php endforeach;?>
		</table>
	<?php else:?>
		<?php echo t('no_records_found');?>
	<?php endif;?>    

</div>




</div>






<script>

var $total_studies=<?php echo $studies_count;?>;


$( ".btn-index" ).on( "click", function() {
    $(".index-status").show().text("Indexing...");
    reindex(0,30);
});


$( ".btn-clear" ).on( "click", function() {
    $(".index-status").show().text("Clearing...");
    clear_index();
});


function reindex(start_row=0, limit=5, processed=0)
{
    var jqxhr = $.get( "<?php echo site_url('api/facets/reindex/');?>"+start_row + '/'+limit, function(data) {
        console.log(data);
        last_row_id=data.result.last_row_id;
        rows_processed=data.result.rows_processed;
        console.log();

        if(last_row_id>0){
            processed+=rows_processed;
            $(".index-status").html("Indexing ... " + '<span class="badge badge-success">'+ processed +'</span>' );
            reindex(last_row_id,limit,processed);
        }
        else{
            $(".index-status").html("Completed - " + '<span class="badge badge-success">'+ processed +'</span> entries indexed' );
            return true;
        }
        
    })
    .fail(function(data) {
        console.log(data);
        $(".index-status").text("ERROR: "+data.responseText);
    })
}


function clear_index()
{
    var jqxhr = $.get( "<?php echo site_url('api/facets/clear_index/');?>", function(data) {
        console.log(data);
        
		$(".index-status").html("Index is cleared" );
		return true;
	
    })
    .fail(function(data) {
        console.log(data);
        $(".index-status").text("ERROR: "+data.responseText);
    })
}

    
</script>