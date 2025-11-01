<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Facilities extends Base_Controller {
    private $facilityModel;
    private $activityModel;
    private $availabilityModel;
    private $blockoutModel;
    private $pricingModel;
    private $addonModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
        $this->facilityModel = $this->loadModel('Facility_model');
        $this->activityModel = $this->loadModel('Activity_model');
        $this->availabilityModel = $this->loadModel('Resource_availability_model');
        $this->blockoutModel = $this->loadModel('Resource_blockout_model');
        $this->pricingModel = $this->loadModel('Resource_pricing_model');
        $this->addonModel = $this->loadModel('Addon_model');
    }

    public function index() {
        $resourceType = $_GET['type'] ?? 'all';
        $category = $_GET['category'] ?? 'all';
        $status = $_GET['status'] ?? 'all';
        
        try {
            if ($resourceType !== 'all') {
                $facilities = $this->facilityModel->getByType($resourceType);
            } elseif ($category !== 'all') {
                $facilities = $this->facilityModel->getByCategory($category);
            } else {
                $facilities = $this->facilityModel->getActive();
            }
            
            // Filter by status if needed
            if ($status !== 'all') {
                $facilities = array_filter($facilities, function($f) use ($status) {
                    return ($f['status'] ?? 'available') === $status;
                });
            }
            
            // Get unique categories and types for filters
            $allFacilities = $this->facilityModel->getAll();
            $categories = array_unique(array_column($allFacilities, 'category'));
            $categories = array_filter($categories);
            $types = ['hall', 'meeting_room', 'equipment', 'vehicle', 'staff', 'other'];
        } catch (Exception $e) {
            $facilities = [];
            $categories = [];
            $types = [];
        }

        $data = [
            'page_title' => 'Resources & Facilities',
            'facilities' => $facilities,
            'categories' => $categories,
            'types' => $types,
            'selected_type' => $resourceType,
            'selected_category' => $category,
            'selected_status' => $status,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('facilities/index', $data);
    }

    public function create() {
        $this->requirePermission('bookings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'facility_code' => sanitize_input($_POST['facility_code'] ?? ''),
                'facility_name' => sanitize_input($_POST['facility_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'capacity' => intval($_POST['capacity'] ?? 0),
                'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
                'daily_rate' => floatval($_POST['daily_rate'] ?? 0),
                'weekend_rate' => floatval($_POST['weekend_rate'] ?? 0),
                'peak_rate' => floatval($_POST['peak_rate'] ?? 0),
                'security_deposit' => floatval($_POST['security_deposit'] ?? 0),
                'minimum_duration' => intval($_POST['minimum_duration'] ?? 1),
                'setup_time' => intval($_POST['setup_time'] ?? 0),
                'cleanup_time' => intval($_POST['cleanup_time'] ?? 0),
                'amenities' => json_encode($_POST['amenities'] ?? []),
                'features' => json_encode($_POST['features'] ?? []),
                'pricing_rules' => json_encode([
                    'peak_hours' => [
                        'start' => $_POST['peak_start'] ?? '17:00',
                        'end' => $_POST['peak_end'] ?? '22:00'
                    ]
                ]),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if (empty($data['facility_code'])) {
                $data['facility_code'] = $this->facilityModel->getNextFacilityCode();
            }

            if ($this->facilityModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Facilities', 'Created facility: ' . $data['facility_name']);
                $this->setFlashMessage('success', 'Facility created successfully.');
                redirect('facilities');
            } else {
                $this->setFlashMessage('danger', 'Failed to create facility.');
            }
        }

        $data = [
            'page_title' => 'Create Facility',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('facilities/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('bookings', 'update');

        try {
            $facility = $this->facilityModel->getWithPhotos($id);
            if (!$facility) {
                $this->setFlashMessage('danger', 'Facility not found.');
                redirect('facilities');
            }
        } catch (Exception $e) {
            $this->setFlashMessage('danger', 'Error loading facility.');
            redirect('facilities');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'facility_name' => sanitize_input($_POST['facility_name'] ?? ''),
                'description' => sanitize_input($_POST['description'] ?? ''),
                'capacity' => intval($_POST['capacity'] ?? 0),
                'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
                'daily_rate' => floatval($_POST['daily_rate'] ?? 0),
                'weekend_rate' => floatval($_POST['weekend_rate'] ?? 0),
                'peak_rate' => floatval($_POST['peak_rate'] ?? 0),
                'security_deposit' => floatval($_POST['security_deposit'] ?? 0),
                'minimum_duration' => intval($_POST['minimum_duration'] ?? 1),
                'setup_time' => intval($_POST['setup_time'] ?? 0),
                'cleanup_time' => intval($_POST['cleanup_time'] ?? 0),
                'amenities' => json_encode($_POST['amenities'] ?? []),
                'features' => json_encode($_POST['features'] ?? []),
                'pricing_rules' => json_encode([
                    'peak_hours' => [
                        'start' => $_POST['peak_start'] ?? '17:00',
                        'end' => $_POST['peak_end'] ?? '22:00'
                    ]
                ]),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if ($this->facilityModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Facilities', 'Updated facility: ' . $data['facility_name']);
                $this->setFlashMessage('success', 'Facility updated successfully.');
                redirect('facilities');
            } else {
                $this->setFlashMessage('danger', 'Failed to update facility.');
            }
        }

        $data = [
            'page_title' => 'Edit Facility',
            'facility' => $facility,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('facilities/edit', $data);
    }
}

