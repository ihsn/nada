<?php 
$lang['keyword_search_help']="<table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;table-grid grid-table&quot;>
<tbody>
<tr>
<td width=&quot;65&quot;><strong>Operateur logique</strong></td>
<td width=&quot;715&quot;><strong>Description</strong></td>
</tr>
<tr>
<td><strong>space</strong></td>
<td>Par défaut, un espace entre deux termes à rechercher indique que l'un des
deux mots est facultatif et équivaut à l'opérateur logique <strong>OR</strong></td>
</tr>
<tr>
<td><strong>+</strong></td>
<td>Le signe plus correspond à l'opérateur logique <strong>ET</strong></td>
</tr>
<tr>
<td><strong>-</strong></td>
<td>Le signe moins correspond à l'opérateur logique <strong>NON</strong></td>
</tr>
<tr>
<td><strong>( )</strong></td>
<td>Les parenthèses entourent les groupes de mots à rechercher en priorité.</td>
</tr>
<tr>
<td><strong>*</strong></td>
<td>L'astérisque est un opérateur de troncature. Contrairement aux autres opérateurs, il est placé à la fin d'un terme ou d'une racine de mot et non au début.</td>
</tr>
<tr>
<td><strong>&quot;&quot;</strong></td>
<td>Placés au début et à la fin d'une phrase, les guillemets permettent
de limiter les résultats de la recherche aux contenus qui comprennent la phrase complète telle qu'elle a été saisie.</td>
</tr>
</tbody>
</table>

<p><br>
<strong>Exemples : </strong></p>

<ul>

<li>Lorsque plusieurs mots-clés séparés par un <strong>espace</strong> sont saisis dans un champ, les résultats de la recherche contiennent toutes les enquêtes répondant à l'un des critères spécifiés. Une liste de mots-clés sera donc traitée avec l'opérateur logique <strong>OU</strong>. Exemple : en spécifiant <strong>sexe âge</strong> comme critères, les résultats de la recherche comprendront toutes les enquêtes dont la description des variables contient les termes &quot;sexe&quot; ou &quot;âge&quot;.<br>
<br>
</li>
<li>Lorsque le signe <strong>+</strong> est placé au début d'un mot, la recherche est traitée avec l'opérateur logique <strong> ET</strong>. Exemple : les critères<strong> +Sexe +Age +Emploi</strong> limiteront les résultats aux enquêtes dans lesquelles apparaissent tous les éléments spécifiés (sexe, âge et emploi).<br>
<br>
</li>
<li>Lorsque le signe <strong>-</strong> est placé au début
d'un mot, la recherche est traitée avec l'opérateur logique<strong> NON.</strong> 
Exemple : les critères<strong> +Sexe +Age +Emploi -Kenya</strong> limiteront
les résultats aux enquêtes contenant tous les éléments spécifiés, à l'exception de celles concernant le Kenya.<br>
<br>
</li>
<li>Lorsque des guillemets <strong>&quot;&quot;</strong>  entourent un critère de recherche,
ce dernier sera considéré comme un seul terme. Exemple : pour limiter la recherche aux descriptions d'enquête contenant précisément la séquence de mots <em>étude sur la santé</em>, il faut taper <strong> <em>&quot;</em>étude sur la santé<em>&quot;</em> </strong>entre guillemets. Sans les guillemets, les résultats de la recherche comprendront toutes les enquêtes contenant les mots santé et étude. 
En ajoutant les guillemets, seules les enquêtes comprenant la séquence <em>étude sur la santé</em> seront affichées.<br>
<br>
</li>
<li>Il est possible de remplacer certaines lettres d'un mot ou d'une phrase par le caractère générique <strong>astérisque *</strong>. Exemple : en tapant chauf<strong>*</strong>, les résultats obtenus contiendront les mots chauffage, chauffer et chauffé.<br>
<br>
</li>
<li>Le caractère générique<strong> ? </strong>peut aussi servir de caractère de remplacement. Exemple: taper <strong>employ?</strong> pour afficher les résultats contenant employeur ou employé.<br>
<br>
</li>
</ul>";


/* End of file search_help_lang.php */
/* Location: ./application/language/french/search_help_lang.php */