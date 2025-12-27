<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Facilities Controller
 * 
 * @deprecated This controller is deprecated and redirects to Locations.
 * Facilities management has been consolidated into the Locations module.
 * Use Locations controller for managing properties and spaces.
 * 
 * @see Locations
 * @see Spaces
 */
class Facilities extends Base_Controller {
    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'read');
    }

    /**
     * @deprecated Use Locations::index() instead
     */
    public function index() {
        $this->setFlashMessage('info', 'Facilities have been moved to Locations. You are being redirected.');
        redirect('locations');
    }

    /**
     * @deprecated Use Locations::view() or Spaces::view() instead
     */
    public function view($id) {
        $this->setFlashMessage('info', 'Facilities have been moved to Locations. You are being redirected.');
        redirect('locations');
    }

    /**
     * @deprecated Use Locations::create() or Spaces::create() instead
     */
    public function create() {
        $this->setFlashMessage('info', 'Facilities have been moved to Locations. You are being redirected.');
        redirect('locations/create');
    }

    /**
     * @deprecated Use Locations::edit() or Spaces::edit() instead
     */
    public function edit($id) {
        $this->setFlashMessage('info', 'Facilities have been moved to Locations. You are being redirected.');
        redirect('locations');
    }

    /**
     * @deprecated Use Locations::delete() or Spaces::delete() instead
     */
    public function delete($id) {
        $this->setFlashMessage('info', 'Facilities have been moved to Locations. You are being redirected.');
        redirect('locations');
    }
}
