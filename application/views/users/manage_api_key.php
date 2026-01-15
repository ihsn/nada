<div class="container-fluid content-fluid page-manage-api-key">

  <?php $message = $this->session->flashdata('message');?>
  <?php echo ($message != "") ? '<div class="alert alert-success">' . $message . '</div>' : ''; ?>
  <?php $error = $this->session->flashdata('error');?>
  <?php echo ($error != "") ? '<div class="alert alert-danger">' . $error . '</div>' : ''; ?>

  <div class="page-links text-right m-3 pb-3">
    <a href="<?php echo site_url('admin/users/api_keys'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fa fa-arrow-left" aria-hidden="true">&nbsp;</i> <?php echo t('back_to_api_keys'); ?>
    </a>
  </div>
  
  <h1 class="page-title mt-3 mb-3"><?php echo t('manage_api_key'); ?></h1>

  <div class="row">
    <div class="col-md-8">
      
      <div class="card mb-3">
        <div class="card-header py-2">
          <h5 class="mb-0"><?php echo t('api_key_details'); ?></h5>
        </div>
        <div class="card-body py-3">
          <table class="table table-borderless">
            <tr>
              <th width="200"><?php echo t('api_key'); ?>:</th>
              <td><code><?php echo html_escape($key_prefix); ?></code></td>
            </tr>
            <tr>
              <th><?php echo t('user'); ?>:</th>
              <td>
                <a href="<?php echo site_url('admin/users/edit/' . $user_id); ?>">
                  <?php echo html_escape($user_display_name); ?>
                </a>
                <div class="text-muted small"><?php echo html_escape($email); ?></div>
              </td>
            </tr>
            <?php if (isset($name) && $name): ?>
            <tr>
              <th><?php echo t('name'); ?>:</th>
              <td><?php echo html_escape($name); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
              <th><?php echo t('status'); ?>:</th>
              <td>
                <?php if ($is_revoked): ?>
                  <span class="badge badge-danger"><?php echo t('revoked'); ?></span>
                <?php elseif (isset($is_legacy) && $is_legacy): ?>
                  <span class="badge badge-info"><?php echo t('legacy'); ?></span>
                <?php elseif ($is_expired): ?>
                  <span class="badge badge-warning"><?php echo t('expired'); ?></span>
                <?php else: ?>
                  <span class="badge badge-success"><?php echo t('active'); ?></span>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <th><?php echo t('created'); ?>:</th>
              <td><?php echo $date_created ? date('Y-m-d H:i:s', $date_created) : '-'; ?></td>
            </tr>
            <tr>
              <th><?php echo t('expires'); ?>:</th>
              <td>
                <?php if (isset($is_legacy) && $is_legacy): ?>
                  <?php echo t('legacy'); ?>
                <?php elseif ($expires_at): ?>
                  <?php echo date('Y-m-d H:i:s', $expires_at); ?>
                  <?php if ($expires_at > time()): ?>
                    <span class="text-muted small">(<?php echo t('in'); ?> <?php echo round(($expires_at - time()) / (60 * 60 * 24)); ?> <?php echo t('days'); ?>)</span>
                  <?php endif; ?>
                <?php else: ?>
                  <?php echo t('never'); ?>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <th><?php echo t('last_used'); ?>:</th>
              <td>
                <?php if (isset($is_legacy) && $is_legacy): ?>
                  <?php echo t('n_a'); ?>
                <?php else: ?>
                  <?php echo $last_used_at ? date('Y-m-d H:i:s', $last_used_at) : t('never'); ?>
                <?php endif; ?>
              </td>
            </tr>
          </table>
        </div>
      </div>

      <?php if (!$is_revoked && (!isset($is_legacy) || !$is_legacy)): ?>
      <div class="card mb-3">
        <div class="card-header py-2">
          <h5 class="mb-0"><?php echo t('extend_expiry'); ?></h5>
        </div>
        <div class="card-body py-3">
          <?php echo form_open('admin/users/manage_api_key/' . $id); ?>
            <?php echo form_hidden('action', 'extend_expiry'); ?>
            <div class="form-inline">
              <div class="form-group mr-2">
                <?php echo form_dropdown('extend_months', array(
                  '1' => '1 ' . t('month'),
                  '3' => '3 ' . t('months'),
                  '6' => '6 ' . t('months'),
                  '12' => '12 ' . t('months'),
                  '24' => '24 ' . t('months'),
                  '36' => '36 ' . t('months')
                ), '12', 'class="form-control"'); ?>
              </div>
              <?php echo form_submit('submit', t('extend_expiry'), array('class' => 'btn btn-primary')); ?>
            </div>
          <?php echo form_close(); ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-header py-2">
          <h5 class="mb-0 text-danger"><?php echo t('danger_zone'); ?></h5>
        </div>
        <div class="card-body py-3">
          <?php if ($is_revoked): ?>
            <p class="text-muted mb-3"><?php echo t('api_key_already_revoked'); ?></p>
            <?php echo form_open('admin/users/manage_api_key/' . $id, array('onsubmit' => 'return confirm("' . t('confirm_permanent_delete_api_key') . '");', 'class' => 'd-inline')); ?>
              <?php echo form_hidden('action', 'delete'); ?>
              <?php echo form_submit('submit', t('delete_api_key'), array('class' => 'btn btn-danger')); ?>
            <?php echo form_close(); ?>
          <?php else: ?>
            <div class="d-inline-block mr-2">
              <?php echo form_open('admin/users/manage_api_key/' . $id, array('onsubmit' => 'return confirm("' . t('confirm_delete_api_key') . '");', 'class' => 'd-inline')); ?>
                <?php echo form_hidden('action', 'revoke'); ?>
                <?php echo form_submit('submit', t('revoke_api_key'), array('class' => 'btn btn-warning')); ?>
              <?php echo form_close(); ?>
            </div>
            <div class="d-inline-block">
              <?php echo form_open('admin/users/manage_api_key/' . $id, array('onsubmit' => 'return confirm("' . t('confirm_permanent_delete_api_key') . '");', 'class' => 'd-inline')); ?>
                <?php echo form_hidden('action', 'delete'); ?>
                <?php echo form_submit('submit', t('delete_api_key'), array('class' => 'btn btn-danger')); ?>
              <?php echo form_close(); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

