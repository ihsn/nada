<?php 
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
//attach a stylesheet so browsers with no support for RSS can still display it
echo '<?xml-stylesheet type="text/xsl" href="'.base_url().'/xslt/rss.xslt"?>';

//from config
$language=$this->config->item("language");
$admin_email=$this->config->item("admin_email");

if ($language=='')
{
	$language='EN';
}

?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:admin="http://webns.net/mvcb/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">

    <channel>    
        <title><?php echo $this->config->item("website_title"); ?></title>
		<link><?php echo site_url(); ?></link>
        <description></description>
        <dc:language><?php echo $language; ?></dc:language>
        <dc:creator><?php echo $admin_email; ?></dc:creator>
        <dc:rights>Copyright <?php echo gmdate("Y", time()); ?></dc:rights>
    
        <?php foreach($records->result() as $entry): ?>
        <item>
          <title><?php echo ($entry->titl); ?></title>
          <link><?php echo site_url('catalog/' . $entry->id) ?></link>
          <guid><?php echo $entry->surveyid ?></guid>
          <description><![CDATA[<?php echo (strip_tags($entry->titlstmt.', ' . $entry->authenty.  ' - ' . $entry->nation)); ?>]]></description>
          <pubDate><?php echo date ('r', $entry->changed);?></pubDate>
        </item>
        <?php endforeach; ?>   
    </channel>
</rss> 