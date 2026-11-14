<?php
/**
 * Three lines: utility bar (language, login), brand, then a full-width menu bar.
 */
return array(
	'title'       => 'Utility bar + menu bar',
	'description' => 'Three lines: language and login on top, then the brand, then a full-width menu bar.',
	'html'        => <<<'HTML'
<div class="sh-utility-bar sh-optional">
    <div class="container">
        <div class="d-flex justify-content-end align-items-center">
            {{language}}
            {{login}}
        </div>
    </div>
</div>
<div class="container">
    <div class="py-2">{{brand}}</div>
</div>
<div class="sh-menu-bar">
    <div class="container">
        <nav class="navbar navbar-expand-md navbar-light wb-navbar">
            {{menu}}
        </nav>
    </div>
</div>
HTML
	,
	'css'         => '.site-header .sh-utility-bar{font-size:.85rem;border-bottom:1px solid var(--wbg-lightbluegray,#e3e8ee)}'
		. '.site-header .sh-utility-bar .nav-link{padding:.3rem .5rem}'
		. '.site-header .sh-utility-bar .lang-container{padding-left:0;margin-right:.5rem}'
		. '.site-header .sh-menu-bar{background:var(--wbg-lightbluegray,#eef2f6)}'
		. '.site-header .sh-menu-bar .navbar-nav{margin-top:0}'
		. '.site-header .sh-menu-bar .navbar-nav.ml-auto{margin-left:0!important}',
	'thumbnail'   => '<svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="160" height="12" fill="currentColor" opacity=".08"/><rect x="104" y="4" width="22" height="4" rx="2" fill="currentColor" opacity=".35"/><circle cx="140" cy="6" r="3" fill="currentColor" opacity=".7"/><rect x="8" y="18" width="40" height="10" rx="2" fill="currentColor" opacity=".8"/><rect x="8" y="31" width="26" height="4" rx="2" fill="currentColor" opacity=".4"/><rect x="0" y="42" width="160" height="16" fill="currentColor" opacity=".1"/><rect x="8" y="47.5" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="28" y="47.5" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="48" y="47.5" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/></svg>',
);
