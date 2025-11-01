<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Currency_model extends Base_Model {
    protected $table = 'currencies';
    
    public function getBaseCurrency() {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE is_base = 1 AND status = 'active' LIMIT 1"
            );
        } catch (Exception $e) {
            error_log('Currency_model getBaseCurrency error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getActive() {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE status = 'active' 
                 ORDER BY is_base DESC, currency_code"
            );
        } catch (Exception $e) {
            error_log('Currency_model getActive error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getExchangeRate($fromCurrency, $toCurrency, $date = null) {
        try {
            if ($fromCurrency === $toCurrency) {
                return 1.0;
            }
            
            $date = $date ?: date('Y-m-d');
            
            // Try to get rate for specific date
            $result = $this->db->fetchOne(
                "SELECT rate FROM `" . $this->db->getPrefix() . "currency_rates` 
                 WHERE from_currency = ? AND to_currency = ? AND rate_date = ? 
                 ORDER BY rate_date DESC LIMIT 1",
                [$fromCurrency, $toCurrency, $date]
            );
            
            if ($result) {
                return floatval($result['rate']);
            }
            
            // Fallback to latest rate
            $result = $this->db->fetchOne(
                "SELECT rate FROM `" . $this->db->getPrefix() . "currency_rates` 
                 WHERE from_currency = ? AND to_currency = ? 
                 ORDER BY rate_date DESC LIMIT 1",
                [$fromCurrency, $toCurrency]
            );
            
            if ($result) {
                return floatval($result['rate']);
            }
            
            // If no rate found, get from currency table
            $from = $this->getByCode($fromCurrency);
            $to = $this->getByCode($toCurrency);
            
            if ($from && $to) {
                $fromRate = floatval($from['exchange_rate']);
                $toRate = floatval($to['exchange_rate']);
                
                if ($fromRate > 0) {
                    return $toRate / $fromRate;
                }
            }
            
            return 1.0; // Default 1:1 if not found
        } catch (Exception $e) {
            error_log('Currency_model getExchangeRate error: ' . $e->getMessage());
            return 1.0;
        }
    }
    
    public function getByCode($code) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE currency_code = ? AND status = 'active'",
                [$code]
            );
        } catch (Exception $e) {
            error_log('Currency_model getByCode error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function convertAmount($amount, $fromCurrency, $toCurrency, $date = null) {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);
        return floatval($amount) * $rate;
    }
    
    public function updateExchangeRate($fromCurrency, $toCurrency, $rate, $date = null) {
        try {
            $date = $date ?: date('Y-m-d');
            
            // Check if rate exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM `" . $this->db->getPrefix() . "currency_rates` 
                 WHERE from_currency = ? AND to_currency = ? AND rate_date = ?",
                [$fromCurrency, $toCurrency, $date]
            );
            
            if ($existing) {
                // Update existing rate
                $this->db->query(
                    "UPDATE `" . $this->db->getPrefix() . "currency_rates` 
                     SET rate = ? WHERE id = ?",
                    [$rate, $existing['id']]
                );
                return true;
            } else {
                // Insert new rate
                $this->db->query(
                    "INSERT INTO `" . $this->db->getPrefix() . "currency_rates` 
                     (from_currency, to_currency, rate, rate_date, created_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    [$fromCurrency, $toCurrency, $rate, $date]
                );
                return true;
            }
        } catch (Exception $e) {
            error_log('Currency_model updateExchangeRate error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function setBaseCurrency($currencyId) {
        try {
            // Unset all base currencies
            $this->db->query("UPDATE `" . $this->db->getPrefix() . $this->table . "` SET is_base = 0");
            
            // Set new base
            return $this->update($currencyId, ['is_base' => 1, 'exchange_rate' => 1.0]);
        } catch (Exception $e) {
            error_log('Currency_model setBaseCurrency error: ' . $e->getMessage());
            return false;
        }
    }
}

