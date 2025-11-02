<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utility_allocations extends Base_Controller {
    private $allocationModel;
    private $billModel;
    private $tenantModel;
    private $spaceModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('utilities', 'read');
        $this->allocationModel = $this->loadModel('Utility_allocation_model');
        $this->billModel = $this->loadModel('Utility_bill_model');
        $this->tenantModel = $this->loadModel('Tenant_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function allocate($billId) {
        $this->requirePermission('utilities', 'create');
        
        try {
            $bill = $this->billModel->getWithDetails($billId);
            if (!$bill) {
                $this->setFlashMessage('danger', 'Bill not found.');
                redirect('utilities/bills');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading bill.');
            redirect('utilities/bills');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $allocations = $_POST['allocations'] ?? [];
            $allocationMethod = sanitize_input($_POST['allocation_method'] ?? 'direct');
            
            try {
                // Delete existing allocations
                $existingAllocations = $this->allocationModel->getByBill($billId);
                foreach ($existingAllocations as $existing) {
                    $this->allocationModel->delete($existing['id']);
                }
                
                $totalAllocated = 0;
                foreach ($allocations as $allocation) {
                    if (empty($allocation['tenant_id']) && empty($allocation['space_id']) && empty($allocation['department_id'])) {
                        continue;
                    }
                    
                    $allocationData = [
                        'bill_id' => $billId,
                        'tenant_id' => !empty($allocation['tenant_id']) ? intval($allocation['tenant_id']) : null,
                        'space_id' => !empty($allocation['space_id']) ? intval($allocation['space_id']) : null,
                        'department_id' => !empty($allocation['department_id']) ? intval($allocation['department_id']) : null,
                        'allocation_method' => $allocationMethod,
                        'allocation_percentage' => !empty($allocation['percentage']) ? floatval($allocation['percentage']) : null,
                        'allocation_amount' => floatval($allocation['amount'] ?? 0),
                        'allocated_consumption' => !empty($allocation['consumption']) ? floatval($allocation['consumption']) : null,
                        'notes' => sanitize_input($allocation['notes'] ?? ''),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->allocationModel->create($allocationData);
                    $totalAllocated += $allocationData['allocation_amount'];
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Utility Allocations', 'Allocated bill: ' . $bill['bill_number']);
                $this->setFlashMessage('success', 'Bill allocated successfully.');
                redirect('utilities/bills/view/' . $billId);
            } catch (Exception $e) {
                $this->setFlashMessage('danger', 'Failed to allocate bill: ' . $e->getMessage());
            }
        }
        
        try {
            $existingAllocations = $this->allocationModel->getByBill($billId);
            $tenants = $this->tenantModel->getActive();
            $spaces = [];
            if ($bill['space_id']) {
                $spaces = $this->spaceModel->getByProperty($bill['property_id'] ?? null);
            }
        } catch (Exception $e) {
            $existingAllocations = [];
            $tenants = [];
            $spaces = [];
        }
        
        $data = [
            'page_title' => 'Allocate Bill: ' . $bill['bill_number'],
            'bill' => $bill,
            'existing_allocations' => $existingAllocations,
            'tenants' => $tenants,
            'spaces' => $spaces,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('utilities/allocations/allocate', $data);
    }
}

