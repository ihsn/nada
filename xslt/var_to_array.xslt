<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform converts variables to array

NOTE: This is no longer used by NADA

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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ddi="http://www.icpsr.umich.edu/DDI" >
	<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" />
	<xsl:param name="line-seperator" select="'{LN-BR}'"/>
	<xsl:param name="column-seperator" select="'{CL-BR}'"/>

<xsl:template match="ddi:codeBook">
		<xsl:apply-templates select="//ddi:var"/>
</xsl:template>

	<xsl:template match="ddi:var">
		<xsl:value-of select="normalize-space(@ID)"/><xsl:value-of select="$column-seperator"/>
		<xsl:value-of select="normalize-space(@name)"/><xsl:value-of select="$column-seperator"/>
		<xsl:value-of select="normalize-space(ddi:labl)" /><xsl:value-of select="$column-seperator"/>
		<xsl:value-of select="normalize-space(ddi:qstn[*/@* | *])" disable-output-escaping="yes" /><xsl:value-of select="$column-seperator"/>
		<!-- categories-->	
		<xsl:apply-templates select="ddi:catgry"/>
        
        <!-- <xsl:variable name="categories">
		<xsl:call-template name="variable-categories">
				<xsl:with-param name="varid"><xsl:value-of select="@ID"/></xsl:with-param>
		</xsl:call-template>
        </xsl:variable>
		<xsl:value-of select="$categories"/>-->
        <xsl:value-of select="$line-seperator"/>
	</xsl:template>

	<xsl:template name="get-variable">

	</xsl:template>

	<!-- returns variable categories -->
	<xsl:template name="variable-categories">
			<xsl:param name="varid"/>
				<xsl:for-each select="//ddi:var[@ID=$varid]/ddi:catgry[position() &lt;= 5]">
					<br/><xsl:value-of select="position()"/><br/>
					<xsl:value-of select="normalize-space(ddi:labl)"/><xsl:text> </xsl:text>
				</xsl:for-each>
	</xsl:template>

	<xsl:template match="ddi:catgry">
			<xsl:value-of select="normalize-space(ddi:labl)"/><xsl:text> </xsl:text>
	</xsl:template>

</xsl:stylesheet>