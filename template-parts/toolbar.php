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
                        $employee_status = linkage_get_employee_status_from_database($current_user->ID);
                        $is_clocked_in = $employee_status->status === 'clocked_in';
                        $is_on_break = $employee_status->status === 'on_break';
                        $is_working = $is_clocked_in || $is_on_break;
                        ?>
                        
                        <div class="clock-buttons flex items-center space-x-4" id="clock-controls">
                            <!-- Work Timer Display -->
                            <div class="timer work-timer bg-gray-100 px-4 py-2 rounded-lg" id="work-timer" 
                                 data-clock-in-time="<?php echo esc_attr($employee_status->clock_in_time); ?>">
                                <div class="flex items-center space-x-2">
                                    <?php if ($is_clocked_in || $is_on_break): ?>
                                        <div class="w-2 h-2 bg-green-500 rounded-full <?php echo $is_clocked_in ? 'animate-pulse' : ''; ?>"></div>
                                        <span class="current text-lg font-mono text-gray-700" id="work-time"><?php echo linkage_format_time_display($employee_status->work_seconds); ?></span>
                                        <span class="text-xs text-gray-500">Work</span>
                                    <?php else: ?>
                                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                        <span class="current text-lg font-mono text-gray-700" id="last-action-time">
                                            <?php 
                                            if ($employee_status->last_action_time && 
                                                $employee_status->last_action_time !== 'Never' && 
                                                $employee_status->last_action_time !== '' &&
                                                strtotime($employee_status->last_action_time) !== false) {
                                                
                                                $last_time = new DateTime($employee_status->last_action_time);
                                                $now = new DateTime();
                                                $today = $now->format('Y-m-d');
                                                $yesterday = $now->modify('-1 day')->format('Y-m-d');
                                                $last_date = $last_time->format('Y-m-d');
                                                
                                                if ($last_date === $today) {
                                                    echo $last_time->format('g:i A') . ', Today';
                                                } elseif ($last_date === $yesterday) {
                                                    echo $last_time->format('g:i A') . ', Yesterday';
                                                } else {
                                                    echo $last_time->format('g:i A, M j');
                                                }
                                            } else {
                                                // Use user registration date as fallback
                                                $user_registered = $current_user->user_registered;
                                                $registration_time = new DateTime($user_registered);
                                                $now = new DateTime();
                                                $today = $now->format('Y-m-d');
                                                $yesterday = $now->modify('-1 day')->format('Y-m-d');
                                                $reg_date = $registration_time->format('Y-m-d');
                                                
                                                if ($reg_date === $today) {
                                                    echo $registration_time->format('g:i A') . ', Today';
                                                } elseif ($reg_date === $yesterday) {
                                                    echo $registration_time->format('g:i A') . ', Yesterday';
                                                } else {
                                                    echo $registration_time->format('g:i A, M j');
                                                }
                                            }
                                            ?>
                                        </span>
                                        <span class="text-xs text-gray-500">Last</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Break Timer Display -->
                            <div class="timer break-timer bg-orange-100 px-4 py-2 rounded-lg" id="break-timer" 
                                 style="display: <?php echo $is_on_break ? 'block' : 'none'; ?>;"
                                 data-break-start-time="<?php echo esc_attr($employee_status->break_start_time); ?>">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                                    <span class="current text-lg font-mono text-orange-700" id="break-time"><?php echo linkage_format_time_display($employee_status->break_seconds); ?></span>
                                    <span class="text-xs text-orange-600">Lunch</span>
                                </div>
                            </div>
                            
                            <!-- Clock Action Buttons -->
                            <div class="clock-panels flex items-center space-x-3">                                
                                <!-- Break Button -->
                                <button id="break-toggle-btn" 
                                        class="flex items-center space-x-2 bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 font-medium"
                                        style="display: <?php echo ($is_clocked_in || $is_on_break) ? 'flex' : 'none'; ?>;"
                                        data-action="<?php echo $is_on_break ? 'break_end' : 'break_start'; ?>">
                                    
                                    <!-- Start Break Icon -->
                                    <svg class="w-5 h-5 break-start-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo $is_on_break ? 'none' : 'block'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a3.5 3.5 0 110 7H9m-1-7h1m4-7v2m0 12v2m4.95-4.95l1.41 1.41m0-14.14l-1.41 1.41M6.464 20.536l1.414-1.414m0-14.14l-1.414 1.414M12 7a5 5 0 100 10 5 5 0 000-10z"></path>
                                    </svg>
                                    
                                    <!-- End Break Icon -->
                                    <svg class="w-5 h-5 break-end-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo $is_on_break ? 'block' : 'none'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    
                                    <span id="break-toggle-text"><?php echo $is_on_break ? 'Lunch End' : 'Lunch Start'; ?></span>
                                </button>

                                <!-- Clock In/Out Button -->
                                <button id="clock-toggle-btn" 
                                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors duration-200 font-medium
                                               <?php echo ($is_clocked_in || $is_on_break) ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'; ?>"
                                        style="display: <?php echo ($is_on_break) ? 'none' : 'flex'; ?>;"
                                        data-action="<?php echo ($is_clocked_in || $is_on_break) ? 'clock_out' : 'clock_in'; ?>">
                                    
                                    <!-- Clock In Icon -->
                                    <svg class="w-5 h-5 clock-in-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo ($is_clocked_in || $is_on_break) ? 'none' : 'block'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    
                                    <!-- Clock Out Icon (Stop) -->
                                    <svg class="w-5 h-5 clock-out-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: <?php echo ($is_clocked_in || $is_on_break) ? 'block' : 'none'; ?>;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
                                    </svg>
                                    
                                    <span id="clock-toggle-text"><?php echo ($is_clocked_in || $is_on_break) ? 'Time Out' : 'Time In'; ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>
