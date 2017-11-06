<?php if ($lic_requests):?>
<h2 style="margin-top:50px;"><?php echo t('licensed_survey_requests');?> - [<?php echo count($lic_requests);?>]</h2>
    <table class="grid-table" cellspacing="0">
    	<tr class="header">
        	<th><?php echo t('#ID');?></th>
            <th><?php echo t('survey_title');?></th>
            <th><?php echo t('status');?></th>
            <th><?php echo t('date');?> </th>
        </tr>
        <?php foreach($lic_requests as $request) :?>
            <tr>
            	<td><?php echo $request['id'];?></td>
                <td><?php echo anchor('access_licensed/track/'.$request['id'],($request['request_title']==NULL) ?  'single study request' : $request['request_title'] );?></td>
                <td><?php echo t($request['status']);?></td>
                <td><?php echo date("m-d-Y",$request['created']); ?></td>
            </tr>
        <?php endforeach;?>    
    </table>    
<?php endif;?>