<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces a list of data files available in the DDI 1/2.x XML document

Author:	 IHSN
Version: MAY 2010
Platform: XSL 1.0

License: 
	Copyright 2010 The World Bank

    This program is free software; you can redistribute it and/or modify it under the terms of the
    GNU Lesser General Public License as published by the Free Software Foundation; either version
    2.1 of the License, or (at your option) any later version.
  
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Lesser General Public License for more details.
  
    The full text of the license is available at http://www.gnu.org/copyleft/lesser.html
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
