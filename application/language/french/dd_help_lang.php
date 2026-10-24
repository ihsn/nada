<?php 
$lang['show_help_tooltip']="Afficher/cacher l'aide";
$lang['show_fields_tooltip']="Afficher tous les champs";
$lang['mandatory_recommended_tooltip']="Afficher les champs obligatoires ou recommandés";
$lang['mandatory_only_tooltip']="Afficher les champs obligatoires";
$lang['save_tooltip']="Sauvegarder la description de l'étude";
$lang['titl']="<p>Entrez le titre officiel de l'enquête. Le titre peut être en anglais ou dans la langue de l'enquête. Incluez la ou les années de référence, mais N'INCLUEZ PAS l'acronyme de l'enquête ou le nom du pays dans le titre. Mettez en majuscule la première lettre de chaque mot (sauf pour les prépositions ou autres conjonctions). Pour les titres en français, anglais, portugais, etc., des caractères accentués doivent être fournis. Exemple : « Enquête Démographique et de Santé 2008-2009 ».</p>";
$lang['altTitl']="<p>Saisissez l'acronyme de l'enquête (y compris l'initiale du pays, le cas échéant). L'année ou les années de référence de l'enquête peuvent être incluses. Exemple : DHS 2008-09.</p>";
$lang['serName']="<p>Le type d'étude ou le type d'enquête est la grande catégorie définissant l'enquête. Sélectionnez une option dans le menu déroulant. Sélectionnez &quot;Autre&quot; si aucune des options ne correspond à votre enquête.</p>";
$lang['serInfo']="<p>Une enquête peut être répétée à intervalles réguliers (comme une enquête annuelle sur la population active) ou faire partie d'un programme d'enquête international (comme le MICS, le CWIQ, l'EDS, le LSMS et autres). L'information sur la série est une description de cette « collection » d'enquêtes. Une brève description des caractéristiques de l'enquête, y compris quand elle a commencé, combien de cycles ont déjà été mis en œuvre et qui en est responsable, serait fournie ici. Si l'enquête n'appartient pas à une série, laissez ce champ vide.</p>";
$lang['parTitl']="<p>Ce champ sera dans la plupart des cas laissé vide. Dans les pays ayant plus d'une langue officielle, une traduction du titre peut être fournie.</p>";
$lang['IDNo']="<pre>
Le numéro d'identification d'un ensemble de données est un numéro unique utilisé pour identifier une enquête particulière. Définir et utiliser un schéma cohérent à utiliser. Un tel ID pourrait être construit comme suit : pays-producteur-année-enquête-version où
- pays est l'abréviation de pays ISO à 3 lettres (par exemple: SEN pour Sénégal)
- producteur est l'abréviation de l'agence productrice
- enquête est l'abréviation de l'enquête
- année est l'année de référence (ou l'année de début de l'enquête)
- version est le numéro de version du jeu de données (voir Description de la version ci-dessous)
</pre>";
$lang['version']="<pre>
<p>La description de la version doit contenir un numéro de version suivi d'une description de la version. Exemples :  </p>
<p>
<ul>
<li>version 0.1 : données brutes de base, obtenues à partir de la saisie des données (avant édition).</li>
<li>version 1.2 : données modifiées, deuxième version, à usage interne uniquement. </li>
<li>version 2.1 : ensemble de données édité et anonyme pour distribution publique. </li>
Une brève description de la version doit suivre l'identification numérique. </li>
</ul>
</p>";
$lang['version_idate']="<p>Il s'agit de la date au format ISO (aaaa-mm-jj) de la production effective et finale des données. Indiquez au moins le mois et l'année. </p>";
$lang['version_notes']="<p>Les notes de version doivent fournir un bref rapport sur les modifications apportées au cours du processus de gestion des versions. La note doit indiquer en quoi cette version diffère des autres versions du même jeu de données.</p>";
$lang['overview_abstract']="<p>Le résumé doit fournir un résumé clair des buts, des objectifs et du contenu de l'enquête. </p>";
$lang['instructions_project_submit']="<p> Une fois que vous êtes satisfait de l'exactitude des informations que vous avez saisies pour votre étude, vous êtes prêt à soumettre votre projet. Veuillez sélectionner une politique d'accès appropriée pour la distribution des données, un catalogue dans lequel les données doivent être publiées, toutes les notes que vous pourriez avoir concernant les embargos, les informations sensibles qui doivent être supprimées avant la distribution ou toute autre note ou instruction spéciale que vous souhaitez à soumettre à la bibliothèque de microdonnées.</p>";
$lang['instructions_project_contributor_review']="<p> Once you are satisfied that the information you have entered for your study is correct you are ready to submit your project. Please select a suitable access policy for the distribution of the data, a catalog in which the data should be published, any notes you might have regarding embargoes, sensitive information that needs to be removed before distribution or any other notes or special instructions you would like to submit to the Microdata Library.</p> ";
$lang['instructions_datafiles_usage']="
<p> Téléchargez tous les fichiers que vous souhaitez partager. Cela comprend les fichiers de données (dans n'importe quel format), les questionnaires, les autres instruments d'enquête et les descriptions de la méthodologie, les fichiers de programme et tous les rapports. Une fois les fichiers téléchargés, veuillez utiliser le lien Modifier (police bleue) ci-dessous pour définir le type de fichier ou de document approprié pour cette ressource. Au minimum, les fichiers de données et le questionnaire doivent être fournis.</p>";
$lang['instructions_citations']="<p>Si vous avez publié des travaux qui utilisent l'ensemble de données en cours de dépôt, vous pouvez entrer les informations de citation pour eux dans cette section. Ces références seront ajoutées à la page d'affichage de votre étude une fois publiée dans les catalogues de la bibliothèque de microdonnées.</p>";
$lang['anlyUnit']="<p>Unité(s) de base d'analyse ou d'observation que l'étude décrit : individus, familles/ménages, groupes, installations, institutions/organisations, unités administratives, emplacements physiques, etc.</p>
<p>Exemples :</p>
<p>
<ul>
<li> Une enquête sur les niveaux de vie avec un questionnaire au niveau de la communauté comporterait les unités d'analyse suivantes : les individus, les ménages et les communautés.</li>
<li> Une enquête économique pourrait avoir l'entreprise et l'établissement comme unités d'analyse.</li></ul>
</p>";
$lang['dataKind']="<p>Ce champ est une large classification des données et il est associé à une liste déroulante fournissant un vocabulaire contrôlé. </p>";
$lang['keyword']="<p>Les mots clés résument le contenu ou le sujet de l'enquête. En tant que classifications thématiques, ils sont utilisés pour faciliter le référencement et les recherches dans les catalogues d'enquêtes. </p>";
$lang['scope_notes']="<p>La portée est une description des thèmes couverts par l'enquête. Il peut être considéré comme un résumé des modules inclus dans le questionnaire. Le champ d'application ne traite pas de la couverture géographique. </p>
<p>Exemple :</p>
<p>La portée de l'enquête en grappes à indicateurs multiples comprend :</p>
<ul>
<li> MÉNAGE : caractéristiques du ménage, liste des ménages, enfants orphelins et vulnérables, éducation, travail des enfants, eau et assainissement, utilisation par le ménage de moustiquaires imprégnées d'insecticide et iodation du sel, avec des modules facultatifs pour la discipline de l'enfant, le handicap de l'enfant, la mortalité maternelle et sécurité d'occupation et pérennité des logements.</li>
<li>FEMMES : caractéristiques des femmes, mortalité infantile, anatoxine tétanique, santé maternelle et néonatale, mariage, polygynie, mutilation génitale féminine, contraception et connaissances sur le VIH/sida, avec des modules optionnels pour les besoins non satisfaits, la violence domestique et le comportement sexuel.</li>
<li>ENFANTS : caractéristiques des enfants, enregistrement des naissances et apprentissage précoce, vitamine A, allaitement, prise en charge des maladies, paludisme, vaccination et anthropométrie, avec un module facultatif sur le développement de l'enfant.</li>
</ul>";
$lang['topcClas']="<p>Une classification thématique facilite le référencement et les recherches dans les catalogues électroniques d'enquêtes. </p> <p>Étant donné que la bibliothèque de microdonnées n'a pas formulé de liste de sujets standard, ce champ peut être laissé vide</p>";
$lang['nation']="<p>Entrez le nom du pays, même dans les cas où l'enquête n'a pas couvert l'ensemble du pays. Dans le champ &quot;Abréviation&quot;, nous vous conseillons de saisir le code ISO à 3 lettres du pays. Si l'ensemble de données que vous documentez couvre plusieurs pays, saisissez-les tous dans des lignes distinctes.</p>";
$lang['geogCover']="<p>Ce champ vise à décrire la couverture géographique de l'échantillon. Les entrées typiques seront &quot;Couverture nationale&quot;, &quot;Zones urbaines (ou rurales) uniquement&quot;, &quot;État de ...&quot;, &quot;Capitale&quot;, etc. </p>";
$lang['study_help']="Veuillez remplir les champs dans chacune des sections ci-dessous. Fournir des informations détaillées ici accélérera le processus de publication de l'étude. Cela permet également aux utilisateurs des données de trouver plus facilement les informations dont ils ont besoin et réduit ainsi la nécessité pour les utilisateurs de contacter le producteur de données pour obtenir des éclaircissements. Seuls trois champs sont obligatoires pour le processus de soumission. Si le temps ou les informations disponibles ne permettent pas de remplir tous les champs, nous demandons qu'au moins les champs obligatoires et recommandés soient renseignés.";
$lang['create_title']="Indiquez le titre complet de votre projet.";
$lang['create_short']="Fournissez un acronyme court pour votre projet. (par exemple, UZB HBS 1998)";
$lang['create_collab']="Ajoutez les adresses e-mail des personnes autorisées à modifier ce projet.";
$lang['create_desc']="Fournissez une description détaillée de votre projet.";
$lang['help_subtitle']="Fournissez un court sous-titre pour votre enquête.";
$lang['section_identification']="Identification";
$lang['section_version']="Version";
$lang['no_files_uploaded']="Aucun fichier importé";
$lang['no_citations_attached']="Aucune citation";

$lang['country_universe']='<p>Il s’agit ici de l’univers de l’enquête (et non de l’univers de certaines sections des questionnaires ou de certaines variables), c’est-à-dire de l’identification de la population d’intérêt de l’enquête. L’univers correspondra rarement à l’ensemble de la population du pays. Les enquêtes par sondage auprès des ménages, par exemple, ne couvrent généralement pas les sans-abri, les nomades, les diplomates ni les ménages collectifs. Certaines enquêtes peuvent ne couvrir que la population d’une tranche d’âge particulière, ou uniquement les hommes (ou les femmes), etc.</p>';
$lang['AuthEnty']='<p>Le chercheur principal est l’institution (ou, dans certains cas, la ou les personnes) chargée de la conception et de la mise en œuvre de l’enquête (et non de son financement ou de l’assistance technique). </p>
<p>L’ordre dans lequel ils sont énumérés est laissé à votre appréciation. Il peut être alphabétique ou fonction de l’importance de la contribution. </p>';
$lang['AuthEnty_iaffiliation']='';
$lang['producers']='<p>Abréviation, affiliation et rôle. Si l’un de ces champs ne s’applique pas, il peut être laissé vide. Les abréviations doivent correspondre à l’abréviation officielle de l’organisation. </p> 
<p>Le rôle doit être une expression ou une description brève et concise de l’assistance spécifique fournie par l’organisation pour produire les données. </p>
<p>Exemples de rôles : </p>
<ul>
<li> [Assistance technique pour la] conception du questionnaire</li>
<li>[Assistance technique pour la] méthodologie / sélection de l’échantillon</li>
<li>[Assistance technique pour la] collecte des données</li>
<li>[Assistance technique pour le] traitement des données</li>
<li>[Assistance technique pour l’]analyse des données</li>
<p>N’incluez pas ici les bailleurs de fonds.</p>
';
$lang['fundAg']='<p>Énumérez les organisations (nationales ou internationales) qui ont contribué, en espèces ou en nature, au financement de l’enquête. </p>';
$lang['othId_p']='<p>Ce champ facultatif peut être utilisé pour remercier toute autre personne ou institution ayant contribué d’une manière ou d’une autre à l’enquête. </p>';
$lang['sampProc']='<p>Ce champ ne s’applique qu’aux enquêtes par sondage. Les informations sur la procédure d’échantillonnage sont essentielles (bien que non applicables aux recensements et aux données administratives). Cette section doit comprendre des informations synthétiques portant notamment, mais pas exclusivement, sur :</p>
<ul>
<li>la taille de l’échantillon</li>
<li>le processus de sélection (par exemple, probabilité proportionnelle à la taille ou suréchantillonnage)</li>
<li>la stratification (implicite et explicite)</li>
<li>les degrés de sélection de l’échantillon</li>
<li> les omissions dans le plan de sondage</li>
<li>le niveau de représentativité</li>
<li> la stratégie adoptée pour les répondants absents, introuvables ou les refus (remplacement ou non)</li>
<li> la base de sondage utilisée et le dénombrement effectué pour la mettre à jour</li>
<p>Il est également utile d’indiquer ici quelles variables des fichiers de données identifient les différents niveaux de stratification et l’unité primaire d’échantillonnage. Ces informations sont essentielles pour les utilisateurs qui souhaitent tenir compte correctement du plan de sondage dans leurs analyses et dans le calcul des erreurs d’échantillonnage. </p>
<p>Cette section n’accepte que du texte ; il n’est pas possible d’y saisir des formules. Dans la plupart des cas, il existe des documents techniques décrivant en détail la stratégie d’échantillonnage. Le cas échéant, indiquez ici une référence (titre/auteur/date) à ce document et assurez-vous qu’il est téléversé dans la section Fichiers de données et autres ressources. </p>';
$lang['deviat']='<p>Ce champ ne s’applique qu’aux enquêtes par sondage.</p>
<p>Il arrive que les réalités du terrain imposent un écart par rapport au plan de sondage (par exemple en raison de difficultés d’accès à certaines zones dues aux conditions météorologiques, à l’instabilité politique, etc.). Si, pour une raison quelconque, le plan de sondage n’a pas été respecté, il convient de le signaler ici. </p>
';
$lang['respRate']='<p>Le taux de réponse indique le pourcentage de ménages (ou d’autres unités d’échantillonnage) ayant participé à l’enquête par rapport à la taille initiale de l’échantillon. Des omissions peuvent survenir en raison d’un refus de participer, de l’impossibilité de localiser le répondant ou pour d’autres raisons. Il arrive aussi qu’un ménage soit remplacé par un autre, conformément au plan de sondage. Vérifiez que les informations fournies ici sont cohérentes avec la taille de l’échantillon indiquée dans le champ « Procédure d’échantillonnage » et avec le nombre d’enregistrements figurant dans le jeu de données (par exemple, si le plan de sondage prévoit un échantillon de 5 000 ménages et que les données ne portent que sur 4 500 ménages, le taux de réponse ne peut pas être de 100 pour cent).</p>
<p>Si possible, indiquez les taux de réponse par strate. Si des informations sont disponibles sur les causes de non-réponse (refus/introuvable/autre), fournissez-les également.</p>
<p>Dans certains cas, ce champ peut également servir à décrire les non-réponses dans les recensements de population.</p>';
$lang['weight']='Ce champ ne s’applique qu’aux enquêtes par sondage ou aux échantillons de recensement.
<p>Indiquez ici la liste des variables utilisées comme coefficients de pondération. S’il existe plusieurs variables de pondération, décrivez en quoi elles diffèrent les unes des autres et à quoi sert chacune d’elles. </p>
<p>Exemple :</p>
<p>Des poids d’échantillonnage ont été calculés pour chacun des fichiers de données.</p>
</p>Les poids d’échantillonnage des données sur les ménages ont été calculés comme l’inverse de la probabilité de sélection du ménage, au niveau du domaine d’échantillonnage (urbain/rural au sein de chaque région). Les poids des ménages ont été ajustés pour tenir compte de la non-réponse au niveau du domaine, puis normalisés par un facteur constant de sorte que le nombre total pondéré de ménages soit égal au nombre total non pondéré de ménages. La variable de pondération des ménages s’appelle HHWEIGHT et est utilisée avec les données HH et HL.</p>
<p>Les poids d’échantillonnage des données sur les femmes reposent sur les poids des ménages non normalisés, ajustés pour tenir compte de la non-réponse au questionnaire femme, puis normalisés par un facteur constant de sorte que le nombre total pondéré de femmes soit égal au nombre total non pondéré de femmes.</p>
<p>Les poids d’échantillonnage des données sur les enfants suivent la même approche que pour les femmes : ils reposent sur les poids des ménages non normalisés, ajustés pour tenir compte de la non-réponse au questionnaire enfant, puis normalisés par un facteur constant de sorte que le nombre total pondéré d’enfants soit égal au nombre total non pondéré d’enfants.</p>
';
$lang['collDate']='<p>Saisissez les dates (au moins le mois et l’année) de début et de fin de la collecte des données. </p>
<p>Dans certains cas, la collecte des données d’une même enquête peut être réalisée en plusieurs vagues. Dans ce cas, saisissez séparément les dates de début et de fin de chaque vague, et identifiez chaque vague dans le champ « cycle ». </p>';
$lang['timePrd']='<p>Ce champ sera généralement laissé vide. La période de référence diffère des dates de collecte, car elle représente la période à laquelle les données collectées s’appliquent ou se rapportent. </p>';
$lang['collMode']='<p>Le mode de collecte des données désigne la manière dont l’entretien a été mené ou dont l’information a été recueillie. Ce champ utilise un vocabulaire contrôlé. </p>
<p>Utilisez la liste déroulante pour sélectionner une option. Dans la plupart des cas, la réponse sera « entretien en face à face ». Mais pour certains types particuliers de données, comme les données pluviométriques, la réponse sera différente.</p>';
$lang['collSitu']='<p>Cet élément permet de documenter toute observation, tout incident ou tout événement particulier survenu pendant la collecte des données. Envisagez de préciser des éléments tels que :</p>
<ul>
<li>Une formation des enquêteurs a-t-elle été organisée ? (Précisez)</li>
<li>Des événements ont-ils pu avoir une incidence sur la qualité des données ? </li>
<li>Quelle a été la durée moyenne d’un entretien ? </li>
<li> Y a-t-il eu un processus de négociation entre les ménages, la communauté et l’organisme d’exécution ? </li>
<li>  Des faits anecdotiques ont-ils été consignés ? </li>
<li>  Les équipes de terrain ont-elles contribué en fournissant des informations sur les problèmes et incidents survenus pendant la collecte ? </li>
<li>  Dans quelle langue l’entretien a-t-il été mené ? </li>
<li>  Une enquête pilote a-t-elle été réalisée ? </li>
<li>  La direction a-t-elle pris des mesures correctives lorsque des problèmes sont survenus sur le terrain ? </li>
</ul>
<p>Exemple :<p>
<p>Le prétest de l’enquête s’est déroulé du 15 au 25 août 2006 et a mobilisé 14 enquêteurs qui sont ensuite devenus superviseurs pour l’enquête principale.</p>
<p>Chaque équipe d’enquête était composée de 3 à 4 enquêtrices (aucun enquêteur masculin n’a été employé en raison de la sensibilité du sujet), d’un contrôleur de terrain, d’un superviseur et d’un chauffeur. Au total, 52 enquêteurs, 14 superviseurs et 14 contrôleurs de terrain ont été mobilisés. La collecte des données s’est déroulée sur une période d’environ 6 semaines, du 2 septembre au 17 octobre 2006. Les entretiens ont eu lieu tous les jours pendant toute la durée du travail de terrain, bien que les équipes aient été autorisées à prendre un jour de repos par semaine. </p>
<p>Les entretiens ont duré en moyenne 35 minutes pour le questionnaire ménage (hors test du sel), 23 minutes pour le questionnaire femme et 27 minutes pour le questionnaire enfant de moins de cinq ans (hors anthropométrie). Les entretiens ont été menés principalement en anglais et en Mumbo-jumbo, mais des traductions locales en double-Dutch ont parfois été utilisées lorsque le répondant ne parlait ni l’anglais ni le Mumbo-jumbo.</p>
<p>Six membres du personnel de GenCenStat ont assuré la coordination et la supervision générales du travail de terrain. La coordinatrice générale du terrain était Mme Doe.</p>';
$lang['resInstru']='<p>Cet élément permet de décrire le ou les questionnaires utilisés pour la collecte des données. Il convient de mentionner :</p>
<ul>
<li>La liste des questionnaires et une brève description de chacun d’eux (tous les questionnaires doivent être fournis en tant que ressources externes)</li>
<li> Dans quelle langue les questionnaires ont-ils été publiés ? </li>
<li> Des informations sur le processus de conception du questionnaire (fondé sur un questionnaire antérieur, sur un questionnaire type standard, examen par les parties prenantes). Si un document regroupant les commentaires des parties prenantes sur le projet de questionnaire, ou un rapport sur le test du questionnaire, a été établi, une référence à ces documents doit être indiquée ici et les documents doivent être fournis en tant que ressources externes. </li>
</ul>
<p>Exemple :</p>
<p>Les questionnaires de la MICS générique étaient des questionnaires structurés fondés sur le questionnaire type MICS3, avec quelques modifications et ajouts. Un questionnaire ménage a été administré dans chaque ménage afin de recueillir des informations sur les membres du ménage, notamment le sexe, l’âge, le lien de parenté et la situation d’orphelin. Le questionnaire ménage porte sur les caractéristiques du ménage, le soutien aux enfants orphelins et vulnérables, l’éducation, le travail des enfants, l’eau et l’assainissement, l’utilisation par le ménage de moustiquaires imprégnées d’insecticide et l’iodation du sel, avec des modules facultatifs sur la discipline des enfants, le handicap des enfants, la mortalité maternelle ainsi que la sécurité d’occupation et la durabilité du logement.</p>
<p>Outre le questionnaire ménage, des questionnaires ont été administrés dans chaque ménage aux femmes âgées de 15 à 49 ans et aux enfants de moins de cinq ans. Pour les enfants, le questionnaire a été administré à la mère ou à la personne qui s’occupe de l’enfant. </p>
<p>Le questionnaire femme porte sur les caractéristiques des femmes, la mortalité des enfants, l’anatoxine tétanique, la santé maternelle et néonatale, le mariage, la polygynie, l’excision, la contraception et les connaissances sur le VIH/sida, avec des modules facultatifs sur les besoins non satisfaits, la violence domestique et le comportement sexuel.</p>
<p>Le questionnaire enfant porte sur les caractéristiques des enfants, l’enregistrement des naissances et l’apprentissage précoce, la vitamine A, l’allaitement, la prise en charge des maladies, le paludisme, la vaccination et l’anthropométrie, avec un module facultatif sur le développement de l’enfant.</p>
<p>Les questionnaires ont été élaborés en anglais à partir des questionnaires types MICS3, puis traduits en Mumbo-jumbo. Après un premier examen, les questionnaires ont été retraduits en anglais par un traducteur indépendant n’ayant aucune connaissance préalable de l’enquête. La rétrotraduction de la version en Mumbo-jumbo a été examinée de manière indépendante et comparée à l’original anglais. Les divergences de traduction ont été examinées et résolues en collaboration avec les traducteurs initiaux.</p>
<p>Les questionnaires anglais et Mumbo-jumbo ont tous deux été testés dans le cadre du prétest de l’enquête.</p>
<p>Tous les questionnaires et modules sont fournis en tant que ressources externes.</p>';
$lang['dataCollector']='<p>Cet élément permet d’enregistrer des informations sur les personnes et/ou les organismes qui ont pris en charge la collecte des données. Il comprend 3 champs : Nom, Abréviation et Affiliation. Dans la plupart des cas, on indiquera ici le nom de l’organisme et non celui des enquêteurs. Ce n’est que dans le cas d’enquêtes de très petite envergure, avec un nombre très limité d’enquêteurs, que le nom des personnes sera également indiqué. Le champ Affiliation est facultatif et n’est pas pertinent dans tous les cas.</p>';
$lang['actMin']='<p>Résumé des mesures prises pour limiter la perte de données. Il s’agit notamment d’informations sur des actions telles que les visites de relance, les contrôles de supervision, l’appariement historique, l’estimation, etc. Notez que cet élément n’a pas à inclure d’informations détaillées sur les taux de réponse, un élément de métadonnées spécifique étant prévu à cet effet.</p>';
$lang['cleanOps']='<p>La section sur l’apurement des données doit indiquer comment les données ont été traitées ou contrôlées en matière de cohérence. Cet élément ne concerne pas la phase de saisie des données, mais uniquement l’apurement des données, qu’il soit manuel ou automatique. </p>
<ul>
<li>A-t-on utilisé une technique d’imputation de type « hot deck » ou « cold deck » pour apurer les données ?</li>
<li> Les corrections ont-elles été effectuées automatiquement (par programme) ou par contrôle visuel du questionnaire ? </li>
<li>Quel logiciel a été utilisé ?  </li>
</ul>
<p>Si des documents sont disponibles (spécifications d’apurement des données, rapport sur l’apurement, programmes utilisés pour l’apurement), ils doivent être énumérés ici et téléversés dans la section Fichiers de données et autres ressources. </p>
<p>Exemple :</p>
<p>L’apurement des données a eu lieu à plusieurs étapes du traitement, notamment :</p>
<ol>
<li>Contrôle et codification au bureau</li>
<li>Pendant la saisie des données</li>
<li>Contrôle de la structure et de l’exhaustivité</li>
<li>Apurement secondaire</li>
<li>Contrôle structurel des fichiers de données SPSS</li>
</ol>
<p>Une documentation détaillée de l’apurement des données figure dans le document « Directives de traitement des données » fourni en tant que ressource externe.</p>';
$lang['method_notes']='<p>Utilisez ce champ pour fournir autant d’informations que possible sur l’organisation de la saisie des données, notamment :</p>
<ul>
<li>Le mode de saisie des données (manuelle ou par numérisation, sur le terrain/dans les régions/au siège)</li>
<li>L’architecture informatique (ordinateurs portables sur le terrain, ordinateurs de bureau, scanners, PDA, autres ; indiquez le nombre d’ordinateurs utilisés) </li>
<li> Les logiciels utilisés </li>
<li>Le recours à la double saisie (et son taux) </li>
<li>La productivité moyenne des opérateurs de saisie ; le nombre d’opérateurs de saisie mobilisés et leurs horaires de travail</li>
</ul>
<p>Des informations sur la tabulation et l’analyse peuvent également être fournies ici. </p>
<p>Tous les documents disponibles (programmes de saisie, de tabulation et d’analyse ; rapports sur la saisie des données) doivent être énumérés ici et fournis en tant que ressources externes.</p>
<p>Exemple :</p>
<p>Les données ont été traitées par grappe, chaque grappe étant traitée comme une unité complète à chaque étape du traitement des données. Chaque grappe passe par les étapes suivantes :</p>
<ol>
<li> Réception des questionnaires</li>
<li> Contrôle et codification au bureau</li>
<li> Saisie des données</li>
<li> Contrôle de la structure et de l’exhaustivité</li>
<li> Saisie de vérification</li>
<li> Comparaison des données de vérification</li>
<li> Sauvegarde des données brutes</li>
<li> Apurement secondaire</li>
<li> Sauvegarde des données apurées</li>
<p>Une fois toutes les grappes traitées, l’ensemble des données est concaténé, puis les étapes suivantes sont réalisées pour tous les fichiers de données :</p>
<li> Exportation vers SPSS en 4 fichiers (hh - ménages, hl - membres des ménages, wm - femmes, ch - enfants de moins de 5 ans)</li>
<li> Recodage des variables nécessaires à l’analyse</li>
<li> Ajout des poids d’échantillonnage</li>
<li> Calcul des quintiles de richesse et intégration aux données</li>
<li> Contrôle structurel des fichiers SPSS</li>
<li> Tableaux sur la qualité des données</li>
<li> Production des tableaux d’analyse</li>
</ol>
<p>Le détail de chacune de ces étapes figure dans la documentation sur le traitement des données, les directives d’apurement des données, les programmes de traitement des données sous CSPro et SPSS, et les directives de tabulation.</p>
<p>La saisie des données a été réalisée par 12 opérateurs de saisie travaillant en deux équipes, encadrés par 2 superviseurs de saisie, sur un total de 7 ordinateurs (6 ordinateurs de saisie et un ordinateur pour les superviseurs). Toute la saisie a été effectuée manuellement au siège de GenCenStat. Pour la saisie des données, CSPro version 2.6.007 a été utilisé avec un programme de saisie très structuré, selon une approche contrôlée par le système qui encadrait la saisie de chaque variable. Tous les contrôles de plage et les sauts étaient gérés par le programme, et les opérateurs ne pouvaient pas les contourner. Un ensemble limité de contrôles de cohérence était également intégré au programme de saisie. En outre, le calcul des scores Z anthropométriques a été intégré aux programmes de saisie en vue de l’analyse. Les réponses ouvertes (réponses « Autre ») n’ont été ni saisies ni codées, sauf dans de rares cas où la réponse correspondait à un code existant du questionnaire. </p>  
<p>Le contrôle de la structure et de l’exhaustivité a permis de s’assurer que tous les questionnaires de la grappe avaient été saisis, que leur structure était correcte et qu’il existait un questionnaire femme et un questionnaire enfant pour chaque femme et chaque enfant éligibles. </p>
<p>Une vérification exhaustive de toutes les variables a été effectuée par vérification indépendante, c’est-à-dire par double saisie des données, suivie d’une comparaison distincte des données et de la modification de l’un des jeux de données, ou des deux, par les opérateurs ayant initialement saisi les fichiers, afin de corriger les erreurs de frappe. </p>
<p>Une fois l’ensemble du traitement sous CSPro achevé, tous les fichiers de grappe individuels ont été sauvegardés avant d’être concaténés à l’aide de l’utilitaire de concaténation de fichiers de CSPro.</p>
<p>Les versions 10.0 et 14.0 de SPSS ont été utilisées pour la tabulation et l’analyse. La version 10.0 a initialement servi pour tous les programmes de tabulation, à l’exception de la mortalité des enfants. Après le transfert de tous les fichiers vers SPSS, certaines variables ont été recodées afin de servir de caractéristiques sociodémographiques dans la tabulation des données, notamment le regroupement par âge, niveau d’instruction et zone géographique selon les besoins de l’analyse. Lors du recodage des âges et des dates, une imputation aléatoire de certaines dates (dans des limites calculées) a été effectuée pour traiter les âges ou dates manquants ou « ne sait pas ». En outre, un indice de richesse (patrimoine) des membres des ménages a été calculé par analyse en composantes principales à partir des biens des ménages, et le score comme les quintiles ont été inclus dans les jeux de données en vue de leur utilisation dans les tableaux.</p>
';
$lang['EstSmpErr']='<p>Pour les enquêtes par sondage, il est de bonne pratique de calculer et de publier les erreurs d’échantillonnage. Ce champ sert à fournir des informations sur ces calculs, notamment :</p>
<ul>
<li>La liste des ratios/indicateurs pour lesquels les erreurs d’échantillonnage ont été calculées. </li>
<li> Des précisions sur le logiciel utilisé pour calculer les erreurs d’échantillonnage, ainsi qu’une référence aux programmes utilisés pour effectuer les calculs (à fournir en tant que ressources externes).</li>
<li>Une référence aux rapports ou autres documents où figurent les résultats (à fournir en tant que ressources externes). </li>
</ul>
<p>Exemple :</p>
<p>Les estimations issues d’une enquête par sondage sont affectées par deux types d’erreurs : 1) les erreurs non dues à l’échantillonnage et 2) les erreurs d’échantillonnage. Les erreurs non dues à l’échantillonnage résultent d’erreurs commises lors de la collecte et du traitement des données. De nombreux efforts ont été déployés lors de la mise en œuvre de la MICS 2005-2006 pour réduire ce type d’erreur ; toutefois, les erreurs non dues à l’échantillonnage sont impossibles à éviter et difficiles à évaluer statistiquement.</p>
<p>Si l’échantillon de répondants avait été un échantillon aléatoire simple, il aurait été possible d’utiliser des formules simples pour calculer les erreurs d’échantillonnage. Cependant, l’échantillon de la MICS 2005-2006 résulte d’un plan stratifié à plusieurs degrés et nécessite par conséquent des formules plus complexes. Le module SPSS Complex Samples a été utilisé pour calculer les erreurs d’échantillonnage de la MICS 2005-2006. Ce module utilise la méthode de linéarisation de Taylor pour l’estimation de la variance des estimations d’enquête qui sont des moyennes ou des proportions. Cette méthode est documentée dans le fichier SPSS CSDescriptives.pdf, accessible via les options Aide, Algorithmes de SPSS. </p>
</p>Les erreurs d’échantillonnage ont été calculées pour une sélection de statistiques (toutes des proportions, en raison des limites de la méthode de linéarisation de Taylor) pour l’échantillon national, les zones urbaines et rurales, et pour chacune des cinq régions. Pour chaque statistique sont présentés l’estimation, son erreur type, le coefficient de variation (ou erreur relative, soit le rapport entre l’erreur type et l’estimation), l’effet de plan de sondage et la racine carrée de l’effet de plan (DEFT, soit le rapport entre l’erreur type obtenue avec le plan de sondage retenu et celle qui résulterait d’un échantillon aléatoire simple), ainsi que les intervalles de confiance à 95 pour cent (+/-2 erreurs types).</p>
<p>Le détail des erreurs d’échantillonnage est présenté dans l’annexe du rapport consacrée aux erreurs d’échantillonnage et dans le tableau des erreurs d’échantillonnage fourni parmi les ressources externes.</p>
';
$lang['dataAppr']='<p>Cette section peut servir à rendre compte de toute autre mesure prise pour évaluer la fiabilité des données, ou de toute observation relative à la qualité des données. Cet élément peut inclure :</p>
<ul>
<li>Pour un recensement de population, des informations sur l’enquête post-censitaire (un rapport doit être fourni/téléversé dans la section Fichiers de données et autres ressources et mentionné ici). </li>
<li>Pour toute enquête ou tout recensement, une comparaison avec des données provenant d’une autre source.</li>
<li> Etc.</li>
<p>Exemple :</p>
<p>Une série de tableaux et de graphiques est disponible pour examiner la qualité des données ; elle comprend :</p>
<ul>
<li>Répartition par âge de la population des ménages</li>
<li> Répartition par âge des femmes éligibles et des femmes interrogées</li>
<li> Répartition par âge des enfants éligibles et des enfants dont la mère ou la personne qui s’en occupe a été interrogée</li>
<li> Répartition par âge des enfants de moins de 5 ans par groupes de 3 mois</li>
<li>Rapports d’âge et de période aux limites d’éligibilité</li>
<li> Pourcentage d’observations comportant des informations manquantes pour certaines variables</li>
<li> Présence de la mère dans le ménage et personne interrogée pour le questionnaire des moins de 5 ans</li>
<li> Fréquentation scolaire par année d’âge</li>
<li> Rapport de masculinité à la naissance parmi les enfants nés vivants, survivants et décédés, selon l’âge de la répondante</li>
<li> Répartition des femmes selon le temps écoulé depuis la dernière naissance</li>
<li> Nuage de points du poids selon la taille, du poids selon l’âge et de la taille selon l’âge</li>
<li> Graphique de la population masculine et féminine par année d’âge</li>
<li> Pyramide des âges</li>
</ul>
<p>Les résultats de chacun de ces tableaux sur la qualité des données figurent en annexe du rapport final et sont également fournis dans la section des ressources externes.<p>
<p>En règle générale, dans les tableaux du rapport final, une colonne est présentée pour les données manquantes si le pourcentage de cas comportant des données manquantes est égal ou supérieur à 1 pour cent. Les cas présentant des données manquantes sur les caractéristiques sociodémographiques (par exemple, le niveau d’instruction) sont inclus dans les tableaux, mais les lignes correspondant aux données manquantes sont supprimées et signalées au bas des tableaux du rapport (mais pas dans les sorties SPSS).</p>
';
$lang['useStmt_contact']='<p>Cette section comprend plusieurs champs : Nom-Affiliation-E-mail-URI. Ces informations désignent la personne ou l’entité à contacter pour obtenir l’autorisation d’accéder aux données. Il est conseillé d’utiliser, dans la mesure du possible, une adresse e-mail générique telle que microdata@worldbank.org afin d’éviter de lier l’accès à une personne dont les fonctions peuvent évoluer au fil du temps.<p/>';
$lang['confDec']='<p>Si le jeu de données n’est pas anonymisé, on peut indiquer ici quel engagement de confidentialité doit être signé avant de pouvoir accéder aux données. Une autre option consiste à inclure cette information dans l’élément suivant (Conditions d’accès). En l’absence de problème de confidentialité, ce champ peut être laissé vide.</p>';
$lang['conditions']='<p>Chaque jeu de données doit être assorti d’une « politique d’accès ». La bibliothèque de microdonnées recommande l’un des niveaux d’accessibilité des données suivants :</p>
<ul>
<li>Fichiers à usage public, accessibles à tous.</li>
<li> Jeux de données sous licence, accessibles sous conditions et après examen.</li>
<li>Données disponibles auprès d’un dépôt externe </li>
</ul>
<p>La Banque mondiale a élaboré des politiques et des formulaires d’accès standard et génériques pour chacun de ces types d’accès. </p> <p>Les conditions d’utilisation de la Banque mondiale pour les données du catalogue public externe peuvent être consultées <a href=http://microdata.worldbank.org/index.php/terms-of-use”  target=”_blank”>ici </a> et les conditions d’utilisation du catalogue interne réservé au personnel peuvent être consultées <a href=”http://microdatalib.worldbank.org/index.php/terms “ target=”_blank”>ici.</a></p>
';
$lang['citReq']='<p>La mention de citation obligatoire indique la manière dont le jeu de données doit être référencé lorsqu’il est cité dans une publication. Chaque jeu de données doit comporter une mention de citation obligatoire. Cela garantit que le producteur des données est dûment crédité et que les résultats analytiques peuvent être rattachés à la bonne version du jeu de données. La politique d’accès doit mentionner explicitement l’obligation de respecter cette mention de citation. La citation doit inclure au minimum le chercheur principal, le nom et l’abréviation du jeu de données, l’année de référence et le numéro de version. Indiquez également un site web où le dépositaire officiel des données met à disposition les données ou des informations sur celles-ci.</p>
<p><small>Sarah Baird, George Washington University, Craig McIntosh, University of California San Diego, Berk Ozler, Banque mondiale. Tanzania Second Social Action Fund (TASAF II) - Vulnerable Groups Impact Evaluation - Round I, Household Survey 2008, Réf. TZA_2008_TASAF-II_v01_M_v01_A_PUF. Jeu de données téléchargé depuis [URL] le [date]</small></p>.
';
$lang['disclaimer']='<p>Exemple : L’utilisateur des données reconnaît que le collecteur initial des données, le distributeur autorisé des données et l’organisme de financement concerné déclinent toute responsabilité quant à l’utilisation des données ou aux interprétations ou inférences fondées sur cette utilisation. </p>';
$lang['copyright']='<p>Indiquez ici une mention de droit d’auteur relative au jeu de données, par exemple :</p>
 <p>(c) 2007, Banque mondiale</p>
