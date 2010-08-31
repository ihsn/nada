<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
		version="1.0" 
        xmlns:xs="http://www.w3.org/2001/XMLSchema" 
        xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
        exclude-result-prefixes="ddi xs">
        <xsl:output method="text" version="1.0" encoding="UTF-8" indent="yes"/>
	
<xsl:template match="/">
	<xsl:apply-templates select="ddi:codeBook/ddi:fileDscr"/>
</xsl:template>	
	
<xsl:template match="ddi:var">
<tr>
<td><xsl:value-of select="@ID"/></td>
<td><xsl:value-of select="@name"/></td>
<td><xsl:value-of select="ddi:labl"/></td>
<td><xsl:value-of select="@intrvl"/></td>
<td><xsl:value-of select="ddi:varFormat/@type"/></td>
<td><xsl:value-of select="ddi:qstn/ddi:preQTxt"/></td>
</tr>
</xsl:template>
		
	<xsl:template match="ddi:fileDscr">
    	<!--
        <li><span><a href="?id=php-survey-id&amp;file={@ID}&amp;section=datafile" onclick="showSection('datafile',1,this.id);return false;" id="{@ID}" class="file" ><xsl:value-of select="substring-before(ddi:fileTxt/ddi:fileName,'.NSDstat')"/></a></span></li> 
        -->
       <xsl:value-of select="@ID"/>{TAB}<xsl:apply-templates select="ddi:fileTxt/ddi:fileName"/>{BR}
        <!--
        <xsl:variable name="file" select="@ID"/><br/>
        <table border="1">
            <xsl:apply-templates select="//ddi:codeBook/ddi:dataDscr/ddi:var[@files=$file]"/>
        </table>
        -->
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
