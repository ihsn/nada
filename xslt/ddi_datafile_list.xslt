<?xml version="1.0" encoding="UTF-8"?>
<!--
	list of the files in the documnet
 -->
<xsl:stylesheet version="1.0" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:i18n="http://toolkit.sf.net/i18n/messages" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:outline="http://worldbank.org/toolkit/cdrom/outline" exclude-result-prefixes="ddi outline">
		<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>		
<xsl:template match="/">	
{START}
	<xsl:for-each select="//ddi:codeBook/ddi:fileDscr">
		<xsl:value-of select="@ID"/>=<xsl:value-of select="normalize-space(ddi:fileTxt/ddi:fileName)"/>{BR}
	</xsl:for-each>
</xsl:template>	
	
</xsl:stylesheet>
