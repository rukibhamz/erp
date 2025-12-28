<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_types extends Base_Controller {
    private $typeModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('customer_types', 'read');
        $this->typeModel = $this->loadModel('Customer_type_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $types = $this->typeModel->getAll();
        $data = [
            'page_title' => 'Customer Types',
            'types' => $types,
            'flash' => $this->getFlashMessage()
        ];
        $this->loadView('customer_types/index', $data);
    }
    
    public function create() {
        $this->requirePermission('customer_types', 'write');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'name' => sanitize_input($_POST['name']),
                'code' => strtoupper(sanitize_input($_POST['code'])),
                'description' => sanitize_input($_POST['description']),
                'discount_percentage' => floatval($_POST['discount_percentage'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            if ($this->typeModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Settings', 'Created customer type: ' . $data['name']);
                $this->setFlashMessage('success', 'Customer type created.');
                redirect('customer_types');
            }
        }
        $this->loadView('customer_types/form', ['page_title' => 'Add Customer Type']);
    }
    
    public function edit($id) {
        $this->requirePermission('customer_types', 'write');
        $type = $this->typeModel->getById($id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $data = [
                'name' => sanitize_input($_POST['name']),
                'description' => sanitize_input($_POST['description']),
                'discount_percentage' => floatval($_POST['discount_percentage'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            if ($this->typeModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Settings', 'Updated customer type: ' . $data['name']);
                $this->setFlashMessage('success', 'Customer type updated.');
                redirect('customer_types');
            }
        }
        $this->loadView('customer_types/form', ['page_title' => 'Edit Customer Type', 'type' => $type]);
    }
    
    public function delete($id) {
        $this->requirePermission('customer_types', 'delete');
        
        $type = $this->typeModel->getById($id);
        if (!$type) {
            $this->setFlashMessage('danger', 'Customer type not found.');
            redirect('customer_types');
        }
        
        // Check if type is in use by customers
        try {
            $customerModel = $this->loadModel('Customer_model');
            $customers = $customerModel->getBy(['customer_type_id' => $id]);
            if (!empty($customers)) {
                $this->setFlashMessage('danger', 'Cannot delete customer type in use. Please reassign customers first.');
                redirect('customer_types');
            }
        } catch (Exception $e) {
            // Continue if model doesn't exist
        }
        
        if ($this->typeModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Settings', 'Deleted customer type: ' . ($type['name'] ?? ''));
            $this->setFlashMessage('success', 'Customer type deleted.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete customer type.');
        }
        
        redirect('customer_types');
    }
}
