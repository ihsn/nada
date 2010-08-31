<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths tarnsform produces an HTML overview of a DDI 1/2.x XML document

Author: Mehmood Asghar (mah0001@gmail.com), Pascal Heus (pascal.heus@gmail.com)
Version: March 2007
Platform: XSL 1.0

Developed with the financial and technical support of the 
International Household Survey Network
http://www.surveynetwork.org

License: 
	Copyright 2007 Mehmood Asghar, Pascal Heus 

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
	<xsl:param name="wrapper">html</xsl:param>
	
	<!-- HTML styles -->
	<xsl:variable name="table_style">border-collapse:collapse;padding:0px;margin-bottom:20px;width:100%;</xsl:variable>
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
				<div style="{$table_th2_style}">
					<xsl:value-of select="ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl"/>
					(<xsl:value-of select="ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:altTitl"/>)
				</div>
				<!--Overview (this section is never empty) -->
				<table style="{$table_style}">
					<tr>
						<td style="{$table_th1_style}" colspan="2">Overview</td>
					</tr>
					<xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:serStmt" mode="row">
						<xsl:with-param name="caption">Type</xsl:with-param>
					</xsl:apply-templates>
					<tr>
						<td style="{$table_td_style}">Identification</td>
						<td style="{$table_td_style}">
							<xsl:value-of select="@ID"/>
						</td>
					</tr>
					<xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:verStmt" mode="row">
						<xsl:with-param name="caption">Version</xsl:with-param>
					</xsl:apply-templates>
					<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:abstract" mode="row">
						<xsl:with-param name="caption">Abstract</xsl:with-param>
						<xsl:with-param name="cols">1</xsl:with-param>
					</xsl:apply-templates>
					<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind" mode="row">
						<xsl:with-param name="caption">Kind of Data</xsl:with-param>
					</xsl:apply-templates>
					<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:anlyUnit" mode="row">
						<xsl:with-param name="caption">Units of Analysis</xsl:with-param>
					</xsl:apply-templates>
				</table>
				<!--End Overview-->				
				<!--Scope and Coverage-->
				<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:notes | ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:geogCover | ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:universe">
					<table style="{$table_style}">
						<tr>
							<td colspan="2" style="{$table_th1_style}">Scope &amp; Coverage</td>
						</tr>
						<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:notes">
							<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:notes" mode="row">
								<xsl:with-param name="caption">Scope</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
						<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:geogCover">
							<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:geogCover" mode="row">
								<xsl:with-param name="caption">Geographic Coverage</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
						<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:universe">
							<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:universe" mode="row">
								<xsl:with-param name="caption">Universe</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
					</table>
				</xsl:if>
				<!--Producers & Sponsors -->
				<xsl:if test="ddi:stdyDscr//ddi:AuthEnty | ddi:stdyDscr//ddi:prodStmt/ddi:producer | ddi:stdyDscr//ddi:prodStmt/ddi:fundAg | ddi:stdyDscr//ddi:othId">
					<table style="{$table_style}">
						<tr>
							<td colspan="2" style="{$table_th1_style}">Producers &amp; Sponsors</td>
						</tr>
						<xsl:if test="ddi:stdyDscr//ddi:AuthEnty">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:AuthEnty" mode="row">
								<xsl:with-param name="caption">Primary Investigator</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
						<xsl:if test="ddi:stdyDscr//ddi:prodStmt/ddi:producer">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:prodStmt/ddi:producer" mode="row">
								<xsl:with-param name="caption">Other Producer</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>	
						<xsl:if test="ddi:stdyDscr//ddi:prodStmt/ddi:fundAg">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:prodStmt/ddi:fundAg" mode="row">
								<xsl:with-param name="caption">Funding Agency</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
						<xsl:if test="ddi:stdyDscr//ddi:othId">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:othId" mode="row">
								<xsl:with-param name="caption">Acknowledgment(s)</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>
					</table>
				</xsl:if>				
				<!--sampling-->
				<xsl:if test="ddi:stdyDscr//ddi:sampProc | ddi:stdyDscr//ddi:deviat | ddi:stdyDscr//ddi:anlyInfo/ddi:respRate | ddi:stdyDscr//ddi:weight">
					<table style="{$table_style}">
						<tr>
							<td style="{$table_th1_style}">Sampling</td>
						</tr>
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
					</table>
				</xsl:if>
				<!--end sampling -->
				
				<!--Data Collection -->
				<xsl:if test="ddi:stdyDscr//ddi:sumDscr | ddi:stdyDscr//ddi:collMode | ddi:stdyDscr//ddi:collSitu | ddi:stdyDscr//ddi:resInstru | ddi:stdyDscr//ddi:actMin">
					<table style="{$table_style}">
						<tr>
							<td colspan="2" style="{$table_th1_style}">Data Collection</td>
						</tr>
						<!--data collection dates -->
						<xsl:if test="ddi:stdyDscr//ddi:weight">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:sumDscr"/>
						</xsl:if>
						<!-- data collection modes -->
						<xsl:if test="ddi:stdyDscr//ddi:collMode">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:collMode"/>
						</xsl:if>
						<!-- data collection notes -->
						<xsl:if test="ddi:stdyDscr//ddi:collSitu">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:collSitu" mode="row">
									<xsl:with-param name="caption">Data Collection Notes</xsl:with-param>
									<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>		
						</xsl:if>								
						<!--questionnaires -->
						<xsl:if test="ddi:stdyDscr//ddi:resInstru">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:resInstru" mode="row">
								<xsl:with-param name="caption">Questionnaires</xsl:with-param>
								<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>						
						<!--supervision -->
						<xsl:if test="ddi:stdyDscr//ddi:actMin">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:actMin" mode="row">
									<xsl:with-param name="caption">Supervision</xsl:with-param>
									<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>	
						</xsl:if>	
					</table>
				</xsl:if>
				<!-- End Data Collection -->
				
			<!-- Data Processing & Appraisal -->
				<xsl:if test="ddi:stdyDscr//ddi:cleanOps | ddi:stdyDscr//ddi:EstSmpErr | ddi:stdyDscr//ddi:dataAppr">
					<table style="{$table_style}">
						<tr>
							<td style="{$table_th1_style}">Data Processing &amp; Appraisal</td>
						</tr>
						<!-- data editing -->
						<xsl:if test="ddi:stdyDscr//ddi:cleanOps">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:cleanOps" mode="row">
									<xsl:with-param name="caption">Data Editing</xsl:with-param>
									<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>	
						</xsl:if>
																		
						<!-- estimate of sampling error -->
						<xsl:if test="ddi:stdyDscr//ddi:EstSmpErr">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:EstSmpErr" mode="row">
									<xsl:with-param name="caption">Estimates of Sampling Error</xsl:with-param>
									<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>	
						</xsl:if>
						<!--other forms of data appraisal-->
						<xsl:if test="ddi:stdyDscr//ddi:dataAppr">
							<xsl:apply-templates select="ddi:stdyDscr//ddi:dataAppr" mode="row">
									<xsl:with-param name="caption">Other Forms of Data Appraisal</xsl:with-param>
									<xsl:with-param name="cols">1</xsl:with-param>
							</xsl:apply-templates>	
						</xsl:if>
					</table>
				</xsl:if>
		<!-- End Data Processing & Appraisal -->
	</xsl:template>
	
	<!-- docsDscr -->
	<xsl:template match="ddi:docDscr">
		<xsl:apply-templates select="ddi:citation/ddi:titlStmt/ddi:titl"/>
		<br/>
		<xsl:value-of select="ddi:citation/ddi:titlStmt/ddi:IDNo"/>
	</xsl:template>
	<!--stdyDscr/citation/verStmt-->
	<xsl:template match="ddi:stdyDscr/ddi:citation/ddi:verStmt">
		Production	Date: <xsl:value-of select="ddi:version/@date"/>
		<br/>
		<xsl:value-of select="ddi:version"/>
		<xsl:if test="normalize-space(ddi:notes)">
			<br/>
			<br/>
			<u>Notes</u>
			<xsl:call-template name="lf2br">
				<xsl:with-param name="text" select="ddi:notes"/>
			</xsl:call-template>
		</xsl:if>
		<br/>
	</xsl:template>
	<!--ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:anlyUnit-->
	<xsl:template match="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:anlyUnit">
		<xsl:value-of select="."/>
		<br/>
	</xsl:template>
	<!--ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind-->
	<xsl:template match="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind">
		<xsl:value-of select="."/>
		<br/>
	</xsl:template>
	<!--primary investigators - ddi:stdyDscr//ddi:AuthEnty-->
	<xsl:template match="ddi:stdyDscr//ddi:AuthEnty">
		<xsl:value-of select="."/>		
		<xsl:if test="normalize-space(@affiliation)">
				, <xsl:value-of select="@affiliation"/>
		</xsl:if>
		<br/>
	</xsl:template>
	<!-- Funding agencies - stdyDscr//Funding Agencies -->
	<xsl:template match="ddi:stdyDscr//ddi:prodStmt/ddi:fundAg">
		<xsl:value-of select="."/>
		<xsl:if test="normalize-space(@abbr)">
			(<xsl:value-of select="@abbr"/>)
		</xsl:if>
		<xsl:if test="normalize-space(@role) ">
			, <xsl:value-of select="@role"/>
		</xsl:if>
		<br/>
	</xsl:template>
	<!--other producers - ddi:stdyDscr//ddi:prodStmt/ddi:producer-->
	<xsl:template match="ddi:stdyDscr//ddi:prodStmt/ddi:producer">
		<xsl:value-of select="."/>
		<xsl:if test="normalize-space(@abbr)">
				(<xsl:value-of select="@abbr"/>)
			</xsl:if>
		<xsl:if test="normalize-space(@affiliation)">
				, <xsl:value-of select="@affiliation"/>
		</xsl:if>
		<xsl:if test="normalize-space(@role)">
				, <xsl:value-of select="@role"/>
		</xsl:if>
		<br/>
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

	<!-- Data Collection -->
	
	<!-- date collection dates -->
	<xsl:template match="ddi:stdyDscr//ddi:sumDscr">
		<xsl:if test="ddi:collDate/@event | ddi:collDate/@date">
			<tr valign="top">
				<td style="{$table_td_style}">Data Collection Dates</td>
				<td style="{$table_td_style}">
					<xsl:for-each select="ddi:collDate">
							<xsl:value-of select="concat(  translate( substring(@event,1,1),'abcdefghijklmnopqrstuvwxyz','ABCDEFGHIJKLMNOPQRSTUVWXYZ' )          , substring(@event,2,100) )"/> <xsl:text> </xsl:text>
							<xsl:value-of select="@date"/>
						<xsl:if test="@event | @date">							
							<br/>						
						</xsl:if>						
					</xsl:for-each>
				</td>
			</tr>
		</xsl:if>	
	</xsl:template>
	<!-- Data Collection Mode -->
	<xsl:template match="ddi:stdyDscr//ddi:collMode">
		<tr valign="top">
			<td style="{$table_td_style}">Data Collection Mode</td>
			<td style="{$table_td_style}">
				<xsl:value-of select="."/>
			</td>
		</tr>
	</xsl:template>	
	<!-- Questionnaires-->
	<xsl:template match="ddi:stdyDscr//ddi:resInstru">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>
		<!-- Data Collection Notes-->
	<xsl:template match="ddi:stdyDscr//ddi:collSitu">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>

	<!-- Supervision-->
	<xsl:template match="ddi:stdyDscr//ddi:actMin">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>
	<!-- End Data Collection -->

	<!-- Data Processing & Appraisal -->
	<!-- Data Editing -->
	<xsl:template match="ddi:stdyDscr//ddi:cleanOps">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>	
	<!-- Estimate of Sampling Error -->
		<xsl:template match="ddi:stdyDscr//ddi:EstSmpErr">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>	
		<!-- Other Forms of Data Appraisal -->
		<xsl:template match="ddi:stdyDscr//ddi:dataAppr">
				<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>
