<?php
/**
 * Custom Post Type: Payments
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_CPT_Payments {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'register'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('manage_tgcb_payment_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_tgcb_payment_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('post_row_actions', array($this, 'row_actions'), 10, 2);
    }
    
    /**
     * Register custom post type
     */
    public static function register() {
        $labels = array(
            'name' => __('Payments', 'tg-course-bot-pro'),
            'singular_name' => __('Payment', 'tg-course-bot-pro'),
            'menu_name' => __('Payments', 'tg-course-bot-pro'),
            'search_items' => __('Search Payments', 'tg-course-bot-pro'),
            'not_found' => __('No payments found', 'tg-course-bot-pro'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false,
            ),
            'map_meta_cap' => true,
            'hierarchical' => false,
            'supports' => array('title'),
            'has_archive' => false,
            'rewrite' => false,
        );
        
        register_post_type('tgcb_payment', $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'tgcb_payment_details',
            __('Payment Details', 'tg-course-bot-pro'),
            array($this, 'render_details_meta_box'),
            'tgcb_payment',
            'normal',
            'high'
        );
        
        add_meta_box(
            'tgcb_payment_actions',
            __('Actions', 'tg-course-bot-pro'),
            array($this, 'render_actions_meta_box'),
            'tgcb_payment',
            'side',
            'high'
        );
    }
    
    /**
     * Render details meta box
     */
    public function render_details_meta_box($post) {
        $tg_id = get_post_meta($post->ID, '_tgcb_tg_id', true);
        $username = get_post_meta($post->ID, '_tgcb_username', true);
        $first_name = get_post_meta($post->ID, '_tgcb_first_name', true);
        $last_name = get_post_meta($post->ID, '_tgcb_last_name', true);
        $course_id = get_post_meta($post->ID, '_tgcb_course_id', true);
        $receipt_photo = get_post_meta($post->ID, '_tgcb_receipt_photo', true);
        $status = get_post_meta($post->ID, '_tgcb_status', true);
        $invite_link = get_post_meta($post->ID, '_tgcb_invite_link', true);
        $joined_date = get_post_meta($post->ID, '_tgcb_joined_date', true);
        $message_id = get_post_meta($post->ID, '_tgcb_message_id', true);
        
        $course_title = $course_id ? get_the_title($course_id) : __('Unknown', 'tg-course-bot-pro');
        
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Student', 'tg-course-bot-pro'); ?></th>
                <td>
                    <strong><?php echo esc_html($first_name . ' ' . $last_name); ?></strong><br>
                    <?php if ($username): ?>
                        <small>@<?php echo esc_html($username); ?></small><br>
                    <?php endif; ?>
                    <small>TG ID: <?php echo esc_html($tg_id); ?></small>
                </td>
            </tr>
            <tr>
                <th><?php _e('Course', 'tg-course-bot-pro'); ?></th>
                <td>
                    <a href="<?php echo get_edit_post_link($course_id); ?>"><?php echo esc_html($course_title); ?></a>
                </td>
            </tr>
            <tr>
                <th><?php _e('Status', 'tg-course-bot-pro'); ?></th>
                <td>
                    <?php
                    $status_labels = array(
                        'pending' => '<span class="tgcb-status tgcb-status-pending">⏳ ' . __('Pending', 'tg-course-bot-pro') . '</span>',
                        'approved' => '<span class="tgcb-status tgcb-status-approved">✅ ' . __('Approved', 'tg-course-bot-pro') . '</span>',
                        'rejected' => '<span class="tgcb-status tgcb-status-rejected">❌ ' . __('Rejected', 'tg-course-bot-pro') . '</span>',
                        'completed' => '<span class="tgcb-status tgcb-status-completed">🎓 ' . __('Completed', 'tg-course-bot-pro') . '</span>',
                    );
                    echo $status_labels[$status] ?? $status;
                    ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Receipt Photo', 'tg-course-bot-pro'); ?></th>
                <td>
                    <?php if ($receipt_photo): ?>
                        <img src="<?php echo esc_url($receipt_photo); ?>" style="max-width: 300px; height: auto;" />
                    <?php else: ?>
                        <?php _e('No photo', 'tg-course-bot-pro'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($invite_link): ?>
            <tr>
                <th><?php _e('Invite Link', 'tg-course-bot-pro'); ?></th>
                <td>
                    <code><?php echo esc_html($invite_link); ?></code>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ($joined_date): ?>
            <tr>
                <th><?php _e('Joined Date', 'tg-course-bot-pro'); ?></th>
                <td>
                    <?php echo esc_html($joined_date); ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    /**
     * Render actions meta box
     */
    public function render_actions_meta_box($post) {
        $status = get_post_meta($post->ID, '_tgcb_status', true);
        
        if ($status === 'pending'):
        ?>
        <div class="tgcb-payment-actions">
            <button type="button" class="button button-primary button-large tgcb-approve-payment" data-payment-id="<?php echo $post->ID; ?>" style="width: 100%; margin-bottom: 10px;">
                ✅ <?php _e('Approve Payment', 'tg-course-bot-pro'); ?>
            </button>
            <button type="button" class="button button-secondary button-large tgcb-reject-payment" data-payment-id="<?php echo $post->ID; ?>" style="width: 100%;">
                ❌ <?php _e('Reject Payment', 'tg-course-bot-pro'); ?>
            </button>
        </div>
        <?php
        else:
        ?>
        <p><?php _e('No actions available', 'tg-course-bot-pro'); ?></p>
        <?php
        endif;
    }
    
    /**
     * Set custom columns
     */
    public function set_custom_columns($columns) {
        return array(
            'cb' => $columns['cb'],
            'title' => __('Student', 'tg-course-bot-pro'),
            'course' => __('Course', 'tg-course-bot-pro'),
            'status' => __('Status', 'tg-course-bot-pro'),
            'receipt' => __('Receipt', 'tg-course-bot-pro'),
            'date' => $columns['date']
        );
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'course':
                $course_id = get_post_meta($post_id, '_tgcb_course_id', true);
                if ($course_id) {
                    echo '<a href="' . get_edit_post_link($course_id) . '">' . get_the_title($course_id) . '</a>';
                }
                break;
            case 'status':
                $status = get_post_meta($post_id, '_tgcb_status', true);
                $status_labels = array(
                    'pending' => '⏳ Pending',
                    'approved' => '✅ Approved',
                    'rejected' => '❌ Rejected',
                    'completed' => '🎓 Completed',
                );
                echo $status_labels[$status] ?? $status;
                break;
            case 'receipt':
                $receipt = get_post_meta($post_id, '_tgcb_receipt_photo', true);
                if ($receipt) {
                    echo '<a href="' . esc_url($receipt) . '" target="_blank">📷 View</a>';
                }
                break;
        }
    }
    
    /**
     * Row actions
     */
    public function row_actions($actions, $post) {
        if ($post->post_type === 'tgcb_payment') {
            unset($actions['inline hide-if-no-js']);
            unset($actions['trash']);
        }
        return $actions;
    }
}
