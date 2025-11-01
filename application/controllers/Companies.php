<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Companies extends Base_Controller {
    private $companyModel;
    
    public function __construct() {
        parent::__construct();
        $this->companyModel = $this->loadModel('Company_model');
    }
    
    public function index() {
        $data = [
            'page_title' => 'Companies',
            'companies' => $this->companyModel->getAll(null, 0, 'created_at DESC'),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('companies/index', $data);
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'zip_code' => $_POST['zip_code'] ?? '',
                'country' => $_POST['country'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'website' => $_POST['website'] ?? '',
                'tax_id' => $_POST['tax_id'] ?? '',
                'currency' => $_POST['currency'] ?? 'USD'
            ];
            
            if (empty($data['name'])) {
                $this->setFlashMessage('danger', 'Company name is required.');
                redirect('companies/create');
            }
            
            $this->companyModel->create($data);
            $this->setFlashMessage('success', 'Company created successfully.');
            redirect('companies');
        }
        
        $data = ['page_title' => 'Create Company', 'flash' => $this->getFlashMessage()];
        $this->loadView('companies/create', $data);
    }
    
    public function edit($id) {
        $company = $this->companyModel->getById($id);
        
        if (!$company) {
            $this->setFlashMessage('danger', 'Company not found.');
            redirect('companies');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'address' => $_POST['address'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'zip_code' => $_POST['zip_code'] ?? '',
                'country' => $_POST['country'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'website' => $_POST['website'] ?? '',
                'tax_id' => $_POST['tax_id'] ?? '',
                'currency' => $_POST['currency'] ?? 'USD'
            ];
            
            $this->companyModel->update($id, $data);
            $this->setFlashMessage('success', 'Company updated successfully.');
            redirect('companies');
        }
        
        $data = ['page_title' => 'Edit Company', 'company' => $company, 'flash' => $this->getFlashMessage()];
        $this->loadView('companies/edit', $data);
    }
}

