<?php 
$lang['keyword_search_help']="<table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;table-grid grid-table&quot;>
<tbody>
<tr>
<td width=&quot;65&quot;><strong>运算符</strong></td>
<td width=&quot;715&quot;><strong>含义</strong></td>
</tr>
<tr>
<td><strong>空格</strong></td>
<td>在默认情况下，搜索词之间的空格表示词是可选的。它意味着<strong>或</strong></td>
</tr>
<tr>
<td><strong>+</strong></td>
<td>位于搜索词前的加号代表<strong>和</strong></td>
</tr>
<tr>
<td><strong>-</strong></td>
<td>位于搜索词前的减号代表<strong>排除</strong></td>
</tr>
<tr>
<td><strong>( )</strong></td>
<td>为子表达式放上括号会在搜索中给予他们更高的优先级。</td>
</tr>
<tr>
<td><strong>*</strong></td>
<td>星号是截断运算符。不同于其他的运算符，它附于词后或词中，但不位于词前。</td>
</tr>
<tr>
<td><strong>&quot;&quot;</strong></td>
<td>以双引号开头，以双引号结束的一个短语，在搜索时需要找到写法完全一致的短语。</td>
</tr>
</tbody>
</table>
 
<p><br>
<strong>示例:</strong></p>
 
<ul>

<li>Multiple keywords listed in a field separated by <strong>spaces</strong>
 will yield search results containing all studies that meet any of the typed criteria. A list of keywords will therefore be treated as an <strong>OR</strong>statement . For example: Listing <strong>gender age</strong> in the variable description will show all studies that have either gender or age in their variable description.<br>
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