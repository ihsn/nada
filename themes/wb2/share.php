<?php
//current page url
$page_url=current_url();

//remove site url from the url string
$page_url=str_replace(site_url(),"",$page_url);

$parts=explode("?",$page_url);

//split URI parts
$uri_parts=explode("/",$parts[0]);

$uri=site_url();
foreach($uri_parts as $part)
{
	if (!ctype_alnum($part))
	{
		$uri.=urlencode($part).'/';
	}
	else
	{
		$uri.=$part.'/';
	}	
}
$page_url=$uri;
if (isset($parts[1]))
{
	$page_url.=urlencode($parts[1]);
}
?>

<div class="share-bar-container">
<ul class="share-bar">
    <?php /*<li><a target="_blank" rel="nofollow" href="<?php echo $page_url;?>?print=yes"><img src="images/print.gif" alt="Print"/></a></li>*/?>
    <li><a rel="nofollow" href="mailto:?subject=<?php echo $title;?>&amp;body=<?php echo $page_url;?>"><img src="images/email.png" alt="Email"/></a></li>
    <li>
    	<a href="#" class="share"><?php echo t('share');?></a>
        <ul class="">
            <li><a title="<?php echo t('share_with_digg');?> "target="_blank" href="http://digg.com/submit?url=<?php echo $page_url;?>&title=<?php echo $title;?>"><img src="images/icons/digg16.png"/> Digg</a></li>
            <li><a title="<?php echo t('share_with_buzz');?>" target="_blank" href="http://www.google.com/buzz/post?message=<?php echo $title;?>&url=<?php echo $page_url;?>"><img src="images/icons/buzz16.png"/> Google Buzz</a></li>
            <li><a title="<?php echo t('share_with_linkedin');?>" target="_blank" href="http://www.linkedin.com/cws/share?url=<?php echo $page_url;?>&title=<?php echo $title;?>&source=<?php echo site_url();?>"><img src="images/icons/linkedin16.png"/> LinkedIn</a></li>
            <li><a title="<?php echo t('share_with_stumpleupon');?>" target="_blank" href="http://www.stumbleupon.com/submit?url=<?php echo $page_url; ?>&title=<?php echo $title;?>"><img src="images/icons/stumbleupon16.png"/> Stumbleupon</a></li>
            <li><a title="<?php echo t('share_with_delicious');?>" target="_blank" href="http://www.delicious.com/save?v=5&noui&jump=close&url=<?php echo $page_url; ?>&title=<?php echo $title; ?>"><img src="images/icons/delicious16.png"/> Delicious</a></li>
        </ul>
    </li>
    <li><a target="_blank" 	title="<?php echo t('share_with_facebook');?>"	href="http://www.facebook.com/sharer.php?u=<?php echo $page_url; ?>&t=<?php echo $title; ?>&src=sp"><img src="images/facebook.png"/></a></li>
    <li><a target="_blank" title="<?php echo t('share_with_twitter');?>"href="http://twitter.com/share?_=original_referer=<?php echo $page_url; ?>&text=<?php echo $title; ?>&url=<?php echo $page_url; ?>"><img src="images/twitter.png" alt="Twitter" /></a></li>
</ul>
</div>

<script type="text/javascript">
jQuery(function($) {
	$('.share-bar .share').click(toggle_menu);
	function toggle_menu(event)
	{
		if ($(this).parent().is(".active"))
		{
			$(this).parent().removeClass("active");
		}
		else
		{
			$(this).parent().addClass('active');
		}	
		event.stopPropagation();
		return false;
	}
	
	$('body').click(function(event) {
 		//hide the share box
		if(!$(event.target).is('.active ul, .active li'))
		{
			$('.share-bar .active').removeClass("active");
		}
 	});
 
});
</script>
