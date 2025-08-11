<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

    <!-- Left Side Drawer -->
    <header id="masthead" class="site-header fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 z-40">
        
        <!-- Drawer Header -->
        <div class="p-6">
            <div class="flex items-center justify-between">
                <!-- Logo and Site Title -->
                <div class="flex items-center">
                    <?php if (has_custom_logo()): ?>
                        <div class="site-logo mr-3">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else: ?>
                        <div class="site-branding">
                            <h1 class="site-title text-xl font-bold text-gray-900">
                                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home" class="hover:text-blue-600 transition duration-200">
                                    <?php bloginfo('name'); ?>
                                </a>
                            </h1>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Drawer Body -->
        <div class="flex-1 overflow-y-auto flex flex-col">
            <!-- Navigation -->
            <nav id="site-navigation" class="main-navigation p-6">
                <?php linkage_display_navigation(); ?>
            </nav>

            <!-- Spacer to push user section to bottom -->
            <div class="flex-1"></div>

            <!-- User Section -->
            <?php if (is_user_logged_in()): ?>
                <div class="relative border-t border-gray-200">
                    <!-- User Toggle Button -->
                    <button id="user-menu-toggle" class="w-full p-6 flex items-center space-x-3 hover:bg-gray-50 transition-colors duration-200">
                        <?php 
                        $current_user = wp_get_current_user();
                        echo linkage_get_small_avatar($current_user->user_email, $current_user->display_name, array('border' => false));
                        ?>
                        <div class="flex-1 text-left">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo esc_html(wp_get_current_user()->display_name); ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?php echo esc_html(linkage_get_user_role_display(wp_get_current_user()->ID)); ?>
                            </p>
                        </div>
                        <svg class="user-menu-arrow w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- User Dropdown Menu -->
                    <div id="user-dropdown-menu" class="absolute bottom-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg mb-2 opacity-0 invisible transform translate-y-2 transition-all duration-200 ease-out z-50">
                        <div class="py-2">
                            <a href="<?php echo esc_url(home_url('/account')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>Your account settings</span>
                                </div>
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" 
                               class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Sign out</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Collapse Toggle -->
        <div class="border-t border-gray-200">
            <button id="drawer-toggle" class="w-full flex items-center justify-center p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all duration-200 group">
                <svg class="arrow-icon w-5 h-5 transform transition-transform duration-300 ease-in-out" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                <span class="ml-2 text-sm font-medium drawer-toggle-text">COLLAPSE</span>
            </button>
        </div>
    </header>

    <!-- Main Content Area -->
    <div id="main-content" class="ml-64 transition-all duration-300">
        <header id="toolbar" class="toolbar bg-white border-b border-gray-200 shadow-sm">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <!-- Left Side - Page Title -->
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php 
                            $page_title = get_the_title();
                            if (is_front_page() || empty($page_title)) {
                                echo 'Dashboard';
                            } else {
                                echo esc_html($page_title);
                            }
                            ?>
                        </h1>
                    </div>
                    
                    <!-- Right Side - Clock Controls -->
                    <?php if (is_user_logged_in()): ?>
                        <?php 
                        $current_user = wp_get_current_user();
                        $employee_status = linkage_get_employee_status($current_user->ID);
                        $is_clocked_in = $employee_status->status === 'clocked_in';
                        $is_on_break = $employee_status->status === 'on_break';
                        $is_working = $is_clocked_in || $is_on_break;
                        ?>
                        
                        <div class="clock-buttons flex items-center space-x-4" id="clock-controls">
                            <!-- Work Timer Display -->
                            <div class="timer work-timer bg-gray-100 px-4 py-2 rounded-lg" id="work-timer" style="display: <?php echo $is_working ? 'block' : 'none'; ?>;">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full <?php echo $is_clocked_in ? 'animate-pulse' : ''; ?>"></div>
                                    <span class="current text-lg font-mono text-gray-700" id="work-time">00:00:00</span>
                                    <span class="text-xs text-gray-500">Work</span>
                                </div>
                            </div>
                            
                            <!-- Break Timer Display -->
                            <div class="timer break-timer bg-orange-100 px-4 py-2 rounded-lg" id="break-timer" style="display: <?php echo $is_on_break ? 'block' : 'none'; ?>;">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                                    <span class="current text-lg font-mono text-orange-700" id="break-time">00:00:00</span>
                                    <span class="text-xs text-orange-600">Break</span>
                                </div>
                            </div>
                            
                            <!-- Clock Action Buttons -->
                            <div class="clock-panels flex items-center space-x-3">
                                <!-- Clock In/Out Button -->
                                <button id="clock-toggle-btn" 
                                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors duration-200 font-medium
                                               <?php echo $is_working ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'; ?>"
                                        data-action="<?php echo $is_working ? 'clock_out' : 'clock_in'; ?>">
                                    
                                    <!-- Clock In Icon -->
                                    <svg class="w-5 h-5 clock-in-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo $is_working ? 'none' : 'block'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    
                                    <!-- Clock Out Icon (Stop) -->
                                    <svg class="w-5 h-5 clock-out-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo $is_working ? 'block' : 'none'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
                                    </svg>
                                    
                                    <span id="clock-toggle-text"><?php echo $is_working ? 'Clock Out' : 'Clock In'; ?></span>
                                </button>
                                
                                <!-- Break Button -->
                                <button id="break-toggle-btn" 
                                        class="flex items-center space-x-2 bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 font-medium"
                                        style="display: <?php echo $is_working ? 'flex' : 'none'; ?>;"
                                        data-action="<?php echo $is_on_break ? 'break_end' : 'break_start'; ?>">
                                    
                                    <!-- Start Break Icon -->
                                    <svg class="w-5 h-5 break-start-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo $is_on_break ? 'none' : 'block'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a3.5 3.5 0 110 7H9m-1-7h1m4-7v2m0 12v2m4.95-4.95l1.41 1.41m0-14.14l-1.41 1.41M6.464 20.536l1.414-1.414m0-14.14l-1.414 1.414M12 7a5 5 0 100 10 5 5 0 000-10z"></path>
                                    </svg>
                                    
                                    <!-- End Break Icon -->
                                    <svg class="w-5 h-5 break-end-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo $is_on_break ? 'block' : 'none'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    
                                    <span id="break-toggle-text"><?php echo $is_on_break ? 'End Break' : 'Start Break'; ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div id="content" class="site-content">
            <main id="main" class="site-main">