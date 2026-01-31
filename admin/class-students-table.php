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
            'unban' => __('Unban', 'tg-course-bot-pro')
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
                    $buttons[] = sprintf(
                        '<button type="button" class="button button-small tgcb-resend-invite" data-tg-id="%s" data-course-id="%d" title="%s - %s"><span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span></button>',
                        esc_attr($item->tg_id),
                        $course_id,
                        __('Resend Invite for', 'tg-course-bot-pro'),
                        esc_attr(get_the_title($course_id))
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

        // Get data
        $total_items = TGCB_Database::get_students_count();
        $offset = ($current_page - 1) * $per_page;

        $this->items = TGCB_Database::get_all_students($offset, $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
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
        }
    }
}