<!-- End Data Processing & Appraisal -->	


	<!-- 
		utility templates/functions 
	-->
	<!-- this template can be call by call-template of by match -->
	<xsl:template match="*" name="row" mode="row">
		<xsl:param name="caption"/>
		<xsl:param name="text"/>
		<xsl:param name="cols" select="2"/>
		<xsl:variable name="label">
			<xsl:value-of select="$caption"/>
			<xsl:choose>
				<xsl:when test="position()>1"> (<xsl:value-of select="position()"/>)</xsl:when>
				<xsl:otherwise>
					<xsl:if test="name(following-sibling::*[1])=name()"> (1)</xsl:if>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<tr valign="top">
			<xsl:choose>
				<xsl:when test="$cols=2">
					<!-- 2-columns -->
					<td style="{$table_td_style}">
						<xsl:value-of select="$label"/>
					</td>
					<td style="{$table_td_style}">
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
					<td style="{$table_td_style}" colspan="2">
						<span style="{$h5_style}">
							<xsl:value-of select="$label"/>
						</span>
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
				</xsl:otherwise>
			</xsl:choose>
		</tr>
	</xsl:template>

	<!-- Function/template: converts line feed to break line <BR> for html display -->
	<xsl:template name="lf2br">
		<xsl:param name="text"/>
		<xsl:choose>
			<xsl:when test="contains($text,'&#10;')">
				<xsl:value-of select="substring-before($text,'&#10;')"/>
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
	<!-- end utility functions/templates -->
</xsl:stylesheet>
