<?php
/**
 * Standalone installer shell — no public theme.
 * Assets come from themes/assets/ (shared Bootstrap / jQuery).
 */
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

$assets = base_url() . 'themes/assets';
$page_title = isset($title) && $title !== '' ? $title : tt('installer_title', 'NADA Installer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo html_escape($page_title); ?></title>
	<link rel="stylesheet" href="<?php echo $assets; ?>/bootstrap/4.1.3/css/bootstrap.min.css">
	<script src="<?php echo base_url(); ?>javascript/jquery/jquery.js"></script>
	<script src="<?php echo $assets; ?>/popper/1.14.3/popper.min.js"></script>
	<script src="<?php echo $assets; ?>/bootstrap/4.1.3/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		var CI = { base_url: '<?php echo site_url(); ?>' };
	</script>
</head>
<body class="bg-light">
	<div class="container border rounded mt-4 p-0 shadow mb-5 bg-white" style="max-width:700px;">
		<nav class="navbar navbar-light bg-light mb-3">
			<span class="navbar-brand mb-0 h1"><?php echo html_escape(tt('installer_title', 'NADA Installer')); ?></span>
		</nav>
		<div class="px-3 pb-3">
			<?php echo isset($content) ? $content : ''; ?>
		</div>
	</div>
</body>
</html>
