# Invoice PDF Generation and Email System - Setup Guide

## Overview

This system provides complete PDF invoice generation, viewing, downloading, and email functionality with proper security measures.

## Features

✅ **Professional PDF Generation**
- Uses Dompdf (preferred), TCPDF, or HTML fallback
- Includes company logo, invoice details, line items, totals
- Responsive design with proper formatting

✅ **Secure Access**
- Authentication required for all endpoints
- Directory protection with .htaccess
- Input validation and SQL injection prevention

✅ **Browser Viewing**
- View invoices directly in browser using `<embed>` or `<iframe>`
- Proper Content-Type headers for PDF display

✅ **Download Functionality**
- Force download with proper Content-Disposition headers
- Sanitized filenames

✅ **Email Integration**
- Send invoices via email with PDF attachments
- HTML email templates
- PHPMailer SMTP support with proper error handling

## Installation

### 1. Install Dependencies

Run Composer to install Dompdf:

```bash
composer install
```

Or manually install Dompdf:

```bash
composer require dompdf/dompdf
```

**Note:** PHPMailer is already included in your project.

### 2. Directory Structure

The following structure is created automatically:

```
/invoices/          # PDF storage (protected by .htaccess)
view_invoice.php     # Browser viewing endpoint
download_invoice.php # Download endpoint
send_invoice_email.php # Email sending endpoint
```

### 3. Configuration

#### Email/SMTP Configuration

Add email settings to your config file (`application/config/config.installed.php` or `application/config/config.php`):

```php
return [
    // ... other config ...
    'email' => [
        'smtp_host' => 'smtp.gmail.com',        // SMTP server
        'smtp_port' => 587,                     // Port (587 for TLS, 465 for SSL)
        'smtp_username' => 'your-email@gmail.com',
        'smtp_password' => 'your-app-password',  // Use App Password for Gmail
        'smtp_encryption' => 'tls',             // 'tls' or 'ssl'
        'from_email' => 'noreply@yourcompany.com',
        'from_name' => 'Your Company Name'
    ]
];
```

#### Gmail Setup

For Gmail, you need to:
1. Enable 2-Factor Authentication
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the App Password (not your regular password) in `smtp_password`

#### Other SMTP Providers

**Outlook/Hotmail:**
```php
'smtp_host' => 'smtp-mail.outlook.com',
'smtp_port' => 587,
'smtp_encryption' => 'tls',
```

**Custom SMTP:**
```php
'smtp_host' => 'mail.yourdomain.com',
'smtp_port' => 587, // or 465 for SSL
'smtp_encryption' => 'tls', // or 'ssl'
```

## Usage

### 1. View Invoice in Browser

```html
<!-- Using iframe -->
<iframe src="view_invoice.php?id=123" width="100%" height="600px"></iframe>

<!-- Using embed -->
<embed src="view_invoice.php?id=123" type="application/pdf" width="100%" height="600px" />

<!-- Direct link -->
<a href="view_invoice.php?id=123" target="_blank">View Invoice</a>
```

### 2. Download Invoice

```html
<a href="download_invoice.php?id=123" download>Download Invoice</a>
```

Or via JavaScript:
```javascript
window.location.href = 'download_invoice.php?id=123';
```

### 3. Send Invoice via Email

**Using HTML Form:**
```html
<form id="sendInvoiceForm" method="POST" action="send_invoice_email.php">
    <input type="hidden" name="invoice_id" value="123">
    <input type="email" name="recipient_email" placeholder="Recipient Email" required>
    <textarea name="message" placeholder="Optional message"></textarea>
    <button type="submit">Send Invoice</button>
</form>

<script>
document.getElementById('sendInvoiceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    const response = await fetch('send_invoice_email.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    if (result.success) {
        alert('Invoice sent successfully!');
    } else {
        alert('Error: ' + result.message);
    }
});
</script>
```

**Using AJAX:**
```javascript
fetch('send_invoice_email.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        invoice_id: 123,
        recipient_email: 'customer@example.com',
        message: 'Please find attached your invoice.'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Invoice sent!');
    } else {
        console.error('Error:', data.message);
    }
});
```

### 4. Generate PDF Programmatically

