<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform prints all variable groups

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

<xsl:stylesheet version="1.0" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="text" encoding="UTF-8" indent="no"/>
	<xsl:template match="/">
		<xsl:apply-templates select="//ddi:varGrp"/>
	</xsl:template>

<xsl:template match="ddi:varGrp">
	<xsl:variable name="line-break" select="'{line}'"/>
	<xsl:variable name="col-break" select="'{colum}'"/>
	<xsl:value-of select="@ID"/><xsl:value-of select="$col-break"/>
	<xsl:value-of select="@varGrp"/><xsl:value-of select="$col-break"/>
	<xsl:value-of select="ddi:labl"/><xsl:value-of select="$line-break"/>
</xsl:template>
	
</xsl:stylesheet>
