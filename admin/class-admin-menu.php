<?php
/**
 * Admin Menu Manager
 * Creates WordPress admin menu structure
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Admin_Menu
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_pages'));
    }

    /**
     * Add admin menu pages
     */
    public function add_menu_pages()
    {
        // Main menu
        add_menu_page(
            __('TG Course Bot PRO', 'tg-course-bot-pro'),
            __('TG Course Bot', 'tg-course-bot-pro'),
            'manage_options',
            'tg-course-bot',
            array($this, 'dashboard_page'),
            'dashicons-welcome-learn-more',
            30
        );

        // Dashboard
        add_submenu_page(
            'tg-course-bot',
            __('Dashboard', 'tg-course-bot-pro'),
            __('Dashboard', 'tg-course-bot-pro'),
            'manage_options',
            'tg-course-bot',
            array($this, 'dashboard_page')
        );

        // Courses (CPT)
        add_submenu_page(
            'tg-course-bot',
            __('Courses', 'tg-course-bot-pro'),
            __('Courses', 'tg-course-bot-pro'),
            'manage_options',
            'edit.php?post_type=tgcb_course'
        );

        // Payments (CPT)
        add_submenu_page(
            'tg-course-bot',
            __('Payments', 'tg-course-bot-pro'),
            __('Payments', 'tg-course-bot-pro'),
            'manage_options',
            'edit.php?post_type=tgcb_payment'
        );

        // Students
        add_submenu_page(
            'tg-course-bot',
            __('Students', 'tg-course-bot-pro'),
            __('Students', 'tg-course-bot-pro'),
            'manage_options',
            'tg-course-bot-students',
            array($this, 'students_page')
        );

        // Settings
        add_submenu_page(
            'tg-course-bot',
            __('Bot Settings', 'tg-course-bot-pro'),
            __('Settings', 'tg-course-bot-pro'),
            'manage_options',
            'tg-course-bot-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Dashboard page
     */
    public function dashboard_page()
    {
        // Get statistics
        $total_courses = wp_count_posts('tgcb_course')->publish;
        $total_students = TGCB_Database::get_students_count();
        $pending_payments = new WP_Query(array(
            'post_type' => 'tgcb_payment',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_tgcb_status',
                    'value' => 'pending'
                )
            )
        ));
        $total_pending = $pending_payments->found_posts;

        $piracy_stats = TGCB_Anti_Piracy::get_piracy_stats();

        ?>
        <div class="wrap tgcb-dashboard">
            <h1>
                <?php _e('TG Course Bot PRO - Dashboard', 'tg-course-bot-pro'); ?>
            </h1>

            <div class="tgcb-stats-grid">
                <a href="<?php echo admin_url('edit.php?post_type=tgcb_course'); ?>" class="tgcb-stat-card"
                    style="text-decoration: none; color: inherit;">
                    <div class="tgcb-stat-icon">📚</div>
                    <div class="tgcb-stat-content">
                        <h3>
                            <?php echo esc_html($total_courses); ?>
                        </h3>
                        <p>
                            <?php _e('Total Courses', 'tg-course-bot-pro'); ?>
                        </p>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=tg-course-bot-students'); ?>" class="tgcb-stat-card"
                    style="text-decoration: none; color: inherit;">
                    <div class="tgcb-stat-icon">👥</div>
                    <div class="tgcb-stat-content">
                        <h3>
                            <?php echo esc_html($total_students); ?>
                        </h3>
                        <p>
                            <?php _e('Total Students', 'tg-course-bot-pro'); ?>
                        </p>
                    </div>
                </a>

                <a href="<?php echo admin_url('edit.php?post_type=tgcb_payment&post_status=all'); ?>"
                    class="tgcb-stat-card tgcb-stat-pending" style="text-decoration: none; color: inherit;">
                    <div class="tgcb-stat-icon">⏳</div>
                    <div class="tgcb-stat-content">
                        <h3>
                            <?php echo esc_html($total_pending); ?>
                        </h3>
                        <p>
                            <?php _e('Pending Payments', 'tg-course-bot-pro'); ?>
                        </p>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=tg-course-bot'); ?>" class="tgcb-stat-card tgcb-stat-danger"
                    style="text-decoration: none; color: inherit;">
                    <div class="tgcb-stat-icon">🚨</div>
                    <div class="tgcb-stat-content">
                        <h3>
                            <?php echo esc_html($piracy_stats['total']); ?>
                        </h3>
                        <p>
                            <?php _e('Piracy Attempts', 'tg-course-bot-pro'); ?>
                        </p>
                    </div>
                </a>
            </div>

            <div class="tgcb-quick-actions">
                <h2>
                    <?php _e('Quick Actions', 'tg-course-bot-pro'); ?>
                </h2>
                <div class="tgcb-actions-grid">
                    <a href="<?php echo admin_url('post-new.php?post_type=tgcb_course'); ?>"
                        class="button button-primary button-hero">
                        ➕
                        <?php _e('Add New Course', 'tg-course-bot-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('edit.php?post_type=tgcb_payment'); ?>"
                        class="button button-secondary button-hero">
                        📋
                        <?php _e('View Payments', 'tg-course-bot-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=tg-course-bot-students'); ?>"
                        class="button button-secondary button-hero">
                        👥
                        <?php _e('Manage Students', 'tg-course-bot-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=tg-course-bot-settings'); ?>"
                        class="button button-secondary button-hero">
                        ⚙️
                        <?php _e('Bot Settings', 'tg-course-bot-pro'); ?>
                    </a>
                </div>
            </div>

            <?php if ($total_pending > 0): ?>
                <div class="tgcb-pending-section">
                    <h2>
                        <?php _e('Pending Payments Requiring Approval', 'tg-course-bot-pro'); ?>
                    </h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>
                                    <?php _e('Student', 'tg-course-bot-pro'); ?>
                                </th>
                                <th>
                                    <?php _e('Course', 'tg-course-bot-pro'); ?>
                                </th>
                                <th>
                                    <?php _e('Date', 'tg-course-bot-pro'); ?>
                                </th>
                                <th>
                                    <?php _e('Receipt', 'tg-course-bot-pro'); ?>
                                </th>
                                <th>
                                    <?php _e('Actions', 'tg-course-bot-pro'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($pending_payments->have_posts()) {
                                $pending_payments->the_post();
                                $payment_id = get_the_ID();
                                $first_name = get_post_meta($payment_id, '_tgcb_first_name', true);
                                $last_name = get_post_meta($payment_id, '_tgcb_last_name', true);
                                $course_id = get_post_meta($payment_id, '_tgcb_course_id', true);
                                $receipt = get_post_meta($payment_id, '_tgcb_receipt_photo', true);
                                ?>
                                <tr>
                                    <td><strong>
                                            <?php echo esc_html($first_name . ' ' . $last_name); ?>
                                        </strong></td>
                                    <td>
                                        <?php echo esc_html(get_the_title($course_id)); ?>
                                    </td>
                                    <td>
                                        <?php echo get_the_date(); ?>
                                    </td>
                                    <td>
                                        <?php if ($receipt): ?>
                                            <a href="<?php echo esc_url($receipt); ?>" target="_blank">📷 View</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($payment_id); ?>" class="button button-small">
                                            <?php _e('Review', 'tg-course-bot-pro'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            wp_reset_postdata();
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Students page
     */
    public function students_page()
    {
        $students_table = new TGCB_Students_Table();
        $students_table->prepare_items();

        ?>
        <div class="wrap">
            <h1>
                <?php _e('Students', 'tg-course-bot-pro'); ?>
            </h1>
            <form method="post">
                <?php $students_table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        TGCB_Bot_Settings::render_page();
    }
}
