<?php
/*
 * Template Name: Approve Timesheets
 */

get_header(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <?php
        // Check if user has appropriate permissions
        if (!current_user_can('manage_options') && !current_user_can('linkage_approve_timesheets')) {
            wp_die('You are not allowed to access this page.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'linkage_timesheets';
        $timesheets = $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending'");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'];
            $id = intval($_POST['id']);
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $wpdb->update($table, ['status' => $status], ['id' => $id]);
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>Timesheet $status.</div>";
            // Refresh the list
            $timesheets = $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending'");
        }
        ?>

        <h1 class="text-3xl font-bold text-gray-900 mb-8">Pending Timesheets</h1>
        
        <?php if (empty($timesheets)): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                No pending timesheets to approve.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300 shadow-lg rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($timesheets as $ts): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= esc_html($ts->id) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= esc_html(get_userdata($ts->user_id)->display_name) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= esc_html($ts->work_date) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= esc_html($ts->hours_worked) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= esc_html($ts->notes) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="post" class="inline-flex space-x-2">
                                        <input type="hidden" name="id" value="<?= esc_attr($ts->id) ?>">
                                        <button type="submit" name="action" value="approve" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                            ✅ Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded text-sm">
                                            ❌ Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
