<?xml version="1.0" encoding="UTF-8"?>
<!--
Transforms the elements from the study and documentation description into a flat XML format for 
importing into the database for searching

Author:	 Mehmood Asghar (IHSN)
Version: Feb 2011
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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/XSL/Format" xmlns:ddi="http://www.icpsr.umich.edu/DDI" exclude-result-prefixes="ddi">
<xsl:output method="xml" version="1.0" encoding="UTF-8" />	
	<xsl:variable name="from">></xsl:variable>
	<xsl:variable name="to"></xsl:variable>
	<xsl:variable name="codebook_id" select="ddi:codeBook/@ID"/>
	<xsl:param name="column-seperator" select="'{TAB}'"/>

<xsl:template match="/">
	<xsl:element name="study">
    <xsl:apply-templates select="ddi:codeBook" />
    </xsl:element>
</xsl:template>

<xsl:template match="ddi:codeBook">
    <!-- ID -->
    <xsl:element name="id">
    	<xsl:value-of select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:IDNo)" />
    </xsl:element>
        
    <!-- titl -->
    <xsl:element name="titl">
	    <xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:titl"/>
    </xsl:element>
        
	<!--titlStmt -->
    <xsl:element name="titlstmt">
	     <xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:titlStmt"/>
	</xsl:element>
             
    <!-- abbreviation -->
    <xsl:element name="abbreviation">
	    <xsl:apply-templates select="ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:altTitl"/>
    </xsl:element>

    <!-- Collections-->
    <xsl:element name="collections">
	    <xsl:for-each select="//ddi:docDscr/ddi:citation/ddi:rspStmt/ddi:othId">
        	<xsl:value-of select="normalize-space(.)"/>{BR}
        </xsl:for-each>
    </xsl:element>

    <!-- kind of data -->
    <xsl:element name="kindofdata">
	    <xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:dataKind"/>
    </xsl:element>

    <!-- authEnty -->    
	<xsl:element name="authenty">
	    <xsl:call-template name="primary-investigator" />
    </xsl:element>
	
    <!-- geogcover -->    
    <xsl:element name="geogcover">
		<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:geogCover"/>
    </xsl:element>
	
    <!-- nation -->    
    <xsl:element name="nation">
		<xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:nation"/>
    </xsl:element>
        
	<!-- topic -->
   	<xsl:call-template name="topics"/>
    
    <!-- Scope-->   
    <xsl:element name="scope">
	    <xsl:apply-templates select="ddi:stdyDscr/ddi:stdyInfo/ddi:notes"/>
    </xsl:element>
    
    <!-- Keywords-->   
    <xsl:element name="keywords">
       	<!--<xsl:call-template name="keywords"/>-->
       	<xsl:value-of select="normalize-space(//ddi:docDscr)"/>
		<xsl:value-of select="normalize-space(//ddi:stdyDscr)"/>
    </xsl:element>    
    
	<!--Study type/serName-->
    <xsl:element name="sername">
	    <xsl:value-of select="//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:serStmt/ddi:serName" />
    </xsl:element>
    
	<!-- producer -->    
	<xsl:element name="producer">
	    <xsl:apply-templates select="ddi:docDscr/ddi:citation/ddi:prodStmt/ddi:producer"/>
    </xsl:element>
    
    <!-- Reference no -->
    <xsl:element name="refno">	
        <xsl:variable name="refid">
                <xsl:choose>
                    <xsl:when test="normalize-space(ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:IDNo )">
                        <xsl:value-of select="translate(normalize-space(ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:IDNo ),$from, $to)"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="@ID"/>
                    </xsl:otherwise> 
                </xsl:choose>
        </xsl:variable>
        <xsl:value-of select="$refid"/>
	</xsl:element>
    
    <!-- prodDate -->
	<xsl:element name="proddate">
	    <xsl:value-of select="substring(normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:collDate[@event='start']/@date),1,4)"/>
    </xsl:element>
        
    <!-- Sponsor-->
    <xsl:element name="sponsor">
	    <xsl:apply-templates select="//ddi:stdyDscr/ddi:citation/ddi:prodStmt/ddi:fundAg"/>
    </xsl:element>

	<xsl:choose>
    	<!-- when single date is specified, use it for start and end -->
        <xsl:when test="normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:collDate[@event='single']/@date)">

            <!-- data collection start date -->
            <xsl:element name="data_coll_start">
                <xsl:value-of select="substring(normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:collDate[@event='single']/@date),1,4)"/>
            </xsl:element>
        
            <!-- data collection end date -->
            <xsl:element name="data_coll_end">
                <xsl:value-of select="substring(normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:collDate[@event='single']/@date),1,4)"/>
            </xsl:element>
            			        
        </xsl:when>
        
        <!--look for start and end dates -->
        <xsl:otherwise>

            <!-- data collection start date -->
            <xsl:element name="data_coll_start">
                <xsl:value-of select="substring(normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:collDate[@event='start']/@date),1,4)"/>
            </xsl:element>
        
            <!-- data collection end date -->
            <xsl:element name="data_coll_end">
                <xsl:value-of select="substring(normalize-space(ddi:stdyDscr/ddi:stdyInfo/ddi:sumDscr/ddi:collDate[@event='end'][last()]/@date),1,4)"/>
            </xsl:element>

        </xsl:otherwise>

	</xsl:choose>
    
    <!-- additional fields for dime -->

    <!-- project name -->	
    <xsl:element name="ie_project_name">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:titl"/>
    </xsl:element>

	<!-- project id -->	
    <xsl:element name="ie_project_id">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:subTitl"/>
    </xsl:element>

	<!-- program -->	
    <xsl:element name="program">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:altTitl"/>
    </xsl:element>    
    
    <!-- lead researchers/ie_team_leaders -->
    <xsl:call-template name="ie_team_leaders"/>
    	
    <!-- project name -->	
    <xsl:element name="project_name">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:docSrc//ddi:titl"/>        
    </xsl:element>
    
    <!-- project id -->	
    <xsl:element name="project_id">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:docSrc//ddi:subTitl"/>
    </xsl:element>

    <!-- ie project url -->	
    <xsl:element name="ie_project_uri">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:citation/ddi:distStmt/ddi:depositr"/>
    </xsl:element>

    <!--project url-->
    <xsl:element name="project_uri">
	    <xsl:apply-templates select="//ddi:codeBook/ddi:docDscr/ddi:docSrc/ddi:distStmt/ddi:depositr"/>
    </xsl:element>

	<!--origArch - center -->
    <xsl:element name="center">
	    <xsl:value-of select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:dataAccs/ddi:setAvail/ddi:origArch)"/>
    </xsl:element>

