<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_Debug extends CI_Controller {
    public function index() {
        echo "<h1>Agent Debugger</h1>";
        
        $spaceId = $this->input->get('space_id') ? $this->input->get('space_id') : 1;
        $date = $this->input->get('date') ? $this->input->get('date') : date('Y-m-d');
        
        // Load dependencies manually to mimic Booking_wizard
        $this->load->model('Facility_model');
        $this->load->model('Space_model');
        
        echo "<h3>Testing logic for Space ID: $spaceId, Date: $date</h3>";
        
        try {
            // 1. Get Space
            $space = $this->Space_model->getWithProperty($spaceId);
            if (!$space) {
                die("Space not found.");
            }
            echo "Found Space: " . $space['space_name'] . "<br>";
            
            // 2. Sync Logic
            $facilityId = $space['facility_id'] ?? null;
            echo "Linked Facility ID: " . ($facilityId ? $facilityId : "NULL (Will attempt sync)") . "<br>";
            
            if (!$facilityId) {
                echo "Attempting sync...<br>";
                $facilityId = $this->Space_model->syncToBookingModule($spaceId);
                echo "Sync result ID: " . ($facilityId ? $facilityId : "FAILED") . "<br>";
            }
            
            if (!$facilityId) {
                die("Critical: No Facility ID available.");
            }
            
            // 3. Call Facility Model
            echo "Calling Facility_model->getAvailableTimeSlots...<br>";
            $result = $this->Facility_model->getAvailableTimeSlots($facilityId, $date, $date);
            
            echo "<h4>Result:</h4>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
        } catch (Exception $e) {
            echo "<strong style='color:red'>Exception caught:</strong> " . $e->getMessage() . "<br>";
            echo "Trace: " . $e->getTraceAsString();
        }
    }
}
