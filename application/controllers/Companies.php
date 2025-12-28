<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Companies extends Base_Controller {
    private $companyModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->companyModel = $this->loadModel('Company_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $this->requirePermission('companies', 'read');
        $data = [
            'page_title' => 'Companies',
            'companies' => $this->companyModel->getAll(null, 0, 'created_at DESC'),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('companies/index', $data);
    }
    
    public function create() {
        $this->requirePermission('companies', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            
            $data = [
                'name' => sanitize_input($_POST['name'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'website' => sanitize_input($_POST['website'] ?? ''),
                'tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD')
            ];
            
            if (empty($data['name'])) {
                $this->setFlashMessage('danger', 'Company name is required.');
                redirect('companies/create');
            }
            
            if ($this->companyModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Companies', 'Created company: ' . $data['name']);
                $this->setFlashMessage('success', 'Company created successfully.');
            }
            redirect('companies');
        }
        
        $data = ['page_title' => 'Create Company', 'flash' => $this->getFlashMessage()];
        $this->loadView('companies/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('companies', 'update');
        
        $company = $this->companyModel->getById($id);
        
        if (!$company) {
            $this->setFlashMessage('danger', 'Company not found.');
            redirect('companies');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
            
            $data = [
                'name' => sanitize_input($_POST['name'] ?? ''),
                'address' => sanitize_input($_POST['address'] ?? ''),
                'city' => sanitize_input($_POST['city'] ?? ''),
                'state' => sanitize_input($_POST['state'] ?? ''),
                'zip_code' => sanitize_input($_POST['zip_code'] ?? ''),
                'country' => sanitize_input($_POST['country'] ?? ''),
                'phone' => sanitize_input($_POST['phone'] ?? ''),
                'email' => sanitize_input($_POST['email'] ?? ''),
                'website' => sanitize_input($_POST['website'] ?? ''),
                'tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                'currency' => sanitize_input($_POST['currency'] ?? 'USD')
            ];
            
            if ($this->companyModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Companies', 'Updated company: ' . $data['name']);
                $this->setFlashMessage('success', 'Company updated successfully.');
            }
            redirect('companies');
        }
        
        $data = ['page_title' => 'Edit Company', 'company' => $company, 'flash' => $this->getFlashMessage()];
        $this->loadView('companies/edit', $data);
    }
    
    public function delete($id) {
        $this->requirePermission('companies', 'delete');
        
        $company = $this->companyModel->getById($id);
        
        if (!$company) {
            $this->setFlashMessage('danger', 'Company not found.');
            redirect('companies');
        }
        
        // Check if company has associated data (users, etc.)
        try {
            $userModel = $this->loadModel('User_model');
            $users = $userModel->getBy(['company_id' => $id]);
            if (!empty($users)) {
                $this->setFlashMessage('danger', 'Cannot delete company with associated users. Please reassign users first.');
                redirect('companies');
            }
        } catch (Exception $e) {
            // User model might not have company_id, continue
        }
        
        if ($this->companyModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Companies', 'Deleted company: ' . ($company['name'] ?? ''));
            $this->setFlashMessage('success', 'Company deleted successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete company.');
        }
        
        redirect('companies');
    }
}
