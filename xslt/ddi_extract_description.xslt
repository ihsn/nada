<?xml version="1.0" encoding="UTF-8"?>
<!--
extract study desc from ddi

Author:	 Mehmood Asghar (IHSN)
Version: JULY 2011
Platform: XSL 1.0

License: 
	Copyright 2011 The World Bank

    This program is free software; you can redistribute it and/or modify it under the terms of the
    GNU Lesser General Public License as published by the Free Software Foundation; either version
    2.1 of the License, or (at your option) any later version.
  
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Lesser General Public License for more details.
  
    The full text of the license is available at http://www.gnu.org/copyleft/lesser.html
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/XSL/Format" xmlns:ddi="http://www.icpsr.umich.edu/DDI" exclude-result-prefixes="ddi" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" />	

<!--	  <xsl:template match="/">
<xsl:element name="codeBook" namespace="http://www.w3.org/2001/XMLSchema-instance" >
		<xsl:attribute name="ID"><xsl:value-of select="ddi:codeBook/@ID"/></xsl:attribute>
		<xsl:attribute name="version"><xsl:value-of select="ddi:codeBook/@version"/></xsl:attribute>
		<xsl:copy-of select="//ddi:docDscr"/>	
		<xsl:copy-of select="//ddi:stdyDscr"/>			
</xsl:element>
</xsl:template> -->

<xsl:template match="@*|node()">
	  <xsl:copy>
		<xsl:apply-templates select="@*|node()"/>
	  </xsl:copy>
</xsl:template>

<!-- sections to exclude -->
<xsl:template match="ddi:fileDscr"/>
<xsl:template match="ddi:dataDscr"/>


</xsl:stylesheet>