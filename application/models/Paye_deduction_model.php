<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paye_deduction_model extends Base_Model {
    protected $table = 'paye_deductions';
    
    public function getByPeriod($period) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE period = ?
                 ORDER BY employee_id",
                [$period]
            );
        } catch (Exception $e) {
            error_log('Paye_deduction_model getByPeriod error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getByEmployee($employeeId, $limit = 12) {
        try {
            $limit = intval($limit);
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE employee_id = ?
                 ORDER BY period DESC
                 LIMIT " . $limit,
                [$employeeId]
            );
        } catch (Exception $e) {
            error_log('Paye_deduction_model getByEmployee error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function calculatePAYE($grossIncome, $pensionContribution = 0, $nhfContribution = 0) {
        // Nigerian PAYE Tax Bands (2024)
        // First N300,000: 7%
        // Next N300,000: 11%
        // Next N500,000: 15%
        // Next N500,000: 19%
        // Next N1,600,000: 21%
        // Above N3,200,000: 24%
        
        // Consolidated Relief Allowance (CRA) = 20% of gross income or N200,000, whichever is higher + 1% of gross income
        $cra1 = max($grossIncome * 0.20, 200000);
        $cra2 = $grossIncome * 0.01;
        $totalCRA = $cra1 + $cra2;
        
        // Taxable income = Gross - Pension - NHF - CRA
        $taxableIncome = max(0, $grossIncome - $pensionContribution - $nhfContribution - $totalCRA);
        
        // Calculate tax using progressive bands
        $tax = 0;
        $remaining = $taxableIncome;
        
        if ($remaining > 3200000) {
            $tax += ($remaining - 3200000) * 0.24;
            $remaining = 3200000;
        }
        if ($remaining > 1600000) {
            $tax += ($remaining - 1600000) * 0.21;
            $remaining = 1600000;
        }
        if ($remaining > 1100000) {
            $tax += ($remaining - 1100000) * 0.19;
            $remaining = 1100000;
        }
        if ($remaining > 600000) {
            $tax += ($remaining - 600000) * 0.15;
            $remaining = 600000;
        }
        if ($remaining > 300000) {
            $tax += ($remaining - 300000) * 0.11;
            $remaining = 300000;
        }
        if ($remaining > 0) {
            $tax += $remaining * 0.07;
        }
        
        // Minimum tax: 1% of gross income
        $minimumTax = $grossIncome * 0.01;
        
        $payeAmount = max($tax, $minimumTax);
        
        return [
            'gross_income' => $grossIncome,
            'pension_contribution' => $pensionContribution,
            'nhf_contribution' => $nhfContribution,
            'consolidated_relief' => $totalCRA,
            'taxable_income' => $taxableIncome,
            'tax_calculated' => $tax,
            'minimum_tax' => $minimumTax,
            'paye_amount' => $payeAmount,
            'tax_bands' => [
                ['band' => '0-300,000', 'rate' => 7, 'amount' => min(300000, $taxableIncome) * 0.07],
                ['band' => '300,001-600,000', 'rate' => 11, 'amount' => $taxableIncome > 300000 ? min(300000, $taxableIncome - 300000) * 0.11 : 0],
                ['band' => '600,001-1,100,000', 'rate' => 15, 'amount' => $taxableIncome > 600000 ? min(500000, $taxableIncome - 600000) * 0.15 : 0],
                ['band' => '1,100,001-1,600,000', 'rate' => 19, 'amount' => $taxableIncome > 1100000 ? min(500000, $taxableIncome - 1100000) * 0.19 : 0],
                ['band' => '1,600,001-3,200,000', 'rate' => 21, 'amount' => $taxableIncome > 1600000 ? min(1600000, $taxableIncome - 1600000) * 0.21 : 0],
                ['band' => 'Above 3,200,000', 'rate' => 24, 'amount' => $taxableIncome > 3200000 ? ($taxableIncome - 3200000) * 0.24 : 0]
            ]
        ];
    }
    
    public function create($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Paye_deduction_model create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalByPeriod($period) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COALESCE(SUM(paye_amount), 0) as total, COUNT(*) as count
                 FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE period = ?",
                [$period]
            );
            return [
                'total_paye' => floatval($result['total'] ?? 0),
                'employee_count' => intval($result['count'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log('Paye_deduction_model getTotalByPeriod error: ' . $e->getMessage());
            return ['total_paye' => 0, 'employee_count' => 0];
        }
    }
}



