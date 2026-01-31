<?php
/**
 * Bot Settings Page
 * Manages bot configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Bot_Settings
{

    /**
     * Render settings page
     */
    public static function render_page()
    {
        $settings_updated = false;

        if (isset($_POST['tgcb_save_settings'])) {
            $settings_updated = self::save_settings();
        }

        $bot_token = get_option('tgcb_bot_token', '');
        $admin_id = get_option('tgcb_admin_id', '');
        $webhook_url = get_rest_url(null, 'tgcb/v1/webhook');

        // Get webhook info
        $webhook_info = self::get_webhook_info();

        ?>
        <div class="wrap tgcb-settings">
            <h1>
                <?php _e('Bot Settings', 'tg-course-bot-pro'); ?>
            </h1>

            <?php if ($settings_updated): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php _e('Settings saved successfully!', 'tg-course-bot-pro'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('tgcb_settings', 'tgcb_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="tgcb_bot_token">
                                <?php _e('Bot Token', 'tg-course-bot-pro'); ?>
                            </label>
                        </th>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <input type="password" id="tgcb_bot_token" name="tgcb_bot_token"
                                    value="<?php echo esc_attr($bot_token); ?>" class="regular-text" required />
                                <button type="button" class="button button-secondary tgcb-toggle-visibility"
                                    data-target="tgcb_bot_token" style="margin-left: 5px;">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                            <p class="description">
                                <?php _e('Get your bot token from', 'tg-course-bot-pro'); ?>
                                <a href="https://t.me/BotFather" target="_blank">@BotFather</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="tgcb_admin_id">
                                <?php _e('Admin Telegram ID', 'tg-course-bot-pro'); ?>
                            </label>
                        </th>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <input type="password" id="tgcb_admin_id" name="tgcb_admin_id"
                                    value="<?php echo esc_attr($admin_id); ?>" class="regular-text" required />
                                <button type="button" class="button button-secondary tgcb-toggle-visibility"
                                    data-target="tgcb_admin_id" style="margin-left: 5px;">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                            <p class="description">
                                <?php _e('Your Telegram user ID. Get it from', 'tg-course-bot-pro'); ?>
                                <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php _e('Webhook URL', 'tg-course-bot-pro'); ?>
                        </th>
                        <td>
                            <input type="text" value="<?php echo esc_url($webhook_url); ?>" class="large-text" readonly />
                            <p class="description">
                                <?php _e('Use this URL to set up your Telegram webhook', 'tg-course-bot-pro'); ?>
                            </p>
                            <button type="button" class="button button-secondary" id="tgcb-copy-webhook">
                                📋
                                <?php _e('Copy URL', 'tg-course-bot-pro'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="tgcb-setup-webhook">
                                🔗
                                <?php _e('Setup Webhook', 'tg-course-bot-pro'); ?>
                            </button>
                        </td>
                    </tr>

                    <?php if ($webhook_info): ?>
                        <tr>
                            <th scope="row">
                                <?php _e('Webhook Status', 'tg-course-bot-pro'); ?>
                            </th>
                            <td>
                                <div class="tgcb-webhook-status">
                                    <?php if ($webhook_info['url'] === $webhook_url): ?>
                                        <span class="tgcb-status-badge tgcb-status-success">✅
                                            <?php _e('Active', 'tg-course-bot-pro'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="tgcb-status-badge tgcb-status-error">❌
                                            <?php _e('Not Set', 'tg-course-bot-pro'); ?>
                                        </span>
                                    <?php endif; ?>
                                    <br>
                                    <small>
                                        <?php _e('Current URL:', 'tg-course-bot-pro'); ?>
                                        <?php echo esc_html($webhook_info['url'] ?: 'None'); ?>
                                    </small>
                                    <br>
                                    <small>
                                        <?php _e('Pending updates:', 'tg-course-bot-pro'); ?>
                                        <?php echo esc_html($webhook_info['pending_update_count']); ?>
                                    </small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <th scope="row">
                            <?php _e('Bot Info', 'tg-course-bot-pro'); ?>
                        </th>
                        <td>
                            <div id="tgcb-bot-info-display">
                                <?php
                                $bot_username = get_option('tgcb_bot_username');
                                if ($bot_username) {
                                    echo '<div style="margin-bottom: 10px;"><strong>' . __('Connected as:', 'tg-course-bot-pro') . '</strong> <a href="https://t.me/' . esc_attr(ltrim($bot_username, '@')) . '" target="_blank">' . esc_html($bot_username) . '</a></div>';
                                } else {
                                    echo '<div style="margin-bottom: 10px; color: #666;">' . __('Not connected or username not fetched.', 'tg-course-bot-pro') . '</div>';
                                }
                                ?>
                            </div>
                            <button type="button" class="button button-secondary" id="tgcb-check-bot-status">
                                🔄 <?php _e('Check Connection & Update Info', 'tg-course-bot-pro'); ?>
                            </button>
                            <span id="tgcb-bot-status-msg" style="margin-left: 10px;"></span>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="tgcb_save_settings" class="button button-primary"
                        value="<?php _e('Save Settings', 'tg-course-bot-pro'); ?>" />
                </p>
            </form>

            <hr>

            <h2>
                <?php _e('Setup Instructions', 'tg-course-bot-pro'); ?>
            </h2>
            <div class="tgcb-instructions">
                <ol>
                    <li>
                        <?php _e('Create a new bot using @BotFather on Telegram', 'tg-course-bot-pro'); ?>
                    </li>
                    <li>
                        <?php _e('Copy the bot token and paste it above', 'tg-course-bot-pro'); ?>
                    </li>
                    <li>
                        <?php _e('Get your Telegram ID from @userinfobot and paste it above', 'tg-course-bot-pro'); ?>
                    </li>
                    <li>
                        <?php _e('Click "Setup Webhook" button to configure the bot', 'tg-course-bot-pro'); ?>
                    </li>
                    <li>
                        <?php _e('Create your courses and add channel IDs', 'tg-course-bot-pro'); ?>
                    </li>
                    <li>
                        <?php _e('Make the bot an admin in your Telegram channels', 'tg-course-bot-pro'); ?>
                    </li>
                </ol>
            </div>

            <hr>

            <h2>
                <?php _e('Test Bot', 'tg-course-bot-pro'); ?>
            </h2>
            <div class="tgcb-test-section">
                <p>
                    <?php _e('Send a test message to verify bot is working:', 'tg-course-bot-pro'); ?>
                </p>
                <textarea id="tgcb-test-message" class="large-text" rows="4"
                    placeholder="<?php _e('Enter test message...', 'tg-course-bot-pro'); ?>"></textarea>
                <br><br>
                <button type="button" class="button button-secondary" id="tgcb-send-test">
                    📤
                    <?php _e('Send Test Message to Me', 'tg-course-bot-pro'); ?>
                </button>
                <div id="tgcb-test-result"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private static function save_settings()
    {
        if (!isset($_POST['tgcb_settings_nonce']) || !wp_verify_nonce($_POST['tgcb_settings_nonce'], 'tgcb_settings')) {
            return false;
        }

        if (!current_user_can('manage_options')) {
            return false;
        }

        $bot_token = sanitize_text_field($_POST['tgcb_bot_token']);
        $admin_id = sanitize_text_field($_POST['tgcb_admin_id']);

        update_option('tgcb_bot_token', $bot_token);
        update_option('tgcb_admin_id', $admin_id);

        return true;
    }


    /**
     * Get webhook info from Telegram
     */
    private static function get_webhook_info()
    {
        $telegram = new TGCB_Telegram_API();
        $info = $telegram->get_webhook_info();

        if ($info && isset($info->result)) {
            return array(
                'url' => $info->result->url ?? '',
                'pending_update_count' => $info->result->pending_update_count ?? 0,
                'last_error_date' => $info->result->last_error_date ?? null,
                'last_error_message' => $info->result->last_error_message ?? ''
            );
        }

        return null;
    }
}

// AJAX handlers for settings page
add_action('wp_ajax_tgcb_setup_webhook', 'tgcb_ajax_setup_webhook');
add_action('wp_ajax_tgcb_send_test', 'tgcb_ajax_send_test');
add_action('wp_ajax_tgcb_check_bot_status', 'tgcb_ajax_check_bot_status');

function tgcb_ajax_setup_webhook()
{
    check_ajax_referer('tgcb_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $webhook_url = get_rest_url(null, 'tgcb/v1/webhook');
    $telegram = new TGCB_Telegram_API();

    $result = $telegram->set_webhook($webhook_url);

    if ($result) {
        wp_send_json_success('Webhook set successfully!');
    } else {
        wp_send_json_error('Failed to set webhook');
    }
}

function tgcb_ajax_send_test()
{
    check_ajax_referer('tgcb_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $message = sanitize_textarea_field($_POST['message']);
    $admin_id = get_option('tgcb_admin_id');

    if (!$admin_id) {
        wp_send_json_error('Admin ID not set');
    }

    $telegram = new TGCB_Telegram_API();
    $result = $telegram->send_message($admin_id, $message);

    if ($result) {
        wp_send_json_success('Message sent!');
    } else {
        wp_send_json_error('Failed to send message');
    }
}

function tgcb_ajax_check_bot_status()
{
    check_ajax_referer('tgcb_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $telegram = new TGCB_Telegram_API();
    $info = $telegram->get_me();

    if ($info && isset($info->result)) {
        $username = '@' . $info->result->username;
        update_option('tgcb_bot_username', $username);
        wp_send_json_success($username);
    } else {
        wp_send_json_error('Failed to connect to bot. Check token.');
    }
}
