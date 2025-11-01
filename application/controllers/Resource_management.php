<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Resource_management extends Base_Controller {
    private $facilityModel;
    private $availabilityModel;
    private $blockoutModel;
    private $pricingModel;
    private $addonModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->availabilityModel = $this->loadModel('Resource_availability_model');
        $this->blockoutModel = $this->loadModel('Resource_blockout_model');
        $this->pricingModel = $this->loadModel('Resource_pricing_model');
        $this->addonModel = $this->loadModel('Addon_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    /**
     * Manage resource availability
     */
    public function availability($resourceId) {
        $this->requirePermission('bookings', 'update');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $days = [0, 1, 2, 3, 4, 5, 6]; // Sunday to Saturday
            
            foreach ($days as $day) {
                $isAvailable = !empty($_POST['available_' . $day]);
                $startTime = sanitize_input($_POST['start_time_' . $day] ?? '');
                $endTime = sanitize_input($_POST['end_time_' . $day] ?? '');
                $breakStart = sanitize_input($_POST['break_start_' . $day] ?? '');
                $breakEnd = sanitize_input($_POST['break_end_' . $day] ?? '');
                
                $this->availabilityModel->setDayAvailability(
                    $resourceId,
                    $day,
                    $isAvailable,
                    $startTime ?: null,
                    $endTime ?: null,
                    $breakStart ?: null,
                    $breakEnd ?: null
                );
            }
            
            $this->activityModel->log($this->session['user_id'], 'update', 'Bookings', 'Updated availability for resource ID: ' . $resourceId);
            $this->setFlashMessage('success', 'Availability updated successfully.');
            redirect('resource-management/availability/' . $resourceId);
        }
        
        try {
            $resource = $this->facilityModel->getById($resourceId);
            if (!$resource) {
                $this->setFlashMessage('danger', 'Resource not found.');
                redirect('facilities');
            }
            
            $availability = $this->availabilityModel->getByResource($resourceId);
            $availabilityMap = [];
            foreach ($availability as $avail) {
                $availabilityMap[$avail['day_of_week']] = $avail;
            }
        } catch (Exception $e) {
            $resource = null;
            $availabilityMap = [];
        }
        
        $data = [
            'page_title' => 'Manage Availability: ' . ($resource['facility_name'] ?? ''),
            'resource' => $resource,
            'availability_map' => $availabilityMap,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('resource_management/availability', $data);
    }

    /**
     * Manage resource blockouts
     */
    public function blockouts($resourceId) {
        $this->requirePermission('bookings', 'read');
        
        try {
            $resource = $this->facilityModel->getById($resourceId);
            if (!$resource) {
                $this->setFlashMessage('danger', 'Resource not found.');
                redirect('facilities');
            }
            
            $blockouts = $this->blockoutModel->getByResource($resourceId);
        } catch (Exception $e) {
            $resource = null;
            $blockouts = [];
        }
        
        $data = [
            'page_title' => 'Blockouts: ' . ($resource['facility_name'] ?? ''),
            'resource' => $resource,
            'blockouts' => $blockouts,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('resource_management/blockouts', $data);
    }

    /**
     * Add blockout
     */
    public function addBlockout() {
        $this->requirePermission('bookings', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'resource_id' => intval($_POST['resource_id'] ?? 0),
                'start_date' => sanitize_input($_POST['start_date'] ?? ''),
                'end_date' => sanitize_input($_POST['end_date'] ?? ''),
                'start_time' => sanitize_input($_POST['start_time'] ?? null),
                'end_time' => sanitize_input($_POST['end_time'] ?? null),
                'reason' => sanitize_input($_POST['reason'] ?? ''),
                'notes' => sanitize_input($_POST['notes'] ?? ''),
                'created_by' => $this->session['user_id']
            ];
            
            if ($this->blockoutModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Added blockout for resource ID: ' . $data['resource_id']);
                $this->setFlashMessage('success', 'Blockout added successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to add blockout.');
            }
            
            redirect('resource-management/blockouts/' . $data['resource_id']);
        }
    }

    /**
     * Delete blockout
     */
    public function deleteBlockout($id) {
        $this->requirePermission('bookings', 'delete');
        
        try {
            $blockout = $this->blockoutModel->getById($id);
            if ($blockout && $this->blockoutModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Bookings', 'Deleted blockout ID: ' . $id);
                $this->setFlashMessage('success', 'Blockout deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete blockout.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error deleting blockout.');
        }
        
        if (isset($blockout)) {
            redirect('resource-management/blockouts/' . $blockout['resource_id']);
        } else {
            redirect('facilities');
        }
    }

    /**
     * Manage resource pricing
     */
    public function pricing($resourceId) {
        $this->requirePermission('bookings', 'read');
        
        try {
            $resource = $this->facilityModel->getById($resourceId);
            if (!$resource) {
                $this->setFlashMessage('danger', 'Resource not found.');
                redirect('facilities');
            }
            
            $pricingRules = $this->pricingModel->getByResource($resourceId);
        } catch (Exception $e) {
            $resource = null;
            $pricingRules = [];
        }
        
        $data = [
            'page_title' => 'Pricing Rules: ' . ($resource['facility_name'] ?? ''),
            'resource' => $resource,
            'pricing_rules' => $pricingRules,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('resource_management/pricing', $data);
    }

    /**
     * Add pricing rule
     */
    public function addPricing() {
        $this->requirePermission('bookings', 'create');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'resource_id' => intval($_POST['resource_id'] ?? 0),
                'rate_type' => sanitize_input($_POST['rate_type'] ?? 'hourly'),
                'price' => floatval($_POST['price'] ?? 0),
                'peak_price' => !empty($_POST['peak_price']) ? floatval($_POST['peak_price']) : null,
                'member_price' => !empty($_POST['member_price']) ? floatval($_POST['member_price']) : null,
                'start_date' => !empty($_POST['start_date']) ? sanitize_input($_POST['start_date']) : null,
                'end_date' => !empty($_POST['end_date']) ? sanitize_input($_POST['end_date']) : null,
                'day_of_week' => !empty($_POST['day_of_week']) ? intval($_POST['day_of_week']) : null,
                'is_seasonal' => !empty($_POST['is_seasonal']) ? 1 : 0,
                'season_name' => !empty($_POST['season_name']) ? sanitize_input($_POST['season_name']) : null,
                'min_duration' => !empty($_POST['min_duration']) ? intval($_POST['min_duration']) : null,
                'max_duration' => !empty($_POST['max_duration']) ? intval($_POST['max_duration']) : null,
                'quantity_discount' => !empty($_POST['quantity_discount']) ? $_POST['quantity_discount'] : null,
                'duration_discount' => !empty($_POST['duration_discount']) ? $_POST['duration_discount'] : null
            ];
            
            if ($this->pricingModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Added pricing rule for resource ID: ' . $data['resource_id']);
                $this->setFlashMessage('success', 'Pricing rule added successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to add pricing rule.');
            }
            
            redirect('resource-management/pricing/' . $data['resource_id']);
        }
    }

    /**
     * Delete pricing rule
     */
    public function deletePricing($id) {
        $this->requirePermission('bookings', 'delete');
        
        try {
            $pricing = $this->pricingModel->getById($id);
            if ($pricing && $this->pricingModel->delete($id)) {
                $this->activityModel->log($this->session['user_id'], 'delete', 'Bookings', 'Deleted pricing rule ID: ' . $id);
                $this->setFlashMessage('success', 'Pricing rule deleted successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to delete pricing rule.');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error deleting pricing rule.');
        }
        
        if (isset($pricing)) {
            redirect('resource-management/pricing/' . $pricing['resource_id']);
        } else {
            redirect('facilities');
        }
    }

    /**
     * Manage addons for a resource
     */
    public function addons($resourceId = null) {
        $this->requirePermission('bookings', 'read');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePermission('bookings', 'create');
            
            $data = [
                'name' => sanitize_input($_POST['name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'addon_type' => sanitize_input($_POST['addon_type'] ?? 'other'),
                'price' => floatval($_POST['price'] ?? 0),
                'resource_id' => !empty($_POST['resource_id']) ? intval($_POST['resource_id']) : null,
                'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                'display_order' => intval($_POST['display_order'] ?? 0)
            ];
            
            if ($this->addonModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Bookings', 'Created addon: ' . $data['name']);
                $this->setFlashMessage('success', 'Addon created successfully.');
            } else {
                $this->setFlashMessage('danger', 'Failed to create addon.');
            }
            
            redirect('resource-management/addons' . ($resourceId ? '/' . $resourceId : ''));
        }
        
        try {
            if ($resourceId) {
                $resource = $this->facilityModel->getById($resourceId);
                $addons = $this->addonModel->getActive($resourceId);
            } else {
                $resource = null;
                $addons = $this->addonModel->getActive();
            }
        } catch (Exception $e) {
            $resource = null;
            $addons = [];
        }
        
        $data = [
            'page_title' => 'Manage Add-ons',
            'resource' => $resource,
            'addons' => $addons,
            'flash' => $this->getFlashMessage()
        ];
        
        $this->loadView('resource_management/addons', $data);
    }
}

