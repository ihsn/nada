<?php 
$lang['keyword_search_help']="<table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;table-grid grid-table&quot;>
<tbody>
<tr>
<td width=&quot;65&quot;><strong>Operator</strong></td>
<td width=&quot;715&quot;><strong>Meaning</strong></td>
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
<td><strong>&quot;&quot;</strong></td>
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
example:<strong> +Gender +Age +Employed</strong> will limit results to
studies that contain all the elements, Gender and Age and Employed in
them.<br>
<br>
</li>
<li>Placing (prepending) a <strong>-</strong> sign to the beginning
of a word treats the search as a<strong> NOT statement.</strong> For
example:<strong> +Gender +Age +Employed -Kenya</strong> will limit
results to studies that contain all the elements Gender and Age and
Employed, but exclude results for Kenya from the results.<br>
<br>
</li>
<li>Placing quotation marks <strong>&quot;&quot;</strong> around a search term
will force the term to be evaluated as one term. For example: to limit
a search to study descriptions that contain the exact sequence of words<em>
health study</em> in them one would type <strong> <em>&quot;</em>Health
Study<em>&quot;</em> </strong>between quotation marks. Without the
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
</ul>";


/* End of file search_help.php */
/* Location: ./application/language/chinese/search_help.php */