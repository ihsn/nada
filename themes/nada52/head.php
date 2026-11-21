<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?php echo $title; ?></title>
<?php /* <base href="<?php echo base_url(); ?>"> */ ?>
<?php if (isset($_meta) ){ echo $_meta;} ?>

<?php if (!isset($theme_assets)) { $theme_assets = 'themes/assets'; } ?>
<?php if($use_cdn):?>    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<?php else:?>
    <link href="<?php echo base_url().$theme_assets; ?>/fontawesome/5.15.2/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo base_url().$theme_assets; ?>/bootstrap/4.1.3/css/bootstrap.min.css">
<?php endif;?>    

<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/fonts.css?v20260721">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/style.css?v20261113">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/study-metadata.css?v20261120-24">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/custom.css?v20260625">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/home.css?v20260927-1">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/variables.css?v07042021">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/facets.css?v062021">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/catalog-tab.css?v07052021">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/catalog-card.css?v10102021-1">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/filter-action-bar.css?v14052021-3">
<?php
	$_ss_ci =& get_instance();
	$_ss_ci->load->library('site_styles_builder');
	echo $_ss_ci->site_styles_builder->render_head();
?>

<?php if($use_cdn):?>
    <script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<?php else:?>
    <script src="<?php echo base_url(); ?>javascript/jquery/jquery.js"></script>
    <script src="<?php echo base_url().$theme_assets; ?>/popper/1.14.3/popper.min.js"></script>
    <script src="<?php echo base_url().$theme_assets; ?>/bootstrap/4.1.3/js/bootstrap.min.js"></script>
<?php endif;?>

<script type="text/javascript">
    var CI = {'base_url': '<?php echo site_url(); ?>'};

<?php
	// Header/footer admin previews are shown in a frame; do not break out of that frame.
	$_sh_ci =& get_instance();
	$_sh_framed_preview = (isset($_sh_ci->site_header_builder) && $_sh_ci->site_header_builder->is_preview())
		|| (isset($_sh_ci->site_footer_builder) && $_sh_ci->site_footer_builder->is_preview())
		|| (isset($_sh_ci->site_styles_builder) && $_sh_ci->site_styles_builder->is_preview());
	if (!$_sh_framed_preview):
?>
    if (top.frames.length!=0) {
        top.location=self.document.location;
    }
<?php endif; ?>

    $(document).ready(function()  {
        /*global ajax error handler */
        $( document ).ajaxError(function(event, jqxhr, settings, exception) {
            if(jqxhr.status==401){
                window.location=CI.base_url+'/auth/login/?destination=catalog/';
            }
            else if (jqxhr.status>=500){
                alert(jqxhr.responseText);
            }
        });

    }); //end-document-ready

</script>


<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<?php $google_ua_code=$this->config->item("google_ua_code"); ?>
<?php if(!empty($google_ua_code)):?>
    <?php require_once 'google_analytics.php';?>
<?php endif;?>