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
<li>FEMMES : caractéristiques des femmes, mortalité infantile, anatoxine tétanique, santé maternelle et néonatale, mariage, polygynie, mutilation génitale féminine, contraception et connaissances sur le VIH/sida, avec des modules optionnels pour les besoins non satisfaits, la violence domestique et le comportement sexuel.< /li>
<li>ENFANTS : caractéristiques des enfants, enregistrement des naissances et apprentissage précoce, vitamine A, allaitement, prise en charge des maladies, paludisme, vaccination et anthropométrie, avec un module facultatif sur le développement de l'enfant.</li>
</ul>";
$lang['topcClas']="<p>Une classification thématique facilite le référencement et les recherches dans les catalogues électroniques d'enquêtes. </p> <p>Étant donné que la bibliothèque de microdonnées n'a pas formulé de liste de sujets standard, ce champ peut être laissé vide</p>";
$lang['nation']="<p>Entrez le nom du pays, même dans les cas où l'enquête n'a pas couvert l'ensemble du pays. Dans le champ &quot;Abréviation&quot;, nous vous conseillons de saisir le code ISO à 3 lettres du pays. Si l'ensemble de données que vous documentez couvre plusieurs pays, saisissez-les tous dans des lignes distinctes.</p>";
$lang['geogCover']="<p>Ce champ vise à décrire la couverture géographique de l'échantillon. Les entrées typiques seront &quot;Couverture nationale&quot;, &quot;Zones urbaines (ou rurales) uniquement&quot;, &quot;État de ...&quot;, &quot;Capitale&quot;, etc. </p>";
$lang['study_help']="Veuillez remplir les champs dans chacune des sections ci-dessous. Fournir des informations détaillées ici accélérera le processus de publication de l'étude. Cela permet également aux utilisateurs des données de trouver plus facilement les informations dont ils ont besoin et réduit ainsi la nécessité pour les utilisateurs de contacter le producteur de données pour obtenir des éclaircissements. Seuls trois champs sont obligatoires pour le processus de soumission. Si le temps ou les informations disponibles ne permettent pas de remplir tous les champs, nous demandons qu'au moins les champs obligatoires et recommandés soient renseignés.";
$lang['create_title']="Indiquez le titre complet de votre projet.";
$lang['create_short']="Fournissez un acronyme court pour votre projet. (par exemple, UZB HBS 1998)";
$lang['create_collab']="Fournissez l'adresse e-mail des autres membres du personnel de la Banque qui peuvent être autorisés à modifier ce projet.";
$lang['create_desc']="Fournissez une description détaillée de votre projet.";
$lang['help_subtitle']="Fournissez un court sous-titre pour votre enquête.";
$lang['section_identification']="Identification";
$lang['section_version']="Version";
$lang['no_files_uploaded']="Aucun fichier importé";
$lang['no_citations_attached']="Aucune citation";


/* End of file dd_help */
/* Location: ./application/language/french/dd_help */