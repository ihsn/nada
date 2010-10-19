<?xml version="1.0" encoding="UTF-8"?>
<!--
A lookup function to find language keys

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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">

		<!-- language  -->
		<xsl:param name="lang" select="'english'" />				
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
