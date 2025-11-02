<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cit_calculation_model extends Base_Model {
    protected $table = 'cit_calculations';
    
    public function getById($id) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE id = ?",
                [$id]
            );
        } catch (Exception $e) {
            error_log('Cit_calculation_model getById error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByYear($year) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE year = ?",
                [$year]
            );
        } catch (Exception $e) {
            error_log('Cit_calculation_model getByYear error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAll($limit = null, $offset = 0, $orderBy = null) {
        try {
            $orderBy = $orderBy ?: 'year DESC';
            return parent::getAll($limit, $offset, $orderBy);
        } catch (Exception $e) {
            error_log('Cit_calculation_model getAll error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function create($data) {
        try {
            return $this->db->insert($this->table, $data);
        } catch (Exception $e) {
            error_log('Cit_calculation_model create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function calculateCIT($year, $profitBeforeTax, $adjustments = [], $capitalAllowances = 0, $taxReliefs = 0) {
        $totalAdjustments = 0;
        foreach ($adjustments as $adj) {
            $totalAdjustments += floatval($adj['amount'] ?? 0);
        }
        
        $assessableProfit = floatval($profitBeforeTax) + $totalAdjustments;
        $citAmount = $assessableProfit * 0.30; // 30% CIT rate
        
        // Minimum tax calculation (0.5% of turnover or N500,000)
        // Note: This would need turnover from accounting records
        
        return [
            'profit_before_tax' => floatval($profitBeforeTax),
            'total_adjustments' => $totalAdjustments,
            'assessable_profit' => $assessableProfit,
            'cit_amount' => $citAmount,
            'capital_allowances' => floatval($capitalAllowances),
            'tax_reliefs' => floatval($taxReliefs),
            'final_tax_liability' => max($citAmount, 0) // Would compare with minimum tax
        ];
    }
}

