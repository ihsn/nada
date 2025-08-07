<?php
/**
* Form for collecting data for the Public Use Access Requests
*
*/
?>
<style>
.public-use {
    border-collapse: collapse;
    width: 100%;
    margin-bottom: 20px;
}

.public-use td {
    border: 1px solid #e0e0e0;
    padding: 12px 15px;
    vertical-align: top;
}

.public-use td:first-child {
    background-color: #f8f9fa;
    font-weight: 600;
    width: 30%;
    color: #495057;
}

.public-use .note {
    background-color: #e7f3ff;
    border-left: 4px solid #007bff;
}

.public-use .border {
    border: 1px solid #dee2e6;
}

.form-section {
    margin-bottom: 25px;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e9ecef;
}

.form-group {
    margin-bottom: 15px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control[readonly] {
    background-color: #f8f9fa;
    color: #6c757d;
}

.required-label {
    color: #dc3545;
    font-weight: bold;
}

.help-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
    font-style: italic;
}

.error {
    color: #dc3545;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.success {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.terms-section {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
}

.submit-section {
    text-align: right;
    padding: 20px 0;
    border-top: 1px solid #dee2e6;
    margin-top: 20px;
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 4px;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

.btn-primary:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
}

.dataset-info {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 10px;
    border-radius: 4px;
    color: #856404;
    font-size: 13px;
}

.abstract-section {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin: 15px 0;
}

.abstract-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 10px;
}

.abstract-help {
    color: #6c757d;
    font-style: italic;
    margin-bottom: 10px;
}

.custom-fields-section {
    margin: 20px 0;
    padding: 20px;
    background-color: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.custom-fields-title {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e9ecef;
}
</style>

<div class="form-section">
    <h1 class="page-title"><?php echo t('application_for_access_to_dataset_'.$form_obj['model']);?></h1>
    <div style="font-style:italic;color:red;margin-bottom:20px;"><?php echo t('fields_marked_mandatory');?></div>

    <?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

    <?php echo form_open(site_url('catalog/'.$survey_id.'/get-microdata'),'style="padding:0px;margin:0px" name="orderform" id="orderform"');?>

        <input type="hidden" name="surveytitle" value="<?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?>" />
        <input type="hidden" name="surveyid" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?>" />
        <input type="hidden" name="id" value="<?php echo get_form_value('id',isset($id) ? $id : ''); ?>" />
        <?php if (isset($this->ajax)):?>
            <input type="hidden" name="ajax" value="true" />
        <?php endif;?>

        <!-- User Information Section -->
        <div class="form-section">
            <table class="public-use">
                <tr class="border">
                    <td><?php echo t('first_name');?></td>
                    <td><?php echo get_form_value('fname',isset($fname) ? $fname : ''); ?></td>
                </tr>
                <tr class="border">
                    <td><?php echo t('last_name');?></td>
                    <td><?php echo get_form_value('lname',isset($lname) ? $lname: ''); ?></td>
                </tr>
                <?php if (isset($organization) && $organization != ''): ?>
                <tr class="border">
                    <td><?php echo t('organization');?></td>
                    <td><?php echo get_form_value('organization',isset($organization) ? $organization : ''); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="border">
                    <td><?php echo t('email');?></td>
                    <td><?php echo get_form_value('email',isset($email) ? $email : ''); ?></td>
                </tr>
                <tr class="border">
                    <td><?php echo t('dataset_requested');?></td>
                    <td>
                        <div class="dataset-info">
                            <?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id : ''); ?> - 
                            <?php echo get_form_value('survey_title',isset($survey_title) ? $survey_title : ''); ?> 
                            <br/>
                            <?php echo get_form_value('proddate',isset($proddate) ? $proddate : ''); ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Abstract Section -->
        <div class="form-section">
            <div class="form-section-title"><?php echo t('intended_use_of_data'); ?></div>
            <div class="abstract-section">
                <div class="abstract-label">
                    <span class="required-label">*</span> <?php echo t('describe_your_project');?>
                </div>                
                <div class="form-group">
                    <textarea id="abstract" name="abstract" class="form-control" rows="8" placeholder="Please describe your research project and how you intend to use this dataset..."><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Custom Fields Section -->
        <?php if (isset($custom_fields) && !empty($custom_fields)): ?>
            <div class="form-section">
                <div class="custom-fields-section bg-light">
                    <?php $this->load->view('access_public/custom_fields', $data); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Terms and Conditions Section -->
        <div class="form-section">
            <div class="form-section-title"><?php echo t('terms_and_conditions'); ?></div>
            <div class="terms-section">
                <div class="bull-list"><?php echo t('terms_text_'.$form_obj['model']);?></div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="submit-section">
            <input type="checkbox" title="I Agree" id="chk_agree" name="chk_agree" onClick="isagree()"/>
            <label for="chk_agree"><?php echo t('i_agree');?>&nbsp;&nbsp;</label>
            <input class="btn btn-primary" type="submit" disabled="disabled" value="<?php echo t('submit');?>" id="submit" name="submit" onClick="submitform()" />
        </div>

    <?php echo form_close();?>
</div>

<script type="text/javascript">
    function isagree(){
        $("#submit").prop('disabled', !$("#chk_agree").prop("checked"))
    }
</script>
