<div class="container-fluid content-fluid page-api-keys-index">

  <?php $message = $this->session->flashdata('message');?>
  <?php echo ($message != "") ? '<div class="alert alert-success">' . $message . '</div>' : ''; ?>
  <?php $error = $this->session->flashdata('error');?>
  <?php echo ($error != "") ? '<div class="alert alert-danger">' . $error . '</div>' : ''; ?>

  <div class="page-links text-right m-3 pb-3">
    <a href="<?php echo site_url('admin/users'); ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-users" aria-hidden="true">&nbsp;</i> <?php echo t('title_user_management'); ?></a>
    <a href="<?php echo site_url('admin/logs/api_logs'); ?>" class="btn btn-outline-primary btn-sm"><i class="fa fa-list-alt" aria-hidden="true">&nbsp;</i> <?php echo t('api_logs'); ?></a>
  </div>
  
  <h1 class="page-title mt-3 mb-3"><?php echo t('api_keys_management'); ?></h1>

  <?php if ($this->session->flashdata('admin_new_api_key')): ?>
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

  <form class="form-inline mb-3" method="GET" id="api-keys-search">
      <div class="form-group mr-2">
          <label for="keywords" class="sr-only"><?php echo t('api_key'); ?></label>
          <input type="text" class="form-control form-control-sm" size="20" name="keywords" id="keywords" 
                 value="<?php echo form_prep($search_keyword); ?>" 
                 placeholder="<?php echo t('api_key'); ?> <?php echo t('search'); ?>..."/>
      </div>

      <div class="form-group mr-2">
          <label for="user_search" class="sr-only"><?php echo t('user'); ?></label>
          <input type="text" class="form-control form-control-sm" size="25" name="user_search" id="user_search" 
                 value="<?php echo form_prep($user_search); ?>" 
                 placeholder="<?php echo t('user'); ?> (<?php echo t('username'); ?>/<?php echo t('email'); ?>)..."/>
      </div>

      <div class="form-group mr-2">
          <select name="status" id="status" class="form-control form-control-sm">
              <option value="active" <?php echo ($status_filter == 'active') ? 'selected="selected"' : ''; ?>><?php echo t('active'); ?></option>
              <option value="expired" <?php echo ($status_filter == 'expired') ? 'selected="selected"' : ''; ?>><?php echo t('expired'); ?></option>
              <option value="revoked" <?php echo ($status_filter == 'revoked') ? 'selected="selected"' : ''; ?>><?php echo t('revoked'); ?></option>
              <option value="all" <?php echo ($status_filter == 'all') ? 'selected="selected"' : ''; ?>><?php echo t('all'); ?></option>
          </select>
      </div>

      <div class="form-group">
          <input type="submit" class="btn btn-primary btn-sm" value="<?php echo t('search'); ?>" name="search"/>
          <?php if ($search_keyword || $user_search || ($status_filter && $status_filter != 'active')): ?>
          <a class="btn btn-default btn-sm ml-1" href="<?php echo site_url('admin/users/api_keys'); ?>"><?php echo t('reset'); ?></a>
          <?php endif;?>
      </div>
  </form>

  <?php if ($rows): ?>
  <?php
    // Pagination
    $page_nums = $this->pagination->create_links();
    $current_page = ($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

    // Sort
    $page_url = site_url() . '/' . $this->uri->uri_string();
    
    // Helper function for sort links
    $CI =& get_instance();
    function create_api_key_sort_link($sort_by, $sort_order, $field, $label, $current_sort_by, $current_sort_order, $page_url) {
        $CI =& get_instance();
        $new_order = ($current_sort_by == $field && $current_sort_order == 'asc') ? 'desc' : 'asc';
        $url = $page_url . '?sort_by=' . $field . '&sort_order=' . $new_order;
        
        $user_search = $CI->input->get('user_search');
        $status = $CI->input->get('status');
        $keywords = $CI->input->get('keywords');
        
        if ($user_search) $url .= '&user_search=' . urlencode($user_search);
        if ($status) $url .= '&status=' . $status;
        if ($keywords) $url .= '&keywords=' . urlencode($keywords);
        
        $arrow = '';
        if ($current_sort_by == $field) {
            $arrow = ($current_sort_order == 'asc') ? ' ↑' : ' ↓';
        }
        
        return '<a href="' . $url . '">' . $label . $arrow . '</a>';
    }
  ?>

  <?php
    if ($this->pagination->cur_page > 0) {
        $to_page = $this->pagination->per_page * $this->pagination->cur_page;
        if ($to_page > $this->pagination->get_total_rows()) {
            $to_page = $this->pagination->get_total_rows();
        }
        $pager = sprintf(t('showing %d-%d of %d'), (($this->pagination->cur_page - 1) * $this->pagination->per_page + (1)), $to_page, $this->pagination->get_total_rows());
    } else {
        $pager = sprintf(t('showing %d-%d of %d'), $current_page, $this->pagination->get_total_rows(), $this->pagination->get_total_rows());
    }
  ?>

  <div class="nada-pagination text-right mb-2">
    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums; ?>
  </div>

  <table class="table table-striped table-sm" width="100%" cellspacing="0" cellpadding="0">
    <tr class="header">
      <th><?php echo create_api_key_sort_link($sort_by, $sort_order, 'key_prefix', t('api_key'), $sort_by, $sort_order, $page_url); ?></th>
      <th><?php echo create_api_key_sort_link($sort_by, $sort_order, 'username', t('user'), $sort_by, $sort_order, $page_url); ?></th>
      <th><?php echo t('name'); ?></th>
      <th><?php echo create_api_key_sort_link($sort_by, $sort_order, 'date_created', t('created'), $sort_by, $sort_order, $page_url); ?></th>
      <th><?php echo create_api_key_sort_link($sort_by, $sort_order, 'expires_at', t('expires'), $sort_by, $sort_order, $page_url); ?></th>
      <th><?php echo create_api_key_sort_link($sort_by, $sort_order, 'last_used_at', t('last_used'), $sort_by, $sort_order, $page_url); ?></th>
      <th><?php echo t('status'); ?></th>
      <th><?php echo t('actions'); ?></th>
    </tr>
    <?php $tr_class = "";?>
    <?php foreach ($rows as $row): ?>
        <?php if ($tr_class == "") {
          $tr_class = "alternate";
          } else {
              $tr_class = "";
          }?>
    <tr class="<?php echo $tr_class; ?>" valign="top">
      <td><code><?php echo html_escape($row['key_prefix']); ?></code></td>
      <td>
        <div>
          <a href="<?php echo site_url('admin/users/api_keys?user_search=' . urlencode($row['email'])); ?>">
            <?php echo html_escape($row['user_display_name']); ?>
          </a>
        </div>
        <div class="description text-muted">          
            <?php echo html_escape($row['email']); ?>          
        </div>
      </td>
      <td><?php echo html_escape($row['name'] ? $row['name'] : '-'); ?></td>
      <td><?php echo $row['date_created'] ? date('Y-m-d H:i', $row['date_created']) : '-'; ?></td>
      <td>
        <?php if (isset($row['is_legacy']) && $row['is_legacy']): ?>
          <?php echo t('legacy'); ?>
        <?php else: ?>
          <?php echo $row['expires_at'] ? date('Y-m-d H:i', $row['expires_at']) : t('never'); ?>
        <?php endif; ?>
      </td>
      <td>
        <?php if (isset($row['is_legacy']) && $row['is_legacy']): ?>
          <?php echo t('n_a'); ?>
        <?php else: ?>
          <?php echo $row['last_used_at'] ? date('Y-m-d H:i', $row['last_used_at']) : t('never'); ?>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($row['is_revoked']): ?>
            <span class="badge badge-danger"><?php echo t('revoked'); ?></span>
        <?php elseif (isset($row['is_legacy']) && $row['is_legacy']): ?>
            <span class="badge badge-info"><?php echo t('legacy'); ?></span>
        <?php elseif ($row['is_expired']): ?>
            <span class="badge badge-warning"><?php echo t('expired'); ?></span>
        <?php else: ?>
            <span class="badge badge-success"><?php echo t('active'); ?></span>
        <?php endif; ?>
      </td>
      <td>
        <a href="<?php echo site_url('admin/users/manage_api_key/' . $row['id']); ?>" 
           class="btn btn-sm btn-outline-primary">
            <?php echo t('manage'); ?>
        </a>
      </td>
    </tr>
  <?php endforeach;?>
  </table>

  <div class="nada-pagination text-right">
    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums; ?>
  </div>

<?php else: ?>
  <div class="py-3"><?php echo t('no_api_keys_found'); ?></div>
<?php endif;?>
</div>

