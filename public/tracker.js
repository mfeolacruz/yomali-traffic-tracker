/**
 * Yomali Traffic Tracker - JavaScript SDK
 * Simple tracking script that captures page URL and visitor IP
 * 
 * Usage:
 * <script src="http://localhost:8888/tracker.js"></script>
 */

(function() {
    'use strict';
    
    /**
     * Track current page view
     */
    function trackPageView() {
        const data = {
            url: window.location.href
        };
        
        fetch('http://localhost:8888/api/v1/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).catch(function(error) {
            // Silently fail - don't break the website if tracking fails
            console.warn('Yomali Tracker: Failed to send tracking data');
        });
    }
    
    // Auto-track page view when script loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackPageView);
    } else {
        trackPageView();
    }
    
})();