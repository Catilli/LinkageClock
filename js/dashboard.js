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
            
            $userDropdown.removeClass('opacity-0 invisible translate-y-2').addClass('opacity-100 visible translate-y-0');
            $userArrow.addClass('rotate-180');
        },
        
        closeUserDropdown: function() {
            const $userDropdown = $('#user-dropdown-menu');
            const $userArrow = $('.user-menu-arrow');
            
            $userDropdown.removeClass('opacity-100 visible translate-y-0').addClass('opacity-0 invisible translate-y-2');
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
            .status-badge.clocked-in {
                background-color: #d4edda;
                color: #155724;
            }
            .status-badge.clocked-out {
                background-color: #f8d7da;
                color: #721c24;
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
                background-color: #dbeafe;
                color: #1d4ed8;
            }
        </style>
    `;
    $('head').append(notificationCSS);
});