</xsl:template>



<!-- templates -->


<!--get all team leaders-->
<xsl:template name="ie_team_leaders">
    <xsl:call-template name="toxml">
        <xsl:with-param name="path" select="//ddi:codeBook/ddi:docDscr/ddi:citation//ddi:distStmt/ddi:contact"/>
        <xsl:with-param name="name" select="'ie_team_leaders'"/>
        <xsl:with-param name="sub-name" select="'leader'"/>
    </xsl:call-template>
</xsl:template>

<xsl:template match="ddi:fundAg">
		<xsl:variable name="fundAg">
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(concat(., ' - ', @abbr, ' - ' , @role ))"/>
			</xsl:call-template>
		</xsl:variable>	
		<xsl:value-of select="$fundAg"/>
        <xsl:text>&lt;BR /&gt;</xsl:text>
</xsl:template>

<xsl:template match="ddi:titl">
    <xsl:variable name="subtitl">
        <xsl:if test="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:subTitl)">
        <!--
            <xsl:call-template name="escape-apos" >
                <xsl:with-param name="string" select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:subTitl)"/>
            </xsl:call-template>        -->
            <xsl:value-of select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:titlStmt/ddi:subTitl)"/>
        </xsl:if>
	</xsl:variable>
    <xsl:variable name="titl"><!--
        <xsl:call-template name="escape-apos" >
            <xsl:with-param name="string" select="normalize-space(.)"/>
        </xsl:call-template>-->
        <xsl:value-of select="normalize-space(.)"/>
    </xsl:variable> 
    <xsl:value-of select="$titl"/>
    <xsl:if test="normalize-space($subtitl)">
		<xsl:value-of select="concat(', ', $subtitl)"  />
    </xsl:if>
