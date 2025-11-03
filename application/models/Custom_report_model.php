<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Custom_report_model extends Base_Model {
    protected $table = 'custom_reports';
    
    /**
     * Create a new custom report
     */
    public function createReport($data) {
        try {
            $reportData = [
                'report_name' => sanitize_input($data['report_name'] ?? ''),
                'report_code' => sanitize_input($data['report_code'] ?? $this->generateReportCode()),
                'description' => sanitize_input($data['description'] ?? ''),
                'module' => sanitize_input($data['module'] ?? ''),
                'report_type' => sanitize_input($data['report_type'] ?? 'table'),
                'data_source' => sanitize_input($data['data_source'] ?? ''),
                'fields_json' => json_encode($data['fields'] ?? []),
                'filters_json' => !empty($data['filters']) ? json_encode($data['filters']) : null,
                'grouping_json' => !empty($data['grouping']) ? json_encode($data['grouping']) : null,
                'sorting_json' => !empty($data['sorting']) ? json_encode($data['sorting']) : null,
                'calculated_fields_json' => !empty($data['calculated_fields']) ? json_encode($data['calculated_fields']) : null,
                'chart_config_json' => !empty($data['chart_config']) ? json_encode($data['chart_config']) : null,
                'format_options_json' => !empty($data['format_options']) ? json_encode($data['format_options']) : null,
                'is_public' => !empty($data['is_public']) ? 1 : 0,
                'is_scheduled' => !empty($data['is_scheduled']) ? 1 : 0,
                'schedule_frequency' => sanitize_input($data['schedule_frequency'] ?? null),
                'schedule_time' => sanitize_input($data['schedule_time'] ?? null),
                'schedule_emails' => sanitize_input($data['schedule_emails'] ?? null),
                'created_by' => $data['created_by'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return $this->create($reportData);
        } catch (Exception $e) {
            error_log('Custom_report_model createReport error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get report with decoded JSON fields
     */
    public function getReportById($reportId) {
        try {
            $report = $this->getById($reportId);
            if ($report) {
                $report['fields'] = json_decode($report['fields_json'] ?? '[]', true);
                $report['filters'] = json_decode($report['filters_json'] ?? '[]', true);
                $report['grouping'] = json_decode($report['grouping_json'] ?? '[]', true);
                $report['sorting'] = json_decode($report['sorting_json'] ?? '[]', true);
                $report['calculated_fields'] = json_decode($report['calculated_fields_json'] ?? '[]', true);
                $report['chart_config'] = json_decode($report['chart_config_json'] ?? '[]', true);
                $report['format_options'] = json_decode($report['format_options_json'] ?? '[]', true);
            }
            return $report;
        } catch (Exception $e) {
            error_log('Custom_report_model getReportById error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user's accessible reports
     */
    public function getUserReports($userId, $role = null) {
        try {
            $sql = "SELECT r.* FROM `" . $this->db->getPrefix() . $this->table . "` r
                    LEFT JOIN `" . $this->db->getPrefix() . "report_access` ra ON r.id = ra.report_id
                    WHERE (r.created_by = ? OR r.is_public = 1 
                           OR (ra.user_id = ?) 
                           OR (ra.role = ? AND ? IS NOT NULL))";
            
            $params = [$userId, $userId, $role, $role];
            
            $sql .= " ORDER BY r.created_at DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Custom_report_model getUserReports error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Execute report and return data
     */
    public function executeReport($reportId, $userId = null) {
        try {
            $report = $this->getReportById($reportId);
            if (!$report) {
                return ['success' => false, 'message' => 'Report not found'];
            }
            
            $startTime = microtime(true);
            
            // Get data source model
            $dataSource = $report['data_source'];
            $model = $this->loadModel(ucfirst($dataSource) . '_model');
            
            // Build query based on report configuration
            $data = $this->buildReportQuery($model, $report);
            
            $executionTime = microtime(true) - $startTime;
            
            // Log execution
            $this->logExecution($reportId, $userId, $executionTime, count($data));
            
            return [
                'success' => true,
                'data' => $data,
                'execution_time' => $executionTime,
                'row_count' => count($data)
            ];
        } catch (Exception $e) {
            error_log('Custom_report_model executeReport error: ' . $e->getMessage());
            
            // Log failed execution
            if (isset($reportId)) {
                $this->logExecution($reportId, $userId, null, null, 'error', $e->getMessage());
            }
            
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Build query based on report configuration
     */
    private function buildReportQuery($model, $report) {
        $fields = $report['fields'] ?? [];
        $filters = $report['filters'] ?? [];
        $sorting = $report['sorting'] ?? [];
        $grouping = $report['grouping'] ?? [];
        
        // For now, use getAll and filter in PHP
        // In production, you'd want to build SQL dynamically
        $allData = $model->getAll(1000); // Limit for now
        
        // Apply filters
        if (!empty($filters)) {
            $allData = $this->applyFilters($allData, $filters);
        }
        
        // Apply grouping
        if (!empty($grouping)) {
            $allData = $this->applyGrouping($allData, $grouping, $fields);
        }
        
        // Apply sorting
        if (!empty($sorting)) {
            $allData = $this->applySorting($allData, $sorting);
        }
        
        // Select only requested fields
        if (!empty($fields)) {
            $allData = $this->selectFields($allData, $fields);
        }
        
        return $allData;
    }
    
    private function applyFilters($data, $filters) {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? '';
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? '';
            
            $data = array_filter($data, function($row) use ($field, $operator, $value) {
                $rowValue = $row[$field] ?? null;
                
                switch ($operator) {
                    case '=':
                        return $rowValue == $value;
                    case '!=':
                        return $rowValue != $value;
                    case '>':
                        return $rowValue > $value;
                    case '<':
                        return $rowValue < $value;
                    case '>=':
                        return $rowValue >= $value;
                    case '<=':
                        return $rowValue <= $value;
                    case 'contains':
                        return stripos($rowValue, $value) !== false;
                    case 'starts_with':
                        return stripos($rowValue, $value) === 0;
                    default:
                        return true;
                }
            });
        }
        
        return array_values($data);
    }
    
    private function applyGrouping($data, $grouping, $fields) {
        $grouped = [];
        foreach ($data as $row) {
            $key = '';
            foreach ($grouping as $groupField) {
                $key .= ($row[$groupField] ?? '') . '|';
            }
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $row;
        }
        
        // Aggregate grouped data
        $result = [];
        foreach ($grouped as $group) {
            $aggregated = [];
            foreach ($fields as $field) {
                if (isset($field['aggregate'])) {
                    $aggregated[$field['name']] = $this->aggregate($group, $field['name'], $field['aggregate']);
                } else {
                    $aggregated[$field['name']] = $group[0][$field['name']] ?? null;
                }
            }
            $result[] = $aggregated;
        }
        
        return $result;
    }
    
    private function applySorting($data, $sorting) {
        usort($data, function($a, $b) use ($sorting) {
            foreach ($sorting as $sort) {
                $field = $sort['field'] ?? '';
                $direction = $sort['direction'] ?? 'ASC';
                
                $aVal = $a[$field] ?? null;
                $bVal = $b[$field] ?? null;
                
                $result = $aVal <=> $bVal;
                if ($direction === 'DESC') {
                    $result = -$result;
                }
                
                if ($result !== 0) {
                    return $result;
                }
            }
            return 0;
        });
        
        return $data;
    }
    
    private function selectFields($data, $fields) {
        $fieldNames = array_column($fields, 'name');
        return array_map(function($row) use ($fieldNames) {
            return array_intersect_key($row, array_flip($fieldNames));
        }, $data);
    }
    
    private function aggregate($group, $field, $function) {
        $values = array_column($group, $field);
        
        switch (strtoupper($function)) {
            case 'SUM':
                return array_sum($values);
            case 'AVG':
                return count($values) > 0 ? array_sum($values) / count($values) : 0;
            case 'MIN':
                return min($values);
            case 'MAX':
                return max($values);
            case 'COUNT':
                return count($values);
            default:
                return null;
        }
    }
    
    /**
     * Log report execution
     */
    private function logExecution($reportId, $userId, $executionTime, $rowsReturned, $status = 'success', $errorMessage = null) {
        try {
            $this->db->insert('report_executions', [
                'report_id' => $reportId,
                'user_id' => $userId,
                'execution_time' => $executionTime,
                'rows_returned' => $rowsReturned,
                'status' => $status,
                'error_message' => $errorMessage,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Custom_report_model logExecution error: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique report code
     */
    private function generateReportCode() {
        return 'REPORT_' . strtoupper(uniqid());
    }
}


