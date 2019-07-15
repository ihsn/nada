<?php 
//tooltips
$lang['show_help_tooltip']   = 'Show/hide all help';
$lang['show_fields_tooltip'] = 'Show all fields';
$lang['mandatory_recommended_tooltip'] = 'Show mandatory and recommended fields only';
$lang['mandatory_only_tooltip'] = 'Show mandatory fields only'; 
$lang['save_tooltip'] = 'Save Study Description';

$lang['titl']="<p>Enter the official title of the survey. The title can be in English or in the language of the survey. Include the reference year(s), but DO NOT include the survey acronym or the country name as part of the title. Capitalize the first letter of each word (except for prepositions or other conjunctions). For titles in French, English, Portuguese etc, accentuated characters must be provided. Example: “Enquête Démographique et de Santé 2008-2009”.</p>";
$lang['altTitl']="<p>Enter the survey acronym (including country initial if relevant). The survey reference year(s) may be included. Example: DHS 2008-09.</p>";
$lang['serName']="<p>The study type or survey type is the broad category defining the survey.  Select one option from the drop-down menu. Select “Other” if none of the options matches your survey.</p>";
$lang['serInfo']="<p>A survey may be repeated at regular intervals (such as an annual labor force survey), or be part of an international survey program (such as the MICS, CWIQ, DHS, LSMS and others). The Series information is a description of this 'collection' of surveys. A brief description of the characteristics of the survey, including when it started, how many rounds were already implemented, and who is in charge would be provided here. If the survey does not belong to a series, leave this field empty.</p>";
$lang['parTitl']="<p>This field will in most cases be left empty. In countries with more than one official language, a translation of the title may be provided.</p>";
$lang['IDNo']="<pre>
The ID number of a dataset is a unique number that is used to identify a particular survey. Define and use a consistent scheme to use. Such an ID could be constructed as follows: country-producer-survey-year-version where
- country is the 3-letter ISO country abbreviation
- producer is the abbreviation of the producing agency
- survey is the survey abbreviation 
- year is the reference year (or the year the survey started)
- version is the number dataset version number (see Version Description below)
</pre>
";
$lang['version']="<pre>
<p>The version description should contain a version number followed by a version description. Examples:</p>
<p>
<ul>
<li>version 0.1:  Basic raw data, obtained from data entry (before editing).</li>
<li>version 1.2:  Edited data, second version, for internal use only. </li>
<li>version 2.1:  Edited, anonymous dataset for public distribution. </li>
A brief description of the version should follow the numerical identification. </li>
</ul>
</p>";
$lang['version_idate']="<p>This is the date in ISO format (yyyy-mm-dd) of actual and final production of the data. Provide at least the month and year. </p>";

$lang['version_notes']="<p>Version notes should provide a brief report on the changes made through the versioning process. The note should indicate how this version differs from other versions of the same dataset.</p>
";
$lang['overview_abstract']="<p>The abstract should provide a clear summary of the purposes, objectives and content of the survey. </p>";

$lang['instructions_project_submit']='<p> Once you are satisfied that the information you have entered for your study is correct you are ready to submit your project. Please select a suitable access policy for the distribution of the data, a catalog in which the data should be published, any notes you might have regarding embargoes, sensitive information that needs to be removed before distribution or any other notes or special instructions you would like to submit to the Microdata Library.</p> ';
$lang['instructions_project_contributor_review'] = $lang['instructions_project_submit'];
$lang['instructions_datafiles_usage']='<p>Upload all the files that you would like to share. This includes data files (in any format), questionnaires, other survey instruments, and descriptions of methodology, program files and any reports. Once the files are uploaded please use the Edit (blue font) link below to define the file or document type appropriate for that resource. At a minimum the data files and questionnaire should be provided.</p>';
$lang['instructions_citations']='<p>If you have published work that use the dataset being deposited you can enter the citation information for them in this section. These references will be added to the display page for your study once published in the Microdata Library catalogs.</p>';

