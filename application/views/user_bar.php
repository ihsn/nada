<?php 
/*
* Shows the login/logout links at the top of the page
*
*/
?>
<?php
//build a list of links for available languages
$languages=$this->config->item("supported_languages");

$lang_ul='';
if ($languages!==FALSE)
{
	if (count($languages)>1)
	{
		foreach($languages as $language)
		{
			$lang_ul.='<li> '.anchor('switch_language/'.$language.'/?destination='.uri_string(), strtoupper(t(strtolower($language)))).' </li>';
		}
	}
}
?>

<div id="user-bar">
<?php $user=$this->session->userdata('username'); ?>
<?php if ($user!=''):?>
    <div class="user-box">
        <ul>                
            <li class="username"><?php echo $user; ?></li>
            <?php if ($this->session->userdata('group_id')==1):?>
	            <li><a href="<?php echo site_url(); ?>/admin"><?php echo t('site_administration');?></a></li>
            <?php endif;?>
            <li><a href="<?php echo site_url(); ?>/auth/profile"><?php echo t('profile');?></a></li>
            <li><a href="<?php echo site_url(); ?>/auth/change_password"><?php echo t('password');?></a></li>                                    
            <li><a href="<?php echo site_url(); ?>/auth/logout"><?php echo t('logout');?></a></li>
            <?php echo $lang_ul;?>
        </ul>        
    </div>
<?php else:?>
<div class="user-box">
    <a href="<?php echo site_url(); ?>/auth/login"><?php echo t('login');?></a> | 
    <a href="<?php echo site_url(); ?>/auth/register"><?php echo t('register');?></a>
    <?php echo $lang_ul;?>
</div>
<?php endif;?>
</div>
