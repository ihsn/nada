<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform produces an HTML table for a datafile with all variables from the file listed

Author:	 Mehmood Asghar (IHSN)
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
        
        <xsl:param name="page_offset" select="0"/>
        <xsl:param name="page_limit" select="10"/>

	<!-- pagination -->
		<xsl:variable name="total" select="count(//ddi:codeBook/ddi:dataDscr/ddi:var[@files=$file])"/>
		
		
<xsl:template match="/">	
	<div id="variable-list">					
    <div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">data_dictionary</xsl:with-param></xsl:call-template></div>
	<xsl:apply-templates select="//ddi:codeBook/ddi:fileDscr[@ID=$file]"/>

	<xsl:variable name="offset">
		<xsl:choose>
			<xsl:when test="$page_offset &gt; $total">
					<xsl:value-of select="$total - $page_limit"/>
			</xsl:when>
			<xsl:when test="$page_offset &lt; 0">
					<xsl:value-of select="0"/>
			</xsl:when>
			<xsl:when test="$page_offset &lt; $total">
				<xsl:value-of select="$page_offset"/>
			</xsl:when>
			<xsl:otherwise>
					<xsl:value-of select="1"/>	
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="limit" select="$page_limit"/>

	<!-- navigation bar -->
	<!-- prev button offset -->
	<xsl:variable name="prev">
		<xsl:choose>
			<xsl:when test="($offset - $limit) &lt; 0">0</xsl:when>
			<xsl:when test="($offset - $limit) = 0">0</xsl:when>
			<xsl:when test="($offset - $limit) &gt; 0"><xsl:value-of select="($offset - $limit)"/></xsl:when>
		</xsl:choose>
	</xsl:variable>
	<!-- next button -->
	<xsl:variable name="next">
		<xsl:choose>
			<xsl:when test="($offset + $limit) &lt; $total"><xsl:value-of select="($offset + $limit)"/></xsl:when>
			<xsl:when test="($offset + $limit) &gt; $total">0</xsl:when>
		</xsl:choose>
	</xsl:variable>
<!--	
	<div class="variable-pager">
	<span>Showing <xsl:value-of select="$offset+1"/> to <xsl:value-of select="$offset+$limit"/> of <xsl:value-of select="$total"/> items</span>
	<xsl:if test="$offset &gt; 0"><a href="{$browser_url}/datafile/{$file}/?limit={$limit}&amp;offset={$prev}">Prev</a></xsl:if>
	<xsl:if test="$next &gt; 0"><a href="{$browser_url}/datafile/{$file}/?limit={$limit}&amp;offset={$next}">Next</a></xsl:if>
	</div>
-->		
	<h2 class="xsl-subtitle" id="variables"><xsl:call-template name="gettext"><xsl:with-param name="msg">Variables</xsl:with-param></xsl:call-template></h2>
	<table border="1" style="border-collapse:collapse;" cellpadding="4" class="table-variable-list" width="100%">
		<tr class="var-th">
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></td>
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Label</xsl:with-param></xsl:call-template></td>
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Question</xsl:with-param></xsl:call-template></td>
		</tr>
		<xsl:apply-templates select="//ddi:codeBook/ddi:dataDscr/ddi:var[@files=$file]"/>
		</table>
        
        <table style="border-collapse:collapse;width:100%;" cellpadding="4" class="ddi-var-pager">
        <tr>
			<td>
			<div class="count">
			<xsl:call-template name="gettext">
				<xsl:with-param name="msg">Total variable(s)</xsl:with-param></xsl:call-template>:
			<xsl:text> </xsl:text> <xsl:value-of select="$total"/>
			</div>
			</td>
			<td>
					<!-- pager bar -->
					<div class="variable-pager-container">
						<xsl:call-template name="tplPaging">
							<xsl:with-param name="numberOfItems" select="$total"/>
							<xsl:with-param name="limit" select="$limit"/>
							<xsl:with-param name="offset" select="$offset"/>
						</xsl:call-template>
					</div>			
			</td>
		</tr>
	</table>

    </div>    
</xsl:template>	
	
<xsl:template match="ddi:var">

	<!-- if offset is greater than the total number of pages, show last page -->
	<xsl:variable name="offset">
		<xsl:choose>
			<xsl:when test="$page_offset &gt; $total">
					<xsl:value-of select="$total - $page_limit"/>
			</xsl:when>
			<xsl:when test="$page_offset &lt; 0">
					<xsl:value-of select="0"/>
			</xsl:when>
			<xsl:when test="$page_offset &lt; $total">
				<xsl:value-of select="$page_offset"/>
			</xsl:when>
			<xsl:otherwise>
					<xsl:value-of select="1"/>	
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
		<xsl:variable name="limit" select="$page_limit"/>
		
	<xsl:if test="(position() &gt;$offset) and (position() &lt;=($offset+$limit))">
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
			<xsl:variable name="link"><xsl:value-of select="$browser_url"/>/datafile/<xsl:value-of select="@files"/>/<xsl:value-of select="$id"/></xsl:variable>
            <xsl:variable name="hover">
	            <xsl:call-template name="gettext"><xsl:with-param name="msg">Click to view variable information</xsl:with-param></xsl:call-template>
            </xsl:variable>
    <tr valign="top" class="{$class}" id="{$id}" title="{$hover}">			
        <td class="var-td"><a href="{$link}" ><xsl:value-of select="@name"/></a></td>
        <td class="var-td"><xsl:value-of select="ddi:labl"/></td>
        <td class="var-td"><xsl:call-template name="lf2br"><xsl:with-param name="text" select="ddi:qstn/ddi:qstnLit"/></xsl:call-template></td>
    </tr>
    <tr class="var-info-panel" style="display:none;">
        <td colspan="4" id="pnl-{$id}" class="panel-td"></td>
    </tr>
