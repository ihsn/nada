<style>
.form-control{width:200px;display:inline;}
.input-fixed-3{width:50px;display:inline;text-align:center;}
.field{margin-bottom:15px;clear:both;}
label{display:block;float:left;width:200px;}
.field-note{font-style:italic;padding-left:5px;color:gray;}
h2{font-size:1.2em;font-weight:bold;border-bottom:1px solid gainsboro;padding-bottom:2px;margin-bottom:10px;}
.field-expanded,.always-visible{background-color:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;margin-right:8px;}
.always-visible{padding:10px;}
.field-expanded .field, .always-visible .field {padding:5px;}
.field-expanded legend, .field-collapsed legend, .always-visible legend{background:white;padding-left:5px;padding-right:5px;font-weight:normal; cursor:pointer;}
.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
.field-collapsed legend {background-position:left top; }

.field-collapsed .field{display:none;}
.field-expanded .field label, .always-visible label{font-weight:normal;}
.instructions{font-weight:bold;}

</style>
<div class="container-fluid mt-5">
<h3 class="page-title"><?php echo t('site_configurations');?></h3>

<?php if (validation_errors() ) : ?>
    <div class="alert alert-danger">
	    <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

<?php if (isset($this->message)):?>
<?php echo ($this->message!="") ? '<div class="alert alert-success">'.$this->message.'</div>' : '';?>
<?php endif;?>


<?php echo form_open('', 'id="form_site_configurations" name="form_site_configurations"');?>

<div style="text-align:right;">
	<input class="btn btn-primary" type="submit" value="<?php echo t('update');?>" name="submit"/>
</div>

<fieldset class="field-expanded  ">
        <legend><i class="fas fa-cogs mr-3" style="color:#007bff;"></i><?php echo t('general_site_settings');?></legend>
    <div class="field form-group">
            <label for="<?php echo 'website_title'; ?>"><?php echo t('website_title');?></label>
            <input class="form-control" name="website_title" type="text" id="website_title"  value="<?php echo get_form_value('website_title',isset($website_title) ? $website_title : ''); ?>"/>
    </div>
    <div class="field">
            <label for="<?php echo 'website_footer'; ?>"><?php echo t('website_footer');?></label>
            <textarea rows="5" cols="20" class="form-control" name="website_footer" type="text" id="website_footer"  ><?php echo get_form_value('website_footer',isset($website_footer) ? $website_footer : ''); ?></textarea>
    </div>        
    <div class="field">
            <label for="<?php echo 'default_home_page'; ?>"><?php echo t('default_home_page');?></label>
            <input class="form-control" name="default_home_page" type="text" id="default_home_page"  value="<?php echo get_form_value('default_home_page',isset($default_home_page) ? $default_home_page : ''); ?>"/>
            <span class="field-note"><?php echo t('instruction_default_home_page'); ?></span>
    </div>    
    <div class="field">
            <label for="<?php echo 'website_webmaster_name'; ?>"><?php echo t('webmaster_name');?></label>
            <input class="form-control" name="website_webmaster_name" type="text" id="website_webmaster_name"  value="<?php echo get_form_value('website_webmaster_name',isset($website_webmaster_name) ? $website_webmaster_name : ''); ?>"/>
    </div>    
    <div class="field">
            <label for="<?php echo 'website_webmaster_email'; ?>"><?php echo t('webmaster_email');?></label>
            <input class="form-control" name="website_webmaster_email" type="text" id="website_webmaster_email"  value="<?php echo get_form_value('website_webmaster_email',isset($website_webmaster_email) ? $website_webmaster_email : ''); ?>"/>
    </div>

    <div class="field">
            <label for="max_resource_upload_size"><?php echo t('max_resource_upload_size');?></label>
            <input class="input-fixed-3" name="max_resource_upload_size" type="text" id="max_resource_upload_size" value="<?php echo get_form_value('max_resource_upload_size',isset($max_resource_upload_size) ? $max_resource_upload_size : '3000'); ?>"/>
            <span class="field-note"><?php echo t('max_resource_upload_size_note');?></span>
    </div>

