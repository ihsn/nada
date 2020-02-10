<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?php echo $title; ?></title>
<!--<base href="<?php echo base_url(); ?>">-->
<meta name="description" content="Central Microdata Catalog">

<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/font-awesome.min.css">

<?php if($use_cdn):?>    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<?php else:?>
    <link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme; ?>/css/bootstrap.min.css">
<?php endif;?>    


<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/superfish@1.7.9/dist/css/superfish.css">

<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/style.css?v03212019">
<link rel="stylesheet" href="<?php echo base_url().$bootstrap_theme ?>/css/custom.css?v042019">

<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<!--<script src="https://code.jquery.com/jquery-3.1.1.slim.min.js"></script>-->

<script src="//code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/superfish/1.7.10/js/superfish.min.js"></script>


<?php if($use_cdn):?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<?php else:?>
    <script src="<?php echo base_url().$bootstrap_theme ?>/js/popper.min.js"></script>
    <script src="<?php echo base_url().$bootstrap_theme ?>/js/bootstrap.min.js"></script>
<?php endif;?>

<script src="<?php echo base_url().$bootstrap_theme ?>/js/script.js"></script>

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<!--<script src="--><?php //echo base_url().$bootstrap_theme ?><!--/js/ie10-viewport-bug-workaround.js"></script>-->
<!-- tooltips  -->

<script type="text/javascript" src="<?php echo base_url();?>javascript/jquery.ba-bbq.js"></script>
<script type="text/javascript">
    var CI = {'base_url': '<?php echo site_url(); ?>'};

    if (top.frames.length!=0) {
        top.location=self.document.location;
    }

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

        $(window).scroll(function() {     
            var scroll = $(window).scrollTop();
            if (scroll > 0) {
                $(".site-header").addClass("active");
            }
            else {
                $(".site-header").removeClass("active");
            }
        });

    }); //end-document-ready

</script>


<?php if (isset($_styles) ){ echo $_styles;} ?>
<?php if (isset($_scripts) ){ echo $_scripts;} ?>

<script>
  jQuery(document).ready(function() {
    jQuery('#site-menu').superfish({
      delay:       1000,                            // one second delay on mouseout
      animation:   {opacity:'show',height:'show'},  // fade-in and slide-down animation
      speed:       'fast',                          // faster animation speed
      cssArrows:  true                            // disable generation of arrow mark-up
    });
  });
</script>


<style>
.sfHover,
.sfHover li, 
.sfHover ul,
.sfHover a{
    color:white;
}


.sf-menu a,
.sf-menu li{
    border:0px;
}

ul.sf-menu {
    margin: 0;
    list-style: none;
    text-align: left;
    float:right;
}


.sf-menu li{
    background:none;
}


ul.sf-menu li a, 
ul.sf-menu li > span {
    text-transform: uppercase;

}


ul.sf-menu li a:hover, 
ul.sf-menu > li > a.is-active:hover {
    background-color: #767676;
    color:white;
}


.sf-menu ul li {
	background-color: rgba(51, 51, 51, 0.85);
}
.sf-menu ul ul li {
	background-color: rgba(51, 51, 51, 0.85);
}
.sf-menu li:hover,
.sf-menu li.sfHover {
	background: rgba(51, 51, 51, 0.85);
    color:white;
	/* only transition out, not in */
	-webkit-transition: none;
	transition: none;
}
.sf-menu .sfHover ul, 
.sf-menu .current {color:white !important;}

.sf-arrows .sf-with-ul:after {
    content: "\f078";
    font-family: 'FontAwesome';
    font-size:8px;
    position: absolute;
    top: 45%;
    right: 3em;
    margin-top: -3px;
    height: 0;
    width: 0;
    border: 0px solid transparent;
    border-top-color: #dFeEFF;
    border-top-color: rgba(255,255,255,.5);
}

.sf-arrows ul .sf-with-ul:after {
    font-family: 'FontAwesome';
    content: "\f105";
}


/*
ul.sf-menu > li > a .sf-sub-indicator:after {
    content: "\f078";
    font-family: 'FontAwesome';
    left: 50%;
    margin-left: -3px;
    font-size: 7px;
    -webkit-transition: all 0.2s ease-in-out;
    -moz-transition: all 0.2s ease-in-out;
    -ms-transition: all 0.2s ease-in-out;
    -o-transition: all 0.2s ease-in-out;
    transition: all 0.2s ease-in-out;
}
ul.sf-menu .sf-sub-indicator:after {
    content: "▼";
    left: 0;
    line-height: 1;
    position: absolute;
    text-indent: 0;
    top: 0;
}
*/
</style>