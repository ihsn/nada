function iframe_dialog(href,unload_func){
	popup_dialog($(href).prop("href")+'?ajax=1&css=true&title=true','variable');
}

//overlay to block/unblock search form
function block_search_form(block){
	if (block==false){$('#search_form').unblock();return false;}
	if ($(".blockUI").is(':visible')){return;}
	$('#search_form').block({ 
		message: '<img src="images/loading.gif" border="0"/> ' + i18n.searching + ' <input type="button" value="'+i18n.cancel+'" onclick="block_search_form(false);"/>', 
		css: { padding:'10px',top:'30px',width:'300px', background:'#F9F9F9', border: '1px solid white' } ,	
		centerX: true, centerY: false,
		overlayCSS:  {backgroundColor: '#fff', opacity:0.7}
    }); 
}


//create history state
function hash_changed()
{
	$.bbq.pushState(get_search_state());
	console.log($.param.fragment());
}

function get_search_state(){
	
	var output=[];
	var country=[];
	var da=[];
	
	var result=[];

	//keywords
	output.push('sk='+$("#sk").val());
	output.push('vk='+$("#vk").val());
		
	//years
	output.push('from='+$("#from").val());
	output.push('to='+$("#to").val());
	
	//countries
	if(!$("#search_form .chk-country-any").is(":checked")) {
		$("#search_form .chk-country:checked").each(function() { 
				country.push($(this).val());
		});		
		output.push('country='+country.join(','));
	}
	
	//data access
	if(!$("#search_form .chk-da-any").is(":checked")) {
		$("#search_form .chk-da:checked").each(function() { 
				da.push($(this).val());
		});	
		output.push('dtype='+da.join(','));
	}
	
	var result={
			view:$("#view").val(),
			sk:$("#sk").val(),
			vk:$("#vk").val(),
			from:$("#from").val(),
			to:$("#to").val(),
			country: country.join(','),
			dtype:da.join(','),
			page:$("#page").val()
			}
	return result;
}

//build and perform search
function advanced_search()
{	
	if ($("#from").val() > $("#to").val()){ 
		alert(i18n.invalid_year_range_selected); 
		return false;
	}
	
	//topics
	selected_topics=$("#search_form .chk-topic:checked").length;
	total_topics=$("#search_form .chk-topic").length;
	
	//countries
	selected_countries=$("#search_form .chk-country:checked").length;
	total_countries=$("#search_form .chk-country").length;
	
	//remove topics/countries from posting
	if (selected_topics==total_topics && selected_countries==total_countries) {		
		data=$("#search_form :not(.chk-topic, .chk-topic-hd, .chk-country, .chk-country-hd)").serialize();
	}
	else if (selected_countries==total_countries) {
		data=$("#search_form :not(.chk-country, .chk-country-hd)").serialize();
	}
	else if (selected_topics==total_topics) {
		data=$("#search_form :not(.chk-topic, .chk-topic-hd)").serialize();
	}
	else{
		data=$("#search_form").serialize();	
	}

	data+='&sort_order='+$("#sort_order").val()+'&sort_by='+$("#sort_by").val();
	$("#link_export").prop("href",CI.base_url+"/catalog/export/?"+data);


	block_search_form(true);
	$.ajax({
        type: "GET",
        url: CI.base_url+"/catalog/search",
        data: data,
        cache: false,
		timeout:30000,
		success: function(data) {
            $("#surveys").html(data);
			window.search_cache[ $.param.fragment() ]=$("#surveys").html();			
			bindBehaviors(this);
			block_search_form(false);
        },
		error: function(XMLHttpRequest, textStatus, errorThrow) {
			$("#surveys").html('<div class="error">Search failed<br>'+XMLHttpRequest.responseText+'</div>');
			block_search_form(false);
        }		
    });
    return false;        
}

