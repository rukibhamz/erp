<?php
defined('BASEPATH') OR exit('No direct script access allowed');

defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_Debug extends Base_Controller {
    
    // Override auth check to allow public access for debugging
    protected function checkAuth() {
        return true;
    }

    public function index() {
        echo "<h1>Agent Debugger</h1>";
        
        $spaceId = isset($_GET['space_id']) ? intval($_GET['space_id']) : 1;
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        echo "<h3>Testing logic for Space ID: $spaceId, Date: $date</h3>";
        
        try {
            // Load Models using Base_Controller method
            $this->facilityModel = $this->loadModel('Facility_model');
            $this->spaceModel = $this->loadModel('Space_model');
            
            // 1. Get Space
            $space = $this->spaceModel->getWithProperty($spaceId);
            if (!$space) {
                die("Space not found.");
            }
            echo "Found Space: " . $space['space_name'] . "<br>";
            
            // 2. Sync Logic
            $facilityId = $space['facility_id'] ?? null;
            echo "Linked Facility ID: " . ($facilityId ? $facilityId : "NULL (Will attempt sync)") . "<br>";
            
            if (!$facilityId) {
                echo "Attempting sync...<br>";
                $facilityId = $this->spaceModel->syncToBookingModule($spaceId);
                echo "Sync result ID: " . ($facilityId ? $facilityId : "FAILED") . "<br>";
            }
            
            if (!$facilityId) {
                die("Critical: No Facility ID available.");
            }
            
            // 3. Call Facility Model
            echo "Calling Facility_model->getAvailableTimeSlots...<br>";
            // Ensure db is connected
            if (!$this->db) {
                die("DB Not initialized in Controller.");
            }
            
            $result = $this->facilityModel->getAvailableTimeSlots($facilityId, $date, $date);
            
            echo "<h4>Result:</h4>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
        } catch (Exception $e) {
            echo "<strong style='color:red'>Exception caught:</strong> " . $e->getMessage() . "<br>";
            echo "Trace: " . $e->getTraceAsString();
        }
    }
}
