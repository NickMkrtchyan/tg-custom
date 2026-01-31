<?php
/**
 * Plugin Name: TG Course Bot PRO
 * Plugin URI: https://github.com/NickMkrtchyan/tg-custom
 * Description: Professional Telegram bot for managing course access with payment verification, invite links, and anti-piracy protection
 * Version: 1.1.1
 * Author: Nick Mkrtchyan
 * Author URI: https://github.com/NickMkrtchyan
 * License: GPL v2 or later
 * Text Domain: tg-course-bot-pro
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TGCB_VERSION', '1.1.1');
define('TGCB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TGCB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TGCB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Initialize auto-updates from GitHub (if library exists)
$puc_path = TGCB_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
if (file_exists($puc_path)) {
    require $puc_path;

    // Initialize update checker
    $myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/NickMkrtchyan/tg-custom/',
        __FILE__,
        'tg-custom'
    );

    // Set the branch to check for updates (main branch)
    $myUpdateChecker->setBranch('main');

    // Add authentication for private repository (if token is defined in wp-config.php)
    if (defined('TGCB_GITHUB_TOKEN')) {
        $myUpdateChecker->setAuthentication(TGCB_GITHUB_TOKEN);
    }
}


// Main Plugin Class
class TG_Course_Bot_Pro
{

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes()
    {
        // Core classes
        require_once TGCB_PLUGIN_DIR . 'includes/class-database.php';
        require_once TGCB_PLUGIN_DIR . 'includes/class-telegram-api.php';
        require_once TGCB_PLUGIN_DIR . 'includes/class-webhook-handler.php';
        require_once TGCB_PLUGIN_DIR . 'includes/class-invite-manager.php';
        require_once TGCB_PLUGIN_DIR . 'includes/class-welcome-handler.php';
        require_once TGCB_PLUGIN_DIR . 'includes/class-anti-piracy.php';

        // Custom Post Types
        require_once TGCB_PLUGIN_DIR . 'includes/class-cpt-courses.php';
        require_once TGCB_PLUGIN_DIR . 'includes/class-cpt-payments.php';

        // Admin classes
        require_once TGCB_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once TGCB_PLUGIN_DIR . 'admin/class-bot-settings.php';
        require_once TGCB_PLUGIN_DIR . 'admin/class-students-table.php';
        require_once TGCB_PLUGIN_DIR . 'admin/class-localization-page.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_filter('plugin_action_links_' . TGCB_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        TGCB_Database::create_tables();
        TGCB_CPT_Courses::register();
        TGCB_CPT_Payments::register();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Load text domain
        load_plugin_textdomain('tg-course-bot-pro', false, dirname(TGCB_PLUGIN_BASENAME) . '/languages');

        // Self-healing: Check if piracy table exists and create if missing
        global $wpdb;
        $table_name = $wpdb->prefix . 'tgcb_piracy_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            TGCB_Database::create_tables();
        }

        // Initialize components
        TGCB_CPT_Courses::get_instance();
        TGCB_CPT_Payments::get_instance();
        TGCB_Webhook_Handler::get_instance();
        TGCB_Admin_Menu::get_instance();
        TGCB_Localization_Page::get_instance();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook)
    {
        $screen = get_current_screen();

        // Check if we are on our plugin pages OR our custom post types
        $is_plugin_page = strpos($hook, 'tg-course-bot') !== false;
        $is_cpt_page = isset($screen->post_type) && in_array($screen->post_type, array('tgcb_payment', 'tgcb_course'));

        if (!$is_plugin_page && !$is_cpt_page) {
            return;
        }

        wp_enqueue_style('tgcb-admin-style', TGCB_PLUGIN_URL . 'assets/css/admin-style.css', array(), TGCB_VERSION);
        wp_enqueue_script('tgcb-admin-script', TGCB_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), TGCB_VERSION, true);

        wp_localize_script('tgcb-admin-script', 'tgcbAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tgcb_admin_nonce')
        ));
    }

    /**
     * Add custom action links to plugin page
     */
    public function add_action_links($links)
    {
        $check_updates_url = admin_url('plugins.php?puc_check_for_updates=1&puc_slug=tg-custom');
        $check_updates_url = wp_nonce_url($check_updates_url, 'puc_check_for_updates');
        $check_updates_link = '<a href="' . esc_url($check_updates_url) . '">' . __('Check for updates', 'tg-course-bot-pro') . '</a>';
        array_unshift($links, $check_updates_link);
        return $links;
    }
}

// Initialize plugin
function tgcb_init()
{
    return TG_Course_Bot_Pro::get_instance();
}

// Start the plugin
tgcb_init();
