<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Install_default_coa extends CI_Migration {

    public function up() {
        // Define the default Chart of Accounts
        $accounts = [
            // ASSETS (1000-1999)
            // Current Assets
            [
                'account_code' => '1000',
                'account_name' => 'Cash on Hand',
                'account_type' => 'Assets',
                'account_category' => 'Cash & Bank',
                'description' => 'Petty cash and physical currency',
                'is_system_account' => 1
            ],
            [
                'account_code' => '1010',
                'account_name' => 'Bank Account - Main',
                'account_type' => 'Assets',
                'account_category' => 'Cash & Bank',
                'description' => 'Primary business bank account',
                'is_system_account' => 0
            ],
            [
                'account_code' => '1100',
                'account_name' => 'Accounts Receivable',
                'account_type' => 'Assets',
                'account_category' => 'Accounts Receivable',
                'description' => 'Unpaid customer invoices',
                'is_system_account' => 1
            ],
            [
                'account_code' => '1200',
                'account_name' => 'Inventory Asset',
                'account_type' => 'Assets',
                'account_category' => 'Inventory',
                'description' => 'Value of goods held for sale',
                'is_system_account' => 1
            ],
            
            // Fixed Assets
            [
                'account_code' => '1500',
                'account_name' => 'Furniture & Equipment',
                'account_type' => 'Assets',
                'account_category' => 'Fixed Assets',
                'description' => 'Office furniture and equipment',
                'is_system_account' => 0
            ],
            
            // LIABILITIES (2000-2999)
            // Current Liabilities
            [
                'account_code' => '2000',
                'account_name' => 'Accounts Payable',
                'account_type' => 'Liabilities',
                'account_category' => 'Accounts Payable',
                'description' => 'Unpaid vendor bills',
                'is_system_account' => 1
            ],
            [
                'account_code' => '2100',
                'account_name' => 'Sales Tax Payable',
                'account_type' => 'Liabilities',
                'account_category' => 'Current Liabilities',
                'description' => 'Sales tax collected but not yet paid',
                'is_system_account' => 1
            ],
            [
                'account_code' => '2200',
                'account_name' => 'Payroll Liabilities',
                'account_type' => 'Liabilities',
                'account_category' => 'Current Liabilities',
                'description' => 'Wages and taxes withheld',
                'is_system_account' => 1
            ],
            
            // EQUITY (3000-3999)
            [
                'account_code' => '3000',
                'account_name' => 'Owner\'s Equity',
                'account_type' => 'Equity',
                'account_category' => 'Equity',
                'description' => 'Owner\'s investment in the business',
                'is_system_account' => 1
            ],
            [
                'account_code' => '3900',
                'account_name' => 'Retained Earnings',
                'account_type' => 'Equity',
                'account_category' => 'Equity',
                'description' => 'Cumulative net income retained',
                'is_system_account' => 1
            ],
            
            // REVENUE (4000-4999)
            [
                'account_code' => '4000',
                'account_name' => 'Sales Revenue',
                'account_type' => 'Revenue',
                'account_category' => 'Income',
                'description' => 'Income from sales of goods/services',
                'is_system_account' => 1
            ],
            [
                'account_code' => '4100',
                'account_name' => 'Service Income',
                'account_type' => 'Revenue',
                'account_category' => 'Income',
                'description' => 'Income from services rendered',
                'is_system_account' => 0
            ],
            [
                'account_code' => '4900',
                'account_name' => 'Other Income',
                'account_type' => 'Revenue',
                'account_category' => 'Other Income',
                'description' => 'Interest, refunds, etc.',
                'is_system_account' => 0
            ],
            
            // EXPENSES (5000-9999)
            [
                'account_code' => '5000',
                'account_name' => 'Cost of Goods Sold',
                'account_type' => 'Expenses',
                'account_category' => 'Cost of Goods Sold',
                'description' => 'Direct costs of goods sold',
                'is_system_account' => 1
            ],
            [
                'account_code' => '6000',
                'account_name' => 'Rent Expense',
                'account_type' => 'Expenses',
                'account_category' => 'Expense',
                'description' => 'Office or building rent',
                'is_system_account' => 0
            ],
            [
                'account_code' => '6100',
                'account_name' => 'Utilities Expense',
                'account_type' => 'Expenses',
                'account_category' => 'Expense',
                'description' => 'Electricity, water, internet',
                'is_system_account' => 0
            ],
            [
                'account_code' => '7000',
                'account_name' => 'Payroll Expense',
                'account_type' => 'Expenses',
                'account_category' => 'Expense',
                'description' => 'Employee salaries and wages',
                'is_system_account' => 1
            ]
        ];

        // Insert accounts if they don't exist
        foreach ($accounts as $account) {
            // Check if account code already exists
            $exists = $this->db->where('account_code', $account['account_code'])
                              ->get('accounts')
                              ->num_rows() > 0;
            
            if (!$exists) {
                $this->db->insert('accounts', [
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'account_type' => $account['account_type'],
                    'account_category' => $account['account_category'],
                    'description' => $account['description'],
                    'is_system_account' => $account['is_system_account'],
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    public function down() {
        // We generally don't want to delete accounts in down() as they might have transactions
        // But for development, we could remove system accounts
        // $this->db->where('is_system_account', 1)->delete('accounts');
    }
}