$lang['anlyUnit']="<p>Basic unit(s) of analysis or observation that the study describes: individuals, families/households, groups, facilities, institutions/organizations, administrative units, physical locations, etc.</p>
<p>Examples:</p>
<p>
<ul>
<li> A living standards survey with community-level questionnaire would have the following units of analysis: individuals, households, and communities.</li>
<li> An economic survey could have the firm and establishment as units of analysis.</li></ul>
</p>";
$lang['dataKind']="<p>This field is a broad classification of the data and it is associated with a drop down box providing a controlled vocabulary. </p>";
$lang['keyword']="<p>Keywords summarize the content or subject matter of the survey. As topic classifications, these are used to facilitate referencing and searches in survey catalogs. </p>";
$lang['scope_notes']="<p>The scope is a description of the themes covered by the survey. It can be viewed as a summary of the modules that are included in the questionnaire. The scope does not deal with geographic coverage. </p>
<p>Example:</p>
<p>The scope of the Multiple Indicator Cluster Survey includes:</p>
<ul>
<li> HOUSEHOLD: Household characteristics, household listing, orphaned and vulnerable children, education, child labour, water and sanitation, household use of insecticide treated mosquito nets, and salt iodization, with optional modules for child discipline, child disability, maternal mortality and security of tenure and durability of housing.</li>
<li>WOMEN: Women's characteristics, child mortality, tetanus toxoid, maternal and newborn health, marriage, polygyny, female genital cutting, contraception, and HIV/AIDS knowledge, with optional modules for unmet need, domestic violence, and sexual behavior.</li>
<li>CHILDREN: Children's characteristics, birth registration and early learning, vitamin A, breastfeeding, care of illness, malaria, immunization, and anthropometry, with an optional module for child development.</li>
</ul>";

$lang['topcClas']="<p>A topic classification facilitates referencing and searches in electronic survey catalogs. </p> <p>As Microdata Library has not formulated a standard topic list yet this filed may be left blank</p>";

$lang['nation']='<p>Enter the country name, even in cases where the survey did not cover the entire country. In the field "Abbreviation", we recommend that you enter the 3-letter ISO code of the country. If the dataset you document covers more than one country, enter all in separate rows.</p>';
$lang['geogCover']='<p>This field aims at describing the geographic coverage of the sample. Typical entries will be "National coverage", "Urban (or rural) areas only", "State of ...", "Capital city", etc. </p>';

