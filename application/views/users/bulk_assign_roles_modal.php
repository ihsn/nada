<?php if (validation_errors()): ?>
    <div class="alert alert-danger">
        <?php echo validation_errors(); ?>
    </div>
<?php endif; ?>

<?php $error = $this->session->flashdata('error');?>
<?php echo ($error != "") ? '<div class="alert alert-danger">' . $error . '</div>' : ''; ?>

<?php $message = $this->session->flashdata('message');?>
<?php echo ($message != "") ? '<div class="alert alert-success">' . $message . '</div>' : ''; ?>

<div class="mb-4">
    <h6 class="text-muted mb-3"><i class="fa fa-users text-primary"></i> <?php echo sprintf(t('assign_roles_to_n_users'), count($users)); ?></h6>

    <?php echo form_open('admin/users/process_bulk_assign_roles', array('id' => 'bulkRoleForm')); ?>

    <input type="hidden" name="user_ids" value="<?php echo htmlspecialchars($user_ids, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="form-group mb-3">
        <label class="font-weight-bold"><?php echo t('select_roles'); ?>:</label>
        <div class="role-selection">
            <?php foreach ($roles as $role): ?>
            <div class="form-check">
                <input class="form-check-input mt-1" type="checkbox" name="role_ids[]" value="<?php echo (int) $role['id']; ?>" id="role_<?php echo (int) $role['id']; ?>">
                <label class="form-check-label" for="role_<?php echo (int) $role['id']; ?>">
                    <strong><?php echo form_prep($role['name']); ?></strong>
                    <?php if ( ! empty($role['description'])): ?>
                    <small class="text-muted d-block mt-1"><?php echo form_prep($role['description']); ?></small>
                    <?php endif; ?>
                </label>
            </div>
            <?php endforeach; ?>
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
