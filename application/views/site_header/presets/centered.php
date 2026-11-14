<?php
/**
 * Centered: brand centered, menu centered on its own line below.
 */
return array(
	'title'       => 'Centered',
	'description' => 'Brand in the middle, menu and login centered on the line below.',
	'html'        => <<<'HTML'
<div class="sh-optional">
    <div class="container">
        <div class="d-flex justify-content-end">{{language}}</div>
    </div>
</div>
<div class="container">
    <div class="sh-centered-brand d-flex justify-content-center pt-2">
        {{brand}}
    </div>
    <nav class="navbar navbar-expand-md navbar-light wb-navbar sh-centered-nav">
        {{menu+login}}
    </nav>
</div>
HTML
	,
	'css'         => '.site-header .sh-centered-brand .navbar-brand{margin-right:0;text-align:center}'
		. '.site-header .sh-centered-nav{justify-content:center;border-top:1px solid var(--wbg-lightbluegray,#e3e8ee)}'
		. '.site-header .sh-centered-nav .navbar-nav.ml-auto{margin-left:auto!important;margin-right:auto!important;margin-top:0}'
		. '@media (max-width:767.98px){.site-header .sh-centered-nav .navbar-toggler{margin:0 auto}}',
	'thumbnail'   => '<svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg"><rect x="60" y="8" width="40" height="10" rx="2" fill="currentColor" opacity=".8"/><rect x="67" y="21" width="26" height="4" rx="2" fill="currentColor" opacity=".4"/><rect x="8" y="33" width="144" height="1" fill="currentColor" opacity=".2"/><rect x="38" y="42" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="58" y="42" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="78" y="42" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="98" y="42" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><circle cx="122" cy="44.5" r="3.5" fill="currentColor" opacity=".7"/></svg>',
);
