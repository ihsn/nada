<?php 
$lang['titl']="<pre>
The title is the official name of the survey as it is stated on the questionnaire or as it appears in the design documents. The following items should be noted:

- Include the reference year(s) of the survey in the title.
- Do not include the abbreviation of the survey name in the title.
- As the survey title is a proper noun, the first letter of each word should be capitalized (except for prepositions or other conjunctions).
- Including the country name in the title is optional.
</pre>
";
$lang['altTitl']="<pre>
The abbreviation of a survey is usually the first letter of each word of the titled survey. The survey reference year(s) may be included.

Examples:  
- DHS 2000 for 'Demographic and Health Survey 2005'
- HIES 2002-2003 for 'Household Income and Expenditure Survey 2003'
</pre>
";
$lang['serName']="<pre>
The study type or survey type is the broad category defining the survey. 
This item has a controlled vocabulary (you may customize the IHSN template to adjust this controlled vocabulary if needed).
</pre>
";
$lang['serInfo']="<pre>
A survey may be repeated at regular intervals (such as an annual labour force survey), or be part of an international survey program (such as the MICS, CWIQ, DHS, LSMS and others). The Series information is a description of this 'collection' of surveys. A brief description of the characteristics of the survey, including when it started, how many rounds were already implemented, and who is in charge would be provided here. If the survey does not belong to a series, leave this field empty.
Example:
The Multiple Indicator Cluster Survey, Round 3 (MICS3) is the third round of MICS surveys, previously conducted around 1995 (MICS1) and 2000 (MICS2).  MICS surveys are designed by UNICEF, and implemented by national agencies in participating countries. MICS was designed to monitor various indicators identified at the World Summit for Children and the Millennium Development Goals. 
Many questions and indicators in MICS3 are consistent and compatible with the prior round of MICS (MICS2) but less so with MICS1, although there have been a number of changes in definition of indicators between rounds. 
Round 1 covered X countries, round 2 covered Y countries, and Round Z covered
</pre>
";
$lang['parTitl']="<pre>
In countries with more than one official language, a translation of the title may be provided. Likewise, the translated title may simply be a translation into English from a country's own language. Special characters should be properly displayed (such as accents and other stress marks or different alphabets).
</pre>
";
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
The version description should contain a version number followed by a version label. The version number should follow a standard convention to be adopted by the institute. We recommend that larger series be defined by a number to the left of a decimal and iterations of the same series by a sequential number that identifies the release. Larger series will typically include (0) the raw, unedited dataset; (1) the edited dataset, non anonymized, for internal use at the data producing agency; and (2) the edited dataset, prepared for dissemination to secondary users (possibly anonymized). 

Examples:
- v0.1:  Basic raw data, obtained from data entry (before editing).
- v1.2:  Edited data, second version, for internal use only.
- v2.1:  Edited, anonymous dataset for public distribution.

A brief description of the version should follow the numerical identification.
</pre>
";
$lang['version_idate']="<pre>
This is the date in ISO format (yyyy-mm-dd) of actual and final production of the data. Production dates of all versions should be carefully tracked. Provide at least the month and year. Use the calendar icon in the Metadata editor to assure that the date selected is in compliance with the ISO format.
</pre>
";
$lang['version_notes']="<pre>
Version notes should provide a brief report on the changes made through the versioning process. The note should indicate how this version differs from other versions of the same dataset.
</pre>
";
$lang['overview_abstract']="<pre>
The abstract should provide a clear summary of the purposes, objectives and content of the survey. It should be written by a researcher or survey statistician aware of the survey.
</pre>
";

$lang['instructions_project_submit']='--insert help message here--';
$lang['instructions_datafiles_usage']='--insert help message here--';
$lang['instructions_citations']='--insert help message here--';

$lang['anlyUnit']="<pre>
Basic unit(s) of analysis or observation that the study describes: individuals, families/households, groups, facilities, institutions/organizations, administrative units, physical locations, etc.

