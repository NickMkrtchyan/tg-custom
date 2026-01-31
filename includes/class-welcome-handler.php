<?php
/**
 * Welcome Message Handler
 * Sends welcome messages when students join courses
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Welcome_Handler
{

    /**
     * Send welcome message to new member
     */
    public static function send_welcome_message($user_id, $chat_id)
    {
        // Find which course this chat belongs to
        $courses = get_posts(array(
            'post_type' => 'tgcb_course',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        $course_id = null;
        foreach ($courses as $course) {
            $channel_id = get_post_meta($course->ID, '_tgcb_channel_id', true);
            if ($channel_id == $chat_id) {
                $course_id = $course->ID;
                break;
            }
        }

        if (!$course_id) {
            error_log('TGCB: Could not find course for chat ' . $chat_id);
            return;
        }

        // Add course to student
        TGCB_Database::add_course_to_student($user_id, $course_id);

        // Update payment status to completed
        self::mark_payment_completed($user_id, $course_id);

        // Get welcome message
        $welcome_message = get_post_meta($course_id, '_tgcb_welcome_message', true);

        if (!$welcome_message) {
            $welcome_message = "🎓 <b>Welcome to " . get_the_title($course_id) . "!</b>\n\n";
            $welcome_message .= "Thank you for joining. Enjoy your learning experience!";
        }

        // Send welcome message
        $telegram = new TGCB_Telegram_API();
        $telegram->send_message($user_id, $welcome_message);

        // Log the join
        error_log("TGCB: User {$user_id} joined course {$course_id}");
    }

    /**
     * Mark payment as completed
     */
    private static function mark_payment_completed($user_id, $course_id)
    {
        // Find the approved payment for this user and course
        $payments = get_posts(array(
            'post_type' => 'tgcb_payment',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_tgcb_tg_id',
                    'value' => $user_id
                ),
                array(
                    'key' => '_tgcb_course_id',
                    'value' => $course_id
                ),
                array(
                    'key' => '_tgcb_status',
                    'value' => 'approved'
                )
            )
        ));

        if (!empty($payments)) {
            $payment_id = $payments[0]->ID;
            update_post_meta($payment_id, '_tgcb_status', 'completed');
            update_post_meta($payment_id, '_tgcb_joined_date', current_time('mysql'));

            // Mark invite link as used
            $invite_link = get_post_meta($payment_id, '_tgcb_invite_link', true);
            if ($invite_link) {
                TGCB_Invite_Manager::mark_as_used($invite_link, $user_id);

                // Revoke the invite link
                TGCB_Invite_Manager::revoke_invite_link($invite_link);
            }
        }
    }

    /**
     * Send custom message to student
     */
    public static function send_custom_message($user_id, $message)
    {
        $telegram = new TGCB_Telegram_API();
        return $telegram->send_message($user_id, $message);
    }

    /**
     * Broadcast message to all students in a course
     */
    public static function broadcast_to_course($course_id, $message)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        // Get all students with this course
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE courses LIKE %s AND banned = 0",
            '%"' . $course_id . '"%'
        ));

        $telegram = new TGCB_Telegram_API();
        $sent_count = 0;

        foreach ($students as $student) {
            if ($telegram->send_message($student->tg_id, $message)) {
                $sent_count++;
            }

            // Sleep to avoid rate limits
            usleep(100000); // 100ms delay
        }

        return $sent_count;
    }

    /**
     * Broadcast message to all students
     */
    public static function broadcast_to_all($message)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        // Get all non-banned students
        $students = $wpdb->get_results("SELECT * FROM $table_name WHERE banned = 0");

        $telegram = new TGCB_Telegram_API();
        $sent_count = 0;

        foreach ($students as $student) {
            if ($telegram->send_message($student->tg_id, $message)) {
                $sent_count++;
            }

            // Sleep to avoid rate limits
            usleep(100000); // 100ms delay
        }

        return $sent_count;
    }
}
