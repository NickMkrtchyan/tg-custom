<?php
/**
 * Database Manager
 * Creates and manages custom database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Database
{

    /**
     * Create custom database tables
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'tgcb_students';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tg_id bigint(20) NOT NULL,
            username varchar(255) DEFAULT NULL,
            first_name varchar(255) DEFAULT NULL,
            last_name varchar(255) DEFAULT NULL,
            first_seen datetime NOT NULL,
            last_access datetime NOT NULL,
            courses text DEFAULT NULL,
            language varchar(5) DEFAULT NULL,
            banned tinyint(1) NOT NULL DEFAULT 0,
            notes text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tg_id (tg_id),
            KEY banned (banned)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Update version
        update_option('tgcb_db_version', TGCB_VERSION);

        // Piracy Log Table
        $table_piracy = $wpdb->prefix . 'tgcb_piracy_log';

        $sql_piracy = "CREATE TABLE IF NOT EXISTS $table_piracy (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            chat_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            details text DEFAULT NULL,
            detected_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY detected_at (detected_at)
        ) $charset_collate;";

        dbDelta($sql_piracy);
    }

    /**
     * Get student by Telegram ID
     */
    public static function get_student_by_tg_id($tg_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE tg_id = %s",
            $tg_id
        ));
    }

    /**
     * Alias for get_student_by_tg_id
     */
    public static function get_student($tg_id)
    {
        return self::get_student_by_tg_id($tg_id);
    }

    /**
     * Update student language
     */
    public static function update_student_language($tg_id, $language)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        $wpdb->update(
            $table_name,
            array('language' => $language),
            array('tg_id' => $tg_id),
            array('%s'), // format for data
            array('%s')  // format for where
        );
    }

    /**
     * Insert or update student
     */
    public static function upsert_student($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        $existing = self::get_student_by_tg_id($data['tg_id']);

        if ($existing) {
            // Update
            $wpdb->update(
                $table_name,
                $data,
                array('tg_id' => $data['tg_id']),
                null,       // format for data (auto-detect)
                array('%s') // format for where
            );
            return $existing->id;
        } else {
            // Insert
            $data['first_seen'] = current_time('mysql');
            $data['last_access'] = current_time('mysql');

            $wpdb->insert($table_name, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Add course to student
     */
    public static function add_course_to_student($tg_id, $course_id)
    {
        $student = self::get_student_by_tg_id($tg_id);

        if (!$student) {
            return false;
        }

        $courses = $student->courses ? json_decode($student->courses, true) : array();

        if (!in_array($course_id, $courses)) {
            $courses[] = (string) $course_id;

            global $wpdb;
            $table_name = $wpdb->prefix . 'tgcb_students';

            $wpdb->update(
                $table_name,
                array(
                    'courses' => json_encode($courses),
                    'last_access' => current_time('mysql')
                ),
                array('tg_id' => $tg_id),
                array('%s', '%s'), // format for data
                array('%s')        // format for where
            );
        }

        return true;
    }

    /**
     * Check if student has access to course
     */
    public static function has_course_access($tg_id, $course_id)
    {
        $student = self::get_student_by_tg_id($tg_id);

        if (!$student || $student->banned) {
            return false;
        }

        $courses = $student->courses ? json_decode($student->courses, true) : array();
        return in_array($course_id, $courses);
    }

    /**
     * Ban student
     */
    public static function ban_student($tg_id, $reason = '')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        return $wpdb->update(
            $table_name,
            array(
                'banned' => 1,
                'notes' => $reason
            ),
            array('tg_id' => $tg_id)
        );
    }

    /**
     * Get all students
     */
    public static function get_all_students($offset = 0, $limit = 20)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY last_access DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    /**
     * Get total students count
     */
    public static function get_students_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_students';

        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
}
