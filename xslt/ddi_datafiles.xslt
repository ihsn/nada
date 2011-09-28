<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces HTML table for all the data files available in a DDI 1/2.x XML document

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
<xsl:stylesheet 
		version="1.0" 
        xmlns:xs="http://www.w3.org/2001/XMLSchema" 
        xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
        exclude-result-prefixes="ddi xs">
        <xsl:output method="text" version="1.0" encoding="UTF-8" indent="yes"/>
	
<xsl:template match="/">
	<xsl:apply-templates select="ddi:codeBook/ddi:fileDscr"/>
</xsl:template>	
	
<xsl:template match="ddi:var">
<tr>
<td><xsl:value-of select="@ID"/></td>
<td><xsl:value-of select="@name"/></td>
<td><xsl:value-of select="ddi:labl"/></td>
<td><xsl:value-of select="@intrvl"/></td>
<td><xsl:value-of select="ddi:varFormat/@type"/></td>
<td><xsl:value-of select="ddi:qstn/ddi:preQTxt"/></td>
</tr>
</xsl:template>
		
	<xsl:template match="ddi:fileDscr">
    	<!--
        <li><span><a href="?id=php-survey-id&amp;file={@ID}&amp;section=datafile" onclick="showSection('datafile',1,this.id);return false;" id="{@ID}" class="file" ><xsl:value-of select="substring-before(ddi:fileTxt/ddi:fileName,'.NSDstat')"/></a></span></li> 
        -->
       <xsl:value-of select="@ID"/>{TAB}<xsl:apply-templates select="ddi:fileTxt/ddi:fileName"/>{BR}
        <!--
        <xsl:variable name="file" select="@ID"/><br/>
        <table border="1">
            <xsl:apply-templates select="//ddi:codeBook/ddi:dataDscr/ddi:var[@files=$file]"/>
        </table>
        -->
	</xsl:template>	
	<!-- FileRef -->
	<xsl:template name="fileRef">
		<xsl:param name="fileId"/>
			<xsl:apply-templates select="/ddi:codeBook/ddi:fileDscr[@ID=$fileId]/ddi:fileTxt/ddi:fileName"/>
	</xsl:template>
	<!-- Filename -->
	<xsl:template match="ddi:fileName">
		<!-- this template removes the .NSDstat extension -->
		<xsl:variable name="filename" select="normalize-space(.)"/>
		<xsl:choose>
			<xsl:when test=" contains( $filename , '.NSDstat' )">
				<xsl:value-of select="substring($filename,1,string-length($filename)-8)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$filename"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
</xsl:stylesheet>
