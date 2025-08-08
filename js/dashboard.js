jQuery(document).ready(function($) {
    'use strict';
    
    // Dashboard functionality
    const dashboard = {
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initFilters();
            this.startAutoRefresh();
        },
        
        bindEvents: function() {
            // Status update buttons
            $(document).on('click', '.status-update-btn', function(e) {
                e.preventDefault();
                const userId = $(this).data('user-id');
                const status = $(this).data('status');
                const actionType = $(this).data('action-type');
                const notes = prompt('Add notes (optional):');
                
                dashboard.updateEmployeeStatus(userId, status, actionType, notes);
            });
            
            // Search functionality
            $('#employee-search').on('input', function() {
                dashboard.filterEmployees();
            });
            
            // Status filter
            $('#status-filter').on('change', function() {
                dashboard.filterEmployees();
            });
            
            // Role filter
            $('#role-filter').on('change', function() {
                dashboard.filterEmployees();
            });
        },
        
        initSearch: function() {
            // Initialize search with debouncing
            let searchTimeout;
            $('#employee-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    dashboard.filterEmployees();
                }, 300);
            });
        },
        
        initFilters: function() {
            // Initialize filters
            $('#status-filter, #role-filter').on('change', function() {
                dashboard.filterEmployees();
            });
        },
        
        filterEmployees: function() {
            const searchTerm = $('#employee-search').val().toLowerCase();
            const statusFilter = $('#status-filter').val();
            const roleFilter = $('#role-filter').val();
            
            $('.employee-row').each(function() {
                const $row = $(this);
                const name = $row.find('.employee-name').text().toLowerCase();
                const email = $row.find('.employee-email').text().toLowerCase();
                const status = $row.find('.employee-status').data('status');
                const role = $row.find('.employee-role').text().toLowerCase();
                
                let show = true;
                
                // Search filter
                if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                    show = false;
                }
                
                // Status filter
                if (statusFilter && statusFilter !== 'all' && status !== statusFilter) {
                    show = false;
                }
                
                // Role filter
                if (roleFilter && roleFilter !== 'all' && role !== roleFilter) {
                    show = false;
                }
                
                $row.toggle(show);
            });
            
            // Update counter
            dashboard.updateEmployeeCount();
        },
        
        updateEmployeeCount: function() {
            const visibleCount = $('.employee-row:visible').length;
            const totalCount = $('.employee-row').length;
            $('#employee-count').text(`${visibleCount} of ${totalCount} employees`);
        },
        
        updateEmployeeStatus: function(userId, status, actionType, notes) {
            $.ajax({
                url: linkage_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'linkage_update_employee_status',
                    user_id: userId,
                    status: status,
                    action_type: actionType,
                    notes: notes || '',
                    nonce: linkage_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        dashboard.showNotification('Status updated successfully', 'success');
                        dashboard.refreshEmployeeList();
                    } else {
                        dashboard.showNotification('Failed to update status', 'error');
                    }
                },
                error: function() {
                    dashboard.showNotification('Error updating status', 'error');
                }
            });
        },
        
        refreshEmployeeList: function() {
            // Reload the page to get updated data
            location.reload();
        },
        
        startAutoRefresh: function() {
            // Auto-refresh every 30 seconds
            setInterval(function() {
                dashboard.refreshEmployeeList();
            }, 30000);
        },
        
        showNotification: function(message, type) {
            const $notification = $(`
                <div class="notification notification-${type} fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg">
                    <div class="flex items-center">
                        <span class="mr-2">${type === 'success' ? '✓' : '✗'}</span>
                        <span>${message}</span>
                    </div>
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        formatTimeAgo: function(datetime) {
            if (!datetime || datetime === 'Never') {
                return 'Never';
            }
            
            const now = new Date();
            const time = new Date(datetime);
            const diff = now - time;
            
            if (diff < 60000) {
                return 'Just now';
            } else if (diff < 3600000) {
                const minutes = Math.floor(diff / 60000);
                return `${minutes} min${minutes > 1 ? 's' : ''} ago`;
            } else if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            } else {
                const days = Math.floor(diff / 86400000);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
        }
    };
    
    // Initialize dashboard
    dashboard.init();
    
    // Add CSS for notifications
    const notificationCSS = `
        <style>
            .notification {
                animation: slideIn 0.3s ease-out;
            }
            .notification-success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .notification-error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .employee-row {
                transition: all 0.3s ease;
            }
            .status-badge {
                transition: all 0.3s ease;
            }
            .status-badge.clocked-in {
                background-color: #d4edda;
                color: #155724;
            }
            .status-badge.clocked-out {
                background-color: #f8d7da;
                color: #721c24;
            }
        </style>
    `;
    $('head').append(notificationCSS);
});
