jQuery(document).ready(function($) {
    'use strict';
    
    /**
     * Timer functionality for LinkageClock
     */
    var Timer = {
        workSeconds: 0,
        breakSeconds: 0,
        isRunning: false,
        workInterval: null,
        breakInterval: null,
        
        init: function() {
            this.loadInitialState();
            this.bindEvents();
        },
        
        loadInitialState: function() {
            // Get initial state from server instead of client-side calculation
            this.getTimeFromServer();
        },
        
        getTimeFromServer: function() {
            if (!linkage_ajax || !linkage_ajax.ajax_url) {
                console.error('Linkage AJAX not initialized or missing URL.');
                return;
            }

            $.post(linkage_ajax.ajax_url, {
                action: 'linkage_get_time_updates',
                nonce: linkage_ajax.nonce
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Update work time display
                    if (data.work_time_display) {
                        $('#work-time').text(data.work_time_display);
                    }
                    
                    // Update break time display
                    if (data.break_time_display) {
                        $('#break-time').text(data.break_time_display);
                    }
                    
                    // Update status if changed
                    if (data.status) {
                        updateStatusDisplay(data.status);
                    }
                    
                    // Update workSeconds from server data
                    if (data.work_seconds) {
                        Timer.workSeconds = Math.max(0, parseInt(data.work_seconds));
                Timer.updateWorkDisplay();
            }
            
                    // Update breakSeconds from server data
                    if (data.break_seconds) {
                        Timer.breakSeconds = Math.max(0, parseInt(data.break_seconds));
                        Timer.updateBreakDisplay();
                    }
                } else {
                    console.error('Failed to get time updates from server:', response.data || 'Unknown error');
                }
            }).fail(function() {
                console.error('Network error getting time updates from server');
            });
        },
        
        updateWorkDisplay: function() {
            var display = this.formatTime(this.workSeconds);
            $('#work-time').text(display);
        },
        
        updateBreakDisplay: function() {
            var display = this.formatTime(this.breakSeconds);
            $('#break-time').text(display);
        },
        
        formatTime: function(seconds) {
            if (seconds < 0) seconds = 0;
            
            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = seconds % 60;
            
            if (hours > 0) {
                return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            } else {
                return String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }
        },
        
        bindEvents: function() {
            var self = this;
            
            // Clock in/out button
            $('#clock-toggle-btn').on('click', function() {
                var action = $(this).data('action');
                self.performClockAction(action);
            });
            
            // Break start/end button
            $('#break-toggle-btn').on('click', function() {
                var action = $(this).data('action');
                self.performBreakAction(action);
        });
        },
        
        performClockAction: function(action) {
            var self = this;
            
            $.post(linkage_ajax.ajax_url, {
                action: 'linkage_clock_action',
                action_type: action,
                nonce: linkage_ajax.nonce
            }, function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Update button states
                    self.updateButtonStates(data.status);
                    
                    // Update time displays with server-calculated times
                    if (data.work_seconds !== undefined) {
                        self.workSeconds = data.work_seconds;
                        self.updateWorkDisplay();
                    }
                    
                    if (data.break_seconds !== undefined) {
                        self.breakSeconds = data.break_seconds;
                        self.updateBreakDisplay();
                    }
                    
                    // Update status display
                    updateStatusDisplay(data.status);
                    
                    // Trigger custom event for other components
                    $(document).trigger('clockActionCompleted', data);
                    
                    console.log('Clock action completed:', data);
                    } else {
                    console.error('Clock action failed:', response.data);
                    alert('Action failed: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                console.error('Network error during clock action');
                alert('Network error. Please try again.');
            });
        },
        
        performBreakAction: function(action) {
            var self = this;
            
            $.post(linkage_ajax.ajax_url, {
                    action: 'linkage_clock_action',
                    action_type: action,
                    nonce: linkage_ajax.nonce
            }, function(response) {
                    if (response.success) {
                    var data = response.data;
                    
                    // Update button states
                    self.updateButtonStates(data.status);
                    
                    // Update time displays with server-calculated times
                    if (data.work_seconds !== undefined) {
                        self.workSeconds = data.work_seconds;
                        self.updateWorkDisplay();
                    }
                    
                    if (data.break_seconds !== undefined) {
                        self.breakSeconds = data.break_seconds;
                        self.updateBreakDisplay();
                    }
                    
                    // Update status display
                    updateStatusDisplay(data.status);
                    
                    // Trigger custom event for other components
                    $(document).trigger('clockActionCompleted', data);
                    
                    console.log('Break action completed:', data);
                    } else {
                    console.error('Break action failed:', response.data);
                    alert('Action failed: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                console.error('Network error during break action');
                alert('Network error. Please try again.');
            });
        },
        
        updateButtonStates: function(status) {
            var clockButton = $('#clock-toggle-btn');
            var breakButton = $('#break-toggle-btn');
            
            switch (status) {
                case 'clocked_in':
                    clockButton.data('action', 'clock_out');
                    $('#clock-toggle-text').text('Time Out');
                    clockButton.removeClass('bg-green-500 hover:bg-green-600').addClass('bg-red-500 hover:bg-red-600');
                    $('.clock-in-icon').hide();
                    $('.clock-out-icon').show();
                    breakButton.show();
                    breakButton.data('action', 'break_start');
                    $('#break-toggle-text').text('Lunch Start');
                    $('.break-start-icon').show();
                    $('.break-end-icon').hide();
                    break;
                    
                case 'on_break':
                    clockButton.data('action', 'clock_out');
                    $('#clock-toggle-text').text('Time Out');
                    clockButton.removeClass('bg-green-500 hover:bg-green-600').addClass('bg-red-500 hover:bg-red-600');
                    $('.clock-in-icon').hide();
                    $('.clock-out-icon').show();
                    breakButton.show();
                    breakButton.data('action', 'break_end');
                    $('#break-toggle-text').text('Lunch End');
                    $('.break-start-icon').hide();
                    $('.break-end-icon').show();
                    break;
                    
                case 'clocked_out':
                    clockButton.data('action', 'clock_in');
                    $('#clock-toggle-text').text('Time In');
                    clockButton.removeClass('bg-red-500 hover:bg-red-600').addClass('bg-green-500 hover:bg-green-600');
                    $('.clock-in-icon').show();
                    $('.clock-out-icon').hide();
                    breakButton.hide();
                    break;
            }
        }
    };

    // Initialize timer
    Timer.init();

    // Server-side time tracking (more accurate than local timers)
    var serverTimeUpdateInterval;
    var lastServerUpdate = 0;

    /**
     * Start server-side time updates
     */
    function startServerTimeUpdates() {
        if (serverTimeUpdateInterval) {
            clearInterval(serverTimeUpdateInterval);
        }
        
        // Update every 5 seconds for real-time accuracy
        serverTimeUpdateInterval = setInterval(function() {
            updateTimeFromServer();
        }, 5000);
        
        // Initial update
        updateTimeFromServer();
    }

    /**
     * Stop server-side time updates
     */
    function stopServerTimeUpdates() {
        if (serverTimeUpdateInterval) {
            clearInterval(serverTimeUpdateInterval);
            serverTimeUpdateInterval = null;
        }
    }

    /**
     * Update time display from server
     */
    function updateTimeFromServer() {
        if (!linkage_ajax || !linkage_ajax.ajax_url) {
            return;
        }
        
        $.post(linkage_ajax.ajax_url, {
            action: 'linkage_get_time_updates',
            nonce: linkage_ajax.nonce
        }, function(response) {
            if (response.success) {
                var data = response.data;
                
                // Update work time display
                if (data.work_time_display) {
                    $('#work-time').text(data.work_time_display);
                }
                
                // Update break time display
                if (data.break_time_display) {
                    $('#break-time').text(data.break_time_display);
                }
                
                // Update status if changed
                if (data.status) {
                    updateStatusDisplay(data.status);
                }
                
                // Update Timer object with server data
                if (data.work_seconds !== undefined) {
                    Timer.workSeconds = Math.max(0, parseInt(data.work_seconds));
                }
                
                if (data.break_seconds !== undefined) {
                    Timer.breakSeconds = Math.max(0, parseInt(data.break_seconds));
                }
                
                lastServerUpdate = Date.now();
            }
        }).fail(function() {
            console.log('Failed to get time update from server');
        });
    }

    /**
     * Update status display
     */
    function updateStatusDisplay(status) {
        var statusElement = $('.employee-status');
        if (statusElement.length) {
            statusElement.text(status.replace('_', ' ').replace(/\b\w/g, function(l) {
                return l.toUpperCase();
            }));
        }
    }

    /**
     * Check if user is currently working
     */
    function isUserWorking() {
        var status = $('.employee-status').text().toLowerCase();
        return status.includes('clocked in') || status.includes('on break');
    }
    
    // Initialize server-side time tracking when page loads
    if (isUserWorking()) {
        startServerTimeUpdates();
    }

    // Start/stop server updates based on clock actions
    $(document).on('clockActionCompleted', function(e, data) {
        if (data.status === 'clocked_in' || data.status === 'on_break') {
            startServerTimeUpdates();
        } else if (data.status === 'clocked_out') {
            stopServerTimeUpdates();
        }
    });
    
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
