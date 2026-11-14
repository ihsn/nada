<?php
/**
 * Banner: brand over a background image band, menu bar below. Upload a background image under Background.
 */
return array(
	'title'       => 'Banner',
	'description' => 'Brand over a background image or colour band, with a menu bar below. Best with a background image.',
	'html'        => <<<'HTML'
<div class="sh-hero">
    <div class="sh-optional">
        <div class="container d-flex justify-content-end align-items-center pt-1">
            {{language}}
            {{login}}
        </div>
    </div>
    <div class="container sh-hero-brand">
        {{brand}}
    </div>
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
	'background_selector' => '.site-header .sh-hero',
	'color_selector'      => '.site-header .sh-hero',
	'css'         => '.site-header .sh-hero{min-height:140px;display:flex;flex-direction:column;background-color:var(--wbg-lightbluegray,#eef2f6)}'
		. '.site-header .sh-hero-brand{flex:1;display:flex;align-items:center;padding-top:1rem;padding-bottom:1rem}'
		. '.site-header .sh-hero-brand .nada-site-title{font-size:1.5rem!important}'
		. '.site-header .sh-hero .nav-link{padding:.3rem .5rem}'
		. '.site-header .sh-hero .lang-container{padding-left:0;margin-right:.5rem}'
		. '.site-header .sh-menu-bar{border-bottom:1px solid var(--wbg-lightbluegray,#e3e8ee)}'
		. '.site-header .sh-menu-bar .navbar-nav{margin-top:0}'
		. '.site-header .sh-menu-bar .navbar-nav.ml-auto{margin-left:0!important}'
		. '@media (max-width:767.98px){.site-header .sh-hero{min-height:100px}}',
	'thumbnail'   => '<svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="0" width="160" height="42" fill="currentColor" opacity=".18"/><path d="M0 42 L40 22 L70 34 L105 14 L160 38 L160 42 Z" fill="currentColor" opacity=".15"/><rect x="104" y="4" width="22" height="4" rx="2" fill="currentColor" opacity=".45"/><circle cx="140" cy="6" r="3" fill="currentColor" opacity=".7"/><rect x="8" y="18" width="48" height="11" rx="2" fill="currentColor" opacity=".85"/><rect x="8" y="49" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="28" y="49" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="48" y="49" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/></svg>',
);