Examples:
- A living standards survey with community-level questionnaire would have the following units of analysis: individuals, households, and communities.
- An economic survey could have the firm and establishment as units of analysis.
</pre>
";
$lang['dataKind']="<pre>
This field is a broad classification of the data and it is associated with a drop down box providing controlled vocabulary. That controlled vocabulary includes 9 items but is not limited to them.
</pre>
";
$lang['keyword']="<pre>
Keywords summarize the content or subject matter of the survey. As topic classifications, these are used to facilitate referencing and searches in electronic survey catalogs. 
Keywords should be selected from a standard thesaurus, preferably an international, multilingual thesaurus. Entering a list of keywords is tedious. This option is provided for advanced users only.
</pre>
";
$lang['scope_notes']="<pre>
The scope is a description of the themes covered by the survey. It can be viewed as a summary of the modules that are included in the questionnaire. The scope does not deal with geographic coverage. 

Example:
The scope of the Multiple Indicator Cluster Survey includes:
- HOUSEHOLD: Household characteristics, household listing, orphaned and vulnerable children, education, child labour, water and sanitation, household use of insecticide treated mosquito nets, and salt iodization, with optional modules for child discipline, child disability, maternal mortality and security of tenure and durability of housing.
- WOMEN: Women's characteristics, child mortality, tetanus toxoid, maternal and newborn health, marriage, polygyny, female genital cutting, contraception, and HIV/AIDS knowledge, with optional modules for unmet need, domestic violence, and sexual behavior.
- CHILDREN: Children's characteristics, birth registration and early learning, vitamin A, breastfeeding, care of illness, malaria, immunization, and anthropometry, with an optional module for child development.
</pre>
";
$lang['topcClas']="<pre>
A topic classification facilitates referencing and searches in electronic survey catalogs. Topics should be selected from a standard thesaurus, preferably an international, multilingual thesaurus. 
The IHSN recommends the use of the thesaurus used by the Council of European Social Science Data Archives (CESSDA). The CESSDA thesaurus has been introduced as a controlled vocabulary in the IHSN Study Template version 1.3 (available at www.surveynetwork.org/toolkit <http://www.surveynetwork.org/toolkit>).
</pre>
";
$lang['nation']="<pre>
Enter the country name, even in cases where the survey did not cover the entire country. In the field &quot;Abbreviation&quot;, we recommend that you enter the 3-letter ISO code of the country. If the dataset you document covers more than one country, enter all in separate rows.
</pre>
";
$lang['geogCover']="<pre>
This filed aims at describing at what geographic level the data are representative. Typical entries will be &quot;National coverage&quot;, &quot;Urban (or rural) areas only&quot;, &quot;State of ...&quot;, &quot;Capital city&quot;, etc. 
Note that we do not describe here where the data was collected. For example, as sample survey could be declared as &quot;national coverage&quot; even in cases where some districts where not included in the sample, as long as the sampling strategy was such that the representativity is national. 
</pre>
";
$lang['country_universe']="<pre>
We are interested here in the survey universe (not the universe of particular sections of the questionnaires or variables), i.e. in the identification of the population of interest in the survey. The universe will rarely be the entire population of the country. Sample household surveys, for example, usually do not cover homeless, nomads, diplomats, community households. Population censuses do not cover diplomats. Try to provide the most detailed information possible on the population covered by the survey/census.

Example:
The survey covered all de jure household members (usual residents), all women aged 15-49 years resident in the household, and all children aged 0-4 years (under age 5) resident in the household.
</pre>
";
$lang['AuthEnty']="<pre>



The primary investigator will in most cases be an institution, but could also be an individual in the case of small-scale academic surveys. The two fields to be completed are the Name and the Affiliation fields. 







Generally, in a survey, the Primary Investigator will be the institution implementing the survey. If various institutions have been equally involved as main investigators, then all should be mentioned. This only includes the agencies responsible for the implementation of the survey, not its funding or technical assistance. 







The order in which they are listed is discretionary. It can be alphabetic or by significance of contribution. Individual persons can also be mentioned. If persons are mentioned use the appropriate format of Surname, First name. 



</pre>



";
$lang['AuthEnty_iaffiliation']="<pre>



</pre>



";
$lang['producers']="<pre>



Abbreviation, Affiliation and Role. If any of the fields are not applicable these can be left blank. The abbreviations should be the official abbreviation of the organization.  







The role should be a short and succinct phrase or description on the specific assistance provided by the organization in order to produce the data. 







The roles should be standard vocabulary such as:



- [Technical assistance in] questionnaire design



- [Technical assistance in] sampling methodology / selection



- [Technical assistance in] data collection



- [Technical assistance in] data processing



- [Technical assistance in] data analysis







Do not include here the financial sponsors.



</pre>



";
$lang['fundAg']="<pre>



List the organizations (national or international) that have contributed, in cash or in kind, to the financing of the survey. 







The government institution that has provided funding should not be forgotten.



</pre>



";
$lang['othId_p']="<pre>



This optional field can be used to acknowledge any other people and institutions that have in some form contributed to the survey. 



</pre>



";
$lang['sampProc']="<pre>



This field only applies to sample surveys. Information on sampling procedure is crucial (although not applicable for censuses and administrative datasets). This section should include summary information that includes though is not limited to:



- Sample size



- Selection process (e.g., probability proportional to size or over sampling)



- Stratification (implicit and explicit)



- Stages of sample selection



- Design omissions in the sample



- Level of representation



- Strategy for absent respondents/not found/refusals (replacement or not) 



- Sample frame used, and listing exercise conducted to update it







It is useful also to indicate here what variables in the data files identify the various levels of stratification and the primary sample unit. These are crucial to the data users who want to properly account for the sampling design in their analyses and calculations of sampling errors. 







This section accepts only text format; formulae cannot be entered. In most cases, technical documents will exist that describe the sampling strategy in detail. In such cases, include here a reference (title/author/date) to this document, and make sure that the document is provided in the External Resources. 







Example:







5000 households were selected for the sample. Of these, 4996 were occupied households and 4811 were successfully interviewed for a response rate of 96.3%.  Within these households, 7815 eligible women aged 15-49 were identified for interview, of which 7505 were successfully interviewed (response rate 96.0%), and 3242 children aged 0-4 were identified for whom the mother or caretaker was successfully interviewed for 3167 children (response rate 97.7%). These give overall response rates (household response rate times individual response rate) for the women's interview of 92.5% and for the children's interview of 94.1%.



</pre>



";
$lang['deviat']="<pre>



This field only applies to sample surveys.







Sometimes the reality of the field requires a deviation from the sampling design (for example due to difficulty to access to zones due to weather problems, political instability, etc). If for any reason, the sample design has deviated, this should be reported here. 



</pre>



";
$lang['respRate']="<pre>



Response rate provides that percentage of households (or other sample unit) that participated in the survey based on the original sample size. Omissions may occur due to refusal to participate, impossibility to locate the respondent, or other.  Sometimes, a household may be replaced by another by design. Check that the information provided here is consistent with the sample size indicated in the &quot;Sampling procedure&quot; field and the number of records found in the dataset (for example, if the sample design mention a sample of 5,000 households and the data on contain data on 4,500 households, the response rate should not be 100 percent).







Provide if possible the response rates by stratum. If information is available on the causes of non-response (refusal/not found/other), provide this information as well.







This field can also in some cases be used to describe non-responses in population censuses.



</pre>



";
$lang['weight']="<pre>



This field only applies to sample surveys.







Provide here the list of variables used as weighting coefficient. If more than one variable is a weighting variable, describe how these variables differ from each other and what the purpose of each one of them is. 







Example:







Sample weights were calculated for each of the data files.



Sample weights for the household data were computed as the inverse of the probability of selection of the household, computed at the sampling domain level (urban/rural within each region). The household weights were adjusted for non-response at the domain level, and were then normalized by a constant factor so that the total weighted number of households equals the total unweighted number of households. The household weight variable is called HHWEIGHT and is used with the HH data and the HL data.



Sample weights for the women's data used the un-normalized household weights, adjusted for non-response for the women's questionnaire, and were then normalized by a constant factor so that the total weighted number of women's cases equals the total unweighted number of women's cases.



Sample weights for the children's data followed the same approach as the women's and used the un-normalized household weights, adjusted for non-response for the children's questionnaire, and were then normalized by a constant factor so that the total weighted number of children's cases equals the total unweighted number of children's cases.



</pre>



";
$lang['collDate']="<pre>



Enter the dates (at least month and year) of the start and end of the data collection. 







DATE MUST BE ENTERED IN THE ISO FORMAT YYYY-MM-DD







In some cases, data collection for a same survey can be conducted in waves. In such case, you should enter the start and end date of each wave separately, and identify each wave in the &quot;cycle&quot; field. 



</pre>



";
$lang['timePrd']="<pre>



This field will usually be left empty. Time period differs from the dates of collection as they represent the period for which the data collected are applicable or relevant. 







NOTE: DATE MUST BE ENTERED IN THE FORMAT YYYY-MM-DD



</pre>



";
$lang['collMode']="<pre>



The mode of data collection is the manner in which the interview was conducted or information was gathered. This field is a controlled vocabulary field. 







Use the drop-down button in the Toolkit to select one option. In most cases, the response will be &quot;face to face interview&quot;. But for some specific kinds of datasets, such as for example data on rain falls, the response will be different.



</pre>



";
$lang['collSitu']="<pre>



This element is provided in order to document any specific observations, occurrences or events during data collection. Consider stating such items like:



- Was a training of enumerators held? (elaborate)



- Any events that could have a bearing on the data quality?



- How long did an interview take on average?



- Was there a process of negotiation between households, the community and the implementing agency?



- Are anecdotal events recorded?



- Have the field teams contributed by supplying information on issues and occurrences during data collection? 



- In what language was the interview conducted?



- Was a pilot survey conducted? 



- Were there any corrective actions taken by management when problems occurred in the field?







Example:







The pre-test for the survey took place from August 15, 2006 - August 25, 2006 and included 14 interviewers who would later become supervisors for the main survey.







Each interviewing team comprised of 3-4 female interviewers (no male interviewers were used due to the sensitivity of the subject matter), together with a field editor and a supervisor and a driver. A total of 52 interviewers, 14 supervisors and 14 field editors were used. Data collection took place over a period of about 6 weeks from September 2, 2006 until October 17, 2006. Interviewing took place everyday throughout the fieldwork period, although interviewing teams were permitted to take one day off per week. 







Interviews averaged 35 minutes for the household questionnaire (excluding salt testing), 23 minutes for the women's questionnaire, and 27 for the under five children's questionnaire (excluding the anthropometry).  Interviews were conducted primarily in English and Mumbo-jumbo, but occasionally used local translation in double-Dutch, when the respondent did not speak English or Mumbo-jumbo.







Six staff members of GenCenStat provided overall fieldwork coordination and supervision.  The overall field coordinator was Mrs. Doe.



</pre>



";
$lang['resInstru']="<pre>



This element is provided to describe the questionnaire(s) used for the data collection. The following should be mentioned:



- List of questionnaires and short description of each (all questionnaires must be provided as External Resources)



- In what language were the questionnaires published?



- Information on the questionnaire design process (based on a previous questionnaire, based on a standard model questionnaire, review by stakeholders). If a document was compiled that contains the comments provided by the stakeholders on the draft questionnaire, or a report prepared on the questionnaire testing, a reference to these documents should be provided here and the documents should be provided as External Resources.







Example:







The questionnaires for the Generic MICS were structured questionnaires based on the MICS3 Model Questionnaire with some modifications and additions. A household questionnaire was administered in each household, which collected various information on household members including sex, age, relationship, and orphanhood status. The household questionnaire includes household characteristics, support to orphaned and vulnerable children, education, child labour, water and sanitation, household use of insecticide treated mosquito nets, and salt iodization, with optional modules for child discipline, child disability, maternal mortality and security of tenure and durability of housing.







In addition to a household questionnaire, questionnaires were administered in each household for women age 15-49 and children under age five. For children, the questionnaire was administered to the mother or caretaker of the child. 







The women's questionnaire include women's characteristics, child mortality, tetanus toxoid, maternal and newborn health, marriage, polygyny, female genital cutting, contraception, and HIV/AIDS knowledge, with optional modules for unmet need, domestic violence, and sexual behavior.







The children's questionnaire includes children's characteristics, birth registration and early learning, vitamin A, breastfeeding, care of illness, malaria, immunization, and anthropometry, with an optional module for child development.







The questionnaires were developed in English from the MICS3 Model Questionnaires, and were translated into Mumbo-jumbo. After an initial review the questionnaires were translated back into English by an independent translator with no prior knowledge of the survey. The back translation from the Mumbo-jumbo version was independently reviewed and compared to the English original. Differences in translation were reviewed and resolved in collaboration with the original translators.



The English and Mumbo-jumbo questionnaires were both piloted as part of the survey pretest.







All questionnaires and modules are provided as external resources.



</pre>



";
$lang['dataCollector']="<pre>



This element is provided in order to record information regarding the persons and/or agencies that took charge of the data collection. This element includes 3 fields: Name, Abbreviation and the Affiliation. In most cases, we will record here the name of the agency, not the name of interviewers. Only in the case of very small-scale surveys, with a very limited number of interviewers, the name of person will be included as well. The field Affiliation is optional and not relevant in all cases.







Example:







Name: Central Statistics Office



Abbreviation: CSO



Affiliation: Ministry of Planning 



</pre>



";
$lang['cleanOps']="<pre>



The data editing should contain information on how the data was treated or controlled for in terms of consistency and coherence. This item does not concern the data entry phase but only the editing of data whether manual or automatic. 



- Was a hot deck or a cold deck technique used to edit the data?



- Were corrections made automatically (by program), or by visual control of the questionnaire?



- What software was used?  







If materials are available (specifications for data editing, report on data editing, programs used for data editing), they should be listed here and provided as external resources. 







Example:







Data editing took place at a number of stages throughout the processing, including:



a) Office editing and coding



