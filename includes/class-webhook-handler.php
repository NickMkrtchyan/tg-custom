<?php
/**
 * Webhook Handler
 * Processes incoming Telegram updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Webhook_Handler
{

    private static $instance = null;
    private $telegram;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->telegram = new TGCB_Telegram_API();

        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_ajax_tgcb_approve_payment', array($this, 'ajax_approve_payment'));
        add_action('wp_ajax_tgcb_reject_payment', array($this, 'ajax_reject_payment'));
        add_action('wp_ajax_tgcb_resend_invite', array($this, 'ajax_resend_invite'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes()
    {
        register_rest_route('tgcb/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Handle incoming webhook
     */
    public function handle_webhook($request)
    {
        // Use JSON_BIGINT_AS_STRING to handle large integer IDs on 32-bit systems
        $update = json_decode($request->get_body(), false, 512, JSON_BIGINT_AS_STRING);

        if (!$update) {
            return new WP_REST_Response(array('error' => 'Invalid JSON'), 400);
        }

        // Log update for debugging
        error_log('TGCB Webhook: ' . print_r($update, true));

        // Handle different update types
        if (isset($update->message)) {
            $this->handle_message($update->message);
        } elseif (isset($update->callback_query)) {
            $this->handle_callback_query($update->callback_query);
        } elseif (isset($update->chat_member)) {
            $this->handle_chat_member_update($update->chat_member);
        }

        return new WP_REST_Response(array('ok' => true), 200);
    }

    /**
     * Handle text/photo messages
     */
    private function handle_message($message)
    {
        $chat_id = $message->chat->id;
        $user = $message->from;

        // Save or update student in database
        TGCB_Database::upsert_student(array(
            'tg_id' => $user->id,
            'username' => isset($user->username) ? $user->username : null,
            'first_name' => isset($user->first_name) ? $user->first_name : null,
            'last_name' => isset($user->last_name) ? $user->last_name : null,
            'last_access' => current_time('mysql')
        ));

        // Check if user is banned
        $student = TGCB_Database::get_student_by_tg_id($user->id);
        if ($student && $student->banned) {
            $msg = get_option('tgcb_msg_banned', '❌ Вы забанены в этом боте.');
            $this->telegram->send_message($chat_id, $msg);
            return;
        }

        // Handle commands
        if (isset($message->text)) {
            $text = $message->text;

            if ($text === '/start') {
                $this->handle_start_command($chat_id, $user);
                return;
            }
        }

        // Handle photo (receipt)
        if (isset($message->photo)) {
            $this->handle_receipt_photo($message);
            return;
        }

        // Handle text commands/buttons
        if (isset($message->text)) {
            $btn_all = get_option('tgcb_btn_all_courses', '📚 Все курсы');
            $btn_my = get_option('tgcb_btn_my_courses', '👤 Мои курсы');
            $btn_help = get_option('tgcb_btn_help', '❓ Помощь');
            $btn_support = get_option('tgcb_btn_support', '👨‍💻 Поддержка');

            if ($message->text === $btn_all) {
                $this->handle_start_command($chat_id, $user);
            } elseif ($message->text === $btn_my) {
                $this->handle_my_courses_command($chat_id, $user);
            } elseif ($message->text === $btn_help || $message->text === '/help') {
                $this->handle_help_command($chat_id, $user->id);
            } elseif ($message->text === $btn_support) {
                $msg = get_option('tgcb_msg_support', "👨‍💻 <b>Поддержка</b>\n\nЕсли у вас есть вопросы, напишите администратору.");
                $this->telegram->send_message($chat_id, $msg);
            }
        }
    }

    /**
     * Handle /start command
     */
    private function handle_start_command($chat_id, $user)
    {
        $courses = get_posts(array(
            'post_type' => 'tgcb_course',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));

        if (empty($courses)) {
            $msg = get_option('tgcb_msg_no_courses', 'На данный момент курсы недоступны.');
            $this->telegram->send_message($chat_id, $msg);
            return;
        }

        $keyboard = array();
        foreach ($courses as $course) {
            $price = get_post_meta($course->ID, '_tgcb_price', true);
            $currency = get_post_meta($course->ID, '_tgcb_currency', true) ?: 'USD';

            $button_text = $course->post_title;
            if ($price) {
                $button_text .= ' (' . $price . ' ' . $currency . ')';
            }

            $keyboard[] = array(
                array(
                    'text' => $button_text,
                    'callback_data' => 'course_' . $course->ID
                )
            );
        }

        $reply_markup = array(
            'inline_keyboard' => $keyboard
        );

        $welcome_text = get_option('tgcb_msg_welcome', "👋 Добро пожаловать, {name}!\n\nПожалуйста, выберите курс из списка:\n\nПосле выбора отправьте скриншот чека об оплате.");
        $welcome_text = str_replace('{name}', $user->first_name, $welcome_text);

        // Persistent Menu Keyboard
        $btn_all = get_option('tgcb_btn_all_courses', '📚 Все курсы');
        $btn_my = get_option('tgcb_btn_my_courses', '👤 Мои курсы');
        $btn_help = get_option('tgcb_btn_help', '❓ Помощь');
        $btn_support = get_option('tgcb_btn_support', '👨‍💻 Поддержка');

        $menu_keyboard = array(
            'keyboard' => array(
                array($btn_all, $btn_my),
                array($btn_help, $btn_support)
            ),
            'resize_keyboard' => true,
            'persistent' => true
        );

        // Send menu first (if not already set)
        $intro = get_option('tgcb_msg_menu_header', '👇 <b>Главное меню</b>');
        $this->telegram->send_message($chat_id, $intro, $menu_keyboard);

        // Then send course buttons
        $this->telegram->send_message($chat_id, $welcome_text, $reply_markup);
    }

    /**
     * Handle "My Courses" command
     */
    private function handle_my_courses_command($chat_id, $user)
    {
        $student = TGCB_Database::get_student_by_tg_id($user->id);

        if (!$student || empty($student->courses)) {
            $msg = get_option('tgcb_msg_my_courses_empty', "👤 <b>Мои курсы</b>\n\nВы еще не записались ни на один курс.\nВыберите '📚 Все курсы', чтобы начать!");
            $this->telegram->send_message($chat_id, $msg);
            return;
        }

        $course_ids = json_decode($student->courses, true);
        if (empty($course_ids)) {
            $msg = get_option('tgcb_msg_my_courses_empty', "👤 <b>Мои курсы</b>\n\nВы еще не записались ни на один курс.\nВыберите '📚 Все курсы', чтобы начать!");
            $this->telegram->send_message($chat_id, $msg);
            return;
        }

        $message = get_option('tgcb_msg_my_courses_header', "👤 <b>Мои курсы</b>\n\nУ вас есть доступ к следующим курсам:\n\n");
        $buttons = array();

        foreach ($course_ids as $course_id) {
            $course = get_post($course_id);
            if ($course && $course->post_status === 'publish') {
                $message .= "✅ <b>" . $course->post_title . "</b>\n";

                // Add button for this course
                $channel_id = get_post_meta($course_id, '_tgcb_channel_id', true);
                if ($channel_id) {
                    $link = '';
                    if (strpos($channel_id, '-100') === 0) {
                        // Private channel with -100 prefix: https://t.me/c/1234567890/1
                        // Remove -100 (first 4 chars)
                        $clean_id = substr($channel_id, 4);
                        $link = "https://t.me/c/{$clean_id}/1";
                    } elseif (strpos($channel_id, '@') === 0) {
                        // Public channel with @username: https://t.me/username
                        $username = substr($channel_id, 1);
                        $link = "https://t.me/{$username}";
                    } else {
                        // Fallback, maybe just an ID or username without @
                        $link = "https://t.me/" . $channel_id;
                    }

                    $buttons[] = array(
                        array(
                            'text' => '➡️ ' . $course->post_title,
                            'url' => $link
                        )
                    );
                }
            }
        }

        $keyboard = null;
        if (!empty($buttons)) {
            $keyboard = json_encode(array(
                'inline_keyboard' => $buttons
            ));
        }

        $this->telegram->send_message($chat_id, $message, $keyboard);
    }

    /**
     * Handle Help command
     */
    private function handle_help_command($chat_id, $user_id)
    {
        $message = get_option('tgcb_msg_help', "❓ <b>Как использовать бота:</b>\n\n1️⃣ Нажмите <b>📚 Все курсы</b>\n2️⃣ Выберите курс\n3️⃣ Отправьте скриншот оплаты\n4️⃣ Ждите подтверждения\n5️⃣ Получите ссылку!\n\nНажмите <b>👤 Мои курсы</b> для просмотра подписок.");
        $this->telegram->send_message($chat_id, $message);
    }

    /**
     * Handle receipt photo
     */
    private function handle_receipt_photo($message)
    {
        $chat_id = $message->chat->id;
        $user = $message->from;
        $photo = end($message->photo); // Get largest photo

        // Get selected course from transient
        $selected_course = get_transient('tgcb_selected_course_' . $user->id);

        if (!$selected_course) {
            $msg = get_option('tgcb_msg_select_first', '❌ Пожалуйста, сначала выберите курс через /start');
            $this->telegram->send_message($chat_id, $msg);
            return;
        }

        // Check if already has access
        if (TGCB_Database::has_course_access($user->id, $selected_course)) {
            $msg = get_option('tgcb_msg_already_joined', '✅ У вас уже есть доступ к этому курсу!');
            $this->telegram->send_message($chat_id, $msg);
            return;
        }

        // Get photo URL
        $file_id = $photo->file_id;
        $photo_url = $this->get_file_url($file_id);

        // Create payment record
        $payment_id = wp_insert_post(array(
            'post_type' => 'tgcb_payment',
            'post_status' => 'publish',
            'post_title' => $user->first_name . ' ' . ($user->last_name ?? '') . ' - ' . get_the_title($selected_course)
        ));

        if ($payment_id) {
            update_post_meta($payment_id, '_tgcb_tg_id', $user->id);
            update_post_meta($payment_id, '_tgcb_username', $user->username ?? '');
            update_post_meta($payment_id, '_tgcb_first_name', $user->first_name ?? '');
            update_post_meta($payment_id, '_tgcb_last_name', $user->last_name ?? '');
            update_post_meta($payment_id, '_tgcb_course_id', $selected_course);
            update_post_meta($payment_id, '_tgcb_receipt_photo', $photo_url);
            update_post_meta($payment_id, '_tgcb_status', 'pending');

            // Send to admin for approval
            $this->notify_admin_new_payment($payment_id);

            $msg = get_option('tgcb_msg_receipt_received', "✅ Чек получен!\n\nВаш платеж проверяется администратором.\nВы получите ссылку-приглашение после подтверждения.");
            $this->telegram->send_message($chat_id, $msg);
        }

        // Clear selected course
        delete_transient('tgcb_selected_course_' . $user->id);
    }

    /**
     * Handle callback queries (button clicks)
     */
    private function handle_callback_query($callback)
    {
        $chat_id = $callback->message->chat->id;
        $user = $callback->from;
        $data = $callback->data;

        // Course selection
        if (strpos($data, 'course_') === 0) {
            $course_id = intval(str_replace('course_', '', $data));

            // Save selected course (expires in 1 hour)
            set_transient('tgcb_selected_course_' . $user->id, $course_id, HOUR_IN_SECONDS);

            $this->telegram->answer_callback_query($callback->id, 'Course selected!');

            $msg = get_option('tgcb_msg_select_course', "📸 Пожалуйста, отправьте скриншот чека об оплате для:\n{course}");
            $msg = str_replace('{course}', '<b>' . get_the_title($course_id) . '</b>', $msg);

            $this->telegram->send_message($chat_id, $msg);
        }
        // Admin approval
        elseif (strpos($data, 'approve_') === 0) {
            $payment_id = intval(str_replace('approve_', '', $data));
            $this->process_approval($payment_id, $callback);
        }
        // Admin rejection
        elseif (strpos($data, 'reject_') === 0) {
            $payment_id = intval(str_replace('reject_', '', $data));
            $this->process_rejection($payment_id, $callback);
        }
    }

    /**
     * Handle chat member updates
     */
    private function handle_chat_member_update($chat_member)
    {
        $user = $chat_member->new_chat_member->user;
        $status = $chat_member->new_chat_member->status;
        $chat_id = $chat_member->chat->id;

        // User joined
        if ($status === 'member' || $status === 'administrator') {
            TGCB_Welcome_Handler::send_welcome_message($user->id, $chat_id);
        }
        // User left
        elseif ($status === 'left' || $status === 'kicked') {
            TGCB_Anti_Piracy::handle_user_left($user->id, $chat_id);
        }
    }

    /**
     * Notify admin about new payment
     */
    private function notify_admin_new_payment($payment_id)
    {
        $admin_id = get_option('tgcb_admin_id');
        if (!$admin_id) {
            return;
        }

        $tg_id = get_post_meta($payment_id, '_tgcb_tg_id', true);
        $username = get_post_meta($payment_id, '_tgcb_username', true);
        $first_name = get_post_meta($payment_id, '_tgcb_first_name', true);
        $last_name = get_post_meta($payment_id, '_tgcb_last_name', true);
        $course_id = get_post_meta($payment_id, '_tgcb_course_id', true);
        $receipt_photo = get_post_meta($payment_id, '_tgcb_receipt_photo', true);

        $course_title = get_the_title($course_id);

        $text = "🔔 <b>New Payment Request</b>\n\n";
        $text .= "👤 Student: {$first_name} {$last_name}\n";
        $text .= "📱 Username: @{$username}\n";
        $text .= "🆔 TG ID: {$tg_id}\n";
        $text .= "📚 Course: {$course_title}\n\n";
        $text .= "View in admin: " . admin_url('post.php?post=' . $payment_id . '&action=edit');

        $keyboard = array(
            array(
                array('text' => '✅ Approve', 'callback_data' => 'approve_' . $payment_id),
                array('text' => '❌ Reject', 'callback_data' => 'reject_' . $payment_id)
            )
        );

        $reply_markup = array('inline_keyboard' => $keyboard);

        if ($receipt_photo) {
            $this->telegram->send_photo($admin_id, $receipt_photo, $text, $reply_markup);
        } else {
            $this->telegram->send_message($admin_id, $text, $reply_markup);
        }
    }

    /**
     * Process payment approval
     */
    private function process_approval($payment_id, $callback)
    {
        $tg_id = get_post_meta($payment_id, '_tgcb_tg_id', true);
        $course_id = get_post_meta($payment_id, '_tgcb_course_id', true);

        // Generate invite link
        $invite_link = TGCB_Invite_Manager::create_invite_link($course_id);

        if ($invite_link) {
            update_post_meta($payment_id, '_tgcb_status', 'approved');
            update_post_meta($payment_id, '_tgcb_invite_link', $invite_link);

            // Grant access in database immediately
            TGCB_Database::add_course_to_student($tg_id, $course_id);

            // Send invite to student
            $course_title = get_the_title($course_id);

            $message = get_option('tgcb_msg_approved', "✅ <b>Оплата подтверждена!</b>\n\nВаш доступ к <b>{course}</b> открыт.\n\nНажмите на ссылку ниже, чтобы вступить:\n{link}\n\n⚠️ Эта ссылка одноразовая и действует 24 часа.");
            $message = str_replace(array('{course}', '{link}'), array($course_title, $invite_link), $message);

            $this->telegram->send_message($tg_id, $message);

            // Update admin
            $this->telegram->answer_callback_query($callback->id, '✅ Approved!', true);
            $this->telegram->edit_message_text(
                $callback->message->chat->id,
                $callback->message->message_id,
                $callback->message->text . "\n\n✅ <b>APPROVED</b>"
            );
        } else {
            $this->telegram->answer_callback_query($callback->id, '❌ Failed to create invite link', true);
        }
    }

    /**
     * Process payment rejection
     */
    private function process_rejection($payment_id, $callback)
    {
        $tg_id = get_post_meta($payment_id, '_tgcb_tg_id', true);

        update_post_meta($payment_id, '_tgcb_status', 'rejected');

        // Notify student
        $message = get_option('tgcb_msg_rejected', "❌ <b>Оплата отклонена</b>\n\nК сожалению, ваш платеж не подтвержден.\nПожалуйста, свяжитесь с поддержкой.");

        $this->telegram->send_message($tg_id, $message);

        // Update admin
        $this->telegram->answer_callback_query($callback->id, '❌ Rejected', true);
        $this->telegram->edit_message_text(
            $callback->message->chat->id,
            $callback->message->message_id,
            $callback->message->text . "\n\n❌ <b>REJECTED</b>"
        );
    }

    /**
     * Get file URL from Telegram
     */
    private function get_file_url($file_id)
    {
        $telegram = new TGCB_Telegram_API();
        $bot_token = get_option('tgcb_bot_token', '');

        $response = wp_remote_get("https://api.telegram.org/bot{$bot_token}/getFile?file_id={$file_id}");

        if (is_wp_error($response)) {
            return '';
        }

        $result = json_decode(wp_remote_retrieve_body($response));

        if ($result && $result->ok && isset($result->result->file_path)) {
            return "https://api.telegram.org/file/bot{$bot_token}/" . $result->result->file_path;
        }

        return '';
    }

    /**
     * AJAX: Approve payment from WordPress admin
     */
    public function ajax_approve_payment()
    {
        check_ajax_referer('tgcb_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $payment_id = intval($_POST['payment_id']);
        $tg_id = get_post_meta($payment_id, '_tgcb_tg_id', true);
        $course_id = get_post_meta($payment_id, '_tgcb_course_id', true);

        // Generate invite link
        $invite_link = TGCB_Invite_Manager::create_invite_link($course_id);

        if ($invite_link) {
            update_post_meta($payment_id, '_tgcb_status', 'approved');
            update_post_meta($payment_id, '_tgcb_invite_link', $invite_link);

            // Grant access in database immediately
            TGCB_Database::add_course_to_student($tg_id, $course_id);

            // Send invite to student
            $telegram = new TGCB_Telegram_API();
            $course_title = get_the_title($course_id);

            $message = get_option('tgcb_msg_approved', "✅ <b>Оплата подтверждена!</b>\n\nВаш доступ к <b>{course}</b> открыт.\n\nНажмите на ссылку ниже, чтобы вступить:\n{link}\n\n⚠️ Эта ссылка одноразовая и действует 24 часа.");
            $message = str_replace(array('{course}', '{link}'), array($course_title, $invite_link), $message);

            $telegram->send_message($tg_id, $message);

            wp_send_json_success('Payment approved and invite sent!');
        } else {
            wp_send_json_error('Failed to create invite link');
        }
    }

    /**
     * AJAX: Reject payment from WordPress admin
     */
    public function ajax_reject_payment()
    {
        check_ajax_referer('tgcb_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $payment_id = intval($_POST['payment_id']);
        $tg_id = get_post_meta($payment_id, '_tgcb_tg_id', true);

        update_post_meta($payment_id, '_tgcb_status', 'rejected');

        // Notify student
        $telegram = new TGCB_Telegram_API();
        $message = get_option('tgcb_msg_rejected', "❌ <b>Оплата отклонена</b>\n\nК сожалению, ваш платеж не подтвержден.\nПожалуйста, свяжитесь с поддержкой.");

        $telegram->send_message($tg_id, $message);

        wp_send_json_success('Payment rejected');
    }

    /**
     * AJAX: Resend invite link
     */
    public function ajax_resend_invite()
    {
        check_ajax_referer('tgcb_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $tg_id = isset($_POST['tg_id']) ? sanitize_text_field($_POST['tg_id']) : 0;
        $course_id = intval($_POST['course_id']);

        if (!$tg_id || !$course_id) {
            wp_send_json_error('Missing parameters');
        }

        // Generate new invite link
        $invite_link = TGCB_Invite_Manager::create_invite_link($course_id);

        if ($invite_link) {
            $telegram = new TGCB_Telegram_API();
            $course_title = get_the_title($course_id);

            $message = "🎟 <b>" . __('New Invite Link', 'tg-course-bot-pro') . "</b>\n\n";
            $message .= sprintf(__('Here is your new invite link for <b>%s</b>:', 'tg-course-bot-pro'), $course_title) . "\n";
            $message .= $invite_link . "\n\n";
            $message .= "⚠️ " . __('This link is one-time use only and will expire in 24 hours.', 'tg-course-bot-pro');

            $telegram->send_message($tg_id, $message);

            wp_send_json_success('Invite link sent to user!');
        } else {
            wp_send_json_error('Failed to create invite link');
        }
    }
}
