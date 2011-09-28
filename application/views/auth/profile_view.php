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
<?php $this->load->view('access_licensed/request_list',array('data'=>$lic_requests));?>