b) During data entry



c) Structure checking and completeness



d) Secondary editing



e) Structural checking of SPSS data files



Detailed documentation of the editing of data can be found in the &quot;Data processing guidelines&quot; document provided as an external resource.



</pre>



";
$lang['method_notes']="<pre>



Use this field to provide as much information as possible on the data entry design. This includes such details as:



- Mode of data entry (manual or by scanning, in the field/in regions/at headquarters)



- Computer architecture (laptop computers in the field, desktop computers, scanners, PDA, other; indicate the number of computers used)



- Software used 



- Use (and rate) of double data entry 



- Average productivity of data entry operators; number of data entry operators involved and their work schedule







Information on tabulation and analysis can also be provided here. 







All available materials (data entry/tabulation/analysis programs; reports on data entry) should be listed here and provided as external resources.







Example:







Data were processed in clusters, with each cluster being processed as a complete unit through each stage of data processing.  Each cluster goes through the following steps:



1) Questionnaire reception



2) Office editing and coding



3) Data entry



4) Structure and completeness checking



5) Verification entry



6) Comparison of verification data



7) Back up of raw data



8) Secondary editing



9) Edited data back up







After all clusters are processed, all data is concatenated together and then the following steps are completed for all data files:



10) Export to SPSS in 4 files (hh - household, hl - household members, wm - women, ch - children under 5)