</xsl:template>

<xsl:template match="ddi:subTitl">
			<!--
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>-->
			<xsl:value-of select="normalize-space(.)"/>
</xsl:template>


<xsl:template match="ddi:titlStmt">
<!--
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:titlStmt[*/@* | *])"/>
			</xsl:call-template>-->
			<xsl:value-of  select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:titlStmt[*/@* | *])"/>
</xsl:template>

<xsl:template match="ddi:AuthEnty">
			<!--
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>-->
			<xsl:value-of select="normalize-space(.)"/>
</xsl:template>

<xsl:template match="ddi:geogCover">
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="substring(normalize-space(.),1,100)"/>
			</xsl:call-template>			
</xsl:template>

<xsl:template match="ddi:nation">
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>
            {BR}
</xsl:template>

<xsl:template match="ddi:topcClas"><!--
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>-->
			<xsl:value-of select="normalize-space(.)"/>
</xsl:template>

<xsl:template name="topics">

	<!--get topics as xml elements-->
    <xsl:call-template name="toxml">
        <xsl:with-param name="path" select="//ddi:stdyDscr/ddi:stdyInfo//ddi:topcClas"/>
        <xsl:with-param name="name" select="'topics'"/>
        <xsl:with-param name="sub-name" select="'topic'"/>
    </xsl:call-template>

	<!--topics as plain text-->
    <!--
    <xsl:for-each select="//ddi:stdyDscr/ddi:stdyInfo//ddi:topcClas">
    	<xsl:value-of select="normalize-space(.)"/><xsl:text> </xsl:text>
    </xsl:for-each>
    -->
</xsl:template>

<xsl:template name="keywords">
		<xsl:for-each select="//ddi:stdyDscr/ddi:stdyInfo/ddi:subject/ddi:keyword">
			<xsl:text> </xsl:text><xsl:value-of select="normalize-space(.)"/> <xsl:text> </xsl:text>
		</xsl:for-each>
</xsl:template>


<xsl:template match="ddi:notes"><!--
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>-->
			<xsl:value-of select="normalize-space(.)"/>
</xsl:template>

<xsl:template match="ddi:serName">
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>
</xsl:template>

<xsl:template match="ddi:producer">	<!--
			<xsl:call-template name="escape-apos" >
				<xsl:with-param name="string" select="normalize-space(.)"/>
			</xsl:call-template>-->
			<xsl:value-of select="normalize-space(.)"/>
</xsl:template>

<!-- primary investigator - returns the first one -->
<xsl:template name="primary-investigator">

	<xsl:variable name="pi">
	<xsl:for-each select="//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:rspStmt/ddi:AuthEnty">
		<xsl:if test="normalize-space(.)">
			<xsl:choose>
				<!--name with affiliation-->		
				<xsl:when test="normalize-space(@affiliation)">
					<xsl:value-of select="normalize-space(.)"/> - <xsl:value-of select="@affiliation"/>{BR}
				</xsl:when>
				<!--without affiliation-->
				<xsl:otherwise>
					<xsl:value-of select="normalize-space(.)"/>{BR}
				</xsl:otherwise>
			</xsl:choose>		
		</xsl:if>
	</xsl:for-each>
	</xsl:variable>
	
	<xsl:value-of select="normalize-space($pi)"/>

	<!--
	<xsl:variable name="pi">
		<xsl:value-of select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:rspStmt/ddi:AuthEnty)"/>
	</xsl:variable>
	
		<xsl:choose>
			<xsl:when test="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:rspStmt/ddi:AuthEnty/@affiliation)">            
                <xsl:variable name="affiliation">
					<xsl:value-of  select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:rspStmt/ddi:AuthEnty/@affiliation)"/>
                </xsl:variable>        
                <xsl:value-of select="concat($pi, ', ',$affiliation ) "	/>	            
			</xsl:when>
			<xsl:otherwise>
                <xsl:variable name="auth">
					<xsl:value-of select="normalize-space(//ddi:codeBook/ddi:stdyDscr/ddi:citation/ddi:rspStmt/ddi:AuthEnty)"/>
                </xsl:variable>
				<xsl:value-of select="normalize-space($auth)"/>
			</xsl:otherwise>
		</xsl:choose>
	-->
