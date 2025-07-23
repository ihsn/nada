<?php
$options_country = $this->ion_auth_model->get_all_countries();
?>

<div class='container'>

    <h1><?php echo t('complete_your_account');?></h1>
    <p class="font-weight-bold pb-5"><?php echo sprintf(t('complete_your_account_description'), $provider_name);?></p>

    <?php if (validation_errors()) : ?>
        <div class="alert alert-danger error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <?php $error = $this->session->flashdata('error'); ?>
    <?php echo ($error != "") ? '<div class="alert alert-danger error">' . $error . '</div>' : ''; ?>

    <?php $message = $this->session->flashdata('message'); ?>
    <?php echo ($message != "") ? '<div class="success">' . $message . '</div>' : ''; ?>

    <div style="max-width:800px;">
        <?php echo form_open(site_url('auth/social_register'), array('class' => 'form register', 'autocomplete' => 'off')); ?>

        <input type="hidden" name="<?php echo $csrf['keys']['name']; ?>" value="<?php echo $csrf['name']; ?>" />
        <input type="hidden" name="<?php echo $csrf['keys']['value']; ?>" value="<?php echo $csrf['value']; ?>" />

        <div class="form-group">
            <label for="social_id"><?php echo $provider_name; ?> ID<span class="required">*</span></label>
            <?php echo form_input(null, $social_id, 'class="form-control" readonly'); ?>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="first_name"><?php echo t('first_name'); ?><span class="required">*</span></label>
                <?php echo form_input($first_name, '', 'class="form-control"'); ?>
            </div>

            <div class="form-group col-md-6">
                <label for="last_name"><?php echo t('last_name'); ?><span class="required">*</span></label>
                <?php echo form_input($last_name, '', 'class="form-control"'); ?>
            </div>
        </div>

        <div class="form-group">
            <label for="email"><?php echo t('email'); ?><span class="required">*</span></label>
            <?php echo form_input($email, '', 'class="form-control"'); ?>
        </div>

        <div class="form-group">
            <label for="country"><?php echo t('country'); ?><span class="required">*</span></label>
            <?php echo form_dropdown('country', $options_country, get_form_value("country", isset($country) ? $country : ''), 'class="form-control"'); ?>
        </div>

        <div class="captcha_container">
            <?php echo $captcha_question; ?>
        </div>

        <?php echo form_submit('submit', t('register'), 'class="btn btn-primary"'); ?>
        <?php echo anchor('', t('cancel'), array('class' => '')); ?>
        <?php echo form_close(); ?>
    </div>

</div> 