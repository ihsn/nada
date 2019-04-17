<style>
 
 .search-nav-tabs .nav-item .active{   
    background-color: transparent;
    border:0px;
    border-bottom: 3px solid red;    
}


.dataset-type-label{
    text-transform: uppercase;
    color:gray;
    font-size:10px;
    border:1px solid gainsboro;
    padding:3px;
    font-weight:normal!important;
}

.breadcrumb{
    display:none;
}

#catalog-search-form{
    margin-top:50px;
}

.search-box-container{
    position:relative;
}

.clear-search-button {
    position: absolute;
    right: 100px;
    top: 9px;
    z-index:999;
    display:none;
}



.nav-tabs-auto-overflow {
  overflow-x: auto;
  overflow-y: hidden;
  display: -webkit-box;
  display: -moz-box;
}
.nav-tabs-auto-overflow >li {
  float: none;
}

.filters-container{
    width:100%;
}

.navbar-toggler-filter,
.navbar-expand-filters{
    padding:0px;
}

.btn-search-submit{
    padding-left:30px;
    padding-right:30px;
}

.lnk-filter-reset{
    xcolor:blue;
    xtext-decoration: underline;
    font-size:small;
    cursor:pointer;
    font-weight:bold;
}

.lnk-filter-reset:hover{
    color:black;
}

.type-count, .type-count-all{
    color: #6c757d;
    display:block;
    font-weight:normal;
    margin-left:17px;
}

.search-nav-tabs .nav-link{
    padding-bottom:0px;
}

#search-keywords {
    border:1px solid #007bff
}

 /* Chrome/Opera/Safari */
#search-keywords::-webkit-input-placeholder {
  color: #dee2e6;
}

/* Firefox 19+ */
#search-keywords::-moz-placeholder { 
    color: #dee2e6;
}

/* IE 10+ */
#search-keywords:-ms-input-placeholder { 
    color: #dee2e6;
}

/* Firefox 18- */
#search-keywords:-moz-placeholder { 
    color: #dee2e6;
}

</style>
<script src="http://browserstate.github.io/history.js/scripts/bundled/html4+html5/jquery.history.js"></script>



<div class="container">
<form method="get" id="catalog-search-form">    
    <!--
<pre>
<?php //print_r($search_options);?>
</pre>
-->
<input type="hidden" name="tab_type" id="tab_type" value="<?php echo $search_options->tab_type;?>"/>
<input type="hidden" name="page" id="page" value="<?php echo $search_options->page;?>"/>

<!--search bar-->
<div>
    <!--<h5>Catalog search</h5>-->
    <div class="row mb-5 justify-content-center align-items-center">
        <div class="input-group col-md-12 col-xl-10 search-box-container">            
        <input class="form-control form-control-lg py-2 search-keywords" id="search-keywords" name="sk" value="<?php echo $search_options->sk;?>" placeholder="Keywords ..."  >
        <span class="input-group-append">
            <button class="btn btn-outline-primary btn-search-submit" type="submit" id="submit_search">
                <i class="fa fa-search"></i>
            </button>
            <!--<a class="btn btn-link btn-sm" href="<?php echo site_url('catalog');?>">Reset</a>-->
            <a href="<?php echo site_url('catalog');?>" class="close clear-search-button" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </a>
        </span>
        </div>
    </div>
</div>

<div>
<ul class="nav nav-tabs nav-tabs-auto-overflow mb-5 search-nav-tabs =">
    <li class="nav-item">
        <a class="dataset-type-tab dataset-type-tab-all nav-link <?php echo $tabs['active_tab']=='' ? 'active' : '';?>" data-value="" href="#">All         
        <span class="type-count-all">&nbsp;</span>
        </a>
    </li>

    <?php 
        $type_icons=array(
            'survey'=>'<i class="fa fa-database" aria-hidden="true"></i>',
            'geospatial'=>'<i class="fa fa-globe" aria-hidden="true"></i>',
            'timeseries'=>'<i class="fa fa-clock-o" aria-hidden="true"></i>',
            'document'=>'<i class="fa fa-file-text-o" aria-hidden="true"></i>',
            'table'=>'<i class="fa fa-table" aria-hidden="true"></i>',
            'visualization'=>'<i class="fa fa-pie-chart" aria-hidden="true"></i>',            
            'script'=>'<i class="fa fa-file-code-o" aria-hidden="true"></i>',
            'image'=>'<i class="fa fa-camera" aria-hidden="true"></i>',
        );
    ?>

    <?php foreach($tabs['types'] as $tab):?>
        <?php 
            $tab_target=site_url("catalog/?tab_type={$tab['code']}");
        ?>
        <li class="nav-item">
            <a class="dataset-type-tab dataset-type-tab-<?php echo $tab['code'];?> nav-link <?php echo $tab['code']==$tabs['active_tab'] ? 'active' : '';?>" data-value="<?php echo $tab['code'];?>" href="<?php echo $tab_target;?>">
                <?php echo @$type_icons[$tab['code']];?>
                <?php echo t('tab_'.$tab['code']);?>
                <?php if(isset($tabs['search_counts_by_type']) ) :?>
                    <?php $count=0;
                        if (array_key_exists($tab['code'],$tabs['search_counts_by_type'])){
                            $count=$tabs['search_counts_by_type'][$tab['code']];
                        }
                    ?>
                    <span class="type-count"> <?php echo @number_format((int)$count);?> </span>
                <?php endif;?>
            </a>
        </li>
    <?php endforeach;?>
        
    </ul>
