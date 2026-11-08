<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Site notice dialog. Added to public pages by inject_site_notice() in config/hooks.php.
 * Styles are self-contained so the dialog looks the same in every theme.
 *
 * @var string $title   Plain text, may be empty
 * @var string $message Plain text; line breaks are preserved by white-space: pre-line
 * @var int    $minutes How long a dismissal is remembered; 0 shows it on every page load
 */
?>
<dialog id="site-notice" <?php echo $title !== '' ? 'aria-labelledby="site-notice-title"' : 'aria-label="Site notice"'; ?>>
    <div class="site-notice__header">
        <span class="site-notice__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        </span>
        <?php if ($title !== ''): ?>
        <h2 id="site-notice-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php endif; ?>
    </div>
    <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
    <form method="dialog">
        <button type="submit" autofocus><?php echo t('site_notice_explore_demo'); ?></button>
    </form>
</dialog>
<style>
#site-notice{position:fixed;inset:0;margin:auto;height:fit-content;max-height:calc(100% - 32px);overflow:auto;box-sizing:border-box;max-width:520px;width:calc(100% - 32px);padding:36px;border:0;border-radius:14px;background:#fff;color:#4b5563;line-height:1.6;box-shadow:0 24px 60px rgba(15,23,42,.25);}
#site-notice[open]{animation:site-notice-in .18s ease-out;}
#site-notice::backdrop{background:rgba(15,23,42,.55);backdrop-filter:blur(2px);}
#site-notice .site-notice__header{display:flex;align-items:center;gap:14px;margin:0 0 18px;}
#site-notice .site-notice__icon{flex:none;display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:#eff6ff;color:#1d4ed8;}
#site-notice h2{margin:0;font-size:1.375rem;font-weight:600;line-height:1.3;color:#111827;}
#site-notice p{margin:0 0 28px;white-space:pre-line;overflow-wrap:anywhere;}
#site-notice form{margin:0;text-align:end;}
#site-notice button{padding:10px 22px;border:0;border-radius:8px;background:#1d4ed8;color:#fff;font:inherit;font-weight:600;cursor:pointer;transition:background-color .15s;}
#site-notice button:hover{background:#1e40af;}
#site-notice button:focus-visible{outline:3px solid #93c5fd;outline-offset:2px;}
@keyframes site-notice-in{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:none;}}
@media (prefers-reduced-motion:reduce){#site-notice[open]{animation:none;}#site-notice button{transition:none;}}
@media (max-width:480px){#site-notice{padding:24px;}}
</style>
<script>
(function () {
    var dialog = document.getElementById('site-notice');
    // Not inside iframes, where popups and embedded pages would each open their own dialog.
    if (window.self !== window.top || typeof dialog.showModal !== 'function') { return; }

    // Keyed by content, so changing the notice shows it again straight away.
    var key = 'nada_site_notice_<?php echo md5($title . "\n" . $message); ?>';
    var rememberMs = <?php echo (int) $minutes; ?> * 60 * 1000;

    var dismissedAt = 0;
    try { dismissedAt = parseInt(localStorage.getItem(key), 10) || 0; } catch (e) {}
    if (Date.now() - dismissedAt < rememberMs) { return; }

    // Fires for both the button and the Esc key.
    dialog.addEventListener('close', function () {
        try { localStorage.setItem(key, String(Date.now())); } catch (e) {}
    });
    dialog.showModal();
})();
</script>
