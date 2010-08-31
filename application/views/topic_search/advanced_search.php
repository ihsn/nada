<style>
.ui-accordion .ui-accordion-header a{padding-top:2px;padding-bottom:2px;}
.ui-state-default a, .ui-state-default a:link, .ui-state-default a:visited{color:#666666;font-weight:normal;}
.button{padding:3px;margin-top:5px;cursor:pointer;background-color:#6699FF;color:white;border:0px;}

input{background-color:white;border:1px solid gainsboro;font-size:14px;}
.checkboxes{font-size:12px;font-weight:normal;}


.topic-container {
	overflow: hidden;
	width: 100%;
	font-size:11px;
}
.topic-items{background:white;display:inline;list-style:none;padding:0px;margin:0px;}
.topic-items li{padding:5px;margin-left:10px;}
x.topic-container ul,.xtopic-container li{list-style:none;display:block;}
div.left {
	width: 46%;
	float: left;
	margin-right:5px;
	margin-bottom:5px;
	margin-left:5px;
}

</style>
<script type="text/javascript"> 
	$(function() {
		$("#accordion").accordion({
			collapsible: true
		});
	});
	</script> 
    
<h1>Search</h1>
<div class="demo"> 
 
<div id="accordion" > 
	<h3><a href="#">Select Countries</a></h3> 
	<div> 
		<p>Mauris mauris ante, blandit et, ultrices a, suscipit eget, quam. Integer ut neque. Vivamus nisi metus, molestie vel, gravida in, condimentum sit amet, nunc. Nam a nibh. Donec suscipit eros. Nam mi. Proin viverra leo ut odio. Curabitur malesuada. Vestibulum a velit eu ante scelerisque vulputate.</p> 
	</div> 
	<h3><a href="#">Topics</a></h3> 
	<div style="height:100px;"> 
		<!-- topics -->
        <div class="topic-container">	
	<div class="left"><div class="topic-heading "><input type="checkbox" value="13" name="id[]">Transport (0)</div><ul class="topic-items"><li class="topic"><input type="checkbox" value="14" name="id[]">Transport &amp; Social Responsibility (0)</li><li class="topic"><input type="checkbox" value="15" name="id[]">Urban Transport (0)</li><li class="topic"><input type="checkbox" value="16" name="id[]">Railways (0)</li></ul></div><div class="left"><div class="topic-heading "><input type="checkbox" value="1" name="id[]">Agriculture &amp; Rural Development (1)</div><ul class="topic-items"><li class="topic"><input type="checkbox" value="17" name="id[]">Empowerment (1)</li></ul></div><div class="left"><div class="topic-heading "><input type="checkbox" value="2" name="id[]">Children &amp; Youth (2)</div><ul class="topic-items"><li class="topic"><input type="checkbox" value="12" name="id[]">child 2 (0)</li><li class="topic"><input type="checkbox" value="11" name="id[]">child 1 (0)</li></ul></div><div class="left"><div class="topic-heading "><input type="checkbox" value="3" name="id[]">Environment (3)</div><ul class="topic-items"><li class="topic"><input type="checkbox" value="4" name="id[]">The Global Environment Facility (1)</li><li class="topic"><input type="checkbox" value="5" name="id[]">Safeguard Policies (1)</li><li class="topic"><input type="checkbox" value="6" name="id[]">Carbon Finance (0)</li></ul></div><div class="left"><div class="topic-heading "><input type="checkbox" value="18" name="id[]">Social Development (2)</div><ul class="topic-items"><li class="topic"><input type="checkbox" value="19" name="id[]">Confilt Prevention (2)</li><li class="topic"><input type="checkbox" value="20" name="id[]">Safe guard (3)</li></ul></div><div class="left"><div class="topic-heading "><input type="checkbox" value="7" name="id[]">Health, Nutrition &amp; Population (1)</div><ul class="topic-items"><li class="topic"><input type="checkbox" value="8" name="id[]">Nutrition (0)</li><li class="topic"><input type="checkbox" value="9" name="id[]">Public Health (0)</li><li class="topic"><input type="checkbox" value="10" name="id[]">HIV/ AIDS (2)</li></ul></div>	<br style="clear: both;">
</div>
        <!-- end topics -->
        
	</div> 
<!--
	<h3><a href="#">Datasets/Surveys</a></h3> 
	<div> 
			<label for="var_keywords">Keywords: </label>
            <input type="text" name="var_keywords" style="width:75%;"/><br/>
	</div> 

	<h3><a href="#">Questions</a></h3> 
	<div style="font-size:12px;"> 
			<label for="var_keywords">Keywords: </label>
            <input type="text" name="var_keywords" style="width:75%;"/><br/>
            
            <input type="checkbox" name="label"/>
            <label for="label">Label</label>
            
            <input type="checkbox" name="question"/>
            <label for="question">Questions</label>
            
            <input type="checkbox" name="Categories"/>
            <label for="label">Categories</label>

		<div style="color:#CC6600;margin-top:10px;">You can search variables by Label, Questions or categories. To search in multiple fields, tick all the boxes that apply.</div>
	</div> 
-->
</div> 

	<div class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all" style="padding:5px;">
    <label for="var_keywords">Search in study description</label>
    <input type="text" name="var_keywords" style="display:block;width:98%;"/>
    
    <br/>
    
			<div class="variable-search">
            <label for="var_keywords">Search in variable description</label>
            <input type="text" name="var_keywords" style="width:99%;display:block"/>
            </div>
          
          	<div class="checkboxes">  
                <input type="checkbox" name="label"/>
                <label for="label">Label</label>
                
                <input type="checkbox" name="question"/>
                <label for="question">Questions</label>
                
                <input type="checkbox" name="Categories"/>
                <label for="label">Categories</label>
            </div>

    </div>
    
 <div style="text-align:right;"> <input class="button" type="button" name="search" value="Search"/><input class="button" style="background-color:gainsboro;margin-left:5px;" type="button" name="search" value="Reset"/></div>
</div><!-- End demo --> 
 
 
 
<div class="demo-description"> 
 
<p>By default, accordions always keep one section open. To allow for all sections to be be collapsible, set the <code>collapsible</code> option to true. Click on the currently open section to collapse its content pane.</p> 
 
 
</div><!-- End demo-description --> 