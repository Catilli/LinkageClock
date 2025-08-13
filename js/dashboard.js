jQuery(document).ready(function($) {
    'use strict';
    
    // Dashboard functionality
    const dashboard = {
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initFilters();
            this.startAutoRefresh();
            this.initDrawerToggle();
            this.initUserDropdown();
            this.setActiveMenuItem();
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
                const position = $row.find('.employee-position').text().toLowerCase();
                const status = $row.find('.employee-status').data('status');
                const role = $row.find('.employee-role').text().trim();
                
                let show = true;
                
                // Search filter
                if (searchTerm && !name.includes(searchTerm) && !position.includes(searchTerm)) {
                    show = false;
                }
                
                // Status filter - handle clocked_in and clocked_out
                if (statusFilter && statusFilter !== 'all' && status !== statusFilter) {
                    show = false;
                }
                
                // Role filter - exact case-insensitive comparison
                if (roleFilter && roleFilter !== 'all') {
                    const roleMatch = role.toLowerCase() === roleFilter.toLowerCase();
                    if (!roleMatch) {
                        show = false;
                    }
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
        
        refreshEmployeeList: function() {
            // Use AJAX to refresh employee data instead of page reload
            this.refreshEmployeeData();
        },
        
        updateTimeDisplays: function() {
            // Update time displays without page reload
            $('.time-ago').each(function() {
                const datetime = $(this).data('datetime');
                if (datetime) {
                    $(this).text(dashboard.formatExactTime(datetime));
                }
            });
        },
        
        startAutoRefresh: function() {
            // Auto-refresh with AJAX every 30 seconds
            setInterval(function() {
                dashboard.refreshEmployeeData();
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
        
        formatExactTime: function(datetime) {
            if (!datetime || datetime === 'Never') {
                return 'Never';
            }
            
            const time = new Date(datetime);
            const now = new Date();
            
            // Format time as H:MM AM/PM
            const timeString = time.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Check if it's today, yesterday, or another date
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const timeDate = new Date(time.getFullYear(), time.getMonth(), time.getDate());
            const timeDiff = today.getTime() - timeDate.getTime();
            
            if (timeDiff === 0) {
                return `${timeString}, Today`;
            } else if (timeDiff === 86400000) { // 1 day in milliseconds
                return `${timeString}, Yesterday`;
            } else {
                // Format as MM/DD/YYYY
                const dateString = time.toLocaleDateString('en-US', {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                });
                return `${timeString}, ${dateString}`;
            }
        },
        
        initDrawerToggle: function() {
            const $drawer = $('#masthead');
            const $mainContent = $('#main-content');
            const $toggleButton = $('#drawer-toggle');
            const $toggleText = $('.drawer-toggle-text');
            const $arrowIcon = $('.arrow-icon');
            let isCollapsed = false;
            
            // Check for saved state
            if (localStorage.getItem('drawerCollapsed') === 'true') {
                this.collapseDrawer();
                isCollapsed = true;
            }
            
            $toggleButton.on('click', function() {
                if (isCollapsed) {
                    dashboard.expandDrawer();
                    isCollapsed = false;
                } else {
                    dashboard.collapseDrawer();
                    isCollapsed = true;
                }
            });
        },
        
        collapseDrawer: function() {
            const $drawer = $('#masthead');
            const $mainContent = $('#main-content');
            const $toggleText = $('.drawer-toggle-text');
            const $arrowIcon = $('.arrow-icon');
            
            $drawer.addClass('drawer-collapsed');
            $mainContent.removeClass('ml-64').addClass('ml-16');
            $toggleText.text('EXPAND');
            $arrowIcon.addClass('arrow-rotated');
            
            // Hide text elements in navigation
            $drawer.find('h3, .site-title, .employee-name, .employee-email, .employee-role').addClass('drawer-text-hidden');
            
            localStorage.setItem('drawerCollapsed', 'true');
        },
        
        expandDrawer: function() {
            const $drawer = $('#masthead');
            const $mainContent = $('#main-content');
            const $toggleText = $('.drawer-toggle-text');
            const $arrowIcon = $('.arrow-icon');
            
            $drawer.removeClass('drawer-collapsed');
            $mainContent.removeClass('ml-16').addClass('ml-64');
            $toggleText.text('COLLAPSE');
            $arrowIcon.removeClass('arrow-rotated');
            
            // Show text elements in navigation
            $drawer.find('h3, .site-title, .employee-name, .employee-email, .employee-role').removeClass('drawer-text-hidden');
            
            localStorage.setItem('drawerCollapsed', 'false');
        },
        
        initUserDropdown: function() {
            const $userToggle = $('#user-menu-toggle');
            const $userDropdown = $('#user-dropdown-menu');
            const $userArrow = $('.user-menu-arrow');
            
            // Toggle dropdown on user button click
            $userToggle.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isOpen = $userDropdown.hasClass('opacity-100');
                
                if (isOpen) {
                    dashboard.closeUserDropdown();
                } else {
                    dashboard.openUserDropdown();
                }
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                const isOpen = $userDropdown.hasClass('opacity-100');
                if (isOpen && !$(e.target).closest('#user-menu-toggle, #user-dropdown-menu').length) {
                    dashboard.closeUserDropdown();
                }
            });
            
            // Close dropdown on escape key
            $(document).on('keydown', function(e) {
                const isOpen = $userDropdown.hasClass('opacity-100');
                if (e.key === 'Escape' && isOpen) {
                    dashboard.closeUserDropdown();
                }
            });
        },
        
        openUserDropdown: function() {
            const $userDropdown = $('#user-dropdown-menu');
            const $userArrow = $('.user-menu-arrow');
            
            $userDropdown.removeClass('opacity-0 invisible translate-x-4').addClass('opacity-100 visible translate-x-0');
            $userArrow.addClass('rotate-180');
        },
        
        closeUserDropdown: function() {
            const $userDropdown = $('#user-dropdown-menu');
            const $userArrow = $('.user-menu-arrow');
            
            $userDropdown.removeClass('opacity-100 visible translate-x-0').addClass('opacity-0 invisible translate-x-4');
            $userArrow.removeClass('rotate-180');
        },
        
        setActiveMenuItem: function() {
            const currentPath = window.location.pathname;
            const currentUrl = window.location.href;
            
            // Remove any existing active classes
            $('.menu-item a').removeClass('active');
            
            // Set active class based on current page
            $('.menu-item a').each(function() {
                const href = $(this).attr('href');
                
                if (href && (currentUrl === href || currentPath === new URL(href).pathname)) {
                    $(this).addClass('active');
                    return false; // Break the loop
                }
            });
            
            // Fallback: if no exact match and we're on home page, highlight dashboard
            if (!$('.menu-item a.active').length && (currentPath === '/' || currentPath === '')) {
                $('.menu-item-dashboard a').addClass('active');
            }
        },

        // AJAX-based data refresh functions
        refreshEmployeeData: function() {
            $.ajax({
                url: linkage_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'linkage_get_employee_updates',
                    nonce: linkage_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        dashboard.updateEmployeeStatuses(response.data.statuses);
                        dashboard.updateTimeDisplays();
                        dashboard.updateEmployeeCount();
                        dashboard.updateEmployeePositions(response.data.positions);
                        dashboard.updateEmployeeHireDates(response.data.hire_dates);
                    }
                },
                error: function() {

                }
            });
        },

        updateEmployeeStatuses: function(statuses) {
            if (!statuses) return;
            
            Object.keys(statuses).forEach(function(userId) {
                const status = statuses[userId];
                const $employeeRow = $(`.employee-row[data-user-id="${userId}"]`);
                
                if ($employeeRow.length) {
                    // Update status badge
                    const $statusElement = $employeeRow.find('.employee-status');
                    $statusElement.attr('data-status', status.status);
                    
                    let statusClass = '';
                    let statusText = '';
                    
                    switch (status.status) {
                        case 'clocked_in':
                            statusClass = 'clocked-in';
                            statusText = 'Clocked In';
                            break;
                        case 'on_break':
                            statusClass = 'break-status';
                            statusText = 'On Break';
                            break;
                        case 'clocked_out':
                        default:
                            statusClass = 'clocked-out';
                            statusText = 'Clocked Out';
                            break;
                    }
                    
                    $statusElement.removeClass('clocked-in clocked-out break-status').addClass(statusClass);
                    $statusElement.text(statusText);
                    
                    // Update last action time with database value
                    if (status.last_action_time) {
                        const $lastActionElement = $employeeRow.find('.last-action-time');
                        $lastActionElement.text(dashboard.formatExactTime(status.last_action_time));
                    }
                }
            });
        },

        updateEmployeePositions: function(positions) {
            if (!positions) return;
            
            Object.keys(positions).forEach(function(userId) {
                const position = positions[userId];
                const $positionElement = $(`.employee-position[data-user-id="${userId}"]`);
                
                if ($positionElement.length) {
                    $positionElement.text(position);
                }
            });
        },

        updateEmployeeHireDates: function(hireDates) {
            if (!hireDates) return;
            
            Object.keys(hireDates).forEach(function(userId) {
                const hireDate = hireDates[userId];
                const $hireDateElement = $(`.employee-hire-date[data-user-id="${userId}"]`);
                
                if ($hireDateElement.length) {
                    $hireDateElement.text(dashboard.formatExactTime(hireDate));
                }
            });
        },

        // Update specific employee row status without full refresh
        updateEmployeeRowStatus: function(userId, status) {
            const $row = $(`.employee-row[data-user-id="${userId}"]`);
            if ($row.length) {
                // Update status badge
                const $statusBadge = $row.find('.status-badge');
                
                let statusClass = '';
                let statusText = '';
                
                switch (status) {
                    case 'clocked_in':
                        statusClass = 'bg-green-100 text-green-800';
                        statusText = 'Clocked In';
                        break;
                    case 'on_break':
                        statusClass = 'bg-orange-100 text-orange-800';
                        statusText = 'On Break';
                        break;
                    case 'clocked_out':
                    default:
                        statusClass = 'bg-red-100 text-red-800';
                        statusText = 'Clocked Out';
                        break;
                }
                
                $statusBadge.removeClass().addClass(`status-badge ${statusClass}`);
                $statusBadge.text(statusText);
                
                // Update last action time
                $row.find('.last-action-time').text('Just now');
                
                // Update data attribute
                $row.find('.employee-status').attr('data-status', status);
            }
        }
    };
    
    // Initialize dashboard
    dashboard.init();
    
    // Add CSS for notifications and drawer functionality
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
            
            /* Drawer collapse styles */
            #masthead {
                transition: width 0.3s ease-in-out;
                overflow: hidden;
            }
            #masthead.drawer-collapsed {
                width: 4rem !important;
            }
            #masthead.drawer-collapsed .drawer-text-hidden {
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease, visibility 0.2s ease;
            }
            #masthead.drawer-collapsed .p-6 {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            #masthead.drawer-collapsed .site-logo,
            #masthead.drawer-collapsed .site-branding {
                display: none;
            }
            #masthead.drawer-collapsed .site-icon {
                display: block !important;
            }
            .site-icon {
                transition: opacity 0.3s ease-in-out;
            }
            #masthead.drawer-collapsed .user-info-text,
            #masthead.drawer-collapsed .user-menu-arrow {
                display: none;
            }
            #masthead.drawer-collapsed #user-menu-toggle {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            #masthead.drawer-collapsed nav a {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            #masthead.drawer-collapsed .drawer-toggle-text {
                display: none;
            }
            #masthead.drawer-collapsed #drawer-toggle {
                justify-content: center;
            }
            #main-content {
                transition: margin-left 0.3s ease-in-out;
            }
            
            /* Arrow rotation animation */
            .arrow-icon {
                transition: transform 0.3s ease-in-out;
            }
            .arrow-icon.arrow-rotated {
                transform: rotate(180deg);
            }
            
            /* User dropdown animation */
            .user-menu-arrow {
                transition: transform 0.2s ease-in-out;
            }
            .rotate-180 {
                transform: rotate(180deg);
            }
            
            /* Menu item styles */
            .menu-item a {
                transition: all 0.2s ease-in-out;
            }
            
            /* Collapsed menu item styles */
            #masthead.drawer-collapsed .menu-item a span {
                opacity: 0;
                visibility: hidden;
            }
            #masthead.drawer-collapsed .menu-item a {
                justify-content: center;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            #masthead.drawer-collapsed .menu-item a svg {
                margin-right: 0;
            }
            
            /* Active menu item */
            .menu-item a.active,
            .menu-item-dashboard a[href*="/"]:not([href*="/time-tracking"]):not([href*="/approve-timesheets"]):not([href*="/account"]) {
                color: #1d4ed8;
            }
        </style>
    `;
    $('head').append(notificationCSS);
});
