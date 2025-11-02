<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tariff_model extends Base_Model {
    protected $table = 'tariffs';
    
    public function getActiveByProvider($providerId, $date = null) {
        try {
            $date = $date ?: date('Y-m-d');
            $result = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE provider_id = ? 
                 AND is_active = 1
                 AND effective_date <= ?
                 AND (expiry_date IS NULL OR expiry_date >= ?)
                 ORDER BY effective_date DESC
                 LIMIT 1",
                [$providerId, $date, $date]
            );
            return $result;
        } catch (Exception $e) {
            error_log('Tariff_model getActiveByProvider error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function calculateBillAmount($tariff, $consumption, $fixedCharge = 0, $previousBalance = 0) {
        try {
            $structure = json_decode($tariff['structure_json'] ?? '{}', true);
            if (!$structure) {
                return false;
            }
            
            $fixedCharge = $structure['fixed_charge'] ?? $fixedCharge;
            $variableRate = $structure['variable_rate'] ?? 0;
            $demandCharge = $structure['demand_charge'] ?? 0;
            $taxRate = $structure['tax_rate'] ?? 0;
            
            // Handle tiered pricing if exists
            $variableCharge = 0;
            if (isset($structure['tiered_rates']) && is_array($structure['tiered_rates'])) {
                $remainingConsumption = $consumption;
                foreach ($structure['tiered_rates'] as $tier) {
                    $tierMax = $tier['max'] ?? PHP_INT_MAX;
                    $tierRate = $tier['rate'] ?? 0;
                    $tierConsumption = min($remainingConsumption, $tierMax);
                    $variableCharge += $tierConsumption * $tierRate;
                    $remainingConsumption -= $tierConsumption;
                    if ($remainingConsumption <= 0) break;
                }
            } else {
                $variableCharge = $consumption * $variableRate;
            }
            
            $subtotal = $fixedCharge + $variableCharge + $demandCharge + $previousBalance;
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;
            
            return [
                'fixed_charge' => $fixedCharge,
                'variable_charge' => $variableCharge,
                'demand_charge' => $demandCharge,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'total_amount' => $total
            ];
        } catch (Exception $e) {
            error_log('Tariff_model calculateBillAmount error: ' . $e->getMessage());
            return false;
        }
    }
}

