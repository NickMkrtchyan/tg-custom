<?php
/**
 * Invite Link Manager
 * Creates and manages one-time invite links
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Invite_Manager
{

    /**
     * Create one-time invite link for course
     */
    public static function create_invite_link($course_id)
    {
        $channel_id = get_post_meta($course_id, '_tgcb_channel_id', true);
        $expire_hours = get_post_meta($course_id, '_tgcb_link_expire_hours', true) ?: 24;

        if (!$channel_id) {
            error_log('TGCB: No channel ID for course ' . $course_id);
            return false;
        }

        $telegram = new TGCB_Telegram_API();

        // Set expiration time
        $expire_date = time() + ($expire_hours * 3600);

        // Create invite link with member limit = 1 (one-time use)
        $invite_link = $telegram->create_chat_invite_link($channel_id, 1, $expire_date);

        if ($invite_link) {
            // Log the invite link
            self::log_invite_link($course_id, $invite_link, $expire_date);
            return $invite_link;
        }

        return false;
    }

    /**
     * Log invite link creation
     */
    private static function log_invite_link($course_id, $invite_link, $expire_date)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_invite_links';

        // Create table if not exists
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id bigint(20) NOT NULL,
            invite_link text NOT NULL,
            created_at datetime NOT NULL,
            expire_at datetime NOT NULL,
            used tinyint(1) NOT NULL DEFAULT 0,
            used_by bigint(20) DEFAULT NULL,
            used_at datetime DEFAULT NULL,
            revoked tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY course_id (course_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert log
        $wpdb->insert(
            $table_name,
            array(
                'course_id' => $course_id,
                'invite_link' => $invite_link,
                'created_at' => current_time('mysql'),
                'expire_at' => date('Y-m-d H:i:s', $expire_date)
            )
        );
    }

    /**
     * Mark invite link as used
     */
    public static function mark_as_used($invite_link, $user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_invite_links';

        $wpdb->update(
            $table_name,
            array(
                'used' => 1,
                'used_by' => $user_id,
                'used_at' => current_time('mysql')
            ),
            array('invite_link' => $invite_link)
        );
    }

    /**
     * Revoke invite link
     */
    public static function revoke_invite_link($invite_link)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_invite_links';

        // Get course info
        $link_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE invite_link = %s",
            $invite_link
        ));

        if (!$link_data) {
            return false;
        }

        $course_id = $link_data->course_id;
        $channel_id = get_post_meta($course_id, '_tgcb_channel_id', true);

        // Revoke via Telegram API
        $telegram = new TGCB_Telegram_API();
        $result = $telegram->revoke_chat_invite_link($channel_id, $invite_link);

        if ($result) {
            // Mark as revoked in database
            $wpdb->update(
                $table_name,
                array('revoked' => 1),
                array('invite_link' => $invite_link)
            );

            return true;
        }

        return false;
    }

    /**
     * Check if invite link was used by someone else
     */
    public static function check_unauthorized_use($invite_link, $expected_user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_invite_links';

        $link_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE invite_link = %s",
            $invite_link
        ));

        if (!$link_data) {
            return false;
        }

        // If link was used by someone else
        if ($link_data->used && $link_data->used_by != $expected_user_id) {
            return $link_data->used_by;
        }

        return false;
    }

    /**
     * Get invite link stats
     */
    public static function get_stats($course_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_invite_links';

        if ($course_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE course_id = %d ORDER BY created_at DESC",
                $course_id
            ));
        }

        return $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100"
        );
    }

    /**
     * Clean up expired links
     */
    public static function cleanup_expired_links()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_invite_links';

        // Get expired links that haven't been revoked
        $expired_links = $wpdb->get_results(
            "SELECT * FROM $table_name 
             WHERE expire_at < NOW() 
             AND revoked = 0 
             AND used = 0"
        );

        foreach ($expired_links as $link) {
            self::revoke_invite_link($link->invite_link);
        }
    }
}

// Schedule cleanup task
if (!wp_next_scheduled('tgcb_cleanup_expired_links')) {
    wp_schedule_event(time(), 'hourly', 'tgcb_cleanup_expired_links');
}

add_action('tgcb_cleanup_expired_links', array('TGCB_Invite_Manager', 'cleanup_expired_links'));
