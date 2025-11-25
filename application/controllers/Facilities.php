<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Facilities extends Base_Controller {
    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
    }

    public function index() {
        redirect('locations');
    }

    public function view($id) {
        // Try to find if this facility is linked to a location/space
        // For now, just redirect to locations list
        redirect('locations');
    }

    public function create() {
        redirect('locations/create');
    }

    public function edit($id) {
        redirect('locations');
    }

    public function delete($id) {
        redirect('locations');
    }
}

