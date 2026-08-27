<?php
/**
 * Back-compat spec-url. Schema $refs are rewritten with base_url() in
 * Schemas::openapi().
 */
header('Location: ../../index.php/schemas/openapi/' . rawurlencode(basename(__DIR__)), true, 302);
exit;