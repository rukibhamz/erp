<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Debug extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Facility_model');
        $this->load->helper('url');
    }

    public function index() {
        echo "<h1>Debug Diagnosis</h1>";
        
        // 1. Check Spaces
        echo "<h2>1. Spaces Config</h2>";
        $spaces = $this->db->limit(5)->get('spaces')->result();
        foreach ($spaces as $s) {
            echo "Space ID: {$s->id} - {$s->space_name}<br>";
            // Check Bookable Config
            $config = $this->db->where('space_id', $s->id)->get('bookable_config')->row();
            if ($config) {
                echo "=> Config found. Rules: " . htmlspecialchars($config->availability_rules) . "<br>";
            } else {
                echo "=> NO bookable_config found.<br>";
            }
        }

        // 2. Check Facilities
        echo "<h2>2. Facilities Config</h2>";
        $facilities = $this->db->limit(5)->get('facilities')->result();
        foreach ($facilities as $f) {
            echo "Facility ID: {$f->id} - {$f->facility_name} (Linked Space: {$f->space_id})<br>";
            
            // Resource Availability
            $resAvail = $this->db->where('resource_id', $f->id)->get('resource_availability')->result();
            if ($resAvail) {
                echo "=> Resource Availability: " . count($resAvail) . " entries.<br>";
                foreach ($resAvail as $ra) {
                   echo "&nbsp;&nbsp;Day {$ra->day_of_week}: Open {$ra->start_time} - {$ra->end_time} (Avail: {$ra->is_available})<br>";
                }
            } else {
                echo "=> No specific resource availability overrides.<br>";
            }
        }

        // 3. Test Availability Logic
        echo "<h2>3. Testing Logic for Fac ID 1, Date 2025-12-31</h2>";
        // Dynamic test if GET params provided
        $fid = $this->input->get('id') ? $this->input->get('id') : ($facilities[0]->id ?? 1);
        $date = $this->input->get('date') ? $this->input->get('date') : '2025-12-31'; // yyyy-mm-dd
        $end = $this->input->get('end') ? $this->input->get('end') : $date;
        
        echo "Running getAvailableTimeSlots($fid, '$date', '$end')...<br>";
        
        try {
            $result = $this->Facility_model->getAvailableTimeSlots($fid, $date, $end);
            
            if ($result['success']) {
                echo "Success! Found " . count($result['slots']) . " slots.<br>";
                echo "Occupied: " . count($result['occupied']) . "<br>";
                if (empty($result['slots'])) {
                    echo "<strong style='color:red'>Slots array is empty! Logic returned success but no slots.</strong><br>";
                } else {
                    echo "First slot: " . print_r($result['slots'][0], true) . "<br>";
                }
            } else {
                echo "<strong style='color:red'>Failed: " . $result['message'] . "</strong><br>";
            }
            
            // Dump detailed result
            echo "<details><summary>Full Result Dump</summary><pre>" . print_r($result, true) . "</pre></details>";
            
        } catch (Exception $e) {
            echo "Exception: " . $e->getMessage();
        }
        
        // 4. Check Bookings on that date
        echo "<h2>4. Existing Bookings on $date</h2>";
        $bookings = $this->db->where('facility_id', $fid)
                             ->group_start()
                                ->where('booking_date', $date)
                                ->or_group_start()
                                    ->where('booking_date <=', $end)
                                    ->where('booking_date >=', $date)
                                ->group_end()
                             ->group_end()
                             ->get('bookings')->result();
        
        echo "Found " . count($bookings) . " raw bookings in DB.<br>";
        foreach ($bookings as $b) {
            echo "Booking #{$b->booking_number}: {$b->start_time} - {$b->end_time} (Status: {$b->status})<br>";
        }
    }
}
