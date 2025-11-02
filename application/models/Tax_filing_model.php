<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tax_filing_model extends Base_Model {
    protected $table = 'tax_filings';
    
    public function getByTaxTypeAndPeriod($taxType, $period) {
        return $this->db->fetchOne(
            "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
             WHERE tax_type = ? AND period_covered = ?",
            [$taxType, $period]
        );
    }
    
    public function getOverdueFilings() {
        try {
            return $this->db->fetchAll(
                "SELECT tf.*, td.deadline_date 
                 FROM `" . $this->db->getPrefix() . $this->table . "` tf
                 LEFT JOIN `" . $this->db->getPrefix() . "tax_deadlines` td 
                 ON tf.tax_type = td.tax_type AND tf.period_covered = td.period_covered
                 WHERE tf.status != 'filed' 
                 AND td.deadline_date < CURDATE()
                 ORDER BY td.deadline_date ASC"
            );
        } catch (Exception $e) {
            error_log('Tax_filing_model getOverdueFilings error: ' . $e->getMessage());
            return [];
        }
    }
}

