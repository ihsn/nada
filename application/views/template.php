<html>
   <head>
      <title><?= $title ?></title>
      <link rel="stylesheet" type="text/css" href="main.css" />
      <style>
	  	div {border:1px solid gray;}
	  </style>
   </head>
   <body>
      <div id="wrapper">
         <div id="header">
            <?= $header ?>
         </div>
         <div id="main">
            <div id="content">
               <h2><?= $title ?></h2>
               <div class="post">
                  <?= $content ?>
               </div>
            </div>
            <div id="sidebar">
               <?= $sidebar ?>
            </div>
         </div>
         <div id="footer">
            <?= $footer ?>
         </div>
      </div>
   </body>
</html>