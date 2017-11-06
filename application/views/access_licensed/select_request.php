<div class="lic-requests-container">

<h2><?php echo t('licensed_survey_requests');?></h2>
<?php if ($rows):?>

<a href="<?php echo site_url('catalog/'.$survey_id.'/get_microdata/');?>?request=new#tab" class="btn btn-small btn-new-request"><?php echo t('make_new_lic_request');?></a>

<p><?php echo t('click_on_a_lic_request_to_see_status_or_download_data');?></p>

    <table class="grid-table licensed-requests" cellspacing="0">
    	<tr class="header">
        	<th><?php echo t('title');?></th>
            <th><?php echo t('status');?></th>
            <th></th>
        </tr>
        <?php foreach($rows as $request) :?>
            <tr>
                <td>
					<h3>#<?php echo $request['id'];?> <?php echo anchor('catalog/'.$survey_id.'/get_microdata/?requestid='.$request['id'],($request['request_title']==NULL) ? $request['titl'] : $request['request_title']);?></h3>
                    <div class="inline">Requested on: <?php echo date("F d, Y",$request['created']); ?></div>
                    <div class="inline">Expires: <?php echo $request['expiry_date']!=NULL ? date("F d, Y",$request['expiry_date']) : 'N/A'; ?></div>
                </td>
                <td><?php echo t($request['status']);?></td>    
                <td>
					<?php if($request['status']=='APPROVED'):?>
                    	<span class="btn-download-microdata"><?php echo anchor('catalog/'.$survey_id.'/get_microdata/?requestid='.$request['id'],t('Download') );?></span>
                    <?php else:?>
                    	<?php //echo anchor('access_licensed/track/'.$request['id'],t('view_request') );?>
                    <?php endif;?>
                </td>
            </tr>
        <?php endforeach;?>    
    </table>    

<?php endif;?>
</div>