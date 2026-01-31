# TG Course Bot PRO - Installation Guide

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- SSL certificate (HTTPS required for Telegram webhooks)
- Telegram Bot Token
- Admin access to Telegram channels

## 🚀 Installation Steps

### 1. Upload Plugin

1. Download the `tg-course-bot-pro` folder
2. Upload to `/wp-content/plugins/` directory
3. Or upload as ZIP via WordPress admin: Plugins → Add New → Upload Plugin

### 2. Activate Plugin

1. Go to **Plugins** in WordPress admin
2. Find "TG Course Bot PRO"
3. Click **Activate**

The plugin will automatically create necessary database tables upon activation.

### 3. Create Telegram Bot

1. Open Telegram and search for [@BotFather](https://t.me/BotFather)
2. Send `/newbot` command
3. Follow the instructions to create your bot
4. Copy the **Bot Token** (something like `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### 4. Get Your Telegram ID

1. Open Telegram and search for [@userinfobot](https://t.me/userinfobot)
2. Send `/start` command
3. Copy your **Telegram ID** (numeric value)

### 5. Configure Bot Settings

1. Go to **TG Course Bot → Settings** in WordPress admin
2. Enter your **Bot Token**
3. Enter your **Telegram ID** (admin ID)
4. Click **Save Settings**

### 6. Setup Webhook

1. On the Settings page, click **Setup Webhook** button
2. Wait for confirmation message
3. Verify webhook status shows "✅ Active"

### 7. Create Telegram Channels

1. Create your course channels in Telegram
2. Make them **Private Channels**
3. Add your bot as **Administrator** with these permissions:
   - Invite users via link
   - Ban users
   - Delete messages

### 8. Get Channel IDs

To get your channel ID:

1. Forward any message from the channel to [@userinfobot](https://t.me/userinfobot)
2. Copy the channel ID (it will be negative, like `-1001234567890`)

Or use this alternative method:

1. Add [@raw_data_bot](https://t.me/raw_data_bot) to your channel
2. The bot will send you the channel ID
3. Remove the bot after getting the ID

### 9. Create Courses

1. Go to **TG Course Bot → Courses → Add New**
2. Enter course title
3. Fill in the course details:
   - **Telegram Channel ID**: The channel ID you got in step 8
   - **Price**: Course price (optional)
   - **Welcome Message**: Message sent to students after joining
   - **Invite Link Expire**: How long links are valid (default: 24 hours)
4. Click **Publish**

### 10. Test the Bot

1. Go to **TG Course Bot → Settings**
2. Scroll to "Test Bot" section
3. Enter a test message
4. Click "Send Test Message to Me"
5. Check your Telegram for the message

## ✅ Verification Checklist

- [ ] Plugin activated successfully
- [ ] Bot token configured
- [ ] Admin ID configured
- [ ] Webhook status shows "Active"
- [ ] At least one course created
- [ ] Bot is admin in all course channels
- [ ] Test message received successfully

## 🔧 Troubleshooting

### Webhook Not Working

**Problem**: Webhook status shows error or "Not Set"

**Solutions**:
- Ensure your site has a valid SSL certificate (HTTPS)
- Check bot token is correct
- Try clicking "Setup Webhook" again
- Check WordPress REST API is accessible: `https://yoursite.com/wp-json/`

### Bot Not Responding

**Problem**: Bot doesn't reply to messages

**Solutions**:
- Verify webhook is set up correctly
- Check bot token is valid
- Ensure WordPress REST API is working
- Check error logs: Settings → Debug Mode

### Can't Get Channel ID

**Problem**: Unable to find channel ID

**Solutions**:
- Use [@userinfobot](https://t.me/userinfobot) method above
- Try [@raw_data_bot](https://t.me/raw_data_bot)
- Channel ID always starts with `-100`
- Make sure the channel is private

### Invite Links Not Working

**Problem**: Links don't grant access

**Solutions**:
- Ensure bot is **admin** in the channel
- Bot must have "Invite users via link" permission
- Check link hasn't expired (default 24 hours)
- Verify course channel ID is correct

## 📞 Support

For issues or questions:
- Check the Administrator Guide
- Review error logs in WordPress
- Test with @BotFather that bot is working
- Verify all permissions are set correctly

## 🔄 Updates

To update the plugin:
1. Deactivate the plugin
2. Replace plugin files
3. Reactivate the plugin
4. Database tables will auto-update

## ⚠️ Important Notes

- Always backup your database before updating
- Keep your bot token secure (never share it)
- Use HTTPS (required for Telegram webhooks)
- Test in a staging environment first
- Monitor the pending payments regularly
