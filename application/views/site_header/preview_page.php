<?php
/**
 * Sample page body under the header preview (site_layout_preview/header), shown in a frame on the admin page.
 *
 * The admin page sends new header HTML with postMessage as settings change, so the frame does not reload.
 * Messages are accepted only from the parent window on this site. The page reports the header height back, and
 * a click on a header row (outside links and buttons) asks the editor to open that row's settings.
 * Links do not leave the preview; menus and dropdowns still open.
 */
$labels = array(
	'top'    => tt('sh_row_top', 'Top bar'),
	'main'   => tt('sh_row_main', 'Main row'),
	'bottom' => tt('sh_row_bottom', 'Bottom bar'),
);
?>
<style>
.sh-preview-body .sh-ph{background:#eef1f4;border-radius:4px}
body.sh-preview-edit [data-sh-row]{position:relative;cursor:pointer}
body.sh-preview-edit [data-sh-row]:hover{outline:2px dashed rgba(21,101,192,.7);outline-offset:-2px}
body.sh-preview-edit [data-sh-row]:hover::after{content:attr(data-sh-label);position:absolute;left:4px;top:2px;z-index:5;
  font:600 11px/1.6 system-ui,sans-serif;color:#fff;background:#1565c0;padding:0 6px;border-radius:3px;pointer-events:none}
</style>
<div class="sh-preview-body py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="sh-ph mb-3" style="height:18px;width:55%"></div>
            <div class="sh-ph mb-2" style="height:10px"></div>
            <div class="sh-ph mb-2" style="height:10px;width:92%"></div>
            <div class="sh-ph mb-2" style="height:10px;width:85%"></div>
            <div class="sh-ph mb-4" style="height:10px;width:60%"></div>
        </div>
        <div class="col-md-4">
            <div class="sh-ph" style="height:90px"></div>
        </div>
    </div>
</div>
<script>
(function () {
    var origin = window.location.origin;
    var labels = <?php echo json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var framed = window.parent && window.parent !== window;

    function header() {
        return document.querySelector('header.site-header');
    }

    function label() {
        var rows = document.querySelectorAll('[data-sh-row]');
        for (var i = 0; i < rows.length; i++) {
            rows[i].setAttribute('data-sh-label', labels[rows[i].getAttribute('data-sh-row')] || '');
        }
    }

    function send(msg) {
        if (framed) {
            window.parent.postMessage(msg, origin);
        }
    }

    function report() {
        var h = header();
        send({ type: 'sh-size', height: h ? Math.ceil(h.getBoundingClientRect().height) : 0 });
    }

    window.addEventListener('message', function (e) {
        if (e.origin !== origin || e.source !== window.parent || !e.data || e.data.type !== 'sh-header') {
            return;
        }
        var old = header();
        if (!old || typeof e.data.html !== 'string') {
            return;
        }
        var style = old.previousElementSibling;
        if (style && style.matches('style[data-sh-style]')) {
            style.parentNode.removeChild(style);
        }
        var tpl = document.createElement('template');
        tpl.innerHTML = e.data.html;
        old.parentNode.replaceChild(tpl.content, old);
        document.body.classList.toggle('sh-preview-edit', !!e.data.editRows);
        label();
        report();
    });

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (a && !a.hasAttribute('data-toggle')) {
            e.preventDefault();
            return;
        }
        if (!document.body.classList.contains('sh-preview-edit') || e.target.closest('a,button,.dropdown-menu')) {
            return;
        }
        var row = e.target.closest('[data-sh-row]');
        if (row) {
            send({ type: 'sh-edit-row', row: row.getAttribute('data-sh-row') });
        }
    }, true);

    label();
    if (window.ResizeObserver) {
        new ResizeObserver(report).observe(document.body);
    }
    window.addEventListener('load', report);
    send({ type: 'sh-ready' });
})();
</script>
