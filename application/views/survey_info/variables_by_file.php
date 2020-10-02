<style>
    .data-file-bg1 tr,.data-file-bg1 td {vertical-align: top;}
    .data-file-bg1 .col-1{width:100px;}
    .data-file-bg1 {margin-bottom:20px;}
    .var-info-panel{display:none;}
    .table-variable-list td{
        cursor:pointer;
    }
    
    .nada-list-group-item {
        position: relative;
        display: block;
        padding: 10px 15px;
        margin-bottom: -1px;
        background-color: #fff;
        border: 1px solid #ddd;
        border-left:0px;
        border-right:0px;
        font-size: small;
        border-bottom: 1px solid gainsboro;
        word-wrap: break-word;
        padding: 5px;
        padding-right: 10px;

    }

    .nada-list-group-title{
        font-weight:bold;
        border-top:0px;
    }

    .variable-groups-sidebar
    .nada-list-vgroup {
        padding-inline-start: 0px;
        font-size:small;
        list-style-type: none;
    }

    .nada-list-vgroup {
        list-style-type: none;
    }

    .nada-list-subgroup{
        padding-left:10px;
    }
    
    .table-variable-list .var-breadcrumb{
        display:none;
    }

    .nada-list-subgroup .nada-list-vgroup-item {
        padding-left: 24px;
        position: relative;
        list-style:none;
    }

    .nada-list-subgroup .nada-list-vgroup-item:before {
        position: absolute;
        font-family: 'FontAwesome';
        top: 0;
        left: 10px;
        content: "\f105";
    }

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
            <li class="nada-list-group-item nada-list-group-title"><?php echo t('data_files');?></li>
            <?php foreach($file_list as $file_):?>
                <li class="nada-list-group-item">
                    <a href="<?php echo site_url("catalog/$sid/data-dictionary/{$file_['file_id']}");?>?file_name=<?php echo html_escape($file_['file_name']);?>"><?php echo wordwrap($file_['file_name'],15,"<BR>");?></a>
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

    <div class="col-sm-10 col-md-10 col-lg-10 wb-border-left tab-body body-files">

        <div class="container-fluid" id="datafile-container">        
        <h4><?php echo t('data_file');?>: <?php echo $file['file_name'];?></h4>
        
        <?php if($file['description']!=''):?>
            <p><?php echo nl2br($file['description']);?></p>
        <?php endif;?>
    
        <table class="data-file-bg1">            
            <tr>
                <td><?php echo t('cases');?>: </td>
                <td><?php echo $file['case_count'];?></td>
            </tr>
            <tr>
                <td><?php echo t('variables');?>: </td>
                <td><?php echo $file['var_count'];?></td>
            </tr>
        </table>

        </div>

        
        <div class="container-fluid variables-container" id="variables-container">
            <h4><?php echo t('variables');?></h4>
            
            <?php $tr_class="";//"row-color1"; ?>
            <div class="container-fluid table-variable-list data-dictionary ">
                <?php foreach($variables as $variable):?>
                    <?php //if($tr_class=="row-color1") {$tr_class="row-color2";} else{ $tr_class="row-color1"; } ?>
                    <div class="row var-row <?php echo $tr_class;?>" >
                    <div class="icon-toggle"><i class="collapased_ fa fa-angle-down" aria-hidden="true"></i><i class="expanded_ fa fa-angle-up" aria-hidden="true"></i></div>            
                        <div class="col-md-3">
                            <div class="var-td p-1">
                            <a class="var-id text-break" id="<?php echo md5($variable['vid']);?>" href="<?php echo site_url("catalog/$sid/variable/$file_id/{$variable['vid']}");?>?name=<?php echo urlencode($variable['name']);?>"><?php echo html_escape($variable['name']);?></a>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-1">
                                <a class="var-id" id="<?php echo md5($variable['vid']);?>" href="<?php echo site_url("catalog/$sid/variable/$file_id/{$variable['vid']}");?>?name=<?php echo urlencode($variable['name']);?>">
                                    <?php echo html_escape($variable['labl']);?>
                                </a>
                            </div>                            
                        </div>                    
                    </div>
                    <div class="row var-info-panel" id="pnl-<?php echo md5($variable['vid']);?>">
                        <div class="panel-td p-4"></div>
                    </div>                
                <?php endforeach;?>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <?php echo t('total');?>: <?php echo $file_variables_count;?>
                </div>
                <div class="col-md-9">
                    <div class="pagination float-right">
                        <?php echo $variable_pagination;?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script type="application/javascript">
    $(document).ready(function () {

        //show/hide variable info
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
        var i18n={
		'js_loading':"<?php echo t('js_loading');?>",
        };
        
        //panel id
        var pnl="#pnl-"+var_obj.attr("id");
        var pnl_body=$(pnl).find(".panel-td");

        //collapse
        if ($(var_obj).closest(".var-row").is(".pnl-active")){
            $(var_obj).closest(".var-row").toggleClass("pnl-active");
            $(pnl).hide();
            return;
        }

        //hide any open panels
        $('.data-dictionary .var-info-panel').hide();

        //unset any active panels
        $(".data-dictionary .var-row").removeClass("pnl-active");

        //error handler
        variable_error_handler(pnl_body);

        $(pnl).show();
        $(var_obj).closest(".var-row").toggleClass("pnl-active");
        $(pnl_body).html('<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> '+ i18n.js_loading);
        $(pnl_body).load(var_obj.attr("href")+'&ajax=true', function(){
            var fooOffset = jQuery('.pnl-active').offset(),
                destination = fooOffset.top;
            $('html,body').animate({scrollTop: destination-50}, 500);
        })
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