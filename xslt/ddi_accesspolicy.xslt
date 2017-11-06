<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths tarnsform produces an HTML access policy of a DDI 1/2.x XML document

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
	
    <xsl:include href="gettext.xslt"/>
	<!-- Setting the $wrapper parameter to "div" will surround the content with a <div> instead of a standalone <html> page
	     Use the <div> setiing if this is to be included in an extsining page -->
	<xsl:param name="wrapper">div</xsl:param>
	
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

					<title><xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl"/>
							(<xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:altTitl"/>)</title>

					<body>
						<xsl:apply-templates select="ddi:codeBook"/>
					</body>
				</html>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<!-- ddi:codeBook -->
	<xsl:template match="ddi:codeBook">
	<div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Data Access</xsl:with-param></xsl:call-template></div>
		<!--Accessibility -->
			<xsl:if test="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:contact | ddi:stdyDscr/ddi:citation//ddi:distStmt/ddi:contact | ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:confDec | ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:conditions | ddi:stdyDscr//ddi:citReq">		

					<!-- Access Authority-->
					<xsl:if test="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:contact">
							<div class="xsl-subtitle" ><xsl:call-template name="gettext"><xsl:with-param name="msg">Access Authority</xsl:with-param></xsl:call-template></div>
							<xsl:apply-templates select="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:contact"/>
					</xsl:if>
                    
					<!-- Contacts-->
					<xsl:if test="ddi:stdyDscr/ddi:citation//ddi:distStmt/ddi:contact">
						<div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Contact(s)</xsl:with-param></xsl:call-template></div>
                        <xsl:for-each select="ddi:stdyDscr/ddi:citation//ddi:distStmt/ddi:contact">
                            <xsl:value-of select="."/>
	                        <xsl:if test="normalize-space(@affiliation)">
                                  (<xsl:value-of select="@affiliation"/>) 
                            </xsl:if>
                            <xsl:if test="normalize-space(@email)">, <a href="mailto:{@email}"><xsl:value-of select="@email"/></a></xsl:if>
                            <xsl:choose>
                                    <xsl:when test="substring(@URI,1,4)='www.'">
                                        <xsl:if test="normalize-space(@URI)">, <a href="http://{@URI}"><xsl:value-of select="@URI"/></a></xsl:if>        
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:if test="normalize-space(@URI)">, <a href="{@URI}"><xsl:value-of select="@URI"/></a></xsl:if>					        
                                    </xsl:otherwise>
                            </xsl:choose>
                            
                            <br/>
                        </xsl:for-each>
					</xsl:if>

					<!--Confidentiality-->
					<xsl:if test="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:confDec">
						<xsl:apply-templates select="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:confDec" mode="row">
								<xsl:with-param name="caption">Confidentiality</xsl:with-param>
								<xsl:with-param name="cols">div</xsl:with-param>
						</xsl:apply-templates>
					</xsl:if>
					<!--Access Conditions-->
					<xsl:if test="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:conditions">
						<xsl:apply-templates select="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:conditions" mode="row">
								<xsl:with-param name="caption">Access Conditions</xsl:with-param>
								<xsl:with-param name="cols">div</xsl:with-param>
						</xsl:apply-templates>
					</xsl:if>	
					<!--Citation Requirements-->
					<xsl:if test="ddi:stdyDscr//ddi:citReq">
						<xsl:apply-templates select="ddi:stdyDscr//ddi:citReq" mode="row">
								<xsl:with-param name="caption">Citation Requirements</xsl:with-param>
								<xsl:with-param name="cols">div</xsl:with-param>
						</xsl:apply-templates>
					</xsl:if>

                <xsl:if test="ddi:stdyDscr/ddi:citation/ddi:holdings/@URI">
                    <xsl:choose>
                        <xsl:when test="substring(ddi:stdyDscr/ddi:citation/ddi:holdings/@URI,1,4)='doi:'">
                            <div class="xsl-subtitle">
                                <xsl:call-template name="gettext"><xsl:with-param name="msg">Digital Object Identifier (DOI)</xsl:with-param></xsl:call-template>
                            </div>
                            <div>
                            <a href="http://doi.org/{substring-after(ddi:stdyDscr/ddi:citation/ddi:holdings/@URI,':')}" target="_blank">
                                http://doi.org/<xsl:value-of select="substring-after(ddi:stdyDscr/ddi:citation/ddi:holdings/@URI,':')"/>
                            </a>
                            </div>
                        </xsl:when>
                        <xsl:when test="substring(ddi:stdyDscr/ddi:citation/ddi:holdings/@URI,1,5)='http:'">
                            <div class="xsl-subtitle">
                                <xsl:call-template name="gettext"><xsl:with-param name="msg">Holdings Information</xsl:with-param></xsl:call-template>
                            </div>
                            <div>
                                <xsl:value-of select="ddi:stdyDscr/ddi:citation/ddi:holdings/@URI"/>
                                <a href="{@URI}" target="_blank">
                                    <xsl:value-of select="ddi:stdyDscr/ddi:citation/ddi:holdings/@URI"/>
                                </a>
                            </div>
                            <br/> <br/>
                        </xsl:when>
                        <xsl:otherwise>
                            <div class="xsl-subtitle">
                                <xsl:call-template name="gettext"><xsl:with-param name="msg">Holdings Information</xsl:with-param></xsl:call-template>
                            </div>
                            <div>
                                    <xsl:value-of select="ddi:stdyDscr/ddi:citation/ddi:holdings/@URI"/>
                            </div>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:if>

			</xsl:if>	
			<!--End Accessibility -->
			
				<!--Rights & Disclaimer -->
				<xsl:if test="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:disclaimer | ddi:stdyDscr/ddi:citation/ddi:prodStmt/ddi:copyright">				
						<!-- Disclaimer-->
						<xsl:if test="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:disclaimer">
							<xsl:apply-templates select="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:disclaimer" mode="row">
									<xsl:with-param name="caption">Disclaimer</xsl:with-param>
									<xsl:with-param name="cols">div</xsl:with-param>
							</xsl:apply-templates>
						</xsl:if>	
						<!-- Copyright [non-repeatable]-->
						<xsl:if test="ddi:stdyDscr/ddi:citation/ddi:prodStmt/ddi:copyright">
								<div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Copyright</xsl:with-param></xsl:call-template></div>
									<xsl:value-of select="ddi:stdyDscr/ddi:citation/ddi:prodStmt/ddi:copyright"/>
						</xsl:if>
				</xsl:if>	
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
							<xsl:value-of select="@event"/> 
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

	<!-- Accessibility -->
	
    <!--Access Authority-->
	<xsl:template match="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:contact">
			<xsl:for-each select=".">
					<xsl:value-of select="."/>
					<xsl:if test="normalize-space(@affiliation)">(<xsl:value-of select="@affiliation"/>)</xsl:if>
                    <xsl:if test="normalize-space(@email)">, <a href="mailto:{@email}"><xsl:value-of select="@email"/></a></xsl:if>
                    <xsl:choose>
                    		<xsl:when test="substring(@URI,1,4)='www.'">
			                    <xsl:if test="normalize-space(@URI)">, <a href="http://{@URI}"><xsl:value-of select="@URI"/></a></xsl:if>        
                            </xsl:when>
                            <xsl:otherwise>
			                    <xsl:if test="normalize-space(@URI)">, <a href="{@URI}"><xsl:value-of select="@URI"/></a></xsl:if>					        
                            </xsl:otherwise>
                    </xsl:choose>
					<br/>
			</xsl:for-each>
	</xsl:template>
	
    <!--Confidentiality-->
	<xsl:template match="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:confDec">
		<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>	
	<!--Access Conditions-->
	<xsl:template match="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:conditions">
		<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>
	<!--Citation Requirements-->
	<xsl:template match="ddi:stdyDscr//ddi:citReq">
		<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
	</xsl:template>
<!--End Accessibility-->

<!--Disclaimer-->
	<xsl:template match="ddi:stdyDscr/ddi:dataAccs/ddi:useStmt/ddi:disclaimer">
		<xsl:call-template name="lf2br">
					<xsl:with-param name="text" select="."/>
				</xsl:call-template>
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
	            <xsl:when test="$cols='div'">
					<!-- DIV -->
						<div class="xsl-subtitle"><xsl:value-of select="$label"/></div>
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
				</xsl:when>
				<xsl:when test="$cols=2">
               		<tr valign="top">
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
                    </tr>
				</xsl:when>
				<xsl:otherwise>					
                    <!-- 1-column -->
                    <tr>
					<td  colspan="2">
						<span >
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
                    </tr>
				</xsl:otherwise>
			</xsl:choose>
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