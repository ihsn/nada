<?php
/**
 * Stacked: brand and language on top; a full-width menu bar below with login at the end.
 */
return array(
	'title'       => 'Menu bar',
	'description' => 'Brand and language on top. A full-width menu bar below, with login at the right.',
	'html'        => <<<'HTML'
<div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap py-2">
        {{brand}}
        {{language}}
    </div>
</div>
<div class="sh-menu-bar">
    <div class="container">
        <nav class="navbar navbar-expand-md navbar-light wb-navbar">
            {{menu+login}}
        </nav>
    </div>
</div>
HTML
	,
	'css'         => '.site-header .sh-menu-bar{background:var(--wbg-lightbluegray,#eef2f6)}'
		. '.site-header .sh-menu-bar .navbar-nav{margin-top:0}'
		. '.site-header .sh-menu-bar .navbar-nav.ml-auto{margin-left:0!important}'
		. '@media (min-width:768px){.site-header .sh-menu-bar .navbar-nav{width:100%}.site-header .sh-menu-bar .navbar-nav>.sh-login{margin-left:auto}}',
	'thumbnail'   => '<svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg"><rect x="8" y="8" width="40" height="10" rx="2" fill="currentColor" opacity=".8"/><rect x="8" y="21" width="26" height="4" rx="2" fill="currentColor" opacity=".4"/><rect x="126" y="12" width="26" height="4" rx="2" fill="currentColor" opacity=".35"/><rect x="0" y="34" width="160" height="18" fill="currentColor" opacity=".1"/><rect x="8" y="40.5" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="28" y="40.5" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="48" y="40.5" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><circle cx="148" cy="43" r="3.5" fill="currentColor" opacity=".7"/></svg>',
);
