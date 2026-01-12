/**
 * Rate Limit Warning System
 * 
 * Monitors response headers for rate limit warnings and displays
 * user-friendly notifications when approaching the limit.
 */
(function() {
    'use strict';

    const CONFIG = {
        warningThreshold: 70,  // Show warning at 70% usage
        criticalThreshold: 90, // Show critical warning at 90%
        toastDuration: 5000,   // How long to show the toast (ms)
        checkInterval: 100,    // How often to check for new responses
    };

    let lastWarningTime = 0;
    const WARNING_COOLDOWN = 10000; // Don't show warnings more than once per 10 seconds

    /**
     * Create and show a toast notification
     */
    function showToast(message, type = 'warning') {
        // Prevent spam
        const now = Date.now();
        if (now - lastWarningTime < WARNING_COOLDOWN) {
            return;
        }
        lastWarningTime = now;

        // Remove existing toast if any
        const existingToast = document.getElementById('rate-limit-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.id = 'rate-limit-toast';
        toast.className = `rate-limit-toast rate-limit-toast--${type}`;
        toast.innerHTML = `
            <div class="rate-limit-toast__icon">
                ${type === 'critical' ? '⚠️' : '⏱️'}
            </div>
            <div class="rate-limit-toast__content">
                <strong>${type === 'critical' ? 'Slow down!' : 'Notice'}</strong>
                <p>${message}</p>
            </div>
            <button class="rate-limit-toast__close" onclick="this.parentElement.remove()">×</button>
        `;

        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('rate-limit-toast--visible');
        });

        // Auto-remove after duration
        setTimeout(() => {
            toast.classList.remove('rate-limit-toast--visible');
            setTimeout(() => toast.remove(), 300);
        }, CONFIG.toastDuration);
    }

    /**
     * Check response headers for rate limit warnings
     */
    function checkRateLimitHeaders(response) {
        const warning = response.headers.get('X-RateLimit-Warning');
        const remaining = parseInt(response.headers.get('X-RateLimit-Remaining'), 10);
        const limit = parseInt(response.headers.get('X-RateLimit-Limit'), 10);
        const usage = parseInt(response.headers.get('X-RateLimit-Usage'), 10);

        if (warning === 'true' && !isNaN(remaining) && !isNaN(limit)) {
            if (usage >= CONFIG.criticalThreshold || remaining <= 5) {
                showToast(
                    `You're making too many requests. You have ${remaining} requests remaining. Please slow down to avoid being temporarily blocked.`,
                    'critical'
                );
            } else if (usage >= CONFIG.warningThreshold) {
                showToast(
                    `You're approaching the request limit. ${remaining} requests remaining this minute.`,
                    'warning'
                );
            }
        }

        // Handle 429 Too Many Requests
        if (response.status === 429) {
            const retryAfter = response.headers.get('Retry-After');
            showToast(
                `Too many requests! Please wait ${retryAfter || 60} seconds before trying again.`,
                'critical'
            );
        }
    }

    /**
     * Intercept fetch requests to monitor rate limit headers
     */
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        const response = await originalFetch.apply(this, args);
        checkRateLimitHeaders(response);
        return response;
    };

    /**
     * Intercept XMLHttpRequest to monitor rate limit headers
     */
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        this._url = url;
        return originalXHROpen.apply(this, [method, url, ...rest]);
    };

    XMLHttpRequest.prototype.send = function(...args) {
        this.addEventListener('load', function() {
            const mockResponse = {
                status: this.status,
                headers: {
                    get: (name) => this.getResponseHeader(name)
                }
            };
            checkRateLimitHeaders(mockResponse);
        });
        return originalXHRSend.apply(this, args);
    };

    /**
     * Also check on page load for rate limit headers in meta tags
     * (useful for full page loads)
     */
    document.addEventListener('DOMContentLoaded', function() {
        const meta = document.querySelector('meta[name="x-ratelimit-warning"]');
        if (meta && meta.content === 'true') {
            const remaining = document.querySelector('meta[name="x-ratelimit-remaining"]')?.content;
            if (remaining) {
                showToast(
                    `You're making requests quickly. ${remaining} requests remaining this minute.`,
                    'warning'
                );
            }
        }
    });

    console.log('[RateLimit] Rate limit warning system initialized');
})();