function popup_dialog(item_link,name) {
	var settings = {
			centerBrowser:1,
			centerScreen:1,
			height:500,
			left:0,
			location:0,
			menubar:0,
			resizable:1,
			scrollbars:1,
			status:0,
			width:600,
			windowName:name,
			windowURL:item_link,
			top:0,
			toolbar:0
		};
		var windowFeatures =    'height=' + settings.height +
								',width=' + settings.width +
								',toolbar=' + settings.toolbar +
								',scrollbars=' + settings.scrollbars +
								',status=' + settings.status + 
								',resizable=' + settings.resizable +
								',location=' + settings.location +
								',menuBar=' + settings.menubar;
		
		if ($.browser.msie) {//IE hack
			centeredY = (window.screenTop - 120) + ((((document.documentElement.clientHeight + 120)/2) - (settings.height/2)));
			centeredX = window.screenLeft + ((((document.body.offsetWidth + 20)/2) - (settings.width/2)));
		}else{
			centeredY = window.screenY + (((window.outerHeight/2) - (settings.height/2)));
			centeredX = window.screenX + (((window.outerWidth/2) - (settings.width/2)));
		}

		window.open(settings.windowURL, settings.windowName, windowFeatures+",left="+centeredX+",top="+centeredY).focus();
		//window.open(settings.windowURL, "Window3", "resizable=1,width=600,height=500,scrollbars=yes,left="+centeredX+",top="+centeredY);
}

//TODO: may be use .ON for binding instead
function bindBehaviors(e)
{
	//show/hide variable search
	$(".vsearch").unbind('click').click(function(event) {		
		var result=$(this).parent().find(".vsearch-result");
		if (result.html()!='' ){
			result.empty().hide();
			result.parent().find(".open-close").prop("src",'images/next.gif');
		}
		else{
			result.show().html(i18n.loading).load($(this).prop("href"), function(data){
				//attach click event handler to the variable list links
				
				$(".variables-found .vsearch-result a").click(function(e){
					item_link=$(this).clone().prop("href",$(this).prop("href")+'?ajax=1&css=true&title=true');
					//iframe_dialog(item_link);
					popup_dialog($(this).prop("href")+'?ajax=1&css=true&title=true','variable');
					return false;
				});
				
				//attach compare handlers
				variable_compare_handlers();
			});
			result.parent().find(".open-close").prop("src",'images/arrow_down.gif');
		}	
		return false;
	})
	//sort links
	$(".catalog-sort-links a").unbind('click').click(function(event) {
		formdata=$(this).prop("href")+'&ajax=1&'+$("#search_form").serialize();
		$('#surveys')
			.html('<img src="images/loading.gif" align="bottom" border="0"/> '+ i18n.searching)
			.load(formdata, function (data){
				bindBehaviors(this);
				});return false;
	});	
	
	//data forms dialog
	$('.accessform').unbind('click').click(function(event) {
			item_link=$(this).clone().prop("href",$(this).prop("href")+'?ajax=true');;
			iframe_dialog(item_link,on_lightbox_unload);return false;
	});

	//compare dialog link
	$(".dlg").unbind('click').click(function(event) {
		item_link=$(this).clone().prop("href",$(this).prop("href")+'?ajax=true&css=true');;
		iframe_dialog(item_link);return false;	
	});
	
	//attach variable compare handlers
	variable_compare_handlers();
	
	//on page size change
	$(".switch-page-size .button, .switch-page-size .btn").unbind('click').click(function(event) {
		$("#ps").val($(this).html());
		$("#page").val(1);
		alert("page-size-pre-search");
		advanced_search();
	});
	
	//page navigation
	$(".pager a.page").unbind("click").click(function(event){
		$("#page").val( $(this).prop("data-page") );
		hash_changed();
		return false;
	});
}

//compare checkbox click
function variable_compare_handlers(){
	$('.compare').unbind('click').click(function(event) {
		if ($(this).prop("checked")){
			$.get(CI.base_url+'/catalog/compare_add/'+ $(this).val());
		}
		else{
			$.get(CI.base_url+'/catalog/compare_remove/'+ $(this).val());
		}	
	});
}

//change page view - study or variable
function change_view(value){
	if ($("#view").val()==value){return false;}
	$("#view").val(value);$("#page").val(1);
	advanced_search();
}