```php
// Load required files
require_once 'application/core/Database.php';
require_once 'application/models/Invoice_model.php';
require_once 'application/models/Entity_model.php';
require_once 'application/libraries/Pdf_generator.php';

// Get invoice data
$invoiceModel = new Invoice_model();
$entityModel = new Entity_model();

$invoice = $invoiceModel->getWithCustomer($invoiceId);
$items = $invoiceModel->getItems($invoiceId);
$companyInfo = $entityModel->getAll()[0] ?? [];

// Prepare customer data
$customer = [
    'company_name' => $invoice['company_name'],
    'address' => $invoice['address'],
    // ... other fields
];

// Generate PDF
$pdfGenerator = new Pdf_generator($companyInfo);
$pdfContent = $pdfGenerator->generateInvoice($invoice, $items, $customer);

// Save to file
$filename = 'invoices/invoice-' . $invoice['invoice_number'] . '.pdf';
file_put_contents($filename, $pdfContent);
```

## Security Features

### 1. Authentication
- All endpoints check for valid session
- User must be logged in to access invoices

### 2. Input Validation
- Invoice IDs are validated as integers
- Email addresses are validated
- SQL injection prevention through prepared statements

### 3. Directory Protection
- `/invoices/` directory protected by `.htaccess`
- Direct file access denied
- Files only accessible through PHP endpoints

### 4. File Access Control
- Users can only access invoices they have permission to view
- Additional permission checks can be added in endpoints

## Troubleshooting

### PDF Not Displaying in Browser

1. **Check if Dompdf is installed:**
   ```bash
   composer show dompdf/dompdf
   ```

2. **Check browser console for errors**

3. **Verify Content-Type headers are set correctly**

4. **Try downloading instead of viewing**

### Email Not Sending

1. **Check SMTP Configuration:**
   - Verify SMTP credentials
   - Check port and encryption settings
   - Test with a simple email first

2. **Enable Debug Mode:**
   In `application/helpers/email_helper.php`, uncomment:
   ```php
   $mail->SMTPDebug = 2;
   ```

3. **Check Error Logs:**
   ```php
   error_log("PHPMailer error: " . $e->getMessage());
   ```

4. **Common Issues:**
   - **Gmail:** Use App Password, not regular password
   - **Port 587:** Use TLS encryption
   - **Port 465:** Use SSL encryption
   - **Firewall:** Ensure port is not blocked

### PDF Generation Errors

1. **Check if library is loaded:**
   ```php
   if (class_exists('Dompdf\Dompdf')) {
       echo "Dompdf loaded";
   }
   ```

2. **Check file permissions:**
   ```bash
   chmod 755 invoices/
   ```

3. **Check memory limit:**
   ```php
   ini_set('memory_limit', '256M');
   ```

### Download Headers Not Working

1. **Ensure no output before headers:**
   - Remove any `echo`, `print`, or whitespace before `header()` calls

2. **Check for errors:**
   - Enable error reporting to catch issues

3. **Verify Content-Disposition header:**
   ```php
   header('Content-Disposition: attachment; filename="invoice.pdf"');
   ```

## API Reference

### view_invoice.php

**Parameters:**
- `id` (required): Invoice ID

**Returns:**
- PDF content with `Content-Type: application/pdf` for browser display

**Example:**
```
GET view_invoice.php?id=123
```

### download_invoice.php

**Parameters:**
- `id` (required): Invoice ID

**Returns:**
- PDF content with `Content-Disposition: attachment` to force download

**Example:**
```
GET download_invoice.php?id=123
```

### send_invoice_email.php

**Method:** POST

**Parameters:**
- `invoice_id` (required): Invoice ID
- `recipient_email` (required): Valid email address
- `message` (optional): Custom message to include in email

**Returns:**
- JSON response: `{"success": true/false, "message": "..."}`

**Example:**
```
POST send_invoice_email.php
Content-Type: application/x-www-form-urlencoded

invoice_id=123&recipient_email=customer@example.com&message=Thank you
```

## Best Practices

1. **Always validate user permissions** before allowing access to invoices
2. **Use HTTPS** in production for secure email transmission
3. **Store SMTP credentials securely** (consider environment variables)
4. **Monitor error logs** for email sending issues
5. **Test email functionality** after SMTP configuration changes
6. **Use App Passwords** for Gmail instead of regular passwords
7. **Set appropriate file permissions** on the invoices directory
8. **Regularly backup** generated PDFs if needed

## Support

For issues or questions:
1. Check error logs in `logs/` directory
2. Enable debug mode in PHPMailer
3. Verify all dependencies are installed
4. Check SMTP configuration matches your provider's requirements

