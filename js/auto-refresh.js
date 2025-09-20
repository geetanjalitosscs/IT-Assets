// Auto-refresh functionality to ensure changes are reflected across pages
(function() {
    'use strict';
    
    // Function to refresh data without full page reload
    function refreshData() {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        // Update any data tables
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.data-table').DataTable().ajax.reload(null, false);
        }
        
        // Force refresh of any cached content
        const refreshElements = document.querySelectorAll('[data-refresh]');
        refreshElements.forEach(element => {
            const url = element.getAttribute('data-refresh');
            if (url) {
                fetch(url + '?t=' + timestamp)
                    .then(response => response.text())
                    .then(data => {
                        element.innerHTML = data;
                    })
                    .catch(error => console.log('Refresh error:', error));
            }
        });
    }
    
    // Listen for form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.tagName === 'FORM') {
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'timestamp';
            input.value = timestamp;
            form.appendChild(input);
            
            // Show loading indicator
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                submitBtn.disabled = true;
                
                // Re-enable after form submission
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 2000);
            }
        }
    });
    
    // Auto-refresh every 30 seconds for live data
    setInterval(function() {
        // Only refresh if user is active
        if (!document.hidden) {
            refreshData();
        }
    }, 30000);
    
    // Refresh data when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshData();
        }
    });
    
    // Refresh button functionality removed per user request
    
    // Force refresh after successful operations
    window.refreshAfterEdit = function() {
        setTimeout(function() {
            location.reload();
        }, 1000);
    };
    
})();
