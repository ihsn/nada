<?php if ($lic_coll_requests):?>
<h2 style="margin-top:50px;"><?php echo t('licensed_survey_requests');?></h2>
<p><?php echo t(sprintf('You have already requested this collection %s times. To make a new request, <a href="%s">click here</a>.',count($lic_coll_requests),site_url('access_licensed/by_collection/'.$this->uri->segment(3).'?request=new')) );?></p>
    <table class="grid-table" cellspacing="0">
    	<tr class="header">
        	<th><?php echo t('#ID');?></th>
            <th><?php echo t('survey_title');?></th>
            <th><?php echo t('status');?></th>
            <th><?php echo t('date');?></th>
        </tr>
        <?php foreach($lic_coll_requests as $request):?>
			<tr>
            	<td><?php echo $request['id'];?></td>
                <td><?php echo anchor('access_licensed/track/'.$request['id'],$request['title']);?></td>
                <td><?php echo t($request['status']);?></td>
                <td><?php echo date("m-d-Y",$request['created']); ?></td>
            </tr>
        <?php endforeach;?>    
    </table>    
<?php endif;?>