$lang['country_universe']="<p>We are interested here in the survey universe (not the universe of particular sections of the questionnaires or variables), i.e. in the identification of the population of interest in the survey. The universe will rarely be the entire population of the country. Sample household surveys, for example, usually do not cover homeless, nomads, diplomats, community households.  Some surveys may cover only the population of a particular age group, or only male (or female), etc.</p>";
$lang['AuthEnty']="<p>The primary investigator is the institution (or in some cases the individual(s)) who was in charge of the design and implementation of the survey (not its funding or technical assistance). </p>
<p>The order in which they are listed is discretionary. It can be alphabetic or by significance of contribution. </p>";
$lang['AuthEnty_iaffiliation']="";
$lang['producers']="<p>Abbreviation, Affiliation and Role. If any of the fields are not applicable these can be left blank. The abbreviations should be the official abbreviation of the organization. </p> 
<p>The role should be a short and succinct phrase or description on the specific assistance provided by the organization in order to produce the data. </p>
<p>Examples of roles: </p>
<ul>
<li> [Technical assistance in] questionnaire design</li>
<li>[Technical assistance in] sampling methodology / selection</li>
<li>[Technical assistance in] data collection</li>
<li>[Technical assistance in] data processing</li>
<li>[Technical assistance in] data analysis</li>
<p>Do not include the financial sponsors here.</p>
";
$lang['fundAg']="<p>List the organizations (national or international) that have contributed, in cash or in kind, to the financing of the survey. </p>";
$lang['othId_p']="<p>This optional field can be used to acknowledge any other people and institutions that have in some form contributed to the survey. </p>";
$lang['sampProc']="<p>This field only applies to sample surveys. Information on sampling procedure is crucial (although not applicable for censuses and administrative datasets). This section should include summary information that includes though is not limited to:</p>
<ul>
<li>Sample size</li>
<li>Selection process (e.g., probability proportional to size or over sampling)</li>
<li>Stratification (implicit and explicit)</li>
<li>Stages of sample selection)</li>
<li> Design omissions in the sample)</li>
<li>Level of representation)</li>
<li> Strategy for absent respondents/not found/refusals (replacement or not) )</li>
<li> Sample frame used, and listing exercise conducted to update it)</li>
<p>It is useful also to indicate here what variables in the data files identify the various levels of stratification and the primary sample unit. These are crucial to the data users who want to properly account for the sampling design in their analyses and calculations of sampling errors. </p>
<p>This section accepts only text format; formulae cannot be entered. In most cases, technical documents will exist that describe the sampling strategy in detail. In such cases, include here a reference (title/author/date) to this document, and make sure that the document is uploaded in the data files and other resources section. </p>";
$lang['deviat']="<p>This field only applies to sample surveys.</p>
<p>Sometimes the reality of the field requires a deviation from the sampling design (for example due to difficulty to access to zones due to weather problems, political instability, etc). If for any reason, the sample design has deviated, this should be reported here. </p>
";
$lang['respRate']='<p>Response rate provides that percentage of households (or other sample unit) that participated in the survey based on the original sample size. Omissions may occur due to refusal to participate, impossibility to locate the respondent, or other.  Sometimes, a household may be replaced by another by design. Check that the information provided here is consistent with the sample size indicated in the "Sampling procedure" field and the number of records found in the dataset (for example, if the sample design mention a sample of 5,000 households and the data on contain data on 4,500 households, the response rate should not be 100 percent).</p>
<p>Provide if possible the response rates by stratum. If information is available on the causes of non-response (refusal/not found/other), provide this information as well.</p>
<p>This field can also in some cases be used to describe non-responses in population censuses.</p>';
$lang['weight']="This field only applies to sample surveys or census samples.
<p>Provide here the list of variables used as weighting coefficient. If more than one variable is a weighting variable, describe how these variables differ from each other and what the purpose of each one of them is. </p>
<p>Example:</p>
<p>Sample weights were calculated for each of the data files.</p>
</p>Sample weights for the household data were computed as the inverse of the probability of selection of the household, computed at the sampling domain level (urban/rural within each region). The household weights were adjusted for non-response at the domain level, and were then normalized by a constant factor so that the total weighted number of households equals the total unweighted number of households. The household weight variable is called HHWEIGHT and is used with the HH data and the HL data.</p>
<p>Sample weights for the women's data used the un-normalized household weights, adjusted for non-response for the women's questionnaire, and were then normalized by a constant factor so that the total weighted number of women's cases equals the total unweighted number of women's cases.</p>
<p>Sample weights for the children's data followed the same approach as the women's and used the un-normalized household weights, adjusted for non-response for the children's questionnaire, and were then normalized by a constant factor so that the total weighted number of children's cases equals the total unweighted number of children's cases.</p>
";
$lang['collDate']='<p>Enter the dates (at least month and year) of the start and end of the data collection. </p>
<p>In some cases, data collection for a same survey can be conducted in waves. In such case, you should enter the start and end date of each wave separately, and identify each wave in the "cycle" field. </p>';
$lang['timePrd']="<p>This field will usually be left empty. Time period differs from the dates of collection as they represent the period for which the data collected are applicable or relevant. </p>";
$lang['collMode']='<p>The mode of data collection is the manner in which the interview was conducted or information was gathered. This field is a controlled vocabulary field. </p>
<p>Use the drop-down button to select one option. In most cases, the response will be "face to face interview". But for some specific kinds of datasets, such as for example data on rain fall, the response will be different.</p>';
$lang['collSitu']="<p>This element is provided in order to document any specific observations, occurrences or events during data collection. Consider stating such items like:</p>
<ul>
<li>Was a training of enumerators held? (Elaborate)</li>
<li>Any events that could have a bearing on the data quality? </li>
<li>How long did an interview take on average? </li>
<li> Was there a process of negotiation between households, the community and the implementing agency? </li>
<li>  Are anecdotal events recorded? </li>
<li>  Have the field teams contributed by supplying information on issues and occurrences during data collection? </li>
<li>  In what language was the interview conducted? </li>
<li>  Was a pilot survey conducted? </li>
<li>  Were there any corrective actions taken by management when problems occurred in the field? </li>
</ul>
<p>Example:<p>
<p>The pre-test for the survey took place from August 15, 2006 - August 25, 2006 and included 14 interviewers who would later become supervisors for the main survey.</p>
<p>Each interviewing team comprised of 3-4 female interviewers (no male interviewers were used due to the sensitivity of the subject matter), together with a field editor and a supervisor and a driver. A total of 52 interviewers, 14 supervisors and 14 field editors were used. Data collection took place over a period of about 6 weeks from September 2, 2006 until October 17, 2006. Interviewing took place everyday throughout the fieldwork period, although interviewing teams were permitted to take one day off per week. </p>
<p>Interviews averaged 35 minutes for the household questionnaire (excluding salt testing), 23 minutes for the women's questionnaire, and 27 for the under five children's questionnaire (excluding the anthropometry).  Interviews were conducted primarily in English and Mumbo-jumbo, but occasionally used local translation in double-Dutch, when the respondent did not speak English or Mumbo-jumbo.</p>
<p>Six staff members of GenCenStat provided overall fieldwork coordination and supervision.  The overall field coordinator was Mrs. Doe.</p>";
$lang['resInstru']="<p>This element is provided to describe the questionnaire(s) used for the data collection. The following should be mentioned :</p>
<ul>
<li>List of questionnaires and short description of each (all questionnaires must be provided as External Resources)</li>
<li> In what language were the questionnaires published? </li>
<li> Information on the questionnaire design process (based on a previous questionnaire, based on a standard model questionnaire, review by stakeholders). If a document was compiled that contains the comments provided by the stakeholders on the draft questionnaire, or a report prepared on the questionnaire testing, a reference to these documents should be provided here and the documents should be provided as External Resources. </li>
</ul>
<p>Example:</p>
<p>The questionnaires for the Generic MICS were structured questionnaires based on the MICS3 Model Questionnaire with some modifications and additions. A household questionnaire was administered in each household, which collected information on household members including sex, age, relationship, and orphanhood status. The household questionnaire includes household characteristics, support to orphaned and vulnerable children, education, child labor, water and sanitation, household use of insecticide treated mosquito nets, and salt iodization, with optional modules for child discipline, child disability, maternal mortality and security of tenure and durability of housing.</p>
<p>In addition to a household questionnaire, questionnaires were administered in each household for women age 15-49 and children under age five. For children, the questionnaire was administered to the mother or caretaker of the child. </p>
<p>The women's questionnaire include women's characteristics, child mortality, tetanus toxoid, maternal and newborn health, marriage, polygyny, female genital cutting, contraception, and HIV/AIDS knowledge, with optional modules for unmet need, domestic violence, and sexual behavior.</p>
<p>The children's questionnaire includes children's characteristics, birth registration and early learning, vitamin A, breastfeeding, care of illness, malaria, immunization, and anthropometry, with an optional module for child development.</p>
<p>The questionnaires were developed in English from the MICS3 Model Questionnaires, and were translated into Mumbo-jumbo. After an initial review the questionnaires were translated back into English by an independent translator with no prior knowledge of the survey. The back translation from the Mumbo-jumbo version was independently reviewed and compared to the English original. Differences in translation were reviewed and resolved in collaboration with the original translators.</p>
<p>The English and Mumbo-jumbo questionnaires were both piloted as part of the survey pretest.</p>
<p>All questionnaires and modules are provided as external resources.</p>";
$lang['dataCollector']="<p>This element is provided in order to record information regarding the persons and/or agencies that took charge of the data collection. This element includes 3 fields: Name, Abbreviation and the Affiliation. In most cases, we will record here the name of the agency, not the name of interviewers. Only in the case of very small-scale surveys, with a very limited number of interviewers, the name of person will be included as well. The field Affiliation is optional and not relevant in all cases.</p>";
$lang['cleanOps']='<p>The data editing should contain information on how the data was treated or controlled for in terms of consistency and coherence. This item does not concern the data entry phase but only the editing of data whether manual or automatic. </p>
<ul>
<li>Was a hot deck or a cold deck technique used to edit the data?</li>
<li> Were corrections made automatically (by program), or by visual control of the questionnaire? </li>
<li>What software was used?  </li>
</ul>
<p>If materials are available (specifications for data editing, report on data editing, programs used for data editing), they should be listed here and uploaded in the data files and other resources section. </p>
<p>Example:</p>
<p>Data editing took place at a number of stages throughout the processing, including :</p>
<ol>
<li>Office editing and coding</li>
<li>During data entry</li>
<li>Structure checking and completeness</li>
<li>Secondary editing</li>
<li>Structural checking of SPSS data files</li>
</ol>
<p>Detailed documentation of the editing of data can be found in the "Data processing guidelines" document provided as an external resource.</p>';
$lang['method_notes']="<p>Use this field to provide as much information as possible on the data entry design. This includes such details as:</p>
<ul>
<li>Mode of data entry (manual or by scanning, in the field/in regions/at headquarters)</li>
<li>Computer architecture (laptop computers in the field, desktop computers, scanners, PDA, other; indicate the number of computers used) </li>
<li> Software used </li>
<li>Use (and rate) of double data entry </li>
<li>Average productivity of data entry operators; number of data entry operators involved and their work schedule</li>
</ul>
<p>Information on tabulation and analysis can also be provided here. </p>
<p>All available materials (data entry/tabulation/analysis programs; reports on data entry) should be listed here and provided as external resources.</p>
<p>Example :</p>
<p>Data were processed in clusters, with each cluster being processed as a complete unit through each stage of data processing.  Each cluster goes through the following steps:</p>
<ol>
<li> Questionnaire reception</li>
<li> Office editing and coding</li>
<li> Data entry</li>
<li> Structure and completeness checking</li>
<li> Verification entry</li>
<li> Comparison of verification data</li>
<li> Back up of raw data</li>
<li> Secondary editing</li>
<li> Edited data backup</li>
<p>After all clusters are processed, all data is concatenated together and then the following steps are completed for all data files:</p>
<li> Export to SPSS in 4 files (hh - household, hl - household members, wm - women, ch - children under 5)</li>
<li> Recoding of variables needed for analysis</li>
<li> Adding of sample weights</li>
<li> Calculation of wealth quintiles and merging into data</li>
<li> Structural checking of SPSS files</li>
<li> Data quality tabulations</li>
<li> Production of analysis tabulations</li>
</ol>
<p>Details of each of these steps can be found in the data processing documentation, data editing guidelines, data processing programs in CSPro and SPSS, and tabulation guidelines.</p>
<p>Data entry was conducted by 12 data entry operators in tow shifts, supervised by 2 data entry supervisors, using a total of 7 computers (6 data entry computers plus one supervisors' computer).  All data entry was conducted at the GenCenStat head office using manual data entry.  For data entry, CSPro version 2.6.007 was used with a highly structured data entry program, using system controlled approach that controlled entry of each variable.  All range checks and skips were controlled by the program and operators could not override these.  A limited set of consistency checks were also included in the data entry program.  In addition, the calculation of anthropometric Z-scores was also included in the data entry programs for use during analysis. Open-ended responses (\"Other\" answers) were not entered or coded, except in rare circumstances where the response matched an existing code in the questionnaire. </p>  
<p>Structure and completeness checking ensured that all questionnaires for the cluster had been entered, were structurally sound, and that women's and children's questionnaires existed for each eligible woman and child. </p>
<p>100% verification of all variables was performed using independent verification, i.e. double entry of data, with separate comparison of data followed by modification of one or both datasets to correct keying errors by original operators who first keyed the files. </p>
<p>After completion of all processing in CSPro, all individual cluster files were backed up before concatenating data together using the CSPro file concatenate utility.</p>
<p>For tabulation and analysis SPSS versions 10.0 and 14.0 were used.  Version 10.0 was originally used for all tabulation programs, except for child mortality.  Later version After transferring all files to SPSS, certain variables were recoded for use as background characteristics in the tabulation of the data, including grouping age, education, geographic areas as needed for analysis.  In the process of recoding ages and dates some random imputation of dates (within calculated constraints) was performed to handle missing or \"don't know\" ages or dates.  Additionally, a wealth (asset) index of household members was calculated using principal components analysis, based on household assets, and both the score and quintiles were included in the datasets for use in tabulations.</p>
";
$lang['EstSmpErr']="<p>For sampling surveys, it is good practice to calculate and publish sampling error. This field is used to provide information on these calculations. This includes:</p>
<ul>
<li>A list of ratios/indicators for which sampling errors were computed. </li>
<li> Details regarding the software used for computing the sampling error, and reference to the programs used (to be provided as external resources) as the program used to perform the calculations.</li>
<li>Reference to the reports or other document where the results can be found (to be provided as external resources). </li>
</ul>
<p>Example:</p>
<p>Estimates from a sample survey are affected by two types of errors: 1) non-sampling errors and 2) sampling errors. Non-sampling errors are the results of mistakes made in the implementation of data collection and data processing.  Numerous efforts were made during implementation of the 2005-2006 MICS to minimize this type of error, however, non-sampling errors are impossible to avoid and difficult to evaluate statistically.</p>
<p>If the sample of respondents had been a simple random sample, it would have been possible to use straightforward formulae for calculating sampling errors.  However, the 2005-2006 MICS sample is the result of a multi-stage stratified design, and consequently needs to use more complex formulae. The SPSS complex samples module has been used to calculate sampling errors for the 2005-2006 MICS.  This module uses the Taylor linearization method of variance estimation for survey estimates that are means or proportions. This method is documented in the SPSS file CSDescriptives.pdf found under the Help, Algorithms options in SPSS. </p>
</p>Sampling errors have been calculated for a select set of statistics (all of which are proportions due to the limitations of the Taylor linearization method) for the national sample, urban and rural areas, and for each of the five regions.  For each statistic, the estimate, its standard error, the coefficient of variation (or relative error -- the ratio between the standard error and the estimate), the design effect, and the square root design effect (DEFT -- the ratio between the standard error using the given sample design and the standard error that would result if a simple random sample had been used), as well as the 95 percent confidence intervals (+/-2 standard errors).</p>
<p>Details of the sampling errors are presented in the sampling errors appendix to the report and in the sampling errors table presented in the external resources.</p>
";
$lang['dataAppr']="<p>This section can be used to report any other action taken to assess the reliability of the data, or any observations regarding data quality. This item can include:</p>
<ul>
<li>For a population census, information on the post enumeration survey (a report should be provided\uploaded in data files and other resources section and mentioned here). </li>
<li>For any survey/census, a comparison with data from another source.</li>
<li> Etc.</li>
<p>Example:</p>
<p>A series of data quality tables and graphs are available to review the quality of the data and include the following :</p>
<ul>
<li>Age distribution of the household population</li>
<li> Age distribution of eligible women and interviewed women</li>
<li> Age distribution of eligible children and children for whom the mother or caretaker was interviewed</li>
<li> Age distribution of children under age 5 by 3 month groups</li>
<li>Age and period ratios at boundaries of eligibility</li>
<li> Percent of observations with missing information on selected variables</li>
<li> Presence of mother in the household and person interviewed for the under 5 questionnaire</li>
<li> School attendance by single year age</li>
<li> Sex ratio at birth among children ever born, surviving and dead by age of respondent</li>
<li> Distribution of women by time since last birth</li>
<li> Scatter plot of weight by height, weight by age and height by age</li>
<li> Graph of male and female population by single years of age</li>
<li> Population pyramid</li>
</ul>
<p>The results of each of these data quality tables are shown in the appendix of the final report and are also given in the external resources section.<p>
<p>The general rule for presentation of missing data in the final report tabulations is that a column is presented for missing data if the percentage of cases with missing data is 1% or more. Cases with missing data on the background characteristics (e.g. education) are included in the tables, but the missing data rows are suppressed and noted at the bottom of the tables in the report (not in the SPSS output, however).</p>
";
$lang['useStmt_contact']="<p>This section is composed of various sections: Name-Affiliation-email-URI. This information provides the contact person or entity to gain authority to access the data. It is advisable to use a generic email contact such as microdata@worldbank.org  whenever possible to avoid tying access to a particular individual whose functions may change over time.<p/>";
$lang['confDec']="<p>If the dataset is not anonymized, we may indicate here what Affidavit of Confidentiality must be signed before the data can be accessed. Another option is to include this information in the next element (Access conditions). If there is no confidentiality issue, this field can be left blank.</p>";
$lang['conditions']="<p>Each dataset should have an 'Access policy' attached to it. The Microdata Library recommends one of the following levels of data accessibility :</p>
<ul>
<li>Public use files, accessible to all.</li>
<li> Licensed datasets, accessible under conditions and following review.</li>
<li>Data available from an external repository </li>
</ul>
<p>The World Bank has formulated standard, generic policies and access forms for each one of these access types. </p> <p>The World Bank Terms of use for data in the external public catalog can be viewed <a href=http://microdata.worldbank.org/index.php/terms-of-use”  target=”_blank”>here </a> and the Terms of use for the inernal staff catalog can be viewed <a href=”http://microdatalib.worldbank.org/index.php/terms “ target=”_blank”>here.</a></p>
";
$lang['citReq']="<p>Citation requirement is the way that the dataset should be referenced when cited in any publication. Every dataset should have a citation requirement. This will guarantee that the data producer gets proper credit, and that analytical results can be linked to the proper version of the dataset. The Access Policy should explicitly mention the obligation to comply with the citation requirement. The citation should include at least the primary investigator, the name and abbreviation of the dataset, the reference year, and the version number. Include also a website where the data or information on the data is made available by the official data depositor.</p>
<p><small>Sarah Baird, George Washington University, Craig McIntosh, University of California San Diego, Berk Ozler, World Bank. Tanzania Second Social Action Fund (TASAF II) - Vulnerable Groups Impact Evaluation - Round I, Household Survey 2008, Ref. TZA_2008_TASAF-II_v01_M_v01_A_PUF. Dataset downloaded from [URL] on [date]</small></p>.
";
$lang['disclaimer']="<p>Example: The user of the data acknowledges that the original collector of the data, the authorized distributor of the data, and the relevant funding agency bear no responsibility for use of the data or for interpretations or inferences based upon such uses. </p>";
$lang['copyright']="<p>Include here a copyright statement on the dataset, such as:</p>
 <p>(c) 2007, The World Bank</p>
