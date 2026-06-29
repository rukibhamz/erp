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
                    $existing = $this->gatewayModel->getAdditionalConfig($id);
                    $additionalConfig = is_array($existing) ? $existing : [];
                    $additionalConfig['encryption_key'] = sanitize_input($_POST['encryption_key'] ?? '');
                    $additionalConfig['enable_subaccounts'] = !empty($_POST['enable_subaccounts']) ? 1 : 0;
                    $additionalConfig['log_split_on_transactions'] = !empty($_POST['log_split_on_transactions']) ? 1 : 0;
                    require_once BASEPATH . 'helpers/payment_config_helper.php';
                    $data['callback_url'] = payment_callback_url('flutterwave');
                    $data['webhook_url'] = payment_webhook_url('flutterwave');
                    $data['additional_config'] = json_encode($additionalConfig);
                }
                
                if (!empty($additionalConfig) && empty($data['additional_config'])) {
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
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('settings/payment-gateways');
            return;
        }
        
        check_csrf();
        
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
    
    /**
     * Roles management page - Admin/SuperAdmin only
     */
    public function roles() {
        // Restrict to admin and super_admin only
        if (!in_array($this->session['role'], ['admin', 'super_admin'])) {
            $this->setFlashMessage('danger', 'Access denied. Admin privileges required.');
            redirect('settings');
            return;
        }
        
        $roleModel = $this->loadModel('Role_model');
        $permissionModel = $this->loadModel('Permission_model');
        
        try {
            $roles = $roleModel->getAllWithPermissionCount();
            $totalPermissions = count($permissionModel->getAll());
        } catch (Exception $e) {
            $roles = [];
            $totalPermissions = 0;
            error_log('Settings roles error: ' . $e->getMessage());
        }
        
        $data = [
            'page_title' => 'Roles & Permissions',
            'roles' => $roles,
            'total_permissions' => $totalPermissions,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/roles', $data);
    }
    
    /**
     * Edit role permissions - Admin/SuperAdmin only
     */
    public function editRole($id) {
        // Restrict to admin and super_admin only
        if (!in_array($this->session['role'], ['admin', 'super_admin'])) {
            $this->setFlashMessage('danger', 'Access denied. Admin privileges required.');
            redirect('settings');
            return;
        }
        
        $roleModel = $this->loadModel('Role_model');
        $permissionModel = $this->loadModel('Permission_model');
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            
            $selectedPermissions = $_POST['permissions'] ?? [];
            
            try {
                $roleModel->updatePermissions($id, $selectedPermissions);
                $this->activityModel->log($this->session['user_id'], 'update', 'Settings', 
                    'Updated permissions for role ID: ' . $id);
                $this->setFlashMessage('success', 'Role permissions updated successfully.');
                redirect('settings/roles');
                return;
            } catch (Exception $e) {
                $this->setFlashMessage('danger', 'Failed to update permissions: ' . $e->getMessage());
            }
        }
        
        try {
            $role = $roleModel->getById($id);
            if (!$role) {
                $this->setFlashMessage('danger', 'Role not found.');
                redirect('settings/roles');
                return;
            }
            
            $permissions = $permissionModel->getAllByModule();
            $rolePermissions = $roleModel->getPermissionIds($id);
        } catch (Exception $e) {
            $role = null;
            $permissions = [];
            $rolePermissions = [];
            error_log('Settings editRole error: ' . $e->getMessage());
        }
        
        $data = [
            'page_title' => 'Edit Role: ' . ($role['role_name'] ?? 'Unknown'),
            'role' => $role,
            'permissions' => $permissions,
            'role_permissions' => $rolePermissions,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('settings/edit_role', $data);
    }

    // --- Flutterwave subaccounts & split rules ---

    private function requireFlutterwaveGateway() {
        $gateway = $this->gatewayModel->getByCode('flutterwave');
        if (!$gateway) {
            $this->setFlashMessage('danger', 'Flutterwave gateway is not installed.');
            redirect('settings/payment-gateways');
            return null;
        }
        return $gateway;
    }

    private function flutterwaveGatewayConfig(array $gateway) {
        require_once BASEPATH . 'helpers/payment_config_helper.php';
        return merge_gateway_config('flutterwave', $gateway);
    }

    public function flutterwaveSubaccounts() {
        $this->requirePermission('settings', 'read');
        $gateway = $this->requireFlutterwaveGateway();
        if (!$gateway) {
            return;
        }

        require_once BASEPATH . 'helpers/flutterwave_split_helper.php';
        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');

        $data = [
            'page_title' => 'Flutterwave Subaccounts',
            'gateway' => $gateway,
            'subaccounts' => $subaccountModel->getAllForAdmin(),
            'subaccounts_enabled' => flutterwave_subaccounts_enabled($this->flutterwaveGatewayConfig($gateway)),
            'log_split' => flutterwave_should_log_split($this->flutterwaveGatewayConfig($gateway)),
            'flash' => $this->getFlashMessage(),
        ];
        $this->loadView('settings/flutterwave_subaccounts', $data);
    }

    public function flutterwaveSubaccountCreate() {
        $this->requirePermission('settings', 'update');
        $gateway = $this->requireFlutterwaveGateway();
        if (!$gateway) {
            return;
        }

        require_once BASEPATH . 'libraries/payment/Flutterwave_subaccount_service.php';
        require_once BASEPATH . 'helpers/flutterwave_split_helper.php';
        $service = new Flutterwave_subaccount_service($this->flutterwaveGatewayConfig($gateway));
        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');

        $entryMode = ($_GET['mode'] ?? $_POST['entry_mode'] ?? 'link') === 'create' ? 'create' : 'link';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $entryMode = ($_POST['entry_mode'] ?? 'create') === 'link' ? 'link' : 'create';

            if ($entryMode === 'link') {
                $subaccountId = strtoupper(trim(sanitize_input($_POST['subaccount_id'] ?? '')));
                $businessName = sanitize_input($_POST['business_name'] ?? '');

                if ($subaccountId === '' || !preg_match('/^RS_[A-Z0-9]+$/', $subaccountId)) {
                    $this->setFlashMessage('danger', 'Enter a valid Flutterwave subaccount code (e.g. RS_FB312AA6C2C84A13421F3079E714F2CB).');
                    redirect('settings/flutterwave/subaccounts/create?mode=link');
                    return;
                }

                if ($businessName === '') {
                    $this->setFlashMessage('danger', 'Enter a display name for this subaccount.');
                    redirect('settings/flutterwave/subaccounts/create?mode=link');
                    return;
                }

                if ($subaccountModel->getBySubaccountId($subaccountId)) {
                    $this->setFlashMessage('danger', 'This subaccount code is already registered in the ERP.');
                    redirect('settings/flutterwave/subaccounts');
                    return;
                }

                $row = [
                    'subaccount_id' => $subaccountId,
                    'flutterwave_numeric_id' => null,
                    'business_name' => $businessName,
                    'account_bank' => '—',
                    'account_number' => 'linked',
                    'account_number_masked' => '****',
                    'country' => 'NG',
                    'split_type' => 'percentage',
                    'split_value' => 0,
                    'business_email' => null,
                    'business_mobile' => null,
                    'test_mode' => !empty($gateway['test_mode']) ? 1 : 0,
                    'is_active' => 1,
                    'is_default' => $subaccountModel->countActive() === 0 ? 1 : 0,
                ];

                $api = $service->getSubaccount($subaccountId);
                if (!empty($api['success']) && is_array($api['data'])) {
                    $d = $api['data'];
                    $row['flutterwave_numeric_id'] = $d['id'] ?? null;
                    $row['business_name'] = $d['business_name'] ?? $d['full_name'] ?? $businessName;
                    $row['account_bank'] = $d['account_bank'] ?? $row['account_bank'];
                    $row['account_number'] = $d['account_number'] ?? $row['account_number'];
                    if (!empty($row['account_number']) && $row['account_number'] !== 'linked') {
                        $row['account_number_masked'] = flutterwave_mask_account_number($row['account_number']);
                    }
                    $row['country'] = strtoupper($d['country'] ?? $row['country']);
                }

                $newId = $subaccountModel->create($row);
                if ($newId && !empty($_POST['set_as_default'])) {
                    $subaccountModel->setDefault((int) $newId);
                }
                $this->activityModel->log($this->session['user_id'], 'create', 'Settings', 'Linked Flutterwave subaccount: ' . $subaccountId);
                $this->setFlashMessage('success', 'Subaccount activated. Enable split payments in gateway settings if not already on.');
                redirect('settings/flutterwave/subaccounts');
                return;
            }

            $payload = [
                'account_bank' => sanitize_input($_POST['account_bank'] ?? ''),
                'account_number' => preg_replace('/\s+/', '', sanitize_input($_POST['account_number'] ?? '')),
                'business_name' => sanitize_input($_POST['business_name'] ?? ''),
                'business_mobile' => sanitize_input($_POST['business_mobile'] ?? ''),
                'country' => strtoupper(sanitize_input($_POST['country'] ?? 'NG')),
                'split_type' => in_array($_POST['split_type'] ?? '', ['percentage', 'flat'], true)
                    ? $_POST['split_type'] : 'percentage',
                'split_value' => (float) ($_POST['split_value'] ?? 0),
            ];
            if (!empty($_POST['business_email'])) {
                $payload['business_email'] = sanitize_input($_POST['business_email']);
            }
            if (!empty($_POST['business_contact'])) {
                $payload['business_contact'] = sanitize_input($_POST['business_contact']);
            }
            if (!empty($_POST['business_contact_mobile'])) {
                $payload['business_contact_mobile'] = sanitize_input($_POST['business_contact_mobile']);
            }

            $api = $service->createSubaccount($payload);
            if (empty($api['success']) || empty($api['data']['subaccount_id'])) {
                $this->setFlashMessage('danger', $api['message'] ?? 'Failed to create subaccount on Flutterwave.');
                redirect('settings/flutterwave/subaccounts/create');
                return;
            }

            $d = $api['data'];
            $subaccountModel->create([
                'subaccount_id' => $d['subaccount_id'],
                'flutterwave_numeric_id' => $d['id'] ?? null,
                'business_name' => $payload['business_name'],
                'account_bank' => $payload['account_bank'],
                'account_number' => $payload['account_number'],
                'account_number_masked' => flutterwave_mask_account_number($payload['account_number']),
                'country' => $payload['country'],
                'split_type' => $payload['split_type'],
                'split_value' => $payload['split_value'],
                'business_email' => $payload['business_email'] ?? null,
                'business_mobile' => $payload['business_mobile'],
                'business_contact' => $payload['business_contact'] ?? null,
                'business_contact_mobile' => $payload['business_contact_mobile'] ?? null,
                'test_mode' => !empty($gateway['test_mode']) ? 1 : 0,
                'is_active' => 1,
            ]);

            $this->activityModel->log($this->session['user_id'], 'create', 'Settings', 'Created Flutterwave subaccount: ' . $payload['business_name']);
            $this->setFlashMessage('success', 'Subaccount created successfully.');
            redirect('settings/flutterwave/subaccounts');
            return;
        }

        $banks = [];
        if ($service->isConfigured() && $entryMode === 'create') {
            $bankRes = $service->getBanks('NG');
            if (!empty($bankRes['success']) && is_array($bankRes['data'])) {
                $banks = $bankRes['data'];
            }
        }

        $this->loadView('settings/flutterwave_subaccount_form', [
            'page_title' => $entryMode === 'link' ? 'Link Flutterwave Subaccount' : 'Create Flutterwave Subaccount',
            'gateway' => $gateway,
            'subaccount' => null,
            'banks' => $banks,
            'entry_mode' => $entryMode,
            'flash' => $this->getFlashMessage(),
        ]);
    }

    public function flutterwaveSubaccountEdit($id) {
        $this->requirePermission('settings', 'update');
        $gateway = $this->requireFlutterwaveGateway();
        if (!$gateway) {
            return;
        }

        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');
        $subaccount = $subaccountModel->getById((int) $id);
        if (!$subaccount) {
            $this->setFlashMessage('danger', 'Subaccount not found.');
            redirect('settings/flutterwave/subaccounts');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $subaccountModel->update((int) $id, [
                'business_name' => sanitize_input($_POST['business_name'] ?? $subaccount['business_name']),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            ]);
            $this->setFlashMessage('success', 'Subaccount updated.');
            redirect('settings/flutterwave/subaccounts');
            return;
        }

        $this->loadView('settings/flutterwave_subaccount_form', [
            'page_title' => 'Edit Flutterwave Subaccount',
            'gateway' => $gateway,
            'subaccount' => $subaccount,
            'banks' => [],
            'flash' => $this->getFlashMessage(),
        ]);
    }

    public function flutterwaveSubaccountDelete($id) {
        $this->requirePermission('settings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('settings/flutterwave/subaccounts');
            return;
        }
        
        check_csrf();
        
        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');
        $row = $subaccountModel->getById((int) $id);
        if ($row) {
            $subaccountModel->update((int) $id, ['is_active' => 0, 'is_default' => 0]);
            $this->setFlashMessage('success', 'Subaccount deactivated locally.');
        }
        redirect('settings/flutterwave/subaccounts');
    }

    public function flutterwaveSubaccountSetDefault($id) {
        $this->requirePermission('settings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('settings/flutterwave/subaccounts');
            return;
        }
        
        check_csrf();
        
        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');
        $row = $subaccountModel->getById((int) $id);
        if ($row && !empty($row['is_active'])) {
            $subaccountModel->setDefault((int) $id);
            $this->setFlashMessage('success', 'Default subaccount updated.');
        }
        redirect('settings/flutterwave/subaccounts');
    }

    public function flutterwaveSplitRules() {
        $this->requirePermission('settings', 'read');
        $gateway = $this->requireFlutterwaveGateway();
        if (!$gateway) {
            return;
        }

        $ruleModel = $this->loadModel('Flutterwave_split_rule_model');
        $this->loadView('settings/flutterwave_split_rules', [
            'page_title' => 'Flutterwave Split Rules',
            'gateway' => $gateway,
            'rules' => $ruleModel->getAllWithSubaccount(),
            'flash' => $this->getFlashMessage(),
        ]);
    }

    public function flutterwaveSplitRuleCreate() {
        $this->requirePermission('settings', 'update');
        $gateway = $this->requireFlutterwaveGateway();
        if (!$gateway) {
            return;
        }

        $ruleModel = $this->loadModel('Flutterwave_split_rule_model');
        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');
        $propertyModel = $this->loadModel('Property_model');
        $spaceModel = $this->loadModel('Space_model');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $scopeType = sanitize_input($_POST['scope_type'] ?? 'global');
            if (!in_array($scopeType, ['global', 'property', 'space'], true)) {
                $scopeType = 'global';
            }
            $scopeId = ($scopeType === 'global') ? null : intval($_POST['scope_id'] ?? 0);
            if ($scopeType !== 'global' && $scopeId <= 0) {
                $this->setFlashMessage('danger', 'Please select a property or space for this rule.');
                redirect('settings/flutterwave/split-rules/create');
                return;
            }

            $ruleModel->create([
                'name' => sanitize_input($_POST['name'] ?? 'Split rule'),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'subaccount_row_id' => (int) ($_POST['subaccount_row_id'] ?? 0),
                'override_charge_type' => sanitize_input($_POST['override_charge_type'] ?? '') ?: null,
                'override_charge' => $_POST['override_charge'] !== '' ? (float) $_POST['override_charge'] : null,
                'split_ratio' => $_POST['split_ratio'] !== '' ? (int) $_POST['split_ratio'] : null,
                'priority' => (int) ($_POST['priority'] ?? 0),
                'currency' => strtoupper(sanitize_input($_POST['currency'] ?? '')) ?: null,
            ]);

            $this->setFlashMessage('success', 'Split rule created.');
            redirect('settings/flutterwave/split-rules');
            return;
        }

        $this->loadView('settings/flutterwave_split_rule_form', [
            'page_title' => 'Create Split Rule',
            'gateway' => $gateway,
            'rule' => null,
            'subaccounts' => $subaccountModel->getAllActive(),
            'properties' => $propertyModel->getAll(null, 0, ['property_name' => 'ASC']),
            'spaces' => $spaceModel->getAll(null, 0, ['space_name' => 'ASC']),
            'flash' => $this->getFlashMessage(),
        ]);
    }

    public function flutterwaveSplitRuleEdit($id) {
        $this->requirePermission('settings', 'update');
        $gateway = $this->requireFlutterwaveGateway();
        if (!$gateway) {
            return;
        }

        $ruleModel = $this->loadModel('Flutterwave_split_rule_model');
        $subaccountModel = $this->loadModel('Flutterwave_subaccount_model');
        $propertyModel = $this->loadModel('Property_model');
        $spaceModel = $this->loadModel('Space_model');
        $rule = $ruleModel->getById((int) $id);

        if (!$rule) {
            $this->setFlashMessage('danger', 'Split rule not found.');
            redirect('settings/flutterwave/split-rules');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();
            $scopeType = sanitize_input($_POST['scope_type'] ?? 'global');
            if (!in_array($scopeType, ['global', 'property', 'space'], true)) {
                $scopeType = 'global';
            }
            $scopeId = ($scopeType === 'global') ? null : intval($_POST['scope_id'] ?? 0);

            $ruleModel->update((int) $id, [
                'name' => sanitize_input($_POST['name'] ?? $rule['name']),
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'subaccount_row_id' => (int) ($_POST['subaccount_row_id'] ?? $rule['subaccount_row_id']),
                'override_charge_type' => sanitize_input($_POST['override_charge_type'] ?? '') ?: null,
                'override_charge' => $_POST['override_charge'] !== '' ? (float) $_POST['override_charge'] : null,
                'split_ratio' => $_POST['split_ratio'] !== '' ? (int) $_POST['split_ratio'] : null,
                'priority' => (int) ($_POST['priority'] ?? 0),
                'currency' => strtoupper(sanitize_input($_POST['currency'] ?? '')) ?: null,
            ]);

            $this->setFlashMessage('success', 'Split rule updated.');
            redirect('settings/flutterwave/split-rules');
            return;
        }

        $this->loadView('settings/flutterwave_split_rule_form', [
            'page_title' => 'Edit Split Rule',
            'gateway' => $gateway,
            'rule' => $rule,
            'subaccounts' => $subaccountModel->getAllActive(),
            'properties' => $propertyModel->getAll(null, 0, ['property_name' => 'ASC']),
            'spaces' => $spaceModel->getAll(null, 0, ['space_name' => 'ASC']),
            'flash' => $this->getFlashMessage(),
        ]);
    }

    public function flutterwaveSplitRuleDelete($id) {
        $this->requirePermission('settings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlashMessage('danger', 'Invalid request method.');
            redirect('settings/flutterwave/split-rules');
            return;
        }
        
        check_csrf();
        
        $ruleModel = $this->loadModel('Flutterwave_split_rule_model');
        $ruleModel->update((int) $id, ['is_active' => 0]);
        $this->setFlashMessage('success', 'Split rule deactivated.');
        redirect('settings/flutterwave/split-rules');
    }
}

