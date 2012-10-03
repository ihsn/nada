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

<div class="grid_20 backgroundcolor page-tools-zindex"> 
          <!-- (F03v1) Page Tools starts -->
          <div id="f03v1-page-tools">
            <div class="parbase f03v1_pagetools"> 
              <div class="pagestools">
                <ul>
                  <li class="Prnt dividerpagetools" title="Print"><a target="_blank" rel="nofollow" href="<?php echo $page_url;?>?print=yes" class="Prt"><span>Print</span></a></li>
                  <li class="email dividerpagetools" title="Email"> <a rel="nofollow" href="mailto:?subject=<?php echo $title;?>&amp;body=<?php echo $page_url;?>" class="Prt"><span>Email</span></a>
                    <form name="emailfrm" method="post" target="emailpopup" action="http://web.worldbank.org/external/default/main?pagePK=7846481&amp;piPK=7846484&amp;theSitePK=523679">
                      <input type="hidden" name="reftitle" value="China">
                      <input type="hidden" name="reflink" value="http://uxmantras.com/wcd/">
                    </form>
                  </li>
                  <li class="Facebook" title="Facebook"><a href="javascript:facebook();" class="Prt"><span>Facebook</span></a></li>
                  <li class="Tweet" title="Tweet"><a href="javascript:twitter();" class="Prt"><span>Tweet</span></a></li>
                  <li class="shareicon" title="Share">
                    <div class="sharecont">
                      <div class="expand_all"></div>
                      <div class="toggle_container" style="display: none; ">
                        <div class="sharebox">
                          <ul>
                            <li class="Linked In" title="LinkedIn"><a href="javascript:linkedin();">LinkedIn</a></li>
                            <li class="Dig" title="Digg"><a href="javascript:digg();">Digg</a></li>
                            <li class="facebook" title="人人网"><a href="javascript:renren();">人人网</a></li>
                            <li class="twitter" title="新浪微博"><a href="javascript:sina();">新浪微博</a></li>
                          </ul>
                        </div>
                        <div class="sharebox2">
                          <ul>
                            <!--<li class="Google" title="Google buzz"><a href="javascript:googlebuzz();">Google buzz</a></li>-->
                            <li class="Stumble" title="Stumble Upon"><a href="javascript:stumbleUpon();">Stumble Upon</a></li>
                            <li class="Delicious" title="Delicious"><a href="javascript:delicious();">Delicious</a></li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
              <div style="clear:both"></div>
            </div>
          </div>
          <!-- (F03v1) Page Tools Ends --> 

        </div>