11) Recoding of variables needed for analysis



12) Adding of sample weights



13) Calculation of wealth quintiles and merging into data



14) Structural checking of SPSS files



15) Data quality tabulations



16) Production of analysis tabulations



 



Details of each of these steps can be found in the data processing documentation, data editing guidelines, data processing programs in CSPro and SPSS, and tabulation guidelines.







Data entry was conducted by 12 data entry operators in tow shifts, supervised by 2 data entry supervisors, using a total of 7 computers (6 data entry computers plus one supervisors' computer).  All data entry was conducted at the GenCenStat head office using manual data entry.  For data entry, CSPro version 2.6.007 was used with a highly structured data entry program, using system controlled approach that controlled entry of each variable.  All range checks and skips were controlled by the program and operators could not override these.  A limited set of consistency checks were also included in the data entry program.  In addition, the calculation of anthropometric Z-scores was also included in the data entry programs for use during analysis. Open-ended responses (&quot;Other&quot; answers) were not entered or coded, except in rare circumstances where the response matched an existing code in the questionnaire.   







Structure and completeness checking ensured that all questionnaires for the cluster had been entered, were structurally sound, and that women's and children's questionnaires existed for each eligible woman and child. 



100% verification of all variables was performed using independent verification, i.e. double entry of data, with separate comparison of data followed by modification of one or both datasets to correct keying errors by original operators who first keyed the files. 







After completion of all processing in CSPro, all individual cluster files were backed up before concatenating data together using the CSPro file concatenate utility.



For tabulation and analysis SPSS versions 10.0 and 14.0 were used.  Version 10.0 was originally used for all tabulation programs, except for child mortality.  Later version 14.0 was used for child mortality, data quality tabulations and other analysis activities.







After transferring all files to SPSS, certain variables were recoded for use as background characteristics in the tabulation of the data, including grouping age, education, geographic areas as needed for analysis.  In the process of recoding ages and dates some random imputation of dates (within calculated constraints) was performed to handle missing or &quot;don't know&quot; ages or dates.  Additionally, a wealth (asset) index of household members was calculated using principal components analysis, based on household assets, and both the score and quintiles were included in the datasets for use in tabulations.



</pre>



";
$lang['EstSmpErr']="<pre>



For sampling surveys, it is good practice to calculate and publish sampling error. This field is used to provide information on these calculations. This includes:



- A list of ratios/indicators for which sampling errors were computed. 



- Details regarding the software used for computing the sampling error, and reference to the programs used (to be provided as external resources) as the program used to perform the calculations.



- Reference to the reports or other document where the results can be found (to be provided as external resources). 







Example:







Estimates from a sample survey are affected by two types of errors: 1) non-sampling errors and 2) sampling errors. Non-sampling errors are the results of mistakes made in the implementation of data collection and data processing.  Numerous efforts were made during implementation of the 2005-2006 MICS to minimize this type of error, however, non-sampling errors are impossible to avoid and difficult to evaluate statistically.







 If the sample of respondents had been a simple random sample, it would have been possible to use straightforward formulae for calculating sampling errors.  However, the 2005-2006 MICS sample is the result of a multi-stage stratified design, and consequently needs to use more complex formulae. The SPSS complex samples module has been used to calculate sampling errors for the 2005-2006 MICS.  This module uses the Taylor linearization method of variance estimation for survey estimates that are means or proportions. This method is documented in the SPSS file CSDescriptives.pdf found under the Help, Algorithms options in SPSS. 







