<?php
/**
 * Localization Page
 * Settings page for editing bot messages
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Localization_Page
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
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('update_option_tgcb_msg_menu_header', array($this, 'log_option_update'), 10, 3);
    }

    /**
     * Log when options are updated (for debugging)
     */
    public function log_option_update($old_value, $value, $option)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("TGCB Option Updated - {$option}");
            error_log("Old value: {$old_value}");
            error_log("New value: {$value}");

            // Force clear options cache
            wp_cache_delete($option, 'options');
        }
    }

    public function add_menu_page()
    {
        add_submenu_page(
            'tg-course-bot',
            __('Localization', 'tg-course-bot-pro'),
            __('Localization', 'tg-course-bot-pro'),
            'manage_options',
            'tgcb-localization',
            array($this, 'render_page')
        );
    }

    /**
     * Initialize default localization settings
     * Call this on plugin activation or when settings are missing
     */
    public static function initialize_defaults()
    {
        $defaults = array(
            // Language & Navigation
            'tgcb_msg_menu_header' => '👇 <b>Главное меню</b>',
            'tgcb_btn_all_courses' => '📚 Все курсы',
            'tgcb_btn_my_courses' => '👤 Мои курсы',
            'tgcb_btn_help' => '❓ Помощь',
            'tgcb_btn_support' => '👨💻 Поддержка',

            // Bot Messages
            'tgcb_msg_welcome' => "👋 Добро пожаловать, {name}!\n\nПожалуйста, выберите курс из списка:\n\nПосле выбора отправьте скриншот чека об оплате.",
            'tgcb_msg_no_courses' => 'На данный момент курсы недоступны.',
            'tgcb_msg_select_course' => "📸 Пожалуйста, отправьте скриншот чека об оплате для:\n{course}",
            'tgcb_msg_receipt_received' => "✅ Чек получен!\n\nВаш платеж проверяется администратором.\nВы получите ссылку-приглашение после подтверждения.",
            'tgcb_msg_approved' => "✅ <b>Оплата подтверждена!</b>\n\nВаш доступ к <b>{course}</b> открыт.\n\nНажмите на ссылку ниже, чтобы вступить:\n{link}\n\n⚠️ Эта ссылка одноразовая и действует 24 часа.",
            'tgcb_msg_rejected' => "❌ <b>Оплата отклонена</b>\n\nК сожалению, ваш платеж не подтвержден.\nПожалуйста, свяжитесь с поддержкой.",
            'tgcb_msg_banned' => '❌ Вы забанены в этом боте.',
            'tgcb_msg_already_joined' => '✅ У вас уже есть доступ к этому курсу!',
            'tgcb_msg_select_first' => '❌ Пожалуйста, сначала выберите курс через /start',

            // My Courses Messages
            'tgcb_msg_my_courses_empty' => "👤 <b>Мои курсы</b>\n\nВы еще не записались ни на один курс.\nВыберите '📚 Все курсы', чтобы начать!",
            'tgcb_msg_my_courses_header' => "👤 <b>Мои курсы</b>\n\nУ вас есть доступ к следующим курсам:",

            // Help & Support Messages
            'tgcb_msg_help' => "❓ <b>Как использовать бота:</b>\n\n1️⃣ Нажмите <b>📚 Все курсы</b>\n2️⃣ Выберите курс\n3️⃣ Отправьте скриншот оплаты\n4️⃣ Ждите подтверждения\n5️⃣ Получите ссылку!\n\nНажмите <b>👤 Мои курсы</b> для просмотра подписок.",
            'tgcb_msg_support' => "👨💻 <b>Поддержка</b>\n\nЕсли у вас есть вопросы, напишите администратору.",

            // Invite Link Messages
            'tgcb_msg_invite_header' => '🎟 <b>Новая ссылка-приглашение</b>',
            'tgcb_msg_invite_body' => 'Вот ваша новая ссылка-приглашение для <b>{course}</b>:',
            'tgcb_msg_invite_warning' => '⚠️ Эта ссылка одноразовая и действует 24 часа.'
        );

        foreach ($defaults as $option_name => $default_value) {
            // Only add if option doesn't exist
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value, '', 'yes');
            }
        }
    }

    public function register_settings()
    {
        // Base settings keys
        $settings = array(
            'tgcb_msg_welcome',
            'tgcb_msg_no_courses',
            'tgcb_msg_select_course',
            'tgcb_msg_receipt_received',
            'tgcb_msg_approved',
            'tgcb_msg_rejected',
            'tgcb_msg_banned',
            'tgcb_msg_already_joined',
            'tgcb_msg_select_first',
            'tgcb_msg_menu_header',
            'tgcb_btn_all_courses',
            'tgcb_btn_my_courses',
            'tgcb_btn_help',
            'tgcb_btn_support',
            'tgcb_msg_my_courses_empty',
            'tgcb_msg_my_courses_header',
            'tgcb_msg_help',
            'tgcb_msg_support',
            'tgcb_msg_invite_header',
            'tgcb_msg_invite_body',
            'tgcb_msg_invite_warning'
        );

        foreach ($settings as $setting) {
            register_setting('tgcb_localization_group', $setting, array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_telegram_html'),
                'default' => ''
            ));
        }
    }

    /**
     * Sanitize callback that allows Telegram HTML tags
     */
    public function sanitize_telegram_html($value)
    {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('TGCB Localization - Before sanitize: ' . $value);
        }

        // Allow only Telegram-supported HTML tags: b, i, u, s, code, pre, a
        $allowed_tags = array(
            'b' => array(),
            'i' => array(),
            'u' => array(),
            's' => array(),
            'code' => array(),
            'pre' => array(),
            'a' => array('href' => array())
        );

        $sanitized = wp_kses($value, $allowed_tags);

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('TGCB Localization - After sanitize: ' . $sanitized);
            error_log('TGCB Localization - Value changed: ' . ($value !== $sanitized ? 'YES' : 'NO'));
        }

        return $sanitized;
    }

    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1><?php _e('Bot Localization', 'tg-course-bot-pro'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('tgcb_localization_group');
                ?>

                <h2 class="title"><?php _e('Language & Navigation', 'tg-course-bot-pro'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label><?php _e('Menu Header', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_menu_header"
                                value="<?php echo esc_attr(get_option('tgcb_msg_menu_header', '👇 <b>Главное меню</b>')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Button: All Courses', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_btn_all_courses"
                                value="<?php echo esc_attr(get_option('tgcb_btn_all_courses', '📚 Все курсы')); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Button: My Courses', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_btn_my_courses"
                                value="<?php echo esc_attr(get_option('tgcb_btn_my_courses', '👤 Мои курсы')); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Button: Help', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_btn_help"
                                value="<?php echo esc_attr(get_option('tgcb_btn_help', '❓ Помощь')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Button: Support', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_btn_support"
                                value="<?php echo esc_attr(get_option('tgcb_btn_support', '👨‍💻 Поддержка')); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Standard Responses', 'tg-course-bot-pro'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label><?php _e('Welcome Message', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_welcome" rows="3"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_welcome', "👋 Добро пожаловать, {name}!\n\nПожалуйста, выберите курс из списка:\n\nПосле выбора отправьте скриншот чека об оплате.")); ?></textarea>
                            <p class="description">Use {name} for user's first name.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('No Courses', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_no_courses"
                                value="<?php echo esc_attr(get_option('tgcb_msg_no_courses', 'На данный момент курсы недоступны.')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Banned Message', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_banned"
                                value="<?php echo esc_attr(get_option('tgcb_msg_banned', '❌ Вы забанены в этом боте.')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Select Course First', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_select_first"
                                value="<?php echo esc_attr(get_option('tgcb_msg_select_first', '❌ Пожалуйста, сначала выберите курс через /start')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Already Joined', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_already_joined"
                                value="<?php echo esc_attr(get_option('tgcb_msg_already_joined', '✅ У вас уже есть доступ к этому курсу!')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Workflow Messages', 'tg-course-bot-pro'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label><?php _e('Request Receipt', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_select_course"
                                value="<?php echo esc_attr(get_option('tgcb_msg_select_course', "📸 Пожалуйста, отправьте скриншот чека об оплате для:\n{course}")); ?>"
                                class="large-text">
                            <p class="description">Use {course} for course name.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Receipt Received', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_receipt_received" rows="3"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_receipt_received', "✅ Чек получен!\n\nВаш платеж проверяется администратором.\nВы получите ссылку-приглашение после подтверждения.")); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Payment Approved', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_approved" rows="4"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_approved', "✅ <b>Оплата подтверждена!</b>\n\nВаш доступ к <b>{course}</b> открыт.\n\nНажмите на ссылку ниже, чтобы вступить:\n{link}\n\n⚠️ Эта ссылка одноразовая и действует 24 часа.")); ?></textarea>
                            <p class="description">Use {course} for course name, {link} for invite link.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Payment Rejected', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_rejected" rows="3"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_rejected', "❌ <b>Оплата отклонена</b>\n\nК сожалению, ваш платеж не подтвержден.\nПожалуйста, свяжитесь с поддержкой.")); ?></textarea>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Pages & Support', 'tg-course-bot-pro'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label><?php _e('My Courses Header', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_my_courses_header" rows="2"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_my_courses_header', "👤 <b>Мои курсы</b>\n\nУ вас есть доступ к следующим курсам:\n\n")); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('My Courses (Empty)', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_my_courses_empty" rows="2"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_my_courses_empty', "👤 <b>Мои курсы</b>\n\nВы еще не записались ни на один курс.\nВыберите '📚 Все курсы', чтобы начать!")); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Help Message', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_help" rows="6"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_help', "❓ <b>Как использовать бота:</b>\n\n1️⃣ Нажмите <b>📚 Все курсы</b>\n2️⃣ Выберите курс\n3️⃣ Отправьте скриншот оплаты\n4️⃣ Ждите подтверждения\n5️⃣ Получите ссылку!\n\nНажмите <b>👤 Мои курсы</b> для просмотра подписок.")); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Support Message', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <textarea name="tgcb_msg_support" rows="2"
                                class="large-text"><?php echo esc_textarea(get_option('tgcb_msg_support', "👨‍💻 <b>Поддержка</b>\n\nЕсли у вас есть вопросы, напишите администратору.")); ?></textarea>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Admin Actions', 'tg-course-bot-pro'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label><?php _e('Invite Link Header', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_invite_header"
                                value="<?php echo esc_attr(get_option('tgcb_msg_invite_header', '🎟 <b>Новая ссылка-приглашение</b>')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Invite Link Body', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_invite_body"
                                value="<?php echo esc_attr(get_option('tgcb_msg_invite_body', 'Вот ваша новая ссылка-приглашение для <b>{course}</b>:')); ?>"
                                class="large-text">
                            <p class="description">Use {course} for course name.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php _e('Invite Link Warning', 'tg-course-bot-pro'); ?></label></th>
                        <td>
                            <input type="text" name="tgcb_msg_invite_warning"
                                value="<?php echo esc_attr(get_option('tgcb_msg_invite_warning', '⚠️ Эта ссылка одноразовая и действует 24 часа.')); ?>"
                                class="large-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
