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
        lastAction: null,
        
        init: function() {
            this.bindEvents();
            this.loadInitialState();
            this.startTimeSync();
        },
        
        startTimeSync: function() {
            // Sync timers with server time every 30 seconds to ensure accuracy
            setInterval(function() {
                if (Timer.isWorking || Timer.isOnBreak) {
                    Timer.syncTimersWithServer();
                }
            }, 30000);
        },
        
        syncTimersWithServer: function() {
            // Get current timestamps from data attributes
            const clockInTime = $('#work-timer').data('clock-in-time');
            const breakStartTime = $('#break-timer').data('break-start-time');
            
            // Recalculate work timer if working
            if (Timer.isWorking && clockInTime) {
                const now = new Date();
                const clockIn = new Date(clockInTime);
                const elapsedSeconds = Math.floor((now - clockIn) / 1000);
                Timer.workSeconds = Math.max(0, elapsedSeconds);
                Timer.updateWorkDisplay();
            }
            
            // Recalculate break timer if on break
            if (Timer.isOnBreak && breakStartTime) {
                const now = new Date();
                const breakStart = new Date(breakStartTime);
                const elapsedSeconds = Math.floor((now - breakStart) / 1000);
                Timer.breakSeconds = Math.max(0, elapsedSeconds);
                Timer.updateBreakDisplay();
            }
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
            // Get the stored timestamps from the page
            const clockInTime = $('#work-timer').data('clock-in-time');
            const breakStartTime = $('#break-timer').data('break-start-time');
            
            // Calculate and start work timer if user is clocked in
            if (clockInTime) {
                Timer.isWorking = true;
                Timer.calculateAndStartWorkTimer(clockInTime);
            }
            
            // Calculate and start break timer if user is on break
            if (breakStartTime) {
                Timer.isOnBreak = true;
                Timer.calculateAndStartBreakTimer(breakStartTime);
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
            
            // Track the last action
            Timer.lastAction = action;
            
            switch (action) {
                case 'clock_in':
                    Timer.showWorkTimer();
                    Timer.showBreakButton();
                    Timer.updateClockButton('clock_out', 'Clock Out', 'red');
                    // Use the actual clock in time from the response
                    if (data.clock_in_time) {
                        Timer.calculateAndStartWorkTimer(data.clock_in_time);
                    } else {
                        Timer.workSeconds = 0;
                        Timer.startWorkTimer();
                    }
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
                    // Use the actual break start time from the response
                    if (data.break_start_time) {
                        Timer.calculateAndStartBreakTimer(data.break_start_time);
                    } else {
                        Timer.breakSeconds = 0;
                        Timer.startBreakTimer();
                    }
                    Timer.isOnBreak = true;
                    break;
                    
                case 'break_end':
                    Timer.hideBreakTimer();
                    Timer.updateBreakButton('break_start', 'Start Break');
                    Timer.stopBreakTimer();
                    // Use the actual clock in time from the response to restart work timer
                    if (data.clock_in_time) {
                        Timer.calculateAndStartWorkTimer(data.clock_in_time);
                    } else {
                        Timer.workSeconds = 0;
                        Timer.startWorkTimer();
                    }
                    Timer.isOnBreak = false;
                    break;
            }
        },
        
        calculateAndStartWorkTimer: function(clockInTime) {
            // Calculate elapsed time since clock in
            const now = new Date();
            const clockIn = new Date(clockInTime);
            const elapsedSeconds = Math.floor((now - clockIn) / 1000);
            
            // Set the current work seconds
            Timer.workSeconds = Math.max(0, elapsedSeconds);
            
            // Start the timer from the calculated elapsed time
            Timer.startWorkTimer();
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
        
        calculateAndStartBreakTimer: function(breakStartTime) {
            // Calculate elapsed time since break start
            const now = new Date();
            const breakStart = new Date(breakStartTime);
            const elapsedSeconds = Math.floor((now - breakStart) / 1000);
            
            // Set the current break seconds
            Timer.breakSeconds = Math.max(0, elapsedSeconds);
            
            // Start the timer from the calculated elapsed time
            Timer.startBreakTimer();
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
            
            // Update the data attribute with current time for page refresh persistence
            if (Timer.isWorking) {
                const now = new Date();
                const clockInTime = new Date(now.getTime() - (Timer.workSeconds * 1000));
                $('#work-timer').attr('data-clock-in-time', clockInTime.toISOString());
            }
        },
        
        updateBreakDisplay: function() {
            const formatted = Timer.formatTime(Timer.breakSeconds);
            $('#break-time').text(formatted);
            
            // Update the data attribute with current time for page refresh persistence
            if (Timer.isOnBreak) {
                const now = new Date();
                const breakStartTime = new Date(now.getTime() - (Timer.breakSeconds * 1000));
                $('#break-timer').attr('data-break-start-time', breakStartTime.toISOString());
            }
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
            // Only refresh if we're on the dashboard page AND it's a clock in/out action (not break actions)
            if ($('.employee-row').length > 0) {
                // Alternative: If there's a refresh function in dashboard.js, call it (without page reload)
                if (typeof dashboard !== 'undefined' && typeof dashboard.refreshEmployeeList === 'function') {
                    setTimeout(function() {
                        dashboard.refreshEmployeeList();
                    }, 500);
                } else {
                    // Only reload for clock in/out actions, not break actions
                    const currentAction = Timer.lastAction;
                    if (currentAction === 'clock_in' || currentAction === 'clock_out') {
                        setTimeout(function() {
                            location.reload();
                        }, 1000); // Longer delay for clock actions
                    }
                }
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
