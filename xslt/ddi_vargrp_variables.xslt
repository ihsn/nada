<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces a list of variables for the given vargrp id

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
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    
    <xsl:include href="gettext.xslt"/>
	<!--vargrp id -->
	<xsl:param name="VarGroupID" select="'VG1'"/>
    <xsl:param name="browser_url" select="''"/>
    
	<xsl:template match="/">
		<div id="variable-list">
			<!-- find the variable group -->
			<xsl:variable name="variable" select="//ddi:codeBook/ddi:dataDscr/ddi:varGrp[@ID=$VarGroupID]"/>
			<h1 class="xsl-title"><xsl:value-of select="$variable/ddi:labl"/></h1>						
			<xsl:choose>
					<xsl:when test="normalize-space($variable/@var)=''">
						<xsl:call-template name="gettext"><xsl:with-param name="msg">No variables were found.</xsl:with-param></xsl:call-template>
					</xsl:when>
					<xsl:otherwise>
						<h2 class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Variables</xsl:with-param></xsl:call-template></h2>
						<table border="1" style="border-collapse:collapse;width:100%;border:1px solid silver;" cellpadding="2" class="table-variable-list">
							<tr class="var-th">
								<td><xsl:call-template name="gettext"><xsl:with-param name="msg">ID</xsl:with-param></xsl:call-template></td>
								<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></td>
								<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Label</xsl:with-param></xsl:call-template></td>
								<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Type</xsl:with-param></xsl:call-template></td>
								<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Format</xsl:with-param></xsl:call-template></td>
								<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Question</xsl:with-param></xsl:call-template></td>
							</tr>
								<!-- print all var elements found -->
								<xsl:call-template name="PrintVariable" >
									<xsl:with-param name="str"><xsl:value-of select="$variable/@var"/></xsl:with-param>
									<xsl:with-param name="delimeter" select="' '"></xsl:with-param>
								</xsl:call-template>
						</table>
					</xsl:otherwise>	
			</xsl:choose>
		</div>
	</xsl:template>
	<xsl:template match="ddi:var">
		<xsl:variable name="class">
			<xsl:choose>
				<xsl:when test="position() mod 2 = 0">
					<xsl:value-of select="'row-color1'"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="'row-color2'"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="id" select="@ID"/>
        <xsl:variable name="link"><xsl:value-of select="$browser_url"/>/variable/<xsl:value-of select="$id"/></xsl:variable>

		<tr valign="top" class="{$class}" style="cursor:pointer" onclick="showVariable(this)" id="{$id}" title="Click to view variable info">
			<td>
				<a class="var-link" href="{$link}">
					<xsl:value-of select="@ID"/>
				</a>
			</td>
			<td>
				<a class="var-link"  href="{$link}">
					<xsl:value-of select="@name"/>
				</a>
			</td>
			<td>
				<xsl:value-of select="ddi:labl"/>
			</td>
			<td>
				<xsl:value-of select="@intrvl"/>
			</td>
			<td>
				<xsl:value-of select="ddi:varFormat/@type"/>
			</td>
			<td>
				<xsl:value-of select="ddi:qstn/ddi:qstnLit"/>
			</td>
		</tr>
	</xsl:template>
	
	
	<xsl:template name="tokenizestring">
		<xsl:param name="str"/>
		<xsl:param name="delimeter"/>
		<xsl:choose>
			<xsl:when test="contains($str,$delimeter)">
				<token>
					<xsl:value-of select="substring-before($str,$delimeter)"/>
				</token>
				<xsl:call-template name="tokenizestring">
					<xsl:with-param name="str" select="substring-after($str,$delimeter)"/>
					<xsl:with-param name="delimeter" select="$delimeter"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<token>
					<xsl:value-of select="$str"/>
				</token>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

<!-- pass in a space seperated list of variables IDs to print them one by one-->
	<xsl:template name="PrintVariable">
		<xsl:param name="str"/>
		<xsl:param name="delimeter"/>
		<xsl:choose>
			<xsl:when test="contains($str,$delimeter)">
				<xsl:variable name="variableid">
					<xsl:value-of select="substring-before($str,$delimeter)"/>
				</xsl:variable>
				<xsl:apply-templates select="//ddi:codeBook//ddi:var[@ID=$variableid]"	/>
				<xsl:call-template name="PrintVariable">
					<xsl:with-param name="str" select="substring-after($str,$delimeter)"/>
					<xsl:with-param name="delimeter" select="$delimeter"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:variable name="variableid">
					<xsl:value-of select="$str"/>
				</xsl:variable>
				<xsl:apply-templates select="//ddi:codeBook//ddi:var[@ID=$variableid]"	/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>
