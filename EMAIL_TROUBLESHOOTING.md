# Email Troubleshooting Guide

## Issue: Emails Show "Sent" But Don't Arrive

If emails are reporting as "sent" but not arriving in the recipient's mailbox, check the following:

### 1. Check Error Logs

Check your PHP error log for detailed error messages:
- Location: Usually in `C:\xampp\php\logs\php_error_log` or check `php.ini` for `error_log` setting
- Look for lines containing "PHPMailer" or "Email_sender"

### 2. Gmail-Specific Issues

**Problem:** Gmail requires the "From" email address to match the SMTP username.

**Solution:**
- In System Settings > Email Configuration:
  - Set **From Email** to the same address as **SMTP Username**
  - Example: If SMTP Username is `yourname@gmail.com`, From Email must also be `yourname@gmail.com`

**Why:** Gmail rejects emails where the authenticated user doesn't match the sender address.

### 3. Check Spam/Junk Folder

- Emails might be delivered but filtered to spam
- Check the recipient's spam/junk folder
- Ask recipient to mark as "Not Spam" if found

### 4. Verify SMTP Credentials

**For Gmail:**
1. Go to https://myaccount.google.com/security
2. Enable **2-Factor Authentication** (required)
3. Go to https://myaccount.google.com/apppasswords
4. Generate an **App Password** for "Mail"
5. Use the 16-character password (not your regular Gmail password)
6. Copy it to **SMTP Password** in System Settings

**Common Mistakes:**
- Using regular Gmail password instead of App Password
- App Password not generated (requires 2FA)
- Wrong SMTP host/port

### 5. SMTP Settings for Common Providers

**Gmail:**
- SMTP Host: `smtp.gmail.com`
- SMTP Port: `587` (TLS) or `465` (SSL)
- Encryption: `TLS` (for port 587) or `SSL` (for port 465)
- Username: Your full Gmail address
- Password: App Password (16 characters)

**Outlook/Hotmail:**
- SMTP Host: `smtp-mail.outlook.com`
- SMTP Port: `587`
- Encryption: `TLS`
- Username: Your full email address
- Password: Your account password

**Yahoo:**
- SMTP Host: `smtp.mail.yahoo.com`
- SMTP Port: `587` or `465`
- Encryption: `TLS` or `SSL`
- Username: Your full email address
- Password: App Password (generate at account.yahoo.com)

### 6. Enable Debug Mode

To see detailed SMTP communication:

1. Edit `application/libraries/Email_sender.php`
2. Find line 141: `$this->mail->SMTPDebug = $this->debugMode ? 2 : 0;`
3. Temporarily change to: `$this->mail->SMTPDebug = 2;`
4. Try sending a test email
5. Check error logs for detailed SMTP conversation
6. **Remember to change it back to 0 after debugging!**

### 7. Firewall/Network Issues

- Ensure port 587 (TLS) or 465 (SSL) is not blocked
- Check if your hosting provider blocks SMTP ports
- Try using port 465 with SSL instead of 587 with TLS

### 8. Test Email Function

Use the test email function in System Settings:
1. Go to System Settings > Email Configuration
2. Enter a test email address
3. Click "Send Test Email"
4. Check the error message if it fails
5. Check error logs for detailed information

### 9. Common Error Messages

**"SMTP connect() failed"**
- Check firewall/port blocking
- Verify SMTP host and port
- Try different port (587 vs 465)

**"Authentication failed" or "535"**
- Wrong password (use App Password for Gmail)
- Username incorrect
- 2FA not enabled (for Gmail)

**"From address does not match"**
- For Gmail: From Email must match SMTP Username
- Update From Email in settings

**"Connection timeout"**
- SMTP host incorrect
- Port blocked by firewall
- Network connectivity issues

### 10. Verify Configuration

Run `test_email.php` in your browser to see:
- Current configuration
- Database settings
- What Email_sender is reading

### Still Not Working?

1. Check PHP error logs for detailed errors
2. Enable debug mode (see #6 above)
3. Verify all SMTP settings match your email provider's requirements
4. Test with a different email provider
5. Check if emails are being sent but going to spam

## Quick Checklist

- [ ] SMTP Host is correct for your provider
- [ ] SMTP Port matches encryption type (587=TLS, 465=SSL)
- [ ] SMTP Username is full email address
- [ ] SMTP Password is App Password (for Gmail) or account password
- [ ] From Email matches SMTP Username (for Gmail)
- [ ] 2FA enabled (for Gmail)
- [ ] App Password generated (for Gmail)
- [ ] Firewall allows SMTP ports
- [ ] Checked spam/junk folder
- [ ] Checked error logs

