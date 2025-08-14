jQuery(document).ready(function($) {
    'use strict';
    

    
    // Check if jQuery is properly loaded
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        alert('jQuery is not loaded. Please check your theme setup.');
        return;
    }
    
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
        $('#clock-toggle-btn').on('click', function(e) {
            e.preventDefault();

                
                var action = $(this).data('action');

                
                if (!action) {
                    console.error('No action found in button data!');
                    alert('Button action not found. Please refresh the page.');
                    return;
                }
                
                if (!linkage_ajax || !linkage_ajax.ajax_url) {
                    console.error('Linkage AJAX not initialized!');

                    alert('AJAX not initialized. Please refresh the page.');
                return;
            }
            
                // Show confirmation popup for clock actions
                if (action === 'clock_out') {
                    self.showClockOutConfirmation(function() {
                        self.performClockAction(action);
                    });
                } else if (action === 'clock_in') {
                    self.showClockInConfirmation(function(notes) {
                        self.performClockAction(action, notes);
                    });
                } else {
                    self.performClockAction(action);
                }
            });
            
            // Break start/end button
            $('#break-toggle-btn').on('click', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                self.performBreakAction(action);
            });
        },
        
        performClockAction: function(action, notes) {
            var self = this;
            
            var postData = {
                action: 'linkage_clock_action',
                action_type: action,
                nonce: linkage_ajax.nonce
            };
            
            // Add notes if provided
            if (notes && notes.trim() !== '') {
                postData.notes = notes.trim();
            }
            
            $.post(linkage_ajax.ajax_url, postData, function(response) {
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
                    

                    } else {
                    console.error('Break action failed:', response.data);
                    alert('Action failed: ' + (response.data || 'Unknown error'));
                }
            }).fail(function() {
                console.error('Network error during break action');
                alert('Network error. Please try again.');
            });
        },
        
        showClockInConfirmation: function(callback) {
            var self = this;
            // Get current date and time
            var now = new Date();
            var currentDate = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            var currentTime = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Create a modern confirmation modal with time info and notes
            var modalHtml = `
                <div id="clock-in-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Confirm Time In</h3>
                        </div>
                        
                        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Start Time:</div>
                            <div class="text-lg font-semibold text-gray-900">${currentTime}</div>
                            <div class="text-sm text-gray-600 mt-1">${currentDate}</div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="clock-in-notes" class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea 
                                id="clock-in-notes" 
                                rows="3" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent resize-none" 
                                placeholder="Add any notes about your work session..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button id="cancel-clock-in" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button id="confirm-clock-in" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                Clock In
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            $('body').append(modalHtml);
            
            // Focus on notes textarea
            setTimeout(function() {
                $('#clock-in-notes').focus();
            }, 100);
            
            // Handle confirmation
            $('#confirm-clock-in').on('click', function() {
                var notes = $('#clock-in-notes').val().trim();
                $('#clock-in-modal').remove();
                callback(notes);
            });
            
            // Handle cancellation
            $('#cancel-clock-in').on('click', function() {
                $('#clock-in-modal').remove();
            });
            
            // Handle escape key and backdrop click
            $('#clock-in-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#clock-in-modal').remove();
                }
            });
            
            $(document).on('keydown.clockInModal', function(e) {
                if (e.keyCode === 27) { // Escape key
                    $('#clock-in-modal').remove();
                    $(document).off('keydown.clockInModal');
                } else if (e.keyCode === 13 && e.ctrlKey) { // Ctrl+Enter to confirm
                    $('#confirm-clock-in').click();
                }
            });
        },
        
        showClockOutConfirmation: function(callback) {
            // Create a modern confirmation modal
            var modalHtml = `
                <div id="clock-out-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Confirm Time Out</h3>
                        </div>
                        <p class="text-gray-600 mb-6">Are you sure you want to clock out? This will end your current work session.</p>
                        <div class="flex justify-end space-x-3">
                            <button id="cancel-clock-out" class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button id="confirm-clock-out" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                Clock Out
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            $('body').append(modalHtml);
            
            // Handle confirmation
            $('#confirm-clock-out').on('click', function() {
                $('#clock-out-modal').remove();
                callback();
            });
            
            // Handle cancellation
            $('#cancel-clock-out').on('click', function() {
                $('#clock-out-modal').remove();
            });
            
            // Handle escape key and backdrop click
            $('#clock-out-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#clock-out-modal').remove();
                }
            });
            
            $(document).on('keydown.clockOutModal', function(e) {
                if (e.keyCode === 27) { // Escape key
                    $('#clock-out-modal').remove();
                    $(document).off('keydown.clockOutModal');
                }
            });
        },
        
        updateButtonStates: function(status) {
            var clockButton = $('#clock-toggle-btn');
            var breakButton = $('#break-toggle-btn');
            
            switch (status) {
                case 'clocked_in':
                    clockButton.show();
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
                    // Hide the Time Out button when user is on break
                    clockButton.hide();
                    breakButton.show();
                    breakButton.data('action', 'break_end');
                    $('#break-toggle-text').text('Lunch End');
                    $('.break-start-icon').hide();
                    $('.break-end-icon').show();
                    break;
                    
                case 'clocked_out':
                    clockButton.show();
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

    
    try {
        Timer.init();

    } catch (error) {
        console.error('Timer initialization failed:', error);
        alert('Timer initialization failed: ' + error.message);
    }

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
        
        // Update every 1 second for real-time accuracy
        serverTimeUpdateInterval = setInterval(function() {
            updateTimeFromServer();
            }, 1000);
        
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

        });
    }

    /**
     * Update status display - now only updates current user's status
     */
    function updateStatusDisplay(status) {
        // Get current user ID from the page
        var currentUserId = linkage_ajax.current_user_id;
        
        if (currentUserId) {
            // Only update the current user's status in the live list
            var statusElement = $(`.employee-row[data-user-id="${currentUserId}"] .employee-status`);
            
            if (statusElement.length) {
                // Update the text
                var statusText = status.replace('_', ' ').replace(/\b\w/g, function(l) {
                    return l.toUpperCase();
                });
                statusElement.text(statusText);
                
                // Update the CSS classes
                statusElement.removeClass('clocked-in clocked-out break-status');
                
                switch (status) {
                    case 'clocked_in':
                        statusElement.addClass('clocked-in');
                        break;
                    case 'clocked_out':
                        statusElement.addClass('clocked-out');
                        break;
                    case 'on_break':
                        statusElement.addClass('break-status');
                        break;
                    default:
                        statusElement.addClass('clocked-out');
                        break;
                }
                
                // Update the data-status attribute
                statusElement.attr('data-status', status);
                
                // Update last action time using the actual database value
                // Don't override with current time - let the AJAX refresh handle this
            }
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
    
    // Add CSS for animations and modal
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
            
            /* Clock Modal Styles */
            #clock-out-modal, #clock-in-modal {
                animation: modalFadeIn 0.2s ease-out;
            }
            
            #clock-out-modal > div, #clock-in-modal > div {
                animation: modalSlideIn 0.2s ease-out;
            }
            
            @keyframes modalFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes modalSlideIn {
                from { 
                    transform: scale(0.95) translateY(-10px);
                    opacity: 0;
                }
                to { 
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
            }
            
            /* Clock-in modal specific styles */
            #clock-in-notes:focus {
                box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
            }
        </style>
    `;
    
    $('head').append(timerCSS);
});