Sampling errors have been calculated for a select set of statistics (all of which are proportions due to the limitations of the Taylor linearization method) for the national sample, urban and rural areas, and for each of the five regions.  For each statistic, the estimate, its standard error, the coefficient of variation (or relative error -- the ratio between the standard error and the estimate), the design effect, and the square root design effect (DEFT -- the ratio between the standard error using the given sample design and the standard error that would result if a simple random sample had been used), as well as the 95 percent confidence intervals (+/-2 standard errors).







Details of the sampling errors are presented in the sampling errors appendix to the report and in the sampling errors table presented in the external resources.



</pre>



";
$lang['dataAppr']="<pre>



This section can be used to report any other action taken to assess the reliability of the data, or any observations regarding data quality. This item can include:



- For a population census, information on the post enumeration survey (a report should be provided in external resources and mentioned here). 



- For any survey/census, a comparison with data from another source.



- Etc.







Example:







A series of data quality tables and graphs are available to review the quality of the data and include the following:



- Age distribution of the household population



- Age distribution of eligible women and interviewed women



- Age distribution of eligible children and children for whom the mother or caretaker was interviewed



- Age distribution of children under age 5 by 3 month groups



- Age and period ratios at boundaries of eligibility



- Percent of observations with missing information on selected variables



- Presence of mother in the household and person interviewed for the under 5 questionnaire



