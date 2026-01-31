<?php
/**
 * Custom Post Type: Courses
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_CPT_Courses
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
        add_action('init', array($this, 'register'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_tgcb_course', array($this, 'save_meta_boxes'));
        add_filter('manage_tgcb_course_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_tgcb_course_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }

    /**
     * Register custom post type
     */
    public static function register()
    {
        $labels = array(
            'name' => __('Courses', 'tg-course-bot-pro'),
            'singular_name' => __('Course', 'tg-course-bot-pro'),
            'menu_name' => __('Courses', 'tg-course-bot-pro'),
            'add_new' => __('Add New', 'tg-course-bot-pro'),
            'add_new_item' => __('Add New Course', 'tg-course-bot-pro'),
            'edit_item' => __('Edit Course', 'tg-course-bot-pro'),
            'new_item' => __('New Course', 'tg-course-bot-pro'),
            'view_item' => __('View Course', 'tg-course-bot-pro'),
            'search_items' => __('Search Courses', 'tg-course-bot-pro'),
            'not_found' => __('No courses found', 'tg-course-bot-pro'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
        );

        register_post_type('tgcb_course', $args);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'tgcb_course_details',
            __('Course Details', 'tg-course-bot-pro'),
            array($this, 'render_meta_box'),
            'tgcb_course',
            'normal',
            'high'
        );

        add_meta_box(
            'tgcb_course_analytics',
            __('Course Analytics', 'tg-course-bot-pro'),
            array($this, 'render_analytics_meta_box'),
            'tgcb_course',
            'normal',
            'high'
        );
    }

    /**
     * Render meta box
     */
    public function render_meta_box($post)
    {
        wp_nonce_field('tgcb_course_meta_box', 'tgcb_course_meta_nonce');

        $channel_id = get_post_meta($post->ID, '_tgcb_channel_id', true);
        $welcome_message = get_post_meta($post->ID, '_tgcb_welcome_message', true);
        $link_expire_hours = get_post_meta($post->ID, '_tgcb_link_expire_hours', true) ?: 24;
        $link_member_limit = get_post_meta($post->ID, '_tgcb_link_member_limit', true) ?: 1;
        $price = get_post_meta($post->ID, '_tgcb_price', true);
        $currency = get_post_meta($post->ID, '_tgcb_currency', true) ?: 'USD';
        ?>
        <table class="form-table">
            <tr>
                <th>
                    <label for="tgcb_channel_id">
                        <?php _e('Telegram Channel ID', 'tg-course-bot-pro'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="tgcb_channel_id" name="tgcb_channel_id" value="<?php echo esc_attr($channel_id); ?>"
                        class="regular-text" required />
                    <p class="description">
                        <?php _e('Use @GetIDsBot to find the ID. Private channels format: -100xxxxxxxxxx.', 'tg-course-bot-pro'); ?>
                        <br>
                        <?php _e('Browser URL check: web.telegram.org/z/-100... -> ID is -100...', 'tg-course-bot-pro'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="tgcb_price">
                        <?php _e('Price', 'tg-course-bot-pro'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" id="tgcb_price" name="tgcb_price" value="<?php echo esc_attr($price); ?>"
                        class="small-text" step="0.01" />
                    <select name="tgcb_currency" id="tgcb_currency">
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                        <option value="RUB" <?php selected($currency, 'RUB'); ?>>RUB</option>
                        <option value="AMD" <?php selected($currency, 'AMD'); ?>>AMD</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="tgcb_welcome_message">
                        <?php _e('Welcome Message', 'tg-course-bot-pro'); ?>
                    </label>
                </th>
                <td>
                    <textarea id="tgcb_welcome_message" name="tgcb_welcome_message" rows="5"
                        class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                    <p class="description">
                        <?php _e('Message sent to student after joining the channel. Supports HTML.', 'tg-course-bot-pro'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="tgcb_link_expire_hours">
                        <?php _e('Invite Link Expire (hours)', 'tg-course-bot-pro'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" id="tgcb_link_expire_hours" name="tgcb_link_expire_hours"
                        value="<?php echo esc_attr($link_expire_hours); ?>" class="small-text" min="1" max="168" />
                    <p class="description">
                        <?php _e('How long the invite link will be valid (1-168 hours)', 'tg-course-bot-pro'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="tgcb_link_member_limit">
                        <?php _e('Member Limit', 'tg-course-bot-pro'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" id="tgcb_link_member_limit" name="tgcb_link_member_limit"
                        value="<?php echo esc_attr($link_member_limit); ?>" class="small-text" min="1" max="1" readonly />
                    <p class="description">
                        <?php _e('Set to "1" so each student gets a unique secure link (recommended).', 'tg-course-bot-pro'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Analytics Meta Box
     */
    public function render_analytics_meta_box($post)
    {
        global $wpdb;
        $course_id = $post->ID;

        // 1. Total Students
        $table_students = $wpdb->prefix . 'tgcb_students';
        $total_students = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_students WHERE courses REGEXP %s",
            '[[:<:]]' . $course_id . '[[:>:]]'
        ));

        // 2. Total Revenue
        // Assuming we store price in Course meta and payments in tgcb_payment post type
        // We need to sum the price of approved payments for this course
        // OR better: count approved payments and multiply by current price (simple version)
        // OR best: query tgcb_payment posts linked to this course and sum them up (if we stored amount)
        // Since we didn't store amount in payment (only course ref), we'll do: Approved Payments Count * Current Price
        // Note: This is an approximation if price changed. For accurate historical data, we should have stored amount in payment.
        // Let's check if we have payment records. Yes 'tgcb_payment'.

        $args = array(
            'post_type' => 'tgcb_payment',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_tgcb_course_id',
                    'value' => $course_id
                ),
                array(
                    'key' => '_tgcb_status',
                    'value' => 'approved'
                )
            ),
            'fields' => 'ids',
            'posts_per_page' => -1
        );
        $payments = get_posts($args);
        $sales_count = count($payments);

        $price = get_post_meta($course_id, '_tgcb_price', true);
        $currency = get_post_meta($course_id, '_tgcb_currency', true) ?: 'USD';
        $total_revenue = $sales_count * floatval($price);

        // 3. Recent Sales
        $recent_sales_args = array(
            'post_type' => 'tgcb_payment',
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_tgcb_course_id',
                    'value' => $course_id
                ),
                array(
                    'key' => '_tgcb_status',
                    'value' => 'approved'
                )
            ),
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        $recent_sales = get_posts($recent_sales_args);

        ?>
        <div class="tgcb-analytics-dashboard"
            style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center; margin-bottom: 20px;">
            <div class="tgcb-stat-card" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                <h3 style="margin: 0; font-size: 14px; color: #646970;"><?php _e('Total Students', 'tg-course-bot-pro'); ?></h3>
                <p style="font-size: 24px; font-weight: bold; margin: 10px 0 0;"><?php echo intval($total_students); ?></p>
            </div>
            <div class="tgcb-stat-card" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                <h3 style="margin: 0; font-size: 14px; color: #646970;"><?php _e('Total Sales', 'tg-course-bot-pro'); ?></h3>
                <p style="font-size: 24px; font-weight: bold; margin: 10px 0 0;"><?php echo intval($sales_count); ?></p>
            </div>
            <div class="tgcb-stat-card" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                <h3 style="margin: 0; font-size: 14px; color: #646970;"><?php _e('Estimated Revenue', 'tg-course-bot-pro'); ?>
                </h3>
                <p style="font-size: 24px; font-weight: bold; margin: 10px 0 0; color: #00a32a;">
                    <?php echo number_format($total_revenue, 2) . ' ' . esc_html($currency); ?>
                </p>
            </div>
        </div>

        <h3 style="padding-left: 10px; border-left: 4px solid #2271b1;"><?php _e('Recent Sales', 'tg-course-bot-pro'); ?></h3>
        <?php if (!empty($recent_sales)): ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'tg-course-bot-pro'); ?></th>
                        <th><?php _e('Student', 'tg-course-bot-pro'); ?></th>
                        <th><?php _e('Amount', 'tg-course-bot-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_sales as $sale):
                        $username = get_post_meta($sale->ID, '_tgcb_username', true);
                        $firstname = get_post_meta($sale->ID, '_tgcb_first_name', true);
                        ?>
                        <tr>
                            <td><?php echo get_the_date('Y-m-d H:i', $sale->ID); ?></td>
                            <td>
                                <strong><?php echo esc_html($firstname); ?></strong>
                                <?php if ($username): ?>
                                    <br><a href="https://t.me/<?php echo esc_attr($username); ?>"
                                        target="_blank">@<?php echo esc_html($username); ?></a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format(floatval($price), 2) . ' ' . esc_html($currency); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No sales yet.', 'tg-course-bot-pro'); ?></p>
        <?php endif; ?>
    <?php
    }

    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id)
    {
        if (!isset($_POST['tgcb_course_meta_nonce']) || !wp_verify_nonce($_POST['tgcb_course_meta_nonce'], 'tgcb_course_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'tgcb_channel_id',
            'tgcb_welcome_message',
            'tgcb_link_expire_hours',
            'tgcb_link_member_limit',
            'tgcb_price',
            'tgcb_currency'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save welcome message with textarea sanitization
        if (isset($_POST['tgcb_welcome_message'])) {
            update_post_meta($post_id, '_tgcb_welcome_message', wp_kses_post($_POST['tgcb_welcome_message']));
        }
    }

    /**
     * Set custom columns
     */
    public function set_custom_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['channel_id'] = __('Channel ID', 'tg-course-bot-pro');
        $new_columns['price'] = __('Price', 'tg-course-bot-pro');
        $new_columns['students'] = __('Students', 'tg-course-bot-pro');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'channel_id':
                echo esc_html(get_post_meta($post_id, '_tgcb_channel_id', true));
                break;
            case 'price':
                $price = get_post_meta($post_id, '_tgcb_price', true);
                $currency = get_post_meta($post_id, '_tgcb_currency', true) ?: 'USD';
                if ($price) {
                    echo esc_html($price . ' ' . $currency);
                } else {
                    echo '—';
                }
                break;
            case 'students':
                // Count students with this course
                global $wpdb;
                $table_name = $wpdb->prefix . 'tgcb_students';
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE courses REGEXP %s",
                    '[[:<:]]' . $post_id . '[[:>:]]'
                ));
                echo intval($count);
                break;
        }
    }
}
