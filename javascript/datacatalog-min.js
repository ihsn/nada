$(function(){$("#accordion").accordion({collapsible:true,active:false,clearStyle:false,autoHeight:true});$(".ceebox2").ceebox();});function iframe_dialog(href,unload_func){$.fn.ceebox.overlay();$.fn.ceebox.popup(href,{onload:true,unload:unload_func});return false;}
function block_search_form(block){if(block==false){$('#search_form').unblock();return false;}
if($(".blockUI").is(':visible')){;return;}
$('#search_form').block({message:'<img src="images/loading.gif" border="0"/> '+i18n.searching+' <input type="button" value="'+i18n.cancel+'" onclick="block_search_form(false);"/>',css:{padding:'10px',top:'30px',width:'300px',background:'#F9F9F9',border:'4px solid white'},centerX:true,centerY:false,overlayCSS:{backgroundColor:'#fff',opacity:0.7}});}
function advanced_search()
{if($("#from").val()>$("#to").val()){alert(i18n.invalid_year_range_selected);return false;}
selected_topics=$("#search_form .chk-topic:checked").length;total_topics=$("#search_form .chk-topic").length;if(selected_topics==total_topics){data=$("#search_form :not(.chk-topic, .chk-topic-hd) ").serialize();}
else{data=$("#search_form").serialize();}
data+='&sort_order='+sort_info.sort_order+'&sort_by='+sort_info.sort_by;$("#link_export").attr("href",CI.base_url+"/catalog/export/?"+data);block_search_form(true);$.ajax({type:"GET",url:CI.base_url+"/catalog/search",data:data,cache:false,timeout:20000,success:function(data){$("#surveys").html(data);bindBehaviors(this);block_search_form(false);},error:function(XMLHttpRequest,textStatus,errorThrow){$("#surveys").html('<div class="error">Search failed<br>'+XMLHttpRequest.responseText+'</div>');block_search_form(false);}});return false;}
function bindBehaviors(e)
{$(".vsearch").unbind('click').click(function(event){var result=$(this).parent().find(".vsearch-result");if(result.html()!=''){result.empty().hide();result.parent().find(".open-close").attr("src",'images/next.gif');}
else{result.show().html(i18n.loading).load($(this).attr("href"),function(data){$(".variables-found .vsearch-result a").click(function(e){item_link=$(this).clone().attr("href",$(this).attr("href")+'?ajax=1&css=true&title=true');iframe_dialog(item_link);return false;});variable_compare_handlers();});result.parent().find(".open-close").attr("src",'images/arrow_down.gif');}
return false;})
$(".catalog-sort-links a").unbind('click').click(function(event){formdata=$(this).attr("href")+'&ajax=1&'+$("#search_form").serialize();$('#surveys').html('<img src="images/loading.gif" align="bottom" border="0"/> '+i18n.searching).load(formdata,function(data){bindBehaviors(this);});return false;});$('.accesspolicy').unbind('click').click(function(event){iframe_dialog($(this));return false;});$('.accessform').unbind('click').click(function(event){item_link=$(this).clone().attr("href",$(this).attr("href")+'?ajax=true');;iframe_dialog(item_link,on_lightbox_unload);return false;});$(".dlg").unbind('click').click(function(event){item_link=$(this).clone().attr("href",$(this).attr("href")+'?ajax=true&css=true');;iframe_dialog(item_link);return false;});variable_compare_handlers();$(".switch-page-size .button").unbind('click').click(function(event){$("#ps").val($(this).html());$("#page").val(1);advanced_search();});}
function variable_compare_handlers(){$('.compare').unbind('click').click(function(event){if($(this).attr("checked")){$.get(CI.base_url+'/catalog/compare_add/'+$(this).val());}
else{$.get(CI.base_url+'/catalog/compare_remove/'+$(this).val());}});}
function change_view(value){if($("#view").val()==value){return false;}
$("#view").val(value);$("#page").val(1);advanced_search();}
$(document).ready(function()
{$("#search_form .chk-country").click(function(event){block_search_form(true);$(this).parent().parent().find('.chk-topic').attr('checked',$(this).attr('checked'));selected_countries_stats();selected_topics_stats();filter_by_countries();});$("#search_form .chk-topic-hd").click(function(event){$(this).parent().parent().find('.chk-topic').attr('checked',$(this).attr('checked'));selected_countries_stats();selected_topics_stats();filter_by_topics();});$("#search_form .chk-topic").click(function(){if($(this).attr('checked')==false){$(this).parents('.topic-container').find('.chk-topic-hd').attr('checked',false);}
selected_topics_stats();filter_by_topics();});$("#btnsearch").click(function(){$("#page").val(0);advanced_search();return false;});year_change_handlers();bindBehaviors();});function search_page(num){$("#page").val(num);advanced_search();}
function select_countries(option){if(option=='all'){$("#search_form").find('.chk-country').attr('checked',true);}
else if(option=='toggle'){$("#search_form .chk-country").each(function(){$(this).attr('checked',!$(this).attr('checked'));});}
else if(option=='none'){$("#search_form").find('.chk-country').attr('checked',false);}
selected_countries_stats();filter_by_countries();return false;}
function selected_countries_stats()
{selected=$("#search_form .chk-country:checked").length;if(selected==0){$("#selected-countries").html('');}
else if(selected==1)
{$("#selected-countries").html(selected+' '+i18n.country_selected);}
else{$("#selected-countries").html(selected+' '+i18n.countries_selected);}}
function select_topics(option){if(option=='all'){$("#search_form").find('.chk-topic, .chk-topic-hd').attr('checked',true);}
else if(option=='toggle'){$("#search_form .chk-topic").each(function(){$(this).attr('checked',!$(this).attr('checked'));});}
else if(option=='none'){$("#search_form").find('.chk-topic, .chk-topic-hd').attr('checked',false);}
selected_topics_stats();filter_by_topics();return false;}
function selected_topics_stats()
{selected=$("#search_form .chk-topic:checked").length;if(selected==0){$("#selected-topics").html('');}
else if(selected==1){$("#selected-topics").html(selected+' '+i18n.topic_selected);}
else{$("#selected-topics").html(selected+' '+i18n.topics_selected);}}
function filter_by_countries(){selected_countries=$("#search_form .chk-country:checked").length;if(selected_countries==0){$("#topics-list .topic-container input:checkbox").attr('disabled',false).parent().removeClass("disabled");advanced_search();return;}
$.getJSON(CI.base_url+'/catalog/filter_by_country',$("#search_form").serialize(),function(data){$("#topics-list .topic-container input:checkbox").attr('disabled',true).parent().addClass("disabled");;jQuery.each(data['topics'],function(){$("#topics-list .topic-container input:checkbox[value='"+this+"']").attr('disabled',false).parent().removeClass("disabled");;})
apply_filter_to_year(data['min_year'],data['max_year']);advanced_search();});}
function filter_by_topics()
{selected_topics=$("#search_form .chk-topic:checked").length;total_topics=$("#search_form .chk-topic").length;if(selected_topics==0||selected_topics==total_topics)
{$("#countries-list input:checkbox").attr('disabled',false).parent().removeClass("disabled");advanced_search();return;}
$.getJSON(CI.base_url+'/catalog/filter_by_topic',$("#search_form").serialize(),function(data){$("#countries-list input:checkbox").attr('disabled',true).parent().addClass("disabled");jQuery.each($("#countries-list input:checkbox"),function()
{if(jQuery.inArray($(this).attr("value"),data["countries"])>-1){$(this).attr('disabled',false).parent().removeClass("disabled");}})
apply_filter_to_year(data['min_year'],data['max_year']);advanced_search();});}
function apply_filter_to_year(min_year,max_year)
{if(min_year==0){return false;min_year=years.from;}
if(max_year==0){return false;max_year=years.to;}
var year_min=$("#from").val();var year_max=$("#to").val;$("#from option, #to option").remove()
for(i=min_year;i<=max_year;i++){$("#from,#to").append('<option value="'+i+'">'+i+'</option>');}
$("#from").val(min_year);$("#to").val(max_year);}
function year_change_handlers()
{$('#from, #to').unbind('change').change(function(event){filter_by_year();});}
function filter_by_year()
{if($("#from").val()>$("#to").val()){alert(i18n.invalid_year_range_selected);return false;}
$.getJSON(CI.base_url+'/catalog/filter_by_years',$("#search_form").serialize(),function(data){$("#countries-list input:checkbox").attr('disabled',true).parent().addClass("disabled");jQuery.each(data["countries"],function(){$("#countries-list input:checkbox[value='"+this+"']").attr('disabled',false).parent().removeClass("disabled");})
$("#topics-list .topic-container input:checkbox").attr('disabled',true).parent().addClass("disabled");;jQuery.each(data['topics'],function(){$("#topics-list .topic-container input:checkbox[value='"+this+"']").attr('disabled',false).parent().removeClass("disabled");;})
$("#page").val(0);advanced_search();});}
function on_lightbox_unload()
{$("#user-container").load(CI.base_url+'/page/user_bar');}
function navigate_page(){$("#page").val($("#page2").val());advanced_search();}