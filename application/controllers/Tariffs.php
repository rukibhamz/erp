<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tariffs extends Base_Controller {
    private $tariffModel;
    private $providerModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->tariffModel = $this->loadModel('Tariff_model');
        $this->providerModel = $this->loadModel('Utility_provider_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $providerId = $_GET['provider_id'] ?? null;
        
        try {
            if ($providerId) {
                $tariffs = $this->tariffModel->getAll();
                $tariffs = array_filter($tariffs, function($t) use ($providerId) {
                    return $t['provider_id'] == $providerId;
                });
            } else {
                $tariffs = $this->tariffModel->getAll();
            }
            
            $providers = $this->providerModel->getActive();
        } catch (Exception $e) {
            $tariffs = [];
            $providers = [];
        }
        
        $data = [
            'page_title' => 'Tariffs',
            'tariffs' => $tariffs,
            'providers' => $providers,
            'selected_provider_id' => $providerId,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/tariffs/index', $data);
    }
    
    public function create() {
        $this->requirePermission('utilities', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            // Build tariff structure JSON
            $structure = [
                'fixed_charge' => floatval($_POST['fixed_charge'] ?? 0),
                'variable_rate' => floatval($_POST['variable_rate'] ?? 0),
                'demand_charge' => floatval($_POST['demand_charge'] ?? 0),
                'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
                'tiered_rates' => []
            ];
            
            // Handle tiered rates if provided
            if (!empty($_POST['tiered_rates']) && is_array($_POST['tiered_rates'])) {
                $structure['tiered_rates'] = $_POST['tiered_rates'];
            }
            
            $data = [
                'provider_id' => intval($_POST['provider_id'] ?? 0),
                'tariff_name' => sanitize_input($_POST['tariff_name'] ?? ''),
                'effective_date' => sanitize_input($_POST['effective_date'] ?? date('Y-m-d')),
                'expiry_date' => !empty($_POST['expiry_date']) ? sanitize_input($_POST['expiry_date']) : null,
                'structure_json' => json_encode($structure),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $tariffId = $this->tariffModel->create($data);
            
            if ($tariffId) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Tariffs', 'Created tariff: ' . $data['tariff_name']);
                $this->setFlashMessage('success', 'Tariff created successfully.');
                redirect('utilities/tariffs/view/' . $tariffId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create tariff.');
            }
        }
        
        try {
            $providers = $this->providerModel->getActive();
        } catch (Exception $e) {
            $providers = [];
        }
        
        $data = [
            'page_title' => 'Create Tariff',
            'providers' => $providers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/tariffs/create', $data);
    }
    
    public function view($id) {
        try {
            $tariff = $this->tariffModel->getById($id);
            if (!$tariff) {
                $this->setFlashMessage('danger', 'Tariff not found.');
                redirect('utilities/tariffs');
            }
            
            $tariff['structure'] = json_decode($tariff['structure_json'] ?? '{}', true);
            
            $provider = $this->providerModel->getById($tariff['provider_id']);
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading tariff.');
            redirect('utilities/tariffs');
        }
        
        $data = [
            'page_title' => 'Tariff: ' . $tariff['tariff_name'],
            'tariff' => $tariff,
            'provider' => $provider,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/tariffs/view', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('utilities', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            // Build tariff structure JSON
            $structure = [
                'fixed_charge' => floatval($_POST['fixed_charge'] ?? 0),
                'variable_rate' => floatval($_POST['variable_rate'] ?? 0),
                'demand_charge' => floatval($_POST['demand_charge'] ?? 0),
                'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
                'tiered_rates' => []
            ];
            
            // Handle tiered rates if provided
            if (!empty($_POST['tiered_rates']) && is_array($_POST['tiered_rates'])) {
                $structure['tiered_rates'] = $_POST['tiered_rates'];
            }
            
            $data = [
                'provider_id' => intval($_POST['provider_id'] ?? 0),
                'tariff_name' => sanitize_input($_POST['tariff_name'] ?? ''),
                'effective_date' => sanitize_input($_POST['effective_date'] ?? date('Y-m-d')),
                'expiry_date' => !empty($_POST['expiry_date']) ? sanitize_input($_POST['expiry_date']) : null,
                'structure_json' => json_encode($structure),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($this->tariffModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Tariffs', 'Updated tariff: ' . $id);
                $this->setFlashMessage('success', 'Tariff updated successfully.');
                redirect('utilities/tariffs/view/' . $id);
            } else {
                $this->setFlashMessage('danger', 'Failed to update tariff.');
            }
        }
        
        try {
            $tariff = $this->tariffModel->getById($id);
            if (!$tariff) {
                $this->setFlashMessage('danger', 'Tariff not found.');
                redirect('utilities/tariffs');
            }
            
            $tariff['structure'] = json_decode($tariff['structure_json'] ?? '{}', true);
            
            $providers = $this->providerModel->getActive();
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading tariff.');
            redirect('utilities/tariffs');
        }
        
        $data = [
            'page_title' => 'Edit Tariff',
            'tariff' => $tariff,
            'providers' => $providers,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/tariffs/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('utilities', 'delete');
        
        try {
            if ($this->tariffModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Tariffs', 'Deleted tariff: ' . $id);
                $this->setFlashMessage('success', 'Tariff deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete tariff.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error deleting tariff: ' . $e->getMessage());
        }
        
        redirect('utilities/tariffs');
    }
}

