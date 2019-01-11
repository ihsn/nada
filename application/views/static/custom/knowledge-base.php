<style>
dt, dt a{font-size:16px; margin-bottom:10px;color:#0071bc;}
dt{padding-left:20px;}
dt:before {
    content: "\f0da";
    font-family: FontAwesome;
    left:-5px;
	margin-right:10px;
 }
dt:hover {cursor:pointer;}
dd {margin-left:35px;}
</style>

<h1>Knowledge Base</h1>
<p>&nbsp;</p>

<div class="loading"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Loading, please wait...</div>

<dl id="support-qa">
</dl>

<p>&nbsp;</p>
<p>&nbsp;</p>

<script type="text/javascript">

$(function() {
	_expand();
});

function _expand(){
	var anchor_name=window.location.hash;
	$(anchor_name).nextUntil('dt').show();	
}


$(function() {

	$.getJSON('https://datahelpdesk.worldbank.org/api/v1/topics/38788/articles.json?oauth_consumer_key=XzQsWhTqmnE8dYDOJ0HjJQ&oauth_signature_method=HMAC-SHA1&per_page=20&callback=?', function(data) {
		
		$(".loading").hide();
		
		var items = [];
		$.each(data.articles, function(key, val) {
			items.push('<dt id="microdata-' + key + '">' + val.title + '</dt><dd><p>'+val.answer_html+'</p></dd>');
		});
		
		var html=items.join('');
		$("#support-qa").append(html);
	  
	  	//hide all answers
		$('dd').hide();
		
		//show/hide on click
		$('dt').click(function(e){
			$(this).nextUntil('dt').toggle();
		});
		
		$("#support-qa").find("ul").addClass("bl");

	});

});
</script>