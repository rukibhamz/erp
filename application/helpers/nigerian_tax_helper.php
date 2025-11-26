<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nigerian PAYE Tax Calculator
 * Based on Nigerian tax laws (2024)
 */

if (!function_exists('calculate_nigerian_paye')) {
    /**
     * Calculate Nigerian PAYE (Pay As You Earn) tax
     * 
     * @param float $annualIncome Annual gross income
     * @param float $reliefAllowance Consolidated relief allowance (default: 20% of gross or ₦200,000, whichever is higher)
     * @return array Array containing tax breakdown
     */
    function calculate_nigerian_paye($annualIncome, $reliefAllowance = null) {
        // Calculate Consolidated Relief Allowance if not provided
        if ($reliefAllowance === null) {
            $reliefAllowance = max(($annualIncome * 0.20) + 200000, 200000);
        }
        
        // Taxable income
        $taxableIncome = $annualIncome - $reliefAllowance;
        
        if ($taxableIncome <= 0) {
            return [
                'annual_income' => $annualIncome,
                'relief_allowance' => $reliefAllowance,
                'taxable_income' => 0,
                'annual_tax' => 0,
                'monthly_tax' => 0
            ];
        }
        
        // Nigerian PAYE Tax Bands (2024)
        $taxBands = [
            ['limit' => 300000, 'rate' => 0.07],      // First ₦300,000 at 7%
            ['limit' => 300000, 'rate' => 0.11],      // Next ₦300,000 at 11%
            ['limit' => 500000, 'rate' => 0.15],      // Next ₦500,000 at 15%
            ['limit' => 500000, 'rate' => 0.19],      // Next ₦500,000 at 19%
            ['limit' => 1600000, 'rate' => 0.21],     // Next ₦1,600,000 at 21%
            ['limit' => PHP_INT_MAX, 'rate' => 0.24]  // Above ₦3,200,000 at 24%
        ];
        
        $tax = 0;
        $remaining = $taxableIncome;
        
        foreach ($taxBands as $band) {
            if ($remaining <= 0) break;
            
            $taxableAtThisRate = min($remaining, $band['limit']);
            $tax += $taxableAtThisRate * $band['rate'];
            $remaining -= $taxableAtThisRate;
        }
        
        return [
            'annual_income' => $annualIncome,
            'relief_allowance' => $reliefAllowance,
            'taxable_income' => $taxableIncome,
            'annual_tax' => $tax,
            'monthly_tax' => $tax / 12
        ];
    }
}

if (!function_exists('calculate_nigerian_pension')) {
    /**
     * Calculate Nigerian Pension contribution
     * Employee contributes 8% of monthly basic, housing and transport allowances
     * 
     * @param float $monthlyBasic Monthly basic salary
     * @param float $housingAllowance Monthly housing allowance
     * @param float $transportAllowance Monthly transport allowance
     * @return float Employee pension contribution
     */
    function calculate_nigerian_pension($monthlyBasic, $housingAllowance = 0, $transportAllowance = 0) {
        $pensionableIncome = $monthlyBasic + $housingAllowance + $transportAllowance;
        return $pensionableIncome * 0.08; // 8% employee contribution
    }
}

if (!function_exists('calculate_nigerian_nhf')) {
    /**
     * Calculate Nigerian National Housing Fund (NHF) contribution
     * 2.5% of basic salary
     * 
     * @param float $monthlyBasic Monthly basic salary
     * @return float NHF contribution
     */
    function calculate_nigerian_nhf($monthlyBasic) {
        return $monthlyBasic * 0.025; // 2.5% of basic salary
    }
}

if (!function_exists('calculate_nigerian_nsitf')) {
    /**
     * Calculate Nigerian NSITF (Nigeria Social Insurance Trust Fund)
     * 1% of total emoluments (employer pays, not deducted from employee)
     * 
     * @param float $totalEmoluments Total monthly emoluments
     * @return float NSITF contribution
     */
    function calculate_nigerian_nsitf($totalEmoluments) {
        return $totalEmoluments * 0.01; // 1% employer contribution
    }
}

if (!function_exists('calculate_nigerian_itf')) {
    /**
     * Calculate Nigerian ITF (Industrial Training Fund)
     * 1% of annual payroll (employer pays, not deducted from employee)
     * 
     * @param float $annualPayroll Annual total payroll
     * @return float ITF contribution
     */
    function calculate_nigerian_itf($annualPayroll) {
        return $annualPayroll * 0.01; // 1% employer contribution
    }
}

if (!function_exists('calculate_monthly_deductions')) {
    /**
     * Calculate all monthly deductions for a Nigerian employee
     * 
     * @param float $monthlyBasic Monthly basic salary
     * @param float $housingAllowance Monthly housing allowance
     * @param float $transportAllowance Monthly transport allowance
     * @param array $otherAllowances Other allowances
     * @return array Deductions breakdown
     */
    function calculate_monthly_deductions($monthlyBasic, $housingAllowance = 0, $transportAllowance = 0, $otherAllowances = []) {
        // Calculate gross monthly income
        $grossMonthly = $monthlyBasic + $housingAllowance + $transportAllowance + array_sum($otherAllowances);
        $annualIncome = $grossMonthly * 12;
        
        // Calculate PAYE
        $payeData = calculate_nigerian_paye($annualIncome);
        
        // Calculate Pension (8% of basic + housing + transport)
        $pension = calculate_nigerian_pension($monthlyBasic, $housingAllowance, $transportAllowance);
        
        // Calculate NHF (2.5% of basic)
        $nhf = calculate_nigerian_nhf($monthlyBasic);
        
        // Total deductions
        $totalDeductions = $payeData['monthly_tax'] + $pension + $nhf;
        
        // Net pay
        $netPay = $grossMonthly - $totalDeductions;
        
        return [
            'gross_pay' => $grossMonthly,
            'deductions' => [
                'paye' => $payeData['monthly_tax'],
                'pension' => $pension,
                'nhf' => $nhf
            ],
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
            'paye_details' => $payeData
        ];
    }
}