$(document).ready(function() 
{	
	//reset page if any form values change; must appear before any change handlers on the form
	$("#search_form :input").change(function() {
		$("#page").val(1);
	});

	//years
	$('#from, #to').change(function(event) {
		if ($("#from").val() > $("#to").val()){ 
			alert(i18n.invalid_year_range_selected); return false;
		}
		hash_changed();
	});

	//country
	/*
	$("#search_form .chk-country").click(function(event) {
		$("#search_form #countries-container .chk-country-any").prop("checked",false);
		hash_changed();
	});	
	$("#search_form #countries-container .chk-country-any").click(function(event) {
		$("#search_form .chk-country").prop("checked",false);
		hash_changed();
	});
	*/
	
	//filter checkbox any or specific item click: applies to country, topic
	$("#search_form .items-container .item :checkbox").click(function(event) {
		//uncheck ANY option
		$(this).closest(".filter-box").find(".chk-any").prop("checked",false);
		hash_changed();
	});	
	//ANY checkbox click handler. unset all other options
	$("#search_form .filter-box .any :checkbox").click(function(event) {
		$(this).closest(".filter-box").find(".items-container").find(".chk").prop("checked",false);
		hash_changed();
	});
	
	//data access
	$("#search_form .chk-da").click(function(event) {
		//block_search_form(true);
		$(this).parent().parent().find('.chk-da').prop('checked',$(this).prop('checked') );
		$("#search_form .chk-da-any").prop("checked",false);
		hash_changed();
	});
	$("#search_form .chk-da-any").click(function(event) {
		$("#search_form .chk-da").prop("checked",false);
		hash_changed();
	});
	
	
	//search button
	$("#btnsearch").click(function() {
    	$("#page").val(1);
		hash_changed();return false;
	});		

	bindBehaviors();
});

function search_page(num){
	$("#page").val(num);advanced_search();	
}



