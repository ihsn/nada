<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces an HTML for a single variable of a DDI 1/2.x XML document

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
	<xsl:include href="OpenInReader.inc.xslt"/>

	<xsl:param name="survey.base" select="'s'"/>	
	<xsl:param name="outline.base" select="'y'"/>
	<xsl:param name="search_vid" select="'V37'"/>
	
	
<xsl:template match="/"><style>
.variable td {border:1px solid gainsboro;}
.variable .bg1{background-color:#EEEEEE;}
.box {border:1px solid gainsboro;margin-top:10px;}
</style>

<div style="font-family:arial;font-size:12px;">
	<!--	<div style="text-align:right;margin-bottom:10px;"> <a style="text-decoration:none;" href="variable.php?id=362&section=variable&vid=V50" onclick="javascript:window.print();return false;"><img border="0" alt="" src="../images/print.gif"/> Print</a> 
<a style="margin-left:5px;text-decoration:none" href="#" onclick="close_var_window();return false;"><img src="../images/close.gif" border="0"/>Close</a></div>-->
	<xsl:apply-templates select="/ddi:codeBook/ddi:dataDscr/ddi:var[@ID=$search_vid]"/>
</div>
</xsl:template>	
	
	<!-- 4.3 VARIABLE -->
	<xsl:template match="ddi:var">
		<!-- OVERVIEW -->
		<div class="content">
			<table border="1" cellpadding="5" cellspacing="0" style="font-size:12px; font-weight:bold;border:1px solid gainsboro;width:98%; border-collapse:collapse" class="variable" >
					<tr >
						<td class="bg1" align="left">Country:</td>
						<td style="font-weight:normal;"><xsl:value-of select="//ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:nation"/></td>
					</tr>
					<tr>
						<td class="bg1" align="left">Study Title:</td>
						<td style="font-weight:normal;"><xsl:value-of select="//ddi:docDscr/ddi:citation/ddi:titlStmt/ddi:titl"/></td>
					</tr>
					<tr>
						<td class="bg1"  align="left">Variable Name:</td>
						<td style="font-weight:normal;"><xsl:value-of select="@name"/></td>
					</tr>
					<tr>
						<td class="bg1"  align="left">Variable Label:</td>
						<td style="font-weight:normal;"><xsl:value-of select="ddi:labl"/></td>
					</tr>
               		<xsl:if test="@files">
                            <tr>
                                <td class="bg1"  align="left">File:</td>
                                <td style="font-weight:normal;">
	                                <xsl:call-template name="fileRef">
    		                            <xsl:with-param name="fileId" select="@files"/>
            		                </xsl:call-template>
                                </td>
                            </tr>                                                
					</xsl:if>
			</table>
		</div>
        <div style="margin-top:10px;border:1px solid gainsboro;">
            <div style="background:#EEEEEE;padding:5px;">
                        <b><xsl:value-of select="'Overview'"/> </b><br/>
            </div>    
		<table cellpadding="5" cellspacing="0" border="0" style="font-family:arial;font-size:12px;">
			<tr>
				<!-- BASIC CHARACTERISTICS -->
				<td valign="top">
					<div class="content" style="margin-left:20px;">
						<xsl:if test="@intrvl">
							<xsl:value-of select="'Type'"/>: 
							<xsl:choose>
								<xsl:when test="@intrvl='discrete'"><xsl:value-of select="'Discrete'"/></xsl:when>
								<xsl:when test="@intrvl='contin'"><xsl:value-of select="'Continuous'"/></xsl:when>
								<xsl:otherwise><xsl:value-of select="'Undetermined'"/></xsl:otherwise>
							</xsl:choose>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:varFormat/@type">
							<xsl:value-of select="'Format'"/>: <xsl:value-of select="ddi:varFormat/@type"/>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:location/@width">
							<xsl:value-of select="'Width'"/>: <xsl:value-of select="ddi:location/@width"/>
							<br/>
						</xsl:if>
						<xsl:if test="@dcml">
							<xsl:value-of select="'Decimals'"/>: <xsl:value-of select="@dcml"/>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:valrng">
							<xsl:value-of select="'Range'"/>:
							<xsl:for-each select="ddi:valrng">
								<xsl:if test="position()>1">, </xsl:if>
								<!-- range -->
								<xsl:for-each select="ddi:range">
									<xsl:if test="position()>1">, </xsl:if>
									<xsl:value-of select="@min"/>-<xsl:value-of select="@max"/>
								</xsl:for-each>
								<!-- item -->
								<xsl:for-each select="ddi:item">
									<xsl:if test="position()>1">, </xsl:if>
									<xsl:value-of select="@VALUE"/>
								</xsl:for-each>
							</xsl:for-each>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:invalrng">
							<xsl:value-of select="'Invalid'"/>:
							<xsl:for-each select="ddi:invalrng">
								<xsl:if test="position()>1">, </xsl:if>
								<!-- range -->
								<xsl:for-each select="ddi:range">
									<xsl:if test="position()>1">, </xsl:if>
									<xsl:value-of select="@min"/>-<xsl:value-of select="@max"/>
								</xsl:for-each>
								<!-- item -->
								<xsl:for-each select="ddi:item">
									<xsl:if test="position()>1">, </xsl:if>
									<xsl:value-of select="@VALUE"/>
								</xsl:for-each>
							</xsl:for-each>
							<br/>
						</xsl:if>
					</div>
				</td>
				<td valign="top">
					<!-- SUMMARY STATISTICS -->
					<xsl:if test="count(./ddi:sumStat)>0">
						<div class="content" style="margin-left:20px;">
							<xsl:apply-templates select="ddi:sumStat"/>
						</div>
					</xsl:if>
				</td>
			</tr>
		</table>
        </div>
		<!-- FILE -->
		<!--<xsl:if test="@files">
			<div class="box" style="padding:5px;">
				<b><xsl:value-of select="'File'"/>: </b>
				<xsl:call-template name="fileRef">
					<xsl:with-param name="fileId" select="@files"/>
				</xsl:call-template>
				<br/>
			</div>
		</xsl:if>-->
		<!-- DESCRIPTION -->
		<!-- Text -->
		<xsl:apply-templates select="ddi:txt"/>
            
		<!-- Universe -->
		<xsl:apply-templates select="ddi:universe"/>

		<!-- Response Unit -->
		<xsl:apply-templates select="ddi:respUnit"/>

    	<!-- CATEGORIES -->
		<xsl:if test="count(./ddi:catgry)>0">
            <div style="margin-top:10px;border:1px solid gainsboro;">
	            <div style="padding:5px;background-color:#EEEEEE;"><b><xsl:value-of select="'Categories'"/></b></div>
				<div style="margin-left:20px;">
					<table class="varCatgry" style="font-family:arial;font-size:12px;width:90%">
						<tbody>
							<tr>
								<th class="varCatgry" align="left"><xsl:value-of select="'Value'"/></th>
								<th class="varCatgry" align="left"><xsl:value-of select="'Category'"/></th>
								<xsl:if test="count(./ddi:catgry/ddi:catStat[@type='freq'])>0">
									<th class="varCatgry" align="left"><xsl:value-of select="'Cases'"/></th>
								</xsl:if>
								<xsl:if test="count(./ddi:catgry/ddi:catStat[@wgtd='wgtd'])>0">
									<th class="varCatgry" align="left"><xsl:value-of select="'Weighted'"/></th>
								</xsl:if>
								<xsl:if test="count(./ddi:catgry/ddi:catStat[@type='freq'])>0">
									<th class="varCatgry" align="left"/>
								</xsl:if>
							</tr>
							<xsl:apply-templates select="ddi:catgry"/>
						</tbody>
					</table>
				</div>
       			<div style="color:red;margin:5px;padding:5px;background:#FFFFCC;">Warning: these figures indicate the number of cases found in the data file. They cannot be interpreted as summary statistics of the population of interest.</div>
            </div>
		</xsl:if>        

		<xsl:if test="normalize-space(ddi:qstn)">            
             <div style="margin-top:10px;border:1px solid gainsboro;">
        	<div style="padding:5px;background:#EEEEEE;font-weight:bold;">Questions and instructions</div>
	            <!-- QUESTIONS -->
                <div style="padding:5px;"><xsl:apply-templates select="ddi:qstn"/></div>
			</div>		
		</xsl:if>        <!-- IMPUTATION / DERIVATION-->
		<!-- Imputation -->
		<xsl:apply-templates select="ddi:imputation"/>
		<!-- Response Unit -->
		<xsl:apply-templates select="ddi:codInstr"/>
		<!-- OTHERS -->
		<!-- Security -->
		<xsl:apply-templates select="ddi:security"/>
		<!-- Concepts -->
		<xsl:if test="count(ddi:concept)>0">
        
			<xsl:variable name="concepts">
				<xsl:apply-templates select="ddi:concept"/>
			</xsl:variable>
			<xsl:call-template name="var_info">
				<xsl:with-param name="title"><xsl:value-of select="'Concepts'"/></xsl:with-param>
				<xsl:with-param name="text">
					<xsl:value-of select="$concepts"/>
				</xsl:with-param>
			</xsl:call-template>
		</xsl:if>
		<!-- Notes -->
		<xsl:apply-templates select="ddi:notes"/>

		<!-- OPEN NESSTAR -->
		<xsl:if test="position()=1">
			<!-- Add open data reader box -->
			<xsl:call-template name="open_in_reader"/>
		</xsl:if>	
	</xsl:template>
	<!--
	    DDI ELEMENT TEMPLATES 
	-->
	<!-- 4.2.3 imputation -->
	<xsl:template match="ddi:imputation">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Imputation'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.4 Security -->
	<xsl:template match="ddi:security">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Security'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.6 response unit -->
	<xsl:template match="ddi:respUnit">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Source_of_information'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8 question  -->
	<xsl:template match="ddi:qstn">
		<xsl:apply-templates select="ddi:preQTxt"/>
		<xsl:apply-templates select="ddi:qstnLit"/>
		<xsl:apply-templates select="ddi:postQTxt"/>
		<xsl:apply-templates select="ddi:ivuInstr"/>
	</xsl:template>
	<!-- 4.3.8.1 pre-question  -->
	<xsl:template match="ddi:preQTxt">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Pre-question'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8.2 literal question  -->
	<xsl:template match="ddi:qstnLit">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Literal_question'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8.3 Post-question  -->
	<xsl:template match="ddi:postQTxt">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Post-question'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8.6 Interviewer Instructions -->
	<xsl:template match="ddi:ivuInstr">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Interviewer_instructions'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.12 variable universe -->
	<xsl:template match="ddi:universe">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Universe'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.14 summary statistics -->
	<xsl:template match="ddi:sumStat">
		<!-- frequencies -->
		<xsl:if test="not(@wgtd='wgtd')">
			<!-- unweighted -->
			<xsl:variable name="type" select="@type"/>
			<xsl:choose>
				<xsl:when test="@type='invd'"><xsl:value-of select="'Invalid'"/>: </xsl:when>
				<xsl:when test="@type='max'"><xsl:value-of select="'Maximum'"/>: </xsl:when>
				<xsl:when test="@type='mean'"><xsl:value-of select="'Mean'"/>: </xsl:when>
				<xsl:when test="@type='min'"><xsl:value-of select="'Minimum'"/>: </xsl:when>
				<xsl:when test="@type='stdev'"><xsl:value-of select="'Standard deviation'"/>: </xsl:when>
				<xsl:when test="@type='vald'"><xsl:value-of select="'Valid cases'"/>: </xsl:when>
			</xsl:choose>
			<xsl:value-of select="format-number(.,'0.#')"/>
			<!-- check if weighted value exists -->
			<xsl:if test="../ddi:sumStat[@type=$type and @wgtd='wgtd']">
				<!-- dislay weighted value here -->
				<xsl:text> (</xsl:text>
				<xsl:value-of select="format-number(../ddi:sumStat[@type=$type and @wgtd='wgtd'],'0.#')"/>
				<xsl:text>)</xsl:text>
			</xsl:if>
			<br/>
		</xsl:if>
		<!-- weighted (this assumes unweighted freq exists as previous element) -->
		<xsl:if test="@wgtd='wgtd'">
			<!-- weighted -->
			<!-- ignore, it's been displayed with the unweighted value -->
		</xsl:if>
	</xsl:template>
	<!-- 4.3.15 variable text -->
	<xsl:template match="ddi:txt">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Description'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.18 variable category -->
	<xsl:template match="ddi:catgry">
		<tr align="left">
			<!-- Value -->
			<td class="varCatgry">
				<xsl:value-of select="ddi:catValu"/>
			</td>
			<!-- Label -->
			<td class="varCatgry">
				<xsl:value-of select="ddi:labl"/>
			</td>
			<!-- Frequency -->
			<xsl:if test="count(ddi:catStat[@type='freq'])>0">
				<td class="varCatgry">
					<xsl:value-of select="ddi:catStat[@type='freq' and not(@wgtd)]"/>
				</td>
			</xsl:if>
			<!-- Wieghted Frequency -->
			<xsl:if test="count(ddi:catStat[@type='freq' and @wgtd='wgtd'])>0">
				<td class="varCatgry">
					<xsl:value-of select="round(ddi:catStat[@type='freq' and @wgtd='wgtd'])"/>
				</td>
			</xsl:if>
			<!-- Graph -->
			<xsl:if test="count(ddi:catStat[@type='freq'])>0">
				<td class="varCatgry">
					<!-- exclude missing values -->
					<xsl:if test="not (@missing='Y')">
						<xsl:choose>
							<xsl:when test="count(ddi:catStat[@type='freq' and @wgtd='wgtd' and not (../@missing='Y')])>0">
								<!-- Weighted graph -->
								<xsl:variable name="thisFreq" select="ddi:catStat[@type='freq' and @wgtd='wgtd']"/>
								<xsl:variable name="allFreq"   select="../*/ddi:catStat[@type='freq' and @wgtd='wgtd' and not (../@missing='Y')]"/>
								<xsl:variable name="sumFreq" select="sum($allFreq)"/>
								<!-- GIF Image -->
								<xsl:element name="img">
									<xsl:attribute name="src"><xsl:value-of select="'../images/dot_bluegreen.gif'"/></xsl:attribute>
									<xsl:attribute name="height">12</xsl:attribute>
									<xsl:attribute name="width">
										<xsl:call-template name="barchart_length">
											<xsl:with-param name="nodes" select="$allFreq"/>
											<xsl:with-param name="value" select="$thisFreq"/>
										</xsl:call-template>
									</xsl:attribute>
								</xsl:element>
								<!-- display percentage -->
								<xsl:text> </xsl:text>
								<xsl:value-of select="format-number(100 * ($thisFreq div $sumFreq),'#0.0')"/>%
							</xsl:when>
							<xsl:otherwise>
								<!-- Frequency graph -->
								<xsl:variable name="thisFreq" select="ddi:catStat[@type='freq']"/>
								<xsl:variable name="allFreq"   select="../*/ddi:catStat[@type='freq' and not (../@missing='Y')]"/>
								<xsl:variable name="sumFreq" select="sum($allFreq)"/>
								<!-- GIF Image -->
								<xsl:element name="img">
									<xsl:attribute name="src"><xsl:value-of select="'../images/dot_bluegreen.gif'"/></xsl:attribute>
									<xsl:attribute name="height">12</xsl:attribute>
									<xsl:attribute name="width">
										<xsl:call-template name="barchart_length">
											<xsl:with-param name="nodes" select="$allFreq"/>
											<xsl:with-param name="value" select="$thisFreq"/>
										</xsl:call-template>
									</xsl:attribute>
								</xsl:element>
								<!-- display percentage -->
								<xsl:text> </xsl:text>
								<xsl:value-of select="format-number(100 * ($thisFreq div $sumFreq),'#0.0')"/>%
							</xsl:otherwise>
						</xsl:choose>
					</xsl:if>
				</td>
			</xsl:if>
		</tr>
	</xsl:template>
	<!-- 4.3.19 conding instructions -->
	<xsl:template match="ddi:codInstr">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Derivation'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.21 Concepts -->
	<xsl:template match="ddi:concept">
		<xsl:if test="position()>1">, </xsl:if>	
		<xsl:value-of select="normalize-space(text())"/>
		<xsl:if test="normalize-space(./@vocab)"> (<xsl:value-of select="./@vocab"/>)</xsl:if>
	</xsl:template>
	<!-- 4.3.24 Notes -->
	<xsl:template match="ddi:notes">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:value-of select="'Notes'"/></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!--
	    UTILITY TEMPLATES 
	-->
	<!-- VARIABLE INFO TEMPLATE -->
	<xsl:template name="var_info">
		<xsl:param name="title"/>
		<xsl:param name="text"/>
        <div class="box" >
        	<div style="background:#EEEEEE;padding:5px;">
			<b>
				<xsl:value-of select="$title"/>
			</b></div>
			<div style="padding:5px;"><xsl:value-of select="$text" disable-output-escaping="yes"/></div>
		</div>
	</xsl:template>
	<!-- BARCHART LENGTH  (for variable categories graphs)-->
	<xsl:template name="barchart_length">
		<xsl:param name="nodes" select="/.."/>
		<xsl:param name="value" select="/.."/>
		<xsl:variable name="maxLength" select="100"/>	
		<xsl:choose>
			<xsl:when test="not($nodes)">NaN</xsl:when>
			<xsl:otherwise>
			<xsl:choose>
				<xsl:when test="number(system-property('xsl:version'))>=2.0">	<!-- XSL 2.0 -->
					<xsl:value-of select="round($maxLength*($value div max($nodes)))"/>
				</xsl:when>
				<xsl:otherwise> <!-- XSL 1.0 (slow) -->
						<!-- This is based on math:max function from exsl: -->
					<xsl:for-each select="$nodes">
						<xsl:sort data-type="number" order="descending"/>
						<xsl:if test="position() = 1">
							<xsl:value-of select="round($maxLength*($value div number(.)))"/>
						</xsl:if>
					</xsl:for-each>
				</xsl:otherwise>	
			</xsl:choose>	
			</xsl:otherwise>
		</xsl:choose>
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
