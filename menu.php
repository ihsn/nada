<?php

$menu=file_get_contents("menu.json"); //http://census.ihsn.org/entity/menu/main/tree?_format=json

$menu=json_decode($menu,true);
?>

<?php /*
<ul class="sf-menu" id="site-menu">
			<li class="item1">
				<a href="followed.html">menu item 1 </a>
				<ul>
					<li>
						<a href="followed.html">menu item</a>
					</li>
					<li class="current">
						<a href="followed.html">long menu item sets sub width</a>
						<ul>
							<li class="current"><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
				</ul>
			</li>
			<li>
				<a href="followed.html">menu item 2</a>
			</li>
			<li>
				<a href="followed.html">menu item 3</a>
				<ul>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">short</a></li>
							<li><a href="followed.html">short</a></li>
							<li><a href="followed.html">short</a></li>
							<li><a href="followed.html">short</a></li>
							<li><a href="followed.html">short</a></li>
						</ul>
					</li>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
					<li>
						<a href="followed.html">menu item</a>
						<ul>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
							<li><a href="followed.html">menu item</a></li>
						</ul>
					</li>
				</ul>
			</li>
			<li>
				<a href="followed.html">menu item 4</a>
			</li>	
		</ul>
*/ ?>
        <?php //return; ?>


<?php 
//echo '<pre>';
//var_dump($menu);

echo '<div  id="containerNavbar" aria-expanded="false">';
echo '<ul id="site-menu" class="sf-menu ">';
/*foreach($menu as $items){
    if ($items['link']['enabled']==false){continue;}
    //var_dump($items['link']);

    $url=get_site_link($items['link']['url']);

    echo "<li>";
    echo '<a href="'.$url.'">'.$items['link']['title'].'</a>';
    
    if (isset($items['subtree']) && count($items['subtree']) > 0){
        echo '<ul>';
        foreach($items['subtree'] as $subtree){
            echo "<li>";
            $url=get_site_link($subtree['link']['url']);

            echo '<a href="'.$url.'">'.$subtree['link']['title'].'</a>';
            echo "</li>";        
        }
        echo "</ul>";
    }
    echo "</li>";
}*/
menu_subtree($menu);
echo '</ul>';
echo '</div>';


function menu_subtree($menu)
{
	foreach($menu as $items){
		if ($items['link']['enabled']==false){continue;}
		//var_dump($items['link']);
	
		$url=get_site_link($items['link']['url']);
	
		echo "<li>";
		if($url!=''){
			echo '<a href="'.$url.'">'.$items['link']['title'].'</a>';
		}else{
			echo '<span>'.$items['link']['title'] .'</span>';
		}
		
		if (isset($items['subtree']) && count($items['subtree']) > 0){
			echo '<ul>';
			menu_subtree($items['subtree']);
			echo "</ul>";
		}
		echo "</li>";
	}
}

function get_site_link($link){

	if ($link==''){
		return '';
	}

    $website_url='http://census.ihsn.org';

    $url=$link;

    if(substr($url,0,4)!=='http'){
        $url=$website_url.$url;
    }

    return $url;
}