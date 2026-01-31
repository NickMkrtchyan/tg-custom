# TG Course Bot PRO - Administrator Guide

## 📚 Table of Contents

1. [Dashboard Overview](#dashboard-overview)
2. [Managing Courses](#managing-courses)
3. [Processing Payments](#processing-payments)
4. [Managing Students](#managing-students)
5. [Bot Settings](#bot-settings)
6. [Common Workflows](#common-workflows)

## 🎯 Dashboard Overview

The dashboard shows key metrics:

- **Total Courses**: Number of active courses
- **Total Students**: All registered students
- **Pending Payments**: Awaiting approval
- **Piracy Attempts**: Detected unauthorized access

Quick actions available:
- Add New Course
- View Payments
- Manage Students
- Bot Settings

## 📖 Managing Courses

### Creating a New Course

1. Navigate to **TG Course Bot → Courses → Add New**
2. Enter course details:
   - **Title**: Course name (shown to students)
   - **Channel ID**: Your Telegram channel ID (e.g., `-1001234567890`)
   - **Price**: Optional price and currency
   - **Welcome Message**: HTML-formatted message sent after joining
   - **Link Expire Hours**: Link validity period (1-168 hours)

3. Click **Publish**

### Course Settings Explained

**Telegram Channel ID**
- Must start with `-100`
- Get it from @userinfobot or @raw_data_bot
- Channel must be private
- Bot must be admin with invite permissions

**Welcome Message**
- Supports HTML formatting: `<b>bold</b>`, `<i>italic</i>`
- Use `{student_name}` for personalization (if implemented)
- Keep it friendly and informative
- Include course access instructions

**Invite Link Settings**
- **Expire Hours**: How long before link becomes invalid
- **Member Limit**: Always 1 (one-time use only)
- Links auto-revoke after use for security

### Editing Courses

1. Go to **TG Course Bot → Courses**
2. Click course title or **Edit**
3. Modify settings as needed
4. Click **Update**

### Viewing Course Statistics

On the courses list:
- **Channel ID**: Quick reference
- **Price**: Course pricing
- **Students**: Total enrolled students

## 💳 Processing Payments

### Payment Workflow

1. Student sends payment receipt to bot
2. Bot creates pending payment record
3. Admin receives notification in Telegram
4. Admin reviews and approves/rejects
5. Bot sends one-time invite link (if approved)
6. Student joins channel
7. Bot sends welcome message
8. Payment marked as completed

### Reviewing Payments

**Method 1: Via Telegram**
- Receive notification with receipt photo
- Click **✅ Approve** or **❌ Reject** buttons
- Bot handles everything automatically

**Method 2: Via WordPress Admin**
1. Go to **TG Course Bot → Payments**
2. Find pending payment
3. Click to open
4. Review receipt photo
5. Click **Approve** or **Reject**

### Payment Statuses

- **⏳ Pending**: Awaiting review
- **✅ Approved**: Invite link sent to student
- **❌ Rejected**: Payment declined
- **🎓 Completed**: Student successfully joined

### Best Practices

- Review payments promptly (students are waiting)
- Check receipt photo carefully
- Verify amount matches course price
- Use reject for unclear/suspicious receipts
- Contact student separately for clarification if needed

## 👥 Managing Students

### Viewing Students

1. Go to **TG Course Bot → Students**
2. View all registered students with:
   - Telegram ID
   - Name and username
   - Enrolled courses
   - First seen / Last access dates
   - Status (Active/Banned)

### Student Actions

**Individual Actions**
- Click student name to view details
- Check course enrollment
- Review access history

**Bulk Actions**
- Select multiple students
- Choose action: Ban / Unban
- Apply changes

### Banning Students

**When to Ban:**
- Using stolen invite links
- Attempting piracy
- Violating terms of service
- Fraudulent payments

**What Happens:**
- Student removed from all courses
- Banned from bot access
- Logged in piracy records

**To Ban:**
1. Go to Students page
2. Check student(s)
3. Select "Ban" from bulk actions
4. Click Apply

### Unbanning Students

1. Go to Students page
2. Check banned student(s)
3. Select "Unban" from bulk actions
4. Click Apply

## ⚙️ Bot Settings

### Basic Settings

**Bot Token**
- From @BotFather
- Keep secret and secure
- Never share publicly

**Admin Telegram ID**
- Your Telegram user ID
- Receives payment notifications
- Only one admin supported (can be extended)

### Webhook Configuration

**Webhook URL**
- Automatically generated
- Must use HTTPS
- Format: `https://yoursite.com/wp-json/tgcb/v1/webhook`

**Setup Webhook Button**
- Registers webhook with Telegram
- Click after changing bot token
- Verify status shows "✅ Active"

**Webhook Status Indicators**
- ✅ Active: Working correctly
- ❌ Not Set: Needs setup
- Pending Updates: Number of queued messages

### Testing the Bot

**Test Message Feature**
1. Enter test message in text area
2. Click "Send Test Message to Me"
3. Check your Telegram
4. Confirms bot is working

## 🔄 Common Workflows

### Workflow 1: Adding a New Course

1. Create Telegram channel
2. Add bot as admin with invite permissions
3. Get channel ID
4. Create course in WordPress
5. Enter all details
6. Test by sending yourself a payment

### Workflow 2: Processing a Payment Request

1. Receive Telegram notification
2. Review receipt photo
3. Verify payment amount and details
4. Click Approve (in Telegram or WordPress)
5. Bot sends invite link automatically
6. Student joins channel
7. Bot sends welcome message
8. Payment auto-marked as completed

### Workflow 3: Handling Suspicious Activity

1. Check piracy stats on dashboard
2. Review piracy logs (if implemented)
3. Ban unauthorized users
4. Revoke compromised invite links
5. Notify legitimate user if link was stolen

### Workflow 4: Bulk Student Management

1. Export student list (via Students page)
2. Filter by course or status
3. Select students for bulk action
4. Apply ban/unban as needed
5. Verify changes

## 🚨 Security & Anti-Piracy

### How Anti-Piracy Works

- Invite links limited to 1 use
- Links expire automatically
- Bot tracks link usage
- Unauthorized users auto-banned
- Admin receives piracy alerts

### Piracy Detection

Bot detects when:
- Someone uses another person's invite link
- Link is shared publicly
- Same link used multiple times

**Automatic Actions:**
- Pirate banned immediately
- Pirate kicked from channel
- Link revoked
- Admin notified
- Incident logged

### Monitoring Security

Check dashboard for:
- Total piracy attempts
- Recent security incidents
- Banned users count

## 💡 Tips & Best Practices

### Course Management
- Use clear, descriptive course names
- Set reasonable link expiry (24-48 hours)
- Write welcoming, helpful welcome messages
- Keep channel IDs documented

### Payment Processing
- Process payments daily
- Respond within 24 hours
- Be clear about rejection reasons
- Consider automated approval for known students (future feature)

### Student Relations
- Ban only when necessary
- Document ban reasons
- Allow appeals process
- Communicate clearly

### Bot Maintenance
- Monitor webhook status weekly
- Test bot functionality regularly
- Keep WordPress and PHP updated
- Backup database before updates
- Review error logs if issues occur

## 📊 Reports & Analytics

### Available Data

**Courses**
- Total students per course
- Enrollment trends
- Popular courses

**Payments**
- Pending count
- Approval rate
- Processing time

**Students**
- Total registered
- Active vs banned
- Course participation

**Security**
- Piracy attempts
- Ban statistics
- Link usage

### Exporting Data

Use WordPress tools to export:
- Student database (via phpMyAdmin)
- Payment records (CPT export)
- Course listings

## ❓ FAQs

**Q: Can I have multiple admins?**
A: Currently supports one admin ID. Can be extended in code.

**Q: How long do invite links last?**
A: Configurable per course (1-168 hours, default 24).

**Q: What if a student loses their invite link?**
A: Generate new link by creating new payment manually.

**Q: Can I broadcast messages to all students?**
A: Yes, use the broadcast functionality (developer feature).

**Q: How do I change course pricing?**
A: Edit course and update price field.

**Q: What happens if a student leaves the channel?**
A: Logged in database. They lose access. Must repay to rejoin.

## 🔧 Troubleshooting

### Students Not Receiving Invite Links

- Check webhook status is active
- Verify bot token is correct
- Ensure payment status is "approved"
- Check Telegram bot is not blocked

### Welcome Messages Not Sending

- Verify welcome message is set for course
- Check bot is admin in channel
- Ensure chat_member updates enabled in webhook

### Payments Stuck as Pending

- Review and approve/reject manually
- Check admin Telegram ID is correct
- Verify bot can send messages to admin

## 📞 Need Help?

1. Check Installation Guide
2. Review error logs in WordPress
3. Test bot with @BotFather
4. Verify all permissions
5. Check Telegram Bot API status

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-23
