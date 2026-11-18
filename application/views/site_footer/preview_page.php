<?php
/**
 * Sample page body above the footer preview (site_layout_preview/footer).
 * The admin page sends new footer HTML with postMessage as settings change.
 * When framed in the admin preview, only the footer is shown (header/body are hidden).
 */
?>
<style>
.sf-preview-body .sf-ph{background:#eef1f4;border-radius:4px}
/* Admin iframe: show only the footer */
html.sf-preview-footer-only body > *:not(footer):not(style){display:none!important}
html.sf-preview-footer-only body{margin:0;min-height:0;background:transparent}
html.sf-preview-footer-only footer{margin:0!important}
</style>
<div class="sf-preview-body py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="sf-ph mb-3" style="height:18px;width:55%"></div>
            <div class="sf-ph mb-2" style="height:10px"></div>
            <div class="sf-ph mb-2" style="height:10px;width:92%"></div>
            <div class="sf-ph mb-2" style="height:10px;width:85%"></div>
            <div class="sf-ph mb-4" style="height:10px;width:60%"></div>
        </div>
        <div class="col-md-4">
            <div class="sf-ph" style="height:90px"></div>
        </div>
    </div>
</div>
<script>
(function () {
    var origin = window.location.origin;
    var framed = window.parent && window.parent !== window;

    if (framed) {
        document.documentElement.classList.add('sf-preview-footer-only');
    }

    function footer() {
        return document.querySelector('footer.site-footer') || document.querySelector('footer');
    }

    function send(msg) {
        if (framed) {
            window.parent.postMessage(msg, origin);
        }
    }

    function report() {
        var f = footer();
        send({ type: 'sf-size', height: f ? Math.ceil(f.getBoundingClientRect().height) : 0 });
    }

    window.addEventListener('message', function (e) {
        if (e.origin !== origin || e.source !== window.parent || !e.data || e.data.type !== 'sf-footer') {
            return;
        }
        var old = footer();
        if (!old || typeof e.data.html !== 'string') {
            return;
        }
        var style = old.previousElementSibling;
        if (style && style.matches && style.matches('style[data-sf-style]')) {
            style.parentNode.removeChild(style);
        }
        var tpl = document.createElement('template');
        tpl.innerHTML = e.data.html;
        old.parentNode.replaceChild(tpl.content, old);
        report();
    });

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (a) {
            e.preventDefault();
        }
    }, true);

    if (window.ResizeObserver) {
        var f = footer();
        if (f) {
            new ResizeObserver(report).observe(f);
        } else {
            new ResizeObserver(report).observe(document.body);
        }
    }
    window.addEventListener('load', report);
    send({ type: 'sf-ready' });
})();
</script>