</fieldset>

<fieldset class="field-expanded ">
	<legend><i class="fas fa-language mr-3" style="color:#007bff;"></i><?php echo t('language');?></legend>

	<?php
		$_avail  = isset($available_folders) && is_array($available_folders) ? $available_folders : array();
		$_map    = isset($lang_mapping)       && is_array($lang_mapping)       ? $lang_mapping       : array();
		$_iso    = isset($iso_languages)      && is_array($iso_languages)      ? $iso_languages      : array();
	?>

	<div class="field">
		<label for="language"><?php echo t('default_language');?></label>
		<?php
			$_def_opts = array();
			foreach ($_avail as $_folder) {
				$_di = isset($_map[$_folder]) ? $_map[$_folder] : null;
				$_def_opts[$_folder] = $_di && !empty($_di['display']) ? $_di['display'] : ucfirst($_folder);
			}
			echo form_dropdown('language', $_def_opts, get_form_value('language', isset($language) ? $language : 'english'));
		?>
		<span class="field-note"><?php echo t('default_language_note');?></span>
	</div>

	<div class="field">
		<label><?php echo t('enabled_languages');?></label>
		<span class="field-note"><?php echo t('enabled_languages_note');?></span>
		<div class="table-responsive mt-2">
			<table class="table table-sm table-bordered" id="languages-table">
				<thead class="thead-light">
					<tr>
						<th style="width:60px;" class="text-center"><?php echo t('enabled');?></th>
						<th><?php echo t('folder');?></th>
						<th><?php echo t('iso_language');?></th>
						<th><?php echo t('display_name');?></th>
						<th><?php echo t('direction');?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($_avail as $_folder):
					$_curr       = isset($_map[$_folder]) ? $_map[$_folder] : null;
					$_curr_code  = ($_curr && isset($_curr['code']))      ? $_curr['code']      : '';
					$_curr_disp  = ($_curr && isset($_curr['display']))   ? $_curr['display']   : '';
					$_curr_dir   = ($_curr && isset($_curr['direction'])) ? $_curr['direction'] : '';
					$_is_enabled = ($_curr !== null);
					?>
					<tr>
						<td class="text-center align-middle">
							<input type="checkbox" name="lang_enabled[<?php echo htmlspecialchars($_folder); ?>]" value="1"<?php echo $_is_enabled ? ' checked' : ''; ?>>
						</td>
						<td class="align-middle"><code><?php echo htmlspecialchars($_folder); ?></code></td>
						<td>
							<select name="lang_code[<?php echo htmlspecialchars($_folder); ?>]" class="form-control form-control-sm iso-select" data-folder="<?php echo htmlspecialchars($_folder); ?>">
								<option value="">-- <?php echo t('select');?> --</option>
								<?php foreach ($_iso as $_code => $_info): ?>
									<option value="<?php echo htmlspecialchars($_code); ?>"<?php echo ($_curr_code === $_code) ? ' selected' : ''; ?>>
										<?php echo htmlspecialchars($_info['name']); ?> — <?php echo htmlspecialchars($_info['display']); ?> (<?php echo htmlspecialchars($_code); ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td class="align-middle"><span id="lang_display_<?php echo htmlspecialchars($_folder); ?>"><?php echo htmlspecialchars($_curr_disp); ?></span></td>
						<td class="align-middle"><span id="lang_dir_<?php echo htmlspecialchars($_folder); ?>"><?php echo htmlspecialchars($_curr_dir); ?></span></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<script>
	(function() {
		var isoData = <?php echo json_encode($_iso); ?>;
		$(document).on('change', '.iso-select', function() {
			var folder = $(this).data('folder');
			var code   = $(this).val();
			var info   = isoData[code];
			$('#lang_display_' + folder).text(info ? info.display    : '');
			$('#lang_dir_'     + folder).text(info ? info.direction  : '');
		});
	})();
	</script>

</fieldset>


<fieldset class="field-expanded ">
	<legend><i class="fas fa-edit mr-3" style="color:#007bff;"></i><?php echo t('use_html_editor_for_html');?></legend>
    <div class="field" >
        <label style="height:50px;" for="use_html_editor"><?php echo t('use_html_editor');?></label>
        <input type="radio" value="yes" name="use_html_editor" <?php echo ($use_html_editor=='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('yes');?> 
        <input type="radio" value="no" name="use_html_editor" <?php echo ($use_html_editor!='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('no');?><br/>
    </div>
</fieldset>

<fieldset class="field-expanded ">
	<legend><i class="fas fa-user-cog mr-3" style="color:#007bff;"></i><?php echo t('survey_catalog_settings');?></legend>
	<div class="field">
        <label for="<?php echo 'catalog_root'; ?>"><?php echo t('catalog_folder');?></label>
        <input class="form-control" name="catalog_root" type="text" id="catalog_root"  value="<?php echo get_form_value('catalog_root',isset($catalog_root) ? $catalog_root : ''); ?>"/>
        <?php echo folder_exists($catalog_root);?>
        <span class="field-note"><?php echo t('instruction_catalog_root'); ?></span>
	</div>
	<div class="field">
        <label for="<?php echo 'ddi_import_folder'; ?>"><?php echo t('ddi_import_folder');?></label>
        <input class="form-control" name="ddi_import_folder" type="text" id="ddi_import_folder"  value="<?php echo get_form_value('ddi_import_folder',isset($ddi_import_folder) ? $ddi_import_folder : ''); ?>"/>
        <?php echo folder_exists($ddi_import_folder);?>
        <span class="field-note"><?php echo t('instruction_ddi_import_folder'); ?></span>
	</div>
	<div class="field">
        <label for="catalog_records_per_page"><?php echo t('data_catalog_page_size');?></label>
        <input class="input-fixed-3" name="catalog_records_per_page" type="text" id="catalog_records_per_page" value="<?php echo get_form_value('catalog_records_per_page',isset($catalog_records_per_page) ? $catalog_records_per_page : ''); ?>"/>
        <span class="field-note"><?php echo t('instruction_catalog_records_per_page'); ?></span>
	</div>

	<div class="field">
        <label><?php echo t('catalog_variable_view');?></label>
        <div>
            <input type="radio" name="catalog_variable_view" value="yes" <?php echo (isset($catalog_variable_view) && $catalog_variable_view=='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('yes');?>&nbsp;&nbsp;
            <input type="radio" name="catalog_variable_view" value="no"  <?php echo (!isset($catalog_variable_view) || $catalog_variable_view!='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('no');?>
        </div>
        <span class="field-note"><?php echo t('catalog_variable_view_note');?></span>
	</div>

	<div class="field">
        <label><?php echo t('catalog_show_abstract');?></label>
        <div>
            <input type="radio" name="catalog_show_abstract" value="yes" <?php echo (!isset($catalog_show_abstract) || $catalog_show_abstract=='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('yes');?>&nbsp;&nbsp;
            <input type="radio" name="catalog_show_abstract" value="no"  <?php echo (isset($catalog_show_abstract) && $catalog_show_abstract!='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('no');?>
        </div>
        <span class="field-note"><?php echo t('catalog_show_abstract_note');?></span>
	</div>

	<div class="field">
        <label><?php echo t('data_types_nav_bar');?></label>
        <div>
            <input type="radio" name="data_types_nav_bar" value="yes" <?php echo (isset($data_types_nav_bar) && $data_types_nav_bar=='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('yes');?>&nbsp;&nbsp;
            <input type="radio" name="data_types_nav_bar" value="no"  <?php echo (!isset($data_types_nav_bar) || $data_types_nav_bar!='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('no');?>
        </div>
        <span class="field-note"><?php echo t('data_types_nav_bar_note');?></span>
	</div>

	<div class="field">
        <label><?php echo t('guests_hide_microdata_tab');?></label>
        <div>
            <input type="radio" name="guests_hide_microdata_tab" value="yes" <?php echo (isset($guests_hide_microdata_tab) && $guests_hide_microdata_tab=='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('yes');?>&nbsp;&nbsp;
            <input type="radio" name="guests_hide_microdata_tab" value="no"  <?php echo (!isset($guests_hide_microdata_tab) || $guests_hide_microdata_tab!='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('no');?>
        </div>
        <span class="field-note"><?php echo t('guests_hide_microdata_tab_note');?></span>
	</div>

	<div class="field">
        <label for="catalog_default_sort_by"><?php echo t('catalog_default_sort_by');?></label>
        <?php
            $sort_options = array(
                ''           => t('sort_default'),
                'relevance'  => t('Relevance'),
                'popularity' => t('Popularity'),
                'year'       => t('year'),
                'title'      => t('title'),
                'country'    => t('country'),
            );
            echo form_dropdown('catalog_default_sort_by', $sort_options, get_form_value('catalog_default_sort_by', isset($catalog_default_sort_by) ? $catalog_default_sort_by : ''));
        ?>
        <span class="field-note"><?php echo t('catalog_default_sort_by_note');?></span>
	</div>

	<div class="field">
        <label for="catalog_default_sort_order"><?php echo t('catalog_default_sort_order');?></label>
        <?php
            $order_options = array(
                ''     => t('sort_default'),
                'desc' => t('sort_desc'),
                'asc'  => t('sort_asc'),
            );
            echo form_dropdown('catalog_default_sort_order', $order_options, get_form_value('catalog_default_sort_order', isset($catalog_default_sort_order) ? $catalog_default_sort_order : ''));
        ?>
	</div>
</fieldset>

<fieldset class="field-expanded ">
	<legend><i class="fas fa-search mr-3" style="color:#007bff;"></i><?php echo t('fulltext_search');?></legend>

	<div class="field">
        <label style="height:75px;"><?php echo t('search_provider');?></label>
        <div>
            <input type="radio" name="search_provider" value="db" <?php echo (isset($search_provider) && $search_provider=='db') ? 'checked="checked"' : ''; ?>/> <?php echo t('search_provider_db');?><br/>
            <input type="radio" name="search_provider" value="opensearch" <?php echo (!isset($search_provider) || $search_provider=='opensearch') ? 'checked="checked"' : ''; ?>/> <?php echo t('search_provider_opensearch');?><br/>
            <input type="radio" name="search_provider" value="solr" <?php echo (isset($search_provider) && $search_provider=='solr') ? 'checked="checked"' : ''; ?>/> <?php echo t('search_provider_solr');?>
            <br/><span class="field-note"><?php echo t('search_provider_note');?></span>
        </div>
	</div>
</fieldset>

<fieldset class="field-expanded ">
	<legend><i class="fas fa-user-circle mr-3" style="color:#007bff;"></i><?php echo t('site_login');?></legend>
    <div class="field">
            <label style="height:50px;" for="<?php echo 'site_password_protect'; ?>"><?php echo t('password_protect_website');?></label>
            <div>
                <input type="radio"  name="site_password_protect" value="yes" <?php echo ($site_password_protect=='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('require_all_users_to_login');?><br/>
                <input type="radio"  name="site_password_protect" value="no" <?php echo ($site_password_protect!='yes') ? 'checked="checked"' : ''; ?>/> <?php echo t('login_not_required');?>
            </div>
    </div>
    
    <div class="field">
            <label for="<?php echo 'login_timeout'; ?>"><?php echo t('login_timeout_in_min');?></label>
            <input class="form-control" name="login_timeout" type="text" id="login_timeout"  value="<?php echo get_form_value('login_timeout',isset($login_timeout) ? $login_timeout : ''); ?>"/>
    </div>
    
    <div class="field">
            <label for="<?php echo 'min_password_length'; ?>"><?php echo t('min_password_length');?></label>
            <input class="form-control" name="min_password_length" type="text" id="min_password_length"  value="<?php echo get_form_value('min_password_length',isset($min_password_length) ? $min_password_length : ''); ?>"/>
    </div>
</fieldset>


<fieldset class="field-expanded ">

        <legend><i class="fas fa-chart-line mr-3" style="color:#007bff;"></i><?php echo t('Google Analytics');?></legend>
    
    <div class="field">
            <label for="google_analytics"><?php echo t('Google analytics UA code');?></label>
            <input class="form-control" name="google_ua_code" type="text" id="google_analytics" placeholder="UA-XXXXXXXX-X"  value="<?php echo get_form_value('google_ua_code',isset($google_ua_code) ? $google_ua_code : ''); ?>"/>
    </div>
    
</fieldset>


<fieldset class="field-expanded ">
	<legend><i class="fas fa-tools mr-3" style="color:#007bff;"></i><?php echo t('mail_settings');?></legend>

        <div class="field m-3">
        <a class="btn btn-outline-primary" href="<?php echo site_url('admin/configurations/test_email');?>"><i class="fas fa-tools mr-1" style="color:#007bff;"></i><?php echo t('test_email_configurations');?></a>
        </div>

    <?php if (file_exists(APPPATH.'/config/email.php')):?>
    	<div class="field warning">
    	    <h5><i class="fas fa-info-circle mr-2"></i><?php echo t('email_configuration_info');?></h5>
    	    <p><?php echo t('email_configuration_file_info');?></p>
    	    <p><strong><?php echo t('file_location');?>:</strong> <code>application/config/email.php</code></p>
    	    <p><?php echo t('email_driver_system_info');?></p>
    	</div>
    	
    	<div class="field">
    	    <h6><?php echo t('available_email_drivers');?></h6>
    	    <ul class="list-unstyled">
    	        <li><strong>SMTP:</strong> <?php echo t('smtp_driver_description');?></li>
    	        <li><strong>SendGrid:</strong> <?php echo t('sendgrid_driver_description');?></li>
    	        <li><strong>Microsoft Graph:</strong> <?php echo t('microsoft_graph_driver_description');?></li>
    	    </ul>
    	</div>
    	
    	<div class="field">
    	    <h6><?php echo t('configuration_example');?></h6>
    	    <pre class="bg-light p-3"><code>// Email Driver Configuration
$config['email_driver'] = 'smtp';  // 'smtp', 'sendgrid', 'microsoft_graph'

// SMTP Configuration
$config['smtp_host'] = 'smtp.example.com';
$config['smtp_user'] = 'user@example.com';
$config['smtp_pass'] = 'password';
$config['smtp_port'] = 587;
$config['smtp_crypto'] = 'tls';

// SendGrid Configuration (when email_driver = 'sendgrid')
$config['sendgrid_api_key'] = 'your-api-key';

// Microsoft Graph Configuration (when email_driver = 'microsoft_graph')
$config['microsoft_graph_client_id'] = 'your-client-id';
$config['microsoft_graph_client_secret'] = 'your-client-secret';
$config['microsoft_graph_tenant_id'] = 'your-tenant-id';</code></pre>
    	</div>
    <?php else:?>        
    
    <!-- Email Driver Selection -->
    <div class="field">
        <label for="email_driver"><?php echo t('email_driver');?></label>
        <select class="form-control" name="email_driver" id="email_driver" onchange="toggleDriverFields()">
            <option value="smtp" <?php echo (isset($email_driver) && $email_driver=='smtp') ? 'selected' : ''; ?>><?php echo t('smtp_driver');?></option>
            <option value="sendmail" <?php echo (isset($email_driver) && $email_driver=='sendmail') ? 'selected' : ''; ?>><?php echo t('sendmail_driver');?></option>
            <option value="sendgrid" <?php echo (isset($email_driver) && $email_driver=='sendgrid') ? 'selected' : ''; ?>><?php echo t('sendgrid_driver');?></option>
            <option value="microsoft_graph" <?php echo (isset($email_driver) && $email_driver=='microsoft_graph') ? 'selected' : ''; ?>><?php echo t('microsoft_graph_driver');?></option>
        </select>
        <small class="form-text text-muted"><?php echo t('email_driver_help');?></small>
    </div>
    
    <!-- Legacy Protocol Selection (for backward compatibility) -->
    <div class="field">
        <label style="height:50px;" for="<?php echo 'mail_protocol'; ?>"><?php echo t('select_mail_protocol');?></label>
        <div>
        <input type="radio" value="mail" name="mail_protocol" <?php echo ($mail_protocol=='mail') ? 'checked="checked"' : ''; ?>/> <?php echo t('use_php_mail');?>  <br/>
        <input type="radio" value="smtp" name="mail_protocol" <?php echo ($mail_protocol=='smtp') ? 'checked="checked"' : ''; ?>/> <?php echo t('use_smtp');?><br/>
        </div>
    </div>
    
    <!-- SMTP Configuration -->
    <div id="smtp_config" class="driver-config">
        <h5><?php echo t('smtp_configuration');?></h5>
        <div class="field">
            <label for="<?php echo 'smtp_host'; ?>"><?php echo t('smtp_host');?></label>
            <input class="form-control" name="smtp_host" type="text" id="smtp_host"  value="<?php echo get_form_value('smtp_host',isset($smtp_host) ? $smtp_host : ''); ?>"/>
        </div>
        
        <div class="field">
            <label for="<?php echo 'smtp_port'; ?>"><?php echo t('smtp_port');?></label>
            <input class="form-control" name="smtp_port" type="text" id="smtp_port"  value="<?php echo get_form_value('smtp_port',isset($smtp_port) ? $smtp_port : ''); ?>"/>
        </div>
        
        <div class="field">
            <label for="<?php echo 'smtp_user'; ?>"><?php echo t('smtp_user');?></label>
            <input class="form-control" name="smtp_user" type="text" id="smtp_user"  value="<?php echo get_form_value('smtp_user',isset($smtp_user) ? $smtp_user : ''); ?>"/>
        </div>
        
        <div class="field">
            <label for="<?php echo 'smtp_pass'; ?>"><?php echo t('smtp_password');?></label>
            <input class="form-control" name="smtp_pass" type="password" id="smtp_pass"  value="<?php echo get_form_value('smtp_pass',isset($smtp_pass) ? $smtp_pass : ''); ?>"/>
        </div>
        
        <div class="field">
            <label for="<?php echo 'smtp_auth'; ?>"><?php echo t('smtp_auth');?></label>
            <select class="form-control" name="smtp_auth" id="smtp_auth">
                <option value=""><?php echo t('auto');?></option>
                <option value="1" <?php echo (isset($smtp_auth) && $smtp_auth=='1') ? 'selected' : ''; ?>><?php echo t('yes');?></option>
                <option value="0" <?php echo (isset($smtp_auth) && $smtp_auth=='0') ? 'selected' : ''; ?>><?php echo t('no');?></option>
            </select>
        </div>
        
        <div class="field">
            <label for="<?php echo 'smtp_crypto'; ?>"><?php echo t('smtp_crypto');?></label>
            <select class="form-control" name="smtp_crypto" id="smtp_crypto">
                <option value=""><?php echo t('none');?></option>
                <option value="tls" <?php echo (isset($smtp_crypto) && $smtp_crypto=='tls') ? 'selected' : ''; ?>>TLS</option>
                <option value="ssl" <?php echo (isset($smtp_crypto) && $smtp_crypto=='ssl') ? 'selected' : ''; ?>>SSL</option>
            </select>
        </div>
    </div>
    
    <!-- SendGrid Configuration -->
    <div id="sendgrid_config" class="driver-config" style="display:none;">
        <h5><?php echo t('sendgrid_configuration');?></h5>
        <div class="field">
            <label for="sendgrid_api_key"><?php echo t('sendgrid_api_key');?></label>
            <input class="form-control" name="sendgrid_api_key" type="password" id="sendgrid_api_key" value="<?php echo get_form_value('sendgrid_api_key',isset($sendgrid_api_key) ? $sendgrid_api_key : ''); ?>"/>
            <small class="form-text text-muted"><?php echo t('sendgrid_api_key_help');?></small>
        </div>
    </div>
    
    <!-- Microsoft Graph Configuration -->
    <div id="microsoft_graph_config" class="driver-config" style="display:none;">
        <h5><?php echo t('microsoft_graph_configuration');?></h5>
        <div class="field">
            <label for="microsoft_graph_client_id"><?php echo t('microsoft_graph_client_id');?></label>
            <input class="form-control" name="microsoft_graph_client_id" type="text" id="microsoft_graph_client_id" value="<?php echo get_form_value('microsoft_graph_client_id',isset($microsoft_graph_client_id) ? $microsoft_graph_client_id : ''); ?>"/>
        </div>
        
        <div class="field">
            <label for="microsoft_graph_client_secret"><?php echo t('microsoft_graph_client_secret');?></label>
            <input class="form-control" name="microsoft_graph_client_secret" type="password" id="microsoft_graph_client_secret" value="<?php echo get_form_value('microsoft_graph_client_secret',isset($microsoft_graph_client_secret) ? $microsoft_graph_client_secret : ''); ?>"/>
        </div>
        
        <div class="field">
            <label for="microsoft_graph_tenant_id"><?php echo t('microsoft_graph_tenant_id');?></label>
            <input class="form-control" name="microsoft_graph_tenant_id" type="text" id="microsoft_graph_tenant_id" value="<?php echo get_form_value('microsoft_graph_tenant_id',isset($microsoft_graph_tenant_id) ? $microsoft_graph_tenant_id : ''); ?>"/>
            <small class="form-text text-muted"><?php echo t('microsoft_graph_tenant_id_help');?></small>
        </div>
    </div>
    
    <!-- Sendmail Configuration -->
    <div id="sendmail_config" class="driver-config" style="display:none;">
        <h5><?php echo t('sendmail_configuration');?></h5>
        <div class="field">
            <p class="text-muted"><?php echo t('sendmail_configuration_help');?></p>
        </div>
    </div>
    
    <?php endif;?>
</fieldset>

<div style="text-align:right;">
	<input class="btn btn-primary" type="submit" value="<?php echo t('update');?>" name="submit"/>
</div>

<?php echo form_close();?>
</div>
<script type="text/javascript">
	function toggle_file_url(field_show,field_hide){
		$('#'+field_show).show();
		$('#'+field_hide).hide();
	}
	
	function toggleDriverFields() {
		var selectedDriver = $('#email_driver').val();
		
		// Hide all driver config sections
		$('.driver-config').hide();
		
		// Show the selected driver config section
		if (selectedDriver === 'smtp') {
			$('#smtp_config').show();
		} else if (selectedDriver === 'sendgrid') {
			$('#sendgrid_config').show();
		} else if (selectedDriver === 'microsoft_graph') {
			$('#microsoft_graph_config').show();
		} else if (selectedDriver === 'sendmail') {
			$('#sendmail_config').show();
		}
	}
	
	$('.field-expanded > legend').click(function(e) {
                e.preventDefault();
                $(this).parent('fieldset').toggleClass("field-collapsed");
                return false;
	});
	
	$(document).ready(function() {
  		$('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');
  		
  		// Initialize driver fields visibility
  		toggleDriverFields();
	});
	
</script>

<?php
function folder_exists($folder)
{
	if (is_dir($folder)){
	  return '<span class="glyphicon glyphicon-ok ico-add-color"></span><span title="'.t('folder_exists_on_server').'"</span>';
	}
	else{
	  return '<span class="glyphicon glyphicon-remove red-color"></span><span title="'.t('path_not_found').'"</span>';
	}
}

function get_languages()
{
	$languages = scandir(APPPATH.'language/');
	foreach($languages as $lang){
          if ($lang!=='.' && $lang!=='..' && $lang!=='.DS_Store'){
             $output[$lang]=$lang;
          }	
	}	
	return $output;
}
?>
