
<div class="row geo-catalogue">
    <div class="col-12 col-md-3 col-lg-2 tab-sidebar sidebar-files">
        <form method="get" action="<?php echo site_url('catalog/'.$sid.'/search');?>" class="dictionary-search">
        <div class="input-group input-group-sm">
            <input type="text" name="vk" class="form-control" placeholder="<?php echo html_escape(t('search_attributes'));?>" aria-label="<?php echo html_escape(t('search_attributes'));?>" value="<?php echo html_escape((string)$this->input->get('vk'));?>">
            <span class="input-group-btn">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fa fa-search"></i></button>
            </span>
        </div>
        </form>

        <ul class="nada-list-group">
            <li class="nada-list-group-item nada-list-group-title"><?php echo t('feature_types');?></li>
            <?php $current_file_id = isset($file_id) ? (string)$file_id : ''; ?>
            <?php foreach ((is_array($files) ? $files : array()) as $file_): ?>
                <?php $is_current = $current_file_id !== '' && (string)$file_['file_id'] === $current_file_id; ?>
                <li class="nada-list-group-item<?php echo $is_current ? ' active' : '';?>">
                    <a href="<?php echo site_url("catalog/$sid/data-dictionary/".rawurlencode($file_['file_id']));?>?file_name=<?php echo rawurlencode($file_['file_name']);?>"<?php echo $is_current ? ' aria-current="page"' : '';?>><?php echo html_escape($file_['file_name']);?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="col-12 col-md-9 col-lg-10 wb-border-left tab-body body-files">
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
