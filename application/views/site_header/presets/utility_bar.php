<?php
/**
 * Utility bar: language and login in a thin bar on top; brand and menu below.
 */
return array(
	'title'       => 'Utility bar',
	'description' => 'A thin bar on top with language and login. Brand and menu on the line below.',
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
    <div class="row">
        <div class="col-12">
            <nav class="navbar navbar-expand-md navbar-light rounded wb-navbar">
                {{brand}}
                {{menu}}
            </nav>
        </div>
    </div>
</div>
HTML
	,
	'css'         => '.site-header .sh-utility-bar{font-size:.85rem;border-bottom:1px solid var(--wbg-lightbluegray,#e3e8ee)}'
		. '.site-header .sh-utility-bar .nav-link{padding:.3rem .5rem}'
		. '.site-header .sh-utility-bar .lang-container{padding-left:0;margin-right:.5rem}',
	'thumbnail'   => '<svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="160" height="14" fill="currentColor" opacity=".08"/><rect x="104" y="5" width="22" height="4" rx="2" fill="currentColor" opacity=".35"/><circle cx="140" cy="7" r="3.5" fill="currentColor" opacity=".7"/><rect x="8" y="28" width="40" height="10" rx="2" fill="currentColor" opacity=".8"/><rect x="8" y="41" width="26" height="4" rx="2" fill="currentColor" opacity=".4"/><rect x="92" y="32" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="112" y="32" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="132" y="32" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/></svg>',
);
