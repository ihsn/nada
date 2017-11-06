<?xml version="1.0" encoding="UTF-8"?>
<!--
Ths transform output the names of the sections that are filled in by the documenter. It 
is used to removed unused menu items from the DDI-Browser left menu.

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
	<xsl:output method="html"/>
	<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
	
	<xsl:template match="/">
			<xsl:apply-templates select="ddi:codeBook"/>
	</xsl:template>
	<!-- ddi:codeBook -->
	<xsl:template match="ddi:codeBook">
		
				<!--Impact Evaluation -->
				<xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:distStmt/ddi:contact | //ddi:codeBook/ddi:docDscr/ddi:docSrc/ddi:distStmt/ddi:contact">
					impact_evaluation
				</xsl:if>
				
				<!--Related Operation -->
				<xsl:if test="//ddi:codeBook/ddi:docDscr/ddi:docSrc//ddi:titl | //ddi:codeBook/ddi:docDscr/ddi:docSrc/ddi:titlStmt/ddi:IDNo">
					related_operations
				</xsl:if>
		
				<!--Data Collection -->
				<xsl:if test="ddi:stdyDscr//ddi:sumDscr | ddi:stdyDscr//ddi:collMode | ddi:stdyDscr//ddi:collSitu | ddi:stdyDscr//ddi:resInstru | ddi:stdyDscr//ddi:actMin">
					datacollection
				</xsl:if>

				<!--SAMPLING -->
				<xsl:if test="ddi:stdyDscr//ddi:sampProc | ddi:stdyDscr//ddi:deviat | ddi:stdyDscr//ddi:anlyInfo/ddi:respRate | ddi:stdyDscr//ddi:weight">
					sampling
				</xsl:if>
				
				<!--DATA APPRAISAL -->
				<xsl:if test="normalize-space(ddi:stdyDscr//ddi:EstSmpErr) | normalize-space(ddi:stdyDscr//ddi:dataAppr)">
					dataappraisal
				</xsl:if>

				<!--QUESTIONNAIRES -->
				<xsl:if test="ddi:stdyDscr//ddi:resInstru">
					questionnaires
				</xsl:if>

				<!--DATA PROCESSING -->
				<xsl:if test="ddi:stdyDscr//ddi:cleanOps |  ddi:stdyDscr/ddi:method/ddi:notes">
					dataprocessing
				</xsl:if>
	</xsl:template>
	
</xsl:stylesheet>
