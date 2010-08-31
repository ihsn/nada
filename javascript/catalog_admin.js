//attach row hover for survey rows
function attach_hover(){
	$(".row .row-title").hover(
	  function () {
		$(this).addClass("row-hover");
	  },
	  function () {
		$(this).removeClass("row-hover");
	  }
	);
}

$(document).ready(function() {
	attach_hover();
	attach_survey_handler();
});

function attach_survey_handler(){
	$("#surveys .row .row-title").click(
			function (e) {
				
				var $row=$(this).parent();
				if ($(e.target).attr("type")=='checkbox') 
				{
					return;
				}
				
				//already selected, reset
				if ($row.data('active')==1) {
					$row.removeClass().addClass("row");
					$row.find(".info").remove();
					$row.data('active',0);
					return false;
				}
				else{
					//collapse any expanded rows
					$("#surveys .row-selected").removeClass().addClass("row").find(".info").remove();
				}
				
				$row.removeClass().addClass("row-selected");
				$row.find(".info").remove().end().append('<div class="info"><img src="images/loading.gif"/>'+i18n.js_loading+'</div>');					

				//set active
				$row.data('active',1);
					
					$.ajax({
						type: "GET",
						url: site_url+"/admin/catalog/survey/"+$row.attr("id"),
						cache: false,
						timeout:20000,
						success: function(data) {
							$row.find(".info").remove().end().append('<div class="info">'+data+'</div>');
						},
						error: function(XMLHttpRequest, textStatus, errorThrow) {
							alert("error");	
							$('#surveys').html('<div class="error">Search failed<br>'+XMLHttpRequest.responseText+'</div>');
						}		
					});
					return false;        
			}
	);
}
