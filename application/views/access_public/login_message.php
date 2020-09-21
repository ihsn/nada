<h3><?php echo t('login_to_access_data');?></h3>

<p><?php echo t('login_to_access_data_text');?></p>

<a class="btn btn-primary" href="<?php echo site_url("auth/login?destination=catalog/$sid/get-microdata");?>"><?php echo t('login');?></a>
<a class="btn btn-primary" href="<?php echo site_url("auth/register");?>"><?php echo t('register');?></a>