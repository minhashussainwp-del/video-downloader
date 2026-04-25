/**
 * Frontend JavaScript for Advanced Shortcode & Snippet Engine
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        ASSEFrontend.init();
    });

    // Frontend object
    var ASSEFrontend = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.responsiveHelpers();
        },
        
        /**
         * Responsive helper functions
         */
        responsiveHelpers: function() {
            // Make device detection functions available globally
            window.asseIsMobile = function() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            };
            
            window.asseIsTablet = function() {
                var userAgent = navigator.userAgent;
                return /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(userAgent);
            };
            
            window.asseIsDesktop = function() {
                return !window.asseIsMobile() && !window.asseIsTablet();
            };
            
            // Handle window resize
            var resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    $(document).trigger('asse_resized');
                }, 250);
            });
        }
    };
    
    // Expose to global scope
    window.ASSEFrontend = ASSEFrontend;

})(jQuery);