";

$lang['impact_wb_name_help'] ='<p>The name of the corresponding WB impact evaluation code or, if the impact evaluation does not have a separate WB code, then the title/name under which this impact evaluation is known</p>';

$lang['impact_wb_id_help'] = '<p>The corresponding WB impact evaluation code (e.g., P012345). Type “N/A” if this impact evaluation does not have a separate WB code)</p>';

$lang['impact_wb_lead_help'] ='<p>The WB TTL(s) and/or lead consultant(s)/researcher(s)</p>';

$lang['impact_wb_members_help'] ='<p>Other WB staff or researchers/consultants who worked on this impact evaluation</p>';

$lang['impact_wb_description_help'] ='<p>The name of the corresponding WB impact evaluation code or, if the impact evaluation does not have a separate WB code, then the title/name under which this impact evaluation is known.</p>';

$lang['operational_wb_name_help'] = '<p>The name of the WB operation to which this impact evaluation is linked</p>';

$lang['operational_wb_id_help'] = '<p>The project code (e.g., P012345) of the related operation</p>';

$lang['operational_wb_summary_help'] ='<p>Provide a summary/overview of the operation and the project development objectives</p>';

$lang['distStmt_contact']="<p>Users of the data may need further clarification and information. This section may include the name-affiliation-email-URI of one or multiple contact persons. Avoid putting the name of individuals. The information provided here should be valid for the long term. It is therefore preferable to identify contact persons by a title. The same applies for the email field. Ideally, a 'generic' email address should be provided. It is easy to configure a mail server in such a way that all messages sent to the generic email address would be automatically forwarded to some staff members.</p>";

