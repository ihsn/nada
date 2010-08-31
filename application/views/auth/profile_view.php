<h1><?php echo t('profile');?></h1>
<div style="text-align:right;"><?php echo anchor('auth/edit_profile',t('edit'));?></div>

<h2><?php echo $user->first_name. ' ' . $user->last_name; ?> </h2>
<table class="grid-table" cellspacing="0">
	<tr>
    	<td><?php echo t('name');?></td>
        <td><?php echo $user->first_name. ' ' . $user->last_name; ?></td>
    </tr>

	<tr>
    	<td><?php echo t('email');?></td>
        <td><?php echo $user->email; ?></td>
    </tr>

	<tr>
    	<td><?php echo t('company');?></td>
        <td><?php echo $user->company; ?></td>
    </tr>

	<tr>
    	<td><?php echo t('phone');?></td>
        <td><?php echo $user->phone; ?></td>
    </tr>  

	<tr>
    	<td><?php echo t('country');?></td>
        <td><?php echo $user->country; ?></td>
    </tr>  
      
</table>

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
                <td><?php echo $request['status'];?></td>
                <td><?php echo date("m-d-Y",$request['created']); ?></td>
            </tr>
        <?php endforeach;?>    
    </table>    

<?php endif;?>