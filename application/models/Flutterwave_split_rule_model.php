<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Flutterwave_split_rule_model extends Base_Model {
    protected $table = 'flutterwave_split_rules';

    public function getAllWithSubaccount() {
        try {
            $prefix = $this->db->getPrefix();
            return $this->db->fetchAll(
                "SELECT r.*, s.business_name, s.subaccount_id, s.split_type AS default_split_type, s.split_value AS default_split_value
                 FROM `{$prefix}{$this->table}` r
                 JOIN `{$prefix}flutterwave_subaccounts` s ON r.subaccount_row_id = s.id
                 ORDER BY r.priority DESC, r.name ASC"
            );
        } catch (Exception $e) {
            error_log('Flutterwave_split_rule_model getAllWithSubaccount: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resolve best active rule: space → property → global.
     *
     * @return array|false Rule row with subaccount fields joined
     */
    public function resolveForBooking($spaceId, $propertyId, $currency = 'NGN') {
        $currency = strtoupper((string) $currency);
        $candidates = [];

        if ($spaceId) {
            $candidates[] = ['scope_type' => 'space', 'scope_id' => (int) $spaceId];
        }
        if ($propertyId) {
            $candidates[] = ['scope_type' => 'property', 'scope_id' => (int) $propertyId];
        }
        $candidates[] = ['scope_type' => 'global', 'scope_id' => null];

        try {
            $prefix = $this->db->getPrefix();
            foreach ($candidates as $scope) {
                $sql = "SELECT r.*, s.subaccount_id, s.business_name, s.is_active AS subaccount_active,
                               s.split_type AS default_split_type, s.split_value AS default_split_value
                        FROM `{$prefix}{$this->table}` r
                        JOIN `{$prefix}flutterwave_subaccounts` s ON r.subaccount_row_id = s.id
                        WHERE r.is_active = 1 AND s.is_active = 1
                          AND r.scope_type = ?";
                $params = [$scope['scope_type']];

                if ($scope['scope_id'] === null) {
                    $sql .= " AND r.scope_id IS NULL";
                } else {
                    $sql .= " AND r.scope_id = ?";
                    $params[] = $scope['scope_id'];
                }

                $sql .= " AND (r.currency IS NULL OR r.currency = '' OR UPPER(r.currency) = ?)
                          ORDER BY r.priority DESC, r.id ASC LIMIT 1";
                $params[] = $currency;

                $row = $this->db->fetchOne($sql, $params);
                if ($row) {
                    return $row;
                }
            }
        } catch (Exception $e) {
            error_log('Flutterwave_split_rule_model resolveForBooking: ' . $e->getMessage());
        }

        return false;
    }
}
