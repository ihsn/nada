<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces variable details/description per data file of a DDI 1/2.x XML document

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
    <!--<xsl:include href="OpenInReader.inc.xslt"/>-->
    
    <xsl:param name="survey.base" select="'s'"/>	
    <xsl:param name="outline.base" select="'y'"/>
    <xsl:param name="file" select="'F1'"/>
	
<xsl:template match="/">
<style>
	.variable td {border:1px solid gainsboro;}
	.variable .bg1{background-color:#EEEEEE;}
	.box {border:1px solid gainsboro;margin-top:10px;}
</style>

    <xsl:apply-templates select="/ddi:codeBook/ddi:dataDscr/ddi:var[@files=$file]"/>

</xsl:template>	
	
	<!-- 4.3 VARIABLE -->
	<xsl:template match="ddi:var">

		<table class="variable-info" border="1px" width="100%" style="width:100%;">

			<xsl:element name="bookmark">
					<xsl:attribute name="content"><xsl:value-of select="ddi:labl"/></xsl:attribute>
					<xsl:attribute name="level">1</xsl:attribute>
			</xsl:element>
			
            <!-- ANCHOR-->
			<xsl:element name="a">
					<xsl:attribute name="name"><xsl:value-of select="$file"/><xsl:value-of select="@ID"/></xsl:attribute>
			</xsl:element>
            
    	<!-- OVERVIEW -->
    	<tr>
    	<td colspan="2">
			<div class="content">
				<div class="xsl-title" style="padding:0px;margin:0px;"><xsl:value-of select="ddi:labl"/>(<xsl:value-of select="@name"/>)
				<br/><span style="font-weight:normal;color:black;"><xsl:call-template name="gettext"><xsl:with-param name="msg">File</xsl:with-param></xsl:call-template>: <xsl:call-template name="fileRef">
					   <xsl:with-param name="fileId" select="@files"/>
				</xsl:call-template></span>
				</div>
			</div>
		</td>
		</tr>

            <tr>
            	<td colspan="2" style="background:#EEEEEE;padding:5px;">
                        <b><xsl:call-template name="gettext"><xsl:with-param name="msg">Overview</xsl:with-param></xsl:call-template></b>
                </td>                
            </tr>

			<tr>
				<!-- BASIC CHARACTERISTICS -->
				<td valign="top">
					<div class="content" style="margin-left:20px;">
						<xsl:if test="@intrvl">
							<xsl:call-template name="gettext"><xsl:with-param name="msg">Type</xsl:with-param></xsl:call-template>: 
							<xsl:choose>
								<xsl:when test="@intrvl='discrete'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Discrete</xsl:with-param></xsl:call-template></xsl:when>
								<xsl:when test="@intrvl='contin'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Continuous</xsl:with-param></xsl:call-template></xsl:when>
								<xsl:otherwise><xsl:value-of select="'Undetermined'"/></xsl:otherwise>
							</xsl:choose>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:varFormat/@type">
							<xsl:call-template name="gettext"><xsl:with-param name="msg">Format</xsl:with-param></xsl:call-template>: <xsl:value-of select="ddi:varFormat/@type"/>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:location/@width">
							<xsl:call-template name="gettext"><xsl:with-param name="msg">Width</xsl:with-param></xsl:call-template>: <xsl:value-of select="ddi:location/@width"/>
							<br/>
						</xsl:if>
						<xsl:if test="@dcml">
							<xsl:call-template name="gettext"><xsl:with-param name="msg">Decimals</xsl:with-param></xsl:call-template>: <xsl:value-of select="@dcml"/>
							<br/>
						</xsl:if>
						<xsl:if test="ddi:valrng">
							<xsl:call-template name="gettext"><xsl:with-param name="msg">Range</xsl:with-param></xsl:call-template>:
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
							<xsl:call-template name="gettext"><xsl:with-param name="msg">Invalid</xsl:with-param></xsl:call-template>:
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

		<!-- FILE -->
		<!-- DESCRIPTION -->
		<!-- Text -->
		<xsl:apply-templates select="ddi:txt"/>
            
		<!-- Universe -->
		<xsl:apply-templates select="ddi:universe"/>

		<!-- Response Unit -->
		<xsl:apply-templates select="ddi:respUnit"/>


        <xsl:if test="normalize-space(ddi:qstn)">            
			<xsl:apply-templates select="ddi:qstn"/>
		</xsl:if>

        <!-- IMPUTATION / DERIVATION-->
        <!-- Imputation -->
        <!--<xsl:apply-templates select="ddi:imputation"/>-->
        <!-- Response Unit -->
       <!-- <xsl:apply-templates select="ddi:codInstr"/>-->
        <!-- OTHERS -->
        <!-- Security -->
        <!--<xsl:apply-templates select="ddi:security"/>-->
        <!-- Concepts -->
        <!--<xsl:if test="count(ddi:concept)>0">-->
        
<!--            <xsl:variable name="concepts">
                <xsl:apply-templates select="ddi:concept"/>
            </xsl:variable>
            <xsl:call-template name="var_info">
                <xsl:with-param name="title"><xsl:value-of select="'Concepts'"/></xsl:with-param>
                <xsl:with-param name="text">
                    <xsl:value-of select="$concepts"/>
                </xsl:with-param>
            </xsl:call-template>
        </xsl:if>-->
        <!-- Notes -->
        <!--<xsl:apply-templates select="ddi:notes"/>-->

        <!-- OPEN NESSTAR -->
<!--        <xsl:if test="position()=1">
            <xsl:call-template name="open_in_reader"/>
        </xsl:if>	-->
        </table>
        <br/>
        <br/>
	</xsl:template>
	<!--
	    DDI ELEMENT TEMPLATES 
	-->
	<!-- 4.2.3 imputation -->
	<xsl:template match="ddi:imputation">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Imputation</xsl:with-param></xsl:call-template></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.4 Security -->
	<xsl:template match="ddi:security">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Security</xsl:with-param></xsl:call-template></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.6 response unit -->
	<xsl:template match="ddi:respUnit">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Source of information</xsl:with-param></xsl:call-template></xsl:with-param>
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
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Pre question</xsl:with-param></xsl:call-template></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8.2 literal question  -->
	<xsl:template match="ddi:qstnLit">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Literal question</xsl:with-param></xsl:call-template></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8.3 Post-question  -->
	<xsl:template match="ddi:postQTxt">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Post question</xsl:with-param></xsl:call-template></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.8.6 Interviewer Instructions -->
	<xsl:template match="ddi:ivuInstr">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Interviewer instructions</xsl:with-param></xsl:call-template></xsl:with-param>
			<xsl:with-param name="text">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	<!-- 4.3.12 variable universe -->
	<xsl:template match="ddi:universe">
		<xsl:call-template name="var_info">
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Universe</xsl:with-param></xsl:call-template></xsl:with-param>
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
				<xsl:when test="@type='invd'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Invalid</xsl:with-param></xsl:call-template>: </xsl:when>
				<xsl:when test="@type='max'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Maximum</xsl:with-param></xsl:call-template>: </xsl:when>
				<xsl:when test="@type='mean'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Mean</xsl:with-param></xsl:call-template>: </xsl:when>
				<xsl:when test="@type='min'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Minimum</xsl:with-param></xsl:call-template>: </xsl:when>
				<xsl:when test="@type='stdev'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Standard deviation</xsl:with-param></xsl:call-template>: </xsl:when>
				<xsl:when test="@type='vald'"><xsl:call-template name="gettext"><xsl:with-param name="msg">Valid cases</xsl:with-param></xsl:call-template>: </xsl:when>
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
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Description</xsl:with-param></xsl:call-template></xsl:with-param>
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
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Derivation</xsl:with-param></xsl:call-template></xsl:with-param>
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
			<xsl:with-param name="title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Notes</xsl:with-param></xsl:call-template></xsl:with-param>
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
        <!--
        <tr>
        	<td><b><xsl:value-of select="$title"/></b></td>
			<td><xsl:value-of select="$text" disable-output-escaping="yes"/></td>
		</tr>
        -->
        <tr>
        	<td colspan="2" style="background-color:#F4F4F4"><b><xsl:value-of select="$title"/></b></td>
        </tr>
        <tr>
        	<td colspan="2">
            <!--<xsl:value-of select="$text" disable-output-escaping="yes"/>-->
            <xsl:call-template name="trim_and_preserve">
		            <xsl:with-param name="text" select="$text"/>
        	</xsl:call-template>            
            </td>
        </tr>
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


	<!-- 
    	Trim and Preserve Spaces (line breaks)
    	Note: Does not preserve HTML in the output
    -->
    <xsl:template name="trim_and_preserve">
    	<xsl:param name="text"/>
        
        <!-- trim -->
        <xsl:variable name="tt">
        	 <xsl:call-template name="trim">
                    <xsl:with-param name="s">
                        <xsl:value-of select="$text"/>
                    </xsl:with-param>
             </xsl:call-template>
        </xsl:variable>

        <!-- preserve line breaks -->
        <xsl:call-template name="PreserveLineBreaks">
            <xsl:with-param name="text">
                <xsl:value-of select="$tt"/>
            </xsl:with-param>
        </xsl:call-template>                
		
    </xsl:template>
    
    <!--
    Taken from: http://www.danrigsby.com/blog/index.php/2008/01/03/preserving-line-breaks-in-xml-while-transforming-to-html-with-xslt/
	Preserve the line breaks
    -->
    <xsl:template name="PreserveLineBreaks">
        <xsl:param name="text"/>
        <xsl:choose>
            <xsl:when test="contains($text,'&#xA;')">
                <xsl:value-of select="substring-before($text,'&#xA;')"/>
                <br/>
                <xsl:call-template name="PreserveLineBreaks">
                    <xsl:with-param name="text">
                        <xsl:value-of select="substring-after($text,'&#xA;')"/>
                    </xsl:with-param>
                </xsl:call-template>                
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$text"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="left-trim">
      <xsl:param name="s" />
      <xsl:choose>
        <xsl:when test="substring($s, 1, 1) = ''">
          <xsl:value-of select="$s"/>
        </xsl:when>
        <xsl:when test="normalize-space(substring($s, 1, 1)) = ''">
          <xsl:call-template name="left-trim">
            <xsl:with-param name="s" select="substring($s, 2)" />
          </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$s" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:template>
    
    <xsl:template name="right-trim">
      <xsl:param name="s" />
      <xsl:choose>
        <xsl:when test="substring($s, 1, 1) = ''">
          <xsl:value-of select="$s"/>
        </xsl:when>
        <xsl:when test="normalize-space(substring($s, string-length($s))) = ''">
          <xsl:call-template name="right-trim">
            <xsl:with-param name="s" select="substring($s, 1, string-length($s) - 1)" />
          </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$s" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:template>
    
    <xsl:template name="trim">
      <xsl:param name="s" />
      <xsl:call-template name="right-trim">
        <xsl:with-param name="s">
          <xsl:call-template name="left-trim">
            <xsl:with-param name="s" select="$s" />
          </xsl:call-template>
        </xsl:with-param>
      </xsl:call-template>
    </xsl:template>
	
</xsl:stylesheet>
