<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:i18n="http://toolkit.sf.net/i18n/messages" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:outline="http://worldbank.org/toolkit/cdrom/outline" exclude-result-prefixes="ddi outline">
		
        <xsl:include href="gettext.xslt"/>
        <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
		<!--file id -->
		<xsl:param name="file" select="'F5'"/>
        <xsl:param name="browser_url" select="''"/>
		
<xsl:template match="/">	
	<div id="variable-list">
    <div class="xsl-title"><xsl:call-template name="gettext"><xsl:with-param name="msg">Data File</xsl:with-param></xsl:call-template></div>
	<xsl:apply-templates select="//ddi:codeBook/ddi:fileDscr[@ID=$file]"/>
	<h2 class="xsl-subtitle" id="variables"><xsl:call-template name="gettext"><xsl:with-param name="msg">Variables</xsl:with-param></xsl:call-template></h2>
	<table border="1" style="border-collapse:collapse;" cellpadding="4" class="table-variable-list" width="100%">
		<tr class="var-th">
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">ID</xsl:with-param></xsl:call-template></td>
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Name</xsl:with-param></xsl:call-template></td>
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Label</xsl:with-param></xsl:call-template></td>
		<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Question</xsl:with-param></xsl:call-template></td>
		</tr>
		<xsl:apply-templates select="//ddi:codeBook/ddi:dataDscr/ddi:var[@files=$file]"/>
	</table>
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
            <xsl:variable name="hover">
	            <xsl:call-template name="gettext"><xsl:with-param name="msg">Click to view variable information</xsl:with-param></xsl:call-template>
            </xsl:variable>
    <tr valign="top" class="{$class}" id="{$id}" title="{$hover}">			
        <td class="var-td"><a style="text-decoration:none;color:black;" href="{$link}" ><xsl:value-of select="@ID"/></a></td>
        <td  class="var-td"><xsl:value-of select="@name"/></td>
        <td class="var-td"><xsl:value-of select="ddi:labl"/></td>
        <td class="var-td"><xsl:value-of select="ddi:qstn/ddi:qstnLit"/></td>
    </tr>
</xsl:template>

	
	
	<!-- 4.3 FILE -->
	<xsl:template match="ddi:fileDscr">
		<xsl:variable name="file" select="@ID"/>
		<h2 class="xsl-subtitle"><xsl:value-of select="substring-before(ddi:fileTxt/ddi:fileName,'.NSDstat')"/></h2>
		<table style="width:100%;" class="data-file-bg1" cellpadding="4" >
			<xsl:if test="normalize-space(ddi:fileTxt/ddi:fileCont)">
			<tr valign="top">
				<td style="width:100px"><xsl:call-template name="gettext"><xsl:with-param name="msg">Content</xsl:with-param></xsl:call-template></td>
				<td>
                		<div style="width:100%;height:50px; overflow:auto;border:1px solid silver;background-color:white;">
                            <div style="padding:5px;">
                                <xsl:value-of select="normalize-space(ddi:fileTxt/ddi:fileCont)"/>
                            </div>
                        </div>
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
				<td><xsl:value-of select="ddi:fileTxt/ddi:verStmt/ddi:version"/></td>
			</tr>
            </xsl:if>
            <xsl:if test="ddi:fileTxt/ddi:filePlac">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Producer</xsl:with-param></xsl:call-template></td>
				<td><xsl:value-of select="ddi:fileTxt/ddi:filePlac"/></td>
			</tr>
			</xsl:if>
            <xsl:if test="normalize-space(ddi:fileTxt/ddi:dataMsng)">
			<tr valign="top">
				<td><xsl:call-template name="gettext"><xsl:with-param name="msg">Missing Data</xsl:with-param></xsl:call-template></td>
				<td>	<div style="width:100%;height:50px; overflow:auto;border:1px solid silver;background-color:white;">
                            <div style="padding:5px;">
                                <xsl:value-of select="ddi:fileTxt/ddi:dataMsng"/>
                            </div>
                        </div>
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
</xsl:stylesheet>
