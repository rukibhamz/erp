<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Promo_codes extends Base_Controller {
    private $promoModel;
    private $activityModel;

    public function __construct() {
        parent::__construct();
        $this->requirePermission('bookings', 'write');
        $this->promoModel = $this->loadModel('Promo_code_model');
        $this->activityModel = $this->loadModel('Activity_model');
    }

    public function index() {
        $codes = $this->promoModel->getAll(null, 0, 'created_at DESC');
        $data = [
            'page_title' => 'Promo Codes',
            'codes'      => $codes,
            'flash'      => $this->getFlashMessage()
        ];
        $this->loadView('promo_codes/index', $data);
    }

    public function create() {
        $this->requirePermission('bookings', 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();

            $code = strtoupper(sanitize_input($_POST['code'] ?? ''));
            if (empty($code)) {
                $code = strtoupper(bin2hex(random_bytes(4))); // auto-generate
            }

            $data = [
                'code'             => $code,
                'description'      => sanitize_input($_POST['description'] ?? ''),
                'discount_type'    => in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed']) ? $_POST['discount_type'] : 'percentage',
                'discount_value'   => floatval($_POST['discount_value'] ?? 0),
                'minimum_amount'   => !empty($_POST['minimum_amount']) ? floatval($_POST['minimum_amount']) : null,
                'maximum_discount' => !empty($_POST['maximum_discount']) ? floatval($_POST['maximum_discount']) : null,
                'valid_from'       => sanitize_input($_POST['valid_from'] ?? date('Y-m-d')),
                'valid_to'         => sanitize_input($_POST['valid_to'] ?? date('Y-m-d', strtotime('+30 days'))),
                'usage_limit'      => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
                'applicable_to'    => in_array($_POST['applicable_to'] ?? '', ['all', 'resource', 'addon']) ? $_POST['applicable_to'] : 'all',
                'apply_to_addons'  => isset($_POST['apply_to_addons']) ? 1 : 0,
                'is_active'        => 1,
                'used_count'       => 0,
            ];

            if ($data['discount_value'] <= 0) {
                $this->setFlashMessage('danger', 'Discount value must be greater than zero.');
                redirect('promo-codes/create');
                return;
            }

            if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
                $this->setFlashMessage('danger', 'Percentage discount cannot exceed 100%.');
                redirect('promo-codes/create');
                return;
            }

            try {
                $id = $this->promoModel->create($data);
                if ($id) {
                    $this->activityModel->log($this->session['user_id'], 'create', 'Promo Codes', 'Created promo code: ' . $code);
                    $this->setFlashMessage('success', 'Promo code "' . $code . '" created successfully.');
                    redirect('promo-codes');
                } else {
                    $this->setFlashMessage('danger', 'Failed to create promo code. Code may already exist.');
                }
            } catch (Exception $e) {
                error_log('Promo_codes create error: ' . $e->getMessage());
                $this->setFlashMessage('danger', 'Error: ' . $e->getMessage());
            }
        }

        $data = [
            'page_title' => 'Create Promo Code',
            'flash'      => $this->getFlashMessage()
        ];
        $this->loadView('promo_codes/create', $data);
    }

    public function edit($id) {
        $this->requirePermission('bookings', 'update');

        $code = $this->promoModel->getById($id);
        if (!$code) {
            $this->setFlashMessage('danger', 'Promo code not found.');
            redirect('promo-codes');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_csrf();

            $data = [
                'description'      => sanitize_input($_POST['description'] ?? ''),
                'discount_type'    => in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed']) ? $_POST['discount_type'] : 'percentage',
                'discount_value'   => floatval($_POST['discount_value'] ?? 0),
                'minimum_amount'   => !empty($_POST['minimum_amount']) ? floatval($_POST['minimum_amount']) : null,
                'maximum_discount' => !empty($_POST['maximum_discount']) ? floatval($_POST['maximum_discount']) : null,
                'valid_from'       => sanitize_input($_POST['valid_from'] ?? $code['valid_from']),
                'valid_to'         => sanitize_input($_POST['valid_to'] ?? $code['valid_to']),
                'usage_limit'      => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
                'applicable_to'    => in_array($_POST['applicable_to'] ?? '', ['all', 'resource', 'addon']) ? $_POST['applicable_to'] : 'all',
                'apply_to_addons'  => isset($_POST['apply_to_addons']) ? 1 : 0,
                'is_active'        => isset($_POST['is_active']) ? 1 : 0,
            ];

            if ($this->promoModel->update($id, $data)) {
                $this->activityModel->log($this->session['user_id'], 'update', 'Promo Codes', 'Updated promo code: ' . $code['code']);
                $this->setFlashMessage('success', 'Promo code updated.');
                redirect('promo-codes');
            } else {
                $this->setFlashMessage('danger', 'Failed to update promo code.');
            }
        }

        $data = [
            'page_title' => 'Edit Promo Code: ' . $code['code'],
            'code'       => $code,
            'flash'      => $this->getFlashMessage()
        ];
        $this->loadView('promo_codes/edit', $data);
    }

    public function toggle($id) {
        $this->requirePermission('bookings', 'update');
        check_csrf();

        $code = $this->promoModel->getById($id);
        if ($code) {
            $this->promoModel->update($id, ['is_active' => $code['is_active'] ? 0 : 1]);
            $status = $code['is_active'] ? 'deactivated' : 'activated';
            $this->activityModel->log($this->session['user_id'], 'update', 'Promo Codes', ucfirst($status) . ' promo code: ' . $code['code']);
            $this->setFlashMessage('success', 'Promo code ' . $status . '.');
        }
        redirect('promo-codes');
    }

    public function delete($id) {
        $this->requirePermission('bookings', 'delete');
        check_csrf();

        $code = $this->promoModel->getById($id);
        if ($code && $this->promoModel->delete($id)) {
            $this->activityModel->log($this->session['user_id'], 'delete', 'Promo Codes', 'Deleted promo code: ' . $code['code']);
            $this->setFlashMessage('success', 'Promo code deleted.');
        } else {
            $this->setFlashMessage('danger', 'Failed to delete promo code.');
        }
        redirect('promo-codes');
    }
}
