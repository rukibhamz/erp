<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Budget_model extends Base_Model {
    protected $table = 'budgets';
    
    public function getByAccount($accountId, $financialYearId = null) {
        try {
            $sql = "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                    WHERE account_id = ?";
            $params = [$accountId];
            
            if ($financialYearId) {
                $sql .= " AND financial_year_id = ?";
                $params[] = $financialYearId;
            }
            
            $sql .= " ORDER BY financial_year_id DESC LIMIT 1";
            
            return $this->db->fetchOne($sql, $params);
        } catch (Exception $e) {
            error_log('Budget_model getByAccount error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getByFinancialYear($financialYearId) {
        try {
            return $this->db->fetchAll(
                "SELECT b.*, a.account_name, a.account_code, a.account_type
                 FROM `" . $this->db->getPrefix() . $this->table . "` b
                 JOIN `" . $this->db->getPrefix() . "accounts` a ON b.account_id = a.id
                 WHERE b.financial_year_id = ? AND b.status = 'active'
                 ORDER BY a.account_type, a.account_number, a.account_code",
                [$financialYearId]
            );
        } catch (Exception $e) {
            error_log('Budget_model getByFinancialYear error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getMonthlyAmount($budgetId, $month) {
        try {
            $months = [
                1 => 'january', 2 => 'february', 3 => 'march', 4 => 'april',
                5 => 'may', 6 => 'june', 7 => 'july', 8 => 'august',
                9 => 'september', 10 => 'october', 11 => 'november', 12 => 'december'
            ];
            
            $monthField = $months[$month] ?? 'january';
            $budget = $this->getById($budgetId);
            
            return $budget ? floatval($budget[$monthField] ?? 0) : 0;
        } catch (Exception $e) {
            error_log('Budget_model getMonthlyAmount error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function calculateActual($accountId, $month, $year, $financialYearId) {
        try {
            // Get actual expenses/revenue for the account in the given month
            $monthStart = sprintf('%04d-%02d-01', $year, $month);
            $monthEnd = sprintf('%04d-%02d-%02d', $year, $month, date('t', strtotime($monthStart)));
            
            $account = $this->loadModel('Account_model')->getById($accountId);
            if (!$account) return 0;
            
            // For expenses, sum debits; for revenue, sum credits
            $isExpense = in_array($account['account_type'], ['Expenses', 'Assets']);
            
            if ($isExpense) {
                $sql = "SELECT COALESCE(SUM(debit - credit), 0) as total
                        FROM `" . $this->db->getPrefix() . "transactions`
                        WHERE account_id = ? 
                        AND transaction_date >= ? AND transaction_date <= ?
                        AND status = 'posted'";
            } else {
                $sql = "SELECT COALESCE(SUM(credit - debit), 0) as total
                        FROM `" . $this->db->getPrefix() . "transactions`
                        WHERE account_id = ? 
                        AND transaction_date >= ? AND transaction_date <= ?
                        AND status = 'posted'";
            }
            
            $result = $this->db->fetchOne($sql, [$accountId, $monthStart, $monthEnd]);
            return $result ? floatval($result['total']) : 0;
        } catch (Exception $e) {
            error_log('Budget_model calculateActual error: ' . $e->getMessage());
            return 0;
        }
    }
    
    public function getBudgetVariance($budgetId, $month, $year) {
        try {
            $budget = $this->getById($budgetId);
            if (!$budget) {
                return ['budget' => 0, 'actual' => 0, 'variance' => 0, 'variance_percent' => 0];
            }
            
            $budgetAmount = $this->getMonthlyAmount($budgetId, $month);
            $actualAmount = $this->calculateActual($budget['account_id'], $month, $year, $budget['financial_year_id']);
            $variance = $actualAmount - $budgetAmount;
            $variancePercent = $budgetAmount > 0 ? ($variance / $budgetAmount) * 100 : 0;
            
            return [
                'budget' => $budgetAmount,
                'actual' => $actualAmount,
                'variance' => $variance,
                'variance_percent' => $variancePercent
            ];
        } catch (Exception $e) {
            error_log('Budget_model getBudgetVariance error: ' . $e->getMessage());
            return ['budget' => 0, 'actual' => 0, 'variance' => 0, 'variance_percent' => 0];
        }
    }
    
    private function loadModel($modelName) {
        require_once BASEPATH . 'models/' . $modelName . '.php';
        return new $modelName();
    }
}

