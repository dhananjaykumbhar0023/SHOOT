# 📧 Email System Troubleshooting Guide

## 🚨 If Emails Are Not Sending

### Step 1: Run Email Test
1. Open `test-email.php` in your browser
2. Check which tests pass (✅) and which fail (❌)
3. Follow the specific solutions below based on the results

### Step 2: Common Issues & Solutions

#### Issue 1: PHPMailer Files Missing
**Symptoms:** ❌ PHPMailer files not found
**Solution:**
```bash
# Make sure these files exist:
Phpmailer/src/PHPMailer.php
Phpmailer/src/SMTP.php
Phpmailer/src/Exception.php
```

#### Issue 2: Gmail SMTP Authentication Failed
**Symptoms:** SMTP authentication errors
**Solutions:**
1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password:**
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Replace `zrgkknimofpndvxx` with your new app password

3. **Allow Less Secure Apps** (if needed):
   - Go to Google Account settings
   - Security → Less secure app access → Turn on

#### Issue 3: Server Mail Function Disabled
**Symptoms:** ❌ PHP mail() function not available
**Solutions:**
1. **Contact your hosting provider** to enable mail() function
2. **Use the simple fallback:** The system automatically tries `booking-simple.php`
3. **Alternative SMTP services:** Consider using SendGrid, Mailgun, or similar

#### Issue 4: File Permissions
**Symptoms:** Cannot write to files
**Solution:**
```bash
# Set proper permissions
chmod 755 booking-mail.php
chmod 755 booking-simple.php
chmod 666 booking_submissions.txt
```

### Step 3: Alternative Email Methods

#### Method 1: Use Simple PHP Mail (Fallback)
The system automatically tries this if PHPMailer fails. No action needed.

#### Method 2: Manual Email Setup
If both automated methods fail:
1. Bookings are still saved to `booking_submissions.txt`
2. Check this file regularly for new bookings
3. Manually email clients using the saved information

#### Method 3: Third-Party Email Services
Consider integrating:
- **SendGrid** (free tier available)
- **Mailgun** (free tier available)
- **Amazon SES** (very cheap)

### Step 4: Testing the Booking Form

1. **Open the booking form:** `components/book.html`
2. **Fill out a test booking** with your email
3. **Check these locations for results:**
   - Your email inbox (both admin and client emails)
   - `booking_submissions.txt` file
   - Browser console for error messages

### Step 5: Debugging Tips

#### Enable Debug Mode
Add this to the top of `booking-mail.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Check Server Logs
Look for error messages in:
- PHP error logs
- Server error logs
- Browser console (F12 → Console)

#### Test Email Delivery
1. Check spam/junk folders
2. Try different email addresses
3. Use email testing tools like Mail-Tester.com

## 🔧 Quick Fixes

### Fix 1: Update Email Credentials
Edit `booking-mail.php` and `booking-simple.php`:
```php
$admin_email = "your-email@gmail.com";
$admin_password = "your-app-password";
```

### Fix 2: Change SMTP Settings
If Gmail doesn't work, try these alternatives:
```php
// For Outlook/Hotmail
$mail->Host = 'smtp-mail.outlook.com';
$mail->Port = 587;

// For Yahoo
$mail->Host = 'smtp.mail.yahoo.com';
$mail->Port = 587;
```

### Fix 3: Disable SMTP (Use Simple Mail)
Edit the form action in `book.html`:
```html
<form id="bookingForm" action="../booking-simple.php" method="POST">
```

## 📊 Success Indicators

### ✅ Everything Working:
- Test emails received in inbox
- Booking form submits successfully
- Both admin and client emails arrive
- Bookings saved to file

### ⚠️ Partial Success:
- Bookings saved but emails not sent
- Only one email (admin OR client) working
- Emails going to spam folder

### ❌ Not Working:
- Form submission fails
- No emails received
- Error messages in console
- Bookings not saved

## 🆘 Emergency Backup Plan

If nothing works:
1. **Bookings are still saved** to `booking_submissions.txt`
2. **Check this file daily** for new bookings
3. **Manually contact clients** using saved information
4. **Set up email forwarding** from a contact form service

## 📞 Support Checklist

Before asking for help, please:
1. ✅ Run `test-email.php` and note results
2. ✅ Check `booking_submissions.txt` for saved bookings
3. ✅ Verify Gmail app password is correct
4. ✅ Test with different email addresses
5. ✅ Check spam/junk folders
6. ✅ Look at browser console for JavaScript errors

## 🎯 Most Common Solution

**90% of email issues are solved by:**
1. Setting up Gmail App Password correctly
2. Enabling 2-Factor Authentication
3. Using the correct app password in the code

**Quick Test:**
1. Go to `test-email.php`
2. If you see ✅ for PHPMailer test, emails should work
3. If you see ❌, follow the Gmail setup steps above

---

**Remember:** Even if emails fail, bookings are always saved to `booking_submissions.txt` so no customer data is lost! 📝✅