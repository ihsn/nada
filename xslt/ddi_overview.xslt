<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces an HTML overview of a DDI 1/2.x XML document

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
<xsl:stylesheet version="1.0" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" exclude-result-prefixes="ddi">
	<xsl:include href="gettext.xslt"/>
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
				<div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Overview</xsl:with-param></xsl:call-template></div>
				<!--Overview (this section is never empty) -->
				<div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Identification</xsl:with-param></xsl:call-template></div>
				<table cellspacing="0" style="width:100%;">
                
                    <xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:nation">
                    	
                        <xsl:variable name="country_count" select="count(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:nation)"/>
                        
                        <div class="xsl-caption">
                        <xsl:choose>
                            	<xsl:when test="$country_count&gt;1"><xsl:call-template name="gettext"><xsl:with-param name="msg">Countries</xsl:with-param></xsl:call-template></xsl:when>
                                <xsl:otherwise><xsl:call-template name="gettext"><xsl:with-param name="msg">Country</xsl:with-param></xsl:call-template></xsl:otherwise>
                         </xsl:choose>
                        </div>
                        
                    	<xsl:for-each select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:nation">
                        	<xsl:value-of select="normalize-space(.)"/>
                            <xsl:choose>
                            	<xsl:when test="position()&lt;$country_count">, </xsl:when>
                            </xsl:choose>
                        </xsl:for-each>
                    </xsl:if>

                    <xsl:if test="ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl">
                        <xsl:apply-templates select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl" mode="row">
                            <xsl:with-param name="caption">Title</xsl:with-param>
                        </xsl:apply-templates>
                    </xsl:if>
                
	                <xsl:if test="ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:subTitl">
                        <xsl:apply-templates select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:subTitl" mode="row">
                            <xsl:with-param name="caption">Subtitle</xsl:with-param>
                        </xsl:apply-templates>
                    </xsl:if>
                    
                    <xsl:if test="normalize-space(ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:parTitl)">
                        <xsl:apply-templates select="//ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:parTitl" mode="row">
                            <xsl:with-param name="caption">Translated Title</xsl:with-param>
                        </xsl:apply-templates>
                    </xsl:if>

                    <xsl:if test="normalize-space(ddi:stdyDscr/ddi:citation/ddi:serStmt/ddi:serName)">
						<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Study Type</xsl:with-param></xsl:call-template></div>
                        <xsl:if test="contains(//ddi:stdyDscr/ddi:citation/ddi:serStmt//ddi:serName,'[')"><xsl:value-of select="substring-before(//ddi:stdyDscr/ddi:citation/ddi:serStmt//ddi:serName,'[')"/></xsl:if>
                        <xsl:if test="not(contains(//ddi:stdyDscr/ddi:citation/ddi:serStmt//ddi:serName,'['))"><xsl:value-of select="//ddi:stdyDscr/ddi:citation/ddi:serStmt//ddi:serName"/></xsl:if>
                    </xsl:if>
                    
                    <xsl:if test="//ddi:stdyDscr//ddi:serInfo">
                        <xsl:apply-templates select="//ddi:stdyDscr//ddi:serInfo" mode="row">
                            <xsl:with-param name="caption">Series Information</xsl:with-param>
                        </xsl:apply-templates>
                    </xsl:if>
                    
                    <xsl:if test="@ID">
                    <tr>
                    	<td colspan="2"><div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">ID Number</xsl:with-param></xsl:call-template></div><xsl:value-of select="@ID"/></td>
                    </tr>                    
                    </xsl:if>                                        
                    
                    </table>
                    
                    <!-- VERSION -->
             		<xsl:if test="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:version or ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:version/@date or ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:notes">
							<div class="xsl-block">
								<div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Version</xsl:with-param></xsl:call-template></div>
								<xsl:if test="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:version">
									<xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:version" mode="row">
										<xsl:with-param name="caption">Version Description</xsl:with-param>
									</xsl:apply-templates>
								</xsl:if>
							</div>
                            
							<!-- production date -->
							<xsl:if test="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:version/@date">
								<div class="xsl-block">
										<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Production Date</xsl:with-param></xsl:call-template></div>
										<xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:version/@date" mode="row">
											<xsl:with-param name="caption">Production Date</xsl:with-param>
										</xsl:apply-templates>
								</div>
							</xsl:if>          
                            <!-- version notes -->
							<xsl:if test="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:notes">
								<div class="xsl-block">
										<xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:verStmt/ddi:notes" mode="row">
											<xsl:with-param name="caption">Notes</xsl:with-param>
										</xsl:apply-templates>
								</div>
							</xsl:if>          
					</xsl:if>
					
					<!-- OVERVIEW-->
					<xsl:if test="normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:abstract) or
                            normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind) or
                            normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:anlyUnit)">
						<div class="xsl-block">
							<div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Overview</xsl:with-param></xsl:call-template></div>
							<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:abstract">
								<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:abstract" mode="row">
									<xsl:with-param name="caption">Abstract</xsl:with-param>
								</xsl:apply-templates>
							</xsl:if>
							<!-- kind of data -->
							<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind">
								<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind" mode="row">
									<xsl:with-param name="caption">Kind of Data</xsl:with-param>
								</xsl:apply-templates>
							</xsl:if>
							<!-- units of analysis -->
							<xsl:if test="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:anlyUnit">
								<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:anlyUnit" mode="row">
									<xsl:with-param name="caption">Units of Analysis</xsl:with-param>
								</xsl:apply-templates>
							</xsl:if>                    
						</div>
					</xsl:if>	
					
                <!-- SCOPE -->                
                 <xsl:if test="normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:notes)">
						 <div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Scope</xsl:with-param></xsl:call-template></div>
						<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:notes" mode="row">
							<xsl:with-param name="caption">Notes</xsl:with-param>
						</xsl:apply-templates>
                 </xsl:if>    

                <!-- TOPICS -->
                <xsl:if test="ddi:stdyDscr/ddi:stdyInfo//ddi:topcClas">
                	<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Topics</xsl:with-param></xsl:call-template></div>
                    <table class="xsl-table" border="1">
                    	<tr>
                        	<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Topic</xsl:with-param></xsl:call-template></th>
                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Vocabulary</xsl:with-param></xsl:call-template></th>
                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">URI</xsl:with-param></xsl:call-template></th>
                        </tr>
                	<xsl:for-each select="ddi:stdyDscr/ddi:stdyInfo//ddi:topcClas">
						<tr>
                        	<td><xsl:value-of select="."/></td>
                            <td><xsl:value-of select="@vocab"/></td>
                            <td><xsl:value-of select="@vocabURI"/></td>
                        </tr>
                    </xsl:for-each>
                    </table>
                </xsl:if>

                <!-- KEYWORDS -->
                <xsl:if test="ddi:stdyDscr/ddi:stdyInfo//ddi:keyword">
                	<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Keywords</xsl:with-param></xsl:call-template></div>
                    <xsl:variable name="keywords_count" select="count(ddi:stdyDscr/ddi:stdyInfo//ddi:keyword)"/>
                	<xsl:for-each select="ddi:stdyDscr/ddi:stdyInfo//ddi:keyword">
                    	<xsl:value-of select="normalize-space(.)"/><xsl:if test="not(position()=$keywords_count)">, </xsl:if>						
                    </xsl:for-each>
                </xsl:if>				

                <!-- COVERAGE -->                
                <xsl:if test="normalize-space(ddi:stdyDscr/ddi:stdyInfo//ddi:geogCover) or normalize-space(ddi:stdyDscr/ddi:stdyInfo//ddi:universe)
                or normalize-space(ddi:stdyDscr/ddi:stdyInfo//ddi:geogUnit)">
							 <div class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Coverage</xsl:with-param></xsl:call-template></div>
                         <xsl:if test="normalize-space(ddi:stdyDscr/ddi:stdyInfo//ddi:geogCover)">
							<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo//ddi:geogCover" mode="row">
								<xsl:with-param name="caption">Geographic Coverage</xsl:with-param>
							</xsl:apply-templates>
					 </xsl:if>                     
					 
					 <xsl:if test="normalize-space(ddi:stdyDscr/ddi:stdyInfo//ddi:geogUnit)">
							<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo//ddi:geogUnit" mode="row">
								<xsl:with-param name="caption">Geographic Unit</xsl:with-param>
							</xsl:apply-templates>
					 </xsl:if> 

					<!-- universe -->
					 <xsl:if test="normalize-space(ddi:stdyDscr/ddi:stdyInfo//ddi:universe)">
						<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo//ddi:universe" mode="row">
							<xsl:with-param name="caption">Universe</xsl:with-param>
						</xsl:apply-templates>
					 </xsl:if>    
				</xsl:if>
                                		 
                <!--PRODUCERS & SPONSORS --> 
                <xsl:if test="ddi:stdyDscr//ddi:AuthEnty | ddi:stdyDscr//ddi:othId | ddi:stdyDscr//ddi:producer | ddi:stdyDscr//ddi:fundAg">
							<div class="xsl-subtitle" style="margin-bottom:10px;"><xsl:call-template name="gettext"><xsl:with-param name="msg">Producers and Sponsors</xsl:with-param></xsl:call-template></div>
							 <xsl:if test="ddi:stdyDscr//ddi:AuthEnty">
								<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Primary Investigator(s)</xsl:with-param></xsl:call-template></div>
								<div style="margin-bottom:10px;">
                                    <table class="xsl-table">
                                    	<tr>
                                        	<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Affiliation</xsl:with-param></xsl:call-template></th>
										</tr>	                                    
                                    <xsl:for-each select="ddi:stdyDscr//ddi:AuthEnty">
                                        <tr>
                                            <td><xsl:value-of select="."/></td>
                                            <td><xsl:value-of select="@affiliation"/></td>
                                        </tr>
                                    </xsl:for-each>    
                                    </table>                                	                                    
                                </div>
							 </xsl:if>                     
							 <xsl:if test="ddi:stdyDscr//ddi:producer">
								<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Other Producer(s)</xsl:with-param></xsl:call-template></div>
								<div style="margin-bottom:10px;">
									<table class="xsl-table">
                                    	<tr>
                                        	<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Affiliation</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Role</xsl:with-param></xsl:call-template></th>
                                        </tr>
                                    <xsl:for-each select="ddi:stdyDscr//ddi:producer">
                                        <tr>
                                            <td><xsl:value-of select="."/></td>
                                            <td><xsl:value-of select="@affiliation"/></td>
                                            <td><xsl:value-of select="@role"/></td>
                                        </tr>
                                    </xsl:for-each>    
                                    </table>                                
								</div>
							 </xsl:if>    				
							 <xsl:if test="ddi:stdyDscr//ddi:fundAg">
								<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Funding</xsl:with-param></xsl:call-template></div>
								<div style="margin-bottom:10px;">
									<table class="xsl-table">
                                    	<tr>
                                        	<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Abbreviation</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Role</xsl:with-param></xsl:call-template></th>
                                        </tr>
                                    <xsl:for-each select="ddi:stdyDscr//ddi:fundAg">
                                        <tr>
                                            <td><xsl:value-of select="."/></td>
                                            <td><xsl:value-of select="@abbr"/></td>
                                            <td><xsl:value-of select="@role"/></td>
                                        </tr>
                                    </xsl:for-each>    
                                    </table>                                     
								</div>    
							 </xsl:if>    
							 <xsl:if test="ddi:stdyDscr//ddi:othId">
								<div class="xsl-caption"><xsl:call-template name="gettext"><xsl:with-param name="msg">Other Acknowledgements</xsl:with-param></xsl:call-template></div>
								<div style="margin-bottom:10px;">
									<table class="xsl-table">
                                    	<tr>
                                        	<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Affiliation</xsl:with-param></xsl:call-template></th>
                                            <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Role</xsl:with-param></xsl:call-template></th>
                                        </tr>
                                    <xsl:for-each select="ddi:stdyDscr//ddi:othId">
                                        <tr>
                                            <td><xsl:value-of select="."/></td>
                                            <td><xsl:value-of select="@affiliation"/></td>
                                            <td><xsl:value-of select="@role"/></td>
                                        </tr>
                                    </xsl:for-each>    
                                    </table>                                      
								</div>    
							 </xsl:if>
				</xsl:if>			 
                 
                <!--METADATA PRODUCTION -->
                <xsl:if test="normalize-space(ddi:docDscr//ddi:producer)">
                    <xsl:variable name="metadata">
                        <xsl:call-template name="metadata_production"/>
                    </xsl:variable>
                    <xsl:if test="$metadata">
                        <div class="xsl-subtitle" style="margin-bottom:10px;"><xsl:call-template name="gettext"><xsl:with-param name="msg">Metadata Production</xsl:with-param></xsl:call-template></div>
                        <div class="xsl-caption" style="margin-bottom:10px;"><xsl:call-template name="gettext"><xsl:with-param name="msg">Metadata Produced By</xsl:with-param></xsl:call-template></div>
                        <xsl:call-template name="metadata_production"/>
                    </xsl:if>
                </xsl:if>

                <xsl:if test="normalize-space(ddi:docDscr//ddi:prodDate)">
                    <div class="xsl-caption" style="margin-top:10px;"><xsl:call-template name="gettext"><xsl:with-param name="msg">Date of Metadata Production</xsl:with-param></xsl:call-template></div>
                    <xsl:value-of select="normalize-space(ddi:docDscr//ddi:prodDate)"/>
                </xsl:if>
	
				<xsl:if test="normalize-space(ddi:docDscr//ddi:version)">
                    <xsl:apply-templates select="ddi:docDscr//ddi:version" mode="row">
								<xsl:with-param name="caption">DDI Document Version</xsl:with-param>
					</xsl:apply-templates>                    
				</xsl:if>
                
                <xsl:if test="normalize-space(//ddi:codeBook/ddi:docDscr/ddi:citation/ddi:titlStmt/ddi:IDNo)">
                    <div class="xsl-caption" style="margin-top:10px;"><xsl:call-template name="gettext"><xsl:with-param name="msg">DDI Document ID</xsl:with-param></xsl:call-template></div>
                    <xsl:value-of select="normalize-space(//ddi:codeBook/ddi:docDscr/ddi:citation/ddi:titlStmt/ddi:IDNo)"/>                
                </xsl:if>    
	</xsl:template>

	<xsl:template name="metadata_production">
    	<table class="xsl-table">
        	<tr>
            	<th><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></th>
                <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Abbreviation</xsl:with-param></xsl:call-template></th>
                <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Affiliation</xsl:with-param></xsl:call-template></th>
                <th><xsl:call-template name="gettext"><xsl:with-param name="msg">Role</xsl:with-param></xsl:call-template></th>
            </tr>
      	<xsl:for-each select="ddi:docDscr//ddi:producer">
        	<tr>            	
            	<td><xsl:value-of select="normalize-space(.)"/></td>
                <td><xsl:value-of select="normalize-space(@abbr)"/></td>
                <td><xsl:value-of select="normalize-space(@affiliation)"/></td>
                <td><xsl:value-of select="normalize-space(@role)"/></td>
            </tr>
        </xsl:for-each>
        </table>
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

		<!-- time periods -->
		<xsl:if test="ddi:timePrd/@event | ddi:timePrd/@date">
			<tr valign="top">
				<td ><xsl:call-template name="gettext"><xsl:with-param name="msg">Time Periods</xsl:with-param></xsl:call-template></td>
				<td >
					<xsl:for-each select="ddi:timePrd">
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
		
			<xsl:choose>
				<xsl:when test="$cols=2">
					<!-- 2-columns -->
				<tr valign="top">
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
						<!--<td  colspan="2">-->
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
                            <xsl:call-template name="lf2br">
									<xsl:with-param name="text" select="."/>
								</xsl:call-template>
								<!--<xsl:apply-templates select="."/>-->
							</xsl:otherwise>
						</xsl:choose>
					<!--</td>-->
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
				<br/><br/>
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
	