<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fn="http://www.w3.org/2005/02/xpath-functions" xmlns:i18n="http://toolkit.sf.net/i18n/messages">
	
	<xsl:param name="lang" select="'fr'"/>

	<xsl:variable name="msg-filename">
		<xsl:choose>
			<xsl:when test="lower-case($lang)='en' or lower-case($lang)='us'">
				<xsl:value-of select="'messages.properties.xml'"/>
			</xsl:when>
			<xsl:when test="normalize-space($lang)">
				<xsl:value-of select="concat('messages_',lower-case($lang),'.properties.xml')"/>
			</xsl:when>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="msg" select="document($msg-filename)"/>
	
	<xsl:variable name="default-msg" select="document('messages.properties.xml')"/>
	
	<xsl:function name="i18n:get-string">
		<xsl:param name="key"/>
		<xsl:choose><xsl:when test="$msg/*/entry[@key=$key]">
			<xsl:value-of select="string($msg/*/entry[@key=$key])"/>
		</xsl:when><xsl:when test="$default-msg/*/entry[@key=$key]">
			<xsl:value-of select="string($default-msg/*/entry[@key=$key])"/>
		</xsl:when><xsl:otherwise>
			!<xsl:value-of select="$key"/>!
		</xsl:otherwise></xsl:choose>
	</xsl:function>
	
	<!-- 
	<xsl:template name="i18n:format-string">
		<xsl:param name="string"/>
  		<xsl:param name="a0"/>
  		<xsl:param name="a1"/>
  		<xsl:param name="a2"/>
  		<xsl:param name="a3"/>
  		<xsl:param name="a4"/>
  		<xsl:value-of select="i18n:recurse-string($string,$args,0)"/>
  	</xsl:template>
  	
  	<xsl:function name="i18n:recurse-string">
		<xsl:param name="string"/>
  		<xsl:param name="args"/>
  		<xsl:param name="counter"/>
  		<xsl:variable name="replace" select="concat('{',$counter,'}')"/>
  		<xsl:choose>
  			<xsl:when test="contains($string, $replace)">
  				<xsl:value-of select="i18n:recurse-string(replace($string,$replace,$args[position() = $counter]),args, $counter + 1)"/>
  			</xsl:when>
  			<xsl:otherwise>
  				<xsl:value-of select="$string"/>
  			</xsl:otherwise>
  		</xsl:choose>
  	</xsl:function>
  	-->
</xsl:stylesheet>
