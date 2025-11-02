<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leases extends Base_Controller {
    private $leaseModel;
    private $spaceModel;
    private $tenantModel;
    private $rentInvoiceModel;
    private $transactionModel;
    private $accountModel;
    private $activityModel;
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('properties', 'read');
        $this->leaseModel = $this->loadModel('Lease_model');
        $this->spaceModel = $this->loadModel('Space_model');
        $this->tenantModel = $this->loadModel('Tenant_model');
        $this->rentInvoiceModel = $this->loadModel('Rent_invoice_model');
        $this->transactionModel = $this->loadModel('Transaction_model');
        $this->accountModel = $this->loadModel('Account_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }
    
    public function index() {
        $status = $_GET['status'] ?? 'active';
        
        try {
            if ($status === 'active') {
                $leases = $this->leaseModel->getActive();
            } elseif ($status === 'expiring') {
                $leases = $this->leaseModel->getExpiring(90);
            } else {
                $leases = [];
            }
        } catch (Exception $e) {
            $leases = [];
        }
        
        $data = [
            'page_title' => 'Leases',
            'leases' => $leases,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('leases/index', $data);
    }
    
    public function create() {
        $this->requirePermission('properties', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $spaceId = intval($_POST['space_id'] ?? 0);
            $tenantId = intval($_POST['tenant_id'] ?? 0);
            
            // Check if space is available
            $existingLease = $this->leaseModel->getBySpace($spaceId);
            $activeLease = array_filter($existingLease, function($l) {
                return $l['status'] === 'active';
            });
            
            if (!empty($activeLease)) {
                $this->setFlashMessage('danger', 'Space is already leased to another tenant.');
                redirect('leases/create');
            }
            
            $data = [
                'lease_number' => sanitize_input($_POST['lease_number'] ?? ''),
                'space_id' => $spaceId,
                'tenant_id' => $tenantId,
                'lease_type' => sanitize_input($_POST['lease_type'] ?? 'commercial'),
                'lease_term' => sanitize_input($_POST['lease_term'] ?? 'fixed_term'),
                'start_date' => sanitize_input($_POST['start_date'] ?? ''),
                'end_date' => !empty($_POST['end_date']) ? sanitize_input($_POST['end_date']) : null,
                'rent_amount' => floatval($_POST['rent_amount'] ?? 0),
                'payment_frequency' => sanitize_input($_POST['payment_frequency'] ?? 'monthly'),
                'rent_due_date' => intval($_POST['rent_due_date'] ?? 5),
                'security_deposit' => floatval($_POST['security_deposit'] ?? 0),
                'service_charge' => floatval($_POST['service_charge'] ?? 0),
                'utility_responsibility' => sanitize_input($_POST['utility_responsibility'] ?? 'tenant'),
                'maintenance_responsibility' => sanitize_input($_POST['maintenance_responsibility'] ?? 'landlord'),
                'permitted_use' => sanitize_input($_POST['permitted_use'] ?? ''),
                'subletting_allowed' => !empty($_POST['subletting_allowed']) ? 1 : 0,
                'rent_escalation_rate' => floatval($_POST['rent_escalation_rate'] ?? 0),
                'terms' => !empty($_POST['terms']) ? json_encode($_POST['terms']) : null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($data['lease_number'])) {
                $data['lease_number'] = $this->leaseModel->getNextLeaseNumber();
            }
            
            $leaseId = $this->leaseModel->create($data);
            
            if ($leaseId) {
                // Update space operational mode
                $this->spaceModel->update($spaceId, [
                    'operational_mode' => 'leased',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                // Post security deposit to accounting (liability)
                $this->postSecurityDeposit($leaseId, $data);
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Leases', 'Created lease: ' . $data['lease_number']);
                $this->setFlashMessage('success', 'Lease created successfully.');
                redirect('leases/view/' . $leaseId);
            } else {
                $this->setFlashMessage('danger', 'Failed to create lease.');
            }
        }
        
        try {
            $spaces = [];
            $allSpaces = $this->spaceModel->getAll();
            // Filter for vacant spaces
            foreach ($allSpaces as $space) {
                if ($space['operational_mode'] === 'vacant' || $space['operational_mode'] === 'available_for_booking') {
                    $spaceWithProperty = $this->spaceModel->getWithProperty($space['id']);
                    if ($spaceWithProperty) {
                        $spaces[] = $spaceWithProperty;
                    }
                }
            }
            
            $tenants = $this->tenantModel->getActive();
            
            // Handle pre-selected space/tenant from query params
            $preselectedSpaceId = isset($_GET['space_id']) ? intval($_GET['space_id']) : null;
            $preselectedTenantId = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : null;
        } catch (Exception $e) {
            $spaces = [];
            $tenants = [];
            $preselectedSpaceId = null;
            $preselectedTenantId = null;
        }
        
        $data = [
            'page_title' => 'Create Lease',
            'spaces' => $spaces,
            'tenants' => $tenants,
            'preselected_space_id' => $preselectedSpaceId ?? null,
            'preselected_tenant_id' => $preselectedTenantId ?? null,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('leases/create', $data);
    }
    
    public function view($id) {
        try {
            $lease = $this->leaseModel->getWithDetails($id);
            if (!$lease) {
                $this->setFlashMessage('danger', 'Lease not found.');
                redirect('leases');
            }
            
            // Get rent invoices
            $invoices = $this->rentInvoiceModel->getByLease($id);
            
            // Calculate statistics
            $totalInvoiced = array_sum(array_column($invoices, 'total_amount'));
            $totalPaid = array_sum(array_column($invoices, 'paid_amount'));
            $totalDue = $totalInvoiced - $totalPaid;
            
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading lease.');
            redirect('leases');
        }
        
        $data = [
            'page_title' => 'Lease: ' . $lease['lease_number'],
            'lease' => $lease,
            'invoices' => $invoices,
            'stats' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue
            ],
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('leases/view', $data);
    }
    
    /**
     * Post security deposit to accounting
     */
    private function postSecurityDeposit($leaseId, $leaseData) {
        try {
            if (floatval($leaseData['security_deposit']) <= 0) {
                return;
            }
            
            // Find Security Deposit account (liability)
            $liabilityAccounts = $this->accountModel->getByType('Liabilities');
            $depositAccount = null;
            foreach ($liabilityAccounts as $acc) {
                if (stripos($acc['account_name'], 'deposit') !== false || 
                    stripos($acc['account_name'], 'security') !== false) {
                    $depositAccount = $acc;
                    break;
                }
            }
            if (!$depositAccount && !empty($liabilityAccounts)) {
                $depositAccount = $liabilityAccounts[0]; // Fallback to first liability
            }
            
            if (!$depositAccount) {
                error_log('No security deposit account found for lease accounting entry.');
                return;
            }
            
            // Find Cash account
            $assetAccounts = $this->accountModel->getByType('Assets');
            $cashAccount = null;
            foreach ($assetAccounts as $acc) {
                if (stripos($acc['account_name'], 'cash') !== false || 
                    stripos($acc['account_name'], 'bank') !== false) {
                    $cashAccount = $acc;
                    break;
                }
            }
            if (!$cashAccount && !empty($assetAccounts)) {
                $cashAccount = $assetAccounts[0]; // Fallback
            }
            
            if (!$cashAccount) {
                error_log('No cash account found for lease accounting entry.');
                return;
            }
            
            // Entry 1: Debit Cash
            $this->transactionModel->create([
                'transaction_number' => $leaseData['lease_number'] . '-DEP-CASH',
                'transaction_date' => $leaseData['start_date'],
                'transaction_type' => 'deposit',
                'reference_id' => $leaseId,
                'reference_type' => 'lease_deposit',
                'account_id' => $cashAccount['id'],
                'description' => 'Security deposit received - ' . $leaseData['lease_number'],
                'debit' => $leaseData['security_deposit'],
                'credit' => 0,
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($cashAccount['id'], $leaseData['security_deposit'], 'debit');
            
            // Entry 2: Credit Security Deposits (Liability)
            $this->transactionModel->create([
                'transaction_number' => $leaseData['lease_number'] . '-DEP-LIAB',
                'transaction_date' => $leaseData['start_date'],
                'transaction_type' => 'deposit',
                'reference_id' => $leaseId,
                'reference_type' => 'lease_deposit',
                'account_id' => $depositAccount['id'],
                'description' => 'Security deposit liability - ' . $leaseData['lease_number'],
                'debit' => 0,
                'credit' => $leaseData['security_deposit'],
                'status' => 'posted',
                'created_by' => $this->session['user_id'] ?? null
            ]);
            $this->accountModel->updateBalance($depositAccount['id'], $leaseData['security_deposit'], 'credit');
            
        } catch (Exception $e) {
            error_log('Leases postSecurityDeposit error: ' . $e->getMessage());
        }
    }
}

