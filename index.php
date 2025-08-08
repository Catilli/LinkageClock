<?php
/**
 * The main template file
 */

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Welcome to LinkageClock
            </h1>
            <p class="text-xl text-gray-600 mb-8">
                Efficient payroll time tracking solution for modern businesses
            </p>
            
            <?php if (is_user_logged_in()): ?>
                <div class="flex justify-center space-x-4">
                    <a href="<?php echo esc_url(home_url('/time-tracking')); ?>" 
                       class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
                        Log Time
                    </a>
                    <?php if (current_user_can('linkage_approve_timesheets')): ?>
                        <a href="<?php echo esc_url(home_url('/approve-timesheets')); ?>" 
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg text-lg">
                            Approve Timesheets
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    Please <a href="<?php echo esc_url(wp_login_url()); ?>" class="underline">log in</a> to access time tracking features.
                </div>
            <?php endif; ?>
        </div>

        <!-- Features Section -->
        <div class="grid md:grid-cols-3 gap-8 mb-12">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-blue-500 text-3xl mb-4">⏰</div>
                <h3 class="text-xl font-semibold mb-2">Time Tracking</h3>
                <p class="text-gray-600">Easily log your work hours with our intuitive time tracking system.</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-green-500 text-3xl mb-4">📊</div>
                <h3 class="text-xl font-semibold mb-2">Payroll Integration</h3>
                <p class="text-gray-600">Seamless integration with payroll systems for accurate processing.</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="text-purple-500 text-3xl mb-4">👥</div>
                <h3 class="text-xl font-semibold mb-2">Employee Management</h3>
                <p class="text-gray-600">Manage employee accounts and permissions efficiently.</p>
            </div>
        </div>

        <!-- Quick Stats (if user is logged in) -->
        <?php if (is_user_logged_in()): ?>
            <div class="bg-gray-50 p-6 rounded-lg mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Quick Stats</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <?php
                    global $wpdb;
                    $user_id = get_current_user_id();
                    $table = $wpdb->prefix . 'linkage_timesheets';
                    
                    // Total hours this month
                    $current_month = date('Y-m');
                    $monthly_hours = $wpdb->get_var($wpdb->prepare(
                        "SELECT SUM(hours_worked) FROM $table WHERE user_id = %d AND DATE_FORMAT(work_date, '%%Y-%%m') = %s",
                        $user_id, $current_month
                    ));
                    
                    // Pending timesheets
                    $pending_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND status = 'pending'",
                        $user_id
                    ));
                    
                    // Approved timesheets
                    $approved_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND status = 'approved'",
                        $user_id
                    ));
                    ?>
                    
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600"><?php echo $monthly_hours ? number_format($monthly_hours, 1) : '0'; ?></div>
                        <div class="text-gray-600">Hours This Month</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-3xl font-bold text-yellow-600"><?php echo $pending_count ?: '0'; ?></div>
                        <div class="text-gray-600">Pending Timesheets</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600"><?php echo $approved_count ?: '0'; ?></div>
                        <div class="text-gray-600">Approved Timesheets</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Posts Section -->
        <?php if (have_posts()): ?>
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Latest Updates</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <?php while (have_posts()): the_post(); ?>
                        <article class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-xl font-semibold mb-2">
                                <a href="<?php the_permalink(); ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php the_title(); ?>
                                </a>
                            </h3>
                            <div class="text-gray-600 mb-3">
                                <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo get_the_date(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <?php if (get_next_posts_link() || get_previous_posts_link()): ?>
                    <div class="flex justify-between mt-8">
                        <?php if (get_previous_posts_link()): ?>
                            <div class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                <?php previous_posts_link('← Previous'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (get_next_posts_link()): ?>
                            <div class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                <?php next_posts_link('Next →'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">No Posts Found</h2>
                <p class="text-gray-600">Check back later for updates and announcements.</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>
