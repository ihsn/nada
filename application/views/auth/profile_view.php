<div class="container">
    <div class="row">
        <div class="col-sm-12">
            <h1><?php echo t('profile'); ?></h1>
        </div>
    </div>


    <div class="row mt-lg-3 mb-5"> 
        <!-- tab-heading -->
        <div class="col-12 col-sm-12">
            <div class="wb-tab-heading px-3">
                <table class="table">
                    <tbody>
                        <tr>
                            <td><?php echo html_escape($user->first_name . ' ' . $user->last_name); ?></td>
                            <td align="right"><?php echo anchor('auth/edit_profile', t('edit')); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo t('name'); ?></td>
                            <td><?php echo html_escape($user->first_name . ' ' . $user->last_name); ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('email'); ?></td>
                            <td><?php echo html_escape($user->email); ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('company'); ?></td>
                            <td><?php echo html_escape($user->company); ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('phone'); ?></td>
                            <td><?php echo html_escape($user->phone); ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('country'); ?></td>
                            <td><?php echo html_escape($user->country); ?></td>
                        </tr>

                    </tbody>
                </table>

            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-8">
            <h3><?php echo t('api_keys'); ?></h3>
        </div>
        <div class="col-md-4">
            <?php if (is_array($api_keys) && count($api_keys) < 5 || !is_array($api_keys)) : ?>                
            <a href="<?php echo site_url('auth/generate_api_key'); ?>" class="btn btn-primary btn-sm float-right"><?php echo t('generate_api_key'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($this->session->flashdata('new_api_key')) : ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-primary alert-dismissible fade show" role="alert">
                <h5><?php echo t('new_api_key_generated'); ?></h5>
                <p><strong><?php echo t('save_this_key'); ?></strong> <?php echo t('key_will_not_be_shown_again'); ?></p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control font-monospace bg-light" id="new_api_key" 
                           value="<?php echo html_escape($this->session->flashdata('new_api_key')); ?>" readonly>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyApiKey()">
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
        function copyApiKey() {
            var copyText = document.getElementById("new_api_key");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("<?php echo t('api_key_copied'); ?>");
        }
    </script>
    <?php endif; ?>

    <div class="row mt-lg-3 mb-5">
        <div class="col-md-12">
            <div class="wb-tab-heading px-3">
                <?php if (is_array($api_keys) && count($api_keys) > 0) : ?>

                    <table class="table">
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
                                    <td><?php echo $key_data['expires_at'] ? date('Y-m-d H:i', $key_data['expires_at']) : t('never'); ?></td>
                                    <td><?php echo $key_data['last_used_at'] ? date('Y-m-d H:i', $key_data['last_used_at']) : t('never'); ?></td>
                                    <td>
                                        <?php if ($key_data['is_expired']) : ?>
                                            <span class="badge badge-warning"><?php echo t('expired'); ?></span>
                                        <?php else : ?>
                                            <span class="badge badge-success"><?php echo t('active'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo site_url('auth/delete_api_key') . '?key_id=' . urlencode($key_data['id']); ?>" 
                                           class="text-danger" 
                                           onclick="return confirm('<?php echo t('confirm_delete_api_key'); ?>');">
                                            <?php echo t('delete'); ?>
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

    <?php $this->load->view('access_licensed/request_list', array('data' => $lic_requests)); ?>


</div> <!-- /.container -->