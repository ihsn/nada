<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; }
body {
	margin: 0;
	min-height: 100vh;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
	font-size: 16px;
	line-height: 1.5;
	color: #1a1a1a;
	background: #f0f2f5;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 24px;
}
.access-denied-page {
	width: 100%;
	max-width: 520px;
	background: #fff;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
	padding: 40px 32px;
	text-align: center;
}
.access-denied-page__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 64px;
	height: 64px;
	margin-bottom: 20px;
	border-radius: 50%;
	background: #fdecea;
	color: #c62828;
	font-size: 18px;
	font-weight: 700;
	letter-spacing: 0.02em;
}
.access-denied-page__title {
	margin: 0 0 12px;
	font-size: 24px;
	font-weight: 600;
	color: #212529;
}
.access-denied-page__message {
	margin: 0;
	color: #495057;
}
</style>
</head>
<body>
<main class="access-denied-page" role="alert">
	<div class="access-denied-page__icon" aria-hidden="true">403</div>
	<h1 class="access-denied-page__title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
	<p class="access-denied-page__message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
</main>
</body>
</html>