(function() {
escape_re = /[#;&,\.\+\*~':"!\^\$\[\]\(\)=>|\/\\]/;
jQuery.escape = function jQuery$escape(s) {
  var left = s.split(escape_re, 1)[0];
  if (left == s) return s;
  return left + '\\' + 
    s.substr(left.length, 1) + 
    jQuery.escape(s.substr(left.length+1));
}
})();







//hashchange event handler
$(window).bind( 'hashchange', function(e) {

	fragments=$.deparam.fragment();
	var found=false;
	
	//country
	if(typeof fragments.country != 'undefined'){		
		var countries=fragments.country.split(",");		
		$("#search_form .chk-country").each(function() { 
			if ($.inArray($(this).prop('value'), countries)!==-1 ){
				$(this).prop('checked',true);
				found=true;
			}
			else{
				$(this).prop('checked',false);
			}
		});	
		
		//uncheck/check ANY option
		$("#search_form .chk-country-any").prop("checked",!found);
	}
	else
	{
		//default country filter options
		$("#search_form .chk-country-any").prop("checked",true);
	}


	//dtype
	if(typeof fragments.dtype != 'undefined'){
		var value_arr=fragments.dtype.split(",");
		var found=false;
		$("#search_form .chk-da").each(function() { 
			if ($.inArray($(this).prop('value'), value_arr)!==-1 ){
				$(this).prop('checked',true);
				found=true;
			}
			else
			{
				$(this).prop('checked',false);
			}
		});	
		//uncheck/check ANY option
		$("#search_form .chk-da-any").prop("checked",!found);
	}
	else
	{
		//default country filter options
		$("#search_form .chk-da-any").prop("checked",true);
	}

	//years
	if(typeof fragments.from != 'undefined'){
		$("#from option[value='"+fragments.from+"']").prop('selected', 'selected');
	}
	if(typeof fragments.to != 'undefined'){
		$("#to option[value='"+fragments.to+"']").prop('selected', 'selected');
	}

	//keywords
	if(typeof fragments.sk != 'undefined'){
		$("#sk").val(fragments.sk);
	}
	if(typeof fragments.vk != 'undefined'){
		$("#vk").val(fragments.vk);
	}

	if(typeof fragments.page != 'undefined'){
		$("#page").val(fragments.page);
	}
	
	var fragment_str = $.param.fragment();
	if ( window.search_cache[ fragment_str ] ) {
		console.log("found in cache");
		$("#surveys").html(window.search_cache[ fragment_str ]);
		bindBehaviors();
	}
	else {	
		if ($.param.fragment()!==""){
			advanced_search();
		}
	}
})

$(document).ready(function()  {
	//global search cache
	window.search_cache={};
	//trigger hashchange event on page load
	$(window).trigger( 'hashchange' );	
	
  	/////////////////////////////////////////////////////////////////////////////////////////////
	// selection dialog
	/////////////////////////////////////////////////////////////////////////////////////////////
	window.init_dialog=function init_dialog(dialog_id,title,data_url)
	{		
		$("body").append('<div id="'+dialog_id+'" title="'+title+'"></div>');
		
	 var dialog=$( "#"+dialog_id ).dialog({
      height: 470,
	  position:"center",
	  width:730,
      modal: true,
	  autoOpen: false,
	  buttons: {
        "Apply filter": function() {
			var dialog=$(this).closest(".ui-dialog");
			var source_list=dialog.data('source-list');			
			var dialog_selection=dialog.find(".container").find(".cnt :checked");
			var values=[];

			dialog_selection.each(function() { 
				values.push($(this).val()); 
			});
			
			var found=false;
			
			source_list.find(".chk").each(function() { 
				if ($.inArray($(this).prop('value'), values)!==-1 ){
					$(this).prop('checked',true);
					found=true;
				}
				else{
					$(this).prop('checked',false);
				}			
			});	
			
			$( this ).dialog( "close" );
			
			if (found) { 
				//uncheck ANY option
				source_list.find(".chk-any").prop("checked",false);
				hash_changed();
			}
			else{
				//check ANY option if nothing selected from the dialog
				source_list.find(".chk-any").prop("checked",true);
				hash_changed();
			}

        }//end apply filter
      }//end-buttons
    });//end-dialog

	//load dialog content
	$('#'+dialog_id).load(data_url, function() {		
		dialog.closest(".ui-dialog").find(".ui-dialog-title").append('<div class="ui-dialog-subtitle"><span class="ui-dialog-stats"></span> | <span class="clear-selection link">Clear</span></div>');
	});
	
	}//end function

	//find out how many dialogs needs to be created
	//add dialog html to the body
	$(".filter-box .btn-select").each(function(){
		var dialog_id=$(this).attr("data-dialog-id");
		var dialog_title=$(this).attr("data-dialog-title");
		var data_url=$(this).attr("data-url");
		if(typeof dialog_id !== "undefined") {
			init_dialog(dialog_id,dialog_title,data_url);
		}
	});

	//dialag open button click handler
	$(".filter-box .btn-select").click(function(event){		
		var dialog_id=$(this).attr("data-dialog-id");		
		if(typeof dialog_id === "undefined") {return false;}		

		var dialog=$( "#"+dialog_id ).dialog( "open" );
		var source_list=$(this).closest(".filter-box").find(".items-container");		
		var dialog_ui=dialog.closest(".ui-dialog");
		dialog_ui.data('source-list', source_list);
		
		//pre select options on the dialog using values from the source_list
		var source_selection=[];
		source_list.find(".chk:checked").each(function() { 
			source_selection.push($(this).val()); 
		});
			
		var found=false;
			
		dialog_ui.find(".container").find(".col-2-s input,.col-2 input").each(function() { 
			if ($.inArray($(this).val(), source_selection)!==-1 ){
				$(this).prop('checked',true);
				found=true;
			}
			else{
				$(this).prop('checked',false);
			}			
		});
		
		dialog_update_stats(dialog_ui);
	});

	

	//append html for country dialog
	//$("body").append('<div id="country-dialog-modal" title="Select countries/regions"></div>');
	//$("body").append('<div id="topic-dialog-modal" title="Select topics"></div>');
	/*	
	 $( "#topic-dialog-modal" ).dialog({
      height: 470,
	  position:"center",
	  width:730,
      modal: true,
	  autoOpen: false,
	  buttons: {
        "Apply filter": function() {
			var dialog=$(this).closest(".ui-dialog");
			var source_list=dialog.data('source-list');			
			var dialog_selection=dialog.find(".container").find(".cnt :checked");
			var values=[];
			
			dialog_selection.each(function() { 
				values.push($(this).val()); 
			});
			
			var found=false;
			
			source_list.find(".chk").each(function() { 
				if ($.inArray($(this).prop('value'), values)!==-1 ){
					$(this).prop('checked',true);
					found=true;
				}
				else{
					$(this).prop('checked',false);
				}			
			});	
			
			$( this ).dialog( "close" );
			
			if (found) { 
				//uncheck ANY option
				source_list.find(".chk-any").prop("checked",false);
				hash_changed();
			}
			else{
				//check ANY option if nothing selected from the dialog
				source_list.find(".chk-any").prop("checked",true);
				hash_changed();
			}

        }//end apply filter
      }//end-buttons
	  
    });
	
	//load dialog content
	$('#topic-dialog-modal').load('index.php/catalog/topic_selection', function() {		
		//setup stats element for showing selection
		$(".ui-dialog-title").append('<div class="ui-dialog-subtitle"><span class="ui-dialog-stats"></span> | <span class="clear-selection">Clear</span></div>')
	});

	*/

  
    /*
	$( "#country-dialog-modal" ).dialog({
      height: 470,
	  position:"center",
	  width:730,
      modal: true,
	  autoOpen: false,
	  buttons: {
        "Apply filter": function() {
			var selected_countries=$(this).closest(".ui-dialog").find(".container").find(".col-2-s :checked");
			var values=[];
			
			selected_countries.each(function() { 
				values.push($(this).val()); 
			});
			
			var found=false;
			
			$("#search_form .chk-country").each(function() { 
				if ($.inArray($(this).prop('value'), values)!==-1 ){
					$(this).prop('checked',true);
					found=true;
					//console.log('country selection updated'+ $(this).prop('value'));
				}
				else{
					$(this).prop('checked',false);
				}			
			});	
			
			$( this ).dialog( "close" );
			
			if (found) { 
				//uncheck ANY option when a country is checked
				$("#search_form .chk-country-any").prop("checked",false);
				hash_changed();
			}
			else{
				//check ANY option if no country was selected from the dialog
				$("#search_form .chk-country-any").prop("checked",true);
				hash_changed();
			}

        }//end apply filter
      }//end-buttons
    });
	
	//load dialog content
	$('#country-dialog-modal').load('index.php/catalog/country_selection', function() {
  		//load tabs
		$( "#tabs" ).tabs();
		
		//setup stats element for showing country selection
		$(".ui-dialog-title").append('<div class="ui-dialog-subtitle"><span class="ui-dialog-stats"></span> | <span class="clear-selection">Clear</span></div>')
	});
	
	//trigger dialog open
	$("#btn-country-selection").click(function(event){
		//update dialog country selection from the page		
			var selected_countries=[];
			$("#search_form .chk-country:checked").each(function() { 
				selected_countries.push($(this).val()); 
			});
			
			console.log(selected_countries);
			var found=false;
			
			$(".ui-dialog").find(".container").find(".col-2-s input,.col-2 input").each(function() { 
				if ($.inArray($(this).val(), selected_countries)!==-1 ){
					$(this).prop('checked',true);
					found=true;
				}
				else{
					$(this).prop('checked',false);
				}			
			});
			country_dialog_update_stats();
			
		//open dialog
		$( "#country-dialog-modal" ).dialog( "open" );
	});
	*/

	//parent/child selection
	$(document.body).on("click",".ui-dialog .rows-container :checkbox", function(){ 
		var data_type=$(this).attr("data-type");
		var dialog=$(this).closest(".ui-dialog");
		if (data_type=='parent'){
			//select/deselect all children
			var parent_checkbox=$(this);
			$(this).closest(".row").find(":checkbox").each(function() { 
				$(this).prop('checked',parent_checkbox.prop("checked"));
				dialog.find('[value="'+$(this).val()+'"]').prop("checked",parent_checkbox.prop("checked"));
			});
		}
		else if (data_type=='child'){
			//deselect parent
			$(this).closest(".row").find(".parent").prop("checked",false);
			//check/uncheck all other instances of the element
			dialog.find('[value="'+$(this).val()+'"]').prop("checked",$(this).prop("checked"));
		}
		dialog_update_stats(dialog);
	});
	
	//update dialog selection
	//@cnt count items only with the class=cnt
	function dialog_update_stats(dialog){
		var selected=dialog.find(".container").find(".cnt :checked").length;
		dialog.find(".ui-dialog-stats").html(selected + ' selected');
	}



	//scrollto options for dialog countries by regions
	$(document.body).on("click",".ui-dialog .container .index span", function(){ 
		$(".ui-dialog .rows-container").scrollTo("#"+$(this).attr("data-id"));
	});

	//dailog reset button	
	$(document.body).on("click",".ui-dialog .clear-selection", function(){ 
		var dialog=$(this).closest(".ui-dialog");
		dialog.find("input:checkbox").prop("checked",false);
		dialog_update_stats(dialog);
	});
	
	
	$(document.body).on("click",".active-filters-container .remove-filter", function(){ 
		console.log($(this));
		var type=$(this).attr("data-type");
		var value=$(this).attr("data-value");
		switch(type)
		{
			case 'country':
				$(".filter-box .items-container :checkbox[value="+value+"]").trigger("click");	
				break;
			case 'years':
				$("#from").val($("#from option:last").val());
				$("#to").val($("#to option:first").val());
				$("#from").trigger("change")				
				break;
			case 'sk':
				$("#sk").val("");hash_changed();
				break;
			case 'vk':
				$("#vk").val("");hash_changed();
				break;
		}
		
	});
	
	
	
	/*
	//country/region parent/child selection
	$(document.body).on("click","#country-dialog-modal .rows-container :checkbox", function(){ 
		var data_type=$(this).attr("data-type");
		if (data_type=='parent'){
			//select/deselect all children
			var parent_checkbox=$(this);
			$(this).closest(".row").find(":checkbox").each(function() { 
				$(this).prop('checked',parent_checkbox.prop("checked"));
				$(this).closest(".ui-dialog").find('[value="'+$(this).val()+'"]').prop("checked",parent_checkbox.prop("checked"));
			});
		}
		else if (data_type=='child'){
			//deselect parent
			$(this).closest(".row").find(".parent").prop("checked",false);
			//check/uncheck all other instances of the element
			$(this).closest(".ui-dialog").find('[value="'+$(this).val()+'"]').prop("checked",$(this).prop("checked"));
		}
		country_dialog_update_stats();
	});
	
	//update country dialog selection message
	function country_dialog_update_stats(){
		var selected=$(".ui-dialog").find(".container").find(".col-2-s :checked").length;
		console.log(selected);
		$(".ui-dialog").find(".ui-dialog-stats").html(selected + ' selected');
	}
	*/
	
	//end-country-dialog //////////////////////////////////////////////////////////////////
	
	
  });//end-document-ready






/*
// Needs reviewing before removed

function select_countries(option){
	if (option=='all'){
		$("#search_form").find('.chk-country').prop('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form .chk-country").each(function() { $(this).prop('checked',!$(this).prop('checked')); });
	}
	else if (option=='none'){
		$("#search_form").find('.chk-country').prop('checked',false);
	}
	return false;
}
function select_centers(option){
	if (option=='all'){
		$("#search_form").find('.chk-center').prop('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form .chk-center").each(function() { $(this).prop('checked',!$(this).prop('checked')); });
	}
	else if (option=='none'){
		$("#search_form").find('.chk-center').prop('checked',false);
	}
	selected_centers_stats();
	return false;
}

function selected_centers_stats()
{
	selected=$("#search_form .chk-center:checked").length;
	if (selected==0) {
		$("#selected-centers").html('');
	}
	else if (selected==1)
	{
		$("#selected-centers").html(selected + ' ' + i18n.center_selected);
	}
	else{
		$("#selected-centers").html(selected + ' ' + i18n.centers_selected);
	}		
}

function select_collections(option){
	if (option=='all'){
		$("#search_form").find('.chk-collection').prop('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form .chk-collection").each(function() { $(this).prop('checked',!$(this).prop('checked')); });
	}
	else if (option=='none'){
		$("#search_form").find('.chk-collection').prop('checked',false);
	}
	selected_centers_stats();
	return false;
}
function selected_collections_stats()
{
	selected=$("#search_form .chk-collection:checked").length;
	if (selected==0) {
		$("#selected-collections").html('');
	}
	else if (selected==1)
	{
		$("#selected-collections").html(selected + ' ' + i18n.collection_selected);
	}
	else{
		$("#selected-collections").html(selected + ' ' + i18n.collections_selected);
	}		
}
function selected_countries_stats()
{
	selected=$("#search_form .chk-country:checked").length;
	if (selected==0) {
		$("#selected-countries").html('');
	}
	else if (selected==1)
	{
		$("#selected-countries").html(selected + ' ' + i18n.country_selected);
	}
	else{
		$("#selected-countries").html(selected + ' ' + i18n.countries_selected);
	}		
}

function select_topics(option){
	if (option=='all'){
		$("#search_form").find('.chk-topic, .chk-topic-hd').prop('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form .chk-topic").each(function() { $(this).prop('checked',!$(this).prop('checked')); });
	}
	else if (option=='none'){
		$("#search_form").find('.chk-topic, .chk-topic-hd').prop('checked',false);
	}
	selected_topics_stats();
	//filter_by_topics();
	return false;
}
function selected_topics_stats()
{
	selected=$("#search_form .chk-topic:checked").length;
	if (selected==0) {
		$("#selected-topics").html('');
	}
	else if (selected==1){
		$("#selected-topics").html(selected + ' ' + i18n.topic_selected);
	}
	else{
		$("#selected-topics").html(selected + ' ' + i18n.topics_selected);
	}
}
function filter_by_countries(){
	selected_countries=$("#search_form .chk-country:checked").length;
	if (selected_countries==0){
		$("#topics-list .topic-container input:checkbox").prop('disabled', false).parent().removeClass("disabled");
		//reset years
		//apply_filter_to_year(years.from,years.to);
		advanced_search();return;
	}
		
	$.getJSON(CI.base_url+'/catalog/filter_by_country',$("#search_form").serialize(), function(data) {
		$("#topics-list .topic-container input:checkbox").prop('disabled', true).parent().addClass("disabled");;
		jQuery.each(data['topics'], function() {
    		$("#topics-list .topic-container input:checkbox[value='"+this+"']").prop('disabled',false).parent().removeClass("disabled");;
		})
		apply_filter_to_year(data['min_year'],data['max_year']);
		advanced_search();				
	});
}


function apply_filter_to_year(min_year, max_year)
{
	//reset years to min/max if zero
	if (min_year==0) {
		return false;
		min_year=years.from;
	}
	
	if(max_year ==0){
		return false;
		max_year=years.to;	
	}
		
	//already selected year data
	var year_min=$("#from").val();
	var year_max=$("#to").val;

	//clear the list
	$("#from option, #to option").remove()

	//add new years
	for(i=min_year;i<=max_year;i++){
		$("#from,#to").append('<option value="'+i+'">'+i+'</option>');
	}
	
	//select years on the list
	$("#from").val(min_year);
	$("#to").val(max_year);	
}

//actions on existing the light box
function on_lightbox_unload()
{
	//refresh login bar
	$("#user-container").load(CI.base_url+'/page/user_bar');
}

//navigate page using the dropdowns
function navigate_page() {
    $("#page").val($("#page2").val());advanced_search();
}

//data access filter helper functions
function select_da(option){
	if (option=='all'){
		$("#search_form #datatype-list").find('.chk').prop('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form #datatype-list .chk").each(function() { $(this).prop('checked',!$(this).prop('checked')); });
	}
	else if (option=='none'){
		$("#search_form #datatype-list").find('.chk').prop('checked',false);
	}
	return false;
}


	//centers
	$("#search_form .chk-center").click(function(event) {
		$(this).parent().parent().find('.chk-center').prop('checked',$(this).prop('checked') );
		selected_centers_stats();
	});
	
	//collections
	$("#search_form .chk-collection").click(function(event) {
		$(this).parent().parent().find('.chk-collection').prop('checked',$(this).prop('checked') );
		selected_collections_stats();
	});

*/