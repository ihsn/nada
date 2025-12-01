<style>
.description{color:gray;font-size:11px;}
.user-role{
  text-transform:capitalize;
}
.user_groups{
  padding-left:20px;
}
label{font-weight:bold;}
.user-role{font-weight:normal;}
</style>
<div class='container-fluid users-edit-page'>

    <h3 class="page title mt-4 mb-3"><?php echo $page_title; ?></h3>
    <?php if (validation_errors()): ?>
        <div class="alert alert-danger">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif;?>

    <?php $error = $this->session->flashdata('error');?>
    <?php echo ($error != "") ? '<div class="alert alert-danger">' . $error . '</div>' : ''; ?>

    <?php $message = $this->session->flashdata('message');?>
    <?php echo ($message != "") ? '<div class="alert alert-success">' . $message . '</div>' : ''; ?>

    <?php
      $form_action_url = site_url() . '/admin/users';
      $current_user_id = $this->uri->segment(4); // Get user ID from URI
      if ($this->uri->segment(3) == 'add') {
          $form_action_url .= '/add';
      } else {
          $form_action_url .= '/edit/' . $current_user_id;
      }
      
      // Get active tab from URL or default to 'edit'
      $active_tab = $this->input->get('tab') ? $this->input->get('tab') : 'edit';
    ?>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo ($active_tab == 'edit') ? 'active' : ''; ?>" 
               id="edit-tab" 
               data-toggle="tab" 
               href="#edit" 
               role="tab" 
               aria-controls="edit" 
               aria-selected="<?php echo ($active_tab == 'edit') ? 'true' : 'false'; ?>">
                <?php echo t('edit_user_account'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($active_tab == 'api_keys') ? 'active' : ''; ?>" 
               id="api-keys-tab" 
               data-toggle="tab" 
               href="#api-keys" 
               role="tab" 
               aria-controls="api-keys" 
               aria-selected="<?php echo ($active_tab == 'api_keys') ? 'true' : 'false'; ?>">
                <?php echo t('api_keys'); ?>
            </a>
        </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <!-- Edit User Tab -->
        <div class="tab-pane fade <?php echo ($active_tab == 'edit') ? 'show active' : ''; ?>" 
             id="edit" 
             role="tabpanel" 
             aria-labelledby="edit-tab">
            
            <div class="row">
                <div class="col-md-8">
                    <?php echo form_open($form_action_url, array('class' => 'form register', 'autocomplete' => 'off')); ?>

      <?php echo form_input($id); ?>
      <div class="col form-group">
          <label for="username"><?php echo t('username'); ?><span class="required">*</span></label>
          <?php echo form_input($username); ?>
      </div>

      <div class="col form-group">
          <label for="email"><?php echo t('email'); ?><span class="required">*</span></label>
          <?php echo form_input($email); ?>
      </div>
        
      <div class="col form-row">
        <div class="col form-group">
            <label for="first_name"><?php echo t('first_name'); ?><span class="required">*</span></label>
            <?php echo form_input($first_name); ?>
        </div>

        <div class="col form-group">
            <label for="last_name"><?php echo t('last_name'); ?><span class="required">*</span></label>
            <?php echo form_input($last_name); ?>
        </div>
    </div>

    <div class="col form-row">
      <div class="col form-group">
          <label for="company"><?php echo t('company'); ?></label>
          <?php echo form_input($company); ?>
      </div>

      <div class="col form-group">
          <label for="phone1"><?php echo t('phone'); ?></label>
          <?php echo form_input($phone1); ?>
      </div>

    </div>
    
    <div class="col-6 form-group">
      <label for="country"><?php echo t('country'); ?></label>
      <?php echo form_dropdown('country', $options_country, get_form_value("country", isset($country) ? $country : ''), 'class="form-control"'); ?>
    </div>
      
    <div class="col form-group">
      <span class="text-danger"><em><?php echo t('leave_password_blank'); ?></em></span>
    </div>

    <div class="col form-row">
      <div class="col-6 form-group">
          <label for="password"><?php echo t('password'); ?><span class="required">*</span></label>
          <?php echo form_input($password); ?>
      </div>

      <div class="col-6 form-group">
          <label for="password_confirm"><?php echo t('password_confirmation'); ?><span class="required">*</span></label>
          <?php echo form_input($password_confirm); ?>
      </div>
    </div>

    <div class="col form-group">
      <label for="password_confirm"><?php echo t('user_account_status'); ?></label>
      <span style="padding-right:10px;"><?php echo form_radio('active', '1', $active == 1); ?> <?php echo t('user_active'); ?> </span>
      <span><?php echo form_radio('active', '0', $active != 1); ?> <?php echo t('user_blocked'); ?> </span>
    </div>
  
    <div class="col form-group">
        <label for="user_groups">
          <?php echo t('User roles');?> 
        </label>
        <div class="user_groups">
        <?php foreach($roles as $role): $role_selected=false;?>
        	<?php if (isset($user_role) && count($user_role)>0 && in_array($role['id'],$user_role)):?>
          <?php $role_selected=true;?>
          <?php endif;?>
            <div class="checkbox">
              <label class="user-role">
                <input type="checkbox" <?php echo $role_selected ? 'checked="checked"' : '';?> name="role[]" value="<?php echo $role['id'];?>"> <?php echo t($role['name']);?>
              </label>
          </div>
	  	  <?php endforeach;?>
        </div>        
    </div>

                    <div class="col form-group">
                        <span class="custom-fields"><?php echo form_submit('submit', t('update'), array('class' => 'btn btn-primary btn-sm')); ?></span>
                        <?php echo anchor('admin/users', t('cancel'), array('class' => 'btn btn-secondary btn-sm')); ?>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
        
        <!-- API Keys Tab -->
        <div class="tab-pane fade <?php echo ($active_tab == 'api_keys') ? 'show active' : ''; ?>" 
             id="api-keys" 
             role="tabpanel" 
             aria-labelledby="api-keys-tab">
            
            <div class="row">
                <div class="col-md-12">
                    
                    <?php if ($this->session->flashdata('admin_new_api_key') && $this->session->flashdata('admin_new_api_key_user_id') == $current_user_id): ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-primary alert-dismissible fade show" role="alert">
                                <h5><?php echo t('new_api_key_generated'); ?></h5>
                                <p><strong><?php echo t('save_this_key'); ?></strong> <?php echo t('key_will_not_be_shown_again'); ?></p>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control font-monospace bg-light" id="admin_new_api_key" 
                                           value="<?php echo html_escape($this->session->flashdata('admin_new_api_key')); ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyAdminApiKey()">
                                            <i class="fas fa-copy"></i>                            
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <script>
                        function copyAdminApiKey() {
                            var copyText = document.getElementById("admin_new_api_key");
                            copyText.select();
                            copyText.setSelectionRange(0, 99999);
                            document.execCommand("copy");
                            alert("<?php echo t('api_key_copied'); ?>");
                        }
                    </script>
                    <?php endif; ?>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <?php if (is_array($api_keys) && count($api_keys) < 5 || !is_array($api_keys)) : ?>
                            <a href="<?php echo site_url('admin/users/generate_api_key/' . $current_user_id); ?>" class="btn btn-primary btn-sm float-right mb-3"><?php echo t('generate_api_key'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?php if (is_array($api_keys) && count($api_keys) > 0) : ?>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?php echo t('api_key'); ?></th>
                                            <th><?php echo t('name'); ?></th>
                                            <th><?php echo t('created'); ?></th>
                                            <th><?php echo t('expires'); ?></th>
                                            <th><?php echo t('last_used'); ?></th>
                                            <th><?php echo t('status'); ?></th>
                                            <th><?php echo t('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($api_keys as $key_data) : ?>
                                            <tr>
                                                <td><code><?php echo html_escape($key_data['prefix']); ?></code></td>
                                                <td><?php echo html_escape($key_data['name'] ? $key_data['name'] : '-'); ?></td>
                                                <td><?php echo $key_data['date_created'] ? date('Y-m-d H:i', $key_data['date_created']) : '-'; ?></td>
                                                <td>
                                                    <?php if (isset($key_data['is_legacy']) && $key_data['is_legacy']): ?>
                                                        <?php echo t('legacy'); ?>
                                                    <?php else: ?>
                                                        <?php echo $key_data['expires_at'] ? date('Y-m-d H:i', $key_data['expires_at']) : t('never'); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($key_data['is_legacy']) && $key_data['is_legacy']): ?>
                                                        <?php echo t('n_a'); ?>
                                                    <?php else: ?>
                                                        <?php echo $key_data['last_used_at'] ? date('Y-m-d H:i', $key_data['last_used_at']) : t('never'); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($key_data['is_legacy']) && $key_data['is_legacy']): ?>
                                                        <span class="badge badge-info"><?php echo t('legacy'); ?></span>
                                                    <?php elseif ($key_data['is_expired']) : ?>
                                                        <span class="badge badge-warning"><?php echo t('expired'); ?></span>
                                                    <?php else : ?>
                                                        <span class="badge badge-success"><?php echo t('active'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo site_url('admin/users/manage_api_key/' . $key_data['id']); ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <?php echo t('manage'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <div class="py-3"><?php echo t('no_api_keys_found'); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
