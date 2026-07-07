<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="access-denied-panel" role="alert">
	<div class="access-denied-panel__icon" aria-hidden="true">403</div>
	<h1 class="access-denied-panel__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
	<p class="access-denied-panel__message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
</div>