</xsl:if>
</xsl:template>

	<!-- 4.3 FILE -->
	<xsl:template match="ddi:fileDscr">
		<xsl:variable name="file" select="@ID"/>
		<h2 class="xsl-subtitle"><xsl:call-template name="gettext"><xsl:with-param name="msg">Data File</xsl:with-param></xsl:call-template>: <xsl:value-of select="substring-before(ddi:fileTxt/ddi:fileName,'.NSDstat')"/></h2>
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
            
		</table>
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


<!--
Creates google style pagination bar
@Author: Brian Pedersen
@Website:http://briancaos.wordpress.com	
-->
<xsl:template name="tplPaging">
	<!-- Identifies the number of items in the list -->
	<xsl:param name="numberOfItems" />
	<xsl:param name="limit" />
	<xsl:param name="offset" />
	
	<!-- Optional parameter identifying the default selected page. Default is 1 -->
	<xsl:param name="currentPage" select="$offset div $limit" />
	
	<!-- Calculate the maximum number of pages to show in the paging component -->
	<xsl:variable name="numberOfPages" select="floor((number($numberOfItems)-1) div $limit)+1"/>

	<xsl:variable name="prev">
		<xsl:choose>
			<xsl:when test="($offset - $limit) &lt; 0">0</xsl:when>
			<xsl:when test="($offset - $limit) = 0">0</xsl:when>
			<xsl:when test="($offset - $limit) &gt; 0"><xsl:value-of select="($offset - $limit)"/></xsl:when>
		</xsl:choose>
	</xsl:variable>
	<!-- next button -->
	<xsl:variable name="next">
		<xsl:choose>
			<xsl:when test="($offset + $limit) &lt; $total"><xsl:value-of select="($offset + $limit)"/></xsl:when>
			<xsl:when test="($offset + $limit) &gt; $total">0</xsl:when>
		</xsl:choose>
	</xsl:variable>

	<!-- Calaulate the starting position of the numbers -->
	<xsl:variable name="startPage">
	  <xsl:choose>
		<xsl:when test="$currentPage &gt; 6">
		  <xsl:value-of select="$currentPage - 5"/>
		</xsl:when>
		<xsl:otherwise>
		  <xsl:value-of select="1"/>
		</xsl:otherwise>
	  </xsl:choose>
	</xsl:variable>
	
	<!-- Calculate the ending position of the numbers -->
	<xsl:variable name="endPage">
	  <xsl:choose>
		<xsl:when test="$numberOfPages - $currentPage &gt; 5">
		  <xsl:value-of select="$currentPage + 5"/>
		</xsl:when>
		<xsl:otherwise>
		  <xsl:value-of select="$numberOfPages"/>
		</xsl:otherwise>
	  </xsl:choose>  
	</xsl:variable>	
	
	<!-- Recursively draw the paging component -->
	<ul class="variable-pager">
	  <xsl:if test="$currentPage &gt; 0">
		<li>
		  <a href="{$browser_url}/datafile/{$file}/?limit={$limit}&amp;offset={$prev}">
		  <xsl:call-template name="gettext"><xsl:with-param name="msg">Prev</xsl:with-param></xsl:call-template></a>
		</li>
	  </xsl:if>
	  <xsl:if test="($offset &lt; $total) and ($limit &lt; $total) ">
	  <xsl:call-template name="tplNumber">
		<xsl:with-param name="current" select="$currentPage"/>
		<xsl:with-param name="number" select="$startPage"/>
		<xsl:with-param name="max" select="$endPage"/>
		<xsl:with-param name="limit" select="$limit"/>
	  </xsl:call-template>
	  </xsl:if>
	  <xsl:if test="$currentPage + 1 &lt; $numberOfPages">
		<li>
		<a href="{$browser_url}/datafile/{$file}/?limit={$limit}&amp;offset={$next}">
		<xsl:call-template name="gettext"><xsl:with-param name="msg">Next</xsl:with-param></xsl:call-template>
		</a>		  
		  </li>
	</xsl:if>
	</ul>
</xsl:template>

<xsl:template name="tplNumber">
  <xsl:param name="current"/>
  <xsl:param name="number"/>
  <xsl:param name="max"/>
  <xsl:param name="limit"/>

  <xsl:choose>
    <xsl:when test="$number = $current +1 ">
      <!-- Show current page without a link -->
	<li class="current">
        <span><xsl:value-of select="$number"/></span>
     </li>
    </xsl:when>
    <xsl:otherwise>
	<li>
        <a href="{$browser_url}/datafile/{$file}/?offset={($number -1) * $limit}&amp;limit={$limit}"><xsl:value-of select="$number"/></a>
     </li>
    </xsl:otherwise>
  </xsl:choose>

  <!-- Recursively call the template untill we reach the max number of pages -->
  <xsl:if test="$number &lt; $max">
    <xsl:call-template name="tplNumber">
      <xsl:with-param name="current" select="$current"/>
      <xsl:with-param name="number" select="$number+1"/>
      <xsl:with-param name="max" select="$max"/>
      <xsl:with-param name="limit" select="$limit"/>
    </xsl:call-template>
  </xsl:if>
</xsl:template>

</xsl:stylesheet>