';

$lang['impact_wb_name_help']='<p>Le nom correspondant au code d’évaluation d’impact de la Banque mondiale ou, si l’évaluation d’impact ne dispose pas d’un code distinct de la Banque mondiale, le titre/nom sous lequel cette évaluation d’impact est connue</p>';
$lang['impact_wb_id_help']='<p>Le code d’évaluation d’impact de la Banque mondiale correspondant (par exemple, P012345). Saisissez « N/A » si cette évaluation d’impact ne dispose pas d’un code distinct de la Banque mondiale</p>';
$lang['impact_wb_lead_help']='<p>Le ou les chefs d’équipe de projet (TTL) de la Banque mondiale et/ou le ou les consultants/chercheurs principaux</p>';
$lang['impact_wb_members_help']='<p>Autres membres du personnel de la Banque mondiale ou chercheurs/consultants ayant travaillé sur cette évaluation d’impact</p>';
$lang['impact_wb_description_help']='<p>Le nom correspondant au code d’évaluation d’impact de la Banque mondiale ou, si l’évaluation d’impact ne dispose pas d’un code distinct de la Banque mondiale, le titre/nom sous lequel cette évaluation d’impact est connue.</p>';
$lang['operational_wb_name_help']='<p>Le nom de l’opération de la Banque mondiale à laquelle cette évaluation d’impact est rattachée</p>';
$lang['operational_wb_id_help']='<p>Le code de projet (par exemple, P012345) de l’opération associée</p>';
$lang['operational_wb_summary_help']='<p>Fournissez un résumé/aperçu de l’opération et des objectifs de développement du projet</p>';
$lang['distStmt_contact']='<p>Les utilisateurs des données peuvent avoir besoin de précisions et d’informations complémentaires. Cette section peut inclure le nom, l’affiliation, l’adresse e-mail et l’URI d’une ou de plusieurs personnes à contacter. Évitez d’indiquer des noms de personnes. Les informations fournies ici doivent rester valables sur le long terme. Il est donc préférable de désigner les personnes à contacter par leur fonction. Il en va de même pour le champ e-mail. Idéalement, une adresse e-mail « générique » devrait être fournie. Il est facile de configurer un serveur de messagerie de sorte que tous les messages envoyés à l’adresse générique soient automatiquement transférés à certains membres du personnel.</p>';
$lang['catalog_to_publish_help']='<p>Choisissez Externe si vous avez l’intention de partager vos données avec le public. Choisissez Interne si vous avez l’intention de les partager uniquement avec le personnel de la Banque. Les études du catalogue externe sont automatiquement incluses dans le catalogue interne</p>';
$lang['is_embargoed_help']='<p>Si cette étude ne doit pas être diffusée auprès du personnel de la Banque ou du public pendant une certaine période, c’est-à-dire si un embargo sur sa diffusion s’applique, cochez cette case. Veuillez indiquer dans le champ ci-dessous la durée et les conditions de l’embargo</p>';
$lang['disclosure_risk_help']='<p>Il est important de protéger l’identité et la vie privée des répondants qui ont fourni les données. Si les fichiers de données que vous déposez contiennent des variables d’identification (par exemple, noms, numéros d’identification officiels, coordonnées SIG précises, etc.) ou toute variable jugée sensible dans le contexte de votre étude (par exemple, origine ethnique, tribu, religion, etc.), veuillez les énumérer ci-dessous afin que l’accès à ces variables puisse être supprimé.</p>';
$lang['notes_to_library_help']='Saisissez ici toute note ou instruction supplémentaire.';
$lang['notes_to_embargoed_help']='<p>Indiquez les modalités de l’embargo, par exemple la durée et toute autre instruction particulière.</p>';
$lang['cc_help']='<p>Saisissez les adresses e-mail des autres personnes qui doivent recevoir une copie récapitulative de votre soumission.</p>';
$lang['suggested_access_policy_help']='<p>Sélectionnez la politique d’accès adaptée à vos données. Pour plus de détails, consultez les conditions d’utilisation suivantes pour le catalogue interne : <a href="http://microdatalib.worldbank.org/index.php/terms">http://microdatalib.worldbank.org/index.php/terms</a> et, pour le catalogue externe, les conditions d’utilisation suivantes : <a href="http://microdata.worldbank.org/index.php/terms-of-use">http://microdata.worldbank.org/index.php/terms-of-use</a></p>';


/* End of file dd_help */
/* Location: ./application/language/french/dd_help */