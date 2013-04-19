<?php /*
$lang['data_direct_description']="Datasets and the related documentation are made available freely to users on the Bank's intranet. There is no need to be being logged into the application.";
$lang['data_public_description']="Related documentation is made freely available to users on the Bank's intranet. Users must, however, be logged into the application to access these datasets.";
$lang['data_licensed_description']="Related documentation is made freely available to users on the Bank's intranet. Access to the datasets requires the user to apply for approval to access these datasets. Users must be logged into the application and fill out an application form when applying for access.";
$lang['data_enclave_description']="Related documentation is made freely available to users on the Bank's intranet. Access to the datasets requires the user to apply for approval to access these datasets at a secure onsite facility.";
$lang['data_remote_description']="Related documentation is made freely available to users on the Bank's intranet. The datasets are held in catalogs outside the DDP Microdata or at other institutions.";
$lang['data_da_description']="Related documentation is made freely available to users on the Bank's intranet. The datasets are however not available for these studies.";
*/ ?>
<div class="filter-da da-help">
<table>
    <tr class="item">
        <td><span class="da-icon-small da-direct"></span></td>
        <td class="nopad"> 
        	<label title="<?php echo t('link_data_direct_hover');?>" for="da_direct"> <span class="title"><?php echo t('legend_data_direct');?></span> </label>
         	<div class="description"><?php echo t('data_direct_description');?></div>
         </td>
    </tr>

    <tr class="item">
    <td><span class="da-icon-small da-public"></span></td>
    <td class="nopad"> 
    	<label title="<?php echo t('link_data_public_hover');?>" for="da_public"><span class="title"> <?php echo t('legend_data_public');?></span></label>
        <div class="description"><?php echo t('data_public_description');?></div>
    </td>
    </tr>

    <tr class="item">
    <td><span class="da-icon-small da-licensed"></span></td>
    <td class="nopad">
       <label title="<?php echo t('link_data_licensed_hover');?>" for="da_licensed">
            <span class="title"> <?php echo t('legend_data_licensed');?></span>
        </label>
        <div class="description"><?php echo t('data_licensed_description');?></div>
    </td>
    </tr>

    <tr class="item">
    <td><span class="da-icon-small da-enclave"></span></td>
    <td class="nopad">
    	<label title="<?php echo t('link_data_enclave_hover');?>" for="da_enclave"><span class="title"> <?php echo t('legend_data_enclave');?></span></label>
        <div class="description"><?php echo t('data_enclave_description');?></div>
    </td>
    </tr>

    <tr class="item">
    <td><span class="da-icon-small da-remote"></span></td>
    <td class="nopad">
    	<label title="<?php echo t('link_data_remote_hover');?>" for="da_remote"><span class="title"><?php echo t('legend_data_remote');?></span></label>
        <div class="description"><?php echo t('data_remote_description');?></div>
    </td>
    </tr>

    <tr class="item">
    <td><span class="da-icon-small da-no_access"></span></td>
    <td class="nopad">
    	<label title="<?php echo t('link_data_na');?>" for="da_na"><span class="title"> <?php echo t('legend_na_access');?></span></label>
        <div class="description"><?php echo t('data_na_description');?></div>
	</td>
    </tr>
</table>
</div>