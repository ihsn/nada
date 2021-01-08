<?php
//items to exclude from side bar display
if (!isset($exclude_sidebar_items)){
    $exclude_sidebar_items=array();
}
?>

<?php if (isset($hide_sidebar) && $hide_sidebar==true):?>
    <!--metadata content no-sidebar -->
    <div class="col-md-12" >
        <?php echo implode('',$output);?>
    </div>
<?php else:?>
    <!-- sidebar with section links -->
    <div class="col-sm-3 col-md-3 col-lg-2 d-none d-sm-block">
    <div class="navbar-collapse sticky-top metadata-sidebar-container">
        <ul class="nav flex-column" id="dataset-metadata-sidebar">
        <?php foreach($output as $key=>$value):?>
            <?php if(in_array($key,$exclude_sidebar_items)){continue;}?>
            <?php if(trim($value)!==""):?>        
            <li class="nav-item">
                <a class="nav-link" href="<?php //echo current_url();?>#metadata-<?php echo $key;?>"><?php echo t($key);?></a>
            </li>
            <?php endif;?>
        <?php endforeach;?>
        </ul>
    </div>
    </div>
    <!--metadata content-->
    <div class="col-sm-9 col-lg-10 wb-border-left" >
        <?php echo implode('',$output);?>
    </div>
<?php endif;?>



<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/2.1.8/linkify.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery-linkify/2.1.8/linkify-jquery.min.js"></script>

<script>
    $(function() {        
        $(".metadata-container").linkify(
            linkify.options.defaults.validate = function (text, type) {
                return type !== 'url' || /^(http|ftp)s?:\/\//.test(text) || text.slice(0,3) === 'www';
            }
        );
    });
</script> 