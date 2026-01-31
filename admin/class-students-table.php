<?php
/**
 * Students Table
 * WP_List_Table for displaying students
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class TGCB_Students_Table extends WP_List_Table
{

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'student',
            'plural' => 'students',
            'ajax' => false
        ));
    }

    /**
     * Get columns
     */
    public function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'tg_id' => __('Telegram ID', 'tg-course-bot-pro'),
            'name' => __('Name', 'tg-course-bot-pro'),
            'username' => __('Username', 'tg-course-bot-pro'),
            'courses' => __('Courses', 'tg-course-bot-pro'),
            'first_seen' => __('First Seen', 'tg-course-bot-pro'),
            'last_access' => __('Last Access', 'tg-course-bot-pro'),
            'banned' => __('Status', 'tg-course-bot-pro'),
            'actions' => __('Actions', 'tg-course-bot-pro')
        );
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns()
    {
        return array(
            'tg_id' => array('tg_id', false),
            'first_seen' => array('first_seen', false),
            'last_access' => array('last_access', true)
        );
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions()
    {
        return array(
            'ban' => __('Ban', 'tg-course-bot-pro'),
            'unban' => __('Unban', 'tg-course-bot-pro'),
            'kick_all' => __('Remove from All Channels', 'tg-course-bot-pro'),
            'delete' => __('Delete Records', 'tg-course-bot-pro')
        );
    }

    /**
     * Column checkbox
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="students[]" value="%s" />', $item->tg_id);
    }

    /**
     * Column default
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'tg_id':
                return '<code>' . esc_html($item->tg_id) . '</code>';
            case 'name':
                $name = $item->first_name;
                if ($item->last_name) {
                    $name .= ' ' . $item->last_name;
                }
                $full_name = '<strong>' . esc_html($name) . '</strong>';
                if ($item->username) {
                    return '<a href="https://t.me/' . esc_attr($item->username) . '" target="_blank" style="text-decoration:none; color:inherit;">' . $full_name . '</a>';
                }
                return $full_name;
            case 'username':
                return $item->username ? '<a href="https://t.me/' . esc_attr($item->username) . '" target="_blank">@' . esc_html($item->username) . '</a>' : '—';
            case 'courses':
                $courses = $item->courses ? json_decode($item->courses, true) : array();
                if (empty($courses)) {
                    return '—';
                }
                $course_names = array();
                foreach ($courses as $course_id) {
                    $edit_link = get_edit_post_link($course_id);
                    $title = get_the_title($course_id);
                    if ($edit_link) {
                        $course_names[] = '<a href="' . esc_url($edit_link) . '">' . esc_html($title) . '</a>';
                    } else {
                        $course_names[] = esc_html($title);
                    }
                }
                return implode('<br>', $course_names);
            case 'actions':
                $courses = $item->courses ? json_decode($item->courses, true) : array();
                if (empty($courses)) {
                    return '';
                }
                $buttons = array();
                foreach ($courses as $course_id) {
                    $course_title = get_the_title($course_id);
                    // Resend invite button
                    $buttons[] = sprintf(
                        '<button type="button" class="button button-small tgcb-resend-invite" data-tg-id="%s" data-course-id="%d" title="%s - %s"><span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span></button>',
                        esc_attr($item->tg_id),
                        $course_id,
                        __('Resend Invite for', 'tg-course-bot-pro'),
                        esc_attr($course_title)
                    );
                    // Kick from channel button
                    $buttons[] = sprintf(
                        '<button type="button" class="button button-small tgcb-kick-from-channel" data-tg-id="%s" data-course-id="%d" title="%s - %s" style="color: #d63638;"><span class="dashicons dashicons-trash" style="vertical-align: middle;"></span></button>',
                        esc_attr($item->tg_id),
                        $course_id,
                        __('Remove from', 'tg-course-bot-pro'),
                        esc_attr($course_title)
                    );
                }
                return implode('&nbsp;', $buttons);


            case 'first_seen':
                return esc_html(mysql2date('Y-m-d H:i', $item->first_seen));
            case 'last_access':
                return esc_html(mysql2date('Y-m-d H:i', $item->last_access));
            case 'banned':
                if ($item->banned) {
                    return '<span class="tgcb-status-badge tgcb-status-error">🚫 Banned</span>';
                } else {
                    return '<span class="tgcb-status-badge tgcb-status-success">✅ Active</span>';
                }
            default:
                return '';
        }
    }

    /**
     * Prepare items
     */
    public function prepare_items()
    {
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        // Handle bulk actions
        $this->process_bulk_action();

        // Get filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;

        // Get data with filters
        $total_items = $this->get_filtered_count($status_filter, $course_filter);
        $offset = ($current_page - 1) * $per_page;

        $this->items = $this->get_filtered_students($offset, $per_page, $status_filter, $course_filter);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    /**
     * Get filtered students count
     */
    private function get_filtered_count($status = '', $course = 0)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        $where = array('1=1');

        if ($status === 'active') {
            $where[] = 'banned = 0';
        } elseif ($status === 'banned') {
            $where[] = 'banned = 1';
        } elseif ($status === 'no_courses') {
            $where[] = "(courses IS NULL OR courses = '[]' OR courses = '')";
        }

        if ($course > 0) {
            $where[] = $wpdb->prepare("courses LIKE %s", '%"' . $course . '"%');
        }

        $where_clause = implode(' AND ', $where);

        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where_clause");
    }

    /**
     * Get filtered students
     */
    private function get_filtered_students($offset, $limit, $status = '', $course = 0)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        $where = array('1=1');

        if ($status === 'active') {
            $where[] = 'banned = 0';
        } elseif ($status === 'banned') {
            $where[] = 'banned = 1';
        } elseif ($status === 'no_courses') {
            $where[] = "(courses IS NULL OR courses = '[]' OR courses = '')";
        }

        if ($course > 0) {
            $where[] = $wpdb->prepare("courses LIKE %s", '%"' . $course . '"%');
        }

        $where_clause = implode(' AND ', $where);

        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE $where_clause ORDER BY last_access DESC LIMIT $offset, $limit"
        );
    }

    /**
     * Display filter bar
     */
    public function extra_tablenav($which)
    {
        if ($which !== 'top') {
            return;
        }

        $current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $current_course = isset($_GET['course']) ? intval($_GET['course']) : 0;

        // Get all courses for dropdown
        $courses = get_posts(array(
            'post_type' => 'tgcb_course',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <div class="alignleft actions">
            <select name="status" id="filter-by-status">
                <option value=""><?php _e('All Statuses', 'tg-course-bot-pro'); ?></option>
                <option value="active" <?php selected($current_status, 'active'); ?>>
                    <?php _e('Active Only', 'tg-course-bot-pro'); ?></option>
                <option value="banned" <?php selected($current_status, 'banned'); ?>>
                    <?php _e('Banned Only', 'tg-course-bot-pro'); ?></option>
                <option value="no_courses" <?php selected($current_status, 'no_courses'); ?>>
                    <?php _e('No Courses', 'tg-course-bot-pro'); ?></option>
            </select>

            <select name="course" id="filter-by-course">
                <option value="0"><?php _e('All Courses', 'tg-course-bot-pro'); ?></option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo esc_attr($course->ID); ?>" <?php selected($current_course, $course->ID); ?>>
                        <?php echo esc_html($course->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php submit_button(__('Filter', 'tg-course-bot-pro'), 'secondary', 'filter_action', false); ?>

            <?php if ($current_status || $current_course): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=tgcb-students')); ?>" class="button">
                    <?php _e('Reset', 'tg-course-bot-pro'); ?>
                </a>
            <?php endif; ?>

            <a href="<?php echo esc_url(admin_url('admin.php?page=tgcb-students&action=export_csv')); ?>"
                class="button button-primary" style="margin-left: 10px;">
                📊 <?php _e('Export CSV', 'tg-course-bot-pro'); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action()
    {
        if (!isset($_POST['students']) || empty($_POST['students'])) {
            return;
        }

        $action = $this->current_action();
        $students = array_map('intval', $_POST['students']);

        if ($action === 'ban') {
            foreach ($students as $tg_id) {
                TGCB_Anti_Piracy::ban_user($tg_id, 'Banned by admin');
            }
            echo '<div class="notice notice-success"><p>' . __('Students banned successfully', 'tg-course-bot-pro') . '</p></div>';
        } elseif ($action === 'unban') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tgcb_students';
            foreach ($students as $tg_id) {
                $wpdb->update(
                    $table_name,
                    array('banned' => 0),
                    array('tg_id' => $tg_id)
                );
            }
            echo '<div class="notice notice-success"><p>' . __('Students unbanned successfully', 'tg-course-bot-pro') . '</p></div>';
        } elseif ($action === 'kick_all') {
            $telegram = new TGCB_Telegram_API();
            $kicked_count = 0;
            foreach ($students as $tg_id) {
                $student = TGCB_Database::get_student($tg_id);
                if ($student && $student->courses) {
                    $courses = json_decode($student->courses, true);
                    foreach ($courses as $course_id) {
                        $channel_id = get_post_meta($course_id, '_tgcb_channel_id', true);
                        if ($channel_id) {
                            $telegram->kick_chat_member($channel_id, $tg_id);
                        }
                    }
                    // Clear courses from database
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'tgcb_students';
                    $wpdb->update(
                        $table_name,
                        array('courses' => json_encode(array())),
                        array('tg_id' => $tg_id)
                    );
                    $kicked_count++;
                }
            }
            echo '<div class="notice notice-success"><p>' . sprintf(__('Removed %d students from all channels', 'tg-course-bot-pro'), $kicked_count) . '</p></div>';
        } elseif ($action === 'delete') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'tgcb_students';
            foreach ($students as $tg_id) {
                $wpdb->delete($table_name, array('tg_id' => $tg_id));
            }
            echo '<div class="notice notice-success"><p>' . sprintf(__('Deleted %d student records', 'tg-course-bot-pro'), count($students)) . '</p></div>';
        }
    }
}
