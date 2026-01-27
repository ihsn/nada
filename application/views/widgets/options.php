<?php

$page_url=current_url();
$qs=http_build_query($this->input->get(NULL,true));

/*if (!empty($qs)){
    $page_url=$page_url.'?'.$qs;
}*/

$embed_code=html_escape('<div data-pym-src="'. ($page_url).'"></div> <script type="text/javascript" src="https://pym.nprapps.org/pym.v1.min.js"></script>');
?>

<style>
.widget-footer-bar {
    font-family: 'Arial', sans-serif;
    margin-top: 10px;
}

.widget-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #6c757d;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

.widget-btn:hover {
    color: #5a6268;
    text-decoration: none;
}

.widget-btn svg {
    width: 1em;
    height: 1em;
}

.widget-share-panel {
    position: sticky;
    bottom: 0px;
    width: 100%;
    display: none;
    overflow: hidden;
    transition: display 0.3s ease;
}

.widget-share-panel.show {
    display: block;
}

.widget-share-content {
    background: gainsboro;
    padding: 15px;
    position: relative;
}

.widget-btn-close {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 4px 8px;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #6c757d;
    background-color: transparent;
    border: 1px solid transparent;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: color 0.15s ease-in-out;
}

.widget-btn-close:hover {
    color: #495057;
}

.widget-btn-close svg {
    width: 16px;
    height: 16px;
    display: block;
}

.widget-form-group {
    margin-bottom: 1rem;
    font-family: 'Arial', sans-serif;
}

.widget-form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #212529;
}

.widget-input-group {
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    width: 100%;
    margin-bottom: 0.5rem;
}

