<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths tarnsform produces an HTML overview of a DDI 1/2.x XML document

Author: IHSN
Version: Feb 2009
Platform: XSL 1.0

Developed with the financial and technical support of the 
International Household Survey Network
http://www.surveynetwork.org

License: 
	Copyright 2009 Mehmood Asghar, Pascal Heus 

    This program is free software; you can redistribute it and/or modify it under the terms of the
    GNU Lesser General Public License as published by the Free Software Foundation; either version
    2.1 of the License, or (at your option) any later version.
  
    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU Lesser General Public License for more details.
  
    The full text of the license is available at http://www.gnu.org/copyleft/lesser.html
-->
<xsl:stylesheet version="1.0" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" exclude-result-prefixes="ddi">
	<xsl:include href="gettext.xslt"/>
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
				<div class="codebook">
					<xsl:apply-templates select="ddi:codeBook"/>
				</div>
			</xsl:when>
			<xsl:otherwise>
				<!-- Wrap in HTML -->
				<html>
					<head>
					<title><xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl"/>
							(<xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:altTitl"/>)</title>
					</head>
					<body class="codebook">
						<xsl:apply-templates select="ddi:codeBook"/>
					</body>
				</html>
			</xsl:otherwise>
		</xsl:choose>
		<style>.codebook p{margin-top:0px;padding-top:0px;}
		</style>
	</xsl:template>
	<!-- ddi:codeBook -->
	<xsl:template match="ddi:codeBook">
				<!--<div style="text-align:right;"> <a style="text-decoration:none;" href="#" onclick="javascript:window.print();return false;"><img border="0" alt="" src="../images/print.gif"/> Print</a></div>-->
				<div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Impact Evaluation Overview</xsl:with-param></xsl:call-template></div>
				<!--Overview (this section is never empty) -->
				<div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Impact Evaluation Identification</xsl:with-param></xsl:call-template></div>
                
                    <xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:titl">
                    	<div class="xsl-caption">Impact Evaluation</div>
                        <div>
                        <xsl:variable name="ie">
	                    	<xsl:value-of select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:titl"/>
                        </xsl:variable>
                        <xsl:choose>
										<!--link to project id-->
										<xsl:when test="normalize-space(//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:subTitl)">
												<xsl:value-of select="$ie"/> (<a href="{normalize-space(//ddi:codeBook/ddi:docDscr/ddi:citation/ddi:distStmt/ddi:depositr)}"><xsl:value-of select="normalize-space(//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:subTitl)"/></a>)												
										</xsl:when>
										<xsl:otherwise>
												<xsl:value-of select="concat($ie,'(',normalize-space(//ddi:codeBook/ddi:docDscr/ddi:docSrc//ddi:subTitl),')')"/>
										</xsl:otherwise>
						</xsl:choose>                        
                        </div>
                    </xsl:if>

                    <xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:altTitl">
                        <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:altTitl" mode="row">
                            <xsl:with-param name="caption">Thematic Area(s)</xsl:with-param>
                        </xsl:apply-templates>
                    </xsl:if>
                
					<!-- Lead Evaluators -->
	                <xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:distStmt/ddi:contact">	                
	                <div class="xsl-caption">Lead Evaluator(s)</div>
						<xsl:for-each select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:distStmt/ddi:contact">
						<div>
						<xsl:choose>
							<xsl:when test="@URI">
										<a href="{@URI}">
										<xsl:value-of select="."/>
										</a>
							</xsl:when>
							<xsl:otherwise>
									<xsl:value-of select="."/>
							</xsl:otherwise>	
						</xsl:choose>
						<xsl:if test="@affiliation">, <xsl:value-of select="@affiliation"/></xsl:if>
						<xsl:if test="@email">, Email: <a href="mailto:{@email}"><xsl:value-of select="@email"/></a></xsl:if>
						</div>
						</xsl:for-each>
                    </xsl:if>
                   
                   <!-- Evaluation team members -->
					<xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:docSrc/ddi:distStmt/ddi:contact">
	                <div class="xsl-caption">Evaluation Team Member(s)</div>
						<xsl:for-each select="//ddi:codeBook/ddi:docDscr/ddi:docSrc/ddi:distStmt/ddi:contact">
						<div>
						<xsl:choose>
							<xsl:when test="@URI">
										<a href="{@URI}">
										<xsl:value-of select="."/>
										</a>
							</xsl:when>
							<xsl:otherwise>
									<xsl:value-of select="."/>
							</xsl:otherwise>	
						</xsl:choose>
						<xsl:if test="@affiliation">, <xsl:value-of select="@affiliation"/></xsl:if>
						<xsl:if test="@email">, Email: <a href="mailto:{@email}"><xsl:value-of select="@email"/></a></xsl:if>
						</div>
						</xsl:for-each>
                    </xsl:if>
                    
					<!--FUNDING SOURCES-->
                    <xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:citation/ddi:prodStmt/ddi:fundAg">
						<div class="xsl-caption">Impact Evaluation Funding Sources</div>
						<xsl:for-each select="//ddi:codeBook/ddi:docDscr/ddi:citation/ddi:prodStmt/ddi:fundAg">
								<div><xsl:value-of select="."/></div>
						</xsl:for-each>							
                    </xsl:if>
					
					<!--IE OVERVIEW -->		
                    <xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:guide">
						<div class="xsl-subtitle">Impact Evaluation Overview</div>
						<div class="xsl-caption">Impact Evaluation Description</div>
						<xsl:call-template name="lf2br">
						<xsl:with-param name="text" select="//ddi:codeBook/ddi:docDscr/ddi:guide"/>
					</xsl:call-template>
                    </xsl:if>
                    
	</xsl:template>

	<xsl:template name="metadata_production">
      	<xsl:for-each select="ddi:docDscr//ddi:producer">
        	<xsl:if test="normalize-space(@abbr)">
        		<xsl:value-of select="normalize-space(@abbr)"/>
            </xsl:if>	
            <div>
            	<xsl:value-of select="normalize-space(.)"/> - 
            	<xsl:value-of select="normalize-space(@affiliation)"/>
            </div>
            <xsl:if test="normalize-space(@role)">
            	(<xsl:value-of select="normalize-space(@role)"/>)</xsl:if>
        </xsl:for-each>
    </xsl:template>
	
	<!-- docsDscr -->
	<xsl:template match="ddi:docDscr">
		<xsl:apply-templates select="ddi:citation/ddi:titlStmt/ddi:titl"/>
		<br/>
		<xsl:value-of select="ddi:citation/ddi:titlStmt/ddi:IDNo"/>
	</xsl:template>
	<!--stdyDscr/citation/verStmt-->
	<xsl:template match="ddi:stdyDscr/ddi:citation/ddi:verStmt">
		<xsl:call-template name="gettext"><xsl:with-param name="msg">Production Date</xsl:with-param></xsl:call-template>: <xsl:value-of select="ddi:version/@date"/>
		<br/>
		<xsl:value-of select="ddi:version"/>
		<xsl:if test="normalize-space(ddi:notes)">
			<br/>
			<br/>
			<u><xsl:call-template name="gettext"><xsl:with-param name="msg">Notes</xsl:with-param></xsl:call-template></u>
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
				<td ><xsl:call-template name="gettext"><xsl:with-param name="msg">Data Collection Dates</xsl:with-param></xsl:call-template></td>
				<td >
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
			<td ><xsl:call-template name="gettext"><xsl:with-param name="msg">Data Collection Mode</xsl:with-param></xsl:call-template></td>
			<td >
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

		<!-- Other aknowledgements -->
		<xsl:template match="ddi:stdyDscr//ddi:othId">
		        <xsl:value-of select="concat(.,'- ')"/>
                <xsl:if test="@affiliation">
                <xsl:value-of select="normalize-space(@affiliation)"/>,
                </xsl:if>
                <xsl:if test="@role">                
	                <xsl:value-of select="normalize-space(@role)"/>
                </xsl:if>
                <br/>
		</xsl:template>

<!-- End Data Processing & Appraisal -->	


	<!-- 
		utility templates/functions 
	-->
	<!-- this template can be call by call-template of by match -->
	<xsl:template match="*" name="row" mode="row">
		<xsl:param name="caption"/>
		<xsl:param name="text"/>
		<xsl:param name="cols" select="1"/>
		<xsl:variable name="label">
			<xsl:call-template name="gettext"><xsl:with-param name="msg"><xsl:value-of select="$caption"/></xsl:with-param></xsl:call-template>
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
					<td  colspan="2">
						<div class="xsl-caption">
							<xsl:value-of select="$label"/>
						</div>
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
				<xsl:variable name="p" select="substring-before($text,'&#10;')"/>
                <xsl:if test="normalize-space($p)">
                                <p><xsl:value-of select="$p"/></p>
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
	<!-- end utility functions/templates -->
</xsl:stylesheet>
	