/**
 * NADA Analytics Tracker V2
 * ----------------------------------------------------------
 * Features:
 * - Advanced bot filtering
 * - GA fallback logic
 * - Dedupe expiration window
 * - Improved session management
 * - Error-safe communication
 * - Single-responsibility architecture
 *
 * Downloads are still server-side ONLY.
 */

(function(window, document) {
    'use strict';

    window.NADA = window.NADA || {};
    NADA.Analytics = {};

    // Default configuration
    const DEFAULT_CONFIG = {
        baseUrl: '', // Must be provided via init() or NADA.Analytics.config
        enabled: true,
        builtinEnabled: true,
        gaEnabled: false, // Set to false to always use built-in tracking
        sessionTimeoutMinutes: 30,
        dedupeWindowMinutes: 30,
        storagePrefix: 'nada_analytics_',
        debug: false,
        userAgentBotPatterns: [
            /headless/i,
            /crawler/i,
            /spider/i,
            /bot/i,
            /wget/i,
            /curl/i,
            /python-requests/i,
            /axios/i,
            /httpclient/i,
            /lighthouse/i,
            /puffin/i,
            /playwright/i,
            /puppeteer/i
        ]
    };

    // Merged configuration (will be set during init)
    let CONFIG = {};

    function log(msg, level = 'info') {
        if (!CONFIG.debug) return;
        const prefix = '[NADA Analytics]';
        console[level === 'error' ? 'error' : 'log'](prefix, msg);
    }

    function getStorage(key) {
        try {
            return JSON.parse(localStorage.getItem(CONFIG.storagePrefix + key));
        } catch (_) { return null; }
    }

    function setStorage(key, val) {
        try {
            localStorage.setItem(CONFIG.storagePrefix + key, JSON.stringify(val));
        } catch (_) { /* ignore */ }
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random()*16|0;
            const v = c === 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        });
    }

    function initSession() {
        const now = Date.now();
        let session = getStorage('session');

        if (session) {
            const ageMin = (now - session.startTime) / 60000;
            if (ageMin < CONFIG.sessionTimeoutMinutes) {
                return session;
            }
        }

        // create new
        session = {
            id: generateUUID(),
            startTime: now,
            lastTracked: {}
        };
        setStorage('session', session);

        return session;
    }

    function isBot() {
        const ua = navigator.userAgent || "";

        if (!ua.trim()) return true;

        return CONFIG.userAgentBotPatterns.some(pattern => pattern.test(ua));
    }

    function isGAAvailable() {
        return typeof window.gtag === 'function' || typeof window.ga === 'function';
    }

    // Use full page URL (pathname + hash) as deduplication key
    // This allows different tabs/pages of the same study to be tracked separately
    function getPageKey() {
        // Use full pathname + hash to distinguish between tabs
        return window.location.pathname + window.location.hash;
    }

    function isRecentlyTracked(session, pageKey) {
        const last = session.lastTracked[pageKey];
        if (!last) return false;

        const minutesAgo = (Date.now() - last) / 60000;
        return minutesAgo < CONFIG.dedupeWindowMinutes;
    }

    function markTracked(session, pageKey) {
        session.lastTracked[pageKey] = Date.now();
        setStorage('session', session);
    }

    function getStudyId() {
        const el = document.querySelector('[data-study-id]');
        if (el) return el.getAttribute('data-study-id');

        if (window.STUDY_ID) return String(window.STUDY_ID);

        const match = window.location.pathname.match(/\/catalog\/([^\/]+)/);
        return match ? match[1] : null;
    }

    async function sendPageview(data) {
        try {
            // Build API URL from baseUrl (ensure proper path construction)
            const base = CONFIG.baseUrl || '';
            const apiUrl = base + (base.endsWith('/') ? '' : '/') + 'api/analytics/pageview';
            log('Fetching: ' + apiUrl);

        const headers = {'Content-Type': 'application/json'};

            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(data)
            });

            log('Response status: ' + res.status);
            log('Response ok: ' + res.ok);

            if (!res.ok) {
                const errorText = await res.text();
                log(`API error: ${res.status} - ${errorText}`, 'error');
                return false;
            }

            const responseData = await res.json();
            log('Response data: ' + JSON.stringify(responseData));
            return true;
        } catch (err) {
            log(`Request failed: ${err.message || err}`, 'error');
            log('Error stack: ' + (err.stack || 'no stack'), 'error');
            return false;
        }
    }
    
    async function trackPageview() {
        if (!CONFIG.enabled || !CONFIG.builtinEnabled) {
            log('Tracking disabled or built-in disabled', 'error');
            return;
        }
        
        if (isBot()) {
            log('Bot detected, skipping.', 'error');
            return;
        }

        const studyId = getStudyId();
        if (!studyId) {
            log('No study ID found. Checked: data-study-id, window.STUDY_ID, URL pattern', 'error');
            return;
        }
        
        log('Study ID found: ' + studyId);

        // GA takes priority unless unavailable
        if (CONFIG.gaEnabled && isGAAvailable()) {
            log('GA available - built-in tracker skipped. Set gaEnabled: false to use built-in tracking.');
            return;
        }

        let session = initSession();
        log('Session ID: ' + session.id);

        // Get page key (full URL path) for deduplication
        // This allows different tabs/pages of the same study to be tracked separately
        const pageKey = getPageKey();
        log('Page key: ' + pageKey);

        // dedupe within window (based on full page URL, not just study_id)
        if (isRecentlyTracked(session, pageKey)) {
            log('Dedupe: pageview suppressed (tracked within last ' + CONFIG.dedupeWindowMinutes + ' minutes)');
            return;
        }

        const payload = {
            study_id: studyId,
            session_id: session.id,
            page_url: window.location.pathname + window.location.search + window.location.hash,
            referrer: document.referrer || null,
            source: 'builtin'
        };

        if (CONFIG.csrfToken && CONFIG.csrfTokenName) {
            payload[CONFIG.csrfTokenName] = CONFIG.csrfToken;
        }

        log('Sending pageview to: ' + CONFIG.apiUrl);
        log('Payload: ' + JSON.stringify(payload));

        const ok = await sendPageview(payload);

        if (ok) {
            markTracked(session, pageKey);
            log('Pageview tracked successfully');
        } else {
            log('Pageview tracking failed', 'error');
        }
    }

    
    // EXTRACT SECTION FROM AJAX URL    
    function extractSectionFromUrl(url) {
        // Parse AJAX request URL to extract section/tab name
        // Examples:
        //   /catalog/452/variable/F3/V120?ajax=true -> 'variable'
        //   /study/data_dictionary?ajax=true -> 'data_dictionary'
        //   /study/related_materials?ajax=true -> 'related_materials'
        
        try {
            // Pattern 1: /catalog/{id}/variable/{...} -> extract 'variable'
            let match = url.match(/\/catalog\/\d+\/([a-z_]+)\//);
            if (match && match[1]) {
                return match[1];
            }
            
            // Pattern 2: /study/{section}?ajax -> extract {section}
            match = url.match(/\/study\/([a-z_]+)\/?[\?#]/);
            if (match && match[1]) {
                const segment = match[1];
                // Filter out framework segments
                if (!['api', 'admin', 'view', 'edit', 'create'].includes(segment)) {
                    return segment;
                }
            }
        } catch (e) {
            console.log('[NADA Analytics] Error extracting section from URL: ' + url, e);
        }
        return null;
    }

    
    // AJAX CONTENT TRACKING    
    function trackAjaxPageview(section, ajaxUrl) {
        console.log('[NADA Analytics] trackAjaxPageview called with section:', section, 'URL:', ajaxUrl);
        
        if (!CONFIG.enabled || !CONFIG.builtinEnabled) {
            console.log('[NADA Analytics] Tracking disabled or built-in disabled');
            return;
        }
        
        if (isBot()) {
            log('Bot detected in AJAX request, skipping.');
            return;
        }

        const studyId = getStudyId();
        console.log('[NADA Analytics] Study ID for AJAX:', studyId);
        if (!studyId) {
            console.log('[NADA Analytics] No study ID found for AJAX tracking');
            return;
        }

        let session = initSession();
        
        // Create a unique page key combining study_id + section
        // This allows deduplication per section, not just per URL
        const ajaxPageKey = studyId + '::' + section;
        console.log('[NADA Analytics] AJAX page key:', ajaxPageKey);

        // Check if this section was recently tracked
        if (isRecentlyTracked(session, ajaxPageKey)) {
            console.log('[NADA Analytics] Dedupe: AJAX section suppressed:', ajaxPageKey);
            return;
        }

        const payload = {
            study_id: studyId,
            session_id: session.id,
            page_url: ajaxUrl,  // Use the AJAX request URL
            section: section,
            referrer: document.referrer || null,
            source: 'ajax'
        };

        if (CONFIG.csrfToken && CONFIG.csrfTokenName) {
            payload[CONFIG.csrfTokenName] = CONFIG.csrfToken;
        }

        console.log('[NADA Analytics] Sending AJAX pageview for section:', section);
        console.log('[NADA Analytics] AJAX Payload:', JSON.stringify(payload));

        sendPageview(payload).then(ok => {
            if (ok) {
                markTracked(session, ajaxPageKey);
                console.log('[NADA Analytics] AJAX pageview tracked successfully:', section);
            } else {
                console.log('[NADA Analytics] AJAX pageview tracking failed:', section);
            }
        }).catch(err => {
            console.log('[NADA Analytics] AJAX tracking promise error:', err);
        });
    }

    function setupJQueryAjaxTracking() {
        // Only setup if jQuery is available
        if (typeof window.jQuery === 'undefined') {
            log('jQuery not available, AJAX tracking skipped');
            return;
        }

        log('Setting up jQuery AJAX tracking...');

        // Hook into jQuery's ajaxComplete event
        // This fires after any AJAX request completes successfully
        window.jQuery(document).on('ajaxComplete', function(event, xhr, settings) {
            try {
                // Extract URL and check if it's an AJAX request from study pages
                const url = settings.url || '';
                console.log('[NADA Analytics] AJAX Complete fired. Full URL:', url);
                
                // Only track AJAX requests from catalog/study pages
                if (!url.includes('catalog/')) {
                    console.log('[NADA Analytics] Skipping - not a catalog request');
                    return;
                }

                // Only track explicit AJAX requests (with ?ajax=true parameter)
                if (!url.includes('ajax=true') && !url.includes('ajax=1')) {
                    console.log('[NADA Analytics] Skipping - no ajax parameter');
                    return;
                }

                // Extract section name from URL
                const section = extractSectionFromUrl(url);
                console.log('[NADA Analytics] Extracted section from URL:', section, 'from URL:', url);
                if (!section) {
                    console.log('[NADA Analytics] Could not extract section from AJAX URL: ' + url);
                    return;
                }

                console.log('[NADA Analytics] AJAX request matched. Section:', section, 'URL:', url);

                // Track this AJAX content as a pageview
                trackAjaxPageview(section, url);
            } catch (err) {
                console.log('[NADA Analytics] Error in AJAX tracking:', err.message || err);
            }
        });

        log('jQuery AJAX tracking initialized');
    }

    function setupHashTracking() {
        // Use hashchange event if available (more efficient)
        if ('onhashchange' in window) {
            window.addEventListener('hashchange', () => {
                setTimeout(trackPageview, 400);
            });
        } else {
            // Fallback for older browsers
            let lastHash = window.location.hash;
            setInterval(() => {
                if (window.location.hash !== lastHash) {
                    lastHash = window.location.hash;
                    setTimeout(trackPageview, 400);
                }
            }, 150);
        }
    }

    function init(userConfig) {
        log('Analytics tracker script loaded');
        
        // Merge user config with defaults
        CONFIG = Object.assign({}, DEFAULT_CONFIG, userConfig || {});
        
        // Build API URL from baseUrl
        if (!CONFIG.baseUrl) {
            // Fallback to CI.base_url if available
            if (typeof CI !== 'undefined' && CI.base_url) {
                CONFIG.baseUrl = CI.base_url;
            } else {
                log('Warning: baseUrl not provided and CI.base_url not available', 'error');
                CONFIG.baseUrl = '';
            }
        }
        
        // Normalize baseUrl (remove trailing slashes, we'll add them in paths)
        CONFIG.baseUrl = CONFIG.baseUrl.replace(/\/+$/, '');
        
        if (!CONFIG.enabled) {
            log('Analytics tracking is disabled', 'error');
            return;
        }
        
        if (!CONFIG.baseUrl) {
            log('Error: baseUrl is required for analytics tracking', 'error');
            return;
        }
        
        log('Initializing analytics tracker...');
        log('Config: baseUrl=' + CONFIG.baseUrl + ', enabled=' + CONFIG.enabled + ', builtinEnabled=' + CONFIG.builtinEnabled + ', gaEnabled=' + CONFIG.gaEnabled);

        // Check if DOM is already ready
        if (document.readyState === 'loading') {
            log('DOM not ready, waiting for DOMContentLoaded...');
            document.addEventListener('DOMContentLoaded', () => {
                log('DOMContentLoaded fired, starting tracking...');
                trackPageview();
                setupHashTracking();
                setupJQueryAjaxTracking();
            });
        } else {
            // DOM already ready, execute immediately
            log('DOM already ready, starting tracking immediately...');
            trackPageview();
            setupHashTracking();
            setupJQueryAjaxTracking();
        }
    }

    // Expose public API
    NADA.Analytics.init = init;
    NADA.Analytics.track = trackPageview;
    NADA.Analytics.config = function(userConfig) {
        CONFIG = Object.assign({}, DEFAULT_CONFIG, CONFIG, userConfig || {});
    };

})(window, document);

