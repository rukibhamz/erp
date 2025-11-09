<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entities extends Base_Controller {
    private $entityModel;
    
    public function __construct() {
        parent::__construct();
        $this->entityModel = $this->loadModel('Entity_model');
    }
    
    public function index() {
        $this->requirePermission('entities', 'read');
        $data = [
            'page_title' => 'Entities',
            'entities' => $this->entityModel->getAll(null, 0, 'created_at DESC'),
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('entities/index', $data);
    }
    
    public function create() {
        $this->requirePermission('entities', 'create');
        
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
                $this->setFlashMessage('danger', 'Entity name is required.');
                redirect('entities/create');
            }
            
            $this->entityModel->create($data);
            $this->setFlashMessage('success', 'Entity created successfully.');
            redirect('entities');
        }
        
        $data = ['page_title' => 'Create Entity', 'flash' => $this->getFlashMessage()];
        $this->loadView('entities/create', $data);
    }
    
    public function edit($id) {
        $this->requirePermission('entities', 'update');
        
        $entity = $this->entityModel->getById($id);
        
        if (!$entity) {
            $this->setFlashMessage('danger', 'Entity not found.');
            redirect('entities');
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
            
            $this->entityModel->update($id, $data);
            $this->setFlashMessage('success', 'Entity updated successfully.');
            redirect('entities');
        }
        
        $data = ['page_title' => 'Edit Entity', 'entity' => $entity, 'flash' => $this->getFlashMessage()];
        $this->loadView('entities/edit', $data);
    }
}

