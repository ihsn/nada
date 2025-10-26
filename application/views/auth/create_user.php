<div class='container'>

    <h1><?php echo t('user_registration'); ?></h1>

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
        <?php echo form_open(site_url('auth/register'), array('class' => 'form register', 'autocomplete' => 'off')); ?>

        <input type="hidden" name="<?php echo $csrf['keys']['name']; ?>" value="<?php echo $csrf['name']; ?>" />
        <input type="hidden" name="<?php echo $csrf['keys']['value']; ?>" value="<?php echo $csrf['value']; ?>" />

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

        <?php if (isset($extra_fields) && $extra_fields['company']['enabled']===true):?>
        <div class="form-group">
            <label for="company"><?php echo t('company'); ?> 
            <?php if ($extra_fields['company']['required']):?>
                 <span class="required">*</span>
            <?php endif;?>
            </label>
            <?php echo form_input($company, '', 'class="form-control company-typeahead" placeholder="Type or select institution name" autocomplete="off"'); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($extra_fields) && $extra_fields['country']['enabled']===true):?>
        <?php $options_country = $this->ion_auth_model->get_all_countries();?>
        <div class="form-group">
            <label for="country"><?php echo t('country'); ?>
            <?php if ($extra_fields['country']['required']):?>
                 <span class="required">*</span>
            <?php endif;?>
            </label>
            <?php echo form_dropdown('country', $options_country, get_form_value("country", isset($country) ? $country : ''), 'class="form-control"'); ?>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="password"><?php echo t('password'); ?><span class="required">*</span></label>
            <?php echo form_input($password, '', 'class="form-control" autocomplete="off"'); ?>
        </div>

        <div class="form-group">
            <label for="password_confirm"><?php echo t('password_confirmation'); ?><span class="required">*</span></label>
            <?php echo form_input($password_confirm, '', 'class="form-control" autocomplete="off"'); ?>
        </div>

        <div class="captcha_container">
            <?php echo $captcha_question; ?>
        </div>

        <?php echo form_submit('submit', t('register'), 'class="btn btn-primary"'); ?>
        <?php echo anchor('', t('cancel'), array('class' => '')); ?>
        <?php echo form_close(); ?>
    </div>

</div>


<?php if (isset($extra_fields) && $extra_fields['company']['enabled']===true):?>
<script src="<?php echo base_url().'javascript/typeahead/typeahead.bundle.js'; ?>"></script>
<link rel="stylesheet" href="<?php echo base_url().'javascript/typeahead/jquery.typeahead.min.css'; ?>" />

<style>
    .tt-query {
  -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
     -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
          box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
}

.tt-hint {
  color: #999
}

.tt-menu {    /* used to be tt-dropdown-menu in older versions */
  width: 100%;
  margin-top: 4px;
  max-height: 200px;
overflow-y: auto;
  padding: 4px 0;
  background-color: #fff;
  border: 1px solid #ccc;
  border: 1px solid rgba(0, 0, 0, 0.2);
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
  -webkit-box-shadow: 0 5px 10px rgba(0,0,0,.2);
     -moz-box-shadow: 0 5px 10px rgba(0,0,0,.2);
          box-shadow: 0 5px 10px rgba(0,0,0,.2);
}

.tt-suggestion {
  padding: 3px 20px;
  line-height: 24px;
}

.tt-suggestion.tt-cursor,.tt-suggestion:hover {
  color: #fff;
  background-color: #0097cf;

}

.tt-suggestion p {
  margin: 0;
}



span.twitter-typeahead .tt-menu {
  cursor: pointer;
}

.dropdown-menu, span.twitter-typeahead .tt-menu {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 1000;
  display: none;
  float: left;
  min-width: 160px;
  padding: 5px 0;
  margin: 2px 0 0;
  font-size: 1rem;
  color: #373a3c;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 0.25rem; }

span.twitter-typeahead .tt-suggestion {
  display: block;
  width: 100%;
  padding: 3px 20px;
  clear: both;
  font-weight: normal;
  line-height: 1.5;
  color: #373a3c;
  text-align: inherit;
  white-space: nowrap;
  background: none;
  border: 0; }
span.twitter-typeahead .tt-suggestion:focus, .dropdown-item:hover, span.twitter-typeahead .tt-suggestion:hover {
    color: #2b2d2f;
    text-decoration: none;
    background-color: #f5f5f5; }
span.twitter-typeahead .active.tt-suggestion, span.twitter-typeahead .tt-suggestion.tt-cursor, span.twitter-typeahead .active.tt-suggestion:focus, span.twitter-typeahead .tt-suggestion.tt-cursor:focus, span.twitter-typeahead .active.tt-suggestion:hover, span.twitter-typeahead .tt-suggestion.tt-cursor:hover {
    color: #fff;
    text-decoration: none;
    background-color: #0275d8;
    outline: 0; }
span.twitter-typeahead .disabled.tt-suggestion, span.twitter-typeahead .disabled.tt-suggestion:focus, span.twitter-typeahead .disabled.tt-suggestion:hover {
    color: #818a91; }
span.twitter-typeahead .disabled.tt-suggestion:focus, span.twitter-typeahead .disabled.tt-suggestion:hover {
    text-decoration: none;
    cursor: not-allowed;
    background-color: transparent;
    background-image: none;
    filter: "progid:DXImageTransform.Microsoft.gradient(enabled = false)"; }
span.twitter-typeahead {
  width: 100%; }
  .input-group span.twitter-typeahead {
    display: block !important; }
    .input-group span.twitter-typeahead .tt-menu {
      top: 2.375rem !important; }
</style>
<?php endif; ?>

<?php if (isset($extra_fields) && $extra_fields['company']['enabled']===true):?>
<script>
$(document).ready(function() {
    var substringMatcher = function(strs) {
        return function findMatches(q, cb) {
            var matches, substringRegex;

            // an array that will be populated with substring matches
            matches = [];

            // regex used to determine if a string contains the substring `q`
            substrRegex = new RegExp(q, 'i');

            // iterate through the pool of strings and for any string that
            // contains the substring `q`, add it to the `matches` array
            $.each(strs, function(i, str) {
                if (substrRegex.test(str)) {
                    matches.push(str);
                }
            });

            cb(matches);
        };
    };

    var items = <?php echo json_encode(array_values($extra_fields['company']['enum']));?>;

    // Initialize typeahead on the company field
    $('.company-typeahead').typeahead({
        hint: true,
        highlight: true,
        minLength: 0
    }, {
        name: 'institutions',
        source: substringMatcher(items),
        limit: 100
    });
});
</script>
<?php endif; ?>