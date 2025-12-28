<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends Base_Controller {
    private $gatewayModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->gatewayModel = $this->loadModel('Payment_gateway_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Settings',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/index', $data);
    }
    
    public function modules() {
        $this->requirePermission('settings', 'update');
        
        $data = [
            'page_title' => 'Module Settings',
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/modules', $data);
    }
    
    public function paymentGateways() {
        $this->requirePermission('settings', 'read');
        
        try {
            $gateways = $this->gatewayModel->getAll();
        } catch (Exception $e) {
            $gateways = [];
        }
        
        $data = [
            'page_title' => 'Payment Gateways',
            'gateways' => $gateways,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/payment_gateways', $data);
    }
    
    public function editGateway($id) {
        $this->requirePermission('settings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $data = [
                'public_key' => sanitize_input($_POST['public_key'] ?? ''),
                'private_key' => sanitize_input($_POST['private_key'] ?? ''),
                'secret_key' => sanitize_input($_POST['secret_key'] ?? ''),
                'webhook_url' => sanitize_input($_POST['webhook_url'] ?? ''),
                'callback_url' => sanitize_input($_POST['callback_url'] ?? ''),
                'test_mode' => !empty($_POST['test_mode']) ? 1 : 0,
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'is_default' => !empty($_POST['is_default']) ? 1 : 0
            ];
            
            // Handle additional config based on gateway
            $gateway = $this->gatewayModel->getById($id);
            if ($gateway) {
                $additionalConfig = [];
                
                // Monnify specific fields
                if ($gateway['gateway_code'] === 'monnify') {
                    $additionalConfig['contract_code'] = sanitize_input($_POST['contract_code'] ?? '');
                }
                
                // Flutterwave specific fields
                if ($gateway['gateway_code'] === 'flutterwave') {
                    $additionalConfig['encryption_key'] = sanitize_input($_POST['encryption_key'] ?? '');
                }
                
                if (!empty($additionalConfig)) {
                    $data['additional_config'] = json_encode($additionalConfig);
                }
            }
            
            if ($this->gatewayModel->update($id, $data)) {
                // If set as default, unset others
                if (!empty($_POST['is_default'])) {
                    $this->gatewayModel->setDefault($id);
                }
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Settings', 'Updated payment gateway: ' . ($gateway['gateway_name'] ?? ''));
                $this->setFlashMessage('success', 'Payment gateway updated successfully.');
                redirect('settings/payment-gateways');
            } else {
                $this->setFlashMessage('danger', 'Failed to update payment gateway.');
            }
        }
        
        try {
            $gateway = $this->gatewayModel->getById($id);
            if (!$gateway) {
                $this->setFlashMessage('danger', 'Payment gateway not found.');
                redirect('settings/payment-gateways');
            }
            
            $additionalConfig = $this->gatewayModel->getAdditionalConfig($id);
        } catch (Exception $e) {
            $gateway = null;
            $additionalConfig = [];
        }
        
        $data = [
            'page_title' => 'Edit Payment Gateway',
            'gateway' => $gateway,
            'additional_config' => $additionalConfig,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/edit_gateway', $data);
    }
    
    public function toggleGateway($id) {
        $this->requirePermission('settings', 'update');
        
        try {
            $gateway = $this->gatewayModel->getById($id);
            if ($gateway) {
                $newStatus = $gateway['is_active'] ? 0 : 1;
                $this->gatewayModel->update($id, ['is_active' => $newStatus]);
                $this->activityModel->log($this->session['user_id'], 'update', 'Settings', 
                    ($newStatus ? 'Activated' : 'Deactivated') . ' payment gateway: ' . $gateway['gateway_name']);
                $this->setFlashMessage('success', 'Payment gateway ' . ($newStatus ? 'activated' : 'deactivated') . ' successfully.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Failed to update payment gateway status.');
        }
        
        redirect('settings/payment-gateways');
    }
}

