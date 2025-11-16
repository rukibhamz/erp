# Test Files

This directory contains test and utility scripts for the ERP system.

## Files

### test_email.php
Standalone test script for email functionality. Tests SMTP configuration and email sending.
- **Usage:** `php tests/test_email.php?to=your-email@example.com`
- **Note:** Requires authentication (user must be logged in)

### test_permission_system.php
Comprehensive permission system testing script. Tests all roles and their permissions.
- **Usage:** `php tests/test_permission_system.php`
- **Note:** Run after migrations to verify permission system works correctly

### Legacy Invoice Files
These are legacy standalone endpoints that have been replaced by controller methods:
- `view_invoice_legacy.php` - Legacy PDF viewer (replaced by `Receivables::pdfInvoice()`)
- `download_invoice_legacy.php` - Legacy PDF downloader (replaced by `Receivables::downloadInvoice()`)
- `send_invoice_email_legacy.php` - Legacy email sender (replaced by `Receivables::sendInvoiceEmail()`)

### debug.php
Debug utility script from install folder.

## Important Notes

⚠️ **These files are for testing and development only.**
- Do not expose these files in production
- Some files may require authentication
- Legacy files are kept for reference only