</div>




<div class="row">
    <!--left side bar -->
    <div class="col-12 col-lg-3 col-md-4">

        <nav class="navbar navbar-expand-sm navbar-expand-filters">
            
            <button class="navbar-toggler btn-block navbar-toggler-filter" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="btn btn-outline-secondary btn-block" xstyle="font-size:12px"><i class="fa fa-sliders" aria-hidden="true"></i> Filters</span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">

                <div class="filters-container">
                    <?php foreach($filters as $filter):?>
                        <?php echo $filter;?>
                    <?php endforeach;?>
                </div>
            </div>                        
        </nav>

    </div>
    <!-- end left side bar -->



    <!-- listing page -->
    <div class="col-lg-9 col-md-8">
        <div id="search-result-container">
            <?php echo $search_output;?>
        </div>
    </div>
    <!-- end listing-->




</div>
</form>

</div>

<script>
$(document).ready(function() 
{
    var page_first_load=true;
    toggle_reset_search_button();
    var State=History.getState();

    if(!State.data.page_state_data){        
        console.log("setting first loaded page state");
        page_first_load=false;
        let search_state=$("#catalog-search-form").serialize();
        let page_state_data={
                'search_options': $("#catalog-search-form").serializeArray(),
                'search_results': null
            };
        History.replaceState({state:search_state,page_state_data}, search_state, "?"+search_state);
    }else{
        load_current_state();
        toggle_reset_search_button();
    }
    

    function reset_page(){
        $("#page").val(1);
    }    

    //submit search form
    $(document.body).on("click","#submit_search", function(){                    
        reset_page();
        change_state();
        return false;
    });

    $(document.body).on("click",".remove-filter", function(){

        name=$(this).attr("data-type");
        value=$(this).attr("data-value");

        el_name="[name='" + name + "']," + "[name='" + name + "[]']";
        elements=$(el_name);

        if (name=='years'){
            $("#from").val("");
            $("#to").val("");
        }

        if (elements.length>0){
            if (elements.prop("type")=='checkbox'){
                named_el=$("[name='" + name + "'][value='"+value+"']");
                console.log(named_el);
                named_el.prop("checked",false);
                console.log(named_el);
            }
            else if(elements.prop("type")=='text' || elements.prop("tagName").toLowerCase()=='select'){
                elements.prop("value",'');
            }
        }
        

        $(this).hide();
        change_state();
        
    });


    function search()
    {
        search_state=$("#catalog-search-form").serialize();
        $( "#search-result-container" ).html('Loading, please wait ...');

        $.get('<?php echo site_url('catalog/search');?>?'+search_state, function( data ) {
            $( "#search-result-container" ).html( data );
            let page_state_data={
                'search_options': $("#catalog-search-form").serializeArray(),
                'search_results': null
            };

            //reset nav-tabs
            $(".dataset-type-tab").find(".type-count").html("0");

            //update nav-tabs
            let types_summary=$(".type-summary").attr("data-types");

            if(types_summary){
                types_summary=JSON.parse(types_summary);
                jQuery.each(types_summary, function(data_type, counts ) {
                    $(".dataset-type-tab-"+data_type).find(".type-count").html(counts);
                });
            }

        });        
    }


    //call this for search
    function change_state()
    {
        console.log("change_state called");
        let search_state=$("#catalog-search-form").serialize();
        
        let page_state_data={
                'search_options': $("#catalog-search-form").serializeArray(),
                'search_results': null
            };
        History.pushState({state:search_state,page_state_data}, search_state, "?"+search_state);
    }


    //sort dropdown
    $(document.body).on("change","#sort-by-select", function()
    {
        let sort_order=$(this).find(':selected').data('sort');
        let sort_by=$(this).val();
        window.x=$(this);
        $("#sort_by").val(sort_by);
        $("#sort_order").val(sort_order);
        change_state();
    });

    $(document.body).on("click",".dataset-type-tab", function()
    {
        $( ".chk-type").prop("checked",false);
        el=$("[name='type[]'][value='"+ $(this).attr("data-value") +"']");
        el.prop("checked",true);
        reset_page();
        $( "#tab_type" ).val($(this).attr("data-value"));
        
        window.location.href='<?php echo site_url('catalog');?>?'+$("#catalog-search-form").serialize();
        return false;
    });

    //pagination link
    $(document.body).on("click",".pagination .page-link", function(){        
        $( "#page" ).val($(this).attr("data-page"));
        change_state();
        return false;
    });

    $(document.body).on("change",".filters-container .chk, .filters-container select", function(){        
        change_state();  
    });
    
    $(document.body).on("keypress",".search-keywords", function(e){    
        var code = e.keyCode;
        toggle_reset_search_button();
        if(code==13){
            $('#submit_search').trigger("click");
            return false;
        }
    });
    


    History.Adapter.bind(window,'statechange',function(){ // Note: We are using statechange instead of popstate        
        var State = History.getState(); // Note: We are using History.getState() instead of event.state        
        //$( "#catalog-search-form" ).html( State.data.data );
        window.data=State.data;
        console.log("loading state");
        console.log(State);
        //$( "#search-result-container" ).html(State.data.search_results);        

        load_current_state();
    });



    function load_current_state(){

        console.log(page_first_load);
        if(page_first_load==true){
            console.log("page_first_load==true");
            page_first_load=false;
            return;
        }

        var State=History.getState();

        if(!State.data.page_state_data){
            console.log("no current state found, exiting");
            return false;
        }
        console.log("load_current_state");

        //$('#catalog-search-form').trigger("reset");
        reset_all_filters();
        jQuery.each(State.data.page_state_data.search_options, function( i, field ) {
            elements=$("[name='" + field.name + "']");
            
            if (elements.prop("type")=='checkbox'){
                named_el=$("[name='" + field.name + "'][value='"+field.value+"']");
                named_el.prop("checked",true);
            }
            else if(elements.prop("type")=='text' || elements.prop("tagName").toLowerCase()=='select'){
                elements.prop("value",field.value);
            }
        });

        //only time search function should be called
        search();
    }


    function reset_all_filters()
    {
        //uncheck all checkboxes
        $(".filters-container .chk").prop("checked",false);

        //reset  select
        $(".filter-container .form-control").prop("value",'');
        }

        function toggle_reset_search_button()
        {
        if (!$("#search-keywords").val()){
            $(".clear-search-button").hide();
        }
        else{
            $(".clear-search-button").show();
        }
    }




    //show/hide study sub-variable search
    $(document.body).on("click",".vsearch", function(event){
        event.stopPropagation();
		$(this).parent().toggleClass("expand");
		var result=$(this).parent().find(".vsearch-result");
		if (result.html()!='' ){
			result.empty().hide();
		}
		else{
			result.show().html('<span class="fa fa-circle-o-notch fa-spin fa-2x text-primary"></span><span>Loading</span>').load($(this).prop("href"), function(data){
				
				//attach compare handlers
				//variable_compare_handlers();
			});
			//result.parent().find(".open-close").prop("src",'images/arrow_down.gif');
		}
		//compare_var_summary();
		return false;
    })

    //show variable details in a modal dialog
    $(document.body).on("click",".variables-found .vsearch-result .link", function(event){
        var row=$(this).closest("tr");
        window.simple_dialog("dialog_id",row.attr("data-title"),$(this).attr("href"));
        event.stopPropagation();
        return false;
    });

    


    /////////////////////////////////////////////////////////////////////////////////////////////
	// simple dialog
	/////////////////////////////////////////////////////////////////////////////////////////////
	window.simple_dialog=function simple_dialog(dialog_id,title,data_url)
	{
		if($("#"+dialog_id).length ==0) {
            $("body").append('<div class="modal fade" id="'+dialog_id+'" tabindex="-1" role="dialog"  aria-hidden="true">\
                <div class="modal-dialog  modal-lg catalog-modal-dialog" role="document">\
                <div class="modal-content">\
                <div class="modal-header">\
                <h5 class="modal-title" id="'+dialog_id+'Label">'+title+'</h5>\
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">\
						<span aria-hidden="true">&times;</span>\
					</button>\
					</div>\
					<div class="modal-body">\
				</div>\
					<div class="modal-footer">\
						<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>\
					</div>\
					</div>\
					</div>\
					</div>');
		}
		else
		{
            /********* for jQuery ui modal *******/
			/*dialog=	$("#"+dialog_id);
			dialog.html("loading...");
			dialog.dialog({ title: title});*/

            /********* for Bootstrap modal *********/
            $('#'+dialog_id+' h5.modal-title').html(title);
            $('#'+dialog_id+' div.modal-body').html("loading...");
        }
        
        // for jQuery ui modal
		/*
		dialog.dialog( "open" ); // for jQuery ui modal
        //$('#'+dialog_id).load(data_url+'?ajax=1');//load content
        */

        // for Bootstrap modal
		$('#'+dialog_id).modal('show');// for Bootstrap modal
        $('#'+dialog_id+' div.modal-body').load(data_url+'?ajax=1');//load content
	}//end function



});





</script>


