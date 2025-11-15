# Quick Start Guide - Invoice PDF & Email System

## ✅ Installation Complete!

All components are installed and ready. Follow these steps to configure and test.

---

## Step 1: Configure Email (REQUIRED for email functionality)

### Option A: Config File (Recommended)

Edit: `application/config/config.installed.php`

Add or update the `email` section:
```php
'email' => [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => 'xxxx xxxx xxxx xxxx', // 16-char App Password
    'smtp_encryption' => 'tls',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Your Company Name'
]
```

### Option B: Inline Configuration

Edit: `application/libraries/Email_sender.php` (lines 18-24)

Update these lines:
```php
'smtp_user' => 'your-email@gmail.com',
'smtp_pass' => 'xxxx xxxx xxxx xxxx', // 16-char App Password
'from_email' => 'your-email@gmail.com',
```

### Gmail App Password Setup

1. Visit: https://myaccount.google.com/security
2. Enable **2-Factor Authentication**
3. Visit: https://myaccount.google.com/apppasswords
4. Select "Mail" and generate password
5. Copy the 16-character password (format: `xxxx xxxx xxxx xxxx`)
6. Paste in config (remove spaces or keep them, both work)

---

## Step 2: Test Email Configuration

Visit in browser:
```
http://localhost/erp/test_email.php?to=your-email@example.com
```

**Expected Result:**
- ✅ Shows configuration
- ✅ Sends test email
- ✅ Shows success message

**If Error:**
- Check error message
- Verify App Password is correct
- Check firewall allows port 587
- Enable debug mode (see troubleshooting)

---

## Step 3: Test Invoice PDF

### View Invoice
```
http://localhost/erp/receivables/invoices/view/1
```

**What to Check:**
- ✅ Invoice details display
- ✅ PDF viewer shows PDF (if generated)
- ✅ "View PDF" button works
- ✅ "Download PDF" button works
- ✅ "Send Email" button appears (if customer has email)

### Generate PDF
```
http://localhost/erp/receivables/invoices/pdf/1
```

**Expected:**
- PDF opens in browser
- File saved to `/uploads/invoices/` directory

### Download PDF
```
http://localhost/erp/receivables/invoices/download/1
```

**Expected:**
- Browser downloads PDF file
- Filename: `Invoice-INVOICE_NUMBER.pdf`

---

## Step 4: Test Email Sending

1. Go to invoice view page
2. Click "Send Email" button
3. Confirm in popup
4. Check customer email inbox

**Expected:**
- ✅ Success message: "Invoice sent to customer@email.com"
- ✅ Email received with PDF attachment
- ✅ Invoice status updates to 'sent'

---

## File Locations

### Libraries
- `application/libraries/Pdf_generator.php` ✅
- `application/libraries/Email_sender.php` ✅

### Controller Methods
- `application/controllers/Receivables.php` ✅
  - `viewInvoice()`
  - `pdfInvoice()`
  - `downloadInvoice()`
  - `sendInvoiceEmail()`

### Routes
- `application/config/routes.php` ✅

### Views
- `application/views/receivables/view_invoice.php` ✅
- `application/views/receivables/invoice_pdf.php` ✅ (template)

### Directories
- `uploads/invoices/` ✅
- `uploads/invoices/.htaccess` ✅

### Test Files
- `test_email.php` ✅ (root directory)

---

## Common Issues & Fixes

### Issue: PDF shows blank/empty

**Fix:**
1. Check if Dompdf is installed: `composer show dompdf/dompdf`
2. Verify template file exists: `application/views/receivables/invoice_pdf.php`
3. Check browser console for errors
4. Check PHP error logs

### Issue: Email test fails

**Fix:**
1. Verify Gmail App Password (not regular password)
2. Check SMTP settings in config
3. Enable debug: `Email_sender.php` line 80 → `SMTPDebug = 2`
4. Check firewall allows port 587
5. Verify 2-Factor Authentication is enabled

### Issue: "Email not configured" error

**Fix:**
1. Update `Email_sender.php` lines 18-24
2. OR update config file: `application/config/config.installed.php`
3. Ensure `smtp_user` and `smtp_pass` are not empty

### Issue: PDF files not saving

**Fix:**
1. Check directory permissions: `chmod 755 uploads/invoices`
2. Verify directory exists: `uploads/invoices/`
3. Check PHP write permissions

---

## Quick Test Commands

### Check Dependencies
```bash
C:\xampp\php\php.exe composer.phar show
```

### Test Email (Browser)
```
http://localhost/erp/test_email.php?to=test@example.com
```

### View Invoice (Browser)
```
http://localhost/erp/receivables/invoices/view/1
```

---

## Success Indicators

✅ **PDF Working:**
- PDF opens in browser
- Files appear in `/uploads/invoices/` directory
- Download works

✅ **Email Working:**
- Test email received
- Invoice email sent successfully
- PDF attachment included

✅ **System Ready:**
- All buttons functional
- No error messages
- Files generated correctly

---

## Need Help?

1. **Check Error Logs:**
   - PHP: `C:\xampp\php\logs\php_error_log`
   - Application: `logs/` directory

2. **Enable Debug Mode:**
   - Email: `Email_sender.php` line 80
   - Shows detailed SMTP communication

3. **Test Components:**
   - PDF: `/receivables/invoices/pdf/1`
   - Email: `/test_email.php?to=your-email@example.com`

---

**Status: ✅ READY TO USE**

Configure email settings and start testing!

