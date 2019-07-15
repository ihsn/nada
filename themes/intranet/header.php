<style>
    .site-header{
        background:#0069d9;
    }
    .site-header, 
    .site-header a,
    .site-header .navbar-light .navbar-nav .nav-link{
        color:white;
    }

    .navbar-light .navbar-nav .nav-link.active{
        border-bottom-color:white;
    }

    .site-header .login-bar .dropdown-menu a{
        color:#212529;
    }

    </style>
<header class="site-header">

    <div class="container">
        <!--  /***** Login Bar Start *****/ -->
        
        <!--  /***** Login Bar Close *****/ -->
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-md navbar-light rounded navbar-toggleable-md wb-navbar">
                    
                    <?php 
                    /**
                     * 
                     * LOGO + Text
                     * 
                     */
                    ?>
                    <?php /* ?>
                    <div class="navbar-brand">
                        <a href="<?php echo site_url();?>" class="website-logo">
                            <img title="<?php echo $this->config->item("website_title");?>" src="<?php echo base_url().$bootstrap_theme; ?>/images/wb-logo.png" class="img-responsive">
                        </a>
                        <strong><a class="nada-sitename" href="#index.html">Microdata Library</a></strong>
                    </div>
                    <?php */ ?>
                    

                    <?php /**
                     * 
                     * text only 
                     **/  ?>
                    <?php /**/ ?>
                    <div class="navbar-brand">                        
                        <div>
                        <img style="max-height:32px" src="<?php echo base_url();?>/themes/intranet/images/logo-sm.png"/>
                        <a class="nada-site-title" style="font-size:16px;" href="<?php echo site_url();?>"> Microdata Library <?php //echo $this->config->item("website_title");?></a></div>                        
                    </div>
                    <?php /**/?>

                    <button class="navbar-toggler navbar-toggler-right collapsed wb-navbar-button-toggler" type="button" data-toggle="collapse" data-target="#containerNavbar" aria-controls="containerNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Start menus -->
                    <div class="navbar-collapse collapse" id="containerNavbar" aria-expanded="false">
                        <?php if ($menu_horizontal===TRUE):?>
                            <?php echo isset($sidebar) ? $sidebar : '';?>
                            <?php include('user_bar.php'); ?>
                        <?php endif;?>
                    </div>
                    <!-- Close Menus -->

                </nav>
            </div>

        </div>
        <!-- /row -->

    </div>

</header>