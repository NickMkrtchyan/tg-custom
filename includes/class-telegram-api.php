<?php
/**
 * Telegram API Handler
 * Manages all Telegram Bot API interactions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TGCB_Telegram_API
{

    private $bot_token;
    private $api_url;

    public function __construct()
    {
        $this->bot_token = get_option('tgcb_bot_token', '');
        $this->api_url = 'https://api.telegram.org/bot' . $this->bot_token . '/';
    }

    /**
     * Send message to user
     */
    public function send_message($chat_id, $text, $reply_markup = null, $parse_mode = 'HTML')
    {
        $data = array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $parse_mode
        );

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        return $this->make_request('sendMessage', $data);
    }

    /**
     * Send photo
     */
    public function send_photo($chat_id, $photo, $caption = '', $reply_markup = null)
    {
        $data = array(
            'chat_id' => $chat_id,
            'photo' => $photo,
            'caption' => $caption
        );

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        return $this->make_request('sendPhoto', $data);
    }

    /**
     * Create chat invite link
     */
    public function create_chat_invite_link($chat_id, $member_limit = 1, $expire_date = null)
    {
        $data = array(
            'chat_id' => $chat_id,
            'member_limit' => $member_limit
        );

        if ($expire_date) {
            $data['expire_date'] = $expire_date;
        }

        $response = $this->make_request('createChatInviteLink', $data);

        if ($response && isset($response->result->invite_link)) {
            return $response->result->invite_link;
        }

        return false;
    }

    /**
     * Revoke chat invite link
     */
    public function revoke_chat_invite_link($chat_id, $invite_link)
    {
        $data = array(
            'chat_id' => $chat_id,
            'invite_link' => $invite_link
        );

        return $this->make_request('revokeChatInviteLink', $data);
    }

    /**
     * Get chat member
     */
    public function get_chat_member($chat_id, $user_id)
    {
        $data = array(
            'chat_id' => $chat_id,
            'user_id' => $user_id
        );

        return $this->make_request('getChatMember', $data);
    }

    /**
     * Ban chat member
     */
    public function ban_chat_member($chat_id, $user_id)
    {
        $data = array(
            'chat_id' => $chat_id,
            'user_id' => $user_id
        );

        return $this->make_request('banChatMember', $data);
    }

    /**
     * Unban chat member
     */
    public function unban_chat_member($chat_id, $user_id, $only_if_banned = true)
    {
        $data = array(
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'only_if_banned' => $only_if_banned
        );

        return $this->make_request('unbanChatMember', $data);
    }

    /**
     * Kick chat member (ban then immediately unban)
     * This removes the user from the channel without permanently banning them
     */
    public function kick_chat_member($chat_id, $user_id)
    {
        // First ban the user
        $ban_result = $this->ban_chat_member($chat_id, $user_id);

        if (!$ban_result) {
            return false;
        }

        // Then immediately unban them (they won't be able to rejoin without a new invite)
        return $this->unban_chat_member($chat_id, $user_id, false);
    }

    /**
     * Delete message
     */
    public function delete_message($chat_id, $message_id)
    {
        $data = array(
            'chat_id' => $chat_id,
            'message_id' => $message_id
        );

        return $this->make_request('deleteMessage', $data);
    }

    /**
     * Answer callback query
     */
    public function answer_callback_query($callback_query_id, $text = '', $show_alert = false)
    {
        $data = array(
            'callback_query_id' => $callback_query_id,
            'text' => $text,
            'show_alert' => $show_alert
        );

        return $this->make_request('answerCallbackQuery', $data);
    }

    /**
     * Edit message text
     */
    public function edit_message_text($chat_id, $message_id, $text, $reply_markup = null)
    {
        $data = array(
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        );

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        return $this->make_request('editMessageText', $data);
    }

    /**
     * Edit message caption (for photos/videos)
     */
    public function edit_message_caption($chat_id, $message_id, $caption, $reply_markup = null)
    {
        $data = array(
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        );

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        return $this->make_request('editMessageCaption', $data);
    }

    /**
     * Edit message reply markup (buttons only)
     */
    public function edit_message_reply_markup($chat_id, $message_id, $reply_markup = null)
    {
        $data = array(
            'chat_id' => $chat_id,
            'message_id' => $message_id
        );

        if ($reply_markup) {
            $data['reply_markup'] = json_encode($reply_markup);
        }

        return $this->make_request('editMessageReplyMarkup', $data);
    }

    /**
     * Set webhook
     */
    public function set_webhook($url)
    {
        $data = array(
            'url' => $url
        );

        return $this->make_request('setWebhook', $data);
    }

    /**
     * Get webhook info
     */
    public function get_webhook_info()
    {
        return $this->make_request('getWebhookInfo');
    }

    /**
     * Make API request
     */
    private function make_request($method, $data = array())
    {
        if (empty($this->bot_token)) {
            return false;
        }

        $url = $this->api_url . $method;

        $args = array(
            'body' => $data,
            'timeout' => 30,
            'sslverify' => true
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('TGCB Telegram API Error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);

        if (!$result || !$result->ok) {
            error_log('TGCB Telegram API Error: ' . $body);
            return false;
        }

        return $result;
    }

    /**
     * Format user mention
     */
    public static function format_user_mention($user)
    {
        $name = isset($user->first_name) ? $user->first_name : 'User';
        if (isset($user->last_name)) {
            $name .= ' ' . $user->last_name;
        }

        if (isset($user->username)) {
            return '<a href="tg://user?id=' . $user->id . '">@' . $user->username . '</a>';
        }

        return '<a href="tg://user?id=' . $user->id . '">' . $name . '</a>';
    }
}