- School attendance by single year age



- Sex ratio at birth among children ever born, surviving and dead by age of respondent



- Distribution of women by time since last birth



- Scatter plot of weight by height, weight by age and height by age



- Graph of male and female population by single years of age



- Population pyramid



 



The results of each of these data quality tables are shown in the appendix of the final report and are also given in the external resources section.



 



The general rule for presentation of missing data in the final report tabulations is that a column is presented for missing data if the percentage of cases with missing data is 1% or more. Cases with missing data on the background characteristics (e.g. education) are included in the tables, but the missing data rows are suppressed and noted at the bottom of the tables in the report (not in the SPSS output, however).



</pre>



";
$lang['useStmt_contact']="<pre>



This section is composed of various sections: Name-Affiliation-email-URI. This information provides the contact person or entity to gain authority to access the data. It is advisable to use a generic email contact such as data@popstatsoffice.org <mailto:data@popstatsoffice.org> whenever possible to avoid tying access to a particular individual whose functions may change over time.



</pre>



";
$lang['confDec']="<pre>



If the dataset is not anonymized, we may indicate here what Affidavit of Confidentiality must be signed before the data can be accessed. Another option is to include this information in the next element (Access conditions). If there is no confidentiality issue, this field can be left blank.







An example of statement could be the following:



Confidentiality of respondents is guaranteed by Articles N to NN of the National Statistics Act of [date]. 



Before being granted access to the dataset, all users have to formally agree: 



1. To make no copies of any files or portions of files to which s/he is granted access except those authorized by the data depositor. 



2. Not to use any technique in an attempt to learn the identity of any person, establishment, or sampling unit not identified on public use data files. 



