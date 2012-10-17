<h2 style="margin-bottom:10px">Datafiles</h2>
<div class="instruction-box"><?php echo t('instructions_datafiles_usage'); ?></div>
<a style="font-size:11pt;margin-bottom:8px;float:right" href="<?php echo site_url(), '/datadeposit/upload/', $id; ?>">Upload files</a>
<style type="text/css">
	.grid-table td {
		margin: 0;
		padding: 0;
		border-collapse:collapse;
	}
</style>
<script type="text/javascript">
    $(function() {
        $('tr.data').each(function() {
            if ($(this).children('.description').html() != 'N/A' && $(this).children('.dctype').html() != 'N/A') {
                $($(this).children('td')[1]).css('color', 'purple');
            }
        });
    });
</script>
<?php if (!empty($files) && !empty($records)): ?>
<table class="grid-table" cellspacing="0" cellpadding="0" style="margin-top:5px;">
    <tr style="width:100px">
            <td>
                <select id="batch_actions">
                    <option value="-1">Batch actions</option>
                    <option value="delete">Delete</option>
                </select>
            </td>
            <td>                <input type="button" id="batch_actions_apply" name="batch_actions_apply" value="Apply">                
            </td>
        </tr>
<tr valign="top" align="left" style="height:10px" class="header">
	<th style="width:20px"><input type="checkbox" value="-1" id="chk_toggle"></th>
    <th style="width:200px"><?php echo t('name');?></th>
    <th style="width:500px"><?php echo t('description');?></th>	
    <th style="width:80px"><?php echo t('type');?></th>
    <th style="width:50px"><?php echo t('size');?></th>
    <!--<th>Exists</th>-->
    <th style="width:100px"><?php echo t('actions');?></th>
</tr>
<?php $prefix = ""; ?>
<?php if (!empty($files) && !empty($records)): ?>
	<?php foreach( $files as $file): ?>
    <?php if (!isset($records[$file['filename']]['size'])) continue; ?>
        <tr class="data" valign="top">
    		<td><input type="checkbox" value="<?php echo $file['id']; ?>" class="chk"></td>
            <td><?php echo $file['filename']; ?></td>
			<td class="description"><?php echo isset($file['description']) ? $file["description"] : 'N/A';?></td>            
            <td class="dctype"><?php echo (isset($file['dctype'])) ? preg_replace('#\[.*?\]#', '', $file['dctype']) : 'N/A';?></td>
            <td><?php echo $records[$file['filename']]['size']; ?></td>
            <td><?php echo anchor('datadeposit/managefiles/'.$file['id'],'<img src="images/page_white_edit.png" alt="'.t('edit').'" title="'.t('edit').'"> ');?> 
                <?php echo anchor('datadeposit/delete_resource/'.$file['id'], '<img src="images/close.gif" alt="'.t('delete').'" title="'.t('delete').'"> ');?> 
              <!--  <?php echo anchor('datadeposit/download/'.$file['id'],'<img src="images/icon_download.gif" alt="'.t('download').'" title="'.t('download').'"> ');?> -->
            </td>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
            <div style="font-size:10pt;float:right;padding:5px;"><?php echo t('total_files_count');?><?php echo count($files);?></div>
<?php endif; ?>
<script type="text/javascript" >



//checkbox select/deselect

jQuery(document).ready(function(){

    $("#chk_toggle").click(

            function (e) 

            {

                $('.chk').each(function(){ 

                    this.checked = (e.target).checked; 

                }); 

            }

    );

    $(".chk").click(

            function (e) 

            {

               if (this.checked==false){

                $("#chk_toggle").attr('checked', false);

               }               

            }

    );          

    $("#batch_actions_apply").click(

        function (e){

            if( $("#batch_actions").val()=="delete"){

                batch_delete();

            }

        }

    );



});



function batch_delete(){

    if ($('.chk:checked').length==0){

        alert("You have not selected any items");

        return false;

    }

    if (!confirm("Are you sure you want to delete the selected item(s)?"))

    {

        return false;

    }

    selected='';

    $('.chk:checked').each(function(){ 

        if (selected!=''){selected+=',';}

        selected+= this.value; 

     });

    

    $.ajax({

        timeout:1000*120,

        cache:false,

        dataType: "json",

        data:{ submit: "submit"},

        type:'POST', 

        url: CI.base_url+'/datadeposit/batch_delete_resource/'+selected,

        success: function(data) {
                $('.chk:checked').each(function(){ 
                   
                    $(this).parent().parent().remove();
                });
        },

        
        error: function(XHR, textStatus, thrownError) {

            alert("Error occured " + XHR.status);

        }

    }); 

}

</script>
