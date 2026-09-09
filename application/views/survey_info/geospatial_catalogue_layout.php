<style>
    .var-info-panel{display:none;}
    .table-variable-list td{ cursor:pointer; }
    .nada-list-group-item {
        position: relative;
        display: block;
        padding: 5px 10px 5px 5px;
        margin-bottom: -1px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-left:0px;
        border-right:0px;
        font-size: small;
        border-bottom: 1px solid gainsboro;
        word-wrap: break-word;
    }
    .nada-list-group-title{
        font-weight:bold;
        border-top:0px;
    }
    .table-variable-list .var-breadcrumb{ display:none; }
    .var-id {
        word-break: break-word!important;
        overflow-wrap: break-word!important;
    }
</style>

<div class="row">
    <div class="col-sm-2 col-md-2 col-lg-2 tab-sidebar hidden-sm-down sidebar-files">
        <form method="get" action="<?php echo site_url('catalog/'.$sid.'/search');?>" class="dictionary-search">
        <div class="input-group input-group-sm">
            <input type="text" name="vk" class="form-control" placeholder="Search for...">
            <span class="input-group-btn">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fa fa-search"></i></button>
            </span>
        </div>
        </form>

        <ul class="nada-list-group">
            <li class="nada-list-group-item nada-list-group-title"><?php echo t('feature_types');?></li>
            <?php foreach ((is_array($files) ? $files : array()) as $file_): ?>
                <li class="nada-list-group-item">
                    <a href="<?php echo site_url("catalog/$sid/data-dictionary/{$file_['file_id']}");?>?file_name=<?php echo html_escape($file_['file_name']);?>"><?php echo wordwrap($file_['file_name'],15,"<BR>");?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="col-sm-10 col-md-10 col-lg-10 wb-border-left tab-body body-files">
        <div class="variable-metadata">
            <?php echo $content;?>
        </div>
    </div>
</div>

<script type="application/javascript">
    $(document).ready(function () {
        $(document.body).on("click",".data-dictionary .var-row", function(){
            var variable=$(this).find(".var-id");
            if(variable){
                get_variable(variable);
            }
            return false;
        });
    });

    function get_variable(var_obj)
    {
        var i18n={'js_loading':"<?php echo t('js_loading');?>"};
        var pnl="#pnl-"+var_obj.attr("id");
        var pnl_body=$(pnl).find(".panel-td");

        if ($(var_obj).closest(".var-row").is(".pnl-active")){
            $(var_obj).closest(".var-row").toggleClass("pnl-active");
            $(pnl).hide();
            return;
        }

        $('.data-dictionary .var-info-panel').hide();
        $(".data-dictionary .var-row").removeClass("pnl-active");

        $.ajaxSetup({
            error:function(XHR){
                $(pnl_body).html('<div class="error">'+XHR.responseText+'</div>');
            }
        });

        $(pnl).show();
        $(var_obj).closest(".var-row").toggleClass("pnl-active");
        $(pnl_body).html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> '+ i18n.js_loading);
        $(pnl_body).load(var_obj.attr("href")+'&ajax=true', function(){
            var fooOffset = jQuery('.pnl-active').offset(),
                destination = fooOffset.top;
            $('html,body').animate({scrollTop: destination-50}, 500);
        })
    }
</script>
