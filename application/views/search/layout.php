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

<<<<<<< HEAD
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
    right: 64px;
    top: 9px;
    display:none;
}

=======
>>>>>>> tip - search
</style>
<script src="http://browserstate.github.io/history.js/scripts/bundled/html4+html5/jquery.history.js"></script>



<div class="container">
<form method="get" id="catalog-search-form">    
<<<<<<< HEAD
    <!--
<pre>
<?php //print_r($search_options);?>
</pre>
-->
<input type="hidden" name="tab_type" id="tab_type" value="<?php echo $search_options->tab_type;?>"/>
<input type="hidden" name="page" id="page" value="<?php echo $search_options->page;?>"/>
=======

<input type="hidden" name="tab_type" id="tab_type" value=""/>
>>>>>>> tip - search

<!--search bar-->
<div>
    <!--<h5>Catalog search</h5>-->
    <div class="row mb-5 justify-content-center align-items-center">
<<<<<<< HEAD
        <div class="input-group col-10 search-box-container">            
        <input class="form-control form-control-lg py-2 search-keywords" id="search-keywords" name="sk" value="<?php echo $search_options->sk;?>" placeholder="Keywords ..."  >
=======
        <div class="input-group col-10">
            <div class="mr-3 mt-2"><strong>Catalog search</strong></div>
        <input class="form-control py-2 search-keywords" id="search-keywords" name="sk" value="" >
>>>>>>> tip - search
        <span class="input-group-append">
            <button class="btn btn-outline-secondary" type="submit" id="submit_search">
                <i class="fa fa-search"></i>
            </button>
<<<<<<< HEAD
            <!--<a class="btn btn-link btn-sm" href="<?php echo site_url('catalog');?>">Reset</a>-->
            <a href="<?php echo site_url('catalog');?>" class="close clear-search-button" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </a>
=======
            <a class="btn btn-link btn-sm" href="<?php echo site_url('catalogue');?>">Reset</a>
>>>>>>> tip - search
        </span>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-5 search-nav-tabs">
    <li class="nav-item">
        <a class="dataset-type-tab nav-link <?php echo $tabs['active_tab']=='' ? 'active' : '';?>" data-value="" href="#">All</a>
    </li>

    <?php foreach($tabs['types'] as $tab):?>
        <li class="nav-item">
            <a class="dataset-type-tab nav-link <?php echo $tab['code']==$tabs['active_tab'] ? 'active' : '';?>" data-value="<?php echo $tab['code'];?>" href="#">
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





<div class="row">
    <!--left side bar -->
    <div class="col-3">

        <div class="filters-container">
            <?php foreach($filters as $filter):?>
                <?php echo $filter;?>
            <?php endforeach;?>
        </div>

    </div>
    <!-- end left side bar -->



    <!-- listing page -->
    <div class="col-9">
        <div id="search-result-container">
            <?php echo $search_output;?>
        </div>
    </div>
    <!-- end listing-->




</div>
</form>

</div>

<script>
    function load_current_state(){

        var State=History.getState();

        if(!State.data.page_state_data){
            return false;
        }

        $('#catalog-search-form').trigger("reset");
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

$(document).ready(function() 
{	
    toggle_reset_search_button();

    var State=History.getState();

    if(!State.data.page_state_data){
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
        search();

        toggle_reset_search_button();
    }
    

    function reset_page(){
        $("#page").val(1);
    }    

    //submit search form
    $(document.body).on("click","#submit_search", function(){                    
        reset_page();
        search();
        return false;
    });

    function search()
    {
        $( "#search-result-container" ).html('loading, please wait ...');
        let search_state=$("#catalog-search-form").serialize();

        $.get('<?php echo site_url('catalog/search');?>?'+search_state, function( data ) {
            $( "#search-result-container" ).html( data );
            let page_state_data={
                'search_options': $("#catalog-search-form").serializeArray(),
                'search_results': null
            };
            History.pushState({state:search_state,page_state_data}, search_state, "?"+search_state);            
        });        
    }

    $(document.body).on("click",".dataset-type-tab", function(){

        $( ".chk-type").prop("checked",false);
        el=$("[name='type[]'][value='"+ $(this).attr("data-value") +"']");
        el.prop("checked",true);
        reset_page();
        $( "#tab_type" ).val($(this).attr("data-value"));
        
        window.location.href='<?php echo site_url('catalog');?>?'+$("#catalog-search-form").serialize();


        /*
        NOTE: no need to track tab_type states, simply load the whole page

        $( ".chk-type").prop("checked",false);
        el=$("[name='type[]'][value='"+ $(this).attr("data-value") +"']");
        el.prop("checked",true);
        $( "#tab_type" ).val($(this).attr("data-value"));
        window.this_=$(this);
        $(this).parent().parent().find(".active").removeClass("active");
        $(this).addClass("active");
        search();
        return false;
        */
    });

    //pagination link
    $(document.body).on("click",".pagination .page-link", function(){        
        $( "#page" ).val($(this).attr("data-page"));
        search();
        return false;
    });

    $(document.body).on("change",".filters-container .chk, .filters-container select", function(){        
        search();        
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
        $( "#search-result-container" ).html(State.data.search_results);        

        load_current_state();
    });
});
</script>


