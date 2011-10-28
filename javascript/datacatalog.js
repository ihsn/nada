//functions related to the datacatalog search page
//requires the site_url variable and years variables
function remove_accordion_content_flash(){
	//remove class to hide page flash
	$(".js").removeClass();
}

$(function() {
	$("#accordion").accordion({
		collapsible: true,
		active: false,
		clearStyle: false,
 	 	autoHeight: false
	});
	setTimeout('remove_accordion_content_flash()',2000);
});

function iframe_dialog(href,unload_func){
	popup_dialog($(href).attr("href")+'?ajax=1&css=true&title=true','variable');
}

//overlay to block/unblock search form
function block_search_form(block){
	if (block==false){$('#search_form').unblock();return false;}
	if ($(".blockUI").is(':visible')){return;}
	$('#search_form').block({ 
		message: '<img src="images/loading.gif" border="0"/> ' + i18n.searching + ' <input type="button" value="'+i18n.cancel+'" onclick="block_search_form(false);"/>', 
		css: { padding:'10px',top:'30px',width:'300px', background:'#F9F9F9', border: '4px solid white' } ,	
		centerX: true, centerY: false,
		overlayCSS:  {backgroundColor: '#fff', opacity:0.7}
    }); 
}

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
	$("#link_export").attr("href",CI.base_url+"/catalog/export/?"+data);
	
	block_search_form(true);
	$.ajax({
        type: "GET",
        url: CI.base_url+"/catalog/search",
        data: data,
        cache: false,
		timeout:20000,
		success: function(data) {
            $("#surveys").html(data);
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

function bindBehaviors(e)
{
	//show/hide variable search
	$(".vsearch").unbind('click').click(function(event) {		
		var result=$(this).parent().find(".vsearch-result");
		if (result.html()!='' ){
			result.empty().hide();
			result.parent().find(".open-close").attr("src",'images/next.gif');
		}
		else{
			result.show().html(i18n.loading).load($(this).attr("href"), function(data){
				//attach click event handler to the variable list links
				
				$(".variables-found .vsearch-result a").click(function(e){
					item_link=$(this).clone().attr("href",$(this).attr("href")+'?ajax=1&css=true&title=true');
					//iframe_dialog(item_link);
					popup_dialog($(this).attr("href")+'?ajax=1&css=true&title=true','variable');
					return false;
				});
				
				//attach compare handlers
				variable_compare_handlers();
			});
			result.parent().find(".open-close").attr("src",'images/arrow_down.gif');
		}	
		return false;
	})
	//sort links
	$(".catalog-sort-links a").unbind('click').click(function(event) {
		formdata=$(this).attr("href")+'&ajax=1&'+$("#search_form").serialize();
		$('#surveys')
			.html('<img src="images/loading.gif" align="bottom" border="0"/> '+ i18n.searching)
			.load(formdata, function (data){
				bindBehaviors(this);
				});return false;
	});	
	
	//data forms dialog
	$('.accessform').unbind('click').click(function(event) {
			item_link=$(this).clone().attr("href",$(this).attr("href")+'?ajax=true');;
			iframe_dialog(item_link,on_lightbox_unload);return false;
	});

	//compare dialog link
	$(".dlg").unbind('click').click(function(event) {
		item_link=$(this).clone().attr("href",$(this).attr("href")+'?ajax=true&css=true');;
		iframe_dialog(item_link);return false;	
	});
	
	//attach variable compare handlers
	variable_compare_handlers();
	
	//page size switch
	$(".switch-page-size .button, .switch-page-size .btn").unbind('click').click(function(event) {
		$("#ps").val($(this).html());
		$("#page").val(1);
		advanced_search();
	});
}

//compare checkbox click
function variable_compare_handlers(){
	$('.compare').unbind('click').click(function(event) {
		if ($(this).attr("checked")){
			$.get(CI.base_url+'/catalog/compare_add/'+ $(this).val());
		}
		else{
			$.get(CI.base_url+'/catalog/compare_remove/'+ $(this).val());
		}	
	});
}

function change_view(value){
	if ($("#view").val()==value){return false;}
	$("#view").val(value);$("#page").val(1);advanced_search();
}


$(document).ready(function() 
{	
	$("#search_form .chk-country").click(function(event) {
		//block_search_form(true);
		$(this).parent().parent().find('.chk-topic').attr('checked',$(this).attr('checked') );
		selected_countries_stats();
		selected_topics_stats();
		//filter_by_countries();//advanced_search();
	});

	$("#search_form .chk-topic-hd").click(function(event) {
		$(this).parent().parent().find('.chk-topic').attr('checked',$(this).attr('checked') );
		selected_countries_stats();
		selected_topics_stats();
		//filter_by_topics();
	});

	//uncheck the parent checkbox if a sub-topic is unchecked
	$("#search_form .chk-topic").click(function() {
		if ($(this).attr('checked')==false)	{
			$(this).parents('.topic-container').find('.chk-topic-hd').attr('checked',false );
		}
		selected_topics_stats();	
    	//filter_by_topics();
	});

	$("#btnsearch").click(function() {
    	$("#page").val(0);
		advanced_search();return false;
	});		
	
	year_change_handlers();
	selected_countries_stats();
	selected_topics_stats();	
	bindBehaviors();
});

function search_page(num){
	$("#page").val(num);advanced_search();	
}

function select_countries(option){
	if (option=='all'){
		$("#search_form").find('.chk-country').attr('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form .chk-country").each(function() { $(this).attr('checked',!$(this).attr('checked')); });
	}
	else if (option=='none'){
		$("#search_form").find('.chk-country').attr('checked',false);
	}
	selected_countries_stats();
	//filter_by_countries();//advanced_search();				
	return false;
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
		$("#search_form").find('.chk-topic, .chk-topic-hd').attr('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form .chk-topic").each(function() { $(this).attr('checked',!$(this).attr('checked')); });
	}
	else if (option=='none'){
		$("#search_form").find('.chk-topic, .chk-topic-hd').attr('checked',false);
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
		$("#topics-list .topic-container input:checkbox").attr('disabled', false).parent().removeClass("disabled");
		//reset years
		//apply_filter_to_year(years.from,years.to);
		advanced_search();return;
	}
		
	$.getJSON(CI.base_url+'/catalog/filter_by_country',$("#search_form").serialize(), function(data) {
		$("#topics-list .topic-container input:checkbox").attr('disabled', true).parent().addClass("disabled");;
		jQuery.each(data['topics'], function() {
    		$("#topics-list .topic-container input:checkbox[value='"+this+"']").attr('disabled',false).parent().removeClass("disabled");;
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

//bind change event to years
function year_change_handlers()
{
	$('#from, #to').unbind('change').change(function(event) {
		filter_by_year();
	});
}

function filter_by_year()
{
	if ($("#from").val() > $("#to").val()){ alert(i18n.invalid_year_range_selected); return false;}
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


/*data access filter helper functions*/
function select_da(option){
	if (option=='all'){
		$("#search_form #datatype-list").find('.chk').attr('checked',true);
	}
	else if (option=='toggle'){
		$("#search_form #datatype-list .chk").each(function() { $(this).attr('checked',!$(this).attr('checked')); });
	}
	else if (option=='none'){
		$("#search_form #datatype-list").find('.chk').attr('checked',false);
	}
	return false;
}