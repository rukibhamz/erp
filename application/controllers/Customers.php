<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customers Controller
 * 
 * Redirects to Receivables module for customer management.
 * This is a compatibility layer for older links or navigation.
 */
class Customers extends Base_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission('receivables', 'read');
    }
    
    public function index() {
        redirect('receivables/customers');
    }
    
    public function create() {
        redirect('receivables/createCustomer');
    }
    
    public function view($id = null) {
        if ($id) {
            redirect('receivables/viewCustomer/' . $id);
        }
        redirect('receivables/customers');
    }
    
    public function edit($id = null) {
        if ($id) {
            redirect('receivables/editCustomer/' . $id);
        }
        redirect('receivables/customers');
    }
}
