# TG Course Bot PRO - Testing Guide

## 🧪 Complete Testing Scenarios

This guide provides step-by-step testing procedures to verify all plugin functionality.

## ✅ Test 1: Installation & Setup

**Objective**: Verify plugin installs correctly and creates necessary resources

### Steps:
1. Upload and activate plugin in WordPress
2. Check admin menu appears: "TG Course Bot"
3. Navigate to database (phpMyAdmin)
4. Verify tables created:
   - `wp_tgcb_students`
   - `wp_tgcb_invite_links`
   - `wp_tgcb_left_log`
   - `wp_tgcb_piracy_log`

### Expected Result:
- Plugin activates without errors
- Admin menu visible
- All database tables exist with correct structure
- Dashboard page loads successfully

## ✅ Test 2: Bot Configuration

**Objective**: Configure bot settings and establish webhook connection

### Prerequisites:
- Telegram account
- Bot created via @BotFather
- Bot token available

### Steps:
1. Go to **TG Course Bot → Settings**
2. Enter bot token from @BotFather
3. Get your Telegram ID from @userinfobot
4. Enter admin Telegram ID
5. Click **Save Settings**
6. Click **Setup Webhook**
7. Verify webhook status shows "✅ Active"

### Expected Result:
- Settings save successfully
- Webhook status shows "Active"
- No error messages
- Webhook URL is HTTPS

## ✅ Test 3: Bot Communication

**Objective**: Verify bot can send and receive messages

### Steps:
1. Go to **TG Course Bot → Settings**
2. Scroll to "Test Bot" section
3. Enter message: "Hello! This is a test."
4. Click **Send Test Message to Me**
5. Check your Telegram app

### Expected Result:
- Success message in WordPress
- Test message received in Telegram
- Message formatted correctly

## ✅ Test 4: Create Telegram Channel

**Objective**: Set up a test course channel

### Steps:
1. Open Telegram
2. Create new channel (private)
3. Name it "Test Course 1"
4. Add your bot as administrator with permissions:
   - Invite users via link ✓
   - Ban users ✓
   - Delete messages ✓
5. Get channel ID using @userinfobot
6. Save channel ID

### Expected Result:
- Channel created successfully
- Bot is admin with correct permissions
- Channel ID obtained (format: `-1001234567890`)

## ✅ Test 5: Create Course in WordPress

**Objective**: Add course and configure settings

### Steps:
1. Go to **TG Course Bot → Courses → Add New**
2. Enter title: "Test Course 1"
3. Fill in details:
   - **Channel ID**: Your channel ID from Test 4
   - **Price**: 50 USD
   - **Welcome Message**: 
     ```
     🎓 <b>Welcome to Test Course 1!</b>
     
     Thank you for joining. Enjoy your learning experience!
     ```
   - **Link Expire**: 24 hours
4. Click **Publish**

### Expected Result:
- Course saved successfully
- Appears in courses list
- Shows correct channel ID and price

## ✅ Test 6: Student Payment Workflow

**Objective**: Test complete payment-to-access workflow

### Prerequisites:
- Course created (Test 5)
- Second Telegram account (student account)

### Steps:

**Part A: Student Sends Receipt**
1. From student account, message your bot
2. Send `/start` command
3. Select "Test Course 1" from buttons
4. Send any image as "payment receipt"

**Part B: Admin Receives Notification**
1. Check admin Telegram account
2. Verify notification received with:
   - Student info
   - Course name
   - Receipt photo
   - Approve/Reject buttons

**Part C: Admin Approves**
1. Click **✅ Approve** button in Telegram
   OR
2. Go to **TG Course Bot → Payments** in WordPress
3. Open pending payment
4. Click **Approve Payment**

**Part D: Student Receives Invite**
1. Check student Telegram account
2. Verify invite message received
3. Invite link present
4. Link is clickable

**Part E: Student Joins Channel**
1. Click invite link
2. Join the channel
3. Verify access granted

**Part F: Welcome Message**
1. Check student receives welcome message
2. Message matches course configuration

**Part G: Verify in WordPress**
1. Go to **TG Course Bot → Payments**
2. Check payment status: "🎓 Completed"
3. Go to **TG Course Bot → Students**
4. Verify student appears with course access

### Expected Result:
- Complete workflow executes without errors
- All notifications sent correctly
- Invite link works and expires after use
- Welcome message delivered
- Payment marked completed
- Student recorded in database

## ✅ Test 7: Payment Rejection

**Objective**: Verify rejection workflow

### Steps:
1. Send payment request from another student account
2. Select course and send receipt
3. Admin receives notification
4. Click **❌ Reject** button
5. Check student account

### Expected Result:
- Rejection message received by student
- No invite link sent
- Payment status: "❌ Rejected"
- Student does not gain access

## ✅ Test 8: Anti-Piracy Protection

**Objective**: Test security against link sharing

### Prerequisites:
- Approved payment with invite link
- Third Telegram account (pirate account)

### Steps:
1. Copy invite link from approved student
2. Try to join from different account (pirate)
3. Check what happens