</xsl:template>


<xsl:template name="search-and-replace">
     <xsl:param name="input"/>
     <xsl:param name="search-string"/>
     <xsl:param name="replace-string"/>
     <xsl:choose>
          <!-- See if the input contains the search string -->
          <xsl:when test="$search-string and 
                           contains($input,$search-string)">
          <!-- If so, then concatenate the substring before the search
          string to the replacement string and to the result of
          recursively applying this template to the remaining substring.
          -->
               <xsl:value-of 
                    select="substring-before($input,$search-string)"/>
               <xsl:value-of select="$replace-string"/>
               <xsl:call-template name="search-and-replace">
                    <xsl:with-param name="input"
                    select="substring-after($input,$search-string)"/>
                    <xsl:with-param name="search-string" 
                    select="$search-string"/>
                    <xsl:with-param name="replace-string" 
                        select="$replace-string"/>
               </xsl:call-template>
          </xsl:when>
          <xsl:otherwise>
               <!-- There are no more occurrences of the search string so 
               just return the current input string -->
               <xsl:value-of select="$input"/>
          </xsl:otherwise>
     </xsl:choose>
</xsl:template>

<!-- replace function -->
<xsl:template name="replace-string">
    <xsl:param name="text"/>
    <xsl:param name="replace"/> 
    <xsl:param name="with"/>
    <xsl:choose>
      <xsl:when test="contains($text,$replace)">
        <xsl:value-of select="substring-before($text,$replace)"/>
        <xsl:value-of select="$with"/>
        <xsl:call-template name="replace-string">
          <xsl:with-param name="text"
select="substring-after($text,$replace)"/>
          <xsl:with-param name="replace" select="$replace"/>
          <xsl:with-param name="with" select="$with"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$text"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>


<xsl:template name="escape-apos">
   <xsl:param name="string" />
   <xsl:choose>
      <xsl:when test='contains($string, "&apos;")'>
         <xsl:value-of select='substring-before($string, "&apos;")' />
         <xsl:text>'</xsl:text>
         <xsl:call-template name="escape-apos">
            <xsl:with-param name="string"
               select='substring-after($string, "&apos;")' />
         </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
         <xsl:value-of select="$string" />
      </xsl:otherwise>
   </xsl:choose>
</xsl:template>

<xsl:template name="toxml">
	<xsl:param name="path"/>
    <xsl:param name="name"/>
    <xsl:param name="sub-name"/>
    
    <xsl:element name="{$name}">
        <xsl:for-each select="$path">    	
            <xsl:element name="{$sub-name}">
            <name><xsl:value-of select="normalize-space(.)"/></name>
            <xsl:if test="@*">
            <xsl:for-each select="@*">
              <xsl:element name="{name()}">
                <xsl:value-of select="." />
              </xsl:element>
            </xsl:for-each>
          </xsl:if>
         <xsl:if test="*">
            <xsl:for-each select="*">
              <xsl:element name="{name()}">
                <xsl:value-of select="." />
              </xsl:element>
            </xsl:for-each>
          </xsl:if>

          </xsl:element>
        </xsl:for-each>
   </xsl:element>    
</xsl:template>


</xsl:stylesheet>
