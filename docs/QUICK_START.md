# ERP System - Quick Start Guide

## Welcome! 🎉

This guide will help you get started with your ERP system in minutes.

---

## Initial Setup

### 1. Database Configuration

Ensure your database is set up:
```sql
CREATE DATABASE erp_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run migrations:
```bash
cd /path/to/erp
php index.php migrate
```

Or import SQL files manually:
```bash
mysql -u username -p erp_system < database/migrations/*.sql
```

### 2. Default Admin Account

**Username**: `admin`  
**Password**: `admin123`

⚠️ **IMPORTANT**: Change this immediately after first login!

**To change password**:
1. Login as admin
2. Go to **Profile → Change Password**
3. Enter new strong password
4. Save

### 3. Email Configuration

See [EMAIL_CONFIGURATION.md](EMAIL_CONFIGURATION.md) for detailed instructions.

Quick setup:
1. Go to **Settings → System Settings → Email**
2. Configure SMTP settings
3. Test email delivery
4. Save configuration

---

## Core Workflows

### Accounting Setup

#### 1. Review Chart of Accounts
- Navigate to **Accounting → Chart of Accounts**
- Verify default accounts are present
- Add custom accounts as needed
- Set account hierarchy (parent-child relationships)

**Required Accounts**:
- 1000: Cash
- 1200: Accounts Receivable
- 2100: Accounts Payable
- 4000: Revenue
- 6000: Expenses

#### 2. Set Opening Balances
- Go to **Accounting → Opening Balances**
- Enter beginning balances for all accounts
- Date should be start of fiscal year
- Ensure debits = credits

**Example**:
```
Cash (1000): $10,000 DR
Equity (3000): $10,000 CR
```

#### 3. Configure Tax Rates
- Navigate to **Tax → Tax Configuration**
- Set up VAT rate (e.g., 7.5%)
- Configure PAYE rates
- Set up WHT rates
- Configure tax groups

### User Management

#### 1. Create Users
- Go to **Settings → Users**
- Click **Add New User**
- Fill in details:
  - Full name
  - Email
  - Username
  - Password
- Assign role (Admin, Manager, Staff, Accountant)
- Save

#### 2. Set Permissions
- Edit user
- Go to **Permissions** tab
- Assign module permissions:
  - Read: View data
  - Create: Add new records
  - Update: Edit records
  - Delete: Remove records
- Save changes

**Recommended Permissions**:
- **Admin**: All permissions
- **Manager**: Read/Create/Update on most modules
- **Staff**: Read/Create on assigned modules
- **Accountant**: Full access to Accounting, Reports

### Customer & Supplier Setup

#### 1. Add Customers
- Navigate to **Receivables → Customers**
- Click **Add Customer**
- Fill in details:
  - Company name
  - Contact person
  - Email
  - Phone
  - Address
  - Payment terms
- Save

#### 2. Add Suppliers
- Navigate to **Payables → Suppliers**
- Click **Add Supplier**
- Fill in details (same as customers)
- Set payment terms
- Save

---

## Common Tasks

### Create an Invoice

1. **Receivables → Invoices → Create Invoice**
2. Select customer
3. Set invoice date and due date
4. Add line items:
   - Description
   - Quantity
   - Unit price
5. Set tax rate (if applicable)
6. Review totals
7. Save and send

**Accounting Impact**:
```
DR: Accounts Receivable
CR: Revenue
CR: VAT Payable (if applicable)
```

### Record a Payment

1. **Receivables → Invoices**
2. Find invoice
3. Click **Record Payment**
4. Enter:
   - Payment date
   - Amount
   - Payment method (Cash, Bank Transfer, etc.)
   - Reference number
5. Save

**Accounting Impact**:
```
DR: Cash/Bank
CR: Accounts Receivable
```

### Process Payroll

1. **Payroll → Process Payroll**
2. Select pay period
3. Review employee list
4. System calculates:
   - Basic salary
   - Allowances
   - PAYE (automatic)
   - Pension (automatic)
   - NHF (automatic)
5. Review totals
6. Post to accounting

**Accounting Impact**:
```
DR: Payroll Expense
CR: Cash (net pay)
CR: PAYE Payable
CR: Pension Payable
CR: NHF Payable
```

### Run Reports

1. **Reports → Financial Reports**
2. Select report type:
   - **Profit & Loss**: Income vs Expenses
   - **Balance Sheet**: Assets, Liabilities, Equity
   - **Trial Balance**: All account balances
   - **Cash Flow**: Cash movements
3. Set date range
4. Generate report
5. Export to PDF/Excel (optional)

---

## Module Overview

### Accounting
- **Chart of Accounts**: Manage account structure
- **Journal Entries**: Manual accounting entries
- **Ledger**: View account transactions
- **Reports**: Financial statements

### Receivables
- **Customers**: Customer database
- **Invoices**: Create and manage invoices
- **Payments**: Record customer payments
- **Aging Reports**: Outstanding receivables

### Payables
- **Suppliers**: Supplier database
- **Bills**: Record vendor bills
- **Payments**: Pay suppliers
- **Aging Reports**: Outstanding payables

### Inventory
- **Items**: Product/service catalog
- **Stock Levels**: Current inventory
- **Stock Adjustments**: Gains/losses
- **Stock Movements**: Transfers between locations

### Payroll
- **Employees**: Employee database
- **Payroll Runs**: Process salaries
- **Payslips**: Generate payslips
- **Tax Reports**: PAYE, Pension, NHF reports

### Fixed Assets
- **Asset Register**: Track all assets
- **Depreciation**: Calculate monthly depreciation
- **Disposal**: Record asset sales
- **Asset Reports**: Asset valuation

### Bookings
- **Spaces**: Manage bookable spaces
- **Bookings**: Create reservations
- **Check-in/Check-out**: Process guests
- **Revenue Reports**: Booking income

### POS (Point of Sale)
- **Sales**: Process retail sales
- **Terminals**: Manage POS terminals
- **Daily Reports**: Sales summaries

---

## Tips & Best Practices

### Data Entry

- ✅ Always double-check amounts before saving
- ✅ Use consistent naming conventions
- ✅ Add descriptions to all transactions
- ✅ Attach supporting documents (receipts, invoices)
- ✅ Review before posting to accounting
- ❌ Don't delete posted transactions (use reversals)
- ❌ Don't skip required fields
- ❌ Don't use special characters in names

### Month-End Procedures

1. **Reconcile bank accounts**
   - Compare bank statement to cash account
   - Record any missing transactions
   - Adjust for bank fees

2. **Review aged receivables/payables**
   - Follow up on overdue invoices
   - Schedule vendor payments
   - Write off bad debts (if approved)

3. **Run depreciation**
   - Fixed Assets → Calculate Depreciation
   - Review depreciation amounts
   - Post to accounting

4. **Generate financial reports**
   - Profit & Loss
   - Balance Sheet
   - Trial Balance
   - Cash Flow Statement

5. **Review for errors**
   - Check for unbalanced entries
   - Verify account balances
   - Investigate anomalies

### Backup

- **Automatic backups**: Daily at midnight
- **Manual backup**: Settings → Backup → Create Backup
- **Store backups off-site**: Cloud storage or external drive
- **Test restore procedure**: Quarterly
- **Retention**: Keep 30 days of daily backups

### Security

- ✅ Change default passwords immediately
- ✅ Use strong passwords (12+ characters, mixed case, numbers, symbols)
- ✅ Limit user permissions to minimum required
- ✅ Review activity logs regularly
- ✅ Keep software updated
- ✅ Enable 2FA for admin accounts (if available)
- ❌ Don't share passwords
- ❌ Don't use same password for multiple accounts
- ❌ Don't grant unnecessary permissions

---

## Getting Help

### Documentation
- **User Manual**: `docs/USER_MANUAL.md`
- **API Docs**: `docs/API.md`
- **Email Setup**: `docs/EMAIL_CONFIGURATION.md`
- **Troubleshooting**: `docs/TROUBLESHOOTING.md`

### Support
- **Email**: support@yourcompany.com
- **Phone**: +234-XXX-XXXX-XXX
- **Hours**: Monday-Friday 9AM-5PM WAT
- **Response Time**: Within 24 hours

### Common Issues

#### Can't login?
- Check username/password (case-sensitive)
- Clear browser cache and cookies
- Try different browser
- Reset password via "Forgot Password"
- Contact admin to verify account status

#### Email not sending?
- Verify SMTP settings in System Settings
- Check email logs for error messages
- Test with different email provider
- Ensure firewall allows SMTP ports
- See EMAIL_CONFIGURATION.md

#### Report not loading?
- Check date range (not too large)
- Verify data exists for selected period
- Clear browser cache
- Try different browser
- Check server error logs

#### Balance doesn't match?
- Run System Health Check
- Verify all transactions posted
- Check for unbalanced journal entries
- Recalculate balances (Accounting → Recalculate)
- Contact support if issue persists

---

## Keyboard Shortcuts

- **Ctrl + S**: Save current form
- **Ctrl + N**: New record (where applicable)
- **Esc**: Close modal/dialog
- **Ctrl + P**: Print current page
- **Ctrl + F**: Search/Filter

---

## Next Steps

### Week 1: Setup
1. ✅ Change admin password
2. ✅ Configure email settings
3. ✅ Add users and set permissions
4. ✅ Review Chart of Accounts
5. ✅ Add customers and suppliers

### Week 2: Data Entry
1. ✅ Enter opening balances
2. ✅ Create first invoice
3. ✅ Record first payment
4. ✅ Process first payroll
5. ✅ Run first reports

### Week 3: Operations
1. ✅ Daily transaction entry
2. ✅ Weekly reconciliation
3. ✅ Monthly reports
4. ✅ User training
5. ✅ Process optimization

### Month 1: Review
1. ✅ Month-end close
2. ✅ Financial review
3. ✅ User feedback
4. ✅ System optimization
5. ✅ Plan for next month

---

## Congratulations! 🎊

You're now ready to use your ERP system effectively. Remember:

- **Start small**: Master basic workflows first
- **Be consistent**: Enter data daily
- **Review regularly**: Check reports weekly
- **Ask for help**: Don't hesitate to contact support
- **Keep learning**: Explore advanced features

Welcome to your ERP system! 🚀
