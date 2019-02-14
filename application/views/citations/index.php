<style>
    .citation-by {text-transform: capitalize;color:#996600;}
    .citation-date{color:#996600;}
    .citation-sub-info{margin-top:10px;}
    .icon-legend{margin-top:20px;width:100%;}
    .icon-legend span{margin-left:10px;}
    .published .label-draft{display:none;}
    .draft .label-published{display:none;}
    .label-published{color: green;}

    .label-published,
    .label-draft{
        font-weight:normal;text-transform:capitalize;
    }

    .label-published:hover,
    .label-draft:hover{
        cursor:pointer;
    }

    .label-user{background:#f7af73;}
    .has-survey{color:green;}
    .no-survey{color:red;}
    .has-flag{background:#f59443;}
    .has-note{background:pink;}
    .label-delete{background:gainsboro;}

    .citation-title{font-size:14px;}
    .citation-dated{font-size:12px;color:gray;padding-right:10px;}

    .citation-authors{color:gray;font-size:smaller;}

    .citation-content-container {border-left:1px solid gainsboro;padding-left:10px;}
    .citation-search{margin-bottom:10px;}

    .search-filters .filter-header{
        border-bottom:1px solid gainsboro;
        position:relative;
        padding-bottom:5px;
    }

    .search-filters .clear-all-filters{position:absolute;right:0px;top:0px;font-size:11px;}
    .search-filters .clear-all-filters:hover {cursor:pointer; color:maroon;}
    .sort_by_container{padding-left:20px;font-size:12px;}

    .items-count{font-size:10px;color: gray;}
    .label-url_status-0,
    .label-url_status-2,
    .label-url_status-3{
        background:red;
    }

    .label-url_status-1{
        background:green;
    }
    
</style>

<?php
$flag_options=array(
    'ds_unclear'=>t('ds_unclear'),
    'incomplete'=>t('incomplete'),
    'tobe_checked'=>t('tobe_checked'),
    'duplicate'=>t('duplicate'),
    'back_to_editor'=>t('back_to_editor'),
);

$publish_options=array(
    '1'=>t('option_publish'),
    '0'=>t('option_do_not_publish')
);
?>


<div class="container-fluid citations-index-page">
    <?php if (!isset($hide_form)):?>
    <div class="pull-right page-links">
        <a href="<?php echo site_url(); ?>/admin/citations/add" class="btn btn-default"><span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('add_new_citation');?></a>
        <a href="<?php echo site_url(); ?>/admin/citations/import" class="btn btn-default"><span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('import_citation');?></a>
        <a href="<?php echo site_url(); ?>/citations/export_all" class="btn btn-default"><span class="glyphicon glyphicon-plus ico-add-color right-margin-5" aria-hidden="true"></span><?php echo t('export_to_csv');?></a>
    </div>    

    <h1 class="page-title"><?php echo t('title_citations');?></h1>

    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

    <div class="row">

        <div class="col-md-2">

            <div class="search-filters" >

                <div class="filter-header">
                    <h2>Refine by</h2>
                    <div class="clear-all-filters btn btn-link">Clear all</div>
                </div>

                <?php if (count($citation_flags)>0):?>
                    <div class="filter">
                        <h3>Flags</h3>
                        <?php foreach($citation_flags as $flag_value):?>
                            <?php if($flag_value['flag']!=''):?>
                                <div>
                                    <label for="flag-<?php echo $flag_value['flag'];?>">
                                        <input id="flag-<?php echo $flag_value['flag'];?>" type="checkbox" name="flag[]" value="<?php echo $flag_value['flag'];?>" <?php echo my_set_checkbox('flag', $flag_value['flag']); ?>/>
                                        <?php echo t($flag_value['flag']);?> <span class="items-count">[<?php echo $flag_value['total'];?>]</span>
                                    </label> </div>
                            <?php endif;?>
                        <?php endforeach;?>
                    </div>
                <?php endif;?>

                <?php if(count($citation_creators)>0):?>
                    <div class="filter">
                        <h3>By User</h3>
                        <?php foreach($citation_creators as $creator):?>
                            <div>
                                <label class="no-bold" for="user-<?php echo $creator['id'];?>"><input id="user-<?php echo $creator['id'];?>" type="checkbox" name="user[]" value="<?php echo $creator['id'];?>" <?php echo my_set_checkbox('user', $creator['id']); ?>/> <?php echo $creator['username'];?></label></div>
                        <?php endforeach;?>
                    </div>
                <?php endif;?>

                <div class="filter">
                    <h3>Status</h3>
                    <div><label for="published-all"><input id="published-all" type="radio" name="published" value="" <?php echo my_set_radio('published', ""); ?>/> All</label></div>
                    <?php foreach($citation_publish_stats as $row):?>
                        <div><label for="published-<?php echo $row['published'];?>">
                                <input id="published-<?php echo $row['published'];?>" type="radio" name="published" value="<?php echo $row['published'];?>" <?php echo my_set_radio('published', 1); ?>/>
                                <?php echo t('published-status-'.$row['published']);?> <span class="items-count">[<?php echo $row['total'];?>]</span>
                            </label>
                        </div>
                    <?php endforeach;?>
                </div>

                <div class="filter">
                    <h3>Other options</h3>
                    <!--<div><label for="opt-no_surveys_attached"><input id="opt-no_surveys_attached" type="checkbox" name="no_survey_attached" value="1" <?php echo my_set_checkbox('no_survey_attached', 1); ?>/> has no surveys</label></div>-->
                    <div><label for="opt-has_notes"><input id="opt-has_notes" type="checkbox" name="has_notes" value="1" <?php echo my_set_checkbox('has_notes', 1); ?>/> has notes</label></div>
                </div>

                <!--url status codes-->
                <?php /* TODO ?>
                <?php if (count($citation_url_stats)>0):?>
                    <div class="filter">
                        <h3>Citation links</h3>
                        <?php foreach($citation_url_stats as $row):?>
                            <?php if(is_numeric($row['url_status'])):?>
                                <div>
                                    <label for="url-status-<?php echo $row['url_status'];?>">
                                        <input id="url-status-<?php echo $row['url_status'];?>" type="checkbox" name="url_status[]" value="<?php echo $row['url_status'];?>" <?php echo my_set_checkbox('url_status', $row['url_status']); ?>/>
                                        <?php echo t('url-status-'.$row['url_status']);?> <span class="items-count">[<?php echo t($row['total']);?>]</span>
                                    </label> </div>
                            <?php endif;?>
                        <?php endforeach;?>
                    </div>
                <?php endif;?>
                <?php */ ?>

            </div>

        </div><!--end span2-->

        <div class="citation-content-container col-md-10">

            <form class="form-horizontal top-margin-15" method="GET" id="citation-search">
                <div class="form-group">
                	<div class="col-md-6">
                        <input class="citation-search form-control" type="text" size="60" name="keywords" id="keywords" value="<?php echo form_prep(get_form_value("keywords",$this->keywords)); ?>"/>
                        <input type="hidden" name="sort_by" value="rank" id="sort_by"/>
                        <input type="hidden" name="sort_order" value="DESC" id="sort_order"/>
                	</div>
                <input type="submit" value="<?php echo t('search');?>" name="search" class="btn btn-primary"/>
                <?php if ($this->keywords!==FALSE && $this->keywords!==''): ?>
                    <a href="<?php echo site_url();?>/admin/citations/?reset=1" class="btn btn-default"><?php echo t('reset');?></a>
                <?php endif; ?>
                <?php endif; ?>
                </div>

                <?php if ($rows): ?>
                <?php
                //pagination
                $page_nums=$this->pagination->create_links();
                $current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

                //sort
                $sort_by=$this->sort_by;
                $sort_order=$this->sort_order;

                $sort_=strtolower($sort_by."_".$sort_order);

                //current page url
                $page_url=site_url().'/'.$this->uri->uri_string();
                ?>

                <?php

                if ($this->pagination->cur_page > 0) {
                    $to_page=$this->pagination->per_page*$this->pagination->cur_page;

                    if ($to_page> $this->total)
                    {
                        $to_page=$this->total;
                    }

                    $pager=sprintf(t('showing %d-%d of %d')
                        ,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
                        ,$to_page
                        ,$this->total);
                }
                else {
                    $pager=sprintf(t('showing %d-%d of %d')
                        ,$current_page
                        ,$this->total
                        ,$this->total);
                }

                $additional_querystring= array('keywords', 'user','published','no_survey_attached','flag','has_notes','sort_by','sort_order','collection');
                ?>



    <form autocomplete="off" >
        <!-- batch operations -->
        <table width="100%" style="margin-top:20px;">
            <tr>
                <td width="60%" style="padding-bottom:10px;">                    
                    <div class="row citation-options">
                    <div class="col-md-5">
                    <div class="input-group">
                        <select id="batch_actions_cit" class="form-control form-control-sm">
                            <option value="-1"><?php echo t('batch_actions');?></option>
                            <option value="delete"><?php echo t('delete');?></option>
                            <!--<option value="validate_url"><?php echo t('validate_publication_links');?></option>-->
                        </select>
                        <span class="input-group-btn">
                            <input type="button" id="batch_actions_apply_cit" name="batch_actions_apply" value="<?php echo t('apply');?>" class="btn btn-default"/>
                        </span>
                    </div>                    
                    </div>

	    <div class="col-md-6">        	
        	 <div class="form-inline">
             <label><span><?php echo t('sort');?>:</span></label>
                <select id="select_sort_by" class="form-control">
                    <option value="rank" data-sort_by="rank" data-sort_order="DESC" <?php echo ($sort_=='rank_desc') ? 'selected="selected"' : "";?> ><?php echo t('Relevance');?></option>
                    <option value="title"  data-sort_by="title" data-sort_order="ASC" <?php echo ($sort_=='title_asc') ? 'selected="selected"' : "";?> ><?php echo t('Title ascending');?></option>
                    <option value="title"  data-sort_by="title" data-sort_order="DESC" <?php echo ($sort_=='title_desc') ? 'selected="selected"' : "";?> ><?php echo t('Title descending');?></option>
                    <option value="pub_year"  data-sort_by="pub_year" data-sort_order="ASC" <?php echo ($sort_=='pub_year_asc') ? 'selected="selected"' : "";?> ><?php echo t('Year ascending');?></option>
                    <option value="pub_year"  data-sort_by="pub_year" data-sort_order="DESC" <?php echo ($sort_=='pub_year_desc') ? 'selected="selected"' : "";?> ><?php echo t('Year descending');?></option>

                    <option value="created" data-sort_by="created" data-sort_order="DESC" <?php echo ($sort_=='created_desc') ? 'selected="selected"' : "";?> ><?php echo t('Created');?></option>
                    <option value="changed" data-sort_by="changed" data-sort_order="DESC" <?php echo ($sort_=='changed_desc') ? 'selected="selected"' : "";?> ><?php echo t('Changed');?></option>

                    <option value="authors" data-sort_by="authors" data-sort_order="ASC" <?php echo ($sort_=='authors_asc') ? 'selected="selected"' : "";?> ><?php echo t('Author ascending');?></option>
                    <option value="authors" data-sort_by="authors" data-sort_order="DESC" <?php echo ($sort_=='authors_desc') ? 'selected="selected"' : "";?> ><?php echo t('Author descending');?></option>
                </select>
        </div>
        </div>
        </div>
                            </td>
                            <td align="right">
                                <div class="nada-pagination"><em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?></div>
                            </td>
                        </tr>
                    </table>

                    <table class="table table-striped table-bordered citation-resultset" width="100%" cellspacing="0" cellpadding="0">
                        <thead>
                        <tr class="header">
                            <th><input type="checkbox" value="-1" id="chkBox_toggle"/></th>
                            <th><?php echo t('citation_type');?></th>
                            <th><?php echo t('title');?></th>
                            <th><?php echo t('Year');?></th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <!--<th></th>-->
                            <th></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $tr_class=""; ?>
                        <?php foreach($rows as $row): ?>
                            <?php $row=(object)$row; //var_dump($row);exit;?>
                            <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                            <tr class="<?php echo $tr_class; ?>" valign="top">
                                <td><input type="checkbox" value="<?php echo $row->id; ?>" class="chkBox"/></td>
                                <td><?php echo t($row->ctype); ?></td>
                                <td>
                                    <div class="citation-title"><a href="<?php echo current_url();?>/edit/<?php echo $row->id;?>">
                                            <?php if (@$row->subtitle):?>
                                                <?php echo $row->title; ?>: <?php echo $row->subtitle;?>
                                            <?php else:?>
                                                <?php echo $row->title; ?>
                                            <?php endif;?>

                                        </a>
                                    </div>
                                    <?php if ($row->authors):?>
                                        <span class="citation-authors">
			<?php $authors=array();foreach($row->authors as $author):?>
                <?php
                $author=array($author['fname'],$author['lname']);
                $authors[]= implode(' ', $author);
                ?>
            <?php endforeach;?>
            <?php echo implode(", ",$authors);?>
			</span>
                                    <?php endif;?>

                                    <div class="citation-sub-info" >

			<span class="citation-dated">
				Created on <span class="citation-date"><?php echo date("d M Y", $row->created); ?></span>
                <?php if(isset($row->created_by_user) && $row->created_by_user):?>
                    by <span class="citation-by"><?php echo $row->created_by_user; ?></span>
                <?php endif;?>
			</span>

			<span class="citation-dated">
				Modified on <span class="citation-date"><?php echo date("d M Y", $row->changed); ?></span>
                <?php if(isset($row->changed_by_user) &&  $row->changed_by_user):?>
                    by <span class="citation-by"><?php echo $row->changed_by_user; ?> </span>
                <?php endif;?>
			</span>
                                    </div>
                                </td>
                                <td nowrap="nowrap"><?php echo $row->pub_year; ?>&nbsp;</td>
                                <td nowrap="nowrap">
                                    <?php if($row->survey_count > 0):?>
                                        <span class="has-survey" title="<?php echo $row->survey_count;?>"><span class="glyphicon glyphicon-th-large" aria-hidden="true"></span><?php //echo $row->survey_count;?></span>
                                    <?php else:?>
                                        <span class="no-survey" title="<?php echo $row->survey_count;?>"><span class="glyphicon glyphicon-th-large" aria-hidden="true"></span><?php //echo $row->survey_count;?></span>
                                    <?php endif;?>
                                </td>
                                <!--
                                <td>
                                    <span class="url_status-<?php echo $row->url_status;?>"><span class="glyphicon glyphicon-globe" aria-hidden="true" title="<?php echo t('url-status-'.$row->url_status);?>" ></span></span>
                                </td>
                                -->
                                <td>
                                    <?php if (trim($row->notes)!==''):?>
                                        <span class="has-note"><span class="glyphicon glyphicon-comment" aria-hidden="true" title="<?php echo $row->notes;?>" ></span></span>
                                    <?php endif;?>
                                </td>
                                <td>
                                    <?php if (trim($row->flag)!==''):?>
                                        <a class="has-flag" href="<?php echo site_url(); ?>/admin/citations/?flag[]=<?php echo $row->flag; ?>"><span class="glyphicon glyphicon-tag ico-white" title="<?php echo $row->flag;?>" ></span></a>
                                    <?php endif;?>
                                </td>
                                <td nowrap="nowrap">
                                    <span class="toggle_publish <?php echo ($row->published==1 ? 'published' : 'draft'); ?>" data-id="<?php echo $row->id;?>">
                                        <span title="Published" class="label-published" data-published="1" ><span class="glyphicon glyphicon-ok-sign"></span><?php //echo t('published');?></span>
                                        <span title="Draft" class="label-draft" data-published="0"><span class="glyphicon glyphicon-minus-sign"></span><?php //echo t('draft');?></span>
                                    </span>
                                </td>
                                <td>
                                    <a title="<?php echo t('delete');?>" class="delete-record" href="<?php echo current_url();?>/delete/<?php echo $row->id;?>/?destination=<?php echo $this->uri->uri_string();?>">
                                        <span class="glyphicon glyphicon-trash"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach;?>
                        </tbody>
                    </table>
                    <table width="100%">
                        <tr>
                            <td>
                                <div class="field">
								<?php echo t("select_number_of_records_per_page");?>:
                                <?php echo form_dropdown('ps', array(5=>5,10=>10,20=>20,30=>30,50=>50,100=>100,500=>t('ALL')), get_form_value("ps",$this->per_page),'id="ps"'); ?>
                                </div>
                            </td>
                            <td>
                                <div class="nada-pagination pull-right">
                                    <em><?php echo $pager; ?></em>&nbsp;&nbsp;&nbsp; <?php echo $page_nums;?>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class="icon-legend">
                        <!--<span class="glyphicon glyphicon-user"> </span> <?php echo t('icon_user');?>-->
                        <span class="glyphicon glyphicon-th-large"> </span> <?php echo t('icon_related_study');?>
                        <span class="glyphicon glyphicon-comment"> </span> <?php echo t('icon_note');?>
                        <span class="glyphicon glyphicon-tag"> </span> <?php echo t('icon_flag');?>
                        <!--<span class="glyphicon glyphicon-globe"> </span> <?php echo t('publication_link_status');?>-->
                    </div>
                    <?php else: ?>
                        <div>
                            <?php echo t('no_records_found');?>
                        </div>
                    <?php endif; ?>
                </form>
        </div>

    </div>
    <script type='text/javascript' >
        //checkbox select/deselect
        jQuery(document).ready(function(){
            $("#chkBox_toggle").click(

                function (e)
                {
                    $('.chkBox').each(function(){
                        this.checked = (e.target).checked;
                    });
                }
            );
            $(".chkBox").click(
                function (e)
                {
                    if (this.checked==false){
                        $("#chkBox_toggle").attr('checked', false);
                    }
                }
            );
            $("#batch_actions_apply").click(
                function (e){
                    if( $("#batch_actions").val()=="delete"){
                        batchCit_delete();
                    }
                    else if( $("#batch_actions").val()=="validate_url"){
                        batch_validate_url();
                    }
                }
            );

            pubUnpub();
        });

        function batch_validate_url() {
            if ($('.chkBox:checked').length==0){
                alert("You have not selected any items");
                return false;
            }

            selected='';
            $('.chkBox:checked').each(function(){
                if (selected!=''){selected+=',';}
                selected+= this.value;
            });

            var url= CI.base_url+'/admin/citations/batch_validate_url/?id='+selected;
            window.open(url,selected);
        }


        function batchCit_delete(){
            if ($('.chkBox:checked').length==0){
                alert("You have not selected any items");
                return false;
            }
            if (!confirm("Are you sure you want to delete the selected item(s)?"))
            {
                return false;
            }
            selected='';
            $('.chkBox:checked').each(function(){
                if (selected!=''){selected+=',';}
                selected+= this.value;
            });

            $.ajax({
                timeout:1000*120,
                dataType: "json",
                data:{ submit: "submit"},
                type:'POST',
                url: CI.base_url+'/admin/citations/delete/'+selected+'/?ajax=true',
                success: function(data) {
                    if (data.success){
                        location.reload();
                    }
                    else{
                        alert(data.error);
                    }
                },
                error: function(XHR, textStatus, thrownError) {
                    alert("Error occured " + XHR.status);
                }
            });
        }
        //page change
        $('#ps').change(function() {
            $('#citation-search').submit();
        });

        function pubUnpub()
        {
            //remove events
            $(".unpublished, .published").unbind('click');

            $(".label-published, .label-draft").click(function (e) {

                var is_published=$(this).attr("data-published");
                var citation_id=$(this).closest(".toggle_publish").attr("data-id");
                var publish_value=0;

                if (is_published==1) {
                    $(this).closest(".toggle_publish").removeClass("published").addClass("draft");
                    publish_value=0;
                }
                else
                {
                    $(this).closest(".toggle_publish").removeClass("draft").addClass("published");
                    publish_value=1;
                }
                url=CI.base_url+'/admin/citations/publish/'+citation_id+'/'+publish_value;
                $.get(url);
            });

        }

        //search by keywords and facets
        function citation_search()
        {
            $("#citation-search").before('<div class="label label-info" style="padding:10px;font-size:16px;margin-bottom:10px">Loading, please wait...</div>');
            submit_url=CI.base_url +'/admin/citations/?'+ $(".search-filters input, #citation-search").serialize();
            window.location.replace(submit_url);
        }

        //clear any selected facet options
        function clear_citation_facets()
        {
            $(".search-filters input").prop('checked',false);
        }

        jQuery(document).ready(function(){

            $(".clear-all-filters").click( function (e) {
                clear_citation_facets();
                citation_search();
            });

            //trigger search on form submit
            $( "#citation-search" ).submit(function( event ) {
                citation_search();
                event.preventDefault();
            });

            //trigger search on click on any of the filter inputs
            $(".search-filters input").click( function (e) {
                clearTimeout($.data(window, 'timer_search_filter'));
                $(window).data('timer_search_filter', setTimeout(citation_search, 1200));
            });

            //set sort options and search
            $("#select_sort_by").change( function (e) {
                $("#sort_order").val( $(this).find("option:selected").attr("data-sort_order") );
                $("#sort_by").val($(this).val());
                citation_search();
            });

        });
    </script>
