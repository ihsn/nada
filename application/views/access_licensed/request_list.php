<?php if ($lic_requests):?>
<h2 style="margin-top:50px;"><?php echo t('licensed_survey_requests');?></h2>
    <table class="grid-table" cellspacing="0">
    	<tr class="header">
        	<th><?php echo t('survey_title');?></th>
            <th><?php echo t('status');?></th>
            <th><?php echo t('date');?></th>
        </tr>
        <?php foreach($lic_requests as $request) :?>
            <tr>
                <td><?php echo anchor('access_licensed/track/'.$request['id'],$request['titl']);?></td>
                <td><?php echo t($request['status']);?></td>
                <td><?php echo date("m-d-Y",$request['created']); ?></td>
            </tr>
        <?php endforeach;?>    
    </table>    
<?php endif;?>