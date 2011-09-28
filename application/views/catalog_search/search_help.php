<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head>

  
  <style>
h3{margin:0px;padding:0px;margin-top:15px;font-size:14px;}
h4{margin:0px;padding:0px;margin-top:25px;font-size:14px;}
p{ margin-top:0px;padding-top:0px;}
td{font-size:12px;}
body{background:white;margin:10px;}
body, td {font-family:Arial, Helvetica, sans-serif;font-size:12px}
.box1{margin-top:10px;background-color:#D3EEF8;padding:5px;margin-bottom:10px;font-weight:bold;font-size:12px}
table{border-collapse:collapse;}
  </style></head><body>
<div style="text-align: right;"> <a target="_blank" href="<?php echo site_url();?>/catalog/help/print"><img src="images/print.gif" border="0"> Print</a></div>

<h1> Data Catalog Search Help </h1>

<p>The data catalog window displays a list of all studies available in
the catalog.</p>

<div class="box1">Filter Options: Country and Topic</div>

<p><img src="images/search_box_help.png" alt="Search Box "></p>

<p>If activated on your site, it is possible to filter the catalog to
only display the country/region and or topics you are interested in.</p>

<p><strong>To filter by country or region</strong>: </p>

<p>Click on the bar labeled Countries in the Search dialog box to
expand it. </p>

<p><img src="images/country_selection_help.png" alt="Country Filter"></p>

<ul>

  <li>Select the countries you want to display in the catalog by
clicking on the box\boxes next to your country\countries of interest.
The filter is automatically applied as you click.</li>
  <li> To select all countries click on <em><strong>select all</strong></em>
in the top right of the Countries dialog box.</li>
  <li> To clear your selection click on <em><strong>clear</strong></em>
in the top right top right of the Countries dialog box.</li>
  <li>To switch your selection from those countries selected to those
not selected click on <em><strong>toggle</strong></em></li>
  <li>The number of studies available in the catalog for each country
is shown in brackets next to the country name.</li>
</ul>

<p><strong>To filter by Topic:</strong></p>

<p>Click on the bar labeled Topics in the Search dialog box to expand
it. </p>

<p><img src="images/topics_selection_help.png" alt="Filter by Topic"></p>

<ul>

  <li>Select the topic\topics you want to display in the catalog by
clicking on the box\boxes next to your topic\topics of interest. The
filter is automatically applied as you click.</li>
  <li>To select all topics click on <em><strong>select all</strong></em>
in the top right of the Topics dialog box.</li>
  <li> To clear your selection click on <em><strong>clear</strong></em>
in the top right top right of the Topics dialog box.</li>
  <li>The number of studies available in the catalog for each Topic is
shown in brackets next to the Topic category.</li>
</ul>

<p><strong>Note: </strong>You may combine both Country and Topic
filters at the same time by expanding both Country and Topic select
dialog boxes and making the relevant selections as described above.</p>

<div class="box1">Search Options: In Study and or Variable Description</div>

<p><img src="images/search_main_box_help.png" alt="Search Box"></p>

<ul>

  <li>To filter your results by the year of the study, select the start
and end years from the drop down boxes: <img src="images/year_search_help.png" alt="Year Filter"></li>
  <li>To search for keywords in the study description and display the
studies containing those keywords, fill in your keywords in the first
text box. For example: Title, Primary Investigator,Funder, Series,
Study Abbreviation, would be relevant in this field.</li>
  <li>To search for keywords in the study variable description and
display the studies containing those keywords, fill in your keywords in
the second text box. For example: gender, labor, health, age, house,
employed, weight, height, expenditure, income etc.</li>
  <li>To limit this search in variable description to only the <em><strong>variable
name</strong></em>, <em><strong>label</strong></em>, <em><strong>question</strong></em>
or <em><strong>classification</strong></em><strong></strong>, tick one
of the boxes below the second text box.</li>
  <li>Initiate the search by clicking on the <img src="images/search.png" alt="search"> button. To start a new search
from the beginning click on the <img src="images/reset.png"> button.</li>
</ul>

<div class="box1">Search Tips</div>

<table border="1" cellpadding="0" cellspacing="0">
  <col width="84"> <col width="1790"> <tbody>
    <tr>
      <td width="65"><strong>Operator</strong></td>
      <td width="715"><strong>Meaning</strong></td>
    </tr>
    <tr>
      <td><strong>space</strong></td>
      <td>By default spaces between search terms indicate the word is
optional. It implies <strong>OR</strong></td>
    </tr>
    <tr>
      <td><strong>+</strong></td>
      <td>A leading plus sign stands for <strong>AND</strong></td>
    </tr>
    <tr>
      <td><strong>-</strong></td>
      <td>A leading minus sign stands for <strong>NOT</strong></td>
    </tr>
    <tr>
      <td><strong>( )</strong></td>
      <td>Parentheses (brackets) are put around sub-expressions to give
them higher precedence in the search.</td>
    </tr>
    <tr>
      <td><strong>*</strong></td>
      <td>An asterisk is the truncation operator. Unlike the other
operators, it is appended to the word (put at end), or fragment, not
prepended (put at beginning).</td>
    </tr>
    <tr>
      <td><strong>""</strong></td>
      <td>Double quotes at the beginning and end of a phrase, matches
only rows that contain the complete phrase, as it was typed.</td>
    </tr>
  </tbody>
</table>

<p><br>
<strong>Examples: </strong></p>

<ul>

  <li>Multiple keywords listed in a field separated by <strong>spaces</strong>
will yield search results containing all studies that meet any of the
typed criteria. A list of keywords will therefore be treated as an <strong>OR</strong>
statement . For example: Listing <strong>gender age</strong> in the
variable description will show all studies that have either gender or
age in their variable description.<br>
    <br>
  </li>
  <li>Placing (prepending) a <strong>+</strong> sign to the beginning
of a word treats the search as an<strong> AND</strong> statement. For
example:<strong> Gender +Age +Employed</strong> will limit results to
studies that contain all the elements, Gender and Age and Employed in
them.<br>
    <br>
  </li>
  <li>Placing (prepending) a <strong>-</strong> sign to the beginning
of a word treats the search as a<strong> NOT statement.</strong> For
example:<strong> Gender +Age +Employed -Kenya</strong> will limit
results to studies that contain all the elements Gender and Age and
Employed, but exclude results for Kenya from the results.<br>
    <br>
  </li>
  <li>Placing quotation marks <strong>""</strong> around a search term
will force the term to be evaluated as one term. For example: to limit
a search to study descriptions that contain the exact sequence of words<em>
health study</em> in them one would type <strong> <em>"</em>Health
Study<em>"</em> </strong>between quotation marks. Without the
quotation marks the search would show all studies with health or study
in them. With the quotation marks only studies with the exact wording
in the sequence <em>health study</em> will be shown.<br>
    <br>
  </li>
  <li>Substituting letters in words or phrases with a wildcard <strong>asterisk
*</strong> is allowed. For example searching for heat<strong>*</strong>
would yield results for heating, heated, heater.<br>
    <br>
  </li>
  <li>The wildcard<strong> ? </strong>may also be used in searches to
substitute for letters. For example: searching for <strong>employ?</strong>
would yield results for employ or employed.<br>
    <br>
  </li>
</ul>

<div class="box1">Displaying the variable description search results</div>

<p>After running the search a list of studies will be displayed which
contain the keywords you searched on. A count is given of the number of
variables in the study which meet your search criteria <img src="images/keyword_count_help.png" alt="Keyword count">. Clicking on
this line will list all those variables.</p>

<p><img src="images/keyword_list_help.png" alt="Keyword List"></p>

<p>Clicking on the <img src="images/question.png" alt="question">icon
will display more details about that variable in a new box. To generate
a PDF of the results click on the <img src="images/pdf.png" alt="PDF">icon
or to print the results click on the <img src="images/printer.png" alt="print">icon.</p>

<p>To close this window click on the X in the top right hand corner of
the box.</p>

<p><img src="images/variable_expand_help.png" alt="Variable Expand"></p>

<div class="box1">Comparing variables</div>

<p>To compare or list the details of variables from your search
results. Select the variables you want to compare and then click on <em><strong>Compare</strong></em><strong></strong>
in the top right of your search results window.</p>

<p><img src="images/compare_help.png" alt="compare"></p>

<p>A box will open displaying your comparison.</p>

<p><img src="images/compare_results_help.png" alt="Compare Results"></p>

<p>You may change the order of the variable descriptions by simply
dragging the variable box to your desired location. Do this by holding
the left mouse button down and dragging the content with your mouse at
the same time. Printing the contents of the compare box as well as
generating a PDF document is also possible. Click on the relevant text
at the top of the box to print or generate a PDF.</p>

<div class="box1">Sort results by</div>

<p>Click on the field name to sort the catalog display by <strong>Title</strong>,
<strong>Year </strong>or <strong>Country</strong>. The first click
sorts the result in ascending order; on a second click the sort is
performed in descending order. The up/down arrow indicates the
direction of the sort. </p>

<p><img src="images/sort_title_year_country.png" alt="Sort results by"></p>

<p>These icons have the following meaning; please note that of the
three types of icons available for data access, only one can be used
for any given study:</p>

<table border="0" cellpadding="3" cellspacing="2" width="100%">

  <tbody>
    <tr>
      <td><img src="images/page_white_cd.png" border="0"></td>
      <td>Displays the metadata from the DDI file </td>
    </tr>
    <tr>
      <td><img src="images/page_white_key.png" border="0"></td>
      <td>Displays the data access policy (in html)</td>
    </tr>
    <tr>
      <td><img src="images/form_public.gif" border="0"></td>
      <td>Opens the form for requesting access to public use data files</td>
    </tr>
    <tr>
      <td><img src="images/form_licensed.gif" border="0"></td>
      <td>Opens the form for requesting access to licensed data files</td>
    </tr>
    <tr>
      <td><img src="images/form_enclave.gif" border="0"></td>
      <td>Opens the form to be filled for requesting access to data in
the data enclave</td>
    </tr>
    <tr>
      <td><img src="images/report.png" border="0"></td>
      <td>Displays the survey reports and analytical outputs</td>
    </tr>
    <tr>
      <td><img src="images/page_white_database.png" border="0"></td>
      <td>Opens a database web site where results of the study are
published</td>
    </tr>
    <tr>
      <td><img src="images/page_question.png" border="0"></td>
      <td>Displays the survey questionnaire</td>
    </tr>
    <tr>
      <td><img src="images/page_white_compressed.png" border="0"></td>
      <td>Displays document providing technical documentation of the
survey </td>
    </tr>
    <tr>
      <td><img src="images/page_white_world.png" border="0"></td>
      <td>Opens a survey web site (provides access to questionnaires,
reports, and others) </td>
    </tr>
  </tbody>
</table>

</body></html>