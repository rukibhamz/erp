<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration to standardize booking tiers from legacy names to basic/standard/premium
 */
class Migration_Standardize_booking_tiers {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function up() {
        $prefix = $this->db->getPrefix();

        // 1. Update bookings table values
        $this->db->query("UPDATE `{$prefix}bookings` SET equipment_tier = 'basic' WHERE equipment_tier = 'light'");
        $this->db->query("UPDATE `{$prefix}bookings` SET equipment_tier = 'standard' WHERE equipment_tier = 'medium'");
        $this->db->query("UPDATE `{$prefix}bookings` SET equipment_tier = 'premium' WHERE equipment_tier = 'heavy'");

        // 2. Update bookable_config pricing_rules JSON
        $configs = $this->db->fetchAll("SELECT * FROM `{$prefix}bookable_config` WHERE pricing_rules IS NOT NULL AND pricing_rules != ''");
        
        foreach ($configs as $config) {
            $rules = json_decode($config['pricing_rules'], true);
            if (empty($rules) || !isset($rules['per_person_rates'])) continue;
            
            $changed = false;
            foreach ($rules['per_person_rates'] as $type => &$data) {
                if (isset($data['equipment_tiers'])) {
                    $tiers = $data['equipment_tiers'];
                    $newTiers = [];
                    
                    // Map legacy to new
                    if (isset($tiers['light'])) { $newTiers['basic'] = $tiers['light']; $changed = true; }
                    if (isset($tiers['medium'])) { $newTiers['standard'] = $tiers['medium']; $changed = true; }
                    if (isset($tiers['heavy'])) { $newTiers['premium'] = $tiers['heavy']; $changed = true; }
                    
                    // Keep already standardized or other keys
                    foreach ($tiers as $k => $v) {
                        if (!in_array($k, ['light', 'medium', 'heavy']) && !isset($newTiers[$k])) {
                            $newTiers[$k] = $v;
                        }
                    }
                    
                    if ($changed) {
                        $data['equipment_tiers'] = $newTiers;
                    }
                }
            }
            
            if ($changed) {
                $this->db->update('bookable_config', 
                    ['pricing_rules' => json_encode($rules)], 
                    'id = ?', 
                    [$config['id']]
                );
            }
        }
    }

    public function down() {
        $prefix = $this->db->getPrefix();
        // Reverse mapping if absolutely necessary
        $this->db->query("UPDATE `{$prefix}bookings` SET equipment_tier = 'light' WHERE equipment_tier = 'basic'");
        $this->db->query("UPDATE `{$prefix}bookings` SET equipment_tier = 'medium' WHERE equipment_tier = 'standard'");
        $this->db->query("UPDATE `{$prefix}bookings` SET equipment_tier = 'heavy' WHERE equipment_tier = 'premium'");
    }
}
