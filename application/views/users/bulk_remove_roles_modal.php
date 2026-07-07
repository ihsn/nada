<?php if (validation_errors()): ?>
    <div class="alert alert-danger">
        <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<div class="mb-4">
    <h6 class="text-muted mb-3"><i class="fa fa-user-minus text-danger"></i> <?php echo sprintf(t('remove_roles_from_n_users'), count($users)); ?></h6>

    <?php echo form_open('admin/users/process_bulk_remove_roles', array('id' => 'bulkRoleRemovalForm')); ?>

    <input type="hidden" name="user_ids" value="<?php echo htmlspecialchars($user_ids, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="form-group mb-3">
        <label class="font-weight-bold"><?php echo t('select_roles_to_remove'); ?>:</label>
        <div class="role-selection">
            <?php
            $all_roles = array();
            foreach ($user_roles as $uid => $roles) {
                foreach ($roles as $role) {
                    if ( ! isset($all_roles[$role['role_id']])) {
                        $all_roles[$role['role_id']] = $role;
                    }
                }
            }
            ?>

            <?php if (empty($all_roles)): ?>
                <div class="alert alert-info mb-0">
                    <i class="fa fa-info-circle"></i> <?php echo t('selected_users_have_no_roles'); ?>
                </div>
            <?php else: ?>
                <?php foreach ($all_roles as $role): ?>
                <div class="form-check">
                    <input class="form-check-input mt-1" type="checkbox" name="role_ids[]" value="<?php echo (int) $role['role_id']; ?>" id="remove_role_<?php echo (int) $role['role_id']; ?>">
                    <label class="form-check-label" for="remove_role_<?php echo (int) $role['role_id']; ?>">
                        <strong><?php echo form_prep($role['name']); ?></strong>
                        <?php if ( ! empty($role['description'])): ?>
                        <small class="text-muted d-block mt-1"><?php echo form_prep($role['description']); ?></small>
                        <?php endif; ?>
                    </label>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php echo form_close(); ?>
</div>

<style>
.role-selection .form-check {
    margin-bottom: 8px;
    padding: 6px 8px 6px 28px;
    border: 1px solid #e9ecef;
    border-radius: 3px;
    background-color: #f8f9fa;
    position: relative;
}
.role-selection {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    padding: 8px;
    background-color: #fff;
}
</style>
