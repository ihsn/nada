<?php

$lang['keyword_search_help'] = '<table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;table-grid grid-table&quot;>
 <tbody>
    <tr>
      <td width=&quot;65&quot;><strong>Operador</strong></td>
      <td width=&quot;715&quot;><strong>Significado</strong></td>
    </tr>
    <tr>
      <td><strong>espaço</strong></td>
      <td>Por predefinição, os espaços entre os termos tornam cada palavra opcional. Equivalem a <strong>OU</strong>.</td>
    </tr>
    <tr>
      <td><strong>+</strong></td>
      <td>Um sinal de mais no início representa <strong>E</strong>.</td>
    </tr>
    <tr>
      <td><strong>-</strong></td>
      <td>Um sinal de menos no início representa <strong>NÃO</strong>.</td>
    </tr>
    <tr>
      <td><strong>( )</strong></td>
      <td>Os parênteses agrupam subexpressões para lhes dar maior precedência na pesquisa.</td>
    </tr>
    <tr>
      <td><strong>*</strong></td>
      <td>O asterisco é o operador de truncagem. Ao contrário dos restantes operadores, é colocado no fim de uma palavra ou de um fragmento, e não no início.</td>
    </tr>
    <tr>
      <td><strong>&quot;&quot;</strong></td>
      <td>As aspas no início e no fim de uma expressão seleccionam apenas os registos que contêm a expressão completa, tal como foi escrita.</td>
    </tr>
  </tbody>
</table>
<p><br><strong>Exemplos: </strong></p>
<ul>
  <li>As palavras-chave separadas por <strong>espaços</strong> encontram estudos que correspondam a qualquer um dos critérios, equivalendo a <strong>OU</strong>. Por exemplo, <strong>género idade</strong> encontra estudos cuja descrição de variáveis contenha género ou idade.<br><br></li>
  <li>Um sinal <strong>+</strong> antes de uma palavra equivale a <strong>E</strong>. Por exemplo, <strong>+género +idade +emprego</strong> limita os resultados aos estudos que contenham os três termos.<br><br></li>
  <li>Um sinal <strong>-</strong> antes de uma palavra equivale a <strong>NÃO</strong>. Por exemplo, <strong>+género +idade +emprego -Quénia</strong> encontra os três termos e exclui resultados relativos ao Quénia.<br><br></li>
  <li>As aspas <strong>&quot;&quot;</strong> agrupam uma expressão exacta. Por exemplo, <strong>&quot;estudo de saúde&quot;</strong> encontra apenas descrições com essa sequência exacta de palavras. Sem aspas, a pesquisa encontra estudos que contenham qualquer um dos termos.<br><br></li>
  <li>O asterisco <strong>*</strong> pode substituir letras no fim de uma palavra. Por exemplo, <strong>trabalh*</strong> pode encontrar trabalho, trabalhador e trabalhadores.<br><br></li>
  <li>O comportamento do carácter <strong>?</strong> depende do motor de pesquisa configurado.<br><br></li>
</ul>';