### Expected Result:
- Pirate account banned automatically
- Pirate kicked from channel (if joined)
- Admin receives piracy alert
- Link revoked
- Incident logged in database
- Dashboard shows piracy attempt

## ✅ Test 9: Student Management

**Objective**: Test student administration features

### Steps:

**Part A: View Students**
1. Go to **TG Course Bot → Students**
2. Verify all test students appear
3. Check data accuracy:
   - Names
   - Usernames
   - Course enrollments
   - Timestamps

**Part B: Ban Student**
1. Select a student
2. Choose "Ban" bulk action
3. Apply
4. Verify student status: "🚫 Banned"

**Part C: Test Banned Access**
1. From banned account, message bot
2. Try to access courses

### Expected Result:
- Students list displays correctly
- Ban action works
- Banned student cannot access bot
- Removed from all course channels

## ✅ Test 10: Multiple Courses

**Objective**: Verify multi-course support

### Steps:
1. Create second Telegram channel
2. Create second course in WordPress
3. Send payment request for Course 2
4. Approve and verify workflow
5. Check student has access to both courses

### Expected Result:
- Multiple courses work independently
- Student can enroll in multiple courses
- Each course has separate invite links
- Welcome messages course-specific

## ✅ Test 11: Link Expiration

**Objective**: Test invite link expiry

### Steps:
1. Create course with 1-hour expiry
2. Generate invite link via payment approval
3. Wait 1 hour (or modify link expiry time in DB for faster testing)
4. Try to use expired link

### Expected Result:
- Link expires after set time
- Expired link shows error
- Cleanup task revokes expired links

## ✅ Test 12: Dashboard Statistics

**Objective**: Verify dashboard accuracy

### Steps:
1. Note current statistics
2. Perform actions:
   - Add course
   - Approve payment
   - Ban student
   - Trigger piracy attempt
3. Return to dashboard
4. Verify numbers updated

### Expected Result:
- All statistics accurate
- Updates reflect recent changes
- Pending payments show correctly

## ✅ Test 13: Student Leaves Channel

**Objective**: Test channel departure logging

### Steps:
1. From active student account
2. Leave course channel
3. Check WordPress database

### Expected Result:
- Departure logged in `wp_tgcb_left_log`
- Course removed from student's course list
- Student can rejoin with new payment

## ✅ Test 14: Webhook Resilience

**Objective**: Test webhook stability

### Steps:
1. Send multiple rapid messages to bot
2. Send different message types:
   - Text
   - Photos
   - Commands
3. Monitor WordPress error logs

### Expected Result:
- All messages processed
- No errors in logs
- Bot responds appropriately
- No duplicate processing

## ✅ Test 15: Edge Cases

**Objective**: Test unusual scenarios

### Test Cases:

**A. No Courses Available**
1. Delete all courses
2. Message bot with `/start`
- Expected: "No courses available" message

**B. Invalid Channel ID**
1. Create course with fake channel ID
2. Try to approve payment
- Expected: Error message, link not created

**C. Duplicate Payment**
1. Send receipt for course
2. Get approved and join
3. Send another receipt for same course
- Expected: "Already enrolled" message

**D. Bot Not Admin**
1. Remove bot admin rights from channel
2. Try to approve payment
- Expected: Error creating invite link

**E. Very Long Welcome Message**
1. Create welcome message > 4096 characters
2. Student joins
- Expected: Message split or truncated properly

## 📝 Test Results Template

Use this template to record test results:

```
Test: [Test Number and Name]
Date: [Date]
Tester: [Name]
Result: ✅ PASS / ❌ FAIL

Notes:
- [Any observations]
- [Issues found]
- [Unexpected behavior]

Screenshots: [if applicable]
```

## 🐛 Bug Reporting

If tests fail, document:

1. **Test number and name**
2. **Steps to reproduce**
3. **Expected result**
4. **Actual result**
5. **Screenshots/error messages**
6. **WordPress version**
7. **PHP version**
8. **Browser (if applicable)**

## ✅ Production Readiness Checklist

Before going live:

- [ ] All 15 tests passed
- [ ] No errors in WordPress debug log
- [ ] Webhook stable under load
- [ ] SSL certificate valid
- [ ] Bot token secured
- [ ] Admin notifications working
- [ ] Anti-piracy tested
- [ ] Multiple concurrent users tested
- [ ] Mobile devices tested
- [ ] Backup procedures in place

## 🔒 Security Testing

Additional security checks:

- [ ] Bot token not exposed in frontend
- [ ] AJAX requests use nonces
- [ ] User capabilities checked
- [ ] SQL injection prevented
- [ ] XSS protection in place
- [ ] CSRF tokens validated

## 📊 Performance Testing

Recommended load tests:

1. **100 concurrent payment requests**
2. **1000 students in database**
3. **Multiple simultaneous approvals**
4. **Webhook under heavy load**

Use tools like:
- Apache Bench for load testing
- Telegram Bot API testing
- WordPress Query Monitor

---

**Testing Complete!** 🎉

If all tests pass, your plugin is ready for production use.
