/**
 * Global Color Mode System
 * Manages Dark/Light/System theme across all pages
 * Applies 'dark' or 'light' class to <html> based on user preference
 */

(function() {
    'use strict';

    const STORAGE_KEY = 'appearance';
    const COOKIE_NAME = 'appearance';

    /**
     * Determine if dark mode should be active
     */
    function isDarkMode(appearance) {
        if (appearance === 'dark') {
            return true;
        }
        if (appearance === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return false;
    }

    /**
     * Apply theme to document
     */
    function applyTheme(appearance) {
        const isDark = isDarkMode(appearance);
        const html = document.documentElement;
        
        // Remove both classes for clean state
        html.classList.remove('dark', 'light');
        
        // Apply appropriate class
        if (isDark) {
            html.classList.add('dark');
        } else {
            html.classList.add('light');
        }
    }

    /**
     * Set cookie for server-side rendering
     */
    function setCookie(value, days = 365) {
        const maxAge = days * 24 * 60 * 60;
        document.cookie = `${COOKIE_NAME}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
    }

    /**
     * Get stored appearance mode
     */
    function getStoredAppearance() {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    }

    /**
     * Initialize theme on page load
     */
    function initializeTheme() {
        const appearance = getStoredAppearance();
        applyTheme(appearance);
    }

    /**
     * Setup radio button event handlers
     */
    function setupRadioButtons() {
        const radios = document.querySelectorAll('input[name="color-mode"]');
        if (radios.length === 0) return;

        const appearance = getStoredAppearance();

        // Set initial checked state
        radios.forEach(radio => {
            radio.checked = radio.value === appearance;
        });

        // Add change listeners
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const mode = this.value;
                    localStorage.setItem(STORAGE_KEY, mode);
                    setCookie(mode);
                    applyTheme(mode);
                }
            });
        });
    }

    /**
     * Listen for system theme changes
     */
    function setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', function() {
                const appearance = getStoredAppearance();
                if (appearance === 'system') {
                    applyTheme('system');
                }
            });
        }
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        initializeTheme();
        setupRadioButtons();
        setupSystemThemeListener();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
