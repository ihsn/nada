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
        <a class="dataset-type-tab nav-link <?php echo $tabs['active_tab']=='' ? 'active' : '';?>" data-value="" href="#">All</a>
    </li>

    <?php foreach($tabs['types'] as $tab):?>
        <?php 
            $tab_target=site_url("catalog/?tab_type={$tab['code']}");
        ?>
        <li class="nav-item">
            <a class="dataset-type-tab nav-link <?php echo $tab['code']==$tabs['active_tab'] ? 'active' : '';?>" data-value="<?php echo $tab['code'];?>" href="<?php echo $tab_target;?>">
                <?php echo $tab['title'];?>
                <?php if(isset($tabs['search_counts_by_type']) &&  array_key_exists($tab['code'],$tabs['search_counts_by_type'])) :?>
                    <?php  /*<br/><div class="badge badge-secondary">    
                        <?php echo $tabs['search_counts_by_type'][$tab['code']];?>
                    </div> */?>
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
        /*//store current page
        let search_state=$("#catalog-search-form").serialize();
        let page_state_data={
                'search_options': $("#catalog-search-form").serializeArray(),
                'search_results': $( "#search-result-container" ).html()
            };
           History.pushState(null,null, );
           console.log("loading for the first time");
           //State.data.page_state_data=page_state_data;
           */
    }else{
        //load current state
        load_current_state();

        //todo: use cache instead?
        //search();

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

        console.log(name, value);
        el_name="[name='" + name + "']," + "[name='" + name + "[]']";
        console.log(el_name);
        elements=$(el_name);

        console.log(elements);
        window.x=elements;


        if (elements.prop("type")=='checkbox'){
            named_el=$("[name='" + name + "'][value='"+value+"']");
            console.log(named_el);
            named_el.prop("checked",false);
            console.log(named_el);
        }
        else if(elements.prop("type")=='text' || elements.prop("tagName").toLowerCase()=='select'){
            elements.prop("value",'');
        }
        

        $(this).hide();
        change_state();
        
    });


    function search()
    {
        console.log("Starting search");
        $( "#search-result-container" ).html('loading, please wait ...');
        let search_state=$("#catalog-search-form").serialize();
        console.log(search_state);

        $.get('<?php echo site_url('catalog/search');?>?'+search_state, function( data ) {
            $( "#search-result-container" ).html( data );
            let page_state_data={
                'search_options': $("#catalog-search-form").serializeArray(),
                'search_results': null
            };
            //History.pushState({state:search_state,page_state_data}, search_state, "?"+search_state);            
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

    $(document.body).on("click",".dataset-type-tab", function(){

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
});
</script>


