# Email Configuration Guide

## Overview

The ERP system uses PHPMailer for sending emails via SMTP. You need to configure your SMTP settings to enable email functionality.

## Supported Email Providers

- Gmail
- Outlook/Office 365
- SendGrid
- Mailgun
- Amazon SES
- Custom SMTP servers

## Configuration Methods

### Method 1: Database Configuration (Recommended)

1. Navigate to **Settings → System Settings → Email**
2. Fill in the following details:
   - **SMTP Host**: Your mail server (e.g., smtp.gmail.com)
   - **SMTP Port**: 587 (TLS) or 465 (SSL)
   - **SMTP Username**: Your email address
   - **SMTP Password**: Your email password or app password
   - **From Email**: Email address to send from
   - **From Name**: Display name for emails
   - **Encryption**: TLS or SSL

3. Click **Save Settings**
4. Click **Send Test Email** to verify

### Method 2: Direct Database Insert

```sql
INSERT INTO erp_email_settings (
    smtp_host, smtp_port, smtp_username, smtp_password,
    smtp_encryption, from_email, from_name, is_enabled
) VALUES (
    'smtp.gmail.com',
    587,
    'your-email@gmail.com',
    'your-app-password',
    'tls',
    'your-email@gmail.com',
    'Your Company Name',
    1
);
```

## Provider-Specific Instructions

### Gmail

1. **Enable 2-Factor Authentication** on your Google account
2. **Generate App Password**:
   - Go to Google Account → Security
   - Select "2-Step Verification"
   - Scroll to "App passwords"
   - Generate password for "Mail"
3. **Use App Password** (not your regular password)

**Settings**:
- Host: `smtp.gmail.com`
- Port: `587`
- Encryption: `TLS`
- Username: `your-email@gmail.com`
- Password: `16-character app password`

### Outlook/Office 365

**Settings**:
- Host: `smtp.office365.com`
- Port: `587`
- Encryption: `STARTTLS`
- Username: `your-email@outlook.com`
- Password: `your-password`

### SendGrid

**Settings**:
- Host: `smtp.sendgrid.net`
- Port: `587`
- Encryption: `TLS`
- Username: `apikey`
- Password: `your-sendgrid-api-key`

### Mailgun

**Settings**:
- Host: `smtp.mailgun.org`
- Port: `587`
- Encryption: `TLS`
- Username: `postmaster@your-domain.mailgun.org`
- Password: `your-mailgun-password`

### Amazon SES

**Settings**:
- Host: `email-smtp.us-east-1.amazonaws.com` (adjust region)
- Port: `587`
- Encryption: `TLS`
- Username: `your-ses-smtp-username`
- Password: `your-ses-smtp-password`

## Testing

### Via UI
1. Go to **Settings → System Settings**
2. Click **Send Test Email**
3. Check your inbox

### Via Command Line
```bash
php index.php system_settings testEmail
```

## Troubleshooting

### "SMTP connect() failed"
- Check firewall settings
- Verify SMTP host and port
- Ensure port 587/465 is not blocked
- Try different port (587 vs 465)

### "Authentication failed"
- Verify username and password
- For Gmail, use app password (not regular password)
- Check if less secure apps is enabled (not recommended)
- Verify 2FA is enabled for app passwords

### "Could not instantiate mail function"
- Check PHP mail() is enabled
- Verify sendmail path in php.ini
- Check server mail configuration
- Ensure PHP has mail extension

### Emails go to spam
- Configure SPF records for your domain
- Set up DKIM signing
- Use authenticated SMTP
- Verify from email matches domain
- Avoid spam trigger words in subject/body

### Connection timeout
- Check if port is blocked by firewall
- Verify server allows outbound SMTP
- Try different encryption method (TLS vs SSL)
- Check if ISP blocks SMTP ports

## Email Features

### Supported Email Types

- ✅ Invoice emails with PDF attachments
- ✅ Payment receipts
- ✅ Booking confirmations
- ✅ User registration
- ✅ Password reset
- ✅ System notifications
- ✅ Payslips
- ✅ Tax reminders

### Email Queue

Emails are queued for reliability:
- Failed emails are retried automatically
- Queue can be processed via cron job
- View queue status in System Settings
- Manual retry available for failed emails

### Email Templates

Customize email templates in:
- `application/views/emails/`

Available templates:
- `invoice.php` - Invoice email
- `payment_receipt.php` - Payment confirmation
- `booking_confirmation.php` - Booking details
- `password_reset.php` - Password reset link
- `user_registration.php` - Welcome email

## Security Best Practices

1. **Use App Passwords** instead of account passwords
2. **Enable TLS/SSL** encryption always
3. **Rotate passwords** regularly (every 90 days)
4. **Monitor email logs** for suspicious activity
5. **Limit email rate** to prevent abuse
6. **Use dedicated email** for system notifications
7. **Never commit** SMTP credentials to version control
8. **Restrict access** to email settings (admin only)

## Cron Job Setup (Optional)

Process email queue automatically:

### Linux/Mac
```bash
# Add to crontab (crontab -e)
*/5 * * * * cd /path/to/erp && php index.php email_queue process
```

### Windows (Task Scheduler)
```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\erp\index.php email_queue process
Trigger: Every 5 minutes
```

This processes queued emails every 5 minutes.

## Email Logs

View email logs:
1. Navigate to **Settings → Email Logs**
2. Filter by status (sent, failed, pending)
3. View error messages for failed emails
4. Retry failed emails manually

## Rate Limiting

To prevent abuse, configure rate limits:

```sql
UPDATE erp_email_settings 
SET max_emails_per_hour = 100
WHERE id = 1;
```

## Attachment Limits

Default attachment size limit: 10MB

To change:
```php
// In Email_sender.php
$this->mail->addAttachment($path, $name, 'base64', 'application/pdf');
```

Check PHP upload limits:
- `upload_max_filesize` in php.ini
- `post_max_size` in php.ini

## Common Use Cases

### Send Invoice via Email
```php
$emailSender = new Email_sender();
$result = $emailSender->sendInvoice(
    $customerEmail,
    'Invoice #' . $invoiceNumber,
    $emailBody,
    $pdfPath,
    'Invoice_' . $invoiceNumber . '.pdf'
);
```

### Send Booking Confirmation
```php
$emailSender = new Email_sender();
$result = $emailSender->sendInvoice(
    $customerEmail,
    'Booking Confirmation',
    $confirmationBody,
    null,
    null
);
```

## FAQ

**Q: Can I use multiple email accounts?**
A: Currently, one SMTP configuration per system. Use email aliases for different departments.

**Q: How do I change the "From" name?**
A: Update `from_name` in email settings.

**Q: Are emails encrypted?**
A: Yes, when using TLS/SSL. Always use encryption.

**Q: Can I send bulk emails?**
A: Yes, but respect rate limits and use email queue.

**Q: What if my email provider blocks SMTP?**
A: Use a dedicated email service like SendGrid or Mailgun.

## Support

If you need help:
1. Check error logs: `application/logs/`
2. Test with different provider
3. Verify firewall settings
4. Contact your email provider
5. Check PHP mail configuration

## Next Steps

1. ✅ Configure SMTP settings
2. ✅ Send test email
3. ✅ Customize email templates
4. ✅ Set up cron job (optional)
5. ✅ Configure rate limits
6. ✅ Monitor email logs

Your email system is now ready! 📧
