<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Currencies extends Base_Controller {
    private $currencyModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('settings', 'read');
        $this->currencyModel = $this->loadModel('Currency_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        try {
            $currencies = $this->currencyModel->getActive();
        } catch (Exception $e) {
            $currencies = [];
        }

        $data = [
            'page_title' => 'Currencies',
            'currencies' => $currencies,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('currencies/index', $data);
    }

    public function create() {
        $this->requirePermission('settings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'currency_code' => strtoupper(sanitize_input($_POST['currency_code'] ?? '')),
                'currency_name' => sanitize_input($_POST['currency_name'] ?? ''),
                'symbol' => sanitize_input($_POST['symbol'] ?? ''),
                'exchange_rate' => floatval($_POST['exchange_rate'] ?? 1.0),
                'is_base' => !empty($_POST['is_base']) ? 1 : 0,
                'position' => sanitize_input($_POST['position'] ?? 'before'),
                'precision' => intval($_POST['precision'] ?? 2),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if ($this->currencyModel->create($data)) {
                if ($data['is_base']) {
                    // Set as base currency
                    $currency = $this->currencyModel->getByCode($data['currency_code']);
                    if ($currency) {
                        $this->currencyModel->setBaseCurrency($currency['id']);
                    }
                }
                
                $this->activityModel->log($this->session['user_id'], 'create', 'Currencies', 'Created currency: ' . $data['currency_code']);
                $this->setFlashMessage('success', 'Currency created successfully.');
                redirect('currencies');
            } else {
                $this->setFlashMessage('danger', 'Failed to create currency.');
            }
        }

        $data = [
            'page_title' => 'Add Currency',
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('currencies/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('settings', 'update');

        $currency = $this->currencyModel->getById($id);
        if (!$currency) {
            $this->setFlashMessage('danger', 'Currency not found.');
            redirect('currencies');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'currency_code' => strtoupper(sanitize_input($_POST['currency_code'] ?? '')),
                'currency_name' => sanitize_input($_POST['currency_name'] ?? ''),
                'symbol' => sanitize_input($_POST['symbol'] ?? ''),
                'exchange_rate' => floatval($_POST['exchange_rate'] ?? 1.0),
                'is_base' => !empty($_POST['is_base']) ? 1 : 0,
                'position' => sanitize_input($_POST['position'] ?? 'before'),
                'precision' => intval($_POST['precision'] ?? 2),
                'status' => sanitize_input($_POST['status'] ?? 'active')
            ];

            if ($this->currencyModel->update($id, $data)) {
                if ($data['is_base']) {
                    $this->currencyModel->setBaseCurrency($id);
                }
                
                $this->activityModel->log($this->session['user_id'], 'update', 'Currencies', 'Updated currency: ' . $data['currency_code']);
                $this->setFlashMessage('success', 'Currency updated successfully.');
                redirect('currencies');
            } else {
                $this->setFlashMessage('danger', 'Failed to update currency.');
            }
        }

        $data = [
            'page_title' => 'Edit Currency',
            'currency' => $currency,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('currencies/edit', $data);
    }

    public function rates() {
        $this->requirePermission('settings', 'read');

        try {
            $currencies = $this->currencyModel->getActive();
            $baseCurrency = $this->currencyModel->getBaseCurrency();
        } catch (Exception $e) {
            $currencies = [];
            $baseCurrency = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fromCurrency = sanitize_input($_POST['from_currency'] ?? '');
            $toCurrency = sanitize_input($_POST['to_currency'] ?? '');
            $rate = floatval($_POST['rate'] ?? 1.0);
            $date = sanitize_input($_POST['rate_date'] ?? date('Y-m-d'));

            if ($this->currencyModel->updateExchangeRate($fromCurrency, $toCurrency, $rate, $date)) {
                $this->setFlashMessage('success', 'Exchange rate updated successfully.');
                redirect('currencies/rates');
            } else {
                $this->setFlashMessage('danger', 'Failed to update exchange rate.');
            }
        }

        $data = [
            'page_title' => 'Exchange Rates',
            'currencies' => $currencies,
            'base_currency' => $baseCurrency,
            'flash' => $this->getFlashMessage()
        ];

        $this->loadView('currencies/rates', $data);
    }
}

