<?php
/**
 * Anti-Piracy Module
 * Prevents unauthorized access and link sharing
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Anti_Piracy
{

    /**
     * Check if user should have access
     */
    public static function verify_access($user_id, $chat_id)
    {
        // Find course by chat_id
        $courses = get_posts(array(
            'post_type' => 'tgcb_course',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tgcb_channel_id',
                    'value' => $chat_id
                )
            )
        ));

        if (empty($courses)) {
            return false;
        }

        $course_id = $courses[0]->ID;

        // Check if student has legitimate access
        return TGCB_Database::has_course_access($user_id, $course_id);
    }

    /**
     * Handle user joining with potentially stolen link
     */
    public static function handle_unauthorized_join($user_id, $chat_id, $invite_link)
    {
        // Check who the link was created for
        $authorized_user = self::get_authorized_user($invite_link);

        if ($authorized_user && $authorized_user != $user_id) {
            // This is piracy!
            self::ban_user($user_id, 'Used stolen invite link');

            // Kick from channel
            $telegram = new TGCB_Telegram_API();
            $telegram->ban_chat_member($chat_id, $user_id);

            // Notify admin
            self::notify_admin_piracy($user_id, $authorized_user, $invite_link);

            // Log the incident
            self::log_piracy_incident($user_id, $authorized_user, $invite_link);

            return true; // Piracy detected
        }

        return false; // Legitimate join
    }

    /**
     * Get the user ID who should use this invite link
     */
    private static function get_authorized_user($invite_link)
    {
        // Find payment with this invite link
        $payments = get_posts(array(
            'post_type' => 'tgcb_payment',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tgcb_invite_link',
                    'value' => $invite_link
                )
            )
        ));

        if (empty($payments)) {
            return null;
        }

        return get_post_meta($payments[0]->ID, '_tgcb_tg_id', true);
    }

    /**
     * Ban user
     */
    public static function ban_user($user_id, $reason = '')
    {
        TGCB_Database::ban_student($user_id, $reason);

        // Remove from all courses
        $student = TGCB_Database::get_student_by_tg_id($user_id);
        if ($student && $student->courses) {
            $courses = json_decode($student->courses, true);
            $telegram = new TGCB_Telegram_API();

            foreach ($courses as $course_id) {
                $channel_id = get_post_meta($course_id, '_tgcb_channel_id', true);
                if ($channel_id) {
                    $telegram->ban_chat_member($channel_id, $user_id);
                }
            }
        }
    }

    /**
     * Handle user leaving a course
     */
    public static function handle_user_left($user_id, $chat_id)
    {
        // Find course
        $courses = get_posts(array(
            'post_type' => 'tgcb_course',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_tgcb_channel_id',
                    'value' => $chat_id
                )
            )
        ));

        if (empty($courses)) {
            return;
        }

        $course_id = $courses[0]->ID;

        // Log the departure
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_left_log';

        // Create table if not exists
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tg_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            left_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY tg_id (tg_id),
            KEY course_id (course_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert log
        $wpdb->insert(
            $table_name,
            array(
                'tg_id' => $user_id,
                'course_id' => $course_id,
                'left_at' => current_time('mysql')
            )
        );

        // Remove course from student
        $student = TGCB_Database::get_student_by_tg_id($user_id);
        if ($student && $student->courses) {
            $courses_array = json_decode($student->courses, true);
            $key = array_search($course_id, $courses_array);
            if ($key !== false) {
                unset($courses_array[$key]);

                global $wpdb;
                $table_name = $wpdb->prefix . 'tgcb_students';
                $wpdb->update(
                    $table_name,
                    array('courses' => json_encode(array_values($courses_array))),
                    array('tg_id' => $user_id)
                );
            }
        }
    }

    /**
     * Notify admin about piracy attempt
     */
    private static function notify_admin_piracy($pirate_id, $victim_id, $invite_link)
    {
        $admin_id = get_option('tgcb_admin_id');
        if (!$admin_id) {
            return;
        }

        $telegram = new TGCB_Telegram_API();

        $message = "🚨 <b>PIRACY DETECTED!</b>\n\n";
        $message .= "👤 Unauthorized user: {$pirate_id}\n";
        $message .= "🎯 Victim user: {$victim_id}\n";
        $message .= "🔗 Stolen link: {$invite_link}\n\n";
        $message .= "The unauthorized user has been banned and kicked from all courses.";

        $telegram->send_message($admin_id, $message);
    }

    /**
     * Log piracy incident
     */
    private static function log_piracy_incident($pirate_id, $victim_id, $invite_link)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_piracy_log';

        // Create table if not exists
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pirate_tg_id bigint(20) NOT NULL,
            victim_tg_id bigint(20) NOT NULL,
            invite_link text NOT NULL,
            detected_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY pirate_tg_id (pirate_tg_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert log
        $wpdb->insert(
            $table_name,
            array(
                'pirate_tg_id' => $pirate_id,
                'victim_tg_id' => $victim_id,
                'invite_link' => $invite_link,
                'detected_at' => current_time('mysql')
            )
        );
    }

    /**
     * Get piracy statistics
     */
    public static function get_piracy_stats()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_piracy_log';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(detected_at) = CURDATE()");
        $week = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE detected_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

        return array(
            'total' => $total,
            'today' => $today,
            'week' => $week
        );
    }
}
