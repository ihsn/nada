<div class="row dd-catalogue">

    <div class="col-12 col-md-3 col-lg-2 tab-sidebar sidebar-files">

        <form method="get" action="<?php echo site_url('catalog/'.$sid.'/search');?>" class="dictionary-search">
        <div class="input-group input-group-sm">
            <input type="text" name="vk" class="form-control" placeholder="<?php echo html_escape(t('search_variables'));?>" aria-label="<?php echo html_escape(t('search_variables'));?>" value="<?php echo html_escape((string)$this->input->get('vk'));?>">
            <span class="input-group-btn">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="fa fa-search"></i></button>
            </span>
        </div>
        </form>

        <ul class="nada-list-group">
            <li class="nada-list-group-item nada-list-group-title"><?php echo t('data_files');?></li>
            <?php $current_file_id = isset($file_id) ? (string)$file_id : ''; ?>
            <?php foreach((isset($files) && is_array($files) ? $files : array()) as $file_):?>
                <?php $is_current = $current_file_id !== '' && (string)$file_['file_id'] === $current_file_id; ?>
                <li class="nada-list-group-item<?php echo $is_current ? ' active' : '';?>">
                    <a href="<?php echo site_url("catalog/$sid/data-dictionary/".rawurlencode($file_['file_id']));?>?file_name=<?php echo rawurlencode($file_['file_name']);?>"<?php echo $is_current ? ' aria-current="page"' : '';?>><?php echo html_escape($file_['file_name']);?></a>
                </li>
            <?php endforeach;?>
        </ul>

        <?php if(isset($variable_groups_html) && !empty($variable_groups_html)):?>
        <div class="variable-groups-sidebar">
            <div class="nada-list-group-item nada-list-group-title"><?php echo t('variable_groups');?></div>
            <?php echo $variable_groups_html;?>
        </div>
        <?php endif;?>

    </div>

    <div class="col-12 col-md-9 col-lg-10 wb-border-left tab-body body-files">

        <div class="variable-metadata">
            <?php echo $content;?>
        </div>

    </div>
</div>

<script type="application/javascript">
    $(document).ready(function () {

        //show/hide variable info
        $(document.body).on("click",".data-dictionary .var-row", function(e){
            //let ctrl/cmd-click on the name link open the variable page
            if ($(e.target).closest("a").length && (e.ctrlKey || e.metaKey || e.shiftKey)){
                return true;
            }
            var variable=$(this).find(".var-id").first();
            if(variable.length){
                get_variable(variable);
            }
            return false;
        });

    });

    function get_variable(var_obj)
    {
        var i18n={
            'js_loading':"<?php echo t('js_loading');?>"
        };

        //panel id
        var pnl="#pnl-"+var_obj.attr("id");
        var pnl_body=$(pnl).find(".panel-td");
        var row=$(var_obj).closest(".var-row");

        //collapse
        if (row.is(".pnl-active")){
            row.removeClass("pnl-active");
            $(pnl).hide();
            return;
        }

        //hide any open panels
        $('.data-dictionary .var-info-panel').hide();
        $(".data-dictionary .var-row").removeClass("pnl-active");

        //error handler
        variable_error_handler(pnl_body);

        $(pnl).show();
        row.addClass("pnl-active");
        $(pnl_body).html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> '+ i18n.js_loading);
        $(pnl_body).load(var_obj.attr("href")+'&ajax=true', function(){
            var destination = row.offset().top;
            $('html,body').animate({scrollTop: destination-50}, 500);
        });
    }


    //show/hide resource
    function toggle_resource(element_id){
        $("#"+element_id).toggle();
    }

    function variable_error_handler(pnl)
    {
        $.ajaxSetup({
            error:function(XHR,e)	{
                $(pnl).html('<div class="error">'+XHR.responseText+'</div>');
            }
        });
    }

</script>
