<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces an HTML sampling methods and other details of a DDI 1/2.x XML document

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

	<!-- Setting the $wrapper parameter to "div" will surround the content with a <div> instead of a standalone <html> page
	     Use the <div> setiing if this is to be included in an extsining page -->
	<xsl:param name="wrapper">div</xsl:param>
	
	<!-- HTML styles -->
	<xsl:variable name="table_style">border:0px;border-collapse:collapse;padding:0px;margin-bottom:20px;</xsl:variable>
	<xsl:variable name="table_td_style">border:1px solid black;padding:5px;</xsl:variable>
	<xsl:variable name="table_th1_style">background:gainsboro;border:1px solid black;font-size:14px;padding:5px;</xsl:variable>
	<xsl:variable name="table_th2_style">background:silver;border:1px solid black;font-size:18px;padding:5px;margin-bottom:10px;</xsl:variable>
	<xsl:variable name="h5_style">text-decoration: underline;display:block</xsl:variable>

	<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
	
	<xsl:template match="/">
		<xsl:choose>
			<xsl:when test="$wrapper='div'">
				<!-- Wrap in DIV -->
				<div>
					<xsl:apply-templates select="ddi:codeBook"/>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<!-- Wrap in HTML -->
				<html>
					<head>
					<title><xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl"/>
							(<xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:altTitl"/>)</title>
						<style>
							td,th, table, p, body{font-family:arial;font-size:12px;}
							.table1{border-collapse: collapse;padding:0px;margin-bottom:20px;width:100%;}
							.table1 td{border:1px solid black;padding:5px;}
							.th1{background:gainsboro;border:1px solid black;font-size:12px;padding:5px;}
							.th2{background:silver;border:1px solid black;font-size:20px;padding:5px;margin-bottom:10px;}					
							.h5{text-decoration: underline;display:block} 
						</style>
					</head>
					<body>
						<xsl:apply-templates select="ddi:codeBook"/>
					</body>
				</html>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- ddi:codeBook -->
	<xsl:template match="ddi:codeBook">
				<!--<div style="text-align:right;"> <a style="text-decoration:none;" href="#" onclick="javascript:window.print();return false;"><img border="0" alt="" src="../images/print.gif"/> Print</a></div>-->
				<div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Sampling</xsl:with-param></xsl:call-template></div>
				<!--sampling-->
                <xsl:choose>                
				<xsl:when test="ddi:stdyDscr//ddi:sampProc | ddi:stdyDscr//ddi:deviat | ddi:stdyDscr//ddi:anlyInfo/ddi:respRate | ddi:stdyDscr//ddi:weight">
						<xsl:if test="ddi:stdyDscr//ddi:sampProc">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:sampProc" mode="row">
								<xsl:with-param name="caption">Sampling Procedure</xsl:with-param>
								<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
						<xsl:if test="ddi:stdyDscr//ddi:deviat">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:deviat" mode="row">
								<xsl:with-param name="caption">Deviations from Sample Design</xsl:with-param>
								<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
						<xsl:if test="ddi:stdyDscr//ddi:anlyInfo/ddi:respRate">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:anlyInfo/ddi:respRate" mode="row">
								<xsl:with-param name="caption">Response Rate</xsl:with-param>
								<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>						
						<xsl:if test="ddi:stdyDscr//ddi:weight">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:weight" mode="row">
								<xsl:with-param name="caption">Weighting</xsl:with-param>
								<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>												
				</xsl:when>
                <xsl:otherwise><xsl:call-template name="gettext"><xsl:with-param name="msg">No content available</xsl:with-param></xsl:call-template></xsl:otherwise>
                </xsl:choose>
				<!--end sampling -->				
	</xsl:template>
	

	<!-- sampling procedure - stdyDscr//sampProc-->
	<xsl:template match="ddi:stdyDscr//ddi:sampProc">
		<xsl:call-template name="lf2br">
			<xsl:with-param name="text" select="."/>
		</xsl:call-template>
		<br/>
	</xsl:template>
	<!--deviations from sample design - stdyDscr//deviat-->
	<xsl:template match="ddi:deviat">
		<xsl:call-template name="lf2br">
			<xsl:with-param name="text" select="."/>
		</xsl:call-template>
		<br/>
	</xsl:template>
	<!--weighting - ddi:stdyDscr//ddi:weight-->
	<xsl:template match="ddi:weight">
		<xsl:call-template name="lf2br">
			<xsl:with-param name="text" select="."/>
		</xsl:call-template>
		<br/>
	</xsl:template>
	<!--response rate - ddi:stdyDscr//ddi:anlyInfo/ddi:respRate-->
	<xsl:template match="ddi:respRate">
		<xsl:call-template name="lf2br">
			<xsl:with-param name="text" select="."/>
		</xsl:call-template>
		<br/>
	</xsl:template>
	<!-- ddi:abstract -->
	<xsl:template match="ddi:abstract">
		<xsl:call-template name="lf2br">
			<xsl:with-param name="text" select="."/>
		</xsl:call-template>
		<br/>
	</xsl:template>
	<!-- ddi:notes -->
	<xsl:template match="ddi:notes">
		<xsl:call-template name="lf2br">
			<xsl:with-param name="text" select="."/>
		</xsl:call-template>
		<br/>
	</xsl:template>

	<!-- 
		utility templates/functions 
	-->
	<!-- this template can be call by call-template of by match -->
	<xsl:template match="*" name="row" mode="row">
		<xsl:param name="caption"/>
		<xsl:param name="text"/>
		<xsl:param name="cols" select="2"/>
		<xsl:variable name="label">
			<xsl:call-template name="gettext"><xsl:with-param name="msg"><xsl:value-of select="$caption"/></xsl:with-param></xsl:call-template>
			<xsl:choose>
				<xsl:when test="position()>1"> (<xsl:value-of select="position()"/>)</xsl:when>
				<xsl:otherwise>
					<xsl:if test="name(following-sibling::*[1])=name()"> (1)</xsl:if>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
			<xsl:choose>
				<xsl:when test="$cols=2">
					<!-- 2-columns -->
					<td >
						<xsl:value-of select="$label"/>
					</td>
					<td >
						<xsl:choose>
							<xsl:when test="normalize-space($text)">
								<xsl:call-template name="lf2br">
									<xsl:with-param name="text" select="$text"/>
								</xsl:call-template>
							</xsl:when>
							<xsl:otherwise>
								<xsl:apply-templates select="."/>
							</xsl:otherwise>
						</xsl:choose>
					</td>
				</xsl:when>
				<xsl:otherwise>
					<!-- 1-column -->
						<div class="xsl-subtitle">
							<xsl:value-of select="$label"/>
						</div>
						<xsl:choose>
							<xsl:when test="normalize-space($text)">
								<xsl:call-template name="lf2br">
									<xsl:with-param name="text" select="$text"/>
								</xsl:call-template>
							</xsl:when>
							<xsl:otherwise>
								<textarea rows="15" cols="100"><xsl:apply-templates select="."/></textarea>
								
								<textarea rows="15" cols="100">							
								<xsl:call-template name="leftTrim">
									<xsl:with-param name="inParam" select="."/>
								</xsl:call-template></textarea>
								
								
							</xsl:otherwise>
						</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
	</xsl:template>

	<!-- Function/template: converts line feed to break line <BR> for html display -->
	<xsl:template name="lf2br">
		<xsl:param name="text"/>
		<xsl:param name="start"/>
		<xsl:choose>
			<xsl:when test="contains($text,'&#10;')">
				<!--<xsl:value-of select="substring-before($text,'&#10;')"/>
				<br />-->
				
				<xsl:call-template name="leftTrim">
					<xsl:with-param name="inParam" select="substring-before($text,'&#10;')"/>
				</xsl:call-template>				
				<br/>
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

	<xsl:template name="gettext">
		<xsl:param name="msg"/>
		<xsl:value-of select="$msg"/>
	</xsl:template>

<xsl:template name="leftTrim">
<xsl:param name="inParam"/>
<xsl:choose>
<xsl:when test="substring($inParam, 1, 1) = ''">
<xsl:value-of select="$inParam"/>
</xsl:when>
<xsl:when test="normalize-space(substring($inParam, 1, 1)) = ''">
<xsl:call-template name="leftTrim">
<xsl:with-param name="inParam" select="substring($inParam, 2)"/>
</xsl:call-template>
</xsl:when>
<xsl:otherwise>
<xsl:value-of select="$inParam"/>
</xsl:otherwise>
</xsl:choose>
</xsl:template>
	<!-- end utility functions/templates -->
</xsl:stylesheet>
