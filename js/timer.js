jQuery(document).ready(function($) {
    'use strict';
    
    // Timer functionality
    const Timer = {
        workTimer: null,
        breakTimer: null,
        workSeconds: 0,
        breakSeconds: 0,
        isWorking: false,
        isOnBreak: false,
        
        init: function() {
            this.bindEvents();
            this.loadInitialState();
        },
        
        bindEvents: function() {
            // Clock In/Out button
            $('#clock-toggle-btn').on('click', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                Timer.handleClockAction(action);
            });
            
            // Break button
            $('#break-toggle-btn').on('click', function(e) {
                e.preventDefault();
                const action = $(this).data('action');
                Timer.handleBreakAction(action);
            });
        },
        
        loadInitialState: function() {
            // Check if user is already clocked in and load timers
            const workTimer = $('#work-timer');
            const breakTimer = $('#break-timer');
            
            if (workTimer.is(':visible')) {
                Timer.isWorking = true;
                Timer.startWorkTimer();
            }
            
            if (breakTimer.is(':visible')) {
                Timer.isOnBreak = true;
                Timer.startBreakTimer();
            }
        },
        
        handleClockAction: function(action) {
            Timer.showLoading('clock-toggle-btn');
            
            $.ajax({
                url: linkage_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'linkage_clock_action',
                    action_type: action,
                    nonce: linkage_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        Timer.updateUI(response.data);
                        Timer.showNotification(response.data.message, 'success');
                        Timer.refreshDashboardList();
                    } else {
                        Timer.showNotification(response.data || 'Error occurred', 'error');
                    }
                },
                error: function() {
                    Timer.showNotification('Network error', 'error');
                },
                complete: function() {
                    Timer.hideLoading('clock-toggle-btn');
                }
            });
        },
        
        handleBreakAction: function(action) {
            Timer.showLoading('break-toggle-btn');
            
            $.ajax({
                url: linkage_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'linkage_clock_action',
                    action_type: action,
                    nonce: linkage_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        Timer.updateUI(response.data);
                        Timer.showNotification(response.data.message, 'success');
                        Timer.refreshDashboardList();
                    } else {
                        Timer.showNotification(response.data || 'Error occurred', 'error');
                    }
                },
                error: function() {
                    Timer.showNotification('Network error', 'error');
                },
                complete: function() {
                    Timer.hideLoading('break-toggle-btn');
                }
            });
        },
        
        updateUI: function(data) {
            const action = data.action;
            const status = data.status;
            
            switch (action) {
                case 'clock_in':
                    Timer.showWorkTimer();
                    Timer.showBreakButton();
                    Timer.updateClockButton('clock_out', 'Clock Out', 'red');
                    Timer.workSeconds = data.work_seconds || 0;
                    Timer.startWorkTimer();
                    Timer.isWorking = true;
                    break;
                    
                case 'clock_out':
                    Timer.hideWorkTimer();
                    Timer.hideBreakTimer();
                    Timer.hideBreakButton();
                    Timer.updateClockButton('clock_in', 'Clock In', 'green');
                    Timer.stopWorkTimer();
                    Timer.stopBreakTimer();
                    Timer.isWorking = false;
                    Timer.isOnBreak = false;
                    break;
                    
                case 'break_start':
                    Timer.showBreakTimer();
                    Timer.updateBreakButton('break_end', 'End Break');
                    Timer.stopWorkTimer();
                    Timer.breakSeconds = data.break_seconds || 0;
                    Timer.startBreakTimer();
                    Timer.isOnBreak = true;
                    break;
                    
                case 'break_end':
                    Timer.hideBreakTimer();
                    Timer.updateBreakButton('break_start', 'Start Break');
                    Timer.stopBreakTimer();
                    Timer.workSeconds = data.work_seconds || 0;
                    Timer.startWorkTimer();
                    Timer.isOnBreak = false;
                    break;
            }
        },
        
        startWorkTimer: function() {
            Timer.stopWorkTimer(); // Clear any existing timer
            Timer.workTimer = setInterval(function() {
                Timer.workSeconds++;
                Timer.updateWorkDisplay();
            }, 1000);
        },
        
        stopWorkTimer: function() {
            if (Timer.workTimer) {
                clearInterval(Timer.workTimer);
                Timer.workTimer = null;
            }
        },
        
        startBreakTimer: function() {
            Timer.stopBreakTimer(); // Clear any existing timer
            Timer.breakTimer = setInterval(function() {
                Timer.breakSeconds++;
                Timer.updateBreakDisplay();
            }, 1000);
        },
        
        stopBreakTimer: function() {
            if (Timer.breakTimer) {
                clearInterval(Timer.breakTimer);
                Timer.breakTimer = null;
            }
        },
        
        updateWorkDisplay: function() {
            const formatted = Timer.formatTime(Timer.workSeconds);
            $('#work-time').text(formatted);
        },
        
        updateBreakDisplay: function() {
            const formatted = Timer.formatTime(Timer.breakSeconds);
            $('#break-time').text(formatted);
        },
        
        formatTime: function(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            return String(hours).padStart(2, '0') + ':' + 
                   String(minutes).padStart(2, '0') + ':' + 
                   String(secs).padStart(2, '0');
        },
        
        showWorkTimer: function() {
            $('#work-timer').fadeIn(300);
        },
        
        hideWorkTimer: function() {
            $('#work-timer').fadeOut(300);
            $('#work-time').text('00:00:00');
            Timer.workSeconds = 0;
        },
        
        showBreakTimer: function() {
            $('#break-timer').fadeIn(300);
        },
        
        hideBreakTimer: function() {
            $('#break-timer').fadeOut(300);
            $('#break-time').text('00:00:00');
            Timer.breakSeconds = 0;
        },
        
        showBreakButton: function() {
            $('#break-toggle-btn').fadeIn(300);
        },
        
        hideBreakButton: function() {
            $('#break-toggle-btn').fadeOut(300);
        },
        
        updateClockButton: function(action, text, color) {
            const btn = $('#clock-toggle-btn');
            const colorClasses = {
                'green': 'bg-green-500 hover:bg-green-600',
                'red': 'bg-red-500 hover:bg-red-600'
            };
            
            // Update button data and text
            btn.data('action', action);
            $('#clock-toggle-text').text(text);
            
            // Update button colors
            btn.removeClass('bg-green-500 hover:bg-green-600 bg-red-500 hover:bg-red-600')
               .addClass(colorClasses[color]);
            
            // Toggle icons
            if (action === 'clock_in') {
                $('.clock-in-icon').show();
                $('.clock-out-icon').hide();
            } else {
                $('.clock-in-icon').hide();
                $('.clock-out-icon').show();
            }
        },
        
        updateBreakButton: function(action, text) {
            const btn = $('#break-toggle-btn');
            
            // Update button data and text
            btn.data('action', action);
            $('#break-toggle-text').text(text);
            
            // Toggle icons
            if (action === 'break_start') {
                $('.break-start-icon').show();
                $('.break-end-icon').hide();
            } else {
                $('.break-start-icon').hide();
                $('.break-end-icon').show();
            }
        },
        
        showLoading: function(buttonId) {
            const btn = $('#' + buttonId);
            btn.prop('disabled', true);
            btn.find('span').append(' <span class="loading-spinner">⏳</span>');
        },
        
        hideLoading: function(buttonId) {
            const btn = $('#' + buttonId);
            btn.prop('disabled', false);
            btn.find('.loading-spinner').remove();
        },
        
        refreshDashboardList: function() {
            // Check if we're on the dashboard page (index.php)
            if ($('.employee-row').length > 0) {
                // Reload the page to refresh the employee list
                setTimeout(function() {
                    location.reload();
                }, 500); // Small delay to let the server update
            }
            
            // Alternative: If there's a refresh function in dashboard.js, call it
            if (typeof dashboard !== 'undefined' && typeof dashboard.refreshEmployeeList === 'function') {
                setTimeout(function() {
                    dashboard.refreshEmployeeList();
                }, 500);
            }
        },
        
        showNotification: function(message, type) {
            const className = type === 'success' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200';
            const icon = type === 'success' ? '✓' : '✗';
            
            const notification = $(`
                <div class="timer-notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg border ${className}">
                    <div class="flex items-center">
                        <span class="mr-2">${icon}</span>
                        <span>${message}</span>
                    </div>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize timer
    Timer.init();
    
    // Add CSS for animations
    const timerCSS = `
        <style>
            .timer-notification {
                animation: slideInRight 0.3s ease-out;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .loading-spinner {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .animate-pulse {
                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            
            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.5;
                }
            }
        </style>
    `;
    
    $('head').append(timerCSS);
});
