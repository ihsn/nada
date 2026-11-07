<?php

$lang['show_help_tooltip'] = 'Mostrar ou ocultar toda a ajuda';
$lang['show_fields_tooltip'] = 'Mostrar todos os campos';
$lang['mandatory_recommended_tooltip'] = 'Mostrar apenas campos obrigatórios e recomendados';
$lang['mandatory_only_tooltip'] = 'Mostrar apenas campos obrigatórios';
$lang['save_tooltip'] = 'Guardar descrição do estudo';

$lang['titl'] = '<p>Introduza o título oficial do inquérito, em inglês ou no idioma do inquérito. Inclua o(s) ano(s) de referência, mas NÃO inclua a sigla do inquérito nem o nome do país no título. Escreva com maiúscula a primeira letra de cada palavra, excepto preposições e outras conjunções. Em títulos em francês, inglês, português e outros idiomas, utilize os caracteres acentuados. Exemplo: “Enquête Démographique et de Santé 2008-2009”.</p>';
$lang['altTitl'] = '<p>Introduza a sigla do inquérito, incluindo a inicial do país, se pertinente. Pode incluir o(s) ano(s) de referência. Exemplo: DHS 2008-09.</p>';
$lang['serName'] = '<p>O tipo de estudo ou de inquérito é a categoria geral que define o inquérito. Seleccione uma opção na lista. Seleccione «Outro» se nenhuma opção corresponder ao seu inquérito.</p>';
$lang['serInfo'] = '<p>Um inquérito pode repetir-se regularmente, como um inquérito anual ao emprego, ou fazer parte de um programa internacional, como MICS, CWIQ, DHS ou LSMS. A informação da série descreve esta «colecção» de inquéritos. Apresente uma breve descrição das características do inquérito, indicando quando começou, quantas rondas já foram realizadas e quem é responsável. Se o inquérito não pertencer a uma série, deixe o campo em branco.</p>';
$lang['parTitl'] = '<p>Na maioria dos casos, este campo fica em branco. Em países com mais de um idioma oficial, pode ser apresentada uma tradução do título.</p>';
$lang['IDNo'] = '<pre>
O número de identificação de um conjunto de dados é um número único utilizado para identificar um inquérito. Defina e utilize um esquema consistente. Por exemplo: país-produtor-inquérito-ano-versão, em que:
- país é a abreviatura ISO de três letras do país;
- produtor é a abreviatura da entidade produtora;
- inquérito é a abreviatura do inquérito;
- ano é o ano de referência ou o ano em que o inquérito começou;
- versão é o número da versão do conjunto de dados (ver Descrição da versão abaixo).
</pre>';
$lang['version'] = '<pre>
<p>A descrição da versão deve conter um número de versão seguido de uma descrição. Exemplos:</p>
<ul>
<li>Versão 0.1: dados brutos básicos obtidos da introdução de dados, antes da edição.</li>
<li>Versão 1.2: dados editados, segunda versão, apenas para utilização interna.</li>
<li>Versão 2.1: conjunto de dados editado e anonimizado para distribuição pública.</li>
</ul>
<p>Após a identificação numérica, apresente uma breve descrição da versão.</p>
</pre>';
$lang['version_idate'] = '<p>Data efectiva da produção final dos dados, no formato ISO (aaaa-mm-dd). Indique pelo menos o mês e o ano.</p>';
$lang['version_notes'] = '<p>As notas da versão devem apresentar um breve relatório das alterações efectuadas durante o processo de controlo de versões e indicar como esta versão difere das outras versões do mesmo conjunto de dados.</p>';
$lang['overview_abstract'] = '<p>O resumo deve apresentar claramente as finalidades, os objectivos e o conteúdo do inquérito.</p>';
$lang['instructions_project_submit'] = '<p>Quando tiver confirmado que a informação introduzida para o estudo está correcta, pode submeter o projecto. Seleccione uma política de acesso adequada à distribuição dos dados e o catálogo em que devem ser publicados. Indique quaisquer notas sobre embargos, informação sensível que deva ser removida antes da distribuição e outras notas ou instruções especiais para a Biblioteca de Microdados.</p>';
$lang['instructions_project_contributor_review'] = $lang['instructions_project_submit'];
$lang['instructions_datafiles_usage'] = '<p>Carregue todos os ficheiros que pretende partilhar, incluindo ficheiros de dados em qualquer formato, questionários, outros instrumentos de inquérito, descrições metodológicas, ficheiros de programas e relatórios. Depois do carregamento, utilize a ligação Editar, a azul, para definir o tipo de ficheiro ou documento adequado a cada recurso. Deve fornecer, no mínimo, os ficheiros de dados e o questionário.</p>';
$lang['instructions_citations'] = '<p>Se publicou trabalhos que utilizam o conjunto de dados depositado, pode introduzir aqui as respectivas citações. Estas referências serão adicionadas à página do estudo quando este for publicado nos catálogos da Biblioteca de Microdados.</p>';
$lang['anlyUnit'] = '<p>Unidade(s) básica(s) de análise ou observação descritas pelo estudo: pessoas, famílias ou agregados familiares, grupos, instalações, instituições ou organizações, unidades administrativas, locais físicos, etc.</p>
<p>Exemplos:</p>
<ul>
<li>Um inquérito às condições de vida com um questionário ao nível da comunidade pode ter como unidades de análise pessoas, agregados familiares e comunidades.</li>
<li>Um inquérito económico pode ter como unidades de análise empresas e estabelecimentos.</li>
</ul>';
$lang['dataKind'] = '<p>Este campo apresenta uma classificação geral dos dados e está associado a uma lista com um vocabulário controlado.</p>';
$lang['keyword'] = '<p>As palavras-chave resumem o conteúdo ou o assunto do inquérito. Tal como as classificações temáticas, facilitam a referenciação e a pesquisa nos catálogos de inquéritos.</p>';
$lang['scope_notes'] = '<p>O âmbito descreve os temas abrangidos pelo inquérito e pode ser visto como um resumo dos módulos incluídos no questionário. O âmbito não diz respeito à cobertura geográfica.</p>
<p>Exemplo:</p>
<p>O âmbito do Inquérito por Agrupamentos de Indicadores Múltiplos inclui:</p>
<ul>
<li>AGREGADOS FAMILIARES: características e listagem dos agregados, crianças órfãs e vulneráveis, educação, trabalho infantil, água e saneamento, utilização de mosquiteiros tratados com insecticida e iodização do sal; módulos opcionais sobre disciplina e deficiência infantil, mortalidade materna, segurança da posse e durabilidade da habitação.</li>
<li>MULHERES: características das mulheres, mortalidade infantil, toxóide tetânico, saúde materna e neonatal, casamento, poliginia, mutilação genital feminina, contracepção e conhecimentos sobre VIH/SIDA; módulos opcionais sobre necessidades não satisfeitas, violência doméstica e comportamento sexual.</li>
<li>CRIANÇAS: características das crianças, registo de nascimento e aprendizagem precoce, vitamina A, aleitamento, cuidados em caso de doença, paludismo, imunização e antropometria; módulo opcional sobre desenvolvimento infantil.</li>
</ul>';
$lang['topcClas'] = '<p>Uma classificação temática facilita a referenciação e a pesquisa nos catálogos electrónicos de inquéritos.</p><p>Como a Biblioteca de Microdados ainda não formulou uma lista temática normalizada, este campo pode ficar em branco.</p>';
$lang['nation'] = '<p>Introduza o nome do país, mesmo que o inquérito não tenha abrangido todo o território. No campo «Abreviatura», recomenda-se a introdução do código ISO de três letras. Se o conjunto de dados abranger mais de um país, introduza cada país numa linha separada.</p>';
$lang['geogCover'] = '<p>Este campo descreve a cobertura geográfica da amostra. Exemplos: «Cobertura nacional», «Apenas áreas urbanas» ou «Apenas áreas rurais», «Estado de ...», «Capital», etc.</p>';
$lang['country_universe'] = '<p>Indique o universo do inquérito, isto é, a população de interesse do inquérito, e não o universo de secções ou variáveis específicas dos questionários. O universo raramente corresponde a toda a população do país. Por exemplo, os inquéritos por amostragem aos agregados familiares geralmente não abrangem pessoas sem abrigo, nómadas, diplomatas ou agregados colectivos. Alguns inquéritos podem abranger apenas um grupo etário ou apenas homens ou mulheres.</p>';
$lang['AuthEnty'] = '<p>O investigador principal é a instituição ou, em alguns casos, a pessoa ou pessoas responsáveis pela concepção e execução do inquérito, e não pelo seu financiamento ou assistência técnica.</p><p>A ordem de apresentação é livre: pode ser alfabética ou reflectir a importância da contribuição.</p>';
$lang['AuthEnty_iaffiliation'] = '';
$lang['producers'] = '<p>Indique a abreviatura, a afiliação e a função. Os campos não aplicáveis podem ficar em branco. Utilize a abreviatura oficial da organização.</p>
<p>A função deve ser uma descrição breve e concisa da assistência específica prestada pela organização para produzir os dados.</p>
<p>Exemplos de funções:</p>
<ul>
<li>Assistência técnica na concepção do questionário;</li>
<li>Assistência técnica na metodologia ou selecção da amostra;</li>
<li>Assistência técnica na recolha de dados;</li>
<li>Assistência técnica no processamento de dados;</li>
<li>Assistência técnica na análise de dados.</li>
</ul>
<p>Não inclua aqui os financiadores.</p>';
$lang['fundAg'] = '<p>Liste as organizações nacionais ou internacionais que contribuíram, em dinheiro ou em espécie, para financiar o inquérito.</p>';
$lang['othId_p'] = '<p>Este campo opcional permite agradecer a outras pessoas e instituições que tenham contribuído de alguma forma para o inquérito.</p>';
$lang['sampProc'] = '<p>Este campo aplica-se apenas a inquéritos por amostragem. A informação sobre o procedimento de amostragem é essencial, embora não se aplique a censos e conjuntos de dados administrativos. Inclua, entre outros aspectos:</p>
<ul>
<li>Tamanho da amostra;</li>
<li>Processo de selecção, por exemplo, probabilidade proporcional ao tamanho ou sobreamostragem;</li>
<li>Estratificação implícita e explícita;</li>
<li>Etapas de selecção da amostra;</li>
<li>Omissões do plano de amostragem;</li>
<li>Nível de representatividade;</li>
<li>Estratégia para pessoas ausentes, não encontradas ou que recusaram responder, incluindo eventual substituição;</li>
<li>Base de amostragem utilizada e operação de listagem realizada para a actualizar.</li>
</ul>
<p>Indique também as variáveis dos ficheiros de dados que identificam os vários níveis de estratificação e a unidade primária de amostragem. Estas são essenciais para que os utilizadores considerem correctamente o plano de amostragem nas análises e no cálculo dos erros de amostragem.</p>
<p>Esta secção aceita apenas texto; não é possível introduzir fórmulas. Se existirem documentos técnicos que descrevam a estratégia de amostragem em detalhe, indique a respectiva referência (título, autor e data) e carregue-os na secção de ficheiros de dados e outros recursos.</p>';
$lang['deviat'] = '<p>Este campo aplica-se apenas a inquéritos por amostragem.</p><p>As condições no terreno podem exigir desvios ao plano de amostragem, por exemplo devido a dificuldades de acesso causadas pelo clima ou por instabilidade política. Descreva aqui qualquer desvio, independentemente do motivo.</p>';
$lang['respRate'] = '<p>A taxa de resposta é a percentagem de agregados familiares, ou de outras unidades de amostragem, que participaram no inquérito em relação ao tamanho original da amostra. Podem ocorrer omissões por recusa, impossibilidade de localizar o inquirido ou outros motivos. Em alguns planos, um agregado pode ser substituído por outro. Verifique a coerência com o tamanho indicado em «Procedimento de amostragem» e com o número de registos no conjunto de dados. Por exemplo, se o plano indicar 5 000 agregados e os dados contiverem apenas 4 500, a taxa de resposta não deve ser de 100 por cento.</p>
<p>Se possível, indique as taxas de resposta por estrato e as causas da não resposta, como recusa, pessoa não encontrada ou outros motivos.</p>
<p>Em alguns casos, este campo também pode descrever a não resposta em censos da população.</p>';
$lang['weight'] = '<p>Este campo aplica-se apenas a inquéritos por amostragem ou amostras de censos.</p>
<p>Liste as variáveis utilizadas como coeficientes de ponderação. Se existir mais do que uma variável de ponderação, descreva as diferenças e a finalidade de cada uma.</p>
<p>Exemplo:</p>
<p>Foram calculados pesos de amostragem para cada ficheiro de dados.</p>
<p>Os pesos dos dados dos agregados familiares foram calculados como o inverso da probabilidade de selecção do agregado ao nível do domínio de amostragem, urbano ou rural em cada região. Foram ajustados para a não resposta ao nível do domínio e normalizados por um factor constante, de modo que o número total ponderado de agregados fosse igual ao número total não ponderado. A variável de peso chama-se HHWEIGHT e é utilizada com os dados HH e HL.</p>
<p>Os pesos dos dados das mulheres utilizaram os pesos não normalizados dos agregados, ajustados para a não resposta ao questionário das mulheres e depois normalizados por um factor constante, de modo que o número total ponderado de casos fosse igual ao total não ponderado.</p>
<p>Os pesos dos dados das crianças seguiram a mesma abordagem: utilizaram os pesos não normalizados dos agregados, ajustados para a não resposta ao questionário das crianças e depois normalizados para que o número total ponderado de casos fosse igual ao total não ponderado.</p>';
$lang['collDate'] = '<p>Introduza as datas de início e fim da recolha de dados, indicando pelo menos o mês e o ano.</p><p>Quando a recolha de um mesmo inquérito for realizada em várias rondas, introduza separadamente as datas de início e fim de cada ronda e identifique-a no campo «Ciclo».</p>';
$lang['timePrd'] = '<p>Este campo fica geralmente em branco. O período de referência difere das datas de recolha: representa o período ao qual os dados recolhidos se aplicam ou são relevantes.</p>';
$lang['collMode'] = '<p>O método de recolha de dados é a forma como a entrevista foi realizada ou a informação foi recolhida. Este campo utiliza um vocabulário controlado.</p><p>Seleccione uma opção na lista. Na maioria dos casos, a resposta será «Entrevista presencial», mas poderá ser diferente para conjuntos de dados específicos, como dados sobre precipitação.</p>';
$lang['collSitu'] = '<p>Documente observações, ocorrências ou acontecimentos específicos durante a recolha de dados. Considere, por exemplo:</p>
<ul>
<li>Foi dada formação aos recenseadores? Descreva-a.</li>
<li>Ocorreram acontecimentos que possam afectar a qualidade dos dados?</li>
<li>Quanto tempo durou, em média, uma entrevista?</li>
<li>Houve um processo de negociação entre os agregados familiares, a comunidade e a entidade executora?</li>
<li>Foram registados acontecimentos pontuais relevantes?</li>
<li>As equipas de terreno forneceram informação sobre problemas e ocorrências durante a recolha?</li>
<li>Em que idioma foi realizada a entrevista?</li>
<li>Foi realizado um inquérito-piloto?</li>
<li>A gestão tomou medidas correctivas quando surgiram problemas no terreno?</li>
</ul>
<p>Exemplo:</p>
<p>O pré-teste do inquérito decorreu de 15 a 25 de Agosto de 2006 e incluiu 14 entrevistadoras que seriam posteriormente supervisoras do inquérito principal.</p>
<p>Cada equipa era composta por três ou quatro entrevistadoras, sem entrevistadores homens devido à sensibilidade do tema, juntamente com um editor de campo, um supervisor e um motorista. Participaram 52 entrevistadoras, 14 supervisores e 14 editores de campo. A recolha decorreu durante cerca de seis semanas, de 2 de Setembro a 17 de Outubro de 2006. As entrevistas realizaram-se diariamente, embora as equipas pudessem ter um dia de descanso por semana.</p>
<p>As entrevistas duraram em média 35 minutos para o questionário dos agregados familiares, excluindo o teste ao sal; 23 minutos para o questionário das mulheres; e 27 minutos para o questionário das crianças menores de cinco anos, excluindo a antropometria. Foram realizadas principalmente em inglês e Mumbo-jumbo, com tradução local ocasional em double-Dutch quando a pessoa inquirida não falava inglês nem Mumbo-jumbo.</p>
<p>Seis membros da GenCenStat asseguraram a coordenação e supervisão geral do trabalho de campo. A coordenadora geral foi a Sra. Doe.</p>';
$lang['resInstru'] = '<p>Descreva os questionários utilizados para a recolha de dados, indicando:</p>
<ul>
<li>A lista dos questionários e uma breve descrição de cada um. Todos devem ser fornecidos como recursos externos.</li>
<li>Os idiomas em que os questionários foram publicados.</li>
<li>O processo de concepção, incluindo a utilização de um questionário anterior ou de um modelo normalizado e a revisão pelas partes interessadas. Se existir um documento com os comentários sobre o projecto de questionário ou um relatório dos testes, indique a respectiva referência e forneça os documentos como recursos externos.</li>
</ul>
<p>Exemplo:</p>
<p>Os questionários do MICS genérico eram estruturados e baseavam-se no modelo MICS3, com algumas alterações e adições. Em cada agregado foi aplicado um questionário que recolhia informação sobre os seus membros, incluindo sexo, idade, relação e situação de orfandade. O questionário abrangia características do agregado, apoio a crianças órfãs e vulneráveis, educação, trabalho infantil, água e saneamento, utilização de mosquiteiros tratados com insecticida e iodização do sal, com módulos opcionais sobre disciplina e deficiência infantil, mortalidade materna, segurança da posse e durabilidade da habitação.</p>
<p>Além do questionário do agregado, foram aplicados questionários às mulheres dos 15 aos 49 anos e às crianças menores de cinco anos. Para as crianças, o questionário foi aplicado à mãe ou à pessoa responsável pelos seus cuidados.</p>
<p>O questionário das mulheres abrangia as suas características, mortalidade infantil, toxóide tetânico, saúde materna e neonatal, casamento, poliginia, mutilação genital feminina, contracepção e conhecimentos sobre VIH/SIDA, com módulos opcionais sobre necessidades não satisfeitas, violência doméstica e comportamento sexual.</p>
<p>O questionário das crianças abrangia as suas características, registo de nascimento e aprendizagem precoce, vitamina A, aleitamento, cuidados em caso de doença, paludismo, imunização e antropometria, com um módulo opcional sobre desenvolvimento infantil.</p>
<p>Os questionários foram desenvolvidos em inglês a partir dos modelos MICS3 e traduzidos para Mumbo-jumbo. Após uma primeira revisão, foram novamente traduzidos para inglês por um tradutor independente sem conhecimento prévio do inquérito. A retrotradução foi revista de forma independente e comparada com o original inglês. As diferenças foram analisadas e resolvidas com os tradutores originais.</p>
<p>Os questionários em inglês e em Mumbo-jumbo foram testados no pré-teste do inquérito. Todos os questionários e módulos são fornecidos como recursos externos.</p>';
$lang['dataCollector'] = '<p>Registe informação sobre as pessoas ou entidades responsáveis pela recolha de dados. Este elemento tem três campos: nome, abreviatura e afiliação. Na maioria dos casos, regista-se o nome da entidade e não o das pessoas que fizeram as entrevistas. Só em inquéritos de muito pequena escala, com um número muito limitado de entrevistadores, se incluem também nomes de pessoas. O campo de afiliação é opcional e nem sempre é pertinente.</p>';
$lang['actMin'] = '<p>Resuma as medidas tomadas para minimizar a perda de dados, como visitas de seguimento, verificações de supervisão, correspondência com registos históricos e estimação. Não é necessário apresentar aqui informação detalhada sobre as taxas de resposta, pois existe um elemento de metadados específico para esse efeito.</p>';
$lang['cleanOps'] = '<p>Descreva como os dados foram editados ou verificados quanto à sua consistência e coerência. Este campo refere-se à edição manual ou automática dos dados, e não à sua introdução.</p>
<ul>
<li>Foi utilizada uma técnica hot deck ou cold deck na edição?</li>
<li>As correcções foram automáticas, por programa, ou efectuadas através da verificação visual do questionário?</li>
<li>Que software foi utilizado?</li>
</ul>
<p>Se existirem materiais, como especificações, relatórios ou programas de edição, liste-os aqui e carregue-os na secção de ficheiros de dados e outros recursos.</p>
<p>Exemplo:</p>
<p>Os dados foram editados em várias fases do processamento:</p>
<ol>
<li>Edição e codificação em escritório;</li>
<li>Durante a introdução de dados;</li>
<li>Verificação da estrutura e da integridade;</li>
<li>Edição secundária;</li>
<li>Verificação estrutural dos ficheiros de dados SPSS.</li>
</ol>
<p>A documentação detalhada da edição encontra-se no documento «Orientações para o processamento de dados», fornecido como recurso externo.</p>';
$lang['method_notes'] = '<p>Utilize este campo para apresentar o máximo de informação possível sobre a concepção da introdução de dados, incluindo:</p>
<ul>
<li>Método de introdução, manual ou por digitalização, no terreno, nas regiões ou na sede;</li>
<li>Arquitectura informática, como computadores portáteis no terreno, computadores de secretária, digitalizadores, PDA ou outros, indicando o número de computadores;</li>
<li>Software utilizado;</li>
<li>Utilização e percentagem de dupla introdução de dados;</li>
<li>Produtividade média dos operadores, número de operadores envolvidos e horário de trabalho.</li>
</ul>
<p>Pode também apresentar informação sobre tabulação e análise. Liste todos os materiais disponíveis, incluindo programas de introdução, tabulação e análise e relatórios sobre a introdução, e forneça-os como recursos externos.</p>
<p>Exemplo:</p>
<p>Os dados foram processados por agrupamento, como uma unidade completa em cada fase. Cada agrupamento passou pelas seguintes etapas:</p>
<ol>
<li>Recepção dos questionários;</li>
<li>Edição e codificação em escritório;</li>
<li>Introdução de dados;</li>
<li>Verificação da estrutura e da integridade;</li>
<li>Introdução de verificação;</li>
<li>Comparação dos dados de verificação;</li>
<li>Cópia de segurança dos dados brutos;</li>
<li>Edição secundária;</li>
<li>Cópia de segurança dos dados editados.</li>
</ol>
<p>Após o processamento de todos os agrupamentos, os dados foram concatenados e aplicaram-se as seguintes etapas a todos os ficheiros:</p>
<ol>
<li>Exportação para SPSS em quatro ficheiros: hh para agregados familiares, hl para membros dos agregados, wm para mulheres e ch para crianças menores de cinco anos;</li>
<li>Recodificação das variáveis necessárias à análise;</li>
<li>Adição dos pesos de amostragem;</li>
<li>Cálculo dos quintis de riqueza e junção aos dados;</li>
<li>Verificação estrutural dos ficheiros SPSS;</li>
<li>Tabulações da qualidade dos dados;</li>
<li>Produção de tabulações para análise.</li>
</ol>
<p>Os detalhes encontram-se na documentação de processamento, nas orientações de edição e tabulação e nos programas de processamento em CSPro e SPSS.</p>
<p>A introdução foi realizada manualmente na sede da GenCenStat por 12 operadores em dois turnos, supervisionados por dois responsáveis, utilizando sete computadores: seis para introdução e um para os supervisores. Foi utilizada a versão 2.6.007 do CSPro com um programa muito estruturado que controlava a introdução de cada variável. Todas as verificações de intervalos e saltos eram controladas pelo programa e os operadores não as podiam contornar. O programa incluía também um conjunto limitado de verificações de consistência e o cálculo de pontuações Z antropométricas para análise. As respostas abertas, «Outras», não foram introduzidas nem codificadas, excepto em casos raros em que correspondiam a um código já existente no questionário.</p>
<p>A verificação da estrutura e da integridade garantiu que todos os questionários do agrupamento tinham sido introduzidos, estavam estruturalmente correctos e incluíam os questionários de cada mulher e criança elegíveis.</p>
<p>Todas as variáveis foram verificadas a 100 por cento por verificação independente, isto é, dupla introdução, comparação separada dos dados e alteração de um ou ambos os conjuntos para corrigir erros de digitação dos operadores originais.</p>
<p>Concluído o processamento em CSPro, foi criada uma cópia de segurança de cada ficheiro de agrupamento antes de concatenar os dados com o utilitário de concatenação do CSPro.</p>
<p>Para tabulação e análise foram utilizadas as versões 10.0 e 14.0 do SPSS. A versão 10.0 foi inicialmente utilizada para todos os programas de tabulação, excepto os de mortalidade infantil. Depois de transferidos os ficheiros para SPSS, algumas variáveis foram recodificadas como características de contexto, agrupando idades, escolaridade e áreas geográficas conforme necessário. Durante a recodificação de idades e datas, foram imputadas algumas datas aleatórias, dentro de limites calculados, para lidar com idades ou datas em falta ou desconhecidas. Foi ainda calculado, por análise de componentes principais, um índice de riqueza dos agregados baseado nos seus bens; a pontuação e os quintis foram incluídos nos conjuntos de dados para tabulação.</p>';
$lang['EstSmpErr'] = '<p>Nos inquéritos por amostragem, é boa prática calcular e publicar o erro de amostragem. Utilize este campo para informar sobre esses cálculos, incluindo:</p>
<ul>
<li>Uma lista dos rácios ou indicadores para os quais foram calculados erros de amostragem;</li>
<li>Detalhes do software e referências aos programas utilizados, que devem ser fornecidos como recursos externos;</li>
<li>Referências aos relatórios ou outros documentos com os resultados, também a fornecer como recursos externos.</li>
</ul>
<p>Exemplo:</p>
<p>As estimativas de um inquérito por amostragem são afectadas por dois tipos de erro: 1) erros não amostrais e 2) erros de amostragem. Os primeiros resultam de falhas na recolha e no processamento. Embora tenham sido feitos muitos esforços para os minimizar no MICS 2005-2006, são impossíveis de evitar e difíceis de avaliar estatisticamente.</p>
<p>Se a amostra de pessoas inquiridas fosse uma amostra aleatória simples, poderiam ser utilizadas fórmulas directas para calcular os erros. No entanto, a amostra do MICS 2005-2006 resulta de um plano estratificado por etapas e requer fórmulas mais complexas. Foi utilizado o módulo de amostras complexas do SPSS, que aplica o método de linearização de Taylor para estimar a variância de médias ou proporções. Este método está documentado no ficheiro CSDescriptives.pdf do SPSS, nas opções Help e Algorithms.</p>
<p>Os erros foram calculados para um conjunto seleccionado de estatísticas, todas proporções devido às limitações do método de Taylor, para a amostra nacional, as áreas urbanas e rurais e cada uma das cinco regiões. Para cada estatística são apresentados a estimativa, o erro-padrão, o coeficiente de variação ou erro relativo, o efeito do plano, a raiz quadrada desse efeito (DEFT, a razão entre o erro-padrão do plano utilizado e o que resultaria de uma amostra aleatória simples) e os intervalos de confiança de 95 por cento (+/-2 erros-padrão).</p>
<p>Os detalhes constam do anexo sobre erros de amostragem do relatório e da tabela correspondente nos recursos externos.</p>';
$lang['dataAppr'] = '<p>Descreva outras medidas tomadas para avaliar a fiabilidade dos dados ou observações sobre a sua qualidade. Pode incluir:</p>
<ul>
<li>Para um censo da população, informação sobre o inquérito pós-recenseamento. O relatório deve ser mencionado e carregado na secção de ficheiros de dados e outros recursos.</li>
<li>Para qualquer inquérito ou censo, uma comparação com dados de outra fonte.</li>
<li>Outras avaliações pertinentes.</li>
</ul>
<p>Exemplo:</p>
<p>Estão disponíveis tabelas e gráficos para avaliar a qualidade dos dados, incluindo:</p>
<ul>
<li>Distribuição etária da população dos agregados familiares;</li>
<li>Distribuição etária das mulheres elegíveis e entrevistadas;</li>
<li>Distribuição etária das crianças elegíveis e das crianças cuja mãe ou pessoa responsável foi entrevistada;</li>
<li>Distribuição das crianças menores de cinco anos por grupos de três meses;</li>
<li>Rácios de idade e período nos limites de elegibilidade;</li>
<li>Percentagem de observações sem informação para variáveis seleccionadas;</li>
<li>Presença da mãe no agregado e pessoa entrevistada para o questionário de menores de cinco anos;</li>
<li>Frequência escolar por ano de idade;</li>
<li>Rácio de sexos à nascença entre crianças nascidas, sobreviventes e falecidas, por idade da pessoa inquirida;</li>
<li>Distribuição das mulheres pelo tempo decorrido desde o último parto;</li>
<li>Gráficos de dispersão do peso por altura, peso por idade e altura por idade;</li>
<li>Gráfico da população masculina e feminina por ano de idade;</li>
<li>Pirâmide etária.</li>
</ul>
<p>Os resultados constam do anexo do relatório final e da secção de recursos externos.</p>
<p>Nas tabulações do relatório final, é apresentada uma coluna de dados em falta quando estes representam pelo menos 1 por cento dos casos. Os casos com características de contexto em falta, como escolaridade, são incluídos nas tabelas, mas as respectivas linhas são omitidas e assinaladas no rodapé das tabelas do relatório, embora não na saída do SPSS.</p>';
$lang['useStmt_contact'] = '<p>Esta secção inclui nome, afiliação, endereço de correio electrónico e URI da pessoa ou entidade responsável por autorizar o acesso aos dados. Sempre que possível, utilize um endereço genérico, como microdata@worldbank.org, para não associar o acesso a uma pessoa cujas funções possam mudar.</p>';
$lang['confDec'] = '<p>Se o conjunto de dados não estiver anonimizado, pode indicar aqui a declaração de confidencialidade que deve ser assinada antes do acesso. Também pode incluir esta informação no elemento seguinte, «Condições de acesso». Se não houver questões de confidencialidade, deixe o campo em branco.</p>';
$lang['conditions'] = '<p>Cada conjunto de dados deve ter uma política de acesso. A Biblioteca de Microdados recomenda um dos seguintes níveis:</p>
<ul>
<li>Ficheiros de uso público, acessíveis a todos;</li>
<li>Conjuntos de dados licenciados, acessíveis mediante condições e após análise;</li>
<li>Dados disponíveis num repositório externo.</li>
</ul>
<p>O Banco Mundial definiu políticas genéricas e formulários de acesso normalizados para cada tipo de acesso.</p>
<p>Consulte os termos de utilização do <a href="http://microdata.worldbank.org/index.php/terms-of-use" target="_blank" rel="noopener noreferrer">catálogo público externo</a> e os termos do <a href="http://microdatalib.worldbank.org/index.php/terms" target="_blank" rel="noopener noreferrer">catálogo interno para funcionários</a>.</p>';
$lang['citReq'] = '<p>O requisito de citação define como o conjunto de dados deve ser referenciado numa publicação. Todos os conjuntos de dados devem ter este requisito, para que o produtor receba o devido reconhecimento e os resultados das análises possam ser associados à versão correcta. A política de acesso deve mencionar expressamente a obrigação de cumprir o requisito. A citação deve incluir, pelo menos, o investigador principal, o nome e a abreviatura do conjunto de dados, o ano de referência e o número da versão. Inclua também o sítio onde o depositante oficial disponibiliza os dados ou informação sobre eles.</p>
<p><small>Sarah Baird, George Washington University, Craig McIntosh, University of California San Diego, Berk Ozler, World Bank. Tanzania Second Social Action Fund (TASAF II) - Vulnerable Groups Impact Evaluation - Round I, Household Survey 2008, Ref. TZA_2008_TASAF-II_v01_M_v01_A_PUF. Conjunto de dados descarregado de [URL] em [date].</small></p>';
$lang['disclaimer'] = '<p>Exemplo: o utilizador dos dados reconhece que a entidade que os recolheu originalmente, o distribuidor autorizado e a entidade financiadora competente não se responsabilizam pela utilização dos dados nem pelas interpretações ou inferências dela decorrentes.</p>';
$lang['copyright'] = '<p>Inclua aqui uma declaração de direitos de autor para o conjunto de dados, por exemplo:</p><p>(c) 2007, Banco Mundial</p>';
$lang['impact_wb_name_help'] = '<p>O nome do código da avaliação de impacto correspondente do Banco Mundial ou, se não existir um código separado, o título ou nome pelo qual a avaliação é conhecida.</p>';
$lang['impact_wb_id_help'] = '<p>O código da avaliação de impacto correspondente do Banco Mundial, por exemplo P012345. Introduza «N/A» se não existir um código separado.</p>';
$lang['impact_wb_lead_help'] = '<p>O(s) responsável(is) pela equipa de projecto do Banco Mundial e/ou consultor(es) ou investigador(es) principal(is).</p>';
$lang['impact_wb_members_help'] = '<p>Outros funcionários do Banco Mundial ou investigadores ou consultores que trabalharam nesta avaliação de impacto.</p>';
$lang['impact_wb_description_help'] = '<p>O nome do código da avaliação de impacto correspondente do Banco Mundial ou, se não existir um código separado, o título ou nome pelo qual a avaliação é conhecida.</p>';
$lang['operational_wb_name_help'] = '<p>O nome da operação do Banco Mundial à qual esta avaliação de impacto está associada.</p>';
$lang['operational_wb_id_help'] = '<p>O código de projecto da operação associada, por exemplo P012345.</p>';
$lang['operational_wb_summary_help'] = '<p>Apresente um resumo da operação e dos objectivos de desenvolvimento do projecto.</p>';
$lang['distStmt_contact'] = '<p>Os utilizadores dos dados podem necessitar de esclarecimentos e informação adicional. Esta secção pode incluir o nome, a afiliação, o endereço de correio electrónico e o URI de uma ou mais pessoas de contacto. Evite indicar nomes de pessoas, pois a informação deve manter-se válida a longo prazo; prefira identificar os contactos pela função. O mesmo se aplica ao endereço electrónico: idealmente, forneça um endereço genérico. O servidor de correio pode ser configurado para reencaminhar automaticamente as mensagens desse endereço para membros da equipa.</p>';
$lang['catalog_to_publish_help'] = '<p>Escolha o catálogo externo se pretender partilhar os dados com o público e o catálogo interno se pretender partilhá-los apenas com funcionários do Banco Mundial. Os estudos do catálogo externo são também incluídos automaticamente no catálogo interno.</p>';
$lang['is_embargoed_help'] = '<p>Assinale esta opção se a divulgação do estudo aos funcionários do Banco Mundial ou ao público estiver sujeita a um embargo durante algum tempo. Indique no campo abaixo o período e as condições do embargo.</p>';
$lang['disclosure_risk_help'] = '<p>É importante proteger a identidade e a privacidade das pessoas que forneceram os dados. Se os ficheiros depositados contiverem variáveis identificadoras, como nomes, números de identificação oficiais ou coordenadas GIS precisas, ou variáveis consideradas sensíveis no contexto do estudo, como etnia, tribo ou religião, liste-as abaixo para que o acesso possa ser suprimido.</p>';
$lang['notes_to_library_help'] = 'Introduza aqui quaisquer notas ou instruções adicionais.';
$lang['notes_to_embargoed_help'] = '<p>Indique as condições do embargo, incluindo o período e quaisquer instruções especiais.</p>';
$lang['cc_help'] = '<p>Introduza os endereços de correio electrónico de outras pessoas que devam receber uma cópia resumida da sua submissão.</p>';
$lang['suggested_access_policy_help'] = '<p>Seleccione a política de acesso adequada aos seus dados. Para mais detalhes, consulte os termos de utilização do catálogo interno em <a href="http://microdatalib.worldbank.org/index.php/terms">http://microdatalib.worldbank.org/index.php/terms</a> e os do catálogo externo em <a href="http://microdata.worldbank.org/index.php/terms-of-use">http://microdata.worldbank.org/index.php/terms-of-use</a>.</p>';
$lang['study_help'] = 'Preencha os campos de cada secção abaixo. A informação detalhada acelera a publicação do estudo e ajuda os utilizadores a encontrar o que precisam, reduzindo a necessidade de contactar o produtor para esclarecimentos. Apenas três campos são obrigatórios para a submissão. Se o tempo ou a informação disponível não permitir preencher todos os campos, solicitamos que preencha pelo menos os obrigatórios e recomendados.';
$lang['create_title'] = 'Indique o título completo do projecto.';
$lang['create_short'] = 'Indique uma sigla curta para o projecto, por exemplo UZB HBS 1998.';
$lang['create_collab'] = 'Adicione os endereços de correio electrónico das pessoas que podem editar este projecto.';
$lang['create_desc'] = 'Apresente uma descrição detalhada do projecto.';
$lang['help_subtitle'] = 'Indique um subtítulo curto para o inquérito.';
$lang['section_identification'] = 'Identificação';
$lang['section_version'] = 'Versão';
$lang['no_files_uploaded'] = 'Nenhum ficheiro carregado';
$lang['no_citations_attached'] = 'Nenhuma citação';
