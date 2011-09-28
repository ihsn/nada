<?xml version="1.0" encoding="UTF-8"?>
<!-- 
 This stylesheet is for rendering RSS as HTML. for browsers such as IE6 or older which do not support RSS 
Original author: sburke@cpan.org (http://interglacial.com/~sburke/stuff/pretty_rss.html)

Updated by: Mehmood Asghar
Updated date: May 23, 2009
-->
<Q:stylesheet version="1.0"
  xmlns:Q = "http://www.w3.org/1999/XSL/Transform"
  xmlns:sy = "http://purl.org/rss/1.0/modules/syndication/"
  xmlns:rss = "http://purl.org/rss/1.0/"
  xmlns:Interglacial = "http://interglacial.com/rss/#Misc1"
  xmlns = "http://www.w3.org/1999/xhtml"
>

<Q:output method="html" />
<Q:template match="/">

<html>
<head>
  <Q:element name="meta">
   <Q:attribute name="content-type">text/html; charset=UTF-8</Q:attribute>
  </Q:element>
<Q:for-each select="/rss/channel/title">
  <title>RSS: <Q:value-of select="."/></title>
</Q:for-each>


<!--
 Make a nice link-alternate thing so that when viewed in Firefox et al,
 the little "RSS" subscribey-icon appears.
-->
<Q:for-each select="/rss/channel/Interglacial:self_url">
  <link rel="alternate" type="application/rss+xml">
    <Q:attribute name="href"><Q:value-of select="."/></Q:attribute>
    <Q:choose>
      <Q:when test="/rss/channel/title">
        <Q:attribute name="title"><Q:value-of select="/rss/channel/title"/></Q:attribute>
      </Q:when>
      <Q:otherwise>
        <Q:attribute name="title">This RSS feed</Q:attribute>
      </Q:otherwise>
    </Q:choose>
  </link>

</Q:for-each>
<style type="text/css">
body{margin:10px;padding:10px;font-family:arial, verdana;font-size:12px;background-color:gainsboro;}
.notice{background-color:white;padding:10px;color:black;font-size:16px;border:1px solid silver;}
.container {background-color:white;border:1px solid silver; padding:10px;font-size:14px;}
dt, dt a{color:blue;font-size:14px;font-weight:bold;}
dd{display:block;list-style:none;color:black;margin:0px;margin-bottom:15px;}
.feeddate{font-size:12px;color:gray;margin-bottom:10px;font-weight:normal}
.feedtitle{color:red;border-bottom:1px solid silver;}
.feedtitle a{text-decoration:none;font-size:24px;}
</style>
</head>

<body>

<div id="cometestme" style="display:none;"
 ><Q:text disable-output-escaping="yes" >&amp;amp;</Q:text></div>

<p class='notice'>NOTE: Your browser does not support RSS feeds. To find more about RSS, please visit: <a href="http://en.wikipedia.org/wiki/RSS_(file_format)">http://en.wikipedia.org/wiki/RSS_(file_format)</a></p>

<div class="container">
<!--
<blockquote class='aboutThisFeed'>
<Q:for-each select="/rss/channel/lastBuildDate"><p><em>
 Last feed update:</em>
 <span id="lastBuildDate"><Q:value-of select="."/></span></p></Q:for-each>

<Q:for-each select="/rss/channel/webMaster">
<p><em>Feed admin:</em> <Q:value-of select="." /></p>
 </Q:for-each>

<Q:for-each select="/rss/channel/language"><p><em>
 Language:</em>
 <Q:value-of select="." /></p></Q:for-each>
<Q:for-each select="/rss/channel/Interglacial:generator_url"
  ><p><em>Feed generator:</em>
  <a accesskey="p" href="{.}">source here</a></p></Q:for-each>
</blockquote>
-->

<div class="feedtitle"><a accesskey="0" href="{/rss/channel/link}">
  <Q:value-of select="/rss/channel/title"/>
</a></div>

<Q:for-each select="/rss/channel/description">
  <Q:if test=". != /rss/channel/title" >
  <!-- no point in printing them both if they're the same -->
    <p class='desc'><Q:value-of select="."/></p>
  </Q:if>
</Q:for-each>
<div class="feedsubtitle">
<Q:value-of select="description"/>
</div>

<Q:if test="/rss/channel/sy:updatePeriod" >
  <p class='updatefreq'>This feed updates

    <Q:variable name="F" select="/rss/channel/sy:updateFrequency" />
    <Q:choose>
      <Q:when test="$F = '' or $F = 1" > once </Q:when>
      <Q:otherwise> <Q:value-of select="$F"/> times </Q:otherwise>
    </Q:choose>

    <Q:value-of select="/rss/channel/sy:updatePeriod"/>.
    Don't poll it any more often than that! 
  </p>
</Q:if>

<Q:if test="/rss/channel/item/enclosure" >
  <p class="notes">This RSS feed is also a
   <a href='http://en.wikipedia.org/wiki/Podcasting'
   >Podcast</a>, which you can read in iTunes, WinAmp, etc.</p>
</Q:if>

<Q:variable name="C" select="count(/rss/channel/item)" />
<p class='leadIn'>
  <Q:choose>
    <Q:when test="$C = 0" >No items </Q:when>
    <Q:when test="$C = 1" >The only item </Q:when>
    <Q:otherwise>The <Q:value-of select="$C" /> items </Q:otherwise>
  </Q:choose>
  currently in this feed:
</p>



<dl class='Items'>

<Q:if test='$C = 0'>  <dt>(Empty)</dt> </Q:if>
<!-- print each feed -->
<Q:for-each select="/rss/channel/item">

<!-- feed item title -->
<dt>
  <Q:for-each select="enclosure">
     <!-- There can be 0, 1, or many enclosures for each item. -->
     <span class="enclosure"><a href="{@url}" type="{@type}"
       title="Click to download a '{@type}' file of about {@length} bytes"
     ><img
       alt  ="Click to download a '{@type}' file of about {@length} bytes"
       src="http://interglacial.com/rss/dl_icon.gif"
       width="35" height="36" border="0"
     /></a></span>
  </Q:for-each>

  <a href="{link}">
    <Q:if test="position() &lt; 10">
      <Q:attribute name='accesskey'><Q:value-of select="position()" /></Q:attribute>
    </Q:if>
    <Q:choose>
      <Q:when test="not(title) or title = ''" ><em>(No title)</em></Q:when>
      <Q:otherwise		><Q:value-of select="title"/></Q:otherwise>
    </Q:choose>
  </a>
<div class="feeddate"> <Q:value-of select="pubDate"/></div>

</dt>

<!-- feed item description -->
<Q:if test="description" >
  <dd name="decodeme"
><Q:value-of  disable-output-escaping="yes" select="description" /></dd>
  <!--
   Alas, many implementations can't, and never will, directly
   support disable-output-escaping.  We try to work around that
   with our JavaScript thing.
  -->
</Q:if>
</Q:for-each>
</dl>
</div>
<!-- The bottom-of-page options: -->
</body>
</html>
		</Q:template>
			</Q:stylesheet>
