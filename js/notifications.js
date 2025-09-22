// Professional Notification System
(function() {
    'use strict';
    
    // Create notification container if it doesn't exist
    function createNotificationContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                width: 100%;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
    }
    
    // Show notification
    function showNotification(message, type = 'success', duration = 5000) {
        createNotificationContainer();
        
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        
        // Generate unique ID
        const id = 'notification-' + Date.now();
        notification.id = id;
        
        // Set notification styles based on type
        let bgColor, iconColor, icon, borderColor;
        switch(type) {
            case 'success':
                bgColor = '#f0f9ff';
                iconColor = '#10b981';
                icon = '✓';
                borderColor = '#10b981';
                break;
            case 'error':
                bgColor = '#fef2f2';
                iconColor = '#ef4444';
                icon = '✕';
                borderColor = '#ef4444';
                break;
            case 'warning':
                bgColor = '#fffbeb';
                iconColor = '#f59e0b';
                icon = '⚠';
                borderColor = '#f59e0b';
                break;
            case 'info':
                bgColor = '#eff6ff';
                iconColor = '#3b82f6';
                icon = 'ℹ';
                borderColor = '#3b82f6';
                break;
            default:
                bgColor = '#f0f9ff';
                iconColor = '#10b981';
                icon = '✓';
                borderColor = '#10b981';
        }
        
        notification.style.cssText = `
            background: ${bgColor};
            border: 2px solid ${borderColor};
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transform: translateX(100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: auto;
            position: relative;
            overflow: hidden;
        `;
        
        // Add subtle animation background
        notification.innerHTML = `
            <div style="
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
                pointer-events: none;
            "></div>
            <div style="
                display: flex;
                align-items: center;
                position: relative;
                z-index: 2;
            ">
                <div style="
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    background: ${iconColor};
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 14px;
                    font-weight: bold;
                    margin-right: 12px;
                    flex-shrink: 0;
                ">${icon}</div>
                <div style="
                    flex: 1;
                    color: #374151;
                    font-size: 14px;
                    font-weight: 500;
                    line-height: 1.4;
                ">${message}</div>
                <button onclick="closeNotification('${id}')" style="
                    background: none;
                    border: none;
                    color: #9ca3af;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    margin-left: 12px;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                    transition: all 0.2s ease;
                " onmouseover="this.style.color='#6b7280'; this.style.backgroundColor='rgba(0,0,0,0.1)'" onmouseout="this.style.color='#9ca3af'; this.style.backgroundColor='transparent'">×</button>
            </div>
        `;
        
        container.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                closeNotification(id);
            }, duration);
        }
        
        return id;
    }
    
    // Close notification
    window.closeNotification = function(id) {
        const notification = document.getElementById(id);
        if (notification) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    };
    
    // Close all notifications
    window.closeAllNotifications = function() {
        const container = document.getElementById('notification-container');
        if (container) {
            const notifications = container.querySelectorAll('[id^="notification-"]');
            notifications.forEach(notification => {
                closeNotification(notification.id);
            });
        }
    };
    
    // Global notification functions
    window.showSuccess = function(message, duration) {
        return showNotification(message, 'success', duration);
    };
    
    window.showError = function(message, duration) {
        return showNotification(message, 'error', duration);
    };
    
    window.showWarning = function(message, duration) {
        return showNotification(message, 'warning', duration);
    };
    
    window.showInfo = function(message, duration) {
        return showNotification(message, 'info', duration);
    };
    
    // Replace Bootstrap alerts with notifications
    function replaceBootstrapAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const message = alert.textContent.trim();
            const alertClass = alert.className;
            
            let type = 'info';
            if (alertClass.includes('alert-success')) type = 'success';
            else if (alertClass.includes('alert-danger')) type = 'error';
            else if (alertClass.includes('alert-warning')) type = 'warning';
            else if (alertClass.includes('alert-info')) type = 'info';
            
            // Hide the original alert
            alert.style.display = 'none';
            
            // Show notification
            showNotification(message, type);
        });
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        replaceBootstrapAlerts();
    });
    
    // Also replace alerts that are added dynamically
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.classList && node.classList.contains('alert')) {
                    const message = node.textContent.trim();
                    const alertClass = node.className;
                    
                    let type = 'info';
                    if (alertClass.includes('alert-success')) type = 'success';
                    else if (alertClass.includes('alert-danger')) type = 'error';
                    else if (alertClass.includes('alert-warning')) type = 'warning';
                    else if (alertClass.includes('alert-info')) type = 'info';
                    
                    node.style.display = 'none';
                    showNotification(message, type);
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
})();