$lang['catalog_to_publish_help'] = '<p>Choose external if you intend to share your data with the public. Choose Internal if you intend to only share with Bank staff. Studies on the external catalog are automatically also included in the internal catalog</p>';

$lang['is_embargoed_help'] = '<p>If this study is not to be released to Bank staff or to the public for a period of time i.e. an embargo on release exists then check this box. Please indicate in the box below the time period and the conditions of embargo</p>';

$lang['disclosure_risk_help'] ='<p>Safeguarding the identity and privacy of respondents that have provided the data is important.  If the data files you are depositing contain any identifying variables (e.g., names, official ID numbers, precise GIS coordinates, etc) or any variables deemed otherwise sensitive in your study setting (e.g., ethnicity, tribe, religion, etc) please list these below so that access to these can be suppressed.</p>';

$lang['notes_to_library_help'] ='Enter any additional notes or instruction here.';

$lang['notes_to_embargoed_help'] = '<p>indicate the terms of the embargo e.g. time period and any other special instructions.</p>';


$lang['cc_help'] = '<p>Enter the email addresses of additional people you would like to receive a summary copy of your submission.</p>';

$lang['suggested_access_policy_help'] = '<p>Select the access policy suitable for your data. For more detail see:  the following terms of use for the internal catalog <a href="http://microdatalib.worldbank.org/index.php/terms">http://microdatalib.worldbank.org/index.php/terms</a>  and for the external catalog see the following terms of use <a href="http://microdata.worldbank.org/index.php/terms-of-use">http://microdata.worldbank.org/index.php/terms-of-use</a></p>';

$lang['study_help'] = 'Please complete the fields in each of the sections below. Providing detailed information here will speed up the process of publishing the study. It  also makes it easier for users of the data to find the information they need and thus lessen the need for users to contact the data producer for clarification. Only three fields are mandatory for the submission process.  If time or information available  does not allow for the completion of all fields then we request that at least the mandatory and recommended fields be completed.';
// create page

$lang['create_title'] = 'Provide the full title of your project.';
$lang['create_short'] = 'Provide a short acronym for your project. (e.g., UZB HBS 1998)';
$lang['create_collab'] = 'Provide the email address of other Bank staff who may be authorized to edit this project.';
$lang['create_desc'] = 'Provide a detailed description for your project.';
$lang['help_subtitle']='Provide a short subtitle for your survey.';
$lang['section_identification']='Identification';
$lang['section_version']='Version';
$lang['no_files_uploaded']='No files uploaded';
$lang['no_citations_attached']='No citations';




/* End of file help_lang.php */
/* Location: ./application/language/english/help_lang.php */