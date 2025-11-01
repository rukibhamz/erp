<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Template_model extends Base_Model {
    protected $table = 'templates';
    
    public function getByType($type) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE template_type = ? AND status = 'active' 
                 ORDER BY is_default DESC, template_name",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Template_model getByType error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getDefault($type) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE template_type = ? AND is_default = 1 AND status = 'active' 
                 LIMIT 1",
                [$type]
            );
        } catch (Exception $e) {
            error_log('Template_model getDefault error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function setDefault($templateId) {
        try {
            $template = $this->getById($templateId);
            if (!$template) {
                return false;
            }
            
            // Unset all defaults for this type
            $this->db->query(
                "UPDATE `" . $this->db->getPrefix() . $this->table . "` 
                 SET is_default = 0 WHERE template_type = ? AND is_default = 1",
                [$template['template_type']]
            );
            
            // Set new default
            return $this->update($templateId, ['is_default' => 1]);
        } catch (Exception $e) {
            error_log('Template_model setDefault error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function render($templateId, $data) {
        try {
            $template = $this->getById($templateId);
            if (!$template) {
                return false;
            }
            
            $html = $template['template_html'];
            
            // Replace placeholders
            foreach ($data as $key => $value) {
                $html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $html);
                $html = str_replace('{' . $key . '}', $value, $html); // For HTML content
            }
            
            return $html;
        } catch (Exception $e) {
            error_log('Template_model render error: ' . $e->getMessage());
            return false;
        }
    }
}

