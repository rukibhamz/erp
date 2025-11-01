<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Taxes extends Base_Controller {
    private $taxModel;
    private $taxGroupModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('taxes', 'read');
        $this->taxModel = $this->loadModel('Tax_model');
        $this->taxGroupModel = $this->loadModel('Tax_group_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        try {
            $taxes = $this->taxModel->getActive();
        } catch (Exception $e) {
            $taxes = [];
        }

        $data = [
            'page_title' => 'Tax Rates',
            'taxes' => $taxes,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('taxes/index', $data);
    }

    public function create() {
        $this->requirePermission('taxes', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tax_name' => sanitize_input($_POST['tax_name'] ?? ''),
                'tax_code' => sanitize_input($_POST['tax_code'] ?? ''),
                'tax_type' => sanitize_input($_POST['tax_type'] ?? 'percentage'),
                'rate' => floatval($_POST['rate'] ?? 0),
                'tax_inclusive' => !empty($_POST['tax_inclusive']) ? 1 : 0,
                'description' => sanitize_input($_POST['description'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if ($this->taxModel->create($data)) {
                $this->activityModel->log($this->session['user_id'], 'create', 'Taxes', 'Created tax: ' . $data['tax_name']);
                $this->setFlashMessage('success', 'Tax rate created successfully.');
                redirect('taxes');
            } else {
                $this->setFlashMessage('danger', 'Failed to create tax rate.');
            }
        }

        $data = [
            'page_title' => 'Create Tax Rate',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('taxes/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('taxes', 'update');

        $tax = $this->taxModel->getById($id);
        if (!$tax) {
            $this->setFlashMessage('danger', 'Tax rate not found.');
            redirect('taxes');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'tax_name' => sanitize_input($_POST['tax_name'] ?? ''),
                'tax_code' => sanitize_input($_POST['tax_code'] ?? ''),
                'tax_type' => sanitize_input($_POST['tax_type'] ?? 'percentage'),
                'rate' => floatval($_POST['rate'] ?? 0),
                'tax_inclusive' => !empty($_POST['tax_inclusive']) ? 1 : 0,
                'description' => sanitize_input($_POST['description'] ?? ''),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if ($this->taxModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Taxes', 'Updated tax: ' . $data['tax_name']);
                $this->setFlashMessage('success', 'Tax rate updated successfully.');
                redirect('taxes');
            } else {
                $this->setFlashMessage('danger', 'Failed to update tax rate.');
            }
        }

        $data = [
            'page_title' => 'Edit Tax Rate',
            'tax' => $tax,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('taxes/edit', $data);
    }

    public function delete($id) {
        $this->requirePermission('taxes', 'delete');

        $tax = $this->taxModel->getById($id);
        if (!$tax) {
            $this->setFlashMessage('danger', 'Tax rate not found.');
            redirect('taxes');
        }

        if ($this->taxModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Taxes', 'Deleted tax: ' . $tax['tax_name']);
            $this->setFlashMessage('success', 'Tax rate deleted successfully.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete tax rate.');
        }

        redirect('taxes');
    }
}

