        </main><!-- #main -->
    </div><!-- #content -->

    <footer id="colophon" class="site-footer bg-gray-800 text-white mt-12">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="footer-widget">
                    <h3 class="text-lg font-semibold mb-4">About LinkageClock</h3>
                    <p class="text-gray-300">
                        Efficient payroll time tracking solution for modern businesses.
                    </p>
                </div>
                
                <div class="footer-widget">
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="<?php echo esc_url(home_url('/')); ?>" class="text-gray-300 hover:text-white">Home</a></li>
                        <li><a href="<?php echo esc_url(home_url('/time-tracking')); ?>" class="text-gray-300 hover:text-white">Time Tracking</a></li>
                        <li><a href="<?php echo esc_url(home_url('/approve-timesheets')); ?>" class="text-gray-300 hover:text-white">Approve Timesheets</a></li>
                    </ul>
                </div>
                
                <div class="footer-widget">
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <p class="text-gray-300">
                        Need help? Contact our support team.
                    </p>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
