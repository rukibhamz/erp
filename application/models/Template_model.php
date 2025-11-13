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
    
    public function getByTypeAndName($type, $name) {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM `" . $this->db->getPrefix() . $this->table . "` 
                 WHERE template_type = ? AND template_name = ? AND status = 'active' 
                 LIMIT 1",
                [$type, $name]
            );
        } catch (Exception $e) {
            error_log('Template_model getByTypeAndName error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Render template with data
     * 
     * SECURITY: All data is escaped by default. Use {{key}} for escaped text,
     * and {key} only for trusted HTML content that has been validated.
     * 
     * @param int $templateId Template ID
     * @param array $data Data to replace in template
     * @param array $trustedKeys Array of keys that contain pre-validated HTML (optional)
     * @return string|false Rendered HTML or false on error
     */
    public function render($templateId, $data, $trustedKeys = []) {
        try {
            $template = $this->getById($templateId);
            if (!$template) {
                return false;
            }
            
            $html = $template['template_html'];
            
            // Replace placeholders
            foreach ($data as $key => $value) {
                // {{key}} - Always escaped (safe for any content)
                $html = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
                
                // {key} - Only for trusted HTML content (must be in trustedKeys whitelist)
                if (in_array($key, $trustedKeys)) {
                    // SECURITY: Even trusted content should be sanitized
                    // Use basic HTML tag whitelist to prevent XSS
                    $html = str_replace('{' . $key . '}', $this->sanitizeHtml($value), $html);
                } else {
                    // If not in trusted list, escape it (default safe behavior)
                    $html = str_replace('{' . $key . '}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
                }
            }
            
            return $html;
        } catch (Exception $e) {
            error_log('Template_model render error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sanitize HTML content with basic whitelist
     * 
     * SECURITY: Allows only safe HTML tags. Removes script tags and event handlers.
     * For production use, consider using HTMLPurifier library for more robust sanitization.
     * 
     * @param string $html HTML content to sanitize
     * @return string Sanitized HTML
     */
    private function sanitizeHtml($html) {
        if (empty($html)) {
            return '';
        }
        
        // SECURITY: Remove script tags and event handlers
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi', '', $html);
        $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
        $html = preg_replace("/on\w+='[^']*'/i", '', $html);
        $html = preg_replace('/on\w+=\S+/i', '', $html);
        
        // SECURITY: Allow only safe HTML tags (basic whitelist)
        // For production, use HTMLPurifier for comprehensive sanitization
        $allowedTags = '<p><br><strong><em><u><b><i><ul><ol><li><a><h1><h2><h3><h4><h5><h6><div><span><table><tr><td><th><thead><tbody><tfoot>';
        $html = strip_tags($html, $allowedTags);
        
        // SECURITY: Remove javascript: and data: URLs from href/src attributes
        $html = preg_replace('/href=["\']javascript:[^"\']*["\']/i', 'href="#"', $html);
        $html = preg_replace('/src=["\']javascript:[^"\']*["\']/i', '', $html);
        $html = preg_replace('/href=["\']data:[^"\']*["\']/i', 'href="#"', $html);
        
        return $html;
    }
}

