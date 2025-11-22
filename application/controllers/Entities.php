<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Entities extends Base_Controller {
    private $entityModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->entityModel = $this->loadModel('Entity_model');
        $this->activityModel = $this->loadModel('Activity_model');
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
    
    public function view($id) {
        $this->requirePermission('entities', 'read');

        try {
            $entity = $this->entityModel->getById($id);
            if (!$entity) {
                $this->setFlashMessage('danger', 'Entity not found.');
                redirect('entities');
            }
        } catch (Exception $e) {
            error_log('Entity view error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'Error loading entity.');
            redirect('entities');
        }

        $data = [
            'page_title' => 'Entity Details',
            'entity' => $entity,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('entities/view', $data);
    }

    public function create() {
        $this->requirePermission('entities', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf(); // CSRF Protection
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
            
            // Validation
            $errors = [];
            if (empty($data['name'])) {
                $errors[] = 'Entity name is required.';
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address.';
            }

            if (!empty($errors)) {
                $this->setFlashMessage('danger', implode('<br>', $errors));
                redirect('entities/create');
            }

            try {
                $entityId = $this->entityModel->create($data);
                if ($entityId) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Entities', 'Created entity: ' . $data['name']);
                    $this->setFlashMessage('success', 'Entity created successfully.');
                    redirect('entities');
                } else {
                    $this->setFlashMessage('danger', 'Failed to create entity.');
                }
            } catch (Exception $e) {
                error_log('Entity create error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'An error occurred while creating entity.');
            }
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

            // Validation
            $errors = [];
            if (empty($data['name'])) {
                $errors[] = 'Entity name is required.';
            }
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address.';
            }

            if (!empty($errors)) {
                $this->setFlashMessage('danger', implode('<br>', $errors));
                redirect('entities/edit/' . $id);
            }
            
            try {
                if ($this->entityModel->update($id, $data)) {
                    $this->activityModel->log($this->session['user_id'], 'update', 'Entities', 'Updated entity: ' . $data['name']);
                    $this->setFlashMessage('success', 'Entity updated successfully.');
                    redirect('entities');
                } else {
                    $this->setFlashMessage('danger', 'Failed to update entity.');
                }
            } catch (Exception $e) {
                error_log('Entity update error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'An error occurred while updating entity.');
            }
        }
        
        $data = ['page_title' => 'Edit Entity', 'entity' => $entity, 'flash' => $this->getFlashMessage()];
        $this->loadView('entities/edit', $data);
    }

    public function delete($id) {
        $this->requirePermission('entities', 'delete');

        try {
            $entity = $this->entityModel->getById($id);
            if (!$entity) {
                $this->setFlashMessage('danger', 'Entity not found.');
                redirect('entities');
            }

            if ($this->entityModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Entities', 'Deleted entity: ' . $entity['name']);
                $this->setFlashMessage('success', 'Entity deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete entity.');
            }
        } catch (Exception $e) {
            error_log('Entity delete error: ' . $e->getMessage());
            $this->setFlashMessage('danger', 'An error occurred while deleting entity.');
        }

        redirect('entities');
    }
}

