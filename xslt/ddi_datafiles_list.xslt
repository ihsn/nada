<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces an HTML table for all data files in the DDI with description

Author:	 IHSN
Version: MAY 2011
Platform: XSL 1.0

License: 
	Copyright 2010-2011 The World Bank

    This program is free software; you can redistribute it and/or modify it under the terms of the
    GNU Lesser General Public License as published by the Free Software Foundation; either version
    2.1 of the License, or (at your option) any later version.
  
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Lesser General Public License for more details.
  
    The full text of the license is available at http://www.gnu.org/copyleft/lesser.html
-->
<xsl:stylesheet version="1.0" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:i18n="http://toolkit.sf.net/i18n/messages" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:outline="http://worldbank.org/toolkit/cdrom/outline" exclude-result-prefixes="ddi outline">
		
        <xsl:include href="gettext.xslt"/>
        <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
		<!--file id -->
		<xsl:param name="file" select="'F5'"/>
        <xsl:param name="browser_url" select="''"/>
		
<xsl:template match="/">	
	<div id="variable-list">
    <div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">data_dictionary</xsl:with-param></xsl:call-template></div>
    <table  cellpadding="4" class="ddi-table data-dictionary" width="100%">	
	<tr>
		<th><xsl:call-template name="gettext"><xsl:with-param name="msg">File</xsl:with-param></xsl:call-template></th>
		<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Description</xsl:with-param></xsl:call-template></th>
		<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Cases</xsl:with-param></xsl:call-template></th>
		<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Variables</xsl:with-param></xsl:call-template></th>		
	</tr>	
	<xsl:apply-templates select="//ddi:codeBook/ddi:fileDscr"/>
	</table>
    </div>    
