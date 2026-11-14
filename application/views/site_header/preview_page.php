<?php
/**
 * Sample page body under the header preview. Links do not leave the preview; menus and dropdowns still open.
 */
?>
<div class="sh-preview-body py-4">
    <div class="row">
        <div class="col-md-8">
            <div class="bg-light rounded mb-3" style="height:18px;width:55%"></div>
            <div class="bg-light rounded mb-2" style="height:10px"></div>
            <div class="bg-light rounded mb-2" style="height:10px;width:92%"></div>
            <div class="bg-light rounded mb-2" style="height:10px;width:85%"></div>
            <div class="bg-light rounded mb-4" style="height:10px;width:60%"></div>
            <div class="bg-light rounded mb-2" style="height:10px"></div>
            <div class="bg-light rounded mb-2" style="height:10px;width:78%"></div>
        </div>
        <div class="col-md-4">
            <div class="bg-light rounded" style="height:120px"></div>
        </div>
    </div>
</div>
<script>
document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a') : null;
    if (!a || a.hasAttribute('data-toggle')) {
        return;
    }
    e.preventDefault();
}, true);
</script>
