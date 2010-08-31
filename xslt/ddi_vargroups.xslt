<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:ddi="http://www.icpsr.umich.edu/DDI" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="text" encoding="UTF-8" indent="no"/>
	<xsl:template match="/">
		<xsl:apply-templates select="//ddi:varGrp"/>
	</xsl:template>

<xsl:template match="ddi:varGrp">
	<xsl:variable name="line-break" select="'{line}'"/>
	<xsl:variable name="col-break" select="'{colum}'"/>
	<xsl:value-of select="@ID"/><xsl:value-of select="$col-break"/>
	<xsl:value-of select="@varGrp"/><xsl:value-of select="$col-break"/>
	<xsl:value-of select="ddi:labl"/><xsl:value-of select="$line-break"/>
</xsl:template>
	
</xsl:stylesheet>
