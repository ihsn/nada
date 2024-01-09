<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="navbar-collapse sticky-top metadata-sidebar-container">
            <div class="nav flex-column">
            <?php foreach($sidebar as $key=>$item):?>
                <li class="nav-item">                    
                    <a class="nav-link" href="#<?php echo str_replace(".",".",$key);?>"><?php echo tt(strtolower($item),$item);?></a>
                </li>
            <?php endforeach;?>
            </div>
            </div>
        </div>
        <div class="col-md-9">
            <?php echo $html;?>
        </div>
    </div>
</div>

<script>
    $(function() {
        $(".study-metadata").linkify();
    });
</script>    

<style>

.study-metadata h2{
    border-bottom:1px solid #e8e8e8;
    padding-bottom:5px;
    margin-bottom:25px;
    padding-top:20px;
}
.study-metadata .field-title{
    text-transform: uppercase;
    margin-top:15px;
    font-weight:bold;
}

.study-metadata h4.field-title{
    margin-top:0px;
}

.field-section-container .field-section-container h2{
    font-size:22px;
}

.badge-tags{
    font-size:14px!important;
}
</style>