</xsl:template>	
	
	<!-- DATA FILE -->
	<xsl:template match="ddi:fileDscr">
		<xsl:variable name="class">
		<xsl:choose>
			<xsl:when test="position() mod 2 = 0">
				<xsl:value-of select="'row-color2'"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="'row-color1'"/>
			</xsl:otherwise>
		</xsl:choose>
		</xsl:variable>
		
		<xsl:variable name="file" select="@ID"/>
		<xsl:variable name="link"><xsl:value-of select="$browser_url"/>/datafile/<xsl:value-of select="$file"/></xsl:variable>
		<tr class="data-file-row {$class}" data-url="{$link}">
				<td><a href="{$link}"><xsl:value-of select="substring-before(ddi:fileTxt/ddi:fileName,'.NSDstat')"/></a></td>
				<td><xsl:value-of select="ddi:fileTxt/ddi:fileCont"/></td>
				<td><xsl:value-of select="ddi:fileTxt/ddi:dimensns/ddi:caseQnty"/></td>
				<td><xsl:value-of select="count(//ddi:dataDscr/ddi:var[@files=$file])"/></td>
		</tr>	
		<!--
		<table style="width:100%;" class="data-file-bg1" cellpadding="4" >
			<xsl:if test="normalize-space(ddi:fileTxt/ddi:fileCont)">
			<tr valign="top">
				<td style="width:100px"><xsl:call-template name="gettext"><xsl:with-param name="msg">Content</xsl:with-param></xsl:call-template></td>
				<td>
                    <xsl:call-template name="lf2br">
                        <xsl:with-param name="text" select="ddi:fileTxt/ddi:fileCont"/>
                    </xsl:call-template>
				</td>
			</tr>
			</xsl:if>
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Cases</xsl:with-param></xsl:call-template></td>
				<td><xsl:value-of select="ddi:fileTxt/ddi:dimensns/ddi:caseQnty"/></td>
			</tr>
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Variable(s)</xsl:with-param></xsl:call-template></td>
				<td><xsl:value-of select="ddi:fileTxt/ddi:dimensns/ddi:varQnty"/></td>
			</tr>
			<xsl:if test="ddi:fileTxt/ddi:fileStrc/@type">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Structure</xsl:with-param></xsl:call-template>:</td>
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Type</xsl:with-param></xsl:call-template>: <xsl:value-of select="ddi:fileTxt/ddi:fileStrc/@type"/><br/>
						<xsl:call-template name="gettext"><xsl:with-param name="msg">Keys</xsl:with-param></xsl:call-template>: 
						<xsl:call-template name="getVariableById"><xsl:with-param name="str"><xsl:value-of select="ddi:fileTxt/ddi:fileStrc/ddi:recGrp/@keyvar"/></xsl:with-param></xsl:call-template>
				</td>
			</tr>
			</xsl:if>
			<xsl:if test="normalize-space(ddi:fileTxt/ddi:verStmt/ddi:version)">
            <tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Version</xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name="lf2br">
                        <xsl:with-param name="text" select="ddi:fileTxt/ddi:verStmt/ddi:version"/>
                    </xsl:call-template>
				</td>
			</tr>
            </xsl:if>
            <xsl:if test="ddi:fileTxt/ddi:filePlac">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Producer</xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name="lf2br">
                        <xsl:with-param name="text" select="ddi:fileTxt/ddi:filePlac"/>
                    </xsl:call-template>
                </td>
			</tr>
			</xsl:if>
            
            <xsl:if test="normalize-space(ddi:fileTxt/ddi:dataMsng)">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Missing Data</xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name="lf2br">
                        <xsl:with-param name="text" select="ddi:fileTxt/ddi:dataMsng"/>
                    </xsl:call-template>
                </td>
			</tr>
			</xsl:if>
            
            <xsl:if test="normalize-space(ddi:fileTxt/ddi:dataChck)">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Processing Checks</xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name="lf2br">
                        <xsl:with-param name="text" select="ddi:fileTxt/ddi:dataChck"/>
                    </xsl:call-template>
                </td>
			</tr>
			</xsl:if>

            <xsl:if test="normalize-space(ddi:notes)">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Notes</xsl:with-param></xsl:call-template></td>
				<td><xsl:call-template name="lf2br">
                        <xsl:with-param name="text" select="ddi:notes"/>
                    </xsl:call-template>
                </td>
			</tr>
			</xsl:if>
            
		</table>-->
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
	
	
	<xsl:template name="getVariableById">
    <xsl:param name="str"/>
    <xsl:variable name="delimeter" select="' '"/>
        <xsl:choose>
        <xsl:when test="contains($str,$delimeter)">
				<xsl:variable name="varid"><xsl:value-of select="substring-before($str,$delimeter)"/></xsl:variable>
				<xsl:variable name="var" select="//ddi:codeBook/ddi:dataDscr/ddi:var[@ID=$varid]"/>
				<xsl:value-of select="$var/@name"/> (<xsl:value-of select="normalize-space($var/ddi:labl)"/>), 
			<xsl:call-template name="getVariableById"> 
				<xsl:with-param name="str" select="substring-after($str,$delimeter)"/>
				<xsl:with-param name="delimeter" select="$delimeter"/>
			</xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
				<xsl:variable name="var" select="//ddi:codeBook/ddi:dataDscr/ddi:var[@ID=$str]"/>
				<xsl:value-of select="$var/@name"/> (<xsl:value-of select="normalize-space($var/ddi:labl)"/>)
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>


	<!-- Function/template: converts line feed to break line <BR> for html display -->
	<xsl:template name="lf2br">
		<xsl:param name="text"/>
		<xsl:choose>
			<xsl:when test="contains($text,'&#10;')">
				<xsl:variable name="p" select="substring-before($text,'&#10;')"/>
                <xsl:value-of select="$p"/>
                <xsl:if test="normalize-space($p)">
				<br/>
                </xsl:if>
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text">
						<xsl:value-of select="substring-after($text,'&#10;')"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$text"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>	

</xsl:stylesheet>