.widget-input-group input {
    position: relative;
    flex: 1 1 auto;
    width: 1%;
    min-width: 0;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem 0 0 0.25rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.widget-input-group input:focus {
    color: #495057;
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.widget-input-append {
    display: flex;
    margin-left: -1px;
}

.widget-input-text {
    display: flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    margin-bottom: 0;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    text-align: center;
    white-space: nowrap;
    background-color: #e9ecef;
    border: 1px solid #ced4da;
    border-radius: 0 0.25rem 0.25rem 0;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

.widget-input-text:hover {
    background-color: #d6d9dc;
}

.widget-input-text svg {
    width: 1em;
    height: 1em;
}
</style>

<div class="widget-footer-bar">
    <button class="widget-btn widget-btn-options" type="button" id="btn-share" aria-expanded="false" aria-controls="shareOptions">
        <svg class="bi bi-gear" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" d="M8.837 1.626c-.246-.835-1.428-.835-1.674 0l-.094.319A1.873 1.873 0 014.377 3.06l-.292-.16c-.764-.415-1.6.42-1.184 1.185l.159.292a1.873 1.873 0 01-1.115 2.692l-.319.094c-.835.246-.835 1.428 0 1.674l.319.094a1.873 1.873 0 011.115 2.693l-.16.291c-.415.764.42 1.6 1.185 1.184l.292-.159a1.873 1.873 0 012.692 1.116l.094.318c.246.835 1.428.835 1.674 0l.094-.319a1.873 1.873 0 012.693-1.115l.291.16c.764.415 1.6-.42 1.184-1.185l-.159-.291a1.873 1.873 0 011.116-2.693l.318-.094c.835-.246.835-1.428 0-1.674l-.319-.094a1.873 1.873 0 01-1.115-2.692l.16-.292c.415-.764-.42-1.6-1.185-1.184l-.291.159A1.873 1.873 0 018.93 1.945l-.094-.319zm-2.633-.283c.527-1.79 3.065-1.79 3.592 0l.094.319a.873.873 0 001.255.52l.292-.16c1.64-.892 3.434.901 2.54 2.541l-.159.292a.873.873 0 00.52 1.255l.319.094c1.79.527 1.79 3.065 0 3.592l-.319.094a.873.873 0 00-.52 1.255l.16.292c.893 1.64-.902 3.434-2.541 2.54l-.292-.159a.873.873 0 00-1.255.52l-.094.319c-.527 1.79-3.065 1.79-3.592 0l-.094-.319a.873.873 0 00-1.255-.52l-.292.16c-1.64.893-3.433-.902-2.54-2.541l.159-.292a.873.873 0 00-.52-1.255l-.319-.094c-1.79-.527-1.79-3.065 0-3.592l.319-.094a.873.873 0 00.52-1.255l-.16-.292c-.892-1.64.902-3.433 2.541-2.54l.292.159a.873.873 0 001.255-.52l.094-.319z" clip-rule="evenodd"></path>
            <path fill-rule="evenodd" d="M8 5.754a2.246 2.246 0 100 4.492 2.246 2.246 0 000-4.492zM4.754 8a3.246 3.246 0 116.492 0 3.246 3.246 0 01-6.492 0z" clip-rule="evenodd"></path>
        </svg>
        Share
    </button>
</div>

<div class="widget-share-panel" id="shareOptions">
    <div class="widget-share-content">
        <button 
            type="button" 
            id="btn-close" 
            class="widget-btn-close" 
            aria-label="Close"  
            aria-expanded="false" 
            aria-controls="shareOptions">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
            </svg>
        </button>

        <div class="widget-form-group">
            <label for="default_options">Embed code</label>
            <div class="widget-input-group">
                <input type="text" id="default_options" placeholder="" value="<?php echo $embed_code;?>">
                <div class="widget-input-append">
                    <div class="widget-input-text" id="btn-copy" role="button" aria-label="Copy to clipboard">
                        <svg class="bi bi-clipboard-data" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M4 1.5H3a2 2 0 00-2 2V14a2 2 0 002 2h10a2 2 0 002-2V3.5a2 2 0 00-2-2h-1v1h1a1 1 0 011 1V14a1 1 0 01-1 1H3a1 1 0 01-1-1V3.5a1 1 0 011-1h1v-1z" clip-rule="evenodd"></path>
                            <path fill-rule="evenodd" d="M9.5 1h-3a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h3a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5zm-3-1A1.5 1.5 0 005 1.5v1A1.5 1.5 0 006.5 4h3A1.5 1.5 0 0011 2.5v-1A1.5 1.5 0 009.5 0h-3z" clip-rule="evenodd"></path>
                            <path d="M4 11a1 1 0 112 0v1a1 1 0 11-2 0v-1zm6-4a1 1 0 112 0v5a1 1 0 11-2 0V7zM7 9a1 1 0 012 0v3a1 1 0 11-2 0V9z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    function copyToClipboard(text) {
        // Try modern Clipboard API first
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).then(function() {
                return true;
            }).catch(function() {
                return false;
            });
        }
        
        // Fallback for older browsers
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-999999px';
        textarea.style.top = '-999999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        
        try {
            var successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            return Promise.resolve(successful);
        } catch (err) {
            document.body.removeChild(textarea);
            return Promise.resolve(false);
        }
    }
    
    function toggleSharePanel() {
        var panel = document.getElementById('shareOptions');
        var btnShare = document.getElementById('btn-share');
        var isExpanded = panel.classList.contains('show');
        
        if (isExpanded) {
            panel.classList.remove('show');
            btnShare.setAttribute('aria-expanded', 'false');
        } else {
            panel.classList.add('show');
            btnShare.setAttribute('aria-expanded', 'true');
        }
    }
    
    function hideSharePanel() {
        var panel = document.getElementById('shareOptions');
        var btnShare = document.getElementById('btn-share');
        panel.classList.remove('show');
        btnShare.setAttribute('aria-expanded', 'false');
    }
    
    // Initialize when DOM is ready
    function init() {
        var btnShare = document.getElementById('btn-share');
        var btnClose = document.getElementById('btn-close');
        var btnCopy = document.getElementById('btn-copy');
        var inputField = document.getElementById('default_options');
        
        if (btnShare) {
            btnShare.addEventListener('click', toggleSharePanel);
        }
        
        if (btnClose) {
            btnClose.addEventListener('click', hideSharePanel);
        }
        
        if (btnCopy && inputField) {
            btnCopy.addEventListener('click', function() {
                copyToClipboard(inputField.value).then(function(success) {
                    if (success) {
                        // Visual feedback could be added here
                        var originalTitle = btnCopy.getAttribute('aria-label');
                        btnCopy.setAttribute('aria-label', 'Copied!');
                        setTimeout(function() {
                            btnCopy.setAttribute('aria-label', originalTitle || 'Copy to clipboard');
                        }, 2000);
                    }
                });
            });
        }
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>   