<?php
/**
 * Classic: the default NADA header. Language links above; brand on the left, menu and login on the right.
 */
return array(
	'title'       => 'Classic',
	'description' => 'Brand on the left, menu and login on the right. Language links above. The default NADA header.',
	'html'        => <<<'HTML'
<div class="container-fluid wb-user-bar sh-optional">
    <div class="container">
        <div class="float-right">{{language}}</div>
        <br/>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-12">
            <nav class="navbar navbar-expand-md navbar-light rounded navbar-toggleable-md wb-navbar">
                {{brand}}
                {{menu+login}}
            </nav>
        </div>
    </div>
</div>
HTML
	,
	'css'         => '',
	'thumbnail'   => '<svg viewBox="0 0 160 64" xmlns="http://www.w3.org/2000/svg"><rect x="118" y="6" width="34" height="4" rx="2" fill="currentColor" opacity=".35"/><rect x="8" y="22" width="40" height="10" rx="2" fill="currentColor" opacity=".8"/><rect x="8" y="35" width="26" height="4" rx="2" fill="currentColor" opacity=".4"/><rect x="74" y="26" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="94" y="26" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><rect x="114" y="26" width="16" height="5" rx="2" fill="currentColor" opacity=".55"/><circle cx="142" cy="28.5" r="4" fill="currentColor" opacity=".7"/></svg>',
);