3. To hold in strictest confidence the identification of any establishment or individual that may be inadvertently revealed in any documents or discussion, or analysis. Such inadvertent identification revealed in her/his analysis will be immediately brought to the attention of the data depositor.







This statement does not replace a more comprehensive data agreement (see Access condition). 



</pre>



";
$lang['conditions']="<pre>



Each dataset should have an 'Access policy' attached to it. The IHSN recommends three levels of accessibility:



- Public use files, accessible to all



- Licensed datasets, accessible under conditions



- Datasets only accessible in a data enclave, for the most sensitive and confidential data.







The IHSN has formulated standard, generic policies and access forms for each one of these three levels (which each country can customize to its specific needs). One of the three policies may be copy/pasted in this field once it has been edited as needed and approved by the appropriate authority. Before you fill this field, a decision has to be made by the management of the data depositor agency. Avoid writing a specific statement for each dataset. 



If the access policy is subject to regular changes, you should enter here a URL where the user will find detailed information on access policy which applies to this specific dataset. If the datasets are sold, pricing information should also be provided on a website instead of being entered here.







If the access policy is not subject to regular changes, you may enter more detailed information here. For a public use file for example, you could enter information like:



The dataset has been anonymized and is available as a Public Use Dataset. It is  accessible to all for statistical and research purposes only, under the following terms and conditions:



1. The data and other materials will not be redistributed or sold to other individuals, institutions, or organizations without the written agreement of the [National Data Archive]. 



2. The data will be used for statistical and scientific research purposes only. They will be used solely for reporting of aggregated information, and not for investigation of specific individuals or organizations. 



3. No attempt will be made to re-identify respondents, and no use will be made of the identity of any person or establishment discovered inadvertently. Any such discovery would immediately be reported to the [National Data Archive]. 



4. No attempt will be made to produce links among datasets provided by the [National Data Archive], or among data from the [National Data Archive] and other datasets that could identify individuals or organizations. 



5. Any books, articles, conference papers, theses, dissertations, reports, or other publications that employ data obtained from the [National Data Archive] will cite the source of data in accordance with the Citation Requirement provided with each dataset. 



6. An electronic copy of all reports and publications based on the requested data will be sent to the [National Data Archive]. 







The original collector of the data, the [National Data Archive], and the relevant funding agencies bear no responsibility for use of the data or for interpretations or inferences based upon such uses. 



</pre>



";
$lang['citReq']="<pre>



Citation requirement is the way that the dataset should be referenced when cited in any publication. Every dataset should have a citation requirement. This will guarantee that the data producer gets proper credit, and that analytical results can be linked to the proper version of the dataset. The Access Policy should explicitly mention the obligation to comply with the citation requirement. The citation should include at least the primary investigator, the name and abbreviation of the dataset, the reference year, and the version number. Include also a website where the data or information on the data is made available by the official data depositor.







Example:







'National Statistics Office of Popstan, Multiple Indicators Cluster Survey 2000 (MICS 2000), Version 1.1 of the public use dataset (April 2001), provided by the National Data Archive. www.nda_popstan.org'



</pre>



";
$lang['disclaimer']="<p>



A disclaimer limits the liability that the Statistics Office has regarding the use of the data. A standard legal statement should be used for all datasets from a same agency. The IHSN recommends the following formulation:



</p>



<p>



The user of the data acknowledges that the original collector of the data, the authorized distributor of the data, and the relevant funding agency bear no responsibility for use of the data or for interpretations or inferences based upon such uses. 



</p>



";
$lang['copyright']="<pre>



Include here a copyright statement on the dataset, such as:



(c) 2007, Popstan Central Statistics Agency



</pre>



";
$lang['distStmt_contact']="<pre>



Users of the data may need further clarification and information. This section may include the name-affiliation-email-URI of one or multiple contact persons. Avoid putting the name of individuals. The information provided here should be valid for the long term. It is therefore preferable to identify contact persons by a title. The same applies for the email field. Ideally, a 'generic' email address should be provided. It is easy to configure a mail server in such a way that all messages sent to the generic email address would be automatically forwarded to some staff members.







Example:







Name: Head, Data Processing Division



Affiliation: National Statistics Office



Email: dataproc@cso.org



URI: www.cso.org/databank 



</pre>



";


/* End of file help_lang.php */
/* Location: ./application/language/english/help_lang.php */