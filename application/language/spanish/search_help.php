<?php 
$lang['keyword_search_help']="<table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;table-grid grid-table&quot;>
 <tbody>
 <tr>
 <td width=&quot;65&quot;> <strong> Operador </ strong> </ td>
 <td width=&quot;715&quot;> <strong> Significado </ strong> </ td>
 </ Tr>
 <tr>
 <td> <strong> espacio </ strong> </ td>
 <td>En forma predeterminada los espacios entre los términos de búsqueda indican que la palabra es
 opcional. Esto implica <strong> O </ strong> </ td>
 </ Tr>
 <tr>
 <td> <strong> + </ strong> </ td>
 <td> Un signo de mas es sinónimo de <strong> Y </ strong> </ td>
 </ Tr>
 <tr>
 <td> <strong> - </ strong> </ td>
 <td> Un signo de menos significa <strong> NO </ strong> </ td>
 </ Tr>
 <tr>
 <td> <strong> () </ strong> </ td>
 <td> Los paréntesis se ponen al comienzo y fin de sub-expresiones para dar a
 ellas mayor precedencia en la búsqueda. </ td>
 </ Tr>
 <tr>
 <td> <strong> * </ strong> </ td>
 <td> El asterisco es el operador para truncar. A diferencia de los otros
 operadores, éste se añade al final de la palabra, o fragmento de texto, no
 al principio. </ td>
 </ Tr>
 <tr>
 <td> <strong> &quot;&quot; </ strong> </ td>
 <td> Las comillas dobles al principio y al final de una frase, permiten hacer una búsqueda de la frase completa, tal cual fue escrita. </ td>
 </ Tr>
 </ Tbody>
 </ Table>

 <p> <br>
 <strong>Ejemplos: </ strong> </ p>

 <ul>

 <li>Varias palabras clave ingresadas en un campo separadas por <strong>espacios</ strong>
 producirá como resultado de la búsqueda todos los estudios que cumplan cualquiera de los
 criterios escritos. Una lista de palabras clave, por lo tanto, será tratado como un operador <strong> O </ strong>
 . Por ejemplo: Si se escribe <strong> género edad </ strong> en la
 descripción de la variable se mostrarán todos los estudios que tengan la palabra género o
 edad en su descripción de la variable. <br>
 <br>
 </ Li>
 <li> Si se coloca (anteponiendo) el signo <strong> + </ strong> al principio
 de una palabra, se tratará la búsqueda como un operador <strong> Y </ strong>. Por
 Ejemplo: <strong> +Género +Edad +Ocupado </ strong> se limitarán los resultados a
 estudios que contienen todos los elementos, Género y Edad y Ocupado en
 ellos. <br>
 <br>
 </ Li>
 <li> Si se coloca (anteponiendo) el signo <strong> - </ strong> al principio
 de una palabra, se tratará la búsqueda como un operador <strong> NO </ strong>. Por
 ejemplo: <strong>+Género +Edad +Ocupado -Uruguay</ strong> se limitarán
 resultados a los estudios que contienen todos los elementos: Género y Edad y
 Ocupado, pero excluyen los estudios de Uruguay de los resultados de la búsqueda. <br>
 <br>
 </ Li>
 <li> Si se ponen comillas dobles <strong> &quot;&quot; </ strong> en torno a una frase de búsqueda
 se forzará que la frase sea evaluada como un sólo término. Por ejemplo: para limitar
 una búsqueda en las descripciones de estudios que contienen la secuencia exacta de palabras: 
 <em> Encuesta de Salud </ em> se debería escribir: <strong> <em> &quot;</ em> Encuesta de Salud <em> &quot;</ em> </ strong> 
 entre comillas dobles. Sin las
 comillas la búsqueda mostraría todos los estudios con las palabras Encuesta o de o Salud
 en sus descripciones. Con las comillas sólo se mostrará los estudios con la secuencia exacta de palabras
 <em> Encuesta de Salud </ em>. <br>
 <br>
 </ Li>
 <li> Es posible sustituir letras en las palabras o frases por un comodín: <strong> asterisco 
 * </ Strong>. Por ejemplo, la búsqueda de calor <strong> * </ strong>
 mostraría estudios que contienen las palabras calefacción, calentador, calentador. <br>
 <br>
 </ Li>
 <li> El comodín <strong> ? </ Strong> también se puede utilizar en las búsquedas para
 sustituir letras. Por ejemplo: la búsqueda de <strong> ocupado? </ strong>
 produciría resultados para ocupado u ocupados. <br>
 <br>
 </ Li>
 </ Ul>";


/* End of file search_help.php */
/* Location: ./application/language/spanish/search_help.php */