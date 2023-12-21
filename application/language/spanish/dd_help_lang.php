<?php 
$lang['show_help_tooltip']="Mostrar/ocultar toda la ayuda";
$lang['show_fields_tooltip']="Mostrar todos los campos";
$lang['mandatory_recommended_tooltip']="Mostrar solo campos obligatorios y recomendados";
$lang['mandatory_only_tooltip']="Mostrar solo campos obligatorios";
$lang['save_tooltip']="Guardar descripción del estudio";
$lang['titl']="<p> Ingrese el título oficial de la encuesta. El título puede estar en inglés o en el idioma de la encuesta. Incluya el (los) año(s) de referencia, pero NO incluya el acrónimo de la encuesta o el nombre del país como parte del título. Escriba con mayúscula la primera letra de cada palabra (excepto en el caso de preposiciones u otras conjunciones). Para títulos en francés, inglés, portugués, etc., se deben proporcionar caracteres acentuados. Ejemplo: “Enquête Démographique et de Santé 2008-2009”. </p>";
$lang['altTitl']="<p> Ingrese el acrónimo de la encuesta (incluida la inicial del país si corresponde). Se pueden incluir los años de referencia de la encuesta. Ejemplo: DHS 2008-09. </p>";
$lang['serName']="<p> El tipo de estudio o tipo de encuesta es la categoría amplia que define la encuesta. Seleccione una opción del menú desplegable. Seleccione &quot;Otro&quot; si ninguna de las opciones coincide con su encuesta. </p>";
$lang['serInfo']="<p> Una encuesta puede repetirse en intervalos regulares (como una encuesta anual de población activa) o ser parte de un programa de encuestas internacional (como MICS, CWIQ, DHS, LSMS y otros). La información de la serie es una descripción de esta &quot;colección&quot; de encuestas. Aquí se proporcionará una breve descripción de las características de la encuesta, incluido cuándo comenzó, cuántas rondas ya se implementaron y quién está a cargo. Si la encuesta no pertenece a una serie, deje este campo vacío. </p>";
$lang['parTitl']="<p> En la mayoría de los casos, este campo se dejará vacío. En países con más de un idioma oficial, se puede proporcionar una traducción del título. </p>";
$lang['IDNo']="<pre>
El número de identificación ID de una base de datos es un número único que se utiliza para identificar una encuesta en particular. Defina y utilice un esquema coherente. El ID podría construirse de la siguiente manera: país-productor-encuesta-año-versión donde:
- país es la abreviatura de país ISO de 3 letras
- productor es la abreviatura de la institución productora
- encuesta es la abreviatura de la encuesta
- año es el año de referencia (o el año en que comenzó la encuesta)
- versión es el número de versión de la base de datos (Vea la Descripción de la versión abajo)
</pre>";
$lang['version']="<pre>
<p> La descripción de la versión debe contener un número de versión seguido de una descripción de la versión. Ejemplos: </p>
<p>
<ul>
<li> versión 0.1: Datos brutos sin procesar, obtenidos de la captura de datos (antes de la edición). </li>
<li> versión 1.2: datos editados, segunda versión, únicamente para uso interno. </li>
<li> versión 2.1: Base de datos anonimizada y editada para distribución pública. </li>
Una breve descripción de la versión debe seguir a la identificación numérica. </li>
</ul>
</p>";
$lang['version_idate']="<p> Esta es la fecha en formato ISO (aaaa-mm-dd) de producción real y final de los datos. Proporcione al menos el mes y el año. </p>";
$lang['version_notes']="<p> Las notas de la versión deben brindar un breve informe sobre los cambios realizados a través del proceso de control de versiones. La nota debe indicar en qué se diferencia esta versión de otras versiones de la misma base de datos. </p>";
$lang['overview_abstract']="<p> El resumen debe proporcionar una síntesis clara de los propósitos, objetivos y contenido de la encuesta. </p>";
$lang['instructions_project_submit']="<p> Una vez que esté satisfecho de que la información que ha registrado en su estudio es correcta, estará listo para enviar su proyecto. Por favor, seleccione una política de acceso adecuada para la distribución de los datos, un catálogo en el cual se publicarán los datos, cualquier nota que pueda tener sobre embargos, información confidencial que deba eliminarse antes de la distribución o cualquier otra observación o instrucciones especiales que desee enviar a la Biblioteca de Microdatos. </p>";
$lang['instructions_project_contributor_review']="<p> Una vez que esté satisfecho de que la información que ha registrado en su estudio es correcta, estará listo para enviar su proyecto. Por favor, seleccione una política de acceso adecuada para la distribución de los datos, un catálogo en el cual se publicarán los datos, cualquier nota que pueda tener sobre embargos, información confidencial que deba eliminarse antes de la distribución o cualquier otra observación o instrucciones especiales que desee enviar a la Biblioteca de Microdatos. </p>";
$lang['instructions_datafiles_usage']="<p> Suba todos los archivos que le gustaría compartir. Esto incluye la base de datos (en cualquier formato), cuestionarios, otros instrumentos de la encuesta y descripciones sobre la metodología, archivos de programa/sintaxis y cualquier informe. Una vez que se hayan cargado los archivos, utilice el enlace Editar (en letra azul) a continuación, para definir el tipo de archivo o documento apropiado para ese recurso. Como mínimo, se deben proporcionar la base de datos y el cuestionario. </p>";
$lang['instructions_citations']="<p> Si ha publicado un trabajo que usa la base de datos que se está depositando, puede ingresar la información para citar esta referencia en esta sección. Estas referencias se agregarán a la página de visualización de su estudio, una vez que se publiquen en el catálogo de la Biblioteca de Microdatos. </p>";
$lang['anlyUnit']="&quot;<p> Unidad(es) básica(s) de análisis u observación que describe el estudio: individuos, familias / hogares, grupos, instalaciones, instituciones/organizaciones, unidades administrativas, ubicaciones físicas, etc. </p>
<p> Ejemplos: </p>
<p>
<ul>
<li> Una encuesta de niveles de vida con un cuestionario a nivel comunitario tendría las siguientes unidades de análisis: individuos, hogares y comunidades. </li>
<li> Una encuesta económica podría tener la empresa y el establecimiento como unidades de análisis. </li> </ul>
</p>&quot;";
$lang['dataKind']="<p> Este campo constituye una clasificación amplia de los datos y está asociado con un cuadro desplegable con un vocabulario controlado. </p>";
$lang['keyword']="<p> Las palabras clave resumen el contenido o el tema de la encuesta. Como los clasificadores por temas, se utilizan para facilitar la realización de referencias y las búsquedas en los catálogos de encuestas. </p>";
$lang['scope_notes']="<p> El alcance es una descripción de los temas cubiertos por la encuesta. Puede verse como un resumen de los módulos que se incluyen en el cuestionario. El alcance no considera la cobertura geográfica. </p>
<p> Ejemplo: </p>
<p> El alcance de la Encuesta de Indicadores Múltiples (MICS) incluye: </p>
<ul>
<li> HOGAR: Características del hogar, listado del hogar, niños huérfanos y vulnerables, educación, trabajo infantil, agua y saneamiento, uso doméstico de mosquiteros tratados con insecticida y yodación de sal, con módulos opcionales para disciplina infantil, discapacidad infantil, mortalidad materna y seguridad de la tenencia y durabilidad de la vivienda. </li>
<li> MUJERES: características de la mujer, mortalidad infantil, toxoide tetánico, salud materna y neonatal, matrimonio, poligamia, ablación genital femenina, anticoncepción y conocimiento del VIH/SIDA, con módulos opcionales para necesidades insatisfechas, violencia doméstica y comportamiento sexual. < / li>
<li> NIÑOS: Características de los niños, registro de nacimientos y aprendizaje temprano, vitamina A, lactancia materna, atención de enfermedades, malaria, inmunización y antropometría, con un módulo opcional para el desarrollo infantil. </li>
</ul>";
$lang['topcClas']="<p> Una clasificación de temas facilita la referencia y las búsquedas en catálogos de encuestas electrónicos. </p> <p> Dado que la Biblioteca de Microdatos aún no ha propuesto una lista de temas estándar, este campo puede dejarse en blanco </p>";
$lang['nation']="<p> Ingrese el nombre del país, incluso en los casos en que la encuesta no cubrió todo el país. En el campo &quot;Abreviatura&quot;, le recomendamos que introduzca el código ISO de 3 letras del país. Si la base de datos que documenta cubre más de un país, ingrese todos los que correspondan en filas separadas. </p>";
$lang['geogCover']="<p> Este campo tiene como objetivo describir la cobertura geográfica de la muestra. Entre las descripciones más comunes está &quot;Cobertura nacional&quot;, &quot;Solo áreas urbanas (o rurales)&quot;, &quot;Estado de ...&quot;, &quot;Ciudad capital&quot;, etc. </p>";
$lang['country_universe']="<p> Aquí nos interesa el universo de la encuesta (no el universo de secciones particulares de los cuestionarios o variables), es decir, la identificación de la población de interés de la encuesta. El universo rara vez será la población total del país. Las encuestas por muestreo de hogares, por ejemplo, generalmente no cubren a personas sin hogar, nómadas, diplomáticos, hogares comunitarios. Algunas encuestas pueden cubrir solo la población de un grupo de edad en particular, o solo hombres (o mujeres), etc. </p>";
$lang['AuthEnty']="<p> El investigador principal es la institución (o en algunos casos la(s) persona(s)) que estuvo a cargo del diseño e implementación de la encuesta (no sus financiandores o quienes prestaron asistencia técnica). </p>
<p> El orden en el que se enlistan es discrecional. Puede ser alfabético o por importancia de contribución. </p>";
$lang['AuthEnty_iaffiliation']="Afiliación";
$lang['producers']="<p> Abreviatura, afiliación y Rol. Si alguno de los campos no corresponde, se puede dejar en blanco. Las abreviaturas deben ser la abreviatura oficial de la organización. </p>
<p> El rol debe ser una frase o descripción breve y concisa sobre el tipo de asistencia específica brindada por la organización para producir los datos. </p>
<p> Ejemplos de roles: </p>
<ul>
<li> [Asistencia técnica en] diseño de cuestionarios </li>
<li> [Asistencia técnica en] metodología de muestreo/selección /li>
<li> [Asistencia técnica en] recopilación de datos </li>
<li> [Asistencia técnica en] procesamiento de datos </li>
<li> [Asistencia técnica en] análisis de datos </li>
<p> No incluya a los financiadores aquí. </p>";
$lang['fundAg']="<p> Enliste las organizaciones (nacionales o internacionales) que han contribuido, en efectivo o en especie, al financiamiento de la encuesta. </p>";
$lang['othId_p']="<p> Este campo opcional se puede utilizar para reconocer a otras personas e instituciones que hayan contribuido de alguna forma a la encuesta. </p>";
$lang['sampProc']="<p> Este campo solo se aplica a encuestas de muestreo. La información sobre el procedimiento de muestreo es crucial (aunque no es aplicable a censos y registros administrativos). Esta sección debe incluir información resumida que incluya, aunque no se limite a: </p>
<ul>
<li> Tamaño de la muestra </li>
<li> Proceso de selección (por ejemplo, probabilidad proporcional al tamaño o sobre muestreo) </li>
<li> Estratificación (implícita y explícita) </li>
<li> Etapas de la selección de la muestra </li>
<li> Omisiones del diseño muestral </li>
<li> Nivel de representación </li>
<li> Estrategia para informantes ausentes / no encontrados / rechazos (reemplazos o no) </li>
<li> Marco muestral utilizado y listado de unidades de análisis realizado y el proceso para actualizarlo) </li>
<p> También es útil indicar aquí qué variables en la base de datos identifican los distintos niveles de estratificación y la unidad primaria de muestreo. Estos son cruciales para los usuarios de datos que desean tener en cuenta adecuadamente el diseño de muestreo en sus análisis y cálculos de errores de muestreo. </p>
<p> Esta sección solo acepta formato de texto; no se pueden introducir fórmulas. En la mayoría de los casos, existirán documentos técnicos que describan la estrategia de muestreo en detalle. En tales casos, incluya aquí una referencia (título/autor/fecha) a este documento y asegúrese de que el documento esté cargado en la sección de archivos de datos y otros recursos. </p>";
$lang['deviat']="<p> Este campo solo aplica a encuestas de muestreo. </p>
<p> En ocasiones, la realidad del trabajo de campo implica alguna desviación en relación con el diseño muestral (por ejemplo, debido a la dificultad de acceso a las zonas debido a problemas climáticos, inestabilidad política, etc.). Si por alguna razón, el diseño muestral se ha desviado, esto se debe informar aquí. </p>";
$lang['respRate']="<p> La tasa de respuesta proporciona el porcentaje de hogares (u otra unidad de muestra) que participaron en la encuesta según el tamaño de la muestra original. Es posible que se produzcan omisiones debido a la negativa a participar, la imposibilidad de localizar al informante u otro motivo. A veces, un hogar puede ser reemplazado por otro, por diseño. Verifique que la información proporcionada aquí sea consistente con el tamaño de muestra indicado en el campo &quot;Procedimiento de muestreo&quot; y el número de registros encontrados en la base de datos (por ejemplo, si el diseño de la muestra menciona una muestra de 5,000 hogares y los datos contienen datos sobre 4,500 hogares, la tasa de respuesta no debe ser del 100 por ciento). </p>
<p> Proporcione, si es posible, las tasas de respuesta por estrato. Si hay información disponible sobre las causas de la falta de respuesta (rechazo/no encontrado/otro), proporcione también esta información. </p>
<p> Este campo también se puede utilizar en algunos casos para describir la tasa de no-respuesta en los censos de población. </p>";
$lang['weight']="Este campo solo se aplica a encuestas por muestreo o muestras censales.
<p> Proporcione aquí la lista de variables utilizadas como coeficiente de ponderación. Si más de una variable es una variable de ponderación, describa en qué se diferencian estas variables entre sí y cuál es el propósito de cada una de ellas. </p>
<p> Ejemplo: </p>
<p> Se calcularon pesos de muestra para cada uno de los archivos de datos. </p>
</p> Las ponderaciones de la muestra para los datos de los hogares se calcularon como la inversa de la probabilidad de selección del hogar, calculada a nivel de dominio de muestreo (urbano/rural dentro de cada región). Las ponderaciones de los hogares se ajustaron para la no-respuesta a nivel de dominio y luego se normalizaron mediante un factor constante de modo que el número total ponderado de hogares sea igual al número total de hogares no ponderados. La variable de peso del hogar se llama HHWEIGHT y se aplica a la base de datos HH y la base de datos HL. </p>
<p> Las ponderaciones de la muestra para los datos de mujeres utilizaron las ponderaciones del hogar no normalizadas, ajustadas por la no-respuesta al cuestionario de mujeres, y luego se normalizaron mediante un factor constante de modo que el número total ponderado de casos de mujeres sea igual al número total no ponderado de casos de mujeres. </p>
<p> Las ponderaciones de la muestra para los datos de niños siguieron el mismo enfoque que el de las mujeres y utilizaron las ponderaciones del hogar no normalizadas, ajustadas por la no-respuesta para el cuestionario de los niños, y luego se normalizaron mediante un factor constante de modo que el número total ponderado de casos de niños es igual al número total no ponderado de casos de niños. </p>";
$lang['collDate']="<p> Ingrese las fechas (al menos el mes y el año) de inicio y finalización de la recopilación de datos. </p>
<p> En algunos casos, la recopilación de datos para una misma encuesta se puede realizar en rondas. En tal caso, debe registrar la fecha de inicio y finalización de cada ronda por separado e identificar cada ronda en el campo &quot;ciclo&quot;. </p>";
$lang['timePrd']="<p> Este campo normalmente se dejará vacío. El período de referencia difiere de las fechas de recopilación, ya que representan el período para el cual los datos recopilados son aplicables o relevantes. </p>";
$lang['collMode']="<p> El modo de recopilación de datos es la forma en que se realizó la entrevista o se recopiló la información. Este campo es un campo de vocabulario controlado. </p>
<p> Utilice el botón desplegable para seleccionar una opción. En la mayoría de los casos, la respuesta será &quot;entrevista cara a cara&quot;&quot;. Pero para algunos tipos específicos de bases de datos, como por ejemplo los datos sobre la lluvia, la respuesta será diferente. </p>";
$lang['collSitu']="<p> Este elemento se proporciona para documentar observaciones, sucesos o eventos específicos durante la recopilación de datos. Considere indicar elementos como: </p>
<ul>
<li> ¿Se llevó a cabo una capacitación de encuestadores? (Explique) </li>
<li> ¿Hubo algún evento que pueda afectar la calidad de los datos? </li>
<li> ¿Cuánto tiempo duró una entrevista en promedio? </li>
<li> ¿Hubo un proceso de negociación entre los hogares, la comunidad y la institución que implementa la encuesta? </li>
<li> ¿Se registran los eventos anecdóticos? </li>
<li> ¿Los equipos de campo han contribuido proporcionando información sobre problemas y sucesos durante la recopilación de datos? </li>
<li> ¿En qué idioma se realizó la entrevista? </li>
<li> ¿Se realizó una encuesta piloto? </li>
<li> ¿La dirección ejecutiva tomó alguna medida correctiva cuando ocurrieron problemas en el campo? </li>
</ul>
<p> Ejemplo: <p>
<p> La prueba piloto de la encuesta se llevó a cabo del 15 de agosto de 2006 al 25 de agosto de 2006 e incluyó a 14 encuestadores que luego se convertirían en supervisores de la encuesta principal. </p>
<p> Cada equipo de encuestadores estaba compuesto por 3-4 encuestadoras (no se utilizaron encuestadores masculinos debido a la sensibilidad del tema), junto con un editor de campo, un supervisor y un conductor. Se utilizaron un total de 52 encuestadores, 14 supervisores y 14 editores de campo. La recolección de datos se llevó a cabo durante un período de aproximadamente 6 semanas desde el 2 de septiembre de 2006 hasta el 17 de octubre de 2006. Las entrevistas se realizaron todos los días durante el período de trabajo de campo, aunque a los equipos de encuestadores se les permitió tomar un día libre por semana. </p>
<p> Las entrevistas tuvieron un promedio de 35 minutos para el cuestionario del hogar (excluyendo la prueba de sal), 23 minutos para el cuestionario para mujeres y 27 para el cuestionario para niños menores de cinco años (excluida la antropometría). Las entrevistas se realizaron principalmente en Inglés y Mumbo-Jumbo, pero ocasionalmente se utilizó traducción local en holandés, cuando el entrevistado no hablaba Inglés o Mumbo-Jumbo. </p>
<p> Seis miembros del personal de GenCenStat brindaron la coordinación y supervisión general del trabajo de campo. La coordinadora general de campo fue la Sra. Doe. </p>";
$lang['resInstru']="<p> Este elemento permite describir los cuestionarios utilizados para la recopilación de datos. Se debería mencionar lo siguiente: </p>
<ul>
<li> Lista de cuestionarios y una breve descripción de cada uno (todos los cuestionarios deben adjuntarse como recursos externos) </li>
<li> ¿En qué idioma se publicaron los cuestionarios? </li>
<li> Información sobre el proceso de diseño del cuestionario (basado en un cuestionario anterior, basado en un modelo de cuestionario estándar, revisión por parte de las partes interesadas). Si se compiló un documento que contiene los comentarios proporcionados por las partes interesadas sobre el borrador del cuestionario, o un informe preparado sobre la prueba del cuestionario, se debe proporcionar aquí una referencia a estos documentos y los documentos se deben proporcionar como Recursos externos. </li>
</ul>
<p> Ejemplo: </p>
<p> Los cuestionarios para el MICS genérico fueron cuestionarios estructurados basados ​​en el cuestionario modelo MICS3 con algunas modificaciones y adiciones. Se administró un cuestionario de hogar en cada hogar, que recopiló información sobre los miembros del hogar, incluido el sexo, la edad, la relación y el estado de orfandad. El cuestionario del hogar incluye características del hogar, apoyo a niños huérfanos y vulnerables, educación, trabajo infantil, agua y saneamiento, uso doméstico de mosquiteros tratados con insecticida y yodación de la sal, con módulos opcionales para disciplina infantil, discapacidad infantil, mortalidad materna y seguridad de los niños, tenencia y durabilidad de la vivienda. </p>
<p> Además de un cuestionario de hogar, se administraron cuestionarios en cada hogar para mujeres de 15 a 49 años y niños menores de cinco años. Para los niños, el cuestionario se administró a la madre o a la persona a cargo del cuidado del niño. </p>
<p> El cuestionario para mujeres incluye características de las mujeres, mortalidad infantil, toxoide tetánico, salud materna y neonatal, matrimonio, poligamia, ablación genital femenina, anticoncepción y conocimientos sobre el VIH/SIDA, con módulos opcionales para necesidades insatisfechas, violencia doméstica y comportamiento sexual . </p>
<p> El cuestionario infantil incluye características de los niños, registro de nacimientos y aprendizaje temprano, vitamina A, lactancia materna, atención de enfermedades, malaria, inmunización y antropometría, con un módulo opcional para el desarrollo infantil. </p>
<p> Los cuestionarios se desarrollaron en inglés a partir de los cuestionarios modelo MICS3 y se tradujeron al Mumbo-Jumbo. Después de una revisión inicial, los cuestionarios fueron traducidos al inglés por un traductor independiente sin conocimiento previo de la encuesta. La traducción inversa de la versión Mumbo-jumbo se revisó de forma independiente y se comparó con el original en inglés. Las diferencias en la traducción se revisaron y resolvieron en colaboración con los traductores originales. </p>
<p> Los cuestionarios en inglés y Mumbo-jumbo se probaron como parte de la prueba piloto de la encuesta. </p>
<p> Todos los cuestionarios y módulos se proporcionan como recursos externos. </p>";
$lang['dataCollector']="<p> Este elemento sirve para registrar información sobre las personas y/o instituciones que se encargaron de la recolección de datos. Este elemento incluye 3 campos: Nombre, Abreviatura y Afiliación. En la mayoría de los casos, registraremos aquí el nombre de la institución, no el nombre de los encuestadores. Solo en el caso de encuestas a muy pequeña escala, con un número muy limitado de encuetadores, también se incluirá el nombre de la persona. El campo Afiliación es opcional y no es relevante en todos los casos. </p>";
$lang['cleanOps']="<p> La edición e imputación de datos debe contener información sobre cómo se trataron o controlaron los datos en términos de consistencia y coherencia. Este ítem no se refiere a la fase de captura de datos, sino sólo a la edición o imputación de datos, ya sea manual o automática. </p>
<ul>
<li> ¿Se utilizó una técnica de plataforma caliente o plataforma fría para imputar los datos? </li>
<li> ¿Se hicieron correcciones automáticamente (por programa) o mediante control visual del cuestionario? </li>
<li> ¿Qué software se utilizó? </li>
</ul>
<p> Si hay materiales disponibles (especificaciones para la imputación de datos, informes sobre la edición o imputación de datos, programas utilizados para la edición o imputación de datos), deben enlistarse aquí y cargarse en la sección de archivos de datos y otros recursos. </p>
<p> Ejemplo: </p>
<p> La edición de datos se llevó a cabo en varias etapas durante el procesamiento, que incluyen: </p>
<ol>
<li> Edición y codificación de Office </li>
<li> Durante la captura de datos </li>
<li> Verificación de la estructura e integridad </li>
<li> Edición secundaria </li>
<li> Verificación estructural de archivos de datos SPSS </li>
</ol>
<p> Puede encontrar documentación detallada sobre la edición e imputación de datos en el documento &quot;Lineamientos de procesamiento de datos&quot; que se proporciona como recurso externo. </p>";
$lang['method_notes']="<p> Utilice este campo para proporcionar la mayor cantidad de información posible sobre el diseño de la captura de datos. Esto incluye detalles como: </p>
<ul>
<li> Modo de captura de datos (manual o por escaneo, en el campo / en regiones / en la sede) </li>
<li> Arquitectura de computadoras (computadoras portátiles en el campo (laptops), computadoras de escritorio (desktops), escáneres, PDA, otros; indique la cantidad de computadoras utilizadas) </li>
<li> Software utilizado </li>
<li> Uso (y tasa) de captura de la doble captura datos</li>
<li> Productividad promedio de los transcriptores de datos; número de transcriptores de datos involucrados y su horario de trabajo </li>
</ul>
<p> Aquí también se puede proporcionar información sobre tabulación y análisis. </p>
<p> Todos los materiales disponibles (programas de captura de datos / tabulación / análisis de datos; informes sobre transcripción de datos) deben incluirse aquí y proporcionarse como recursos externos. </p>
<p> Ejemplo: </p>
<p> Los datos se procesaron en grupos, y cada grupo se procesó como una unidad completa en cada etapa del procesamiento de datos. Cada grupo pasa por los siguientes pasos: </p>
<ol>
<li> Recepción del cuestionario </li>
<li> Edición y codificación de Office </li>
<li> Transcripción de datos </li>
<li> Comprobación de la estructura y la integridad </li>
<li> Captura de verificación </li>
<li> Comparación de datos de verificación </li>
<li> Copia de seguridad de datos brutos </li>
<li> Segunda Edición </li>
<li> Copia de seguridad de datos editados </li>
<p> Una vez que se procesan todos los clústeres, todos los datos se concatenan juntos y luego se completan los siguientes pasos para todos los archivos de datos: </p>
<li> Exportar a SPSS en 4 archivos (hh - hogar, hl - miembros del hogar, wm - mujeres, ch - niños menores de 5 años) </li>
<li> Recodificación de las variables necesarias para el análisis </li>
<li> Adición de pesos de muestra </li>
<li> Cálculo de quintiles de riqueza y fusión con datos </li>
<li> Comprobación estructural de archivos SPSS </li>
<li> Tabulaciones de la calidad de los datos </li>
<li> Producción de tabulaciones de análisis </li>
</ol>
<p> Los detalles de cada uno de estos pasos se pueden encontrar en la documentación de procesamiento de datos, las guías de edición de datos, los programas de procesamiento de datos en CSPro y SPSS, y la guía para tabulación. </p>
<p> La captura de datos fue realizada por 12 transcriptores de datos en dos turnos, supervisados ​​por 2 supervisores de transcripción de datos, utilizando un total de 7 computadoras (6 computadoras de captura de datos más una computadora de supervisor). Toda la transcripción de datos se realizó en la oficina central de GenCenStat mediante la transcripción de datos manual. Para la transcripción de datos, se usó CSPro versión 2.6.007 con un programa de captura de datos altamente estructurado, utilizando un enfoque controlado por el sistema que controlaba la captura de cada variable. El programa controlaba todos los controles de rango y saltos y los transcriptores no podían anularlos. También se incluyó un conjunto limitado de controles de coherencia en el programa de captura de datos. Además, el cálculo de las puntuaciones Z antropométricas también se incluyó en los programas de entrada de datos para su uso durante el análisis. Las respuestas abiertas (&quot;&quot;Otras&quot;&quot; respuestas) no se ingresaron ni codificaron, excepto en raras circunstancias en las que la respuesta coincidía con un código existente en el cuestionario. </p>
<p> La verificación de la estructura y la integridad aseguró que todos los cuestionarios para el grupo se habían transcrito, eran estructuralmente sólidos y que existían cuestionarios para mujeres y niños para cada mujer y niño elegible. </p>
<p> Se realizó una verificación del 100% de todas las variables mediante una verificación independiente, es decir, una doble captura de datos, con una comparación separada de los datos seguida de la modificación de uno o ambos conjuntos de datos para corregir los errores de tecleado por parte de los transcriptores originales que primero transcribieron los archivos. </p>
<p> Después de completar todo el procesamiento en CSPro, se realizó una copia de seguridad de todos los archivos de clúster individuales antes de concatenar los datos mediante la herramienta de concatenación de archivos CSPro. </p>
<p> Para la tabulación y análisis se utilizaron las versiones 10.0 y 14.0 de SPSS. La versión 10.0 se utilizó originalmente para todos los programas de tabulación, excepto para la mortalidad infantil. En una versión posterior, después de transferir todos los archivos a SPSS, se recodificaron ciertas variables para usarlas como características socioeconómicas  en la tabulación de los datos, incluida la edad de agrupación, educación, áreas geográficas según sea necesario para el análisis. En el proceso de recodificación de edades y fechas, se realizó una imputación aleatoria de fechas (dentro de las limitaciones calculadas) para manejar las edades o fechas faltantes o los &quot;&quot;no sabe&quot;&quot;. Además, se calculó un índice de riqueza (activos) de los miembros del hogar mediante el análisis de componentes principales, basado en los activos del hogar, y tanto la puntuación como los quintiles se incluyeron en las bases de datos para su uso en tabulaciones. </p>";
$lang['EstSmpErr']="<p> Para las encuestas de muestreo, es una buena práctica calcular y publicar el error de muestreo. Este campo se utiliza para proporcionar información sobre estos cálculos. Esto incluye: </p>
<ul>
<li> Una lista de ratios/indicadores para los que se calcularon los errores de muestreo. </li>
<li> Detalles sobre el software utilizado para calcular el error de muestreo y una referencia a los programas utilizados (que se proporcionarán como recursos externos) como el programa utilizado para realizar los cálculos. </li>
<li> Referencia a los informes u otro documento donde se pueden encontrar los resultados (se proporcionará como recursos externos). </li>
</ul>
<p> Ejemplo: </p>
<p> Las estimaciones de una encuesta por muestreo se ven afectadas por dos tipos de errores: 1) errores ajenos al muestreo y 2) errores de muestreo. Los errores ajenos al muestreo son el resultado de errores cometidos en la implementación de la recopilación y el procesamiento de datos. Se hicieron numerosos esfuerzos durante la implementación de las MICS 2005-2006 para minimizar este tipo de error, sin embargo, los errores ajenos al muestreo son imposibles de evitar y difíciles de evaluar estadísticamente. </p>
<p> Si la muestra de encuestados hubiera sido una muestra aleatoria simple, habría sido posible utilizar fórmulas sencillas para calcular los errores de muestreo. Sin embargo, la muestra MICS 2005-2006 es el resultado de un diseño estratificado de múltiples etapas y, en consecuencia, necesita utilizar fórmulas más complejas. El módulo de muestras complejas de SPSS se ha utilizado para calcular los errores de muestreo para las MICS 2005-2006. Este módulo utiliza el método de linealización de Taylor de estimación de la varianza para estimaciones de encuestas que son medias o proporciones. Este método está documentado en el archivo SPSS CSDescriptives.pdf que se encuentra en el menú Ayuda, Opciones de algoritmos en SPSS. </p>
</p> Se han calculado errores de muestreo para un conjunto selecto de estadísticas (todas las cuales son proporciones debido a las limitaciones del método de linealización de Taylor) para la muestra nacional, áreas urbanas y rurales, y para cada una de las cinco regiones. Para cada estadística, la estimación, su error estándar, el coeficiente de variación (o error relativo - la razón entre el error estándar y la estimación), el efecto del diseño y el efecto del diseño de la raíz cuadrada (DEFT - la razón entre el error estándar usando el diseño de muestra dado y el error estándar que resultaría si se hubiera usado una muestra aleatoria simple), así como los intervalos de confianza del 95 por ciento (+/- 2 errores estándar). </p>
<p> Los detalles de los errores de muestreo se presentan en el apéndice de errores de muestreo del informe y en la tabla de errores de muestreo que se presenta en los recursos externos. </p>";
$lang['dataAppr']="<p> Esta sección puede usarse para informar cualquier otra acción tomada para evaluar la confiabilidad de los datos, o cualquier observación relacionada con la calidad de los datos. Este campo considera los siguientes elementos: </p>
<ul>
<li> Para un censo de población, información sobre la encuesta posterior a la enumeración (se debe proporcionar un informe\cargarse en la sección de archivos de datos y otros recursos y mencionarlo aquí). </li>
<li> Para cualquier encuesta/censo, una comparación con datos de otra fuente. </li>
<li> Etc. </li>
<p> Ejemplo: </p>
<p> Hay una serie de tablas y gráficos de calidad de datos disponibles para revisar la calidad de los datos e incluyen lo siguiente: </p>
<ul>
<li> Distribución por edades de la población del hogar </li>
<li> Distribución por edad de las mujeres elegibles y las mujeres entrevistadas </li>
<li> Distribución por edad de los niños elegibles y de los niños para quienes se entrevistó a la madre o al cuidador </li>
<li> Distribución por edades de los niños menores de 5 años por grupos de 3 meses </li>
<li> Proporciones de edad y período en los límites de elegibilidad </li>
<li> Porcentaje de observaciones con datos perdidos sobre las variables seleccionadas </li>
<li> Presencia de la madre en el hogar y de la persona entrevistada para el cuestionario para menores de 5 años </li>
<li> Asistencia a la escuela por edad de un año </li>
<li> Proporción de sexos al nacer entre los niños nacidos vivos, sobrevivientes y muertos por edad del encuestado </li>
<li> Distribución de mujeres por tiempo desde el último parto </li>
<li> Diagrama de dispersión de peso por altura, peso por edad y altura por edad </li>
<li> Gráfico de la población masculina y femenina por años de edad individual </li>
<li> Pirámide poblacional </li>
</ul>
<p> Los resultados de cada una de estas tablas de calidad de datos se muestran en el apéndice del informe final y también se dan en la sección de recursos externos. <p>
<p> La regla general para la presentación de datos perdidos en las tabulaciones del informe final es que se presenta una columna para los datos perdidos si el porcentaje de casos con datos perdidos es del 1% o más. Los casos en los que faltan datos sobre las características básicas (p. Ej., Educación) se incluyen en las tablas, pero las filas con datos perdidos se suprimen y se anotan en la parte inferior de las tablas en el informe (sin embargo, no en la salida de SPSS). </p>";
$lang['useStmt_contact']="<p> Esta sección se compone de varias secciones: Nombre-Afiliación-correo electrónico-URI. Esta información proporciona a la persona o entidad de contacto para obtener autorización para acceder a los datos. Es aconsejable utilizar un correo electrónico de contacto genérico como microdata@worldbank.org siempre que sea posible para evitar vincular el acceso a una persona en particular cuyas funciones pueden cambiar con el tiempo. <P />";
$lang['confDec']="<p> Si la base de datos no está anonimizada, podemos indicar aquí que Declaración Jurada de Confidencialidad debe firmarse antes de que se pueda acceder a los datos. Otra opción es incluir esta información en el siguiente elemento (Condiciones de acceso). Si no hay ningún problema de confidencialidad, este campo se puede dejar en blanco. </p>";
$lang['conditions']="<p> Cada base de datos debe tener una 'Política de acceso' adjunta. La Biblioteca de Microdatos recomienda uno de los siguientes niveles de accesibilidad a los datos: </p>
<ul>
<li> Archivos de uso público, accesibles para todos. </li>
<li> Bases de datos con licencia, accesibles bajo condiciones y revisión posterior. </li>
<li> Datos disponibles de un repositorio externo </li>
</ul>
<p> El Banco Mundial ha propuesto formularios de acceso y políticas genéricas estándar para cada uno de estos tipos de acceso. </p> <p> Los Términos de uso del Banco Mundial para los datos del catálogo público externo se pueden consultar <a href = http://microdata.worldbank.org/index.php/terms-of-use ”target =”_blank ”> aquí </a> y los Términos de uso del catálogo de personal interno se pueden ver <a href=”http://microdatalib.worldbank.org/index.php/terms“target=”_blank”> aquí. </a> </p>";
$lang['citReq']="<p> Los requisitos para citar a la fuente es la forma en que se debe hacer referencia a la base de datos cuando se cita en cualquier publicación. Cada base de datos debe tener requisitos para citar a la fuente. Esto garantizará que el productor de datos obtenga el crédito adecuado y que los resultados analíticos se puedan vincular a la versión adecuada de la base de datos. La Política de Acceso debe mencionar explícitamente la obligación de cumplir con el requisito de citar a la fuente. La cita debe incluir al menos el investigador principal, el nombre y la abreviatura de la base de datos, el año de referencia y el número de versión. Incluya también un sitio web donde el custodio oficial de datos pone a disposición los datos o la información sobre los datos. </p>
<p> <pequeña> Sarah Baird, Universidad George Washington, Craig McIntosh, Universidad de California San Diego, Berk Ozler, Banco Mundial. Segundo Fondo de Acción Social de Tanzania (TASAF II) - Evaluación de impacto de grupos vulnerables - Ronda I, Encuesta de hogares 2008, Ref. TZA_2008_TASAF-II_v01_M_v01_A_PUF. Base de datos descargado de [URL] el [fecha] </small> </p>.";
$lang['disclaimer']="<p> Ejemplo: el usuario de los datos reconoce que el recolector original de los datos, el distribuidor autorizado de los datos y la institución de financiamiento relevante no tienen ninguna responsabilidad por el uso de los datos o por las interpretaciones o inferencias basadas en dichos usos. </p>";
$lang['copyright']="<p> Incluya aquí una declaración de derechos de autor en la base de datos, como: </p>
<p> (c) 2007, Banco Mundial </p>";
$lang['impact_wb_name_help']="<p> El nombre del código de evaluación de impacto del Banco Mundial correspondiente o, si la evaluación de impacto no tiene un código del Banco Mundial separado, el título/nombre bajo el cual se conoce esta evaluación de impacto </p>";
$lang['impact_wb_id_help']="<p> El código de evaluación de impacto del Banco Mundial correspondiente (p. ej., P012345). Escriba &quot;N/A&quot; si esta evaluación de impacto no tiene un código Banco Mundial separado) </p>";
$lang['impact_wb_lead_help']="<p> Los TTL del Banco Mundial y/o consultor(es)/investigador(es) principal(es) </p>";
$lang['impact_wb_members_help']="<p> Otro personal del Banco Mundial o investigadores / consultores que trabajaron en esta evaluación de impacto </p>
";
$lang['impact_wb_description_help']="<p> El nombre del código de evaluación de impacto del Banco Mundial correspondiente o, si la evaluación de impacto no tiene un código del Banco Mundial separado, el título/nombre bajo el cual se conoce esta evaluación de impacto. </p>";
$lang['operational_wb_name_help']="<p> El nombre de la operación del Banco Mundial a la que está vinculada esta evaluación de impacto </p>";
$lang['operational_wb_id_help']="<p> El código del proyecto (por ejemplo, P012345) de la operación relacionada </p>
";
$lang['operational_wb_summary_help']="<p> Proporcione un resumen/descripción general de la operación y los objetivos de desarrollo del proyecto </p>
";
$lang['distStmt_contact']="<p> Los usuarios de los datos pueden necesitar más aclaraciones e información. Esta sección puede incluir el nombre-afiliación-correo electrónico-URI de una o varias personas de contacto. Evite poner el nombre de personas. La información proporcionada aquí debe ser válida a largo plazo. Por tanto, es preferible identificar a las personas de contacto por un cargo. Lo mismo se aplica al campo de correo electrónico. Idealmente, debería proporcionarse una dirección de correo electrónico &quot;genérica&quot;. Es fácil configurar un servidor de correo de tal manera que todos los mensajes enviados a la dirección de correo electrónico genérica se reenvíen automáticamente a algunos miembros del personal. </p>";
$lang['catalog_to_publish_help']="<p> Elija externo si tiene la intención de compartir sus datos con el público. Elija Interno si tiene la intención de compartir solo con el personal del Banco Mundial. Los estudios del catálogo externo también se incluyen automáticamente en el catálogo interno </p>";
$lang['is_embargoed_help']="<p> Si este estudio no se entregará al personal del Banco Mundial o al público durante un período de tiempo, por ejemplo, cuando existe un embargo sobre la publicación, marque esta casilla. Por favor, indique en el cuadro a continuación el período de tiempo y las condiciones del embargo </p>";
$lang['disclosure_risk_help']="<p> Es importante salvaguardar la identidad y la privacidad de los informantes que han proporcionado los datos. Si las bases de datos que está depositando contienen alguna variable de identificación (por ej., Nombres, números de identificación oficiales, coordenadas GIS precisas, etc.) o cualquier variable que se considere sensible en su entorno de estudio (por Ej., Etnia, tribu, religión, etc.), enlístelas a continuación, para que se pueda suprimir el acceso a éstas. </p>";
$lang['notes_to_library_help']="Ingrese aquí cualquier nota o instrucción adicional.";
$lang['notes_to_embargoed_help']="<p> Indique los términos del embargo, por ej. período de tiempo y cualquier otra instrucción especial. </p>
";
$lang['cc_help']="<p> Ingrese las direcciones de correo electrónico de las personas adicionales que le gustaría que reciban una copia resumida de su envío. </p>";
$lang['suggested_access_policy_help']="<p> Seleccione la política de acceso adecuada a sus datos. Para obtener más detalles, consulte: las siguientes condiciones de uso de datos del catálogo interno <a href=&quot;http://microdatalib.worldbank.org/index.php/terms&quot;> http://microdatalib.worldbank.org/index.php/ condiciones </a> y para el catálogo externo, consulte las siguientes condiciones de uso de datos<a href=&quot;http://microdata.worldbank.org/index.php/terms-of-use&quot;> http: //microdata.worldbank. org / index.php / condiciones de uso </a> </p>";
$lang['study_help']="Por favor, complete los campos en cada una de las siguientes secciones. Proporcionar información detallada aquí acelerará el proceso de publicación del estudio. También facilita a los usuarios de los datos encontrar la información que necesitan y, por lo tanto, disminuye la necesidad de que los usuarios se pongan en contacto con el productor de datos para obtener aclaraciones. Solo tres campos son obligatorios para el proceso de envío. Si el tiempo o la información disponible no permiten completar todos los campos, solicitamos que se completen al menos los campos obligatorios y recomendados.";
$lang['create_title']="Proporcione el título completo de su proyecto.";
$lang['create_short']="Proporcione un acrónimo corto para su proyecto. (por ejemplo, UZB HBS 1998)";
$lang['create_collab']="Proporcione la dirección de correo electrónico de otros miembros del personal del Banco Mundial que puedan estar autorizados a editar este proyecto.";
$lang['create_desc']="Proporcione una descripción detallada de su proyecto.";
$lang['help_subtitle']="Proporcione un subtítulo corto para su encuesta.
";
$lang['section_identification']="Identificación";
$lang['section_version']="Versión";
$lang['no_files_uploaded']="No se han subido archivos";
$lang['no_citations_attached']="Sin citas";


/* End of file dd_help */
/* Location: ./application/language/spanish/dd_help */