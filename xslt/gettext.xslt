<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">

		<!-- language  -->
		<xsl:param name="lang" select="'es'" />				
		<!-- path to the language file -->
		<xsl:param name="langfile" select="concat($lang,'.xml')" />
		<!-- read the xml file -->
		<xsl:variable name="langEntries" select="document($langfile)/properties/entry"/>

   <!-- get translated messages -->
	 <xsl:template name="gettext">     		
			<xsl:param name="msg"/>
			<xsl:choose>            	
				<xsl:when test="$langEntries[@key=$msg]">
					<xsl:value-of select="$langEntries[@key=$msg] "/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$msg"/>
                    <!--
                    <span style="color:red">
					<xsl:value-of select="$msg"/>
					</span>-->
				</xsl:otherwise> 
			</xsl:choose>				
	</xsl:template>   

</xsl:stylesheet>
