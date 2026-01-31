# TG Course Bot PRO

A professional WordPress plugin for managing Telegram course access with automated payment verification, one-time invite links, and anti-piracy protection.

## 🌟 Features

- **Course Management**: Create and manage multiple Telegram courses from WordPress
- **Payment Verification**: Automated receipt processing with admin approval workflow
- **One-Time Invite Links**: Secure, auto-expiring, single-use channel invitations
- **Welcome Messages**: Customizable greeting for new students
- **Anti-Piracy Protection**: Automatic detection and banning of unauthorized access
- **Student Database**: Track all students, enrollments, and activity
- **Admin Dashboard**: Real-time statistics and quick actions
- **Telegram Integration**: Full bot API integration with webhook support

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- SSL Certificate (HTTPS required)
- Telegram Bot Token
- MySQL 5.6+

## 🚀 Quick Start

1. **Install Plugin**
   ```
   Upload to /wp-content/plugins/tg-course-bot-pro/
   Activate via WordPress admin
   ```

2. **Configure Bot**
   - Get bot token from [@BotFather](https://t.me/BotFather)
   - Add token in Settings
   - Click "Setup Webhook"

3. **Create Course**
   - Add Telegram channel
   - Make bot admin with invite permissions
   - Create course in WordPress with channel ID

4. **Start Accepting Students**
   - Students send receipts to bot
   - You approve/reject
   - Bot sends one-time invite links
   - Students join automatically

## 📖 Documentation

- [Installation Guide](INSTALLATION.md) - Detailed setup instructions
- [Administrator Guide](ADMIN-GUIDE.md) - Complete feature documentation
- [Testing Guide](TESTING.md) - Quality assurance procedures

## 🏗️ Plugin Structure

```
tg-course-bot-pro/
├── tg-course-bot-pro.php          # Main plugin file
├── includes/                       # Core classes
│   ├── class-database.php          # Database operations
│   ├── class-telegram-api.php      # Telegram Bot API wrapper
│   ├── class-webhook-handler.php   # Process incoming updates
│   ├── class-invite-manager.php    # One-time link management
│   ├── class-welcome-handler.php   # Welcome messages
│   ├── class-anti-piracy.php       # Security & piracy detection
│   ├── class-cpt-courses.php       # Courses custom post type
│   └── class-cpt-payments.php      # Payments custom post type
├── admin/                          # Admin interface
│   ├── class-admin-menu.php        # Menu structure
│   ├── class-bot-settings.php      # Settings page
│   └── class-students-table.php    # Students list table
├── assets/                         # Frontend resources
│   ├── css/
│   │   └── admin-style.css         # Admin styles
│   └── js/
│       └── admin-script.js         # Admin JavaScript
├── INSTALLATION.md                 # Installation guide
├── ADMIN-GUIDE.md                  # Administrator manual
├── TESTING.md                      # Testing procedures
└── README.md                       # This file
```

## 🔧 How It Works

### Payment Flow

1. **Student Request**
   - Student messages bot with `/start`
   - Selects desired course
   - Sends payment receipt photo

2. **Admin Review**
   - Admin receives notification in Telegram
   - Reviews receipt and student info
   - Clicks Approve or Reject

3. **Access Granted**
   - Bot generates one-time invite link
   - Sends link to student (expires in 24h)
   - Student joins channel
   - Link auto-revokes after use

4. **Onboarding**
   - Bot detects student joined
   - Sends welcome message
   - Updates database
   - Marks payment completed

### Anti-Piracy System

- Invite links limited to single use
- Links expire automatically
- Tracks link usage by user ID
- Detects unauthorized access attempts
- Auto-bans pirates and alerts admin
- Logs all security incidents

## 🗄️ Database Tables

**wp_tgcb_students**
- Stores all student information
- Tracks course enrollments
- Manages ban status

**wp_tgcb_invite_links**
- Logs all generated links
- Tracks usage and expiration
- Anti-piracy verification

**wp_tgcb_left_log**
- Records channel departures
- Analyzes student retention

**wp_tgcb_piracy_log**
- Security incident tracking
- Identifies attack patterns

## 🎨 Screenshots

(Add screenshots of your admin interface here)

## 🔐 Security Features

- One-time use invite links
- Automatic link expiration
- Real-time piracy detection
- User banning system
- Secure webhook validation
- Nonce verification for AJAX
- SQL injection prevention
- XSS protection

## 🌍 Localization

Plugin is translation-ready:
- Text domain: `tg-course-bot-pro`
- POT file included
- RTL support ready

## 🔄 REST API Endpoints

**Webhook Endpoint**
```
POST /wp-json/tgcb/v1/webhook
```
Receives Telegram bot updates

## ⚙️ Filters & Actions

### Actions
- `tgcb_payment_approved` - Fires when payment approved
- `tgcb_student_joined` - Fires when student joins course
- `tgcb_piracy_detected` - Fires on security incident

### Filters
- `tgcb_welcome_message` - Modify welcome message
- `tgcb_invite_link_expire` - Custom link expiry
- `tgcb_payment_statuses` - Add custom statuses

## 🛠️ Developer Notes

### Extending the Plugin

**Add Custom Payment Methods**
```php
add_filter('tgcb_payment_methods', function($methods) {
    $methods['crypto'] = 'Cryptocurrency';
    return $methods;
});
```

**Custom Welcome Message Logic**
```php
add_filter('tgcb_welcome_message', function($message, $course_id, $user_id) {
    // Custom logic
    return $message;
}, 10, 3);
```

## 📞 Support

For issues or questions:
1. Check documentation
2. Review error logs
3. Test bot with @BotFather
4. Verify permissions

## 📝 Changelog

### Version 1.0.0 (2026-01-23)
- Initial release
- Course management system
- Payment verification workflow
- One-time invite links
- Anti-piracy protection
- Student database
- Admin dashboard

## 📄 License

GPL v2 or later

## 👨‍💻 Author

Your Name  
https://example.com

## 🙏 Credits

- WordPress Core Team
- Telegram Bot API
- Community contributors

## ⚠️ Disclaimer

This plugin requires:
- Valid Telegram bot
- HTTPS (SSL certificate)
- Proper server configuration
- Regular maintenance

Always test in staging environment before production deployment.

---

**Made with ❤️ for WordPress and Telegram**
