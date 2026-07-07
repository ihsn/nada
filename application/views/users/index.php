<div class="container-fluid content-fluid page-users-index">

<style type="text/css">
.page-users-index .user-access-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.page-users-index .user-access-chip {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  line-height: 1.4;
  white-space: nowrap;
}
.page-users-index .user-access-chip--role {
  background: #e9ecef;
  color: #495057;
}
.page-users-index .user-access-chip--collection {
  background: #cfe2ff;
  color: #084298;
  text-decoration: none;
}
.page-users-index .user-access-chip--collection:hover {
  background: #b6d4fe;
  color: #052c65;
  text-decoration: none;
}
.page-users-index .page-users-table {
  table-layout: fixed;
}
.page-users-index .col-roles-collections {
  max-width: 300px;
  width: 300px;
}
.page-users-index .users-filter-card {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 12px 14px;
  margin-bottom: 16px;
}
.page-users-index .bulk-actions-toolbar {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 6px 10px;
  margin-bottom: 15px;
}
.page-users-index .bulk-actions-toolbar.disabled {
  opacity: 0.55;
  pointer-events: none;
}
.page-users-index .status-pending {
  color: #856404;
}
</style>

<?php
$user_groups = (isset($user_groups) && is_array($user_groups)) ? $user_groups : array();
$user_collections = (isset($user_collections) && is_array($user_collections)) ? $user_collections : array();
$roles = (isset($roles) && is_array($roles)) ? $roles : array();
$rows = (isset($rows) && is_array($rows)) ? $rows : array();
?>

  <?php $message = $this->session->flashdata('message');?>
  <?php echo ($message != "") ? '<div class="alert alert-success">' . $message . '</div>' : ''; ?>
  <?php $error = $this->session->flashdata('error');?>
  <?php echo ($error != "") ? '<div class="alert alert-danger">' . $error . '</div>' : ''; ?>

  <?php if (!isset($hide_form)): ?>
    <div class="page-links text-right m-3 pb-3">
      <a href="<?php echo site_url('admin/users/add'); ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus-circle" aria-hidden="true">&nbsp;</i> <?php echo t('create_user_account'); ?></a>
      <a href="<?php echo site_url('admin/users/api_keys'); ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-key" aria-hidden="true">&nbsp;</i> <?php echo t('api_keys_management'); ?></a>
      <?php if (!empty($show_permissions_link)): ?>
      <a href="<?php echo site_url('admin/permissions'); ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-users" aria-hidden="true">&nbsp;</i> <?php echo t('User roles'); ?></a>
      <?php endif; ?>
    </div>

    <h1 class="page-title mt-3 mb-3"><?php echo t('title_user_management'); ?></h1>

    <div class="users-filter-card">
      <form method="GET" id="user-search">
        <div class="form-row align-items-end">
          <div class="form-group col-md-3 mb-2">
            <label class="small mb-1" for="keywords"><?php echo t('search'); ?></label>
            <input type="text" class="form-control form-control-sm" name="keywords" id="keywords" value="<?php echo form_prep($this->input->get('keywords')); ?>"/>
          </div>
          <div class="form-group col-md-2 mb-2">
            <label class="small mb-1" for="field"><?php echo t('search'); ?></label>
            <select name="field" id="field" class="form-control form-control-sm">
              <option value="all" <?php echo ($this->input->get('field') == 'all') ? 'selected="selected"' : ''; ?>><?php echo t('all_fields'); ?></option>
              <option value="username" <?php echo ($this->input->get('field') == 'username') ? 'selected="selected"' : ''; ?>><?php echo t('username'); ?></option>
              <option value="email" <?php echo ($this->input->get('field') == 'email') ? 'selected="selected"' : ''; ?>><?php echo t('email'); ?></option>
            </select>
          </div>
          <div class="form-group col-md-2 mb-2">
            <label class="small mb-1" for="status_filter"><?php echo t('status'); ?></label>
            <select name="status_filter" id="status_filter" class="form-control form-control-sm">
              <option value=""><?php echo t('all_users'); ?></option>
              <option value="active" <?php echo $this->input->get('status_filter') === 'active' ? 'selected' : ''; ?>><?php echo t('active'); ?></option>
              <option value="pending" <?php echo $this->input->get('status_filter') === 'pending' ? 'selected' : ''; ?>><?php echo t('pending_activation'); ?></option>
              <option value="disabled" <?php echo $this->input->get('status_filter') === 'disabled' ? 'selected' : ''; ?>><?php echo t('inactive'); ?></option>
            </select>
          </div>
          <div class="form-group col-md-2 mb-2">
            <label class="small mb-1" for="role_filter"><?php echo t('User roles'); ?></label>
            <select name="role_filter" id="role_filter" class="form-control form-control-sm">
              <option value=""><?php echo t('all_roles'); ?></option>
              <?php foreach ($roles as $role): ?>
                <option value="<?php echo (int) $role['id']; ?>" <?php echo (int) $this->input->get('role_filter') === (int) $role['id'] ? 'selected' : ''; ?>><?php echo form_prep($role['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-2 mb-2">
            <label class="small mb-1" for="collection_access"><?php echo t('collection_access'); ?></label>
            <select name="collection_access" id="collection_access" class="form-control form-control-sm">
              <option value=""><?php echo t('all_users'); ?></option>
              <option value="has" <?php echo $this->input->get('collection_access') === 'has' ? 'selected' : ''; ?>><?php echo t('has_collection_access'); ?></option>
              <option value="none" <?php echo $this->input->get('collection_access') === 'none' ? 'selected' : ''; ?>><?php echo t('no_collection_access'); ?></option>
            </select>
          </div>
          <div class="form-group col-md-2 mb-2">
            <label class="small mb-1" for="api_keys_filter"><?php echo t('api_keys'); ?></label>
            <select name="api_keys_filter" id="api_keys_filter" class="form-control form-control-sm">
              <option value=""><?php echo t('all_users'); ?></option>
              <option value="has" <?php echo $this->input->get('api_keys_filter') === 'has' ? 'selected' : ''; ?>><?php echo t('has_api_keys'); ?></option>
              <option value="none" <?php echo $this->input->get('api_keys_filter') === 'none' ? 'selected' : ''; ?>><?php echo t('no_api_keys'); ?></option>
            </select>
          </div>
          <div class="form-group col-md-2 mb-2">
            <label class="small mb-1" for="last_login_filter"><?php echo t('last_login'); ?></label>
            <select name="last_login_filter" id="last_login_filter" class="form-control form-control-sm">
              <option value=""><?php echo t('all_users'); ?></option>
              <option value="today" <?php echo $this->input->get('last_login_filter') === 'today' ? 'selected' : ''; ?>><?php echo t('today'); ?></option>
              <option value="week" <?php echo $this->input->get('last_login_filter') === 'week' ? 'selected' : ''; ?>><?php echo t('this_week'); ?></option>
              <option value="month" <?php echo $this->input->get('last_login_filter') === 'month' ? 'selected' : ''; ?>><?php echo t('this_month'); ?></option>
              <option value="never" <?php echo $this->input->get('last_login_filter') === 'never' ? 'selected' : ''; ?>><?php echo t('never_logged_in'); ?></option>
            </select>
          </div>
          <div class="form-group col-md-1 mb-2">
            <button type="submit" class="btn btn-primary btn-sm btn-block"><?php echo t('apply_filters'); ?></button>
          </div>
        </div>
        <?php if ($this->input->get('keywords') || $this->input->get('status_filter') || $this->input->get('role_filter') || $this->input->get('collection_access') || $this->input->get('api_keys_filter') || $this->input->get('last_login_filter')): ?>
          <a class="btn btn-link btn-sm px-0" href="<?php echo site_url('admin/users'); ?>"><?php echo t('clear_filters'); ?></a>
        <?php endif; ?>
      </form>
    </div>

  <?php endif;?>

  <?php
    $page_nums = '';
    $pager = '';
    $sort_by = $this->input->get('sort_by');
    $sort_order = $this->input->get('sort_order');
    $page_url = site_url() . '/' . $this->uri->uri_string();

    if ($rows) {
      $page_nums = $this->pagination->create_links();
      $current_page = ($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

      if ($this->pagination->cur_page > 0) {
        $to_page = $this->pagination->per_page * $this->pagination->cur_page;
        if ($to_page > $this->pagination->get_total_rows()) {
          $to_page = $this->pagination->get_total_rows();
        }
        $pager = sprintf(t('showing %d-%d of %d'), (($this->pagination->cur_page - 1) * $this->pagination->per_page + 1), $to_page, $this->pagination->get_total_rows());
      } else {
        $pager = sprintf(t('showing %d-%d of %d'), $current_page, $this->pagination->get_total_rows(), $this->pagination->get_total_rows());
      }
    }
  ?>

  <?php if (!empty($can_bulk_edit)): ?>
  <div id="bulk-actions-toolbar" class="bulk-actions-toolbar disabled">
    <div class="dropdown">
      <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="bulkActionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-cog"></i> <?php echo t('actions'); ?> (<span id="bulk-selected-count">0</span>)
      </button>
      <div class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
        <a class="dropdown-item" href="#" onclick="bulkAction('activate'); return false;"><i class="fa fa-check"></i> <?php echo t('activate_users'); ?></a>
        <a class="dropdown-item" href="#" onclick="bulkAction('deactivate'); return false;"><i class="fa fa-ban"></i> <?php echo t('deactivate_users'); ?></a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="#" onclick="bulkAction('assign-roles'); return false;"><i class="fa fa-users"></i> <?php echo t('assign_roles'); ?></a>
        <a class="dropdown-item" href="#" onclick="bulkAction('remove-roles'); return false;"><i class="fa fa-user-minus"></i> <?php echo t('remove_roles'); ?></a>
        <?php if (!empty($can_bulk_delete)): ?>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="#" onclick="bulkAction('delete'); return false;"><i class="fa fa-trash"></i> <?php echo t('delete_users'); ?></a>
        <?php endif; ?>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="#" onclick="clearSelection(); return false;"><i class="fa fa-times"></i> <?php echo t('clear_selection'); ?></a>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($rows): ?>
  <div class="nada-pagination text-right">
    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums; ?>
  </div>

  <table class="table table-striped table-sm page-users-table" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
      <?php if (!empty($can_bulk_edit)): ?>
      <th style="width:32px;"><input type="checkbox" id="select-all-users" onchange="toggleAllUsers(this)"></th>
      <?php endif; ?>
      <th><?php echo create_sort_link($sort_by, $sort_order, 'username', t('username'), $page_url); ?></th>
      <th><?php echo create_sort_link($sort_by, $sort_order, 'email', t('email'), $page_url); ?></th>
      <th class="col-roles-collections"><?php echo t('roles_and_collections'); ?></th>
      <th><?php echo create_sort_link($sort_by, $sort_order, 'active', t('status'), $page_url); ?></th>
      <th><?php echo t('api_keys'); ?></th>
      <th><?php echo create_sort_link($sort_by, $sort_order, 'country', t('country'), $page_url); ?></th>
      <th><?php echo create_sort_link($sort_by, $sort_order, 'created_on', t('join_date'), $page_url); ?></th>
      <th><?php echo create_sort_link($sort_by, $sort_order, 'last_login', t('last_login'), $page_url); ?></th>
      <th><?php echo t('actions'); ?></th>
    </tr>
    <?php $tr_class = ''; ?>
    <?php foreach ($rows as $row): ?>
        <?php $row = (object) $row; ?>
        <?php $tr_class = ($tr_class === '') ? 'alternate' : ''; ?>
    <tr class="<?php echo $tr_class; ?>" valign="top">
      <?php if (!empty($can_bulk_edit)): ?>
      <td><input type="checkbox" class="user-checkbox" value="<?php echo (int) $row->id; ?>" onchange="updateBulkToolbar()"></td>
      <?php endif; ?>
      <td>
        <div><a href="<?php echo site_url('admin/users/edit/' . $row->id); ?>"><?php echo form_prep($row->username); ?></a></div>
      </td>
      <td><?php echo form_prep($row->email); ?>&nbsp;</td>
      <td class="col-roles-collections">
        <div class="user-access-chips">
            <?php if (!empty($user_groups[$row->id])): ?>
              <?php foreach ($user_groups[$row->id] as $group): ?>
                <span class="user-access-chip user-access-chip--role"><?php echo htmlspecialchars($group['name']); ?></span>
              <?php endforeach;?>
            <?php endif;?>
            <?php if (!empty($user_collections[$row->id])): ?>
              <?php foreach ($user_collections[$row->id] as $collection): ?>
                <?php
                  $collection_label = $collection['title'] !== '' ? $collection['title'] : $collection['repositoryid'];
                  $collection_url = site_url('admin/collections#/permissions/' . (int) $collection['id']);
                ?>
                <a class="user-access-chip user-access-chip--collection" href="<?php echo $collection_url; ?>"><?php echo htmlspecialchars($collection_label); ?></a>
              <?php endforeach;?>
            <?php endif;?>
        </div>
      </td>
      <td>
        <?php if ((int) $row->active === 1): ?>
          <?php echo t('ACTIVE'); ?>
        <?php elseif (!empty($row->activation_code)): ?>
          <span class="status-pending"><?php echo t('pending_activation'); ?></span>
        <?php else: ?>
          <?php echo t('DISABLED'); ?>
        <?php endif; ?>
      </td>
      <td>
        <?php
          $key_count = isset($api_key_counts[$row->id]) ? $api_key_counts[$row->id] : 0;
          if ($key_count > 0) {
            echo '<a href="' . site_url('admin/users/api_keys?user_search=' . urlencode($row->username)) . '">' . $key_count . '</a>';
          } else {
            echo '0';
          }
        ?>
      </td>
      <td><?php echo form_prep($row->country); ?></td>
      <td><?php echo date('m-d-Y', $row->created_on); ?></td>
      <?php if ($row->last_login > $row->created_on): ?>
      <td><?php echo date('m-d-Y', $row->last_login); ?></td>
      <?php else: ?>
        <td>-</td>
      <?php endif;?>
      <td>
          <a href="<?php echo current_url(); ?>/edit/<?php echo $row->id; ?>"><?php echo t('edit'); ?></a> |
          <a href="<?php echo current_url(); ?>/delete/<?php echo $row->id; ?>"><?php echo t('delete'); ?></a>
      </td>
    </tr>
  <?php endforeach;?>
  </table>

  <div class="nada-pagination text-right">
    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums; ?>
  </div>

  <?php else: ?>
  <div class="alert alert-info"><?php echo t('no_records_found'); ?></div>
  <?php endif;?>

<?php if (!empty($can_bulk_edit)): ?>
<div class="modal fade" id="bulkRoleModal" tabindex="-1" role="dialog" aria-labelledby="bulkRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bulkRoleModalLabel"><?php echo t('bulk_assign_roles'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo t('cancel'); ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="roleAssignmentContent"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><?php echo t('cancel'); ?></button>
        <button type="button" class="btn btn-primary btn-sm" id="assignRolesBtn" onclick="processBulkRoleAssignment()"><?php echo t('assign_roles'); ?></button>
        <button type="button" class="btn btn-danger btn-sm" id="removeRolesBtn" onclick="processBulkRoleRemoval()" style="display:none;"><?php echo t('remove_roles'); ?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
var selectedUsers = new Set();

function toggleAllUsers(checkbox) {
  document.querySelectorAll('.user-checkbox').forEach(function(cb) {
    cb.checked = checkbox.checked;
    if (checkbox.checked) {
      selectedUsers.add(cb.value);
    } else {
      selectedUsers.delete(cb.value);
    }
  });
  updateBulkToolbar();
}

function updateBulkToolbar() {
  selectedUsers.clear();
  document.querySelectorAll('.user-checkbox:checked').forEach(function(cb) {
    selectedUsers.add(cb.value);
  });

  var toolbar = document.getElementById('bulk-actions-toolbar');
  var countEl = document.getElementById('bulk-selected-count');
  var selectAll = document.getElementById('select-all-users');
  var total = document.querySelectorAll('.user-checkbox').length;
  var checked = selectedUsers.size;

  if (countEl) {
    countEl.textContent = checked;
  }
  if (toolbar) {
    toolbar.classList.toggle('disabled', checked === 0);
  }
  if (selectAll) {
    selectAll.checked = total > 0 && checked === total;
    selectAll.indeterminate = checked > 0 && checked < total;
  }
}

function clearSelection() {
  document.querySelectorAll('.user-checkbox').forEach(function(cb) { cb.checked = false; });
  var selectAll = document.getElementById('select-all-users');
  if (selectAll) {
    selectAll.checked = false;
    selectAll.indeterminate = false;
  }
  selectedUsers.clear();
  updateBulkToolbar();
}

function bulkAction(action) {
  if (selectedUsers.size === 0) {
    alert(<?php echo json_encode(t('please_select_users')); ?>);
    return;
  }
  var userIds = Array.from(selectedUsers);
  switch (action) {
    case 'delete':
      if (confirm(<?php echo json_encode(t('confirm_bulk_delete_users')); ?>)) {
        performBulkAction('delete', userIds);
      }
      break;
    case 'activate':
      if (confirm(<?php echo json_encode(t('confirm_bulk_activate_users')); ?>)) {
        performBulkAction('activate', userIds);
      }
      break;
    case 'deactivate':
      if (confirm(<?php echo json_encode(t('confirm_bulk_deactivate_users')); ?>)) {
        performBulkAction('deactivate', userIds);
      }
      break;
    case 'assign-roles':
      showRoleAssignmentModal(userIds);
      break;
    case 'remove-roles':
      showRoleRemovalModal(userIds);
      break;
  }
}

function performBulkAction(action, userIds) {
  var formData = new FormData();
  formData.append('action', action);
  formData.append('user_ids', JSON.stringify(userIds));
  fetch(<?php echo json_encode(site_url('admin/users/bulk_action')); ?>, {
    method: 'POST',
    body: formData
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    if (data.success) {
      location.reload();
    } else {
      alert(data.message || <?php echo json_encode(t('operation_failed')); ?>);
    }
  })
  .catch(function() {
    alert(<?php echo json_encode(t('error_occurred')); ?>);
  });
}

function showRoleAssignmentModal(userIds) {
  document.getElementById('bulkRoleModalLabel').textContent = <?php echo json_encode(t('bulk_assign_roles')); ?>;
  document.getElementById('assignRolesBtn').style.display = '';
  document.getElementById('removeRolesBtn').style.display = 'none';
  document.getElementById('roleAssignmentContent').innerHTML = '<div class="text-center py-3"><?php echo t('js_loading'); ?>...</div>';
  if (typeof jQuery !== 'undefined') {
    jQuery('#bulkRoleModal').modal('show');
    jQuery.get(<?php echo json_encode(site_url('admin/users/bulk_assign_roles')); ?>, { user_ids: userIds.join(',') }, function(html) {
      document.getElementById('roleAssignmentContent').innerHTML = html;
    });
  }
}

function showRoleRemovalModal(userIds) {
  document.getElementById('bulkRoleModalLabel').textContent = <?php echo json_encode(t('bulk_remove_roles')); ?>;
  document.getElementById('assignRolesBtn').style.display = 'none';
  document.getElementById('removeRolesBtn').style.display = '';
  document.getElementById('roleAssignmentContent').innerHTML = '<div class="text-center py-3"><?php echo t('js_loading'); ?>...</div>';
  if (typeof jQuery !== 'undefined') {
    jQuery('#bulkRoleModal').modal('show');
    jQuery.get(<?php echo json_encode(site_url('admin/users/bulk_remove_roles')); ?>, { user_ids: userIds.join(',') }, function(html) {
      document.getElementById('roleAssignmentContent').innerHTML = html;
    });
  }
}

function processBulkRoleAssignment() {
  var form = document.getElementById('bulkRoleForm');
  if (!form) {
    return;
  }
  if (typeof jQuery !== 'undefined') {
    jQuery.post(form.action, jQuery(form).serialize(), function() {
      jQuery('#bulkRoleModal').modal('hide');
      location.reload();
    });
  } else {
    form.submit();
  }
}

function processBulkRoleRemoval() {
  var form = document.getElementById('bulkRoleRemovalForm');
  if (!form) {
    return;
  }
  if (typeof jQuery !== 'undefined') {
    jQuery.post(form.action, jQuery(form).serialize(), function() {
      jQuery('#bulkRoleModal').modal('hide');
      location.reload();
    });
  } else {
    form.submit();
  }
}
</script>
<?php endif; ?>

</div>
