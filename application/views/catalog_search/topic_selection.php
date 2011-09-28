<style>
	.topic-container {
	border: 1px solid gainsboro;
	overflow: hidden;
	width: 100%;
	margin-bottom:10px;
	background-color:#EBEBEB;
	padding-top:10px;
	padding-bottom:10px;
	font-size:12px;
}

xdiv.left {
	width: 45%;
	float: left;
}

div.right {
	width: 45%;
	float: right;
}

/* tabs start */
#tabmenu {
		color: gray;
		border-bottom: 1px solid gainsboro;
		margin: 12px 0px 0px 0px;
		padding: 0px;
		z-index: 1;
		padding-left: 10px }

	#tabmenu li {
		display: inline;
		overflow: hidden;
		list-style-type: none; }

	#tabmenu a, a.active {
		color: red;
		background: gainsboro;
		font: normal 1em "Trebuchet MS", Arial, sans-serif;
		border: 1px solid gainsboro;
		padding: 2px 5px 0px 5px;
		margin: 0;
		text-decoration: none; }

	#tabmenu a.active {
		background: #EBEBEB;
		color:green;
		border-bottom: 3px solid #F3F3F3; }

	#tabmenu a:hover {
		color: #fff;
		background: #ADC09F; }

	#tabmenu a:visited {
		color:#666666 }

	#tabmenu a.active:hover {
		background: #ABAD85;
		color: orange; }

	#tab-contents {font: 0.9em/1.3em "bitstream vera sans", verdana, sans-serif;
		text-align: justify;
		background: #F3F3F3;
		padding: 20px;
		border: 1px solid gainsboro;
		border-top: none;
		z-index: 2;	}

	#tab-contents a {
		text-decoration: none;
		color: #E8E9BE; }

	#tab-contents a:hover { background-color:#F3F3F3 }
/* tabs end */
</style>


<ul id="tabmenu">
	<li><a href="#">Project</a></li>
    <li class="selected"><a href="#">studies</a></li>
    <li><a href="#">variables</a></li>
    <li><a class="active" href="#">topics</a></li>
</ul>
<div class="topic-container">
<?php foreach($topics as $item): ?>
		<div class="left"><input type="checkbox"/><?php echo $item['title'] .' ('.$item['surveys_found'].')';?></div>
<?php endforeach;?>
</div>


