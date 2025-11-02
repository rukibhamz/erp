<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_settings_model extends Base_Model {
    protected $table = 'tax_settings';
    
    public function getSettings() {
        try {
            $settings = $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 ORDER BY id DESC LIMIT 1"
            );
            
            if (!$settings) {
                // Return default settings
                return [
                    'company_tin' => '',
                    'company_registration_number' => '',
                    'tax_office' => '',
                    'accounting_year_end_month' => 12,
                    'vat_registration_number' => '',
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log('Tax_settings_model getSettings error: ' . $e->getMessage());
            // Return default settings on error
            return [
                'company_tin' => '',
                'company_registration_number' => '',
                'tax_office' => '',
                'accounting_year_end_month' => 12,
                'vat_registration_number' => '',
            ];
        }
    }
    
    public function updateSettings($data) {
        $existing = $this->getSettings();
        if (isset($existing['id'])) {
            return $this->db->update($this->table, $data, "id = ?", [$existing['id']]);
        } else {
            return $this->db->insert($this->table, $data);
        }
    }
}

