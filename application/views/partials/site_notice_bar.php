<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Site notice header bar. Added to public pages by inject_site_notice() in config/hooks.php.
 * Styles are self-contained so the bar looks the same in every theme.
 *
 * @var string $text Plain text
 */
?>
<div id="site-notice-bar" role="note">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
    <span><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></span>
</div>
<script>
// Not inside iframes, where popups and embedded pages would each show their own bar.
if (window.self !== window.top) { document.getElementById('site-notice-bar').remove(); }
</script>
<style>
#site-notice-bar{display:flex;align-items:center;justify-content:center;gap:8px;box-sizing:border-box;width:100%;margin:0;padding:8px 16px;border-bottom:1px solid #fcd34d;background:#fef3c7;color:#78350f;font-size:14px;font-weight:500;line-height:1.4;text-align:center;overflow-wrap:anywhere;}
#site-notice-bar svg{flex:none;}
/* The blank template fixes .site-header to the top of the page, which would cover the bar. Compare Variables
   is excluded because it hides the site header and sets its own layout in catalog_search/compare.php. */
body.wb-template-blank:has(> #site-notice-bar):not(:has(.compare-header)) .site-header{position:sticky;}
body.wb-template-blank:has(> #site-notice-bar):not(:has(.compare-header)) .wb-page-body.container-fluid{margin-top:40px;}
@media print{#site-notice-bar{display:none;}}
